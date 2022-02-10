<?php

namespace Vemcogroup\Translation;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Vemcogroup\Translation\Exceptions\POEditorException;
use const DIRECTORY_SEPARATOR;

class Translation
{
    protected $apiKey;
    protected $projectId;
    protected $baseLanguage;
    protected $baseFilename;

    public function __construct()
    {
        $this->baseLanguage = config('translation.base_language');
        $this->baseFilename = app()->langPath() . DIRECTORY_SEPARATOR . $this->baseLanguage . '.json';
    }

    public function scan($mergeKeys = false): int
    {
        $allMatches = [];
        $finder = new Finder();

        $finder->in(base_path())
            ->exclude(config('translation.excluded_directories'))
            ->name(config('translation.extensions'))
            ->followLinks()
            ->files();

        /*
         * This pattern is derived from Barryvdh\TranslationManager by Barry vd. Heuvel <barryvdh@gmail.com>
         *
         * https://github.com/barryvdh/laravel-translation-manager/blob/master/src/Manager.php
         */
        $functions = config('translation.functions');
        $pattern =
            // See https://regex101.com/r/jS5fX0/5
            '[^\w]' . // Must not start with any alphanum or _
            '(?<!->)' . // Must not start with ->
            '(' . implode('|', $functions) . ')' . // Must start with one of the functions
            "\(" . // Match opening parentheses
            "\s*" . // Allow whitespace chars after the opening parenthese
            "[\'\"]" . // Match " or '
            '(' . // Start a new group to match:
            '.+' . // Must start with group
            ')' . // Close group
            "[\'\"]" . // Closing quote
            "\s*" . // Allow whitespace chars before the closing parenthese
            "[\),]"  // Close parentheses or new parameter
        ;

        foreach ($finder as $file) {
            if (preg_match_all("/$pattern/siU", $file->getContents(), $matches)) {
                $allMatches[$file->getRelativePathname()] = $matches[2];
            }
        }

        $collapsedKeys = collect($allMatches)->collapse();
        $keys = $collapsedKeys->combine($collapsedKeys);

        if ($mergeKeys) {
            $content = $this->getFileContent();
            $keys = $content->union(
                $keys->filter(function ($key) use ($content) {
                    return !$content->has($key);
                })
            );
        }

        file_put_contents($this->baseFilename, json_encode($keys->sortKeys(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $keys->count();
    }

    public function createJs(): int
    {
        $jsLangPath = public_path('build/lang');
        if (!is_dir($jsLangPath) && !mkdir($jsLangPath, 0777, true)) {
            throw POEditorException::unableToCreateJsDirectory($jsLangPath);
        }

        $translations = $this->getTranslations();
        $translations->each(function ($content, $language) use ($jsLangPath) {
            $content = 'window.i18n = ' . json_encode($content) . ';';
            file_put_contents($jsLangPath . DIRECTORY_SEPARATOR . $language . '.js', $content);
        });

        return $translations->count();
    }

    public function download(): Collection
    {
        try {
            $this->setupPoeditorCredentials();
            $response = $this->query('https://api.poeditor.com/v2/languages/list', [
                'form_params' => [
                    'api_token' => $this->apiKey,
                    'id' => $this->projectId
                ]
            ], 'POST');
        } catch (Exception $e) {
            throw $e;
        }

        $languages = collect($response['result']['languages']);
        $languages->each(function ($language) {
            $response = $this->query('https://api.poeditor.com/v2/projects/export', [
                'form_params' => [
                    'api_token' => $this->apiKey,
                    'id' => $this->projectId,
                    'language' => $language['code'],
                    'type' => 'key_value_json'
                ]
            ], 'POST');

            $content = collect($this->query($response['result']['url']))
                ->mapWithKeys(function ($entry, $key) {
                    return is_array($entry) ? [trim(array_key_first($entry)) => array_pop($entry)] : [$key => trim($entry)];
                })
                ->sortKeys()
                ->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            file_put_contents(app()->langPath() . DIRECTORY_SEPARATOR . $language['code'] . '.json', $content);
        });

        return $languages->pluck('code');
    }

    public function syncTerms(): void
    {
        try {
            $this->setupPoeditorCredentials();
            $entries = $this->getFileContent()
                ->map(function ($value, $key) {
                    return ['term' => $key];
                })
                ->toJson();

            $this->query('https://api.poeditor.com/v2/projects/sync', [
                'form_params' => [
                    'api_token' => $this->apiKey,
                    'id' => $this->projectId,
                    'data' => $entries,
                ]
            ], 'POST');
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function addTerms(): void
    {
        try {
            $this->setupPoeditorCredentials();
            $entries = $this->getFileContent()
                ->map(function ($value, $key) {
                    return ['term' => $key];
                })
                ->toJson();

            $this->query('https://api.poeditor.com/v2/terms/add', [
                'form_params' => [
                    'api_token' => $this->apiKey,
                    'id' => $this->projectId,
                    'data' => $entries,
                ]
            ], 'POST');
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function syncTranslations(?array $languages = null): void
    {
        try {
            $this->setupPoeditorCredentials();
            $translations = $this->getTranslations($languages);

            foreach ($translations as $language => $entries) {
                $json = collect($entries)
                    ->mapToGroups(static function ($value, $key) {
                        return [[
                            'term' => $key,
                            'translation' => [
                                'content' => $value,
                            ],
                        ]];
                    })
                    ->first()
                    ->toJson();

                $this->query('https://api.poeditor.com/v2/translations/update', [
                    'form_params' => [
                        'api_token' => $this->apiKey,
                        'id' => $this->projectId,
                        'language' => $language,
                        'data' => $json,
                    ]
                ], 'POST');
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function setupPoeditorCredentials(): void
    {
        if (!$this->apiKey = config('translation.api_key')) {
            throw POEditorException::noApiKey();
        }

        if (!$this->projectId = config('translation.project_id')) {
            throw POEditorException::noProjectId();
        }
    }

    protected function getFileContent(): Collection
    {
        return file_exists($this->baseFilename)
            ? collect(json_decode(file_get_contents($this->baseFilename), true))
            : collect();
    }

    protected function getTranslations(?array $languages = null): Collection
    {
        $namePattern = '*.json';

        if ($languages !== null) {
            $namePattern = '/(' . implode('|', $languages) . ').json/';
        }

        return collect(app(Finder::class)
            ->in(app()->langPath())
            ->name($namePattern)
            ->files())
            ->mapWithKeys(function (SplFileInfo $file) {
                return [$file->getBaseName('.json') => json_decode($file->getContents(), true)];
            });
    }

    protected function query($url, $parameters = [], $type = 'GET'): ?array
    {
        try {
            $response = app(Client::class)->request($type, $url, $parameters);
            return $this->handleResponse($response);
        } catch (POEditorException $e) {
            throw POEditorException::communicationError($e->getMessage());
        } catch (GuzzleException $e) {
            throw POEditorException::communicationError($e->getMessage());
        }
    }

    protected function handleResponse(GuzzleResponse $response)
    {
        if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED], true)) {
            throw POEditorException::communicationError($response->getBody()->getContents());
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}

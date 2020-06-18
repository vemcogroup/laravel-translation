<?php

namespace Vemcogroup\Translation\Commands;

use Exception;
use Illuminate\Console\Command;
use Vemcogroup\Translation\Translation;

class Upload extends Command
{
    protected $signature = 'translation:upload
                            {--scan : Whether the job should scan before uploading}
                            {--translations=all : Upload translations for language sv,da,...}
                            ';

    protected $description = 'Upload all translations to POEditor';

    public function handle(): void
    {
        try {
            $this->info('⬆️  Preparing to upload translations');

            if ($this->option('scan')) {
                $this->call('translation:scan');
            }

            app(Translation::class)->syncTerms();

            if ($this->hasOption('translations')) {
                $language = in_array($this->option('translations'), [null, 'all'], true) ? null : explode(',', $this->option('translations'));
                app(Translation::class)->syncTranslations($language);
            }

            $this->info('⬆️  Finished uploading all translations');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}

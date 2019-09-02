<?php

namespace Vemcogroup\Translation\Commands;

use Exception;
use Illuminate\Console\Command;
use Vemcogroup\Translation\Translation;

class CreateJs extends Command
{
    protected $signature = 'translation:create-js
                            {--download : Download language files before creating js}';
    protected $description = 'Create js files based on json language files';

    public function handle(): void
    {
        try {
            $this->info('Preparing create js files');

            if ($this->option('download')) {
                $this->call('translation:download');
            }

            $files = app(Translation::class)->createJs();

            $this->info('Finished creating js files, created: ' . $files . ' files');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}

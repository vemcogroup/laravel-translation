<?php

namespace Vemcogroup\Translation\Commands;

use Exception;
use Illuminate\Console\Command;
use Vemcogroup\Translation\Translation;

class Scan extends Command
{
    protected $signature = 'translation:scan
                            {--merge : Whether the job should overwrite or new translations keys}';
    protected $description = 'Scan code base for translation variables';

    public function handle(): void
    {
        try {
            $this->info('Preparing to scan code base');
            $this->info('Finding all translation variables');
            $this->info($this->option('merge') ? 'Merging keys' : 'Overwriting keys');

            $variables = app(Translation::class)->scan($this->option('merge'));

            $this->info('Finished scanning code base, found: ' . $variables . ' variables');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}

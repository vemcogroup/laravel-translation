<?php

namespace Vemcogroup\Translation\Commands;

use Exception;
use Illuminate\Console\Command;
use Vemcogroup\Translation\Translation;

class Scan extends Command
{
    protected $signature = 'translation:scan';
    protected $description = 'Scan code base for translation variables';

    public function handle(): void
    {
        try {
            $this->info('Preparing to scan code base');
            $this->info('Finding all translation variables');

            $variables = app(Translation::class)->scan();

            $this->info('Finished scanning code base, found: ' . $variables . ' variables');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}

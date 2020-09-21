<?php

namespace Vemcogroup\Translation\Commands;

use Exception;
use Illuminate\Console\Command;
use Vemcogroup\Translation\Translation;

class AddTerms extends Command
{
    protected $signature = 'translation:add-terms
                            {--scan : Whether the job should scan before uploading}
                            ';

    protected $description = 'Upload all terms to POEditor';

    public function handle(): void
    {
        try {
            $this->info('⬆️  Preparing to upload terms');

            if ($this->option('scan')) {
                $this->call('translation:scan');
            }

            app(Translation::class)->addTerms();

            $this->info('⬆️  Finished uploading all terms');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}

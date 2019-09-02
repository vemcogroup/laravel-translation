<?php

namespace Vemcogroup\Translation\Commands;

use Exception;
use Illuminate\Console\Command;
use Vemcogroup\Translation\Translation;

class Upload extends Command
{
    protected $signature = 'translation:upload
                            {--scan : Whether the job should scan before uploading}';
    protected $description = 'Upload all translations to POEditor';

    public function handle(): void
    {
        try {
            $this->info('⬆️ Preparing to upload translations');

            if ($this->option('scan')) {
                $this->call('translation:scan');
            }

            app(Translation::class)->upload();

            $this->info('⬆ Finished uploading all translations');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}

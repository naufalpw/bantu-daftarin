<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\ResultDocument;
use App\Services\ExpiredFilePurger;
use Illuminate\Console\Command;
use League\Flysystem\UnableToDeleteFile;

class PurgeExpiredFiles extends Command
{
    protected $signature = 'files:purge {--dry-run : Only report files that would be removed}';

    protected $description = 'Purge private documents after their configured retention date.';

    public function handle(ExpiredFilePurger $purger): int
    {
        $count = 0;
        $failures = 0;
        foreach ([Document::class, ResultDocument::class] as $modelClass) {
            $modelClass::query()
                ->whereNull('deleted_at')
                ->whereNotNull('retention_until')
                ->where('retention_until', '<=', now())
                ->where(function ($query): void {
                    $query->whereNull('deletion_scheduled_at')
                        ->orWhere('deletion_scheduled_at', '<=', now()->subMinutes(15));
                })
                ->select(['id', 'public_id'])
                ->chunkById(100, function ($files) use (&$count, &$failures, $modelClass, $purger): void {
                    foreach ($files as $file) {
                        if ($this->option('dry-run')) {
                            $count++;
                            $this->line($file->getMorphClass().' '.$file->public_id);

                            continue;
                        }

                        try {
                            $purged = $purger->purge($modelClass, $file->getKey());
                        } catch (UnableToDeleteFile) {
                            $failures++;
                            $this->error('Gagal menghapus '.$file->getMorphClass().' '.$file->public_id);

                            continue;
                        }

                        if (! $purged) {
                            $failures++;
                            $this->error('Gagal menghapus '.$file->getMorphClass().' '.$file->public_id);

                            continue;
                        }

                        $count++;
                    }
                });
        }
        $this->info(($this->option('dry-run') ? 'Would purge ' : 'Purged ').$count.' file(s).');

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}

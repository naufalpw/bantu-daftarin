<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\ResultDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredFiles extends Command
{
    protected $signature = 'files:purge {--dry-run : Only report files that would be removed}';

    protected $description = 'Purge private documents after their configured retention date.';

    public function handle(): int
    {
        $count = 0;
        foreach ([Document::class, ResultDocument::class] as $modelClass) {
            $modelClass::query()->whereNull('deleted_at')->whereNotNull('retention_until')->where('retention_until', '<=', now())->chunkById(100, function ($files) use (&$count): void {
                foreach ($files as $file) {
                    if ($this->option('dry-run')) {
                        $count++;
                        $this->line($file->getMorphClass().' '.$file->public_id);

                        continue;
                    }
                    if (! Storage::disk($file->storage_disk)->delete($file->storage_path)) {
                        $this->error('Gagal menghapus '.$file->getMorphClass().' '.$file->public_id);

                        continue;
                    }
                    $count++;
                    $file->forceFill(['deleted_at' => now(), 'deletion_scheduled_at' => now(), 'deletion_reason' => 'retention_expired'])->save();
                    AuditLog::create(['event' => 'file.purged', 'auditable_type' => $file->getMorphClass(), 'auditable_id' => $file->getKey(), 'properties' => ['reason' => 'retention_expired'], 'created_at' => now()]);
                }
            });
        }
        $this->info(($this->option('dry-run') ? 'Would purge ' : 'Purged ').$count.' file(s).');

        return self::SUCCESS;
    }
}

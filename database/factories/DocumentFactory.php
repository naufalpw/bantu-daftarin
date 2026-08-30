<?php

namespace Database\Factories;

use App\Enums\DocumentReviewStatus;
use App\Enums\DocumentScanStatus;
use App\Models\Application;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $application = Application::factory()->create();

        return ['application_id' => $application->getKey(), 'version_number' => 1, 'original_filename' => 'synthetic.pdf', 'stored_filename' => 'synthetic.pdf', 'storage_disk' => 'private', 'storage_path' => 'synthetic/'.$this->faker->uuid.'.pdf', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size_bytes' => 1024, 'sha256_checksum' => hash('sha256', 'synthetic'), 'scan_status' => DocumentScanStatus::PASSED, 'review_status' => DocumentReviewStatus::PENDING, 'uploaded_by_user_id' => $application->user_id, 'uploaded_at' => now(), 'active' => true];
    }
}

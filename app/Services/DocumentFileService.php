<?php

namespace App\Services;

use App\Contracts\MalwareScanner;
use App\Exceptions\FileSecurityException;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentFileService
{
    public function __construct(private readonly MalwareScanner $scanner) {}

    /** @return array<string,mixed> */
    public function store(UploadedFile $file, Application $application, ApplicationRequirement $requirement): array
    {
        if (! $file->isValid()) {
            throw new FileSecurityException('File tidak dapat divalidasi.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();
        $allowedExtensions = array_map('strtolower', $requirement->allowed_extensions ?? []);
        $allowedMimes = array_map('strtolower', $requirement->allowed_mimes ?? []);

        if (! in_array($extension, $allowedExtensions, true) || ! in_array(strtolower($mime), $allowedMimes, true)) {
            throw new FileSecurityException('Format file tidak diperbolehkan.');
        }

        if ((int) $file->getSize() > (int) $requirement->max_size_bytes) {
            throw new FileSecurityException('Ukuran file melebihi batas.');
        }

        $realPath = $file->getRealPath();
        if (! is_string($realPath) || ! is_file($realPath)) {
            throw new FileSecurityException('File tidak tersedia untuk pemeriksaan.');
        }

        $this->assertSignature($realPath, $extension, $mime);
        $checksum = hash_file('sha256', $realPath);
        $storedFilename = (string) Str::uuid().'.'.$extension;
        $safeCode = preg_replace('/[^A-Za-z0-9_-]/', '_', $requirement->code) ?: 'requirement';
        $relativePath = 'applications/'.$application->public_id.'/documents/'.$safeCode.'/'.$storedFilename;
        $quarantinePath = Storage::disk('quarantine')->putFileAs(
            dirname($relativePath),
            $file,
            $storedFilename
        );

        if (! $quarantinePath) {
            throw new FileSecurityException('File tidak dapat disimpan untuk pemeriksaan.');
        }

        $scan = $this->scanner->scan(Storage::disk('quarantine')->path($quarantinePath));
        if (! ($scan['available'] ?? false)) {
            Storage::disk('quarantine')->delete($quarantinePath);
            throw new FileSecurityException('Pemeriksaan keamanan file belum tersedia.');
        }

        if (! ($scan['clean'] ?? false)) {
            Storage::disk('quarantine')->delete($quarantinePath);
            throw new FileSecurityException('File ditolak oleh pemeriksaan keamanan.');
        }

        try {
            if (! Storage::disk('private')->put($relativePath, Storage::disk('quarantine')->get($quarantinePath))) {
                throw new FileSecurityException('File tidak dapat dipindahkan ke penyimpanan private.');
            }
        } catch (\Throwable $exception) {
            Storage::disk('quarantine')->delete($quarantinePath);
            throw $exception;
        }
        Storage::disk('quarantine')->delete($quarantinePath);

        $retentionDays = config('files.retention_days');
        if (app()->environment('production') && ! is_numeric($retentionDays)) {
            Storage::disk('private')->delete($relativePath);
            throw new FileSecurityException('Retensi dokumen belum dikonfigurasi untuk production.');
        }

        return [
            'original_filename' => mb_substr((string) $file->getClientOriginalName(), 0, 255),
            'stored_filename' => $storedFilename,
            'storage_disk' => 'private',
            'storage_path' => $relativePath,
            'mime_type' => $mime,
            'extension' => $extension,
            'size_bytes' => (int) $file->getSize(),
            'sha256_checksum' => $checksum,
            'scan_status' => 'PASSED',
            'retention_until' => is_numeric($retentionDays) ? now()->addDays((int) $retentionDays) : null,
        ];
    }

    private function assertSignature(string $path, string $extension, string $mime): void
    {
        $handle = fopen($path, 'rb');
        $header = $handle ? fread($handle, 12) : false;
        if (is_resource($handle)) {
            fclose($handle);
        }

        $valid = match ($extension) {
            'jpg', 'jpeg' => is_string($header) && str_starts_with($header, "\xFF\xD8\xFF"),
            'png' => is_string($header) && str_starts_with($header, "\x89PNG\x0D\x0A\x1A\x0A"),
            'pdf' => is_string($header) && str_starts_with($header, '%PDF-'),
            default => false,
        };

        if (! $valid || ($extension === 'pdf' && $mime !== 'application/pdf') || (($extension === 'jpg' || $extension === 'jpeg') && $mime !== 'image/jpeg') || ($extension === 'png' && $mime !== 'image/png')) {
            throw new FileSecurityException('Isi file tidak sesuai dengan format yang dipilih.');
        }
        if ($extension === 'pdf') {
            $contents = file_get_contents($path);
            $size = filesize($path);
            $tail = is_int($size) && $size > 0 ? file_get_contents($path, false, null, max(0, $size - 1048576)) : false;
            if (! is_string($contents) || ! is_string($tail) || ! str_contains($tail, '%%EOF') || preg_match('/\/(?:JavaScript|JS|Launch|EmbeddedFile|OpenAction)\b/i', $contents)) {
                throw new FileSecurityException('Dokumen PDF tidak dapat diproses dengan aman.');
            }
        }
        if (in_array($extension, ['jpg', 'jpeg', 'png'], true) && @getimagesize($path) === false) {
            throw new FileSecurityException('Gambar tidak dapat dibaca.');
        }
    }
}

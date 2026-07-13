<?php 
namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class FileHelper
{
    public static function store(UploadedFile $file, string $dir = 'uploads'): string
    {
        return $file->store($dir, 'public');
    }

    public static function delete(?string $path): bool
    {
        if (! $path) return false;

        return Storage::disk('public')->delete($path);
    }

    public static function url(?string $path): ?string
{
    if (! $path) return null;

    return asset('storage/' . ltrim($path, '/'));
}
}
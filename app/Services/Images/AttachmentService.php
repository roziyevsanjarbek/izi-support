<?php

namespace App\Services\Images;

use App\Models\Images\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    public function store(UploadedFile $file,Model $attachable,string $collection = 'default',string $disk = 'public',string $directory = 'attachments',int $order = 0,array $meta = []): Attachment
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension();
        $fileName = Str::uuid()->toString() . '.' . $extension;

        $folder = trim($directory, '/')
            . '/' . class_basename($attachable)
            . '/' . $attachable->getKey()
            . '/' . $collection;

        $path = $file->storeAs($folder, $fileName, $disk);

        [$width, $height] = $this->getImageSize($file);

        return $attachable->attachments()->create([
            'collection'     => $collection,
            'disk'           => $disk,
            'path'           => $path, 
            'original_name'  => $file->getClientOriginalName(),
            'file_name'      => $fileName,
            'extension'      => $extension,
            'mime_type'      => $file->getMimeType(),
            'size'           => $file->getSize(),
            'width'          => $width,
            'height'         => $height,
            'order'          => $order,
            'meta'           => $meta,
        ]);
    }

    public function storeMany(array $files,Model $attachable,string $collection = 'gallery',string $disk = 'public',string $directory = 'attachments',array $meta = []): \Illuminate\Support\Collection 
    {
        $created = collect();

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $created->push(
                $this->store(
                    file: $file,
                    attachable: $attachable,
                    collection: $collection,
                    disk: $disk,
                    directory: $directory,
                    order: $index + 1,
                    meta: $meta
                )
            );
        }

        return $created;
    }

    public function delete(Attachment $attachment): bool
    {
        Storage::disk($attachment->disk)->delete($attachment->path);

        return (bool) $attachment->delete();
    }

    private function getImageSize(UploadedFile $file): array
    {
        if (! str_starts_with((string) $file->getMimeType(), 'image/')) {
            return [null, null];
        }

        $size = @getimagesize($file->getRealPath());

        if (! $size) {
            return [null, null];
        }

        return [$size[0] ?? null, $size[1] ?? null];
    }
}
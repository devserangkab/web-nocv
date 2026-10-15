<?php

namespace App\Actions\Media;

use App\Actions\Activities\RecordActivity;
use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StoreMedia
{
    /**
     * Simpan media (foto/video galeri: kondisi existing, evidence aktivitas,
     * diagram, dll) untuk entitas pemilik media.
     *
     * @param  Model  $mediable  Location/Rack/Server/Proxmox/Vms/Panel/Application (atau lain mediator supported)
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Model $mediable, array $data, ?int $addedBy = null): Media
    {
        $data['mediable_type'] = $mediable->getMorphClass();
        $data['mediable_id'] = $mediable->id;
        $data['added_by'] = $addedBy;

        $data = Validator::make($data, static::rules(), static::messages())->validate();

        $file = $data['file'];

        // File upload baru: tentukan jenis dari mime bila tidak eksplisit
        if ($file instanceof UploadedFile) {
            $mime = $file->getMimeType();

            $data['file'] = $file->store('media', 'private');
            $data['type'] ??= str_starts_with((string) $mime, 'video/') ? MediaType::Video->value : MediaType::Image->value;
        }

        // Thumbnail hanya untuk gambar; ukuran ≤150px/SVG menghasilkan null (UI memakai file asli)
        $type = $data['type'] instanceof MediaType ? $data['type'] : MediaType::tryFrom((string) $data['type']);
        $data['thumbnail'] = $type === MediaType::Image && filled($data['file'])
            ? $this->generateThumbnail((string) $data['file'])
            : null;

        $media = $mediable->medias()->create($data);

        app(RecordActivity::class)->audit($media, 'Menambahkan media '.($media->title ?? $media->media_id));

        return $media;
    }

    /**
     * Generate thumbnail max 150x150 px menjaga aspect ratio (fit, tanpa crop dan tanpa upscaling).
     * Menghendaskan path relatif di disk private; mengembalikan null untuk SVG/unknown/gagal.
     */
    private function generateThumbnail(string $relativePath): ?string
    {
        $disk = Storage::disk('private');

        $source = $disk->path($relativePath);

        if (! is_file($source)) {
            return null;
        }

        $info = @getimagesize($source);

        if ($info === false) {
            return null; // svg atau format tidak dikenal
        }

        [$sourceWidth, $sourceHeight, $imageType] = $info;

        if ($sourceWidth <= 150 && $sourceHeight <= 150) {
            return null; // tidak perlu thumbnail
        }

        // Ratio keep aspect
        $ratio = min(150 / $sourceWidth, 150 / $sourceHeight);
        $targetWidth = (int) max(1, (int) floor($sourceWidth * $ratio));
        $targetHeight = (int) max(1, (int) floor($sourceHeight * $ratio));

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = @imagecreatefromjpeg($source);
                $extension = 'jpg';

                break;

            case IMAGETYPE_PNG:
                $sourceImage = @imagecreatefrompng($source);
                $extension = 'png';

                break;

            case IMAGETYPE_WEBP:
                $sourceImage = @imagecreatefromwebp($source);
                $extension = 'webp';

                break;

            case IMAGETYPE_GIF:
                $sourceImage = @imagecreatefromgif($source);
                $extension = 'gif';

                break;

            default:
                return null;
        }

        if ($sourceImage === false) {
            return null;
        }

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        // Pertahankan alpha untuk format yang mendukung
        if (in_array($imageType, [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparant = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
            imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparant);
        }

        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        $thumbnailPath = 'media/thumbs/'.pathinfo($relativePath, PATHINFO_FILENAME).'-thumb.'.$extension;
        $fullThumbnail = $disk->path($thumbnailPath);

        $directory = dirname($fullThumbnail);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        match ($imageType) {
            IMAGETYPE_JPEG => imagejpeg($targetImage, $fullThumbnail, 85),
            IMAGETYPE_PNG => imagepng($targetImage, $fullThumbnail, 8),
            IMAGETYPE_WEBP => imagewebp($targetImage, $fullThumbnail, 85),
            IMAGETYPE_GIF => imagegif($targetImage, $fullThumbnail),
        };

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        return $thumbnailPath;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'mediable_type' => ['required', 'string', 'max:60'],
            'mediable_id' => ['required', 'integer'],
            'type' => ['nullable', Rule::enum(MediaType::class)],
            'title' => ['nullable', 'max:255'],
            'description' => ['nullable', 'max:2000'],
            'file' => ['required', function (string $attribute, $value, $fail) {
                if ($value instanceof UploadedFile) {
                    $check = Validator::make(
                        ['file' => $value],
                        ['file' => ['mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm', 'max:51200']],
                        [
                            'file.mimetypes' => 'File harus berupa gambar (jpg, png, webp, gif, svg) atau video (mp4, webm).',
                            'file.max' => 'Ukuran file maksimal 50 MB.',
                        ]
                    );

                    if ($check->fails()) {
                        $fail($check->errors()->first('file'));
                    }
                }
            }],
            'added_by' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'type.in' => 'Type media harus IMAGE atau VIDEO.',
            'title.max' => 'Title maksimal 255 karakter.',
            'description.max' => 'Description maksimal 2000 karakter.',
            'file.required' => 'File wajib diisi.',
        ];
    }
}

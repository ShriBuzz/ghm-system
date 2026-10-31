<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\Grievance;
use App\Models\GrievanceMessage;
use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AttachmentStore
{
    private const FOLDER = 'attachments';

    // About 100 MB once decoded; turning a photo upright doubles it, within the 256 MB the host must allow.
    private const MAX_PIXELS = 25_000_000;

    /**
     * The upload rule, shared by every form that takes a file (P5, S4).
     * The type is read from the file's content, not its name.
     */
    public static function rule(): File
    {
        return File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])->max(Config::integer('ghm.upload_max_kb'));
    }

    /**
     * Validate, clean and store one file on the private disk (S4), with photo
     * metadata such as GPS position and phone model removed (S5). Errors are
     * reported under the caller's form field name.
     */
    public static function store(UploadedFile $file, Grievance $grievance, ?User $uploader = null, ?GrievanceMessage $message = null, string $field = 'file'): Attachment
    {
        // Arr::set, so a dotted Filament name such as "data.attachment" validates the nested value.
        $data = [];
        Arr::set($data, $field, $file);
        Validator::validate($data, [$field => ['required', self::rule()]]);

        $mime = (string) $file->getMimeType();
        $contents = $mime === 'application/pdf' ? (string) $file->get() : self::reencode($file, $mime, $field);
        $storedName = Str::random(40);

        Storage::disk('local')->put(self::FOLDER.'/'.$storedName, $contents)
            || throw new RuntimeException('Attachment could not be written to the private disk.');

        return $grievance->attachments()->create([
            'message_id' => $message?->id,
            'uploaded_by' => $uploader?->id,
            'stored_name' => $storedName,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size' => strlen($contents),
        ]);
    }

    /**
     * Where the file sits on the private disk.
     */
    public static function path(Attachment $attachment): string
    {
        return self::FOLDER.'/'.$attachment->stored_name;
    }

    /**
     * Redraw the image with GD, which writes pixels only and drops every
     * metadata block. The EXIF orientation is applied first, or phone photos
     * would come out sideways. The size is read from the header first, so a
     * small file claiming a huge picture is refused before it is decoded.
     */
    private static function reencode(UploadedFile $file, string $mime, string $field): string
    {
        [$width, $height] = @getimagesize($file->getRealPath()) ?: [0, 0];
        $image = $width * $height > 0 && $width * $height <= self::MAX_PIXELS
            ? @imagecreatefromstring((string) $file->get())
            : false;

        if (! $image instanceof GdImage) {
            throw ValidationException::withMessages([$field => __('validation.uploaded', ['attribute' => $field])]);
        }

        if ($mime === 'image/jpeg') {
            $image = self::upright($image, $file->getRealPath());
        }

        ob_start();
        match ($mime) {
            'image/png' => imagesavealpha($image, true) && imagepng($image),
            'image/webp' => imagewebp($image, null, 85),
            default => imagejpeg($image, null, 85),
        };

        return (string) ob_get_clean();
    }

    /**
     * shortcut: handles rotation (orientations 3, 6, 8), not the rare mirrored
     * ones; skipped where the host lacks the exif extension.
     */
    private static function upright(GdImage $image, string $path): GdImage
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($path) : false;

        $angle = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }
}

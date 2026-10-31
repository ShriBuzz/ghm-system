<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\Grievance;
use App\Models\GrievanceMessage;
use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class AttachmentStore
{
    private const FOLDER = 'attachments';

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
     * metadata such as GPS position and phone model removed (S5).
     */
    public static function store(UploadedFile $file, Grievance $grievance, ?User $uploader = null, ?GrievanceMessage $message = null): Attachment
    {
        Validator::validate(['file' => $file], ['file' => ['required', self::rule()]]);

        $mime = (string) $file->getMimeType();
        $contents = $mime === 'application/pdf' ? (string) $file->get() : self::reencode($file, $mime);
        $storedName = Str::random(40);

        Storage::disk('local')->put(self::FOLDER.'/'.$storedName, $contents);

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
     * would come out sideways.
     */
    private static function reencode(UploadedFile $file, string $mime): string
    {
        $image = @imagecreatefromstring((string) $file->get());

        if (! $image instanceof GdImage) {
            throw ValidationException::withMessages(['file' => __('validation.uploaded', ['attribute' => 'file'])]);
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

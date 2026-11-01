<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Support\AttachmentStore;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController
{
    /**
     * Stream a case document to staff who may see its case (S4). Always a
     * download, never shown inline, under its original name.
     */
    public function __invoke(Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment->grievance);

        return Storage::disk('local')->download(AttachmentStore::path($attachment), $attachment->original_name);
    }
}

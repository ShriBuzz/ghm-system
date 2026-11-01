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
        $case = $attachment->grievance()->firstOrFail();
        Gate::authorize('view', $case);

        activity()->performedOn($case)->event('attachment_viewed')
            ->withProperties(['attachment_id' => $attachment->id])->log('Document viewed');

        return Storage::disk('local')->download(AttachmentStore::path($attachment), $attachment->original_name);
    }
}

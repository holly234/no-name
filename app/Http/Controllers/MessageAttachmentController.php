<?php

namespace App\Http\Controllers;

use App\Models\MessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageAttachmentController extends Controller
{
    public function download(Request $request, MessageAttachment $attachment)
    {
        $business = $request->attributes->get('currentBusiness');

        abort_unless($attachment->business_id === $business->id, 403);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->storage_path), 404);

        return Storage::disk($attachment->disk)->download(
            $attachment->storage_path,
            $attachment->filename,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream']
        );
    }
}

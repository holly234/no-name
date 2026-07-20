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

        $headers = [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ];

        $inlineTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];

        if ($request->boolean('inline') && in_array($attachment->mime_type, $inlineTypes, true)) {
            if ($attachment->mime_type === 'application/pdf') {
                $headers['Content-Security-Policy'] = "sandbox; default-src 'none'; style-src 'unsafe-inline'";
            }
            return Storage::disk($attachment->disk)->response(
                $attachment->storage_path,
                $attachment->filename,
                $headers
            );
        }

        return Storage::disk($attachment->disk)->download($attachment->storage_path, $attachment->filename, $headers);
    }
}

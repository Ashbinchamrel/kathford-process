<?php

namespace App\Http\Controllers;

use App\Models\FormAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download(FormAttachment $attachment)
    {
        $user = auth()->user();

        $parent = $attachment->attachable;
        abort_unless($parent, 404);
        foreach (\App\Support\RecordVisibility::MODULES as [$class, $owner, $module]) {
            if ($parent instanceof $class) {
                abort_unless($user->can($module.'.view') && \App\Support\RecordVisibility::apply($parent->newQuery(), $user)->whereKey($parent->id)->exists(), 403);
                return $this->streamDownload($attachment);
            }
        }
        abort_unless($user->isSuperAdmin(), 403);
        return $this->streamDownload($attachment);
    }

    private function streamDownload(FormAttachment $attachment)
    {
        abort_unless(Storage::disk('private')->exists($attachment->disk_path), 404);

        return Storage::disk('private')->download(
            $attachment->disk_path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type]
        );
    }
}

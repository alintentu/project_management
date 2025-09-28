<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskAttachmentRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TaskAttachmentController extends Controller
{
    public function store(StoreTaskAttachmentRequest $request, Task $task): RedirectResponse
    {
        foreach ($request->file('attachments', []) as $file) {
            $task->addMedia($file)
                ->toMediaCollection('attachments');
        }

        return Redirect::back()->with('flash', [
            'message' => 'Fișiere încărcate cu succes.',
        ]);
    }

    public function destroy(Task $task, Media $media): RedirectResponse
    {
        if ($media->model_type !== $task->getMorphClass() || $media->model_id !== $task->id) {
            abort(404);
        }

        $media->delete();

        return Redirect::back()->with('flash', [
            'message' => 'Fișierul a fost șters.',
        ]);
    }
}

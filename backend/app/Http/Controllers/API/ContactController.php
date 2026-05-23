<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactSubmittedMail;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(StoreContactMessageRequest $request)
    {
        $message = ContactMessage::query()->create($request->validated());

        $admin = config('mail.admin_address');
        if (is_string($admin) && $admin !== '') {
            Mail::to($admin)->queue(new ContactSubmittedMail($message));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->id,
            ],
            'message' => 'Message sent successfully',
        ], 201);
    }
}

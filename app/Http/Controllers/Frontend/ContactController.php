<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreContactRequest;
use App\Mail\ContactFormMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Handle contact form submission.
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            // Send email to the specified address
            Mail::to('hariharasuthan.m@hbitpartner.com')->send(new ContactFormMail($validated));

            return redirect()->route('frontend.home')
                ->with('success', 'Thank you for contacting us! We will get back to you soon.');
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Contact form email failed to send', [
                'error' => $e->getMessage(),
                'contact_data' => $validated
            ]);

            return redirect()->route('frontend.home')
                ->with('error', 'Sorry, there was an error sending your message. Please try again later.');
        }
    }
}

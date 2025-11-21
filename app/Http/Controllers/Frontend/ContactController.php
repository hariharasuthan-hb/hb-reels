<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreContactRequest;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    /**
     * Handle contact form submission.
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Process contact form (send email, save to database, etc.)
        // TODO: Implement contact form processing
        // Example: Mail::to(config('mail.from.address'))->send(new ContactFormMail($validated));

        return redirect()->route('frontend.home')
            ->with('success', 'Thank you for contacting us! We will get back to you soon.');
    }
}

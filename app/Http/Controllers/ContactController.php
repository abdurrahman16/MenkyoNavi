<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('contact.index');
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $recipient = (string) (config('menkyo.contact_recipient_email') ?: '');

        if ($recipient === '' || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            report(new \RuntimeException('Contact recipient email is not configured.'));

            return back()
                ->withInput()
                ->with('error', __('pages.contact.failure'));
        }

        $payload = $request->safe()->only(['name', 'email', 'subject', 'message']);

        try {
            Mail::to($recipient)->send(new ContactMessageMail($payload));
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', __('pages.contact.failure'));
        }

        $statusKey = config('mail.default') === 'log'
            ? 'pages.contact.success_logged'
            : 'pages.contact.success';

        return redirect()
            ->route('contact')
            ->with('status', __($statusKey));
    }
}

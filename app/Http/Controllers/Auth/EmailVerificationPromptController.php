<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\MailDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return view('auth.verify-email', [
            'externalMail' => MailDelivery::usesExternalTransport(),
            'mailer' => MailDelivery::defaultMailer(),
            'maskedEmail' => $this->maskEmail((string) $request->user()->email),
        ]);
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);

        if (count($parts) !== 2) {
            return '***';
        }

        [$local, $domain] = $parts;
        $visible = mb_substr($local, 0, 1);

        return $visible.'***@'.$domain;
    }
}

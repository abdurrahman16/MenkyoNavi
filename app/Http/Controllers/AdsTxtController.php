<?php

namespace App\Http\Controllers;

use App\Support\Advertising;
use Illuminate\Http\Response;

class AdsTxtController extends Controller
{
    /**
     * Serve ads.txt when a validated publisher ID is configured.
     * Live AdSense rendering remains gated by Advertising::isLiveReady().
     * Does not invent placeholder publisher identifiers.
     */
    public function __invoke(): Response
    {
        if (! Advertising::shouldServeAdsTxt()) {
            abort(404);
        }

        $publisherId = Advertising::adsTxtPublisherId();

        $body = "google.com, {$publisherId}, DIRECT, f08c47fec0942fa0\n";

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}

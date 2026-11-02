<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PasskeyEndpointsController extends Controller
{
    /**
     * Tell password managers where the user adds and manages passkeys
     * (the .well-known/passkey-endpoints standard).
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'enroll' => route('security.edit'),
            'manage' => route('security.edit'),
        ]);
    }
}

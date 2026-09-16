<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sends and checks the one-time codes that prove a partner's mobile number.
 */
class PartnerPhoneVerificationController extends Controller
{
    public function __construct(private readonly PhoneVerificationService $phones) {}

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:40'],
        ]);

        $result = $this->phones->issue($data['phone'], $request->ip());

        if (! $result['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $result['error'],
                'cooldown' => $result['cooldown'],
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Code sent. Check your SMS.',
            'cooldown' => $result['cooldown'],
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:40'],
            'code' => ['required', 'string', 'max:10'],
        ]);

        $result = $this->phones->confirm($data['phone'], $data['code']);

        if (! $result['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Mobile number verified.',
        ]);
    }
}

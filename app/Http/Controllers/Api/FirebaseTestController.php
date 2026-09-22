<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Application\Firebase\FirebaseService;
use Illuminate\Http\Request;
use Kreait\Firebase\Exception\Messaging\NotFound;

class FirebaseTestController extends Controller
{

    public function send(Request $request, FirebaseService $firebase)
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            $result = $firebase->sendToToken(
                $request->token,
                'ShelfSpot Test',
                'Firebase notification is working 🚀',
                [
                    'type' => 'test',
                    'screen' => 'home',
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully.',
                'data' => $result,
            ]);

        } catch (NotFound $e) {
            return response()->json([
                'success' => false,
                'message' => 'FCM token is not registered or is no longer valid.',
            ], 422);
        }
    }}

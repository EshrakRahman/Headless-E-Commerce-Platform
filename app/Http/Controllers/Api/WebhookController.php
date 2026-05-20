<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhook events.
     *
     * @tags Webhooks
     *
     * @unauthenticated
     */
    public function handleStripe(Request $request, PaymentService $paymentService): Response
    {
        $result = $paymentService->handleWebhook(
            $request->getContent(),
            $request->header('Stripe-Signature', '')
        );

        $status = $result['handled'] ? 200 : 400;

        return response()->noContent($status);
    }
}

<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WiseWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify webhook signature
        if (!$this->verifyWebhookSignature($request)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        $eventType = $payload['event_type'] ?? null;
        $transferId = $payload['data']['resource']['id'] ?? null;

        if (!$eventType || !$transferId) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // Find associated payment
        $payment = Payment::where('wise_transfer_id', $transferId)->first();
        if (!$payment) {
            Log::warning('Wise webhook received for unknown transfer', ['transfer_id' => $transferId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Update payment status based on event
        switch ($eventType) {
            case 'transfer.funds_converted':
                $payment->status = 'processing';
                break;
            case 'transfer.completed':
                $payment->status = 'completed';
                break;
            case 'transfer.cancelled':
                $payment->status = 'cancelled';
                break;
            case 'transfer.failed':
                $payment->status = 'failed';
                break;
        }

        $payment->save();

        return response()->json(['message' => 'Webhook processed']);
    }

    protected function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Signature-SHA256');
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac(
            'sha256',
            $payload,
            config('services.wise.webhook_secret')
        );

        return hash_equals($expectedSignature, $signature);
    }
} 
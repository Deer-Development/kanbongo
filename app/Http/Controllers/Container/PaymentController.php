<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Payment;
use App\Models\User;
use App\Services\Wise\WisePaymentException;
use App\Services\Wise\WisePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected $wiseService;

    public function __construct(WisePaymentService $wiseService)
    {
        $this->wiseService = $wiseService;
    }

    public function getRecipients()
    {
        try {
            $recipients = $this->wiseService->getRecipients();
            return response()->json([
                'recipients' => $recipients
            ]);
        } catch (WisePaymentException $e) {
            return response()->json([
                'message' => 'Failed to fetch recipients',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    public function processPayment(ProcessPaymentRequest $request, int $boardId, int $userId)
    {
        try {
            DB::beginTransaction();

            $user = User::findOrFail($userId);
            $payment = Payment::create([
                'user_id' => $userId,
                'board_id' => $boardId,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'status' => 'processing',
                'payment_method' => 'wise',
                'reference' => uniqid('PAY-'),
            ]);

            // 1. Create quote
            $quote = $this->wiseService->createQuote(
                $request->amount,
                'USD',
                $request->currency
            );

            // 2. Use existing recipient or create new one
            $recipientId = $request->recipient_id;
            if (!$recipientId) {
                $recipient = $this->wiseService->createRecipient($user, $request->currency);
                $recipientId = $recipient['id'];
            }

            // Rest of the code...
        }
    }
}
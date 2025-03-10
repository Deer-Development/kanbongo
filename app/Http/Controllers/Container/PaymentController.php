<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Payment;
use App\Models\User;
use App\Services\Wise\WisePaymentService;
use App\Services\Wise\WisePaymentException;
use App\Events\PaymentProcessed;
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

            // 3. Create transfer
            $transfer = $this->wiseService->createTransfer(
                $quote['id'],
                $recipientId,
                [
                    'reference' => $payment->reference,
                    'sourceOfFunds' => 'business',
                    'transferPurpose' => 'service_payment',
                ]
            );

            // 4. Fund transfer
            $fundedTransfer = $this->wiseService->fundTransfer($transfer['id']);

            // 5. Update payment record
            $payment->update([
                'status' => 'completed',
                'wise_transfer_id' => $transfer['id'],
                'wise_recipient_id' => $recipientId,
                'processed_at' => now(),
            ]);

            // 6. Update time entries
            if (!empty($request->selected_entries)) {
                \DB::table('time_entries')
                    ->whereIn('id', $request->selected_entries)
                    ->update([
                        'payment_id' => $payment->id,
                        'paid_at' => now()
                    ]);
            }

            DB::commit();

            // 7. Dispatch event
            PaymentProcessed::dispatch($payment);

            return response()->json([
                'message' => 'Payment processed successfully',
                'payment' => $payment->fresh(),
                'transfer' => $fundedTransfer,
            ]);

        } catch (WisePaymentException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentStatus(string $transferId)
    {
        try {
            $status = $this->wiseService->getTransferStatus($transferId);
            return response()->json($status);
        } catch (WisePaymentException $e) {
            return response()->json([
                'message' => 'Failed to get payment status',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
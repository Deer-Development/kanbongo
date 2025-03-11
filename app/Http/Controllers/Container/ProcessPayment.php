<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Container\ContainerResource;
use App\Services\Container\ContainerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Clients\WiseClient;
use Illuminate\Support\Facades\DB;
use App\Models\Paycheck;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessPayment extends BaseController
{
    protected $service;
    protected $wiseClient;

    public function __construct(ContainerService $service)
    {
        $this->service = $service;

        $this->wiseClient = new WiseClient([
            "token" => config("services.wise.api_key"),
            "profile_id" => config("services.wise.profile_id"),
            "env" => config("services.wise.sandbox") ? "sandbox" : "live"
        ]);
    }

    public function __invoke(int $id, int $userId, Request $request): JsonResponse
    {
        $dateRange = $request->input('date_range');
        $selectedEntries = $request->input('selected_entries');
        $recipientId = $request->input('recipient_id');
        $model = $this->processPayment($id, $userId, $dateRange, $selectedEntries, $recipientId);

        return $this->successResponse(new ContainerResource($model), 'Payment processed successfully.', Response::HTTP_OK);
    }

    public function processPayment(int $id, int $userId, $dateRange, $selectedEntries = [], $recipientId = null)
    {
        if (count($selectedEntries)) {
            $model = $this->service->getModelInstance()::with([
                'boards' => function ($q) use ($selectedEntries) {
                    $q->orderBy('created_at')->with([
                        'tasks' => function ($q) use ($selectedEntries) {
                            $q->with([
                                'timeEntries' => function ($q) use ($selectedEntries) {
                                    $q->whereIn('id', $selectedEntries);
                                },
                                'members'
                            ]);
                        }
                    ]);
                },
                'members' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ])->findOrFail($id);
        } else {
            $startDate = null;
            $endDate = null;
            if ($dateRange) {
                [$start, $end] = array_pad(explode(' to ', $dateRange), 2, null);
                $startDate = Carbon::parse($start)->startOfDay();
                $endDate = $end ? Carbon::parse($end)->endOfDay() : now()->endOfDay();
            }

            $model = $this->service->getModelInstance()::with([
                'boards' => function ($q) use ($startDate, $endDate) {
                    $q->orderBy('created_at')->with([
                        'tasks' => function ($q) use ($startDate, $endDate) {
                            $q->with([
                                'timeEntries' => function ($q) use ($startDate, $endDate) {
                                    if ($startDate) {
                                        $q->where('start', '>=', $startDate);
                                    }
                                    if ($endDate) {
                                        $q->where('end', '<=', $endDate);
                                    }
                                    $q->where('is_paid', false);
                                },
                                'members'
                            ]);
                        }
                    ]);
                },
                'members' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ])->findOrFail($id);
        }

        DB::transaction(function () use ($model, $userId, $recipientId) {
            foreach ($model->members as $member) {
                $totalHours = 0;
                $totalAmount = 0;
                $timeEntryIds = [];

                foreach ($model->boards as $board) {
                    foreach ($board->tasks as $task) {
                        foreach ($task->timeEntries as $timeEntry) {
                            if ($timeEntry->user_id === $member->user_id && !$timeEntry->is_paid) {
                                $durationInHours = Carbon::parse($timeEntry->start)
                                        ->diffInMinutes(Carbon::parse($timeEntry->end)) / 60;

                                $amountPaid = $durationInHours * $member->billable_rate;

                                $timeEntry->update([
                                    'is_paid' => true,
                                    'paid_rate' => $member->billable_rate,
                                    'amount_paid' => $amountPaid
                                ]);

                                $totalHours += $durationInHours;
                                $totalAmount += $amountPaid;
                                $timeEntryIds[] = $timeEntry->id;
                            }
                        }
                    }
                }

                if ($totalHours > 0 && $totalAmount > 0) {
                    Log::info('Starting payment process', [
                        'user_id' => $member->user_id,
                        'container_id' => $model->id,
                        'total_hours' => $totalHours,
                        'total_amount' => $totalAmount
                    ]);

                    $paycheck = Paycheck::create([
                        'user_id' => $member->user_id,
                        'container_id' => $model->id,
                        'project_id' => $model->project_id,
                        'created_by' => Auth::user()->id,
                        'total_hours' => $totalHours,
                        'total_amount' => $totalAmount,
                        'status' => 'paid',
                    ]);

                    Log::info('Paycheck created successfully', [
                        'paycheck_id' => $paycheck->id,
                        'paycheck_details' => $paycheck->toArray()
                    ]);

                    // Update time entries with paycheck ID
                    Log::info('Updating time entries with paycheck ID', [
                        'time_entry_ids' => $timeEntryIds,
                        'paycheck_id' => $paycheck->id
                    ]);

                    DB::table('time_entries')
                        ->whereIn('id', $timeEntryIds)
                        ->update(['paycheck_id' => $paycheck->id]);

                    Log::info('Time entries updated successfully');

                    // Create Wise quote
                    Log::info('Creating Wise quote', [
                        'amount' => $totalAmount,
                        'source_currency' => 'USD',
                        'target_currency' => 'USD'
                    ]);

                    try {
                        // dd($this->wiseClient->profiles->all());

                        $quote = $this->wiseClient->quotes->create_by_profile([
                            "sourceCurrency" => "USD",
                            "targetCurrency" => "USD",
                            "sourceAmount" => null,
                            "targetAmount" => $totalAmount,
                        ], 28671410);

                        Log::info('Wise quote created successfully', [
                            'quote_id' => $quote['id'],
                            'quote_details' => (array) $quote
                        ]);

                        // Retrieve recipient account
                        Log::info('Retrieving recipient account', [
                            'recipient_id' => $recipientId
                        ]);

                        $recipientAccount = $this->wiseClient->recipient_accounts->retrieve($recipientId);
                        
                        Log::info('Recipient account retrieved successfully', [
                            'recipient_account' => (array) $recipientAccount
                        ]);

                        // Create transfer
                        Log::info('Creating Wise transfer', [
                            'recipient_id' => $recipientAccount['id'],
                            'quote_id' => $quote['id'],
                            'paycheck_id' => $paycheck->id
                        ]);

                        $transfer = $this->wiseClient->transfers->create([
                            "targetAccount" => $recipientAccount['id'],
                            "quoteUuid" => $quote['id'],
                            "customerTransactionId" => Str::uuid(),
                            "details" => [
                                "reference" => "P-" . $paycheck->id,
                                "transferPurpose"=> "verification.transfers.purpose.pay.bills",
                                "sourceOfFunds"=> "verification.source.of.funds.other"
                            ]
                        ]);

                        Log::info('Wise transfer created successfully', [
                            'transfer_id' => $transfer['id'],
                            'transfer_details' => (array) $transfer
                        ]);

                        // Fund the transfer
                        Log::info('Funding Wise transfer', [
                            'transfer_id' => $transfer['id']
                        ]);

                        // $fundResult = $this->wiseClient->transfers->fund($transfer['id']);
                        
                        // Log::info('Wise transfer funded successfully', [
                        //     'fund_result' => (array) $fundResult
                        // ]);

                    } catch (\Exception $e) {
                        Log::error('Error in Wise payment process', [
                            'error_message' => $e->getMessage(),
                            'error_trace' => $e->getTraceAsString(),
                            'paycheck_id' => $paycheck->id
                        ]);
                        throw $e;
                    }
                }
            }
        });

        return $model;
    }
}

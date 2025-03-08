<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Models\PaymentDetail;
use App\Http\Requests\User\UpdatePaymentDetailsRequest;
use Illuminate\Http\JsonResponse;

class PaymentDetailsController extends BaseController
{
    public function show(): JsonResponse
    {
        $paymentDetails = auth()->user()->paymentDetails;
        
        return $this->successResponse($paymentDetails ?? new PaymentDetail());
    }

    public function store(UpdatePaymentDetailsRequest $request): JsonResponse
    {
        $paymentDetails = PaymentDetail::updateOrCreate(
            ['user_id' => auth()->id()],
            $request->validated()
        );

        return $this->successResponse($paymentDetails, 'Payment details updated successfully');
    }
} 
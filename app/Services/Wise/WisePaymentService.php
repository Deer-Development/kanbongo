<?php

namespace App\Services\Wise;

use Illuminate\Support\Facades\Http;
use App\Exceptions\WisePaymentException;

class WisePaymentService
{
    // ... existing code ...

    public function getRecipients(): array
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->apiUrl}/v1/accounts", [
                'profile' => $this->profileId
            ]);

        if (!$response->successful()) {
            throw new WisePaymentException('Failed to fetch recipients: ' . $response->body());
        }

        return $response->json();
    }

    public function getRecipientDetails(string $recipientId): array
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->apiUrl}/v1/accounts/{$recipientId}");

        if (!$response->successful()) {
            throw new WisePaymentException('Failed to fetch recipient details: ' . $response->body());
        }

        return $response->json();
    }
}
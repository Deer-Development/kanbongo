<?php

namespace App\Services\Wise;

use Illuminate\Support\Facades\Http;
use App\Exceptions\WisePaymentException;

class WisePaymentService
{
    protected $apiKey;
    protected $apiUrl;
    protected $profileId;

    public function __construct()
    {
        $this->apiKey = config('services.wise.api_key');
        $this->apiUrl = config('services.wise.api_url');
        $this->profileId = config('services.wise.profile_id');
    }

    protected function makeRequest()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json'
        ]);
    }

    public function getRecipients(): array
    {
        $response = $this->makeRequest()
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
        $response = $this->makeRequest()
            ->get("{$this->apiUrl}/v1/accounts/{$recipientId}");

        if (!$response->successful()) {
            throw new WisePaymentException('Failed to fetch recipient details: ' . $response->body());
        }

        return $response->json();
    }
}
<?php

namespace App\Services\Payment;

use App\Models\User;
use App\Models\PaymentDetail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WiseService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.wise.url');
        $this->apiKey = config('services.wise.key');
    }

    public function createProfile(User $user, array $data): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->post("{$this->baseUrl}/v1/profiles", [
                    'type' => $data['profile_type'],
                    'details' => [
                        'firstName' => $data['first_name'],
                        'lastName' => $data['last_name'],
                        'dateOfBirth' => $data['date_of_birth'],
                        'phoneNumber' => $data['phone'],
                        // Add more fields based on profile type
                    ]
                ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Wise Profile Creation Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function createBalanceAccount(string $profileId, string $currency): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->post("{$this->baseUrl}/v1/profiles/{$profileId}/balances", [
                    'currency' => $currency
                ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Wise Balance Account Creation Error', [
                'profile_id' => $profileId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getBankDetails(string $profileId, string $currency): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->get("{$this->baseUrl}/v1/profiles/{$profileId}/balance-accounts");

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Wise Bank Details Error', [
                'profile_id' => $profileId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // Add more methods for other Wise API operations
} 
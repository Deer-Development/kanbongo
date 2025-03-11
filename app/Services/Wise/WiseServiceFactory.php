<?php

namespace App\Services\Wise;

use App\Services\Wise\RecipientAccountService;
use App\Services\Wise\QuoteService;
use App\Services\Wise\TransferService;

class WiseServiceFactory
{
    private $client;
    private $services = [];

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function __get($name)
    {
        if (!isset($this->services[$name])) {
            $this->services[$name] = $this->createService($name);
        }

        return $this->services[$name];
    }

    private function createService($name)
    {
        switch ($name) {
            case 'recipient_accounts':
                return new RecipientAccountService($this->client);
            case 'quotes':
                return new QuoteService($this->client);
            case 'transfers':
                return new TransferService($this->client);
            case 'profiles':
                return new ProfileService($this->client);
            case 'addresses':
                return new AddressService($this->client);
            case 'validators':
                return new ValidatorService($this->client);
            case 'banks':
                return new BankService($this->client);
            case 'profileWebhooks':
                return new ProfileWebhookService($this->client);
            default:
                throw new \InvalidArgumentException("Unknown service {$name}");
        }
    }
} 
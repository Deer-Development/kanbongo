<?php

namespace App\Services\Wise;

class WiseServiceFactory
{
    private $_client;

    private static $services = [
        'addresses' => \App\Services\Wise\AddressService::class,
        'profiles' => \App\Services\Wise\ProfileService::class,
        'quotes' => \App\Services\Wise\QuoteService::class,
        "recipient_accounts" => \App\Services\Wise\RecipientAccountService::class,
        "transfers" => \App\Services\Wise\TransferService::class,
        "validators" => \App\Services\Wise\ValidatorService::class,
        "banks" => \App\Services\Wise\BankService::class,
        "profileWebhooks" => \App\Services\Wise\ProfileWebhookService::class
    ];

    private $instances = [];

    /**
     * Construct service
     *
     * @param Client $client Client Object
     *
     * @return void
     */
    public function __construct($client)
    {
        $this->_client = $client;
    }

    /**
     * Fetch service object given the service name
     *
     * @param String $name Service name
     *
     * @return Service
     */
    protected function getService($name)
    {
        return array_key_exists($name, self::$services) ?
            self::$services[$name] : false;
    }

    /**
     * Get servce by name from the pool of services. Initialize it
     * if it has not been initialized before
     *
     * @param String $name Service name
     *
     * @return void
     */
    public function __get($name)
    {
        $className = $this->getService($name);

        if ($className) {
            if (!array_key_exists($name, $this->instances)) {
                $this->instances[$name] = new $className($this->_client);
            }
            return $this->instances[$name];
        }

        return null;
    }
}
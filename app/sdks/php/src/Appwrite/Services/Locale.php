<?php

namespace Appwrite\Services;

use Exception;
use Appwrite\Client;
use Appwrite\Service;

class Locale extends Service
{
    /**
     * Get User Locale
     *
     * Get the current user location based on IP. Returns an object with user
     * country code, country name, continent name, continent code, ip address and
     * suggested currency. You can use the locale header to get the data in a
     * supported language.
     *
     * @throws Exception
     * @return array
     */
    public function getLocale():array
    {
        $path   = str_replace([], [], '/locale');
        $params = [];


        return $this->client->call(Client::METHOD_GET, $path, [
            'content-type' => 'application/json',
        ], $params);
    }

    /**
     * List Countries
     *
     * List of all continents. You can use the locale header to get the data in a
     * supported language.
     *
     * @throws Exception
     * @return array
     */
    public function getContinents():array
    {
        $path   = str_replace([], [], '/locale/continents');
        $params = [];


        return $this->client->call(Client::METHOD_GET, $path, [
            'content-type' => 'application/json',
        ], $params);
    }

    /**
     * List Countries
     *
     * List of all countries. You can use the locale header to get the data in a
     * supported language.
     *
     * @throws Exception
     * @return array
     */
    public function getCountries():array
    {
        $path   = str_replace([], [], '/locale/countries');
        $params = [];


        return $this->client->call(Client::METHOD_GET, $path, [
            'content-type' => 'application/json',
        ], $params);
    }

    /**
     * List EU Countries
     *
     * List of all countries that are currently members of the EU. You can use the
     * locale header to get the data in a supported language. UK brexit date is
     * currently set to 2019-10-31 and will be updated if and when needed.
     *
     * @throws Exception
     * @return array
     */
    public function getCountriesEU():array
    {
        $path   = str_replace([], [], '/locale/countries/eu');
        $params = [];


        return $this->client->call(Client::METHOD_GET, $path, [
            'content-type' => 'application/json',
        ], $params);
    }

    /**
     * List Countries Phone Codes
     *
     * List of all countries phone codes. You can use the locale header to get the
     * data in a supported language.
     *
     * @throws Exception
     * @return array
     */
    public function getCountriesPhones():array
    {
        $path   = str_replace([], [], '/locale/countries/phones');
        $params = [];


        return $this->client->call(Client::METHOD_GET, $path, [
            'content-type' => 'application/json',
        ], $params);
    }

    /**
     * List Currencies
     *
     * List of all currencies, including currency symol, name, plural, and decimal
     * digits for all major and minor currencies. You can use the locale header to
     * get the data in a supported language.
     *
     * @throws Exception
     * @return array
     */
    public function getCurrencies():array
    {
        $path   = str_replace([], [], '/locale/currencies');
        $params = [];


        return $this->client->call(Client::METHOD_GET, $path, [
            'content-type' => 'application/json',
        ], $params);
    }

}
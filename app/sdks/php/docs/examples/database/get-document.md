<?php

use Appwrite\Client;
use Appwrite\Services\Database;

$client = new Client();

$client
    ->setProject('')
    ->setKey('')
;

$database = new Database($client);

$result = $database->getDocument('[COLLECTION_ID]', '[DOCUMENT_ID]');
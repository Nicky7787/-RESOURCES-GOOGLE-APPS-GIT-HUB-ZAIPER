<?php

use Appwrite\Client;
use Appwrite\Services\Users;

$client = new Client();

$client
    ->setProject('')
    ->setKey('')
;

$users = new Users($client);

$result = $users->createUser('email@example.com', 'password');
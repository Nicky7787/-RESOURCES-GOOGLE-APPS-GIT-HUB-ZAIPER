<?php

use Appwrite\Client;
use Appwrite\Services\Teams;

$client = new Client();

$client
    ->setProject('')
    ->setKey('')
;

$teams = new Teams($client);

$result = $teams->getTeam('[TEAM_ID]');
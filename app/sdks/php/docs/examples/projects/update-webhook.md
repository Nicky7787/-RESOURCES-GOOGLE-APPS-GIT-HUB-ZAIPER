<?php

use Appwrite\Client;
use Appwrite\Services\Projects;

$client = new Client();

$client
    ->setProject('')
    ->setKey('')
;

$projects = new Projects($client);

$result = $projects->updateWebhook('[PROJECT_ID]', '[WEBHOOK_ID]', '[NAME]', [], '[URL]', 0);
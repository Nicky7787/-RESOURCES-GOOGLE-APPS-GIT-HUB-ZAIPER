<?php

return [
    'settings.inspire' => '"Kunstin om at vera vís er at vita hvat man skal misrøkja."', // This is the line printed in the homepage and console 'view-source'
    'settings.locale' => 'fo',
    'settings.direction' => 'ltr',

    // Service - Users
    'auth.emails.team' => '%s Lið',
    'auth.emails.confirm.title' => 'Vátta brúkari',
    'auth.emails.confirm.body' => 'fo.email.auth.confirm.tpl',
    'auth.emails.recovery.title' => 'Glómt passord',
    'auth.emails.recovery.body' => 'fo.email.auth.recovery.tpl',
    'auth.emails.invitation.title' => 'Innbjóðing til %s Lið hjá %s',
    'auth.emails.invitation.body' => 'fo.email.auth.invitation.tpl',

    'locale.country.unknown' => 'Ókjent',

    'countries' => include 'fo.countries.php',
    'continents' => include 'fo.continents.php',
];

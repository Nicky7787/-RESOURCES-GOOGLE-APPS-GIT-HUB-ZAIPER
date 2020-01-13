<?php

include_once 'shared/web.php';

global $utopia, $response, $request, $layout, $version, $providers;

use Utopia\View;
use Database\Database;
use Database\Validator\UID;

$utopia->init(function () use ($layout, $utopia) {
    $layout
        ->setParam('analytics', 'UA-26264668-5')
    ;
});

$utopia->shutdown(function () use ($utopia, $response, $request, $layout, $version) {
    $header = new View(__DIR__.'/../views/console/comps/header.phtml');
    $footer = new View(__DIR__.'/../views/console/comps/footer.phtml');

    $footer
        ->setParam('home', $request->getServer('_APP_HOME', ''))
        ->setParam('version', $version)
    ;

    $layout
        ->setParam('header', [$header])
        ->setParam('footer', [$footer])
        ->setParam('prefetch', [
            //'/console/database?version=' . $version,
            //'/console/storage?version=' . $version,
            //'/console/users?version=' . $version,
            //'/console/settings?version=' . $version,
            //'/console/account?version=' . $version,
        ])
    ;

    $response->send($layout->render());
});

$utopia->get('/error/:code')
    ->desc('Error page')
    ->label('permission', 'public')
    ->label('scope', 'home')
    ->param('code', null, new \Utopia\Validator\Numeric(), 'Valid status code number', false)
    ->action(function ($code) use ($layout) {
        $page = new View(__DIR__.'/../views/error.phtml');

        $page
            ->setParam('code', $code)
        ;

        $layout
            ->setParam('title', APP_NAME.' - Error')
            ->setParam('body', $page);
    });

$utopia->get('/console')
    ->label('permission', 'public')
    ->label('scope', 'console')
    ->action(function () use ($layout, $request) {
        $page = new View(__DIR__.'/../views/console/index.phtml');

        $page
            ->setParam('home', $request->getServer('_APP_HOME', ''))
        ;

        $layout
            ->setParam('title', APP_NAME.' - Console')
            ->setParam('body', $page);
    });

$utopia->get('/console/account')
    ->label('permission', 'public')
    ->label('scope', 'console')
    ->action(function () use ($layout) {
        $page = new View(__DIR__.'/../views/console/account/index.phtml');

        $cc = new View(__DIR__.'/../views/console/forms/credit-card.phtml');

        $page
            ->setParam('cc', $cc)
        ;

        $layout
            ->setParam('title', 'Account - '.APP_NAME)
            ->setParam('body', $page);
    });

$utopia->get('/console/notifications')
    ->desc('Platform console notifications')
    ->label('permission', 'public')
    ->label('scope', 'console')
    ->action(function () use ($layout) {
        $page = new View(__DIR__.'/../views/v1/console/notifications/index.phtml');

        $layout
            ->setParam('title', APP_NAME.' - Notifications')
            ->setParam('body', $page);
    });

$utopia->get('/console/home')
    ->desc('Platform console project home')
    ->label('permission', 'public')
    ->label('scope', 'console')
    ->action(function () use ($layout) {
        $page = new View(__DIR__.'/../views/console/home/index.phtml');

        $layout
            ->setParam('title', APP_NAME.' - Console')
            ->setParam('body', $page);
    });

$utopia->get('/console/settings')
    ->desc('Platform console project settings')
    ->label('permission', 'public')
    ->label('scope', 'console')
    ->action(function () use ($layout) {
        $page = new View(__DIR__.'/../views/console/settings/index.phtml');

        $layout
            ->setParam('title', APP_NAME.' - Settings')
            ->setParam('body', $page);
    });

$utopia->get('/console/webhooks')
    ->desc('Platform console project webhooks')
    ->label('permission', 'public')
    ->label('scope', 'console')
    ->action(function () use ($layout) {
        $page = new View(__DIR__.'/../views/console/webhooks/index.phtml');

        $layout
            ->setParam('title', APP_NAME.' - Webhooks')
            ->setParam('body', $page);
    });

$utopia->get('/console/keys')
    ->desc('Platform console project keys')
    ->label('permission', 'public')
    ->label('scope', 'console')
    ->action(function () use ($layout) {
        $page = new View(__DIR__.'/../views/console/keys/index.phtml');

        $layout
            ->setParam('title', APP_NAME.' - API Keys')
            ->setParam('body', $page);
    });

$utopia->get('/console/tasks')
    ->desc('Platform console project tasks')
    ->label('permission', 'public')
    ->label('scope', 'console')
    ->action(function () use ($layout) {
        $page = new View(__DIR__.'/../views/console/tasks/index.phtml');

        $layout
            ->setParam('title', APP_NAME.' - Tasks')
            ->setParam('body', $page);
    });

$utopia->get('/console/database')
    ->desc('Platform console project settings')
    ->label('permission', 'public')
    ->label('scope', 'console')
    ->action(function () use ($layout) {
        $page = new View(__DIR__.'/../views/console/database/index.phtml');

        $layout
            ->setParam('title', APP_NAME.' - Database')
            ->setParam('body', $page);
    });

$utopia->get('/console/database/collection')
    ->desc('Platform console project settings')
    ->label('permission', 'public')
    ->label('scope', 'console')
    ->param('id', '', function () { return new UID(); }, 'Collection unique ID.')
    ->action(function ($id) use ($layout, $projectDB) {
        $collection = $projectDB->getDocument($id, false);

        if (empty($collection->getUid()) || Database::SYSTEM_COLLECTION_COLLECTIONS != $collection->getCollection()) {
            throw new Exception('Collection not found', 404);
        }

        $page = new View(__DIR__.'/../views/console/database/collection.phtml');

        $page
            ->setParam('collection', $collection->getArrayCopy())
        ;

        $layout
            ->setParam('title', APP_NAME.' - Database')
            ->setParam('body', $page);
    });

$utopia->get('/console/storage')
    ->desc('Platform console project settings')
    ->label('permission', 'public')
    ->label('scope', 'console')
    ->action(function () use ($request, $layout) {
        $page = new View(__DIR__.'/../views/console/storage/index.phtml');

        $page
            ->setParam('home', $request->getServer('_APP_HOME', ''))
        ;

        $layout
            ->setParam('title', APP_NAME.' - Storage')
            ->setParam('body', $page);
    });

$utopia->get('/console/users')
    ->desc('Platform console project settings')
    ->label('permission', 'public')
    ->label('scope', 'console')
    ->action(function () use ($layout, $providers) {
        $page = new View(__DIR__.'/../views/console/users/index.phtml');

        $page->setParam('providers', $providers);

        $layout
            ->setParam('title', APP_NAME.' - Users')
            ->setParam('body', $page);
    });

$utopia->get('/console/users/view')
    ->desc('Platform console project user')
    ->label('permission', 'public')
    ->label('scope', 'console')
    ->action(function () use ($layout, $providers) {
        $page = new View(__DIR__.'/../views/console/users/view.phtml');

        $layout
            ->setParam('title', APP_NAME.' - View User')
            ->setParam('body', $page);
    });

<?php

/**
 * FunkFeuer Node Manager
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://github.com/decke/nodeman
 */
namespace FunkFeuer\Nodeman;

require_once __DIR__.'/vendor/autoload.php';

$session = new Session();

$app = new \Slim\App();

/* init php-view */
$container = $app->getContainer();
$container['view'] = function($container) use ($session) {
    $renderer = new \Slim\Views\PhpRenderer(__DIR__.'/templates/');
    $renderer->addAttribute('session', $session);
    $renderer->addAttribute('config', new \FunkFeuer\Nodeman\Config());
    $renderer->addAttribute('flash', new \Slim\Flash\Messages());

    return $renderer;
};

/* init flash messages */
$container['flash'] = function() {
    return new \Slim\Flash\Messages();
};


/* landing page */
$app->get('/', function($request, $response) {
    return $this->view->render($response, 'index.html');
});

/* Authentication - Login */
$app->post('/login', function($request, $response) use ($session) {
    if (!$request->getParam('username') || !$request->getParam('password')) {
        $this->flash->addMessage('error', 'Authentication failed');
    }
    elseif (!$session->login($request->getParam('username'), $request->getParam('password'))) {
        $this->flash->addMessage('error', 'Authentication failed');
    }

    return $response->withStatus(302)->withHeader('Location', '/');
});

$app->get('/logout', function($request, $response, $args) use ($session) {
    $session->logout();
    return $response->withStatus(302)->withHeader('Location', '/');
});

$app->run();

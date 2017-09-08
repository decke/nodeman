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

/* Registration */
$app->get('/register', function($request, $response) {
    return $this->view->render($response, 'register.html');
});

$app->post('/register', function($request, $response) use ($session) {
    if (!preg_match('/^[0-9A-Za-z@._-]{3,50}$/', $request->getParam('username'))) {
        $this->flash->addMessage('error', 'Username is invalid. Length from 3-50. Allowed characters only 0-9, A-Z, a-z, @, _, -, .');
    }
    if (strlen($request->getParam('password1')) < 6) {
        $this->flash->addMessage('error', 'Password too short (min length 6)');
    }
    if ($request->getParam('password1') != $request->getParam('password2')) {
        $this->flash->addMessage('error', 'Passwords do not match');
    }
    if (!filter_var($request->getParam('email'), FILTER_VALIDATE_EMAIL)) {
        $this->flash->addMessage('error', 'EMail address invalid');
    }
    if (strlen($request->getParam('email')) > 50) {
        $this->flash->addMessage('error', 'EMail address too short (max length 50)');
    }

    $user = new User();
    if ($user->load($request->getParam('username'))) {
        $this->flash->addMessage('error', 'Username already exists');
    }

    if ($user->emailExists($request->getParam('email'))) {
        $this->flash->addMessage('error', 'EMail address already in use');
    }

    /* HACK: Slim-Flash hasMessage('error') does not see messages for next request */
    if(!isset($_SESSION['slimFlash']['error']))
    {
        $user = new User();
        $user->username = $request->getParam('username');
        $user->setPassword($request->getParam('password1'));
        $user->email = $request->getParam('email');
        $user->firstname = $request->getParam('firstname');
        $user->lastname = $request->getParam('lastname');
        $user->phone = $request->getParam('phone');
        $user->usergroup = 'user';

        if($user->save()) {
            $this->flash->addMessage('success', 'Account created');
            return $response->withStatus(302)->withHeader('Location', '/');
        }
        else {
            $this->flash->addMessage('error', 'Account creation failed');
        }
    }

    $data = array(
        'username' => htmlentities($request->getParam('username')),
        'email' => htmlentities($request->getParam('email')),
        'firstname' => htmlentities($request->getParam('firstname')),
        'lastname' => htmlentities($request->getParam('lastname')),
        'phone' => htmlentities($request->getParam('phone'))
    );

    return $this->view->render($response, 'register.html', array('data' => $data));
});

/* Locations */
$app->get('/locations/add', function($request, $response) use ($session) {
    if (!$session->isAuthenticated()) {
        $this->flash->addMessage('error', 'Please login first');
        return $response->withStatus(302)->withHeader('Location', '/');
    }

    return $this->view->render($response, 'locations/add.html');
});

$app->post('/locations/add', function($request, $response) use ($session) {
    if (!$session->isAuthenticated()) {
        $this->flash->addMessage('error', 'Please login first');
        return $response->withStatus(302)->withHeader('Location', '/');
    }

    if (!preg_match('/^[0-9A-Za-z]{3,50}$/', $request->getParam('name'))) {
        $this->flash->addMessage('error', 'Location name is invalid. Length from 3-50. Allowed characters only 0-9, A-Z, a-z');
    }
    if (strlen($request->getParam('address')) < 5) {
        $this->flash->addMessage('error', 'Address is invalid');
    }
    if (strlen($request->getParam('address')) > 255) {
        $this->flash->addMessage('error', 'Address too long (max length 255)');
    }

    $location = new Location();
    if ($location->load($request->getParam('name'))) {
        $this->flash->addMessage('error', 'Location name already exists');
    }

    /* HACK: Slim-Flash hasMessage('error') does not see messages for next request */
    if(!isset($_SESSION['slimFlash']['error']))
    {
        $location = new Location();
        $location->name = $request->getParam('name');
        $location->owner = $session->getUser()->userid;
        $location->address = $request->getParam('address');
        $location->latitude = 0.0;
        $location->longitude = 0.0;
        $location->status = '';
        $location->description = '';

        if($location->save()) {
            $this->flash->addMessage('success', 'Location created');
            return $response->withStatus(302)->withHeader('Location', '/');
        }
        else {
            $this->flash->addMessage('error', 'Location creation failed');
        }
    }

    $data = array(
        'name' => htmlentities($request->getParam('name')),
        'address' => htmlentities($request->getParam('address'))
    );

    return $this->view->render($response, 'locations/add.html', array('data' => $data));
});

$app->run();

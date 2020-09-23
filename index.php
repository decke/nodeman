<?php

/**
 * FunkFeuer Node Manager.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017-2020 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://github.com/decke/nodeman
 */

namespace FunkFeuer\Nodeman;

use DI\Container;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use Slim\Views\TwigMiddleware;

require_once __DIR__.'/vendor/autoload.php';

/* handle static files from php builtin webserver */
if (php_sapi_name() == 'cli-server') {
    $basedir = dirname(__FILE__);
    $allowed_subdirs = array('/css/', '/js/', '/images/');

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = realpath($basedir.$uri);

    if ($path !== false && strpos($path, $basedir) === 0) {
        foreach ($allowed_subdirs as $dir) {
            if (strpos($path, $basedir.$dir) === 0) {
                return false;
            }
        }
    }
}

$session = new Session();

// Create container
$container = new Container();
AppFactory::setContainer($container);

// init flash messages
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

// Add twig-view Middleware
$container->set('view', function () {
    return Twig::create(__DIR__.'/templates/', ['cache' => false]);
});

// Create App
$app = AppFactory::create();

$app->add(TwigMiddleware::createFromContainer($app));

$view = $container->get('view');

$env = $view->getEnvironment();
$env->addGlobal('nonce', bin2hex(random_bytes(5)));
$env->addGlobal('session', $session);
$env->addGlobal('config', new \FunkFeuer\Nodeman\Config());
$env->addGlobal('flash', $container->get('flash'));


/* Middlewares */
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);

    // CSP and other security headers
    if (!$response->hasHeader('Content-Security-Policy')) {
        $globals = $this->get('view')->getEnvironment()->getGlobals();

        return $response->withHeader('Content-Security-Policy', "script-src 'strict-dynamic' 'nonce-".$globals['nonce']."' 'unsafe-inline' http: https:; object-src 'none'; font-src 'self'; base-uri 'none'; frame-ancestors 'none';");
    }

    return $response;
});

/* landing page */
$app->get('/', function ($request, $response) {
    return $this->get('view')->render($response, 'index.html');
});

/* Authentication - Login */
$app->post('/login', function ($request, $response) use ($session) {
    if (!$request->getParam('email') || !$request->getParam('password')) {
        $this->get('flash')->addMessage('error', 'Authentication failed');
    } elseif (!$session->login($request->getParam('email'), $request->getParam('password'))) {
        $this->get('flash')->addMessage('error', 'Authentication failed');
    }

    return $response->withStatus(302)->withHeader('Location', '/');
});

$app->get('/logout', function ($request, $response) use ($session) {
    $session->logout();

    return $response->withStatus(302)->withHeader('Location', '/');
});

/* Registration */
$app->get('/register', function ($request, $response) {
    return $this->get('view')->render($response, 'register.html');
});

$app->post('/register', function ($request, $response) {
    if (!filter_var($request->getParam('email'), FILTER_VALIDATE_EMAIL)) {
        $this->get('flash')->addMessageNow('error', 'EMail address invalid');
    }
    if (strlen($request->getParam('email')) > 50) {
        $this->get('flash')->addMessageNow('error', 'EMail address too short (max length 50)');
    }
    if (strlen($request->getParam('password1')) < 6) {
        $this->get('flash')->addMessageNow('error', 'Password too short (min length 6)');
    }
    if ($request->getParam('password1') != $request->getParam('password2')) {
        $this->get('flash')->addMessageNow('error', 'Passwords do not match');
    }

    $user = new User();
    if ($user->emailExists($request->getParam('email'))) {
        $this->get('flash')->addMessageNow('error', 'EMail address already in use');
    }

    if (!$this->get('flash')->hasMessage('error')) {
        $user = new User();
        $user->setPassword($request->getParam('password1'));
        $user->email = $request->getParam('email');
        $user->firstname = $request->getParam('firstname');
        $user->lastname = $request->getParam('lastname');
        $user->phone = $request->getParam('phone');
        $user->usergroup = 'user';

        if ($user->save()) {
            $user->setAttribute('needsverification', 'true');
            $user->setAttribute('sendmail', 'verifyemail');

            $this->get('flash')->addMessage('success', 'Account created. Please check your EMails!');

            return $response->withStatus(302)->withHeader('Location', '/');
        } else {
            $this->get('flash')->addMessageNow('error', 'Account creation failed');
        }
    }

    $data = array(
        'email'     => $request->getParam('email'),
        'firstname' => $request->getParam('firstname'),
        'lastname'  => $request->getParam('lastname'),
        'phone'     => $request->getParam('phone')
    );

    return $this->get('view')->render($response, 'register.html', array('data' => $data));
});

$app->get('/verify', function ($request, $response) {
    $data = array(
        'token' => '',
        'email' => ''
    );

    if ($request->getParam('token')) {
        $data['token'] = $request->getParam('token');
    }
    if ($request->getParam('email')) {
        $data['email'] = $request->getParam('email');
    }

    return $this->get('view')->render($response, 'verify.html', array('data' => $data));
});

$app->post('/verify', function ($request, $response) {
    $user = new User();

    $data = array(
        'token' => '',
        'email' => ''
    );

    if ($user->loadByEMail($request->getParam('email'))) {
        if ($user->getAttribute('emailtoken') != $request->getParam('token')) {
            $this->get('flash')->addMessageNow('error', 'EMail verification failed');
        } elseif ($user->getAttribute('emailtokenvalid') < time()) {
            $this->get('flash')->addMessageNow('error', 'EMail verification failed');
        } else {
            $user->delAttribute('emailtoken');
            $user->delAttribute('emailtokenvalid');
            $user->delAttribute('needsverification');

            $this->get('flash')->addMessage('success', 'EMail Address verified. You can login now!');

            return $response->withStatus(302)->withHeader('Location', '/');
        }
    } else {
        $this->get('flash')->addMessageNow('error', 'EMail verification failed');
    }

    return $this->get('view')->render($response, 'verify.html', array('data' => $data));
});

$app->get('/passwordreset', function ($request, $response) {
    return $this->get('view')->render($response, 'passwordreset.html');
});

$app->post('/passwordreset', function ($request, $response) {
    $user = new User();
    if ($user->emailExists($request->getParam('email'))) {
        $user->loadByEMail($request->getParam('email'));

        $user->setAttribute('sendmail', 'passwordreset');
    }
    $this->get('flash')->addMessage('success', 'EMail was send');

    return $response->withStatus(302)->withHeader('Location', '/passwordresetcode?email='.$user->email);
});

$app->get('/passwordresetcode', function ($request, $response) {
    $data = array(
        'token' => '',
        'email' => ''
    );

    if ($request->getParam('token')) {
        $data['token'] = $request->getParam('token');
    }
    if ($request->getParam('email')) {
        $data['email'] = $request->getParam('email');
    }

    return $this->get('view')->render($response, 'passwordresetcode.html', array('data' => $data));
});

$app->post('/passwordresetcode', function ($request, $response) {
    $user = new User();

    if ($user->loadByEMail($request->getParam('email'))) {
        if ($user->getAttribute('emailtoken') != $request->getParam('token')) {
            $this->get('flash')->addMessageNow('error', 'Token is invalid');
        } elseif ($user->getAttribute('emailtokenvalid') < time()) {
            $this->get('flash')->addMessageNow('error', 'Token expired');
        } elseif (strlen($request->getParam('password1')) < 6) {
            $this->get('flash')->addMessageNow('error', 'Password too short (min length 6)');
        } elseif ($request->getParam('password1') != $request->getParam('password2')) {
            $this->get('flash')->addMessageNow('error', 'Passwords do not match');
        } elseif (!$user->setPassword($request->getParam('password1'))) {
            $this->get('flash')->addMessageNow('error', 'Password reset failed');
        } elseif (!$user->save()) {
            $this->get('flash')->addMessageNow('error', 'Password reset failed');
        } else {
            $user->delAttribute('emailtoken');
            $user->delAttribute('emailtokenvalid');

            $this->get('flash')->addMessage('success', 'New Password was set. You can login now!');
            return $response->withStatus(302)->withHeader('Location', '/');
        }
    } else {
        $this->get('flash')->addMessageNow('error', 'Password reset failed');
    }

    $data = array(
        'email' => $request->getParam('email')
    );

    return $this->get('view')->render($response, 'passwordresetcode.html', array('data' => $data));
});


/* Map */
$app->get('/map', function ($request, $response) {
    $query = '';

    if ($request->getParam('lat') && $request->getParam('lng')) {
        $query = sprintf('?lat=%f&lng=%f', $request->getParam('lat'), $request->getParam('lng'));
    }

    return $this->get('view')->render($response, 'map.html', array(
        'css' => array('/css/leaflet.css'),
        'js'  => array('/js/leaflet.min.js', '/mapdata'.$query)
    ));
});

$app->get('/mapdata', function ($request, $response) {
    $links = array();
    $location = new Location();
    $locations = array();
    $deflocation = array();

    if ($request->getParam('lat') && $request->getParam('lng')) {
        $deflocation['lat'] = $request->getParam('lat');
        $deflocation['lng'] = $request->getParam('lng');
    }

    foreach ($location->getAllLocations(null, 0, 999999) as $loc) {
        $popup = sprintf('<b>%s</b><br>%s', $loc->name, $loc->address);

        if (strlen($loc->gallerylink)) {
            $popup .= sprintf('<br><a href=\"%s\">Gallery</a>', $loc->gallerylink);
        }

        $locations[] = array(
            'name'     => $loc->name,
            'type'     => $loc->status,
            'location' => $loc->getLongLat(),
            'popup'    => $popup
        );
    }

    $link = new InterfaceLink();

    foreach ($link->getAllLinks() as $link) {
        $fromloc = $link->getFromLocation();
        $toloc = $link->getToLocation();

        $links[] = array(
            'from'    => $fromloc->getLongLat(),
            'to'      => $toloc->getLongLat(),
            'quality' => $link->quality
        );
    }

    return $this->get('view')->render($response, 'map.js', array(
        'deflocation' => $deflocation,
        'locations'   => $locations,
        'links'       => $links
    ))->withHeader('Content-Type', 'application/javascript; charset=utf-8');
});

/* Locations */
$app->get('/locations', function ($request, $response) {
    $loc = new Location();

    return $this->get('view')->render($response, 'locations.html', array(
        'locations' => $loc->getAllLocations(null, 0, 999999)
    ));
});

$app->get('/location/add', function ($request, $response) use ($session) {
    if (!$session->isAuthenticated()) {
        $this->get('flash')->addMessage('error', 'Please login first');

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    return $this->get('view')->render($response, 'location/add.html', array(
        'css' => array('/css/leaflet.css'),
        'js'  => array('/js/leaflet.min.js', '/js/grazmap.js')
    ));
});

$app->post('/location/add', function ($request, $response) use ($session) {
    if (!$session->isAuthenticated()) {
        $this->get('flash')->addMessageNow('error', 'Please login first');

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    if (!preg_match('/^[0-9A-Za-z]{3,50}$/', $request->getParam('name'))) {
        $this->get('flash')->addMessageNow('error', 'Location name is invalid. Length from 3-50. Allowed characters only 0-9, A-Z, a-z');
    }
    if (strlen($request->getParam('address')) < 5) {
        $this->get('flash')->addMessageNow('error', 'Address is invalid');
    }
    if (strlen($request->getParam('address')) > 255) {
        $this->get('flash')->addMessageNow('error', 'Address too long (max length 255)');
    }
    if (!$request->getParam('latitude') || !$request->getParam('longitude')) {
        $this->get('flash')->addMessageNow('error', 'Position on map is missing');
    }

    $location = new Location();
    if ($location->loadByName($request->getParam('name'))) {
        $this->get('flash')->addMessageNow('error', 'Location name already exists');
    }

    /* HACK: Slim-Flash hasMessage('error') does not see messages for next request */
    if (!isset($_SESSION['slimFlash']['error'])) {
        $location = new Location();

        if (!$location->loadByName($request->getParam('name'))) {
            $location->name = $request->getParam('name');
            $location->owner = $session->getUser()->userid;
            $location->address = $request->getParam('address');
            $location->latitude = $request->getParam('latitude');
            $location->longitude = $request->getParam('longitude');
            $location->status = 'offline';
            $location->gallerylink = '';
            $location->description = '';

            if ($location->save()) {
                $this->get('flash')->addMessage('success', 'Location created');

                return $response->withStatus(302)->withHeader('Location', '/');
            } else {
                $this->get('flash')->addMessageNow('error', 'Location creation failed');
            }
        } else {
            $this->get('flash')->addMessageNow('error', 'Location name already used');
        }
    }

    $data = array(
        'name'    => $request->getParam('name'),
        'address' => $request->getParam('address')
    );

    return $this->get('view')->render($response, 'location/add.html', array(
        'data' => $data,
        'css'  => array('/css/leaflet.css'),
        'js'   => array('/js/leaflet.min.js', '/js/grazmap.js')
    ));
});

/* Nodes */
$app->get('/location/{locationid}/add', function ($request, $response, $args) use ($session) {
    if (!$session->isAuthenticated()) {
        $this->get('flash')->addMessage('error', 'Please login first');

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    return $this->get('view')->render($response, 'location/node/add.html', array(
        'data' => array('locationid' => $args['locationid'])
    ));
});

$app->post('/location/{locationid}/add', function ($request, $response, $args) use ($session) {
    if (!$session->isAuthenticated()) {
        $this->get('flash')->addMessage('error', 'Please login first');

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    $location = new Location($args['locationid']);
    if ($location->owner != $session->getUser()->userid) {
        $this->get('flash')->addMessage('error', 'Permission denied');

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    if (!preg_match('/^[0-9A-Za-z]{3,50}$/', $request->getParam('name'))) {
        $this->get('flash')->addMessageNow('error', 'Node name is invalid. Length from 3-50. Allowed characters only 0-9, A-Z, a-z');
    }
    if (strlen($request->getParam('description')) > 16384) {
        $this->get('flash')->addMessageNow('error', 'Description is too long (max 16K)');
    }

    $location = new Location($args['locationid']);
    if ($location->nodeExists($request->getParam('name'))) {
        $this->get('flash')->addMessageNow('error', 'Node name already exists');
    }

    /* HACK: Slim-Flash hasMessage('error') does not see messages for next request */
    if (!isset($_SESSION['slimFlash']['error'])) {
        $node = new Node();
        $node->name = $request->getParam('name');
        $node->owner = $session->getUser()->userid;
        $node->location = $args['locationid'];
        $node->description = $request->getParam('description');

        if ($node->save()) {
            $this->get('flash')->addMessage('success', 'Node created');

            return $response->withStatus(302)->withHeader('Location', '/location/'.$node->location.'/node/'.$node->nodeid.'/');
        } else {
            $this->get('flash')->addMessageNow('error', 'Location creation failed');
        }
    }

    $data = array(
        'name'          => $request->getParam('name'),
        'description'   => $request->getParam('description'),
        'locationid'    => $args['locationid']
    );

    return $this->get('view')->render($response, 'location/node/add.html', array(
        'data' => $data
    ));
});

/* User Area */
$app->get('/user/home', function ($request, $response) use ($session) {
    if (!$session->isAuthenticated()) {
        $this->get('flash')->addMessage('error', 'Please login first');

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    $loc = new Location();

    return $this->get('view')->render($response, 'user/home.html', array(
        'user'      => $session->getUser(),
        'locations' => $loc->getAllLocations($session->getUser()->userid, 0, 999999)
    ));
});

/**
 * Catch-all route to serve a 404 Not Found page if none of the routes match
 * NOTE: make sure this route is defined last
 */
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
});

$app->run();

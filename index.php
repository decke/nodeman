<?php

/**
 * FunkFeuer Node Manager.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://github.com/decke/nodeman
 */

namespace FunkFeuer\Nodeman;

require_once __DIR__.'/vendor/autoload.php';

/* handle static files from php builtin webserver */
if (php_sapi_name() == 'cli-server') {
    $basedir = dirname(__FILE__);
    $allowed_subdirs = array('/css/', '/js/', '/fonts/', '/images/');

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

$app = new \Slim\App();

/* init php-view */
$container = $app->getContainer();

/* init flash messages */
$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};

/* init twig-view */
$container['view'] = function ($container) use ($session) {
    $renderer = new \Slim\Views\Twig(__DIR__.'/templates/', array(
        'cache' => false,
        'debug' => true
        // 'cache' => Config::get('cache.directory')
    ));

    $env = $renderer->getEnvironment();
    $env->addExtension(new \Twig_Extension_Debug());
    $env->addGlobal('nonce', bin2hex(random_bytes(5)));
    $env->addGlobal('session', $session);
    $env->addGlobal('config', new \FunkFeuer\Nodeman\Config());
    $env->addGlobal('flash', $container->get('flash'));

    $router = $container->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));

    $renderer->addExtension(new \Slim\Views\TwigExtension($router, $uri));

    return $renderer;
};

/* Middlewares */
$app->add(function ($request, $response, $next) {
    /* CSP and other security headers */
    if (!$response->hasHeader('Content-Security-Policy')) {
        $globals = $this->view->getEnvironment()->getGlobals();

        $response = $response->withHeader('Content-Security-Policy', "script-src 'strict-dynamic' 'nonce-".$globals['nonce']."' 'unsafe-inline' http: https:; object-src 'none'; font-src 'self'; base-uri 'none'; frame-ancestors 'none';");
    }

    return $next($request, $response);
});

/* landing page */
$app->get('/', function ($request, $response) {
    return $this->view->render($response, 'index.html');
});

/* Authentication - Login */
$app->post('/login', function ($request, $response) use ($session) {
    if (!$request->getParam('email') || !$request->getParam('password')) {
        $this->flash->addMessage('error', 'Authentication failed');
    } elseif (!$session->login($request->getParam('email'), $request->getParam('password'))) {
        $this->flash->addMessage('error', 'Authentication failed');
    }

    return $response->withStatus(302)->withHeader('Location', '/');
});

$app->get('/logout', function ($request, $response) use ($session) {
    $session->logout();

    return $response->withStatus(302)->withHeader('Location', '/');
});

/* Registration */
$app->get('/register', function ($request, $response) {
    return $this->view->render($response, 'register.html');
});

$app->post('/register', function ($request, $response) {
    if (!filter_var($request->getParam('email'), FILTER_VALIDATE_EMAIL)) {
        $this->flash->addMessageNow('error', 'EMail address invalid');
    }
    if (strlen($request->getParam('email')) > 50) {
        $this->flash->addMessageNow('error', 'EMail address too short (max length 50)');
    }
    if (strlen($request->getParam('password1')) < 6) {
        $this->flash->addMessageNow('error', 'Password too short (min length 6)');
    }
    if ($request->getParam('password1') != $request->getParam('password2')) {
        $this->flash->addMessageNow('error', 'Passwords do not match');
    }

    $user = new User();
    if ($user->emailExists($request->getParam('email'))) {
        $this->flash->addMessageNow('error', 'EMail address already in use');
    }

    /* HACK: Slim-Flash hasMessage('error') does not see messages for next request */
    if (!isset($_SESSION['slimFlash']['error'])) {
        $user = new User();
        $user->setPassword($request->getParam('password1'));
        $user->email = $request->getParam('email');
        $user->firstname = $request->getParam('firstname');
        $user->lastname = $request->getParam('lastname');
        $user->phone = $request->getParam('phone');
        $user->usergroup = 'user';

        if ($user->save()) {
            $this->flash->addMessage('success', 'Account created');

            return $response->withStatus(302)->withHeader('Location', '/');
        } else {
            $this->flash->addMessageNow('error', 'Account creation failed');
        }
    }

    $data = array(
        'email'     => $request->getParam('email'),
        'firstname' => $request->getParam('firstname'),
        'lastname'  => $request->getParam('lastname'),
        'phone'     => $request->getParam('phone')
    );

    return $this->view->render($response, 'register.html', array('data' => $data));
});

/* Map */
$app->get('/map', function ($request, $response) {
    $query = '';

    if ($request->getParam('lat') && $request->getParam('lng')) {
        $query = sprintf('?lat=%f&lng=%f', $request->getParam('lat'), $request->getParam('lng'));
    }

    return $this->view->render($response, 'map.html', array(
        'css' => array('/css/leaflet.css', '/css/map.css'),
        'js'  => array('/js/leaflet.js', '/mapdata'.$query)
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

    return $this->view->render($response, 'map.js', array(
        'deflocation' => $deflocation,
        'locations'   => $locations,
        'links'       => $links
    ))->withHeader('Content-Type', 'application/javascript; charset=utf-8');
});

/* Locations */
$app->get('/locations', function ($request, $response) {
    $loc = new Location();

    return $this->view->render($response, 'locations.html', array(
        'locations' => $loc->getAllLocations(null, 0, 999999)
    ));
});

$app->get('/location/add', function ($request, $response) use ($session) {
    if (!$session->isAuthenticated()) {
        $this->flash->addMessage('error', 'Please login first');

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    return $this->view->render($response, 'location/add.html', array(
        'css' => array('/css/leaflet.css'),
        'js'  => array('/js/leaflet.js', '/js/grazmap.js')
    ));
});

$app->post('/location/add', function ($request, $response) use ($session) {
    if (!$session->isAuthenticated()) {
        $this->flash->addMessageNow('error', 'Please login first');

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    if (!preg_match('/^[0-9A-Za-z]{3,50}$/', $request->getParam('name'))) {
        $this->flash->addMessageNow('error', 'Location name is invalid. Length from 3-50. Allowed characters only 0-9, A-Z, a-z');
    }
    if (strlen($request->getParam('address')) < 5) {
        $this->flash->addMessageNow('error', 'Address is invalid');
    }
    if (strlen($request->getParam('address')) > 255) {
        $this->flash->addMessageNow('error', 'Address too long (max length 255)');
    }
    if (!$request->getParam('latitude') || !$request->getParam('longitude')) {
        $this->flash->addMessageNow('error', 'Position on map is missing');
    }

    $location = new Location();
    if ($location->loadByName($request->getParam('name'))) {
        $this->flash->addMessageNow('error', 'Location name already exists');
    }

    /* HACK: Slim-Flash hasMessage('error') does not see messages for next request */
    if (!isset($_SESSION['slimFlash']['error'])) {
        $location = new Location();
        $location->name = $request->getParam('name');
        $location->owner = $session->getUser()->userid;
        $location->address = $request->getParam('address');
        $location->latitude = $request->getParam('latitude');
        $location->longitude = $request->getParam('longitude');
        $location->status = 'offline';
        $location->gallerylink = '';
        $location->description = '';

        if ($location->save()) {
            $this->flash->addMessage('success', 'Location created');

            return $response->withStatus(302)->withHeader('Location', '/');
        } else {
            $this->flash->addMessageNow('error', 'Location creation failed');
        }
    }

    $data = array(
        'name'    => $request->getParam('name'),
        'address' => $request->getParam('address')
    );

    return $this->view->render($response, 'location/add.html', array(
        'data' => $data,
        'css'  => array('/css/leaflet.css'),
        'js'   => array('/js/leaflet.js', '/js/grazmap.js')
    ));
});

/* Nodes */
$app->get('/location/{locationid}/add', function ($request, $response, $args) use ($session) {
    if (!$session->isAuthenticated()) {
        $this->flash->addMessage('error', 'Please login first');

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    return $this->view->render($response, 'location/node/add.html', array(
        'data' => array('locationid' => $args['locationid'])
    ));
});

$app->post('/location/{locationid}/add', function ($request, $response, $args) use ($session) {
    if (!$session->isAuthenticated()) {
        $this->flash->addMessage('error', 'Please login first');

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    $location = new Location($args['locationid']);
    if ($location->owner != $session->getUser()->userid) {
        $this->flash->addMessage('error', 'Permission denied');

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    if (!preg_match('/^[0-9A-Za-z]{3,50}$/', $request->getParam('name'))) {
        $this->flash->addMessageNow('error', 'Node name is invalid. Length from 3-50. Allowed characters only 0-9, A-Z, a-z');
    }
    if (strlen($request->getParam('description')) > 16384) {
        $this->flash->addMessageNow('error', 'Description is too long (max 16K)');
    }

    $location = new Location($args['locationid']);
    if ($location->nodeExists($request->getParam('name'))) {
        $this->flash->addMessageNow('error', 'Node name already exists');
    }

    /* HACK: Slim-Flash hasMessage('error') does not see messages for next request */
    if (!isset($_SESSION['slimFlash']['error'])) {
        $node = new Node();
        $node->name = $request->getParam('name');
        $node->owner = $session->getUser()->userid;
        $node->location = $args['locationid'];
        $node->hardware = 0;
        $node->description = $request->getParam('description');

        if ($node->save()) {
            $this->flash->addMessage('success', 'Node created');

            return $response->withStatus(302)->withHeader('Location', '/location/'.$node->location.'/node/'.$node->nodeid.'/');
        } else {
            $this->flash->addMessageNow('error', 'Location creation failed');
        }
    }

    $data = array(
        'name'          => $request->getParam('name'),
        'description'   => $request->getParam('description'),
        'locationid'    => $args['locationid']
    );

    return $this->view->render($response, 'location/node/add.html', array(
        'data' => $data
    ));
});

/* User Area */
$app->get('/user/home', function ($request, $response) use ($session) {
    if (!$session->isAuthenticated()) {
        $this->flash->addMessage('error', 'Please login first');

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    $loc = new Location();

    return $this->view->render($response, 'user/home.html', array(
        'user'      => $session->getUser(),
        'locations' => $loc->getAllLocations($session->getUser()->userid, 0, 999999)
    ));
});

$app->run();

<?php

namespace FunkFeuer\Nodeman;

/**
 * Manage PHP sessions and authenticate users.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://github.com/decke/nodeman
 */
class Session
{
    public function __construct()
    {
        self::initialize();

        session_start();
    }

    public static function initialize()
    {
        // do not expose Cookie value to JavaScript (enforced by browser)
        ini_set('session.cookie_httponly', 1);

        if (Config::get('security.https_only') === true) {
            // only send cookie over https
            ini_set('session.cookie_secure', 1);
        }

        // prevent caching by sending no-cache header
        session_cache_limiter('nocache');

        // rename session
        session_name('SESSIONID');
    }

    public static function getSessionId()
    {
        return session_id();
    }

    public static function login($username, $password)
    {
        $user = new User($username);

        if(!$user->checkPassword($password)) {
            return false;
        }

        /* login assumed to be successfull */
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['loginip'] = $_SERVER['REMOTE_ADDR'];

        return true;
    }

    public static function getUsername()
    {
        if (isset($_SESSION['username'])) {
            return $_SESSION['username'];
        }

        return false;
    }

    public static function getUser()
    {
        if(self::isAuthenticated()) {
            return new User(self::getUsername());
        }

        return false;
    }

    public static function isAuthenticated()
    {
        return isset($_SESSION['authenticated']);
    }

    public static function logout()
    {
        $_SESSION = array();

        /* also destroy session cookie on client */
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );

        session_destroy();

        return true;
    }
}

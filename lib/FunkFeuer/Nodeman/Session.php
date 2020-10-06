<?php

namespace FunkFeuer\Nodeman;

/**
 * Manage PHP sessions and authenticate users.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017-2020 Bernhard Froehlich
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
        // rename session
        session_name('SESSIONID');

        // do not expose Cookie value to JavaScript (enforced by browser)
        ini_set('session.cookie_httponly', 'true');

        // avoid cross-origin leakage
        ini_set('session.cookie_samesite', 'Strict');

        if (Config::get('security.https_only') == 'true' || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
            // only send cookie over https
            ini_set('session.cookie_secure', 'true');

            // rename session
            session_name('__Secure-SESSIONID');
        }

        // prevent caching by sending no-cache header
        session_cache_limiter('nocache');
    }

    public static function getSessionId()
    {
        return session_id();
    }

    public static function login($email, $password)
    {
        $user = new User();
        if (!$user->loadByEmail($email)) {
            return false;
        }

        if (!$user->checkPassword($password)) {
            return false;
        }

        /* login assumed to be successfull */
        $_SESSION['authenticated'] = true;
        $_SESSION['userid'] = $user->userid;
        $_SESSION['loginip'] = $_SERVER['REMOTE_ADDR'];

        $user->lastlogin = time();
        return $user->save();
    }

    public static function deauthenticate()
    {
        unset($_SESSION['authenticated']);
        unset($_SESSION['userid']);
        unset($_SESSION['loginip']);
    }

    public static function getUser()
    {
        if (self::isAuthenticated()) {
            return new User($_SESSION['userid']);
        }

        return null;
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
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );

        session_destroy();

        return true;
    }
}

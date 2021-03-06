<?php
declare(strict_types=1);

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
    const SESSIONNAME = 'SESSIONID';

    public function __construct()
    {
        self::initialize();
    }

    public static function initialize(): void
    {
        // rename session
        session_name(self::SESSIONNAME);

        // only accept valid session id's
        ini_set('session.use_strict_mode', 'true');

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

        session_start();
    }

    public static function getSessionId(): string
    {
        $id = session_id();

        if($id === false)
            return "";

        return $id;
    }

    public static function login(string $email, string $password): bool
    {
        $user = new User();
        if (!$user->loadByEmail($email)) {
            return false;
        }

        if (!$user->checkPassword($password)) {
            return false;
        }

        /* login assumed to be successfull */
        session_regenerate_id();

        $_SESSION['authenticated'] = true;
        $_SESSION['userid'] = $user->userid;
        $_SESSION['loginip'] = $_SERVER['REMOTE_ADDR'];

        $user->lastlogin = time();
        return $user->save();
    }

    public static function deauthenticate(): void
    {
        unset($_SESSION['authenticated']);
        unset($_SESSION['userid']);
        unset($_SESSION['loginip']);
    }

    public static function getUser(): User
    {
        if (self::isAuthenticated()) {
            return new User((int)$_SESSION['userid']);
        }
        return new User();
    }

    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['authenticated']);
    }

    public static function logout(): bool
    {
        $_SESSION = array();

        /* also destroy session cookie on client */
        $params = session_get_cookie_params();
        setcookie(
            self::SESSIONNAME,
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

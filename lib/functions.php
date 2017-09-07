<?php
/**
 * Various functions.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://github.com/decke/nodeman
 */

function checkDbSchema($handle)
{
}

function jsonResponse($code, $data = array())
{
    $app = \Slim\Slim::getInstance();
    $app->response->setStatus($code);
    $app->response->headers->set('Content-Type', 'application/json');
    $app->response->write(json_encode($data));

    if ($code != 200) {
        $app->stop();
    }

    return true;
}

function textResponse($code, $data = '')
{
    $app = \Slim\Slim::getInstance();
    $app->response->setStatus($code);
    $app->response->headers->set('Content-Type', 'text/plain');
    $app->response->write($data);

    if ($code != 200) {
        $app->stop();
    }

    return true;
}

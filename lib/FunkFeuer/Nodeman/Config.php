<?php

namespace FunkFeuer\Nodeman;

/**
 * Configuration class to store various static settings.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017-2020 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://github.com/decke/nodeman
 */
class Config
{
    protected static $datasource = 'sqlite:share/nodeman.db';
    protected static $handle = null;

    public static function getDataSource()
    {
        return self::$datasource;
    }

    public static function getDbHandle()
    {
        if (self::$handle === null) {
            self::$handle = new \PDO(self::$datasource);
            self::$handle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::$handle->exec('PRAGMA foreign_keys = ON;');
            self::$handle->exec('PRAGMA encoding = "UTF-8";');
        }

        return self::$handle;
    }

    public static function exists($property)
    {
        $handle = self::getDbHandle();

        $stmt = $handle->prepare('SELECT name, value FROM config WHERE name = ?');
        if (!$stmt) {
            return false;
        }

        if (!$stmt->execute(array(strtolower($property)))) {
            return false;
        }

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return false;
        }

        return true;
    }

    public static function get($property)
    {
        $handle = self::getDbHandle();

        $stmt = $handle->prepare('SELECT name, value FROM config WHERE name = ?');
        if (!$stmt) {
            return false;
        }

        if (!$stmt->execute(array(strtolower($property)))) {
            throw new \Exception('Could not find config property '.$property);
        }

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            throw new \Exception('Could not find config property '.$property);
        }

        return $row['value'];
    }

    public static function set($property, $value)
    {
        $handle = self::getDbHandle();

        if (!self::exists($property)) {
            return false;
        }

        $stmt = $handle->prepare('UPDATE config SET value = ? WHERE name = ?');

        return $stmt->execute(array($value, strtolower($property)));
    }
}

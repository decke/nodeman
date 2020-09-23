<?php

namespace FunkFeuer\Nodeman;

/**
 * User class for registered users.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017-2020 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://github.com/decke/nodeman
 */
class User
{
    private $_handle;
    private $_data = array(
        'userid'    => null,
        'password'  => null,
        'email'     => null,
        'firstname' => null,
        'lastname'  => null,
        'phone'     => null,
        'usergroup' => null,
        'lastlogin' => null,
        'regdate'   => null
    );

    public function __construct($userid = null)
    {
        $this->_handle = Config::getDbHandle();

        if ($userid !== null) {
            $this->load($userid);
        }
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }

        throw new \Exception('Undefined property '.$name.' in class User');
    }

    public function __set($name, $value)
    {
        if ($name == 'password') {
            return $this->setPassword($value);
        }

        if ($name == 'email') {
            $value = strtolower($value);
        }

        if (array_key_exists($name, $this->_data)) {
            $this->_data[$name] = $value;

            return true;
        }

        throw new \Exception('Undefined property '.$name.' in class User');
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->_data);
    }

    public function setPassword($password)
    {
        if (strlen($password) > 0) {
            $this->_data['password'] = password_hash($password, PASSWORD_DEFAULT, array('cost' => 11));
        } else {
            $this->_data['password'] = '';
        }

        return true;
    }

    public function checkPassword($password)
    {
        /* Support old MD5 hashes */
        if (strlen($this->password) == 32 && $this->password[0] != '$') {
            if (md5($password) == $this->password) {
                /* generate a new hash */
                $this->setPassword($password);

                return $this->save();
            }

            return false;
        }

        return password_verify($password, $this->password);
    }

    public function emailExists($email)
    {
        $stmt = $this->_handle->prepare('SELECT count(*) FROM users WHERE email = ?');
        $stmt->execute(array(strtolower($email)));
        $result = $stmt->fetchAll();

        return $result[0][0] > 0;
    }

    public function load($userid)
    {
        $stmt = $this->_handle->prepare('SELECT userid, password, email, firstname,
            lastname, phone, usergroup, lastlogin, regdate FROM users WHERE userid = ?');
        if (!$stmt->execute(array(strtolower($userid)))) {
            return false;
        }

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $this->_data = $row;

            return true;
        }

        return false;
    }

    public function loadByEMail($email)
    {
        $stmt = $this->_handle->prepare('SELECT userid, password, email, firstname,
            lastname, phone, usergroup, lastlogin, regdate FROM users WHERE email = ?');
        if (!$stmt->execute(array(strtolower($email)))) {
            return false;
        }

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $this->_data = $row;

            return true;
        }

        return false;
    }

    public function save()
    {
        if (!$this->userid) {
            $stmt = $this->_handle->prepare('INSERT INTO users (password, email, firstname,
                lastname, phone, usergroup, lastlogin, regdate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

            if ($stmt->execute(array($this->password, $this->email, $this->firstname,
                $this->lastname, $this->phone, $this->usergroup, $this->lastlogin, $this->regdate))) {
                $this->userid = $this->_handle->lastInsertId();

                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE users SET password = ?, email = ?,
                firstname = ?, lastname = ?, phone = ?, usergroup = ?, lastlogin = ?, regdate = ? WHERE userid = ?');

            return $stmt->execute(array($this->password, $this->email, $this->firstname,
                $this->lastname, $this->phone, $this->usergroup, $this->lastlogin, $this->regdate, $this->userid));
        }

        return false;
    }

    public function getAllAttributes()
    {
        if (!$this->userid) {
            throw new \Exception('User does not have an ID yet in class User');
        }

        $data = array();

        $stmt = $this->_handle->prepare('SELECT key, value FROM userattributes WHERE userid = ? ORDER BY key');
        if (!$stmt->execute(array($this->userid))) {
            return $data;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[$row['key']] = $row['value'];
        }

        return $data;
    }

    public function getAttribute($key)
    {
        if (!$this->userid) {
            throw new \Exception('User does not have an ID yet in class User');
        }

        $stmt = $this->_handle->prepare('SELECT value FROM userattributes WHERE userid = ? AND key = ?');
        if (!$stmt->execute(array($this->userid, $key))) {
            return false;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $row['value'];
        }

        return false;
    }

    public function delAttribute($key)
    {
        if (!$this->userid) {
            throw new \Exception('User does not have an ID yet in class User');
        }

        $stmt = $this->_handle->prepare('DELETE FROM userattributes WHERE userid = ? AND key = ?');

        if ($stmt->execute(array($this->userid, $key))) {
            return true;
        }

        return false;
    }

    public function setAttribute($key, $value)
    {
        if (!$this->userid) {
            throw new \Exception('Node does not have an ID yet in class Node');
        }

        if ($this->getAttribute($key) === false) {
            $stmt = $this->_handle->prepare('INSERT INTO userattributes (userid, key, value)
                VALUES (?, ?, ?)');

            if ($stmt->execute(array($this->userid, $key, $value))) {
                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE userattributes SET value = ? WHERE userid = ? AND key = ?');

            return $stmt->execute(array($value, $this->userid, $key));
        }

        return false;
    }
}

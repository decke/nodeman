<?php

namespace FunkFeuer\Nodeman;

/**
 * User class for registered users.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017 Bernhard Froehlich
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
        'usergroup' => null
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
        $this->_data['password'] = password_hash($password, PASSWORD_DEFAULT, array('cost' => 11));

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
            lastname, phone, usergroup FROM users WHERE userid = ?');
        if (!$stmt->execute(array(strtolower($userid)))) {
            return false;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $this->_data = $row;

            return true;
        }

        return false;
    }

    public function loadByEMail($email)
    {
        $stmt = $this->_handle->prepare('SELECT userid, password, email, firstname,
            lastname, phone, usergroup FROM users WHERE email = ?');
        if (!$stmt->execute(array(strtolower($email)))) {
            return false;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $this->_data = $row;

            return true;
        }

        return false;
    }

    public function save()
    {
        if (!$this->userid) {
            $stmt = $this->_handle->prepare('INSERT INTO users (password, email, firstname,
                lastname, phone, usergroup) VALUES (?, ?, ?, ?, ?, ?)');

            if ($stmt->execute(array($this->password, $this->email, $this->firstname,
                $this->lastname, $this->phone, $this->usergroup))) {
                $this->userid = $this->_handle->lastInsertId();

                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE users SET password = ?, email = ?,
                firstname = ?, lastname = ?, phone = ?, usergroup = ? WHERE userid = ?');

            return $stmt->execute(array($this->password, $this->email, $this->firstname,
                $this->lastname, $this->phone, $this->usergroup, $this->userid));
        }

        return false;
    }
}

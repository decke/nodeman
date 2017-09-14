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
        'username'  => null,
        'password'  => null,
        'email'     => null,
        'firstname' => null,
        'lastname'  => null,
        'phone'     => null,
        'usergroup' => null
    );

    public function __construct($username = null)
    {
        $this->_handle = Config::getDbHandle();

        if ($username !== null) {
            $this->load($username);
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
        if ($name == 'username') {
            $value = strtolower($value);
        }

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
            if(md5($password) == $this->password) {
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

    public function load($username)
    {
        $stmt = $this->_handle->prepare('SELECT userid, username, password, email, firstname,
            lastname, phone, usergroup FROM users WHERE username = ?');
        if (!$stmt->execute(array(strtolower($username)))) {
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
            $stmt = $this->_handle->prepare('INSERT INTO users (username, password, email, firstname,
                lastname, phone, usergroup) VALUES (?, ?, ?, ?, ?, ?, ?)');

            if ($stmt->execute(array($this->username, $this->password, $this->email, $this->firstname,
                $this->lastname, $this->phone, $this->usergroup))) {
                $this->userid = $this->_handle->lastInsertId();

                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE users SET username = ?, password = ?, email = ?,
                firstname = ?, lastname = ?, phone = ?, usergroup = ? WHERE userid = ?');

            return $stmt->execute(array($this->username, $this->password, $this->email, $this->firstname,
                $this->lastname, $this->phone, $this->usergroup, $this->userid));
        }

        return false;
    }
}

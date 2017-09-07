<?php

namespace FunkFeuer\Nodeman;

/**
 * User class for registered users
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
        'userid'   => null,
        'username' => null,
        'password' => null,
        'email'    => null,
        'phone'    => null
    );

    public function __construct($username = null)
    {
        $this->_handle = Config::getDbHandle();

        if($username !== null) {
            $this->load($username);
        }
    }

    public function __get($name)
    {
        if(isset($this->_data[$name])) {
            return $this->_data[$name];
        }

        throw new Exception('Undefined property '.$name.' in class User');
    }

    public function __set($name, $value)
    {
        if($name == 'password') {
            return $this->setPassword($value);
        }

        if(isset($this->_data[$name])) {
            $this->_data[$name] = $value;
            return true;
        }

        throw new Exception('Undefined property '.$name.' in class User');
    }

    public function setPassword($password)
    {
        $this->_data['password'] = password_hash($password, PASSWORD_DEFAULT, array('cost' => 11));
        return true;
    }

    public function checkPassword($password)
    {
        return password_verify($password, $this->password);
    }

    public function load($username)
    {
        $stmt = $this->_handle->prepare("SELECT userid, username, password, email, phone FROM users WHERE username = ?");
        if(!$stmt->execute(array($username)))
            return false;

        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $this->_data = $row;

            return true;
        }

        return false;
    }

    public function save()
    {
        if(!$this->userid)
        {
            $stmt = $this->_handle->prepare("INSERT INTO users (username, password, email, phone) VALUES (?, ?, ?, ?)");

            if($stmt->execute(array($this->username, $this->password, $this->email, $this->phone)))
            {
                $this->userid = $this->_handle->lastInsertId();
                return true;
            }
        }
        else
        {
            $stmt = $this->_handle->prepare("UPDATE users SET username = ?, password = ?, email = ?, phone = ? WHERE userid = ?");

            return $stmt->execute(array($this->username, $this->password, $this->email, $this->phone, $this->userid));
        }

        return false;
    }
}

<?php
declare(strict_types=1);

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
    private \PDO $_handle;

    public int $userid;
    public string $password;
    public string $email;
    public string $firstname;
    public string $lastname;
    public string $phone;
    public string $usergroup;
    public int $lastlogin;
    public int $regdate;

    public function __construct(int $userid = null)
    {
        $this->_handle = Config::getDbHandle();

        if ($userid !== null) {
            $this->load($userid);
        }
    }

    public function setEMail(string $email): bool
    {
        $this->email = strtolower($email);
        return true;
    }

    public function setPassword(string $password): bool
    {
        if (strlen($password) > 0) {
            $hash = password_hash($password, PASSWORD_DEFAULT, array('cost' => 11));

            if ($hash === false) {
                return false;
            }

            $this->password = $hash;
        } else {
            $this->password = '';
        }

        return true;
    }

    public function checkPassword(string $password): bool
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

    public function emailExists(string $email): bool
    {
        $stmt = $this->_handle->prepare('SELECT count(*) FROM users WHERE email = ?');
        if (!$stmt->execute(array(strtolower($email)))) {
            return true;
        }

        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            return ((int)$row[0] > 0);
        }

        return true;
    }

    public function load(int $userid): bool
    {
        $stmt = $this->_handle->prepare('SELECT * FROM users WHERE userid = ?');
        $stmt->setFetchMode(\PDO::FETCH_INTO, $this);

        if (!$stmt->execute(array($userid))) {
            return false;
        }

        if ($stmt->fetch(\PDO::FETCH_INTO) === false) {
            return false;
        }

        return true;
    }

    public function loadByEMail(string $email): bool
    {
        $stmt = $this->_handle->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->setFetchMode(\PDO::FETCH_INTO, $this);

        if (!$stmt->execute(array(strtolower($email)))) {
            return false;
        }

        if ($stmt->fetch(\PDO::FETCH_INTO) === false) {
            return false;
        }

        return true;
    }

    public function save(): bool
    {
        if (!isset($this->userid)) {
            $stmt = $this->_handle->prepare('INSERT INTO users (password, email, firstname,
                lastname, phone, usergroup, lastlogin, regdate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

            if ($stmt->execute(array($this->password, $this->email, $this->firstname,
                $this->lastname, $this->phone, $this->usergroup, $this->lastlogin, $this->regdate))) {
                $this->userid = (int)$this->_handle->lastInsertId();

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

    public function getAllAttributes(): array
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

    public function getAttribute(string $key): ?string
    {
        if (!$this->userid) {
            throw new \Exception('User does not have an ID yet in class User');
        }

        $stmt = $this->_handle->prepare('SELECT value FROM userattributes WHERE userid = ? AND key = ?');
        if (!$stmt->execute(array($this->userid, $key))) {
            return null;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $row['value'];
        }

        return null;
    }

    public function delAttribute(string $key): bool
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

    public function setAttribute(string $key, string $value): bool
    {
        if (!$this->userid) {
            throw new \Exception('Node does not have an ID yet in class Node');
        }

        if ($this->getAttribute($key) === null) {
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

    public function delete(): bool
    {
        $stmt = $this->_handle->prepare('UPDATE locations SET maintainer = 0 WHERE maintainer = ?');
        if (!$stmt->execute(array($this->userid))) {
            return false;
        }

        $stmt = $this->_handle->prepare('UPDATE nodes SET maintainer = 0 WHERE maintainer = ?');
        if (!$stmt->execute(array($this->userid))) {
            return false;
        }

        $stmt = $this->_handle->prepare('DELETE FROM users WHERE userid = ?');
        if (!$stmt->execute(array($this->userid))) {
            return false;
        }

        $stmt = $this->_handle->prepare('DELETE FROM users WHERE userid = ?');
        if (!$stmt->execute(array($this->userid))) {
            return false;
        }

        return true;
    }
}

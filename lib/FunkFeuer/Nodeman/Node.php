<?php

namespace FunkFeuer\Nodeman;

/**
 * Node implementation which groups together various interfaces.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017-2020 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://github.com/decke/nodeman
 */
class Node
{
    private $_handle;
    private $_data = array(
        'nodeid'        => null,
        'name'          => null,
        'owner'         => null,
        'location'      => null,
        'description'   => null
    );

    public function __construct($nodeid = null)
    {
        $this->_handle = Config::getDbHandle();

        if ($nodeid !== null) {
            $this->load($nodeid);
        }
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }

        throw new \Exception('Undefined property '.$name.' in class Node');
    }

    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->_data)) {
            $this->_data[$name] = $value;

            return true;
        }

        throw new \Exception('Undefined property '.$name.' in class Node');
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->_data);
    }

    public function load($id)
    {
        $stmt = $this->_handle->prepare('SELECT nodeid, name, owner, location,
            description FROM nodes WHERE nodeid = ?');
        if (!$stmt->execute(array($id))) {
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
        if (!$this->nodeid) {
            $stmt = $this->_handle->prepare('INSERT INTO nodes (name, owner, location,
                description) VALUES (?, ?, ?, ?)');

            if ($stmt->execute(array($this->name, $this->owner, $this->location,
                $this->description))) {
                $this->nodeid = $this->_handle->lastInsertId();

                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE nodes SET name = ?, owner = ?, location = ?,
                description = ? WHERE nodeid = ?');

            return $stmt->execute(array($this->name, $this->owner, $this->location,
                $this->description, $this->nodeid));
        }

        return false;
    }

    public function getInterfaceByName($name)
    {
        $stmt = $this->_handle->prepare('SELECT interfaceid FROM interfaces WHERE node = ? AND name = ?');
        if (!$stmt->execute(array($this->nodeid, $name))) {
            return null;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return new NetInterface($row['interfaceid']);
        }

        return null;
    }

    public function getAllInterfaces()
    {
        if (!$this->nodeid) {
            throw new \Exception('Node does not have an ID yet in class Node');
        }

        $data = array();

        $stmt = $this->_handle->prepare('SELECT interfaceid FROM interfaces WHERE (node = ? OR ? IS NULL) ORDER BY interfaceid');
        if (!$stmt->execute(array($this->nodeid, $this->nodeid))) {
            return $data;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = new NetInterface($row['interfaceid']);
        }

        return $data;
    }

    public function getAllAttributes()
    {
        if (!$this->nodeid) {
            throw new \Exception('Node does not have an ID yet in class Node');
        }

        $data = array();

        $stmt = $this->_handle->prepare('SELECT key, value FROM nodeattributes WHERE node = ? ORDER BY key');
        if (!$stmt->execute(array($this->nodeid))) {
            return $data;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[$row['key']] = $row['value'];
        }

        return $data;
    }

    public function getAttribute($key)
    {
        if (!$this->nodeid) {
            throw new \Exception('Node does not have an ID yet in class Node');
        }

        $stmt = $this->_handle->prepare('SELECT value FROM nodeattributes WHERE node = ? AND key = ?');
        if (!$stmt->execute(array($this->nodeid, $key))) {
            return false;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $row['value'];
        }

        return false;
    }

    public function delAttribute($key)
    {
        if (!$this->nodeid) {
            throw new \Exception('Node does not have an ID yet in class Node');
        }

        $stmt = $this->_handle->prepare('DELETE FROM nodeattributes WHERE node = ? AND key = ?');

        if ($stmt->execute(array($this->nodeid, $key))) {
            return true;
        }

        return false;
    }

    public function setAttribute($key, $value)
    {
        if (!$this->nodeid) {
            throw new \Exception('Node does not have an ID yet in class Node');
        }

        if ($this->getAttribute($key) === false) {
            $stmt = $this->_handle->prepare('INSERT INTO nodeattributes (node, key, value)
                VALUES (?, ?, ?)');

            if ($stmt->execute(array($this->nodeid, $key, $value))) {
                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE nodeattributes SET value = ? WHERE node = ? AND key = ?');

            return $stmt->execute(array($value, $this->nodeid, $key));
        }

        return false;
    }
}

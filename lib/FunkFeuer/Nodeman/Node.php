<?php

namespace FunkFeuer\Nodeman;

/**
 * Node implementation which groups together various interfaces.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017 Bernhard Froehlich
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
        'hardware'      => null,
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
        $stmt = $this->_handle->prepare('SELECT nodeid, name, owner, location, hardware,
            description FROM nodes WHERE nodeid = ?');
        if (!$stmt->execute(array($id))) {
            return false;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $this->_data = $row;

            return true;
        }

        return false;
    }

    public function loadByName($name)
    {
        $stmt = $this->_handle->prepare('SELECT nodeid, name, owner, location, hardware,
            description FROM nodes WHERE name = ?');
        if (!$stmt->execute(array($name))) {
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
        if (!$this->nodeid) {
            $stmt = $this->_handle->prepare('INSERT INTO nodes (name, owner, location, hardware,
                description) VALUES (?, ?, ?, ?, ?)');

            if ($stmt->execute(array($this->name, $this->owner, $this->location, $this->hardware,
                $this->description))) {
                $this->nodeid = $this->_handle->lastInsertId();

                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE nodes SET name = ?, owner = ?, location = ?,
                hardware = ?, description = ? WHERE nodeid = ?');

            return $stmt->execute(array($this->name, $this->owner, $this->location, $this->hardware,
                $this->description, $this->nodeid));
        }

        return false;
    }

    public function getAllInterfaces()
    {
        $data = array();

        $stmt = $this->_handle->prepare('SELECT interfaceid FROM interfaces WHERE (node = ? OR ? IS NULL) ORDER BY interfacid');
        if (!$stmt->execute(array($this->node, $this->node))) {
            return $data;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = new NetInterface($row['interfaceid']);
        }

        return $data;
    }
}

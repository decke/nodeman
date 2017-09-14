<?php

namespace FunkFeuer\Nodeman;

/**
 * Network Interface with assigned IP Address.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://github.com/decke/nodeman
 */
class NetInterface
{
    private $_handle;
    private $_data = array(
        'interfaceid'   => null,
        'name'          => null,
        'node'          => null,
        'category'      => null,
        'type'          => null,
        'address'       => null,
        'status'        => null,
        'ping'          => null,
        'description'   => null
    );

    public function __construct($interfaceid = null)
    {
        $this->_handle = Config::getDbHandle();

        if ($interfaceid !== null) {
            $this->load($interfaceid);
        }
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }

        throw new \Exception('Undefined property '.$name.' in class '.__CLASS__);
    }

    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->_data)) {
            $this->_data[$name] = $value;

            return true;
        }

        throw new \Exception('Undefined property '.$name.' in class '.__CLASS__);
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->_data);
    }

    public function load($id)
    {
        $stmt = $this->_handle->prepare('SELECT interfaceid, name, node, category, type, address,
            status, ping, description FROM interfaces WHERE interfaceid = ?');
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
        $stmt = $this->_handle->prepare('SELECT interfaceid, name, node, category, type, address,
            status, ping, description FROM interfaces WHERE name = ?');
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
        if (!$this->locationid) {
            $stmt = $this->_handle->prepare('INSERT INTO interfaces (name, node, category, type, address,
                status, ping, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

            if ($stmt->execute(array($this->name, $this->node, $this->category, $this->type,
                $this->address, $this->status, $this->ping, $this->description))) {
                $this->interfaceid = $this->_handle->lastInsertId();

                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE interfaces SET name = ?, node = ?, category = ?,
                type = ?, address = ?, status = ?, ping = ?, description = ? WHERE interfaceid = ?');

            return $stmt->execute(array($this->name, $this->node, $this->category, $this->type,
                $this->address, $this->status, $this->ping, $this->description, $this->interfaceid));
        }

        return false;
    }
}

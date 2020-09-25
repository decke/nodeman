<?php

namespace FunkFeuer\Nodeman;

/**
 * Network Interface with assigned IP Address.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017-2020 Bernhard Froehlich
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

    public function renderDescription()
    {
        $parser = new \Parsedown();
        $parser->setSafeMode(true);
        return $parser->text(str_replace('\r\n', "\n\n", $this->description));
    }

    public function load($id)
    {
        $stmt = $this->_handle->prepare('SELECT interfaceid, name, node, category, type, address,
            status, ping, description FROM interfaces WHERE interfaceid = ?');
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
        if (!$this->interfaceid) {
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

    public function loadByIPAddress($address)
    {
        $stmt = $this->_handle->prepare('SELECT interfaceid, name, node, category, type, address,
            status, ping, description FROM interfaces WHERE address = ?');
        if (!$stmt->execute(array($address))) {
            return false;
        }

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $this->_data = $row;

            return true;
        }

        return false;
    }

    public function loadByPath($path)
    {
        $parts = explode('.', $path);
        if (count($parts) != 3) {
            throw new \Exception('Invalid NetInterface path '.$path);
        }

        $loc = new Location();
        $loc->loadByName($parts[0]);
        $node = $loc->getNodeByName($parts[1]);

        if ($node === null) {
            throw new \Exception('Invalid NetInterface path '.$path);
        }

        $iface = $node->getInterfaceByName($parts[2]);

        return $this->load($iface->interfaceid);
    }

    public function getPath()
    {
        return sprintf("%s.%s.%s", $this->getNode()->getLocation()->name, $this->getNode()->name, $this->name);
    }

    public function getNode()
    {
        return new Node($this->node);
    }

    public function recalcStatus()
    {
        $stmt = $this->_handle->prepare('SELECT count(*) FROM linkdata WHERE (fromif = ? OR toif = ?) AND status = ?');
        if (!$stmt->execute(array($this->interfaceid, $this->interfaceid, 'up'))) {
            return false;
        }

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($row['count(*)'] > 0) {
                $this->status = 'online';
            } else {
                $this->status = 'offline';
            }

            return $this->save();
        }

        return false;
    }

    public function getAllAttributes()
    {
        if (!$this->interfaceid) {
            throw new \Exception('NetInterface does not have an ID yet in class NetInterface');
        }

        $data = array();

        $stmt = $this->_handle->prepare('SELECT key, value FROM interfaceattributes WHERE interface = ? ORDER BY key');
        if (!$stmt->execute(array($this->interfaceid))) {
            return $data;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[$row['key']] = $row['value'];
        }

        return $data;
    }

    public function getAttribute($key)
    {
        if (!$this->interfaceid) {
            throw new \Exception('NetInterface does not have an ID yet in class NetInterface');
        }

        $stmt = $this->_handle->prepare('SELECT value FROM interfaceattributes WHERE interface = ? AND key = ?');
        if (!$stmt->execute(array($this->interfaceid, $key))) {
            return false;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $row['value'];
        }

        return false;
    }

    public function delAttribute($key)
    {
        if (!$this->interfaceid) {
            throw new \Exception('NetInterface does not have an ID yet in class NetInterface');
        }

        $stmt = $this->_handle->prepare('DELETE FROM interfaceattributes WHERE interface = ? AND key = ?');

        if ($stmt->execute(array($this->interfaceid, $key))) {
            return true;
        }

        return false;
    }

    public function setAttribute($key, $value)
    {
        if (!$this->interfaceid) {
            throw new \Exception('NetInterface does not have an ID yet in class NetInterface');
        }

        if ($this->getAttribute($key) === false) {
            $stmt = $this->_handle->prepare('INSERT INTO interfaceattributes (interface, key, value)
                VALUES (?, ?, ?)');

            if ($stmt->execute(array($this->interfaceid, $key, $value))) {
                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE interfaceattributes SET value = ? WHERE interface = ? AND key = ?');

            return $stmt->execute(array($value, $this->interfaceid, $key));
        }

        return false;
    }
}

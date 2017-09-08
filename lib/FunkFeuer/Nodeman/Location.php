<?php

namespace FunkFeuer\Nodeman;

/**
 * Location data for nodes
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://github.com/decke/nodeman
 */
class Location
{
    private $_handle;
    private $_data = array(
        'locationid'  => null,
        'name'        => null,
        'owner'       => null,
        'address'     => null,
        'latitude'    => null,
        'longitude'   => null,
        'status'      => null,
        'description' => null
    );

    public function __construct($name = null)
    {
        $this->_handle = Config::getDbHandle();

        if($name !== null) {
            $this->load($name);
        }
    }

    public function __get($name)
    {
        if(array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }

        throw new \Exception('Undefined property '.$name.' in class Location');
    }

    public function __set($name, $value)
    {
        if(array_key_exists($name, $this->_data)) {
            $this->_data[$name] = $value;
            return true;
        }

        throw new \Exception('Undefined property '.$name.' in class Location');
    }

    public function load($name)
    {
        $stmt = $this->_handle->prepare("SELECT locationid, name, owner, address,
            latitude, longitude, status, description FROM locations WHERE name = ?");
        if(!$stmt->execute(array($name)))
            return false;

        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $this->_data = $row;

            return true;
        }

        return false;
    }

    public function save()
    {
        if(!$this->locationid)
        {
            $stmt = $this->_handle->prepare("INSERT INTO locations (name, owner, address,
                longitude, latitude, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)");

            if($stmt->execute(array($this->name, $this->owner, $this->address,
                $this->longitude, $this->latitude, $this->status, $this->description)))
            {
                $this->locationid = $this->_handle->lastInsertId();
                return true;
            }
        }
        else
        {
            $stmt = $this->_handle->prepare("UPDATE locations SET name = ?, owner = ?, address = ?,
                longitude = ?, latitude = ?, status = ?, description = ? WHERE locationid = ?");

            return $stmt->execute(array($this->name, $this->owner, $this->address,
                $this->longitude, $this->latitude, $this->status, $this->description, $this->locationid));
        }

        return false;
    }
}

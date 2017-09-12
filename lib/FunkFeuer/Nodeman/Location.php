<?php

namespace FunkFeuer\Nodeman;

/**
 * Location data for nodes.
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
        'gallerylink' => null,
        'description' => null
    );

    public function __construct($locationid = null)
    {
        $this->_handle = Config::getDbHandle();

        if ($locationid !== null) {
            $this->load($locationid);
        }
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }

        throw new \Exception('Undefined property '.$name.' in class Location');
    }

    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->_data)) {
            $this->_data[$name] = $value;

            return true;
        }

        throw new \Exception('Undefined property '.$name.' in class Location');
    }

    public function getLongLat()
    {
        return sprintf('[%f, %f]', $this->latitude, $this->longitude);
    }

    public function load($id)
    {
        $stmt = $this->_handle->prepare('SELECT locationid, name, owner, address,
            latitude, longitude, status, gallerylink, description FROM locations WHERE locationid = ?');
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
        $stmt = $this->_handle->prepare('SELECT locationid, name, owner, address,
            latitude, longitude, status, gallerylink, description FROM locations WHERE name = ?');
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
            $stmt = $this->_handle->prepare('INSERT INTO locations (name, owner, address,
                longitude, latitude, status, gallerylink, description) VALUES (?, ?, ?, ?, ?, ?, ?,?)');

            if ($stmt->execute(array($this->name, $this->owner, $this->address,
                $this->longitude, $this->latitude, $this->status, $this->gallerylink, $this->description))) {
                $this->locationid = $this->_handle->lastInsertId();

                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE locations SET name = ?, owner = ?, address = ?,
                longitude = ?, latitude = ?, status = ?, gallerylink = ?, description = ? WHERE locationid = ?');

            return $stmt->execute(array($this->name, $this->owner, $this->address,
                $this->longitude, $this->latitude, $this->status, $this->gallerylink, $this->description, $this->locationid));
        }

        return false;
    }

    public function getAllLocations($owner = null, $start = 0, $limit = 100)
    {
        $data = array();

        $stmt = $this->_handle->prepare('SELECT locationid FROM locations WHERE (owner = ? OR ? IS NULL) ORDER BY name LIMIT ?, ?');
        if (!$stmt->execute(array($owner, $owner, $start, $limit))) {
            return $data;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = new self($row['locationid']);
        }

        return $data;
    }

    public function countAllLocations($owner = null)
    {
        $stmt = $this->_handle->prepare('SELECT count(*) FROM locations WHERE (owner = ? OR ? IS NULL)');
        if (!$stmt->execute(array($owner, $owner))) {
            return false;
        }

        return $stmt->fetch(\PDO::FETCH_BOTH)[0];
    }

    public function getAllNodes()
    {
        $data = array();

        $stmt = $this->_handle->prepare('SELECT nodeid FROM nodes WHERE (location = ? OR ? IS NULL) ORDER BY nodeid');
        if (!$stmt->execute(array($this->location, $this->location))) {
            return $data;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = new Node($row['nodeid']);
        }

        return $data;
    }

    public function nodeExists($name)
    {
        $stmt = $this->_handle->prepare('SELECT count(*) FROM nodes WHERE location = ? AND LOWER(name) = LOWER(?)');
        if (!$stmt->execute(array($this->location, $name))) {
            return false;
        }

        return $stmt->fetch(\PDO::FETCH_BOTH)[0] > 0;
    }
}

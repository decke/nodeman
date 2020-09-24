<?php

namespace FunkFeuer\Nodeman;

/**
 * Data for Connections between two NetInterfaces
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017-2020 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://github.com/decke/nodeman
 */
class Linkdata
{
    private $_handle;
    private $_data = array(
        'linkid'  => null,
        'fromif'  => null,
        'toif'    => null,
        'quality' => null,
        'source'  => null
    );

    public function __construct($linkid = null)
    {
        $this->_handle = Config::getDbHandle();

        if ($linkid !== null) {
            $this->load($linkid);
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
        $stmt = $this->_handle->prepare('SELECT linkid, fromif, toif, quality, source
            FROM linkdata WHERE linkid = ?');
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
        if (!$this->linkid) {
            $stmt = $this->_handle->prepare('INSERT INTO linkdata (fromif, toif, quality, source)
               VALUES (?, ?, ?, ?)');

            if ($stmt->execute(array($this->fromif, $this->toif, $this->quality, $this->source))) {
                $this->linkid = $this->_handle->lastInsertId();

                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE linkdata SET fromif = ?, toif = ?, quality = ?,
                source = ? WHERE linkid = ?');

            return $stmt->execute(array($this->fromif, $this->toif, $this->quality, $this->source,
                $this->linkid));
        }

        return false;
    }

    public function getFromInterface()
    {
        return new NetInterface($this->fromif);
    }

    public function getToInterface()
    {
        return new NetInterface($this->toif);
    }
}

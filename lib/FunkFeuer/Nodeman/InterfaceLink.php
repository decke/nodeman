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
class InterfaceLink
{
    private $_handle;
    private $_switchfromto = false;

    private $_data = array(
        'linkid'  => null,
        'fromif'  => null,
        'toif'    => null,
        'quality' => null,
        'source'  => null,
        'status'  => null,
        'firstup' => null,
        'lastup'  => null,
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

    public function switchFromTo($switch = true)
    {
        $this->_switchfromto = $switch;
    }

    public function load($id)
    {
        $stmt = $this->_handle->prepare('SELECT linkid, fromif, toif, quality, source, status,
            firstup, lastup FROM linkdata WHERE linkid = ?');
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
            $stmt = $this->_handle->prepare('INSERT INTO linkdata (fromif, toif, quality, source,
               status, firstup, lastup) VALUES (?, ?, ?, ?, ?, ?, ?)');

            if ($stmt->execute(array($this->fromif, $this->toif, $this->quality, $this->source,
                $this->status, $this->firstup, $this->lastup))) {
                $this->linkid = $this->_handle->lastInsertId();

                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE linkdata SET fromif = ?, toif = ?, quality = ?,
                source = ?, status = ?, firstup = ?, lastup = ? WHERE linkid = ?');

            return $stmt->execute(array($this->fromif, $this->toif, $this->quality, $this->source,
                $this->status, $this->firstup, $this->lastup, $this->linkid));
        }

        return false;
    }

    public function loadLinkFromTo($linkidfrom, $linkidto)
    {
        $stmt = $this->_handle->prepare('SELECT linkid, fromif, toif, quality, source, status,
            firstup, lastup FROM linkdata WHERE (fromif = ? AND toif = ?) OR (fromif = ? AND toif = ?)');
        if (!$stmt->execute(array($linkidfrom, $linkidto, $linkidto, $linkidfrom))) {
            return false;
        }

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $this->_data = $row;

            if ($this->fromif != $linkidfrom) {
                $this->switchFromTo();
            } else {
                $this->switchFromTo(false);
            }

            return true;
        }

        return false;
    }

    public function getNetInterfaceByIP($ip)
    {
        $stmt = $this->_handle->prepare('SELECT interfaceid FROM interfaces WHERE address = ?');
        if (!$stmt->execute(array($ip))) {
            return null;
        }

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return new NetInterface($row['interfaceid']);
        }

        return null;
    }

    public function getAllLinks()
    {
        $data = array();

        $stmt = $this->_handle->prepare('SELECT linkid FROM linkdata WHERE 1=1');
        if (!$stmt->execute(array())) {
            return $data;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = new self($row['linkid']);
        }

        return $data;
    }

    public function getFromInterface()
    {
        $iface = ($this->_switchfromto) ? $this->toif : $this->fromif;

        return new NetInterface($iface);
    }

    public function getToInterface()
    {
        $iface = ($this->_switchfromto) ? $this->fromif : $this->toif;

        return new NetInterface($iface);
    }

    public function getFromLocation()
    {
        return $this->getFromInterface()->getNode()->getLocation();
    }

    public function getToLocation()
    {
        return $this->getToInterface()->getNode()->getLocation();
    }
}

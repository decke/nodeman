<?php
declare(strict_types=1);

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
    private \PDO $_handle;

    public int $nodeid;
    public string $name;
    public int $maintainer;
    public int $location;
    public int $createdate;
    public string $description;

    public function __construct(int $nodeid = null)
    {
        $this->_handle = Config::getDbHandle();

        if ($nodeid !== null) {
            $this->load($nodeid);
        }
    }

    public function renderDescription(): string
    {
        $parser = new \Parsedown();
        $parser->setSafeMode(true);
        return $parser->text(str_replace('\r\n', "\n\n", $this->description));
    }

    public function load(int $id): bool
    {
        $stmt = $this->_handle->prepare('SELECT * FROM nodes WHERE nodeid = ?');
        $stmt->setFetchMode(\PDO::FETCH_INTO, $this);

        if (!$stmt->execute(array($id))) {
            return false;
        }

        if ($stmt->fetch(\PDO::FETCH_INTO) === false) {
            return false;
        }

        return true;
    }

    public function save(): bool
    {
        if (!isset($this->nodeid)) {
            $stmt = $this->_handle->prepare('INSERT INTO nodes (name, maintainer, location, createdate,
                description) VALUES (?, ?, ?, ?, ?)');

            if ($stmt->execute(array($this->name, $this->maintainer, $this->location, $this->createdate,
                $this->description))) {
                $this->nodeid = (int)$this->_handle->lastInsertId();

                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE nodes SET name = ?, maintainer = ?, location = ?,
                createdate = ?, description = ? WHERE nodeid = ?');

            return $stmt->execute(array($this->name, $this->maintainer, $this->location, $this->createdate,
                $this->description, $this->nodeid));
        }

        return false;
    }

    public function getLocation(): Location
    {
        return new Location($this->location);
    }

    public function getPath(): string
    {
        return sprintf('%s.%s', $this->getLocation()->name, $this->name);
    }

    public function getInterfaceByName(string $name): ?NetInterface
    {
        $stmt = $this->_handle->prepare('SELECT interfaceid FROM interfaces WHERE node = ? AND name = ?');
        if (!$stmt->execute(array($this->nodeid, $name))) {
            return null;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return new NetInterface((int)$row['interfaceid']);
        }

        return null;
    }

    public function getAllInterfaces(): array
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
            $data[] = new NetInterface((int)$row['interfaceid']);
        }

        return $data;
    }

    public function getAllLinks(): array
    {
        if (!$this->nodeid) {
            throw new \Exception('Node does not have an ID yet in class Node');
        }

        $data = array();

        foreach ($this->getAllInterfaces() as $interface) {
            $stmt = $this->_handle->prepare('SELECT linkid FROM linkdata WHERE fromif = ? OR toif = ? ORDER BY linkid');
            if (!$stmt->execute(array($interface->interfaceid, $interface->interfaceid))) {
                return $data;
            }

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $link = new InterfaceLink((int)$row['linkid']);

                if ($link->fromif != $interface->interfaceid) {
                    $link->switchFromTo();
                }

                $data[] = $link;
            }
        }

        return $data;
    }

    public function getAllAttributes(): array
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

    public function getAttribute(string $key): ?string
    {
        if (!$this->nodeid) {
            throw new \Exception('Node does not have an ID yet in class Node');
        }

        $stmt = $this->_handle->prepare('SELECT value FROM nodeattributes WHERE node = ? AND key = ?');
        if (!$stmt->execute(array($this->nodeid, $key))) {
            return null;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $row['value'];
        }

        return null;
    }

    public function delAttribute(string $key): bool
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

    public function setAttribute(string $key, string $value): bool
    {
        if (!$this->nodeid) {
            throw new \Exception('Node does not have an ID yet in class Node');
        }

        if ($this->getAttribute($key) === null) {
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

    public function delete(): bool
    {
        foreach($this->getAllInterfaces() as $iface) {
            if (!$iface->delete()) {
                return false;
            }
        }

        foreach($this->getAllAttributes() as $attr) {
            if (!$this->delAttribute($attr)) {
                return false;
            }
        }

        $stmt = $this->_handle->prepare('DELETE FROM nodes WHERE nodeid = ?');
        if (!$stmt->execute(array($this->nodeid))) {
            return false;
        }

        return true;
    }

}

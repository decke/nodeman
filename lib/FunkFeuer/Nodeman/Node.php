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
    private array $_data = array(
        'nodeid'        => null,
        'name'          => null,
        'owner'         => null,
        'location'      => null,
        'createdate'    => null,
        'description'   => null
    );

    public function __construct(int $nodeid = null)
    {
        $this->_handle = Config::getDbHandle();

        if ($nodeid !== null) {
            $this->load($nodeid);
        }
    }

    public function __get(string $name): string
    {
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }

        throw new \Exception('Undefined property '.$name.' in class Node');
    }

    public function __set(string $name, string $value): bool
    {
        if (array_key_exists($name, $this->_data)) {
            $this->_data[$name] = $value;

            return true;
        }

        throw new \Exception('Undefined property '.$name.' in class Node');
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->_data);
    }

    public function renderDescription(): bool
    {
        $parser = new \Parsedown();
        $parser->setSafeMode(true);
        return $parser->text(str_replace('\r\n', "\n\n", $this->description));
    }

    public function load(int $id): bool
    {
        $stmt = $this->_handle->prepare('SELECT nodeid, name, owner, location, createdate,
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

    public function save(): bool
    {
        if (!$this->nodeid) {
            $stmt = $this->_handle->prepare('INSERT INTO nodes (name, owner, location, createdate,
                description) VALUES (?, ?, ?, ?, ?)');

            if ($stmt->execute(array($this->name, $this->owner, $this->location, $this->createdate,
                $this->description))) {
                $this->nodeid = $this->_handle->lastInsertId();

                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE nodes SET name = ?, owner = ?, location = ?,
                createdate = ?, description = ? WHERE nodeid = ?');

            return $stmt->execute(array($this->name, $this->owner, $this->location, $this->createdate,
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
            return new NetInterface($row['interfaceid']);
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
            $data[] = new NetInterface($row['interfaceid']);
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
                $link = new InterfaceLink($row['linkid']);

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
}

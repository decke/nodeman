<?php
declare(strict_types=1);

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
    private \PDO $_handle;
    private array $_data = array(
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

    public function __construct(int $interfaceid = null)
    {
        $this->_handle = Config::getDbHandle();

        if ($interfaceid !== null) {
            $this->load($interfaceid);
        }
    }

    public function __get(string $name): ?string
    {
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }

        throw new \Exception('Undefined property '.$name.' in class '.__CLASS__);
    }

    public function __set(string $name, string $value): bool
    {
        if (array_key_exists($name, $this->_data)) {
            $this->_data[$name] = $value;

            return true;
        }

        throw new \Exception('Undefined property '.$name.' in class '.__CLASS__);
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->_data);
    }

    public function renderDescription(): string
    {
        $parser = new \Parsedown();
        $parser->setSafeMode(true);
        return $parser->text(str_replace('\r\n', "\n\n", $this->description));
    }

    public function load(int $id): bool
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

    public function save(): bool
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

    public function loadByIPAddress(string $address): bool
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

    public function loadByPath(string $path): bool
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
        if ($iface === null) {
            throw new \Exception('Invalid NetInterface path '.$path);
        }

        return $this->load($iface->interfaceid);
    }

    public function getPath(): string
    {
        return sprintf('%s.%s.%s', $this->getNode()->getLocation()->name, $this->getNode()->name, $this->name);
    }

    public function getNode(): Node
    {
        return new Node((int)$this->node);
    }

    public function recalcStatus(): bool
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

    public function getAllAttributes(): array
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

    public function getAttribute(string $key): ?string
    {
        if (!$this->interfaceid) {
            throw new \Exception('NetInterface does not have an ID yet in class NetInterface');
        }

        $stmt = $this->_handle->prepare('SELECT value FROM interfaceattributes WHERE interface = ? AND key = ?');
        if (!$stmt->execute(array($this->interfaceid, $key))) {
            return null;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $row['value'];
        }

        return null;
    }

    public function delAttribute(string $key): bool
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

    public function setAttribute(string $key, string $value): bool
    {
        if (!$this->interfaceid) {
            throw new \Exception('NetInterface does not have an ID yet in class NetInterface');
        }

        if ($this->getAttribute($key) === null) {
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

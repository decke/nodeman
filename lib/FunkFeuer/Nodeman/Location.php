<?php
declare(strict_types=1);

namespace FunkFeuer\Nodeman;

/**
 * Location data for nodes.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2017-2020 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://github.com/decke/nodeman
 */
class Location
{
    private \PDO $_handle;

    public int $locationid;
    public string $name;
    public int $owner;
    public string $address;
    public float $latitude;
    public float $longitude;
    public string $status;
    public string $gallerylink;
    public int $createdate;
    public string $description;

    public function __construct(int $locationid = null)
    {
        $this->_handle = Config::getDbHandle();

        if ($locationid !== null) {
            $this->load($locationid);
        }
    }

    public function getLongLat(): string
    {
        return sprintf('[%f, %f]', $this->latitude, $this->longitude);
    }

    public function renderDescription(): string
    {
        $parser = new \Parsedown();
        $parser->setSafeMode(true);
        return $parser->text(str_replace('\r\n', "\n\n", $this->description));
    }

    public function load(int $id): bool
    {
        $stmt = $this->_handle->prepare('SELECT * FROM locations WHERE locationid = ?');
        $stmt->setFetchMode(\PDO::FETCH_INTO, $this);

        if (!$stmt->execute(array($id))) {
            return false;
        }

        if ($stmt->fetch(\PDO::FETCH_INTO) === false) {
            return false;
        }

        return true;
    }

    public function loadByName(string $name): bool
    {
        $stmt = $this->_handle->prepare('SELECT * FROM locations WHERE name = ?');
        $stmt->setFetchMode(\PDO::FETCH_INTO, $this);

        if (!$stmt->execute(array($name))) {
            return false;
        }

        if ($stmt->fetch(\PDO::FETCH_INTO) === false) {
            return false;
        }

        return true;
    }

    public function save(): bool
    {
        if (!isset($this->locationid)) {
            $stmt = $this->_handle->prepare('INSERT INTO locations (name, owner, address,
                longitude, latitude, status, gallerylink, createdate, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)');

            if ($stmt->execute(array($this->name, $this->owner, $this->address,
                $this->longitude, $this->latitude, $this->status, $this->gallerylink, $this->createdate, $this->description))) {
                $this->locationid = (int)$this->_handle->lastInsertId();

                return true;
            }
        } else {
            $stmt = $this->_handle->prepare('UPDATE locations SET name = ?, owner = ?, address = ?,
                longitude = ?, latitude = ?, status = ?, gallerylink = ?, createdate = ?, description = ? WHERE locationid = ?');

            return $stmt->execute(array($this->name, $this->owner, $this->address,
                $this->longitude, $this->latitude, $this->status, $this->gallerylink, $this->createdate, $this->description, $this->locationid));
        }

        return false;
    }

    public function recalcStatus(): bool
    {
        $stmt = $this->_handle->prepare('SELECT count(*) FROM interfaces WHERE node IN (SELECT nodeid FROM nodes WHERE location = ?) AND status = ?');
        if (!$stmt->execute(array($this->locationid, 'online'))) {
            return false;
        }

        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            if ($row[0] > 0) {
                $this->status = 'online';
            } else {
                $this->status = 'offline';
            }

            return $this->save();
        }

        return false;
    }

    public function getAllLocations(int $owner = null, int $start = 0, int $limit = 100): array
    {
        $data = array();

        $stmt = $this->_handle->prepare('SELECT locationid FROM locations WHERE (owner = ? OR ? IS NULL) ORDER BY name LIMIT ?, ?');
        if (!$stmt->execute(array($owner, $owner, $start, $limit))) {
            return $data;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = new self((int)$row['locationid']);
        }

        return $data;
    }

    public function getMaintainer(): User
    {
        return new User($this->owner);
    }

    public function countAllLocations(int $owner = null): int
    {
        $stmt = $this->_handle->prepare('SELECT count(*) FROM locations WHERE (owner = ? OR ? IS NULL)');
        if (!$stmt->execute(array($owner, $owner))) {
            return 0;
        }

        return (int)$stmt->fetch(\PDO::FETCH_NUM)[0];
    }

    public function countNodes(): int
    {
        $stmt = $this->_handle->prepare('SELECT count(*) FROM nodes WHERE location = ?');
        if (!$stmt->execute(array($this->locationid))) {
            return 0;
        }

        return (int)$stmt->fetch(\PDO::FETCH_NUM)[0];
    }

    public function getNodes(): array
    {
        $data = array();

        $stmt = $this->_handle->prepare('SELECT nodeid FROM nodes WHERE (location = ? OR ? IS NULL) ORDER BY nodeid');
        if (!$stmt->execute(array($this->locationid, $this->locationid))) {
            return $data;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = new Node((int)$row['nodeid']);
        }

        return $data;
    }

    public function getNodeByName(string $name): ?Node
    {
        $stmt = $this->_handle->prepare('SELECT nodeid FROM nodes WHERE location = ? AND name = ?');
        if (!$stmt->execute(array($this->locationid, $name))) {
            return null;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return new Node((int)$row['nodeid']);
        }

        return null;
    }

    public function nodeExists(string $name): bool
    {
        $stmt = $this->_handle->prepare('SELECT count(*) FROM nodes WHERE location = ? AND LOWER(name) = LOWER(?)');
        if (!$stmt->execute(array($this->locationid, $name))) {
            return false;
        }

        return (int)$stmt->fetch(\PDO::FETCH_NUM)[0] > 0;
    }
}

<?php
declare(strict_types=1);

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
    private \PDO $_handle;
    private bool $_switchfromto = false;

    public int $linkid;
    public int $fromif;
    public int $toif;
    public float $quality;
    public string $source;
    public string $status;
    public int $firstup;
    public int $lastup;

    public function __construct(int $linkid = null)
    {
        $this->_handle = Config::getDbHandle();

        if ($linkid !== null) {
            $this->load($linkid);
        }
    }

    public function switchFromTo(bool $switch = true): void
    {
        $this->_switchfromto = $switch;
    }

    public function load(int $id): bool
    {
        $stmt = $this->_handle->prepare('SELECT * FROM linkdata WHERE linkid = ?');
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
        if (!isset($this->linkid)) {
            $stmt = $this->_handle->prepare('INSERT INTO linkdata (fromif, toif, quality, source,
               status, firstup, lastup) VALUES (?, ?, ?, ?, ?, ?, ?)');

            if ($stmt->execute(array($this->fromif, $this->toif, $this->quality, $this->source,
                $this->status, $this->firstup, $this->lastup))) {
                $this->linkid = (int)$this->_handle->lastInsertId();

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

    public function loadLinkFromTo(int $linkidfrom, int $linkidto): bool
    {
        $stmt = $this->_handle->prepare('SELECT * FROM linkdata WHERE (fromif = ? AND toif = ?) OR (fromif = ? AND toif = ?)');
        $stmt->setFetchMode(\PDO::FETCH_INTO, $this);

        if (!$stmt->execute(array($linkidfrom, $linkidto, $linkidto, $linkidfrom))) {
            return false;
        }

        if ($stmt->fetch(\PDO::FETCH_INTO) === false) {
            return false;
        }

        if ($this->fromif != $linkidfrom) {
            $this->switchFromTo();
        } else {
            $this->switchFromTo(false);
        }

        return true;
    }

    public function getNetInterfaceByIP(string $ip): ?NetInterface
    {
        $stmt = $this->_handle->prepare('SELECT interfaceid FROM interfaces WHERE address = ?');
        if (!$stmt->execute(array($ip))) {
            return null;
        }

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return new NetInterface((int)$row['interfaceid']);
        }

        return null;
    }

    public function getAllLinks(): array
    {
        $data = array();

        $stmt = $this->_handle->prepare('SELECT linkid FROM linkdata WHERE 1=1');
        if (!$stmt->execute(array())) {
            return $data;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = new self((int)$row['linkid']);
        }

        return $data;
    }

    public function getFromInterface(): NetInterface
    {
        $iface = ($this->_switchfromto) ? $this->toif : $this->fromif;

        return new NetInterface((int)$iface);
    }

    public function getToInterface(): NetInterface
    {
        $iface = ($this->_switchfromto) ? $this->fromif : $this->toif;

        return new NetInterface((int)$iface);
    }

    public function getFromLocation(): Location
    {
        return $this->getFromInterface()->getNode()->getLocation();
    }

    public function getToLocation(): Location
    {
        return $this->getToInterface()->getNode()->getLocation();
    }
}

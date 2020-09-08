# Nodeman

Nodeman ist der Nachfolger von manman und wird bei Funkfeuer Graz für die
Verwaltung, Dokumentation und das Monitoring des Funkfeuer Netzes verwendet.

## Das Netz

Im Gegensatz zu anderen freien Netzen basiert das Funkfeuer Netz in Graz
aus einem Backbone Netz (5bone) mit WLAN Punkt zu Punkt Verbindungen und
einzelnen Glasfaserverbindungen.
Manche Standorte sind auch per OpenVPN Tunnel angebunden.

## Core Team

Das Core Team kümmert sich gemeinsam um das Backbone Netz und die
Infrastruktur.
Knotenbetreiber kümmern sich selbst um ihre Knoten.


# Aufbau

## techn. Anforderungen

- PHP7
- sqlite
- olsrd (v1)

## Definitionen

- Location / Node / Interface / Verbindungsarten
- 5bone Netz, fiber, wifi2.4, wifi5, wifi60, tunnel

## olsrd

- Link detection

## Beteiligte externe Systeme (DNS, Karte, Smokeping, OpenVPN, Statistiken)

- Smokeping export ala: https://manman.graz.funkfeuer.at/export/smokeping


# Features

## Karte

## Benutzerverwaltung

- Admin Bereich aehnlich Datenbankbrowser

## Knotenverwaltung

- Topology Übersicht über Location wie Nodes und Interfaces untereinander und mit dem
  restlichen Netz verbunden sind. (JS: http://sigmajs.org/)

## Knotendokumentation

- Attributes (Key/Value) frei definierbar für Nodes und Interfaces
    vordefiniert: Hardware, Firmware, VLANID, Antenne, Antennengewinn, Bandbreite, BSSID, Altitude

- Markdown fuer Kommentare (parsedown safemode fuer untrusted user input)

## IP Adressvergabe

- IP Pool Verwaltung
- IPv4 und IPv6 Support
- Zuweisung von /32 oder größeren Subnetzen


## Monitoring von Knoten und Links

- OPT IN: festlegen von Gegenstelle
- Überwachung von Knoten die erreichbar sein müssen
- Überwachung von Links die verbunden sein müssen
- EMail Benachrichtigung wenn Knoten/Links down gehen
- Statusseite welche Knoten/Links fehlen
- Prüfung falls nicht vergebene IPs aus definiertem Range verwendet werden
- Plausibilität prüfen ob IP mit korrekter Gegenstelle verbunden ist (falls Gegenstelle eingetragen ist)


# Wunschliste

## Versionierung

## Gallerie

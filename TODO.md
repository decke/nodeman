## TODO
* Location Overview: linkdata lastup anzeigen wenn status down
* Location Overview: Proxy links intern/extern anzeigen http://wan.unused.bluelife2.ext.graz.funkfeuer.at

* updatehnadata: ping test ob client erreichbar?
* Location delete nur wenn keine nodes mehr
* Locations: Spalte "links online / links gesamt"

* LinkMonitoring: eigene Tabelle (id, linkid, userid, lastnotified)
* LinkMonitoring: anzeigen ob link monitored, hinzufuegen/loeschen von monitoring fuer link
* LinkMonitoring: EMail Benachrichtigung wenn Link down geht

* Location status wenn offline und max(lastup) < 10tage -> obsolete
* Manuelles eintragen von Links (zB Fiber TU - Realraum)
* Attribute in nodes einbauen (Hardware, Firmware, Altitude)
* Attribute in interfaces einbauen (BSSID, Bandbreite, VLANID, Antenne, Antennengewinn)

* Kontaktdaten von Location maintainer (Name, Email, Telefonnummer) nur fuer
    eingeloggte Benutzer anzeigen

high
* Nodes (Equipment) anzeigen / anlegen / editieren
* Interfaces anlegen

medium
* Tunnel erkennung einbauen (in migrations script)
* Admin Bereich aehnlich Datenbankbrowser mit HTML5
    https://w3lessons.info/html5-inline-edit-with-php-mysql-jquery-ajax/
* Admins duerfen auch Locations/Nodes/Interfaces editieren

bugs
* MAP: Icons auf Chrome reparieren
* Layout mit Link anzeige auf smartphones kaputt

wishlist
* IP Pools und Adresszuweisung
  * allocations tabelle die mit location verbunden ist
  * self assignment durch user fuer interfaces und dann Eintrag der IP wie bisher
* OpenVPN Zertifikat beantragen

* editieren von User, Locations, Nodes, Interfaces
* loeschen von Interfaces, Nodes, Locations, User
* PLZ und Ort automatisch setzen mit OSM Nominatim

* DSGVO


## QUESTIONS
* Aktueller manman Datenbankabzug (aj)
* EMail an alle Knotenbetreiber die jsoninfo/httpinfo/txtinfo nicht aktiviert haben?

* Statistics integrieren in nodeman? (olsrd versionen, #links)
* Status Seite integrieren (monitored links down, ips die verwendet werden aber nicht alloziert sind, inkonsistente links (fiber zu wifi5))

* Wenn neue Locations angelegt werden wie kriegen das die anderen mit
  * DNS
  * HTTP Proxy (*.ext.graz.funkfeuer.at)
  * Statistiken
  * OpenVPN Tunnel?
* Wie soll die IP Adressvergabe funktionieren? automatisch|manuelle zuweisung, immer 1 IP|IP Block?


## Links
* https://git.ffgraz.net/karte.git/tree/data.php
* https://github.com/ffgraz/manman
* https://git.ffgraz.net/
* http://nominatim.openstreetmap.org/search?q=Weizbachweg+40a&countrycodes=at&format=json&addressdetails=1

* https://wiki.funkfeuer.at/wiki/Projekte/0xFF-NodeMap
* https://github.com/freifunk-gluon/packages/tree/master/net/respondd

## TODO

### MGMT

* EMail an core fuer Knotenbetreiber die jsoninfo/httpinfo/txtinfo nicht aktiviert haben
    config snippet fuer olsr versionen und Erklaerung


### QUICKFIX

* BUG: MAP: Icons auf Chrome reparieren
* BUG: Layout mit Link anzeige auf smartphones kaputt
* QUICK: Cookie nur setzen wenn User sich einloggt
* QUICK: fullname statt firstname + lastname, checkbox ob sichtbar
* QUICK: Mehrere Maintainer Felder pro Location (hauptmaintainer kann alles, andere nur hinzufuegen, aendern)
* QUICK: Admins duerfen auch Locations/Nodes/Interfaces editieren
* DSGVO: checkboxen ob name/email/telefonnummer fuer andere sichbar sein soll (default off)
* cleanupdb: wenn location status offline und max(lastup) < 10tage -> obsolete
* cleanupdb: aufraeumen soll grossteils manuell passieren


### migratedb

* migratedb: unterschiedliche maintainer von location und node beruecksichtigen
* migratedb: html URLs zu Markdown konvertieren und manman zu nodeman
* migratedb: fivebone mit neuem dump aktualisieren
* migratedb: Tunnel erkennung einbauen
* migratedb: warnings bei migration reparieren (dateninkonsistenzen von manman)


### LOCATION

* Location: HTTP proxy abschaltbar wie smokeping (default off)
* Location Overview: linkdata lastup anzeigen wenn status down
* Location Overview: Proxy links intern/extern anzeigen http://wan.unused.bluelife2.ext.graz.funkfeuer.at
* Location delete nur wenn keine nodes mehr
* Locations: Spalte "links online / links gesamt"


### IP ASSIGNMENT

* IP Pools und Adresszuweisung auf User
* Assignments durch FF fuer User, Person vergibt IPs dann bei den Knoten selbst
* FF Admin kann auch anstatt user Interfaces anlegen und IPs eintragen (allozierung aber auf user)


### LINKMONITORING
* eigene Tabelle (id, linkid, userid, lastnotified)
* anzeigen ob link monitored, hinzufuegen/loeschen von monitoring fuer link
* EMail Benachrichtigung wenn Link down geht


### OTHER

* MESSAGES: Messages integrieren ala ECE-Dashboard
* MESSAGES: Allocation fuer IP/Subnetz/PublicIP beantragen, OpenVPN Zertifikat beantragen
* Manuelles eintragen von Links (zB Fiber TU - Realraum)


### FEATURES

* Locations: anzeigen / anlegen / editieren / loeschen
* Nodes: anzeigen / anlegen / editieren / loeschen
* Interfaces: anzeigen / anlegen / editieren / loeschen
* User: editieren / loeschen
* Attribute (Nodes / Interfaces): anlegen / editieren / loeschen durch User
    nodes: Hardware, Firmware, Altitude
    interfaces: BSSID, Bandbreite, VLANID, Antenne, Antennengewinn


### WISHLIST

* STATS: einfache Statistiken in nodeman integrieren wie derzeit bei stats.ffgraz.net (olsrd versionen, links, IPs/knoten up, topology)
* STATS: topology karte mit DOT Language in visjs: https://visjs.github.io/vis-network/examples/network/data/dotLanguage/dotLanguage.html

* STATUS: Status Seite integrieren (monitored links down, ips die verwendet werden aber nicht alloziert sind, inkonsistente links (fiber zu wifi5))

* MIGRATION: wie authentifiziert Wiki die User? (email oder benutzername, ev. braucht nodeman usernamen doch noch)
* MIGRATION: Daten Export fuer DNS, HTTP Proxy (nginx) (Format mit AJ absprechen)

* PLZ und Ort automatisch setzen mit OSM Nominatim


## QUESTIONS

* updatehnadata: ping test ob client erreichbar?


## Links
* https://hamnetdb.net/

* https://git.ffgraz.net/karte.git/tree/data.php
* https://github.com/ffgraz/manman
* https://git.ffgraz.net/
* http://nominatim.openstreetmap.org/search?q=Weizbachweg+40a&countrycodes=at&format=json&addressdetails=1

* https://wiki.funkfeuer.at/wiki/Projekte/0xFF-NodeMap
* https://github.com/freifunk-gluon/packages/tree/master/net/respondd

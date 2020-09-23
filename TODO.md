## TODO

* Utility funktion: NetInterface::loadByPath("tub.combinesch.lan")
* backbone_links in migration einbauen
* Manuelles eintragen von Links (zB Fiber TU - Realraum)
* Attribute in nodes einbauen (Hardware, Firmware, Altitude)
* Attribute in interfaces einbauen (BSSID, Bandbreite, VLANID, Antenne, Antennengewinn)
* Tower SVGs wie feather icons integrieren
* Login verbieten wenn EMail nicht verifiziert ist

high
* Login eigene Maske
* Interfaces anlegen
* Location Details (Gallerylink, Smokeping, Link zur Karte)

medium
* Tunnel erkennung einbauen (in migrations script)
* Map: Tunnel links in blau zeichnen
* Admin Bereich aehnlich Datenbankbrowser mit HTML5
    https://w3lessons.info/html5-inline-edit-with-php-mysql-jquery-ajax/

wishlist
* Planned links hinzufuegen
* Kommentare und Dokumentation (parsedown, setSafeMode(true))
* IP Pools und Adresszuweisung
* OpenVPN Zertifikat beantragen

* SmokePing config export ala https://manman.graz.funkfeuer.at/export/smokeping
* editieren von User, Locations, Nodes, Interfaces
* loeschen von Interfaces, Nodes, Locations, User
* private/hidden flag fuer Nodes und Links
* altitutde information pro Node
* PLZ und Ort automatisch setzen mit OSM Nominatim

* DSGVO


## BUGS

* Konvertierung Umlaute sind kaputt (MySQL dump schon kaputt?)


## QUESTIONS

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

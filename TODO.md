## TODO

* Tower SVGs wie feather icons integrieren

high
* Login eigene Maske
* Interfaces anlegen
* Location Details (Gallerylink, Smokeping, Link zur Karte)

medium
* Tunnel erkennung einbauen (in migrations script)
* Map: Tunnel links in blau zeichnen
* Admin Bereich aehnlich Datenbankbrowser

low
* Planned links hinzufuegen
* Kommentare und Dokumentation (markdown)
* IP Pools und Adresszuweisung
* OpenVPN Zertifikat beantragen

* SmokePing config export ala https://manman.graz.funkfeuer.at/export/smokeping
* editieren von User, Locations, Nodes, Interfaces
* loeschen von Interfaces, Nodes, Locations, User
* private/hidden flag fuer Nodes und Links
* PLZ und Ort automatisch setzen mit OSM Nominatim

* Passwort vergessen
* EMail verification bei Registrierung
* DSGVO


## BUGS

* Konvertierung Umlaute sind kaputt (MySQL dump schon kaputt?)


## QUESTIONS

* Wie sind derzeit DNS, HTTP Proxy und Statistiken integriert?
* Wie soll die IP Adressvergabe funktionieren? automatisch|manuelle zuweisung, immer 1 IP|IP Block?
* Wie soll OpenVPN integriert werden?


## Links
* https://git.ffgraz.net/karte.git/tree/data.php
* https://github.com/ffgraz/manman
* https://git.ffgraz.net/
* http://nominatim.openstreetmap.org/search?q=Weizbachweg+40a&countrycodes=at&format=json&addressdetails=1

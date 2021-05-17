## TODO

* updatelinkdata: alle interfaces die online sind mit MID Liste (main+alias) abgleichen und was dort nicht drin ist -> down setzen
* TOPO: MID Daten sagen welche IPs es noch auf dem Geraet gibt
 * bei Topology einbauen, alle IPs auf up setzen
 * wie koennen wir mit Topology Daten dann die Links einer IP zuordnen?


### QUICKFIX

* KARTE: Verbindungsqualitaet anzeigen mit linienstaerke?
* KARTE: Kupfer Verbindungsart von tri280_aa zu tri280_ab
* KARTE: Permalink der layerauswahl, zoom, position beruecksichtigt
    https://blog.mastermaps.com/2012/10/how-to-control-your-leaflet-map-with.html
* LOCATION: Links alphabetisch sortieren (FROM asc, TO asc)
* LOCATION: Linkquality anzeigen
* OLSRINFO: nur IPs die in OLSRD topology aufscheinen auch anfragen (ca 230)
* BUG: MAP: Icons auf Chrome reparieren
* BUG: Layout mit Link anzeige auf smartphones kaputt
* QUICK: Mehrere Maintainer Felder pro Location (hauptmaintainer kann alles, andere nur hinzufuegen, aendern)
* QUICK: Admins duerfen auch Locations/Nodes/Interfaces editieren
* DSGVO: checkboxen ob name/email/telefonnummer fuer andere sichbar sein soll (default off)
* QUICK: ETX einbauen, ETX=1/(LinkQuality x NeighborLinkQuality)
   https://wiki.funkfeuer.at/index.php/FAQ#Was_bedeuten_die_Werte_f.C3.BCr_LinkQuality_.2F_NLQ_.2F_ETX_in_der_Freifunk-Firmware.3F

### migratedb

* migratedb: linktyp erkennung einbauen wie in karte: https://git.ffgraz.net/karte.git/plain/data.php
* migratedb: fiber konvertieren aus backbone_links (type=1)
* migratedb: fivebone links eintragen

Link laut DB            Loc A      Loc B       Iface A - Iface B
============            ========   ========    ======================
algo-mkl                algo       mk
g68-r3                  graba63    r3
graba68-wie19           graba68    wieland19
hbg31-steinbruch        hbg31      steinbruch
mcg-graba68             mcg        graba68
mcg-ner                 mcg        ???
mit-poh                 mitteregg  ???
mkl-idl52               mkl        idl52
mkl-rh                  mkl        ???
mkl-suedtirloerplatz    mkl        suedtiroler   ??? suedtiroler.router.wifi
rhs-rb88                rhsender   rb88
steinbruch-venlo        steinbruch venlo         ??? venlo.5ag-sb.wifi
stg30-inf10             steyrer30  inffeld10
teipl-stiwoll           ???        ???
zoes-moarweg            zoesenberg40 ???
zoes-stiwoll            zoes       ???
spek-mkl                spektral   mkl           spektral.mkl.wifi ???
spek-hoch               spektral   hochstein


### LOCATION

* Location Overview: MAP: alle links (auch offline in rot) zu der location anzeigen
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
* olsrinfo: interface namen per json auslesen und in DB anpassen

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
* STATS: topology karte mit DOT Language
    halfviz: http://arborjs.org/halfviz/
    ngraph: https://github.com/anvaka/ngraph.fromdot
    visjs: https://visjs.github.io/vis-network/examples/network/data/dotLanguage/dotLanguage.html

* KARTE: Entfernungsmesstool
* KARTE: Stich von Punkt A zu Punkt B zeichnen fuer planung
* STATUS: Status Seite integrieren (monitored links down, ips die verwendet werden aber nicht alloziert sind, inkonsistente links (fiber zu wifi5))

* MIGRATION: Wiki auth anpassen (verwendet auch MySQL connection) https://wiki.graz.funkfeuer.at/ManmanAuth
* MIGRATION: Daten Export fuer DNS, HTTP Proxy (nginx) (Format mit AJ absprechen)

* PLZ und Ort automatisch setzen mit OSM Nominatim


## QUESTIONS

* Karte: wie werden user authentifiziert
* updatehnadata: ping test ob client erreichbar?


## Links
* https://hamnetdb.net/

* https://git.ffgraz.net/karte.git/tree/data.php
* https://github.com/ffgraz/manman
* https://git.ffgraz.net/
* http://nominatim.openstreetmap.org/search?q=Weizbachweg+40a&countrycodes=at&format=json&addressdetails=1

* https://wiki.funkfeuer.at/wiki/Projekte/0xFF-NodeMap
* https://github.com/freifunk-gluon/packages/tree/master/net/respondd
* https://openwrt.org/docs/guide-user/additional-software/imagebuilder
* https://github.com/aparcar/asu

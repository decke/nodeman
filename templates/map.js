var map;

{% if deflocation %}
var deflocation = [{{ deflocation.lat }}, {{ deflocation.lng }}];
{% else %}
var deflocation = [47.0707, 15.4395];
{% endif %}
var defzoom = 13;

function initmap() {
   // create the tile layer with correct attribution
   var basemap = L.tileLayer('https://maps{s}.wien.gv.at/basemap/geolandbasemap/normal/google3857/{z}/{y}/{x}.{format}', {
      	maxZoom: 20,
	attribution: 'Datenquelle: <a href="https://www.basemap.at/">basemap.at</a>',
	subdomains: ["", "1", "2", "3", "4"],
	format: 'png',
	bounds: [[46.35877, 8.782379], [49.037872, 17.189532]]
   });

   var orthomap = L.tileLayer('https://maps{s}.wien.gv.at/basemap/bmaporthofoto30cm/normal/google3857/{z}/{y}/{x}.{format}', {
      	maxZoom: 20,
	attribution: 'Datenquelle: <a href="https://www.basemap.at/">basemap.at</a>',
	subdomains: ["", "1", "2", "3", "4"],
	format: 'jpeg',
	bounds: [[46.35877, 8.782379], [49.037872, 17.189532]]
   });

   var topomap = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
	attribution: 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ, TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL, Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong), and the GIS User Community'
   });

   map = new L.map('map');

   // start the map in Graz
   map.setView(deflocation, defzoom);
   map.addLayer(basemap);

   L.control.layers({ "Karte": basemap, "Satellit": orthomap, "Terrain": topomap }).addTo(map);

   {% if deflocation %}
   L.marker([{{ deflocation.lat }}, {{ deflocation.lng }}]).addTo(map);
   {% endif %}

   // custom icons
   var TowerIcon = L.Icon.extend({
      options: {
         iconSize: [ 24, 24 ],
         iconAnchor: [ 12, 20 ],
         popupAnchor: [ 0, -12 ]
      }
   });

   var OfflineIcon = L.Icon.extend({
      options: {
         iconSize: [ 10, 10 ],
         iconAnchor: [ 12, 20 ],
         popupAnchor: [ 0, -12 ]
      }
   });

   var onlineIcon = new TowerIcon({iconUrl: '/images/tower-online.svg'});
   var offlineIcon = new OfflineIcon({iconUrl: '/images/tower-offline.svg'});
   var tunnelIcon = new TowerIcon({iconUrl: '/images/tower-tunnel.svg'});

   {% for loc in locations %}
       L.marker({{ loc.location }}, {icon:{{ loc.type}}Icon}).addTo(map).bindPopup("{{ loc.popup|raw }}");
   {% endfor %}

   var links = [
       {% for link in links %}
           [ {{ link.from }}, {{ link.to }} ]{% if not loop.last %},{% endif %}
       {% endfor %}
   ];

   L.polyline(links, { color: 'green', weight: 3, opacity: 0.5 }).addTo(map);
}

initmap();


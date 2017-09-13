var map;

var deflocation = [47.0707, 15.4395];
var defzoom = 13;

function initmap() {
   map = new L.map('map');

   // create the tile layer with correct attribution
   var layer = L.tileLayer('https://maps{s}.wien.gv.at/basemap/geolandbasemap/normal/google3857/{z}/{y}/{x}.{format}', {
      	maxZoom: 20,
	attribution: 'Datenquelle: <a href="www.basemap.at">basemap.at</a>',
	subdomains: ["", "1", "2", "3", "4"],
	format: 'png',
	bounds: [[46.35877, 8.782379], [49.037872, 17.189532]]
   });

   // start the map in Graz
   map.setView(deflocation, defzoom);
   map.addLayer(layer);

   // custom icons
   var TowerIcon = L.Icon.extend({
      options: {
         iconSize: [ 24, 24 ],
         iconAnchor: [ 12, 20 ],
         popupAnchor: [ 0, -12 ]
      }
   });

   var onlineIcon = new TowerIcon({iconUrl: '/images/tower-online.svg'});
   var offlineIcon = new TowerIcon({iconUrl: '/images/tower-offline.svg'});
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


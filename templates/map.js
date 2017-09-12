var map;

var deflocation = [47.0707, 15.4395];
var defzoom = 13;

function initmap() {
   map = new L.map('map');

   // create the tile layer with correct attribution
   var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
   var osmAttrib='Map data &copy;  <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
   var osm = new L.TileLayer(osmUrl, {minZoom: 10, maxZoom: 19, attribution: osmAttrib});		

   // start the map in Graz
   map.setView(deflocation, defzoom);
   map.addLayer(osm);

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

   var link = L.polyline(links, { color: 'green', weight: 3, opacity: 0.5 }).addTo(map);
   map.fitBounds(polyline.getBounds());
}

initmap();


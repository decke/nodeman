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

   var onlineIcon = new TowerIcon({iconUrl: 'css/images/tower-online.svg'});
   var offlineIcon = new TowerIcon({iconUrl: 'css/images/tower-offline.svg'});
   var tunnelIcon = new TowerIcon({iconUrl: 'css/images/tower-tunnel.svg'});

   L.marker([47.0800, 15.4400], { icon: onlineIcon }).addTo(map).bindPopup("node1");
   L.marker([47.0710, 15.4390], { icon: offlineIcon }).addTo(map).bindPopup("node2");
   L.marker([47.0850, 15.4490], { icon: tunnelIcon }).addTo(map).bindPopup("node2");

   var latlngs = [
      [ [47.0800, 15.4400], [47.0710, 15.4390] ],
      [ [47.0730, 15.4420], [47.0700, 15.4420] ],
      [ [47.0730, 15.4420], [47.0710, 15.4390] ]
   ];

   var link = L.polyline(latlngs, { color: 'green', weight: 3, opacity: 0.5 }).addTo(map);
   map.fitBounds(polyline.getBounds());
}

initmap();


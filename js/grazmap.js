var map;
var marker;

var deflocation = [47.0707, 15.4395];
var defzoom = 13;

function onMapClick(e) {
   if(marker !== null)
      map.removeLayer(marker);

   marker = new L.marker(e.latlng);
   marker.addTo(map);

   document.getElementById('latitude').value = e.latlng.lat;
   document.getElementById('longitude').value = e.latlng.lng;
}

function initmap() {
   map = new L.map('map');

   if(document.getElementById('latitude') && document.getElementById('latitude').value > 0) {
      deflocation[0] = document.getElementById('latitude').value;
   }

   if(document.getElementById('longitude') && document.getElementById('longitude').value > 0) {
      deflocation[1] = document.getElementById('longitude').value;
   }

   marker = L.marker(deflocation).addTo(map);

   // create the tile layer with correct attribution
   var osmUrl='https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
   var osmAttrib='\u00a9  <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
   var osm = new L.TileLayer(osmUrl, {minZoom: 10, maxZoom: 19, attribution: osmAttrib});		

   // start the map in Graz
   map.setView(deflocation, defzoom);
   map.addLayer(osm);

   // add marker
   if(!document.getElementById('mapimmutable')){
      map.on('click', onMapClick);
   }
}

initmap();


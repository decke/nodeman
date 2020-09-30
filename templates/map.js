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

   layerControl = L.control.layers(
   {
      "Karte": basemap,
      "Satellit": orthomap,
      "Terrain": topomap
   });

   layerControl.addTo(map);

   {% if deflocation %}
   L.marker([{{ deflocation.lat }}, {{ deflocation.lng }}]).addTo(map);
   {% endif %}

   {% for loctype, data in locationdata %}
      // {{ loctype }}
      {{ loctype }}Grp = new L.LayerGroup();
      {{ loctype }}Icon = L.divIcon({className: '{{ data.icon }}', iconSize: [12,12], iconAnchor: [6,12], popupAnchor: [0,-12]});

      {% for loc in data.locations %}
          L.marker({{ loc.location }}, {icon:{{ loctype }}Icon}).addTo({{ loctype }}Grp).bindPopup("{{ loc.popup|raw }}");
      {% endfor %}

      layerControl.addOverlay({{ loctype }}Grp, "Location: {{ data.name }}");
      {% if not data.hide %}
         map.addLayer({{ loctype }}Grp);
      {% endif %}

   {% endfor %}

   {% for linktype, data in linkdata %}
       // {{ linktype }}
       {{ linktype }}Links = [
           {% for link in data.links %}
               [ {{ link.from }}, {{ link.to }} ]{% if not loop.last %},{% endif %}

           {% endfor %}
       ];

       {{ linktype }}Poly = L.polyline({{ linktype }}Links, { color: '{{ data.color }}', weight: 3, opacity: 0.5 });
       layerControl.addOverlay({{ linktype }}Poly, "Link: {{ data.name }}");
       map.addLayer({{ linktype }}Poly);

   {% endfor %}
}

initmap();


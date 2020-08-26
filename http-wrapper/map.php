<?php
if(!file_exists("include/common.php"))
  header('Location: install.php');
require_once "include/common.php";
$ojnTemplate->setTitle(__tr('Map'));

require_once('include/message.php');
?>
<div class="card my-4">
	<h5 class="card-header">
		<i class="icon-world"></i> <?php echo __tr('Location of bunnies') ?>
	</h5>
	<div class="card-body">
		<div id="map" style="height: 500px"></div>
	</div>
</div>
<?php
//$js = ' <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7/leaflet.css" /><script src="http://cdn.leafletjs.com/leaflet-0.7/leaflet.js"></script>';
$js = ' <link rel="stylesheet" href="http://unpkg.com/leaflet@1.4.0/dist/leaflet.css" /><script src="http://unpkg.com/leaflet@1.4.0/dist/leaflet.js"></script>';
//$js .= ' <link rel="stylesheet" href="css/MarkerCluster.css" />';
//$js .= ' <link rel="stylesheet" href="css/MarkerCluster.Default.css" />';
//$js .= ' <script src="js/leaflet.markercluster-src.js"></script>';
$js .= ' <link rel="stylesheet" href="http://leaflet.github.io/Leaflet.markercluster/dist/MarkerCluster.css" />';
$js .= ' <link rel="stylesheet" href="http://leaflet.github.io/Leaflet.markercluster/dist/MarkerCluster.Default.css" />';
$js .= ' <script src="http://leaflet.github.io/Leaflet.markercluster/dist/leaflet.markercluster-src.js"></script>';
//$js .= ' <script src="http://leaflet.github.io/Leaflet.markercluster/example/realworld.388.js"></script>';
/*
$js .= '
<script>
    var tiles = L.tileLayer(\'http://{s}.tile.osm.org/{z}/{x}/{y}.png\', {
        maxZoom: 18,
        attribution: \'&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors\'
      }),
      latlng = L.latLng(-37.82, 175.24);

    var map = L.map(\'map\', {center: latlng, zoom: 13, layers: [tiles]});

    var markers = L.markerClusterGroup();

    for (var i = 0; i < addressPoints.length; i++) {
      var a = addressPoints[i];
      var title = a[2];
      var marker = L.marker(new L.LatLng(a[0], a[1]), { title: title });
      marker.bindPopup(title);
      markers.addLayer(marker);
    }

    map.addLayer(markers);
';
*/
$js .= '<script>

var map = L.map(\'map\')//.setView([51.505, -0.09], 13);
//L.tileLayer(\'http://{s}.tile.cloudmade.com/a50d6fdc215643f290b705d2e5dfea31/997/256/{z}/{x}/{y}.png\', {
//L.tileLayer(\'http://{s}.tile.stamen.com/watercolor/{z}/{x}/{y}.jpg\', {
//L.tileLayer(\'http://{s}.tile.thunderforest.com/landscape/{z}/{x}/{y}.png\', {
L.tileLayer(\'http://tile.thunderforest.com/landscape/{z}/{x}/{y}.png?apikey={apiKey}\',
  {
    attribution: \'Map data &copy; OpenStreetMap contributors, Imagery © <a href="http://thunderforest.com">Thunderforest</a>\',
    maxZoom: 12,
    minZoom: 2,
    apiKey: \''. THUNDERFOREST_APIKEY .'\'
}).addTo(map);

  map.setView(new L.LatLng(48.85, 2.35),4);
var markers = L.markerClusterGroup();
  ';

//var marker = L.marker([51.5, -0.09]).addTo(map);

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$sql = "SELECT * FROM geo WHERE DATE_ADD(date, INTERVAL 1 WEEK) > NOW();";
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
   //$js .= "L.marker([".$row['longitude'].",".$row['latitude']."]).addTo(map);\n";
   $js .= "markers.addLayer(L.marker([".$row['longitude'].",".$row['latitude']."]));\n";
}
$js .= "map.addLayer(markers);";

mysqli_close($link);


$js .= '</script>';
$ojnTemplate->setJS($js);

<?php
require_once "../include/common.php";
require(ROOT_SITE.'include/message.php');
$ojnTemplate->setTitle(__tr('LED Language Help'));

?>
<style>
h4 {
	margin-top: 20px;
}
h5 {
	margin-top: 15px;
}
</style>
<div class="row">
    <div class="span12">
        <div class="widget widget">
            <div class="widget-header">
                <i class="icon-list-alt"></i><h3><?php echo __tr("LED Language Help") ?></h3>
            </div>
            <div class="widget-content">
<?php
/*

//  noir     rouge    vert     jaune    bleu     violet   cyan     blanc
	0x000000 0xff0000 0x00ff00 0xffff00 0x0000ff 0xff00ff 0x00ffff 0xffffff
//  gris     rose     G clair  J clair  B clair  V clair  C clair  orange
	0x808080 0xff8080 0x80ff80 0xffcc00 0x8080ff 0xff80ff 0x80ffff 0xff8000
Meteo
        [25 {3 3 3 3 3 3 3 3 3 3 3 3 3 3 3 0 0 0 0 0 0 0 0 0}] // soleil
        [125 {0 3 0 4 0 4}] // nuages
        [25 {4 4 4 4 4 4 4 4 4 4 4 4 4 4 4 0 0 0}] // brouillard
        [20 {0 0 0 0 4 0 4 0 4 0 0 0 0 0 4 4 0 0 0 0 0 0 4 0 0 0 4}] // pluie
        [40 {4 0 0 0 0 0 0 0 4 0 0 0 0 4 0 0 0 0 0 0 4 0 0 0 0 4 0 0 0 0 4 0 0 0 0 0}] // neige
        [25 {0 4 3 0 0 0 0 0 0 0 0 0 0 0 0 4 3 0 0 4 3 0 0 0 0 0 0 0 3 4 3 4 0}] // orage
bourse
        [7 {0 0 11 0 11 0 11 0 0 0 0 0 0 0 0 0 0 0}]
        [14 {0 0 11 0 11 0 11 0 0 0 0 0}]
        [28 {0 0 11 0 11 0 11 0 0 0 0 0}]
        [28 {0 11 0 0 0 0}]
        [28 {11 0 0 0 11 0 0 0 11 0 0 0}]
        [14 {11 0 0 0 11 0 0 0 11 0 0 0}]
        [7 {11 0 0 0 11 0 0 0 11 0 0 0 0 0 0 0 0 0}]
traffic
        [100 {1 0 1 1 0 1 1 0 1 0 0 0 0 0 0 0 0 0}]
        [100 {0 1 0 1 0 1 0 0 0 0 0 0 0 0 0}]
        [50 {0 1 0 1 0 1 0 0 0 0 0 0 0 0 0}]
        [25 {0 1 0 1 0 1 0 0 0 0 0 0 0 0 0}]
        [12 {0 1 0 1 0 1 0 0 0 0 0 0 0 0 0}]
        [8 {0 1 0 1 0 1 0 0 0 0 0 0 0 0 0}]
        [4 {0 1 0 1 0 1 0 1 0 1 0 1 0 0 0 0 0 0}]
mails
        [70 {5 0 0 0 5 0 0 0 5 0 5 0}]
        [56 {0 5 0 0 0 0}]
        [56 {5 0 5 0 0 0}]
        [56 {5 5 5 0 0 0}]
pollution
        [42 {6 6 6 6 6 6 6 6 6 0 0 0}]
        [42 {6 6 6 6 6 6 6 6 6 0 0 0}]
        [42 {6 6 6 6 6 6 6 6 6 0 0 0}]
        [42 {6 6 6 6 6 6 6 6 6 0 0 0}]
        [42 {6 6 6 6 6 6 6 6 6 0 0 0}]
        [14 {0 6 6 6 6 0 6 6 6 6 6 6 6 6 6 6 6 6 6 0 6 0 0 6 0 0 0 0 6 0 0 6 6 6 6 6 6 6 6 6 6 6 6 6 0 6 0 6 0 6 6}]
        [14 {0 6 6 6 6 0 6 6 6 6 6 6 6 6 6 6 6 6 6 0 6 0 0 6 0 0 0 0 6 0 0 6 6 6 6 6 6 6 6 6 6 6 6 6 0 6 0 6 0 6 6}]
        [14 {0 6 6 6 6 0 6 6 6 6 6 6 6 6 6 6 6 6 6 0 6 0 0 6 0 0 0 0 6 0 0 6 6 6 6 6 6 6 6 6 6 6 6 6 0 6 0 6 0 6 6}]
        [14 {0 6 0 0 6 6 6 6 6 6 0 0 0 0 0 0 6 0 0 6 6 0 0 0 6 6 0 6 6 6 0 0 6 6 0 0 0 6 0 0 0 0 6 0 6 0 6 0}]
        [14 {0 6 0 0 6 6 6 6 6 6 0 0 0 0 0 0 6 0 0 6 6 0 0 0 6 6 0 6 6 6 0 0 6 6 0 0 0 6 0 0 0 0 6 0 6 0 6 0}]
        [14 {0 6 0 0 6 6 6 6 6 6 0 0 0 0 0 0 6 0 0 6 6 0 0 0 6 6 0 6 6 6 0 0 6 6 0 0 0 6 0 0 0 0 6 0 6 0 6 0}]
*/
?>
    <canvas id="myCanvas" width="600" height="200"></canvas>
    <script>
      var colors = new Array();
      colors[0] = "#000000";
      colors[1] = "#ff0000";
      colors[2] = "#00ff00";
      colors[3] = "#ffff00";
      colors[4] = "#0000ff";
      colors[5] = "#ff00ff";
      colors[6] = "#00ffff";
      colors[7] = "#ffffff";
      colors[8] = "#808080";
      colors[9] = "#ff8080";
      colors[10] = "#80ff80";
      colors[11] = "#ffcc00";
      colors[12] = "#8080ff";
      colors[13] = "#ff80ff";
      colors[14] = "#80ffff";
      colors[15] = "#ff8000";

      var canvas = document.getElementById('myCanvas');
      var context = canvas.getContext('2d');
      var line = 1;
      var radius = 12;
      var centerX1 = canvas.width / 2 - 2.5 * radius;
      var centerY1 = canvas.height / 2;
      var centerX2 = canvas.width / 2;
      var centerY2 = canvas.height / 2;
      var centerX3 = canvas.width / 2 + 2.5 * radius;
      var centerY3 = canvas.height / 2;

      var index = 0;

      function play(tempo, chor)
      {
          index = 0;
          setInterval(function() {next(chor);},10 * tempo);
      }
      function next(chor)
      {
          var color1 = colors[chor[3*index]];
          var color2 = colors[chor[3*index+1]];
          var color3 = colors[chor[3*index+2]];

	      context.beginPath();
	      context.arc(centerX1, centerY1, radius, 0, 2 * Math.PI, false);
	      context.fillStyle = color1;
	      context.fill();
	      context.lineWidth = line;
	      context.strokeStyle = '#000000';
	      context.stroke();

	      context.beginPath();
	      context.arc(centerX2, centerY2, radius, 0, 2 * Math.PI, false);
	      context.fillStyle = color2;
	      context.fill();
	      context.lineWidth = line;
	      context.strokeStyle = '#000000';
	      context.stroke();

	      context.beginPath();
	      context.arc(centerX3, centerY3, radius, 0, 2 * Math.PI, false);
	      context.fillStyle = color3;
	      context.fill();
	      context.lineWidth = line;
	      context.strokeStyle = '#000000';
	      context.stroke();
	  index++;
          index = index % (chor.length / 3);
      }

      var chor = [3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0];
      chor = [0, 3, 0, 4, 0, 4];
      chor = [4, 0, 0, 0, 0, 0, 0, 0, 4, 0, 0, 0, 0, 4, 0, 0, 0, 0, 0, 0, 4, 0, 0, 0, 0, 4, 0, 0, 0, 0, 4, 0, 0, 0, 0, 0];
      //chor = [2,3,1,2,3,1,2,3,1,2,3,0];
	chor = [0,4,3,0,0,0,0,0,0,0,0,0,0,0,0,4,3,0,0,4,3,0,0,0,0,0,0,0,3,4,3,4,0];
      play(25, chor);
    </script>
            </div>
        </div>
    </div>
</div>
<?php
require_once "../include/append.php";
?>

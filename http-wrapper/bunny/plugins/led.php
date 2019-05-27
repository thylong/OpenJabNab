<?php
function getLedScript()
{
?>
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
      colors[16] = "#000000";



function Leds (name, tempo, chor) {
    this.name = name;
    this.canvas = document.getElementById('canvas_' + name);
    this.context = this.canvas.getContext('2d');
    this.chor = chor;
    this.tempo = tempo;

      this.line = 1;
      this.radius = 13;
      this.centerX1 = this.canvas.width / 2 - 2.5 * this.radius;
      this.centerY1 = this.canvas.height / 2;
      this.centerX2 = this.canvas.width / 2;
      this.centerY2 = this.canvas.height / 2;
      this.centerX3 = this.canvas.width / 2 + 2.5 * this.radius;
      this.centerY3 = this.canvas.height / 2;

      this.index = 0;
	this.interval = null;
}

Leds.prototype.play = function() {
          this.index = 0;
	  eval("var t = chor_" + this.name);
	  clearInterval(this.interval);
          this.interval = setInterval(function() {t.next();}, 10 * this.tempo);	
};

Leds.prototype.next = function () {
//console.log(this.name + " : " + this.index);
          var color1 = colors[this.chor[3*this.index]];
          var color2 = colors[this.chor[3*this.index+1]];
          var color3 = colors[this.chor[3*this.index+2]];

	      this.context.beginPath();
	      this.context.arc(this.centerX1, this.centerY1, this.radius, 0, 2 * Math.PI, false);
	      this.context.fillStyle = color1;
	      this.context.fill();
	      this.context.lineWidth = this.line;
	      this.context.strokeStyle = '#000000';
	      this.context.stroke();

	      this.context.beginPath();
	      this.context.arc(this.centerX2, this.centerY2, this.radius, 0, 2 * Math.PI, false);
	      this.context.fillStyle = color2;
	      this.context.fill();
	      this.context.lineWidth = this.line;
	      this.context.strokeStyle = '#000000';
	      this.context.stroke();

	      this.context.beginPath();
	      this.context.arc(this.centerX3, this.centerY3, this.radius, 0, 2 * Math.PI, false);
	      this.context.fillStyle = color3;
	      this.context.fill();
	      this.context.lineWidth = this.line;
	      this.context.strokeStyle = '#000000';
	      this.context.stroke();

if(this.chor[3*this.index] == 16)
{
	this.context.fillStyle = "white";
        this.context.font = "bold 16px Arial";
        this.context.fillText("X", this.centerX1-6, this.centerY1+6);
}
if(this.chor[3*this.index+1] == 16)
{
	this.context.fillStyle = "white";
        this.context.font = "bold 16px Arial";
        this.context.fillText("X", this.centerX2-6, this.centerY2+6);
}
if(this.chor[3*this.index+2] == 16)
{
	this.context.fillStyle = "white";
        this.context.font = "bold 16px Arial";
        this.context.fillText("X", this.centerX3-6, this.centerY3+6);
}

	  this.index++;
          this.index = this.index % (this.chor.length / 3);
      }
Leds.prototype.update = function (tempo, chor) {
	this.tempo = tempo;
	this.chor = chor;
	this.play();
}
    </script>
<?php
}
function getLed($chor, $tempo, $id = '', $k = '', $w = 100, $h = 30) {
?>
    <canvas id="canvas_<?php echo md5($chor . $id . $k) ?>" width="<?php echo $w ?>" height="<?php echo $h ?>"></canvas>
    <script>
      var chor_<?php echo md5($chor . $id . $k) ?> = new Leds('<?php echo md5($chor . $id . $k) ?>', <?php echo $tempo ?>, [<?php echo $chor ?>]);
      chor_<?php echo md5($chor . $id . $k) ?>.play();
    </script>
<?php
}
?>

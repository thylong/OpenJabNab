<?php
require_once "include/common.php";

$days = array(
	1 => __tr('Monday'),
	2 => __tr('Tuesday'),
	3 => __tr('Wednesday'),
	4 => __tr('Thursday'),
	5 => __tr('Friday'),
	6 => __tr('Saturday'),
	7 => __tr('Sunday'),
);
$whens = array(
	"first" => __tr('First'),
	"second" => __tr('Second'),
	"last" => __tr('Last'),
);
$months = array(
	1 => __tr('January'),
	2 => __tr('February'),
	3 => __tr('March'),
	4 => __tr('April'),
	5 => __tr('May'),
	6 => __tr('June'),
	7 => __tr('July'),
	8 => __tr('August'),
	9 => __tr('September'),
	10 => __tr('October'),
	11 => __tr('November'),
	12 => __tr('December'),
);

$timezones = $ojnAPI->getApiMapped("translate/listTimezones?".$ojnAPI->getToken());
$reload = false;

if(isset($_GET['remove'])) {
	list($area, $location) = explode("/", $_GET['remove']);
	Message::AddFromApi($ojnAPI->getApiString('timezones/removeTimezone?area='.$area.'&location='.$location.'&'.$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_GET['removedst'])) {
	list($start, $end) = preg_split('/\|/', $_GET['removedst']);
	Message::AddFromApi($ojnAPI->getApiString('timezone/'.$_GET['edit'].'/removedst?start='.urlencode($start).'&end='.urlencode($end).'&'.$ojnAPI->getToken()));
	$reload = true;
}
if(count($_POST)) {
	if(isset($_POST['addA']) && isset($_POST['addL'])) {
		$id = 0;
		if(!isset($_GET['edit']))
			$id = Message::AddFromApi($ojnAPI->getApiString('timezones/addTimezone?area='.$_POST['addA'].'&location='.$_POST['addL'].'&'.$ojnAPI->getToken()));
		Message::AddFromApi($ojnAPI->getApiString('timezone/'.$_POST['addA'].'/'.$_POST['addL'].'/settimezone?stdCode='.$_POST['stdC'].'&stdName='.urlencode($_POST['stdN']).'&stdOffset='.$_POST['stdO'].'&dstCode='.$_POST['dstC'].'&dstName='.urlencode($_POST['dstN']).'&dstOffset='.$_POST['dstO'].'&'.$ojnAPI->getToken()), $id);
		$reload = true;
	}
	else if(isset($_POST['stdC'])) {
		Message::AddFromApi($ojnAPI->getApiString('timezone/'.$_GET['edit'].'/settimezone?stdCode='.$_POST['stdC'].'&stdName='.urlencode($_POST['stdN']).'&stdOffset='.$_POST['stdO'].'&dstCode='.$_POST['dstC'].'&dstName='.urlencode($_POST['dstN']).'&dstOffset='.$_POST['dstO'].'&'.$ojnAPI->getToken()));
		$reload = true;
	}
	else
	{
		$start = "";
		$end = "";
		if($_POST['sType'] == 'variable')
			$start = (strlen(trim($_POST['sYear'])) ? "in ".$_POST['sYear'].", " : "").$_POST['sWhen']." ".$_POST['sDay']." of ".$_POST['sMonth']." at ".$_POST['sHour'];
		else
			$start = $_POST['sdYear']."-".$_POST['sdMonth']."-".$_POST['sdDay']." at ".$_POST['sdHour'];
		if($_POST['eType'] == 'variable')
			$end = (strlen(trim($_POST['eYear'])) ? "in ".$_POST['eYear'].", " : "").$_POST['eWhen']." ".$_POST['eDay']." of ".$_POST['eMonth']." at ".$_POST['eHour'];
		else
			$end = $_POST['edYear']."-".$_POST['edMonth']."-".$_POST['edDay']." at ".$_POST['edHour'];
		Message::AddFromApi($ojnAPI->getApiString('timezone/'.$_GET['edit'].'/adddst?start='.urlencode($start).'&end='.urlencode($end).'&'.$ojnAPI->getToken()));
		$reload = true;
	}
}
if($reload)
{
	if(isset($_GET['edit']))
		header("Location: timezone.php?edit=".$_GET['edit']);
	else if(isset($_POST['addA']) && isset($_POST['addL']))
		header("Location: timezone.php?edit=".$_POST['addA']."/".$_POST['addL']);
	else
		header('Location: timezone.php');
	exit;
}
?>
<?php
include(ROOT_SITE.'include/message.php');
?>

	      <div class="row">
	      	<div class="span12">
	      		<div class="widget ">
	      			<div class="widget-header">
	      				<i class="icon-user"></i>
					<h3 id="config"><?php echo __tr('Timezones setup') ?></h3>
	  			</div> <!-- /widget-header -->
				<div class="widget-content">


<?php
if(isset($_GET['edit'])) {
	list($area, $location) = explode("/", $_GET['edit']);
	$tz = $ojnAPI->getApiMapped('timezone/'.$_GET['edit'].'/gettimezone?'.$ojnAPI->getToken());
?>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Area") ?></label>
            <div class="controls">
		<input type="text" class="disabled" disabled id="addA" value="<?php echo $area ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Location") ?></label>
            <div class="controls">
		<input type="text" class="disabled" disabled id="addL" value="<?php echo $location ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Standard code") ?></label>
            <div class="controls">
		<input type="text" name="stdC" value="<?php echo $tz['stdcode'] ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Standard name") ?></label>
            <div class="controls">
		<input type="text" name="stdN" value="<?php echo $tz['stdname'] ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Standard offset") ?></label>
            <div class="controls">
		<input type="text" name="stdO" value="<?php echo $tz['stdoffset'] ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Daylight saving code") ?></label>
            <div class="controls">
		<input type="text" name="dstC" value="<?php echo $tz['dstcode'] ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Daylight saving name") ?></label>
            <div class="controls">
		<input type="text" name="dstN" value="<?php echo $tz['dstname'] ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Daylight saving offset") ?></label>
            <div class="controls">
		<input type="text" name="dstO" value="<?php echo $tz['dstoffset'] ?>"/>
            </div>
          </div>


          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="timezone.php" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>
<br />
<form method="post" class="form-horizontal">


          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Start") ?></label>
            <div class="controls">
		<input checked="checked" type="radio" name="sType" value="variable">
		<div class="input-prepend"><span class="add-on"><?php echo __tr("Year") ?></span><input type="text" class="span1" name="sYear"></div>&nbsp;
		<select name="sWhen" class="span2">
			<?php foreach($whens as $key => $value): ?>
			<option value="<?php echo $key ?>"><?php echo $value ?></option>
			<?php endforeach; ?>
		</select>

		<select name="sDay" class="span2">
			<?php foreach($days as $key => $value): ?>
			<option value="<?php echo $key ?>"><?php echo $value ?></option>
			<?php endforeach; ?>
		</select>

		<?php echo __tr('of') ?>

		<select name="sMonth" class="span2">
			<?php foreach($months as $key => $value): ?>
			<option value="<?php echo $key ?>"><?php echo $value ?></option>
			<?php endforeach; ?>
		</select>

		<?php echo __tr('at') ?>
		<div class="input-append"><input type="text" class="span1" name="sHour"><span class="add-on"><?php echo __tr("hh:mm") ?></span></div>
            </div>
		<br />
            <div class="controls">
		<input type="radio" name="sType" value="date">
		<div class="input-prepend"><span class="add-on"><?php echo __tr("Year") ?></span><input type="text" class="span1" name="sdYear"></div>&nbsp;
		<div class="input-prepend"><span class="add-on"><?php echo __tr("Month") ?></span><input type="text" class="span1" name="sdMonth"></div>&nbsp;
		<div class="input-prepend"><span class="add-on"><?php echo __tr("Day") ?></span><input type="text" class="span1" name="sdDay"></div>
		<?php echo __tr('at') ?>
		<div class="input-append"><input type="text" class="span1" name="sdHour"><span class="add-on"><?php echo __tr("hh:mm") ?></span></div>

            </div>
          </div>

          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("End") ?></label>
            <div class="controls">
		<input checked="checked" type="radio" name="eType" value="variable">
		<div class="input-prepend"><span class="add-on"><?php echo __tr("Year") ?></span><input type="text" class="span1" name="eYear"></div>&nbsp;
		<select name="eWhen" class="span2">
			<?php foreach($whens as $key => $value): ?>
			<option value="<?php echo $key ?>"><?php echo $value ?></option>
			<?php endforeach; ?>
		</select>

		<select name="eDay" class="span2">
			<?php foreach($days as $key => $value): ?>
			<option value="<?php echo $key ?>"><?php echo $value ?></option>
			<?php endforeach; ?>
		</select>

		<?php echo __tr('of') ?>

		<select name="eMonth" class="span2">
			<?php foreach($months as $key => $value): ?>
			<option value="<?php echo $key ?>"><?php echo $value ?></option>
			<?php endforeach; ?>
		</select>

		<?php echo __tr('at') ?>
		<div class="input-append"><input type="text" class="span1" name="eHour"><span class="add-on"><?php echo __tr("hh:mm") ?></span></div>

            </div>
		<br />
            <div class="controls">
		<input type="radio" name="eType" value="date">
		<div class="input-prepend"><span class="add-on"><?php echo __tr("Year") ?></span><input type="text" class="span1" name="edYear"></div>&nbsp;
		<div class="input-prepend"><span class="add-on"><?php echo __tr("Month") ?></span><input type="text" class="span1" name="edMonth"></div>&nbsp;
		<div class="input-prepend"><span class="add-on"><?php echo __tr("Day") ?></span><input type="text" class="span1" name="edDay"></div>
		<?php echo __tr('at') ?>
		<div class="input-append"><input type="text" class="span1" name="edHour"><span class="add-on"><?php echo __tr("hh:mm") ?></span></div>

            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add daylight saving period") ?></button>
          </div>
</form>
<?php
$dsts = $ojnAPI->getApiList("timezone/".$area."/".$location."/getdstlist?".$ojnAPI->getToken());
$aliases = $ojnAPI->getApiList("timezone/".$area."/".$location."/alias?action=list&".$ojnAPI->getToken());
?>
<table class="table table-bordered table-striped span10">
	<tr>
		<th class="span4"><?php echo __tr('Start') ?></th>
		<th class="span4"><?php echo __tr('End') ?></th>
		<th class="span2"><?php echo __tr('Actions') ?></th>
	</tr>
<?php
global $days, $months;
	foreach($dsts as $dst){
		if(list($start, $end) = preg_split("/\|/", $dst)) {
		$start = ucfirst($start);
		$end = ucfirst($end);
		$start = ucfirst(strtolower(preg_replace_callback("|^(.*) (\d+) of (\d+) at|", create_function('$match', 'global $days, $months; return __tr($match[1])." ".$days[$match[2]]." ".__tr("of")." ".$months[$match[3]]." ".__tr("at");'), $start)));
		$end = ucfirst(strtolower(preg_replace_callback("|^(.*) (\d+) of (\d+) at|", create_function('$match', 'global $days, $months; return __tr($match[1])." ".$days[$match[2]]." ".__tr("of")." ".$months[$match[3]]." ".__tr("at");'), $end)));
?>
	<tr>
		<td><?php echo $start; ?></td>
		<td><?php echo $end; ?></td>
		<td><a class="btn btn-danger" href="timezone.php?edit=<?php echo $_GET['edit'] ?>&removedst=<?php echo $dst; ?>"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php } } ?>
</table>

<table class="table table-bordered table-striped span10">
	<tr>
		<th class="span4"><?php echo __tr('Alias') ?></th>
		<th class="span2"><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	foreach($aliases as $alias){
?>
	<tr>
		<td><?php echo $alias; ?></td>
		<td><a class="btn btn-danger" href="timezone.php?removealias=<?php echo $alias; ?>"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } else if(isset($_GET['add'])) { ?>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Area") ?></label>
            <div class="controls">
		<input type="text" name="addA"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Location") ?></label>
            <div class="controls">
		<input type="text" name="addL"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Standard code") ?></label>
            <div class="controls">
		<input type="text" name="stdC"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Standard name") ?></label>
            <div class="controls">
		<input type="text" name="stdN"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Standard offset") ?></label>
            <div class="controls">
		<input type="text" name="stdO"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Daylight saving code") ?></label>
            <div class="controls">
		<input type="text" name="dstC"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Daylight saving name") ?></label>
            <div class="controls">
		<input type="text" name="dstN"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Daylight saving offset") ?></label>
            <div class="controls">
		<input type="text" name="dstO"/>
            </div>
          </div>


          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add timezone") ?></button>
            <a href="timezone.php" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>
<?php } else { ?>
<table class="table table-bordered table-striped span10">
	<tr>
		<th colspan="4"><?php echo __tr('Timezone') ?></th>
	</tr>
	<tr>
		<th class="span3"><?php echo __tr('Area') ?></th>
		<th class="span3"><?php echo __tr('Location') ?></th>
		<th class="span3"><?php echo __tr('Local time') ?></th>
		<th class="span3"><?php echo __tr('Actions') ?></th>
	</tr>
<?php
asort($timezones);
foreach($timezones as $t => $time){
		if(strpos($t,'/'))
		{
			list($area, $location) = explode("/", $t);
		}
		else
		{
			$area = $t;
			$location ='';
		}
?>
	<tr>
		<td><?php echo $area; ?></td>
		<td><?php echo $location; ?></td>
		<td><?php echo $time; ?></td>
		<td><a class="btn btn-danger" href="timezone.php?remove=<?php echo $t; ?>"><?php echo __tr('Remove') ?></a>&nbsp;<a class="btn btn-primary" href="timezone.php?edit=<?php echo $t; ?>"><?php echo __tr('Edit') ?></a></td>
	</tr>
<?php } ?>
</table>

<br style="clear:both"/>
		<form id="edit-profile" class="form-horizontal" method="post">
			<div class="form-actions">
				<a href="timezone.php?add=tz" class="btn btn-primary"><?php echo __tr('Create new timezone') ?></a>
			</div> <!-- /form-actions -->
		</form>
<?php } ?>

				</div>
			</div>
		</div>
	</div>

<?php
require_once 'include/append.php'
?>

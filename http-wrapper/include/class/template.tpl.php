<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr-FR">
  <head>
    <meta charset="utf-8">
    <title><!!TITLE!!></title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">

    <link href="/media/css/bootstrap.min.css" rel="stylesheet">
    <link href="/media/css/bootstrap-responsive.min.css" rel="stylesheet">

    <link href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet">
    <link href="/media/css/font-awesome.css" rel="stylesheet">
    <link href="/media/fontcustom/fontcustom.css" rel="stylesheet">

    <link href="/media/css/bootstrap-timepicker.min.css" rel="stylesheet">
    <link href="/media/css/<!!CSS!!>" rel="stylesheet">

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-33856842-1']);
  _gaq.push(['_setDomainName', 'openjabnab.fr']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
  </head>
<body>

<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">
			<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse2"><i class="icon-cogs"></i></a>
		        <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse1"><i class="icon-cog"></i></a>
			<a class="brand" href="/">openJabNab Admin</a>
			<div class="nav-collapse nav-collapse1">
				<ul class="nav pull-right"><!!USER!!></ul>
<!--
				<form class="navbar-search pull-right">
					<input type="text" class="search-query" placeholder="Search">
				</form>
-->
			</div><!--/.nav-collapse -->
		</div> <!-- /container -->
	</div> <!-- /navbar-inner -->
</div> <!-- /navbar -->
<div class="navbar navbar-primary">
	<div class="navbar-inner">
		<div class="container">
            <div class="nav-collapse nav-collapse2">
			<!!MENU!!>
		</div>
		</div> <!-- /container -->
	</div> <!-- /subnavbar-inner -->
</div> <!-- /subnavbar -->

<div class="main">
	<div class="main-inner">
	    <div class="container">
<!!CONTENT!!>
	    </div> <!-- /container -->
	</div> <!-- /main-inner -->
</div> <!-- /main -->

<div class="extra">
	<div class="extra-inner">
		<div class="container">
			<div class="row"><!!FOOTER!!></div> <!-- /row -->
		</div> <!-- /container -->
	</div> <!-- /extra-inner -->
</div> <!-- /extra -->

<div class="footer">

	<div class="footer-inner">
		<div class="container">
			<div class="row">
    			<div class="span12">
    				&copy; 2012 <a>openJabNab</a> - <!!TIME!!>.
    			</div> <!-- /span12 -->
    		</div> <!-- /row -->
		</div> <!-- /container -->
	</div> <!-- /footer-inner -->
</div> <!-- /footer -->

<script src="/media/js/jquery-1.7.2.min.js"></script>
<script src="/media/js/excanvas.min.js"></script>
<script src="/media/js/bootstrap.js"></script>
<script src="/media/js/base.js"></script>
<script src="/media/js/bootstrap-timepicker.min.js"></script>
<!!JS!!>
  </body>
</html>

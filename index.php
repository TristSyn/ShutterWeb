<?php 
	if (session_id() == '')
		session_start(); 
	
	require_once "pagesections.php";
	require_once "gphoto2.php";
	
	if(isset($_GET["load"])) {
		LoadPageSection($_GET["load"]);
		return;
	}
		
	if(isset($_GET["camera"]))
		$_SESSION['selectedCamera'] = $_GET["camera"];

	if(isset($_GET["p"]))
		$selMenu = $_GET["p"];
	
	if(!isset($selMenu))
		$selMenu = "info";

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" type="text/css" href="./static/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" href="./static/bootstrap-theme.min.css">
		<link rel="stylesheet" type="text/css" href="./static/style.css">
		
		<script type="text/javascript" src="./static/jquery-1.10.1.min.js"></script>
		<script type="text/javascript" src="./static/bootstrap.min.js"></script>
		<script type="text/javascript" src="./static/library.js"></script>
		<script type="text/javascript">
			$(document).ready( 
				function() {	
					loadContent();
					$('[data-toggle="popover"]').popover(); 	
				});
		</script>
	</head>
	<body>
		<div id="main" class="container">
			
			<div id="banner">
				<img src="./images/camera160.png" class="hidden-xs"/>
				<img src="./images/camera40.png" class="visible-xs"/>
				
				<nav class="navbar navbar-default navbar-secondary navtopright visible-xs" style="float:right;">
					 <div class="navbar-header">
						<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#cameraoptionsmenu" aria-expanded="false">
							<span class="sr-only">Toggle navigation</span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>
						<a class="navbar-brand hidden-xs" href="#">Options</a>
					</div>
				</nav>
				
				
				<h1 class="pageheading" >Shutter Web</h1>
			</div>
			
			<div style="clear:both;"></div>
			
			<nav class="navbar navbar-default" style="margin-bottom:5px;">
				<div class="container">
				
					 <div class="navbar-header hidden-xs"">
						<a class="navbar-brand" href="#">Camera:</a>
					</div>
					
					<div class="navbar-btn btn-group">
						<button class="btn btn-default "><?php echo SelectedCameraName(); ?></button>
						<button data-toggle="dropdown" class="btn btn-default dropdown-toggle" style="height:34px;"><span class="caret"></span></button>
						<ul class="dropdown-menu">
							<?php 
								$cams = Cameras::AllCameras();
								
								foreach($cams as $cam) {
									echo '<li><a href="?camera='.$cam->Port.'">'.$cam->Name.'</a></li>';
								}
							?>
						</ul>
					</div>
				</div>
			</nav>
			
			<nav class="navbar navbar-default navbar-secondary" style="">
				<div class="container">
				
					<div class="collapse navbar-collapse" id="cameraoptionsmenu">
						<ul class="nav navbar-nav">
							<li <?php if($selMenu=="info") echo 'class="active"'; ?>><a href="?p=info">Information</a></li>
							<li <?php if($selMenu=="take") echo 'class="active"'; ?>><a href="?p=take">Take</a></li>
							<li ><a href="#" data-toggle="modal" data-target="#photopopupmodal" onclick="showLatest(false)">Latest</a></li>
							<li <?php if($selMenu=="browse") echo 'class="active"'; ?>><a href="?p=browse">Browse</a></li>
						</ul>
					</div>
				</div>
			</nav>
			<?php 
				$cam = Cameras::SelectedCamera();
				if($cam != null && $cam->Timelapse != null) {
					$ct = $cam->Timelapse;
					?>
					<script type="text/javascript">
						$(function() { setTimelapseProgress(<?php echo $ct->Done() ?>, <?php echo $ct->Count ?>);});
					</script>
					<?php
				}
			?>
			
			<div id="timelapseProgress" class="progress" style="display:none;" >
				<div class="progress-bar progress-bar-warning progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;min-width:5em;">
					<span class="">0 / 0 images taken</span>
				</div>
			</div>
			
			<div id="main">
				<div class="box full mh200" load="?load=<?php echo $selMenu ; ?>"></div>
			</div>
			

			<div class="modal fade bs-example-modal-lg" id="photopopupmodal" tabindex="-1" role="dialog">
			  <div class="modal-dialog modal-lg">
				<div class="modal-content">
				  <img id="photopopup" src="latest.jpg" style="width:100%;" />
				</div>
			  </div>
			</div>
		</div>
	</body>
</html>
<?php
	require_once "services.php";

	function LoadPageSection($section) {

		if(!isset($_SESSION['selectedCamera'])) {
			echo "No Camera Selected";
			return;
		}
		$cam = Cameras::SelectedCamera();
		$params = isset($_GET['params']) ? $_GET["params"] : '';
		
		switch ($section) {
			case "info":
				Section_Info($cam);
				break;
			case "take": 
				Section_Take($cam);
				break;
			case "log": 
				Section_Log();
				break;
			case "commands": 
				Section_Commands();
				break;
				
			case "browse": 
				Section_Browse($cam);
				break;
		
			case "getconfigrow":
				Section_ConfigRow($cam);
				break;
				
			case "imagesettings":
				Section_ImageSettings($cam);
				break;
			case "capturesettings":
				Section_CaptureSettings($cam, false);
				break;
				
				
		}
	}
	
	function Section_Info($cam) {
		?>
		<div class="row">
			
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">Information</div>
					<div class="panel-body form-horizontal" style="word-wrap: break-word;">
						<?php 
							$summary = $cam->Summary();
							$lines = explode('||',str_replace("<br/>\r\n", '||', $summary));
							foreach($lines as $line) {
								
								if (strrpos($line,':')+1 ==strlen($line))
									echo "<h3>".$line."</h3>";
								else 
									echo "<b>".substr($line, 0, strpos($line, ':'))."</b>".substr($line, strpos($line, ':'))."<br/>";
								
							}
						?>
					</div>
				</div>
			</div>
			
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">Abilities</div>
					<div class="panel-body form-horizontal">
						<?php
							$abilities =$cam->Abilities();
							$lines = explode('||',str_replace("\n", '||', $abilities));
							foreach($lines as $line) {
								echo "<b>".substr($line, 0, strpos($line, ':'))."</b>".substr($line, strpos($line, ':'));//."<br/>";
							}
						?>
					</div>
				</div>
			</div>
		</div>
		<?php 
		
	}

	function Section_Take($cam) {
		?>
		<div class="row">
			<div class="col-md-6">
				<?php /*Section_ImageSettings($cam);*/ ?>
				
				<div load="?load=imagesettings"></div>
				
				<div class="panel panel-default">
					<div class="panel-heading">Take Settings</div>
					<div class="panel-body form-horizontal">
					
						

						<div class="form-group">
							<label class="col-md-5 control-label" style="text-align:left;"> <input type="checkbox" id="saveToCamera" checked="checked" />Save to Camera</label>
						</div>
						
						<div class="form-group">
							<label class="col-md-5 control-label" style="text-align:left;"> <input type="checkbox" id="saveToServer" onclick="toggleDisabled('saveToServerSettings')" />Save to Server</label>
						</div>
						<fieldset id="saveToServerSettings" disabled>
							<div class="form-group ">
								<label class="col-md-5 control-label">Save location</label>
								<div class="col-md-7">
									<input type="text" class="form-control" id="saveLocation" value="./photos" />
								</div>
							</div>
						</fieldset>
						
						<div class="form-group">
							<label class="col-md-5 control-label" style="text-align:left;"> <input type="checkbox" id="timelapse" onclick="toggleDisabled('timelapseSettings')" />Timelapse</label>
						</div>
						 <fieldset id="timelapseSettings" disabled>
							<div class="form-group " >
								<label class="col-md-5 control-label">Timelapse Name</label>
								<div class="col-md-7">
									<input type="text" class="form-control" id="timelapsename" value="<?php echo date('Y-m-d_H_i_s'); ?>" />
								</div>
							</div>
							
							<div class="form-group" >
								<label class="col-md-5 control-label">Delay between shots</label>
								<div class="col-md-7">
									<input type="text" class="form-control" id="shotdelay" value="10" />
								</div>
							</div>
							
							<div class="form-group" >
								<label class="col-md-5 control-label">Shot count</label>
								<div class="col-md-7">
									<input type="text" class="form-control" id="shotcount" value="10" />
								</div>
							</div>
						</fieldset>
						<button class="btn btn-default " <?php echo ($cam->Timelapse != null ? " disabled='disabled' title='Timelapse running'" : "")?> onclick="takePicture()">Take Picture</button>
						<button class="btn btn-link" onclick="prompt('Timelapse Url', getStartTimelapseUrl()); return false;" style="float:right;">As Url</button>
						<button class="btn btn-default " <?php echo ($cam->Timelapse != null ? " disabled='disabled' title='Timelapse running'" : "")?> onclick="startTimelapse()" style="float:right;">Start timelapse</button>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				
				<div class="panel panel-default">
					<div class="panel-heading" data-toggle="collapse" data-target="#divcapturesettings">Capture Settings</div>
					
					<div class="panel-body form-horizontal collapse in" id="divcapturesettings">
						<?php Section_CaptureSettings($cam, true); ?>
						<div load="?load=capturesettings"></div>
						<button class="btn btn-link" onclick="prompt('Current Settings Url', getCurrentSettingsUrl()); return false;">Current Settings Url</button>
					</div>
				</div>
			</div>
		</div>
		<?php 
	}
	
	function Section_Log() {
		$Log = GetLog();
		echo count($Log)." log entries<br/><br/>";
		foreach($Log as &$lg) {
			echo $lg."<br/>";
		}
	}
	
	function Section_Commands() {
		?>
		<button onclick="return reboot();">Reboot</button>
		<?php 
	}
	
	function Section_Browse($cam) {
		$folders = $cam->getFolders();
		?>
				
			<div class="boxWithHead full">
				
				<div class="body">
					<div class="row">
						<div class="col-md-5">
							<div class="panel panel-default">
								<div class="panel-heading" >Folders</div>
								<div class="panel-body" >
									<?php 
										foreach($folders as $key => $value) {
											if($key != '0') {
												?>
												<div class="browsefolder" onclick="return getFilesInFolder('<?php echo $key; ?>');">
													<span class="glyphicon glyphicon-folder-open" aria-hidden="true"></span> <?php echo $key; ?>
												</div>
												<?php
											}
										}
									?>
									<img id="thumbnail" />
								</div>
							</div>
						</div>
						<div class="col-md-7">
							<div class="panel panel-default">
								<div class="panel-heading" >Files</div>
								<div class="panel-body" id="filesInFolder">
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php 		
	}
	
	function Section_ConfigRow($cam) {
		
		$configOption = $cam->Config($_GET["config"]);
		echo '<label class="col-md-5 control-label">'.$configOption->Label().'</label>';//
		Section_ConfigOption($cam, $_GET["config"]);
		
	}
	
	function Section_ConfigOption($cam, $config) {

		$configOption = $cam->Config($config);
		$isShootingMode = $configOption == $cam->ShootingMode();
		$hasTL = ($cam->Timelapse != null);
		echo "<div class=\"col-md-7\"><select ".($hasTL ? " disabled title='Timelapse running'" : "")." class=\"form-control\" config=\"".$config."\" current=\"".$configOption->Current()."\" onchange=\"SaveSetting(this)\"".($isShootingMode ? " sm=\"true\"" : "").">";
		$options = $configOption->Options();
		for($i=0; $i < count($options); $i++) {
			//echo '<option>test</option>';
			echo "<option value=\"".$options[$i]->Value."\"".($configOption->Current() == $options[$i]->Value? "selected" : "").">".$options[$i]->Name."</option>";
		}
		echo "</select></div>";
	}
	
	function Section_ImageSettings($cam) {
		?>
			<div class="panel panel-default">
				<div class="panel-heading" data-toggle="collapse" data-target="#divimagesettings">Image Settings</div>
				<div class="panel-body form-horizontal collapse in" id="divimagesettings">
					<?php 
						$captureSettings = $cam->ConfigsInCategory('/main/imgsettings');
						foreach($captureSettings as &$setting) {
							//echo "<div class=\"form-group\" priority=\"true\" load=\"?load=getconfigrow&config=".$setting->Setting."\">".$setting->Setting."</div>";
							echo '<div class="form-group"><label class="col-md-5 control-label">'.$setting->Label().'</label>';//
							Section_ConfigOption($cam, $setting->Setting);
							echo '</div>';
						}
					?>
				</div>
			</div>
		<?php
		
	}
	
	function Section_CaptureSettings($cam, $justTheFirst) {

		$captureSettings = $cam->ConfigsInCategory('/main/capturesettings');
		$isFirst = true;
		foreach($captureSettings as &$setting) {
			//echo "<div class=\"form-group\" load=\"?load=getconfigrow&config=".$setting->Setting."\">".$setting->Setting."</div>";
			if($justTheFirst == $isFirst ) {
				echo '<div class="form-group"><label class="col-md-5 control-label">'.$setting->Label().'</label>';//
				Section_ConfigOption($cam, $setting->Setting);
				echo '</div>';
			}
			if($justTheFirst && $isFirst) {
				echo "<hr />";
				break;
			}
			$isFirst = false;
		}
	}
?>
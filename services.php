<?php 
	if (session_id() == '')
		session_start(); 

	require_once "gphoto2.php";
	
	if(isset($_GET["type"])) {
		$section = $_GET["type"];
		$cam = Cameras::SelectedCamera();
		$params = isset($_GET['params']) ? $_GET["params"] : '';
	
		switch ($section) {
			case "capture":
				if($cam == null)
					$cam = Cameras::SelectFirstCamera();
				$cam->Capture($_POST["saveToCamera"], $_POST["saveToServer"], $_POST["saveLocation"], $_POST["params"]);
				echo "{}";
				break;
			case "startTimelapse":
				//services.php?type=startTimelapse&name=xxx&shotdelay=123&shotcount=123&saveToCamera=true|false&saveToServer=true|false&saveLocation=true|false&params=someparam|somevalue
				//e.g. services.php?type=startTimelapse&name=xxx&shotdelay=10&shotcount=3&saveToCamera=true&saveToServer=false&saveLocation=photos/&params=
				if($cam == null)
					$cam = Cameras::SelectFirstCamera();
				if($cam != null) {
					header('Content-Type: application/json');
					$tl = $cam->StartTimelapse($_REQUEST["name"], $_REQUEST["shotdelay"], $_REQUEST["shotcount"], $_REQUEST["saveToCamera"], $_REQUEST["saveToServer"], $_REQUEST["saveLocation"], $_REQUEST["params"] );
					echo "{ \"done\" : ".$tl->Done().", \"count\":".$tl->Count."}";
				}
				break;
			case "setValue":
				$setResult = $cam->SetValue($params);
				if($setResult)
					echo "{ \"success\" : true}";
				else {
					$index = strpos($params, "=");
					$name = substr($params, 0, $index);
					//$val= substr($params, $index+1);
					$config = $cam->Config($name);
					
					echo "{ \"success\" : false, \"value\": ".$config->Current()."}";
				}
				break;
			case "setValues":
				$setResult = $cam->SetValues($params);
				if($setResult)
					echo "{ \"success\" : true}";
				else 
					echo "{ \"success\" : false}";
				break;
			case "tlProgress":
				header('Content-Type: application/json');
				if($cam != null && $cam->Timelapse != null) {
					$done = $cam->Timelapse->Done();
					
					echo "{ \"done\" : ".$done.", \"count\":".$cam->Timelapse->Count."}";
					
					if($done >= $cam->Timelapse->Count) {
						$cam->Timelapse = null;
						$cam->Persist();
					}
					return;
				}
				echo "{ \"done\" : 0, \"count\":0}";
				break;
			case "files":
				header('Content-Type: application/json');
				echo json_encode($cam->getFilesInFolder($_GET['folder']));
				break;
			case "thumbnail":
				echo "{ \"thumbnailfile\" : \"".$cam->getFileThumbnail($_GET['idx'])."\" }";
				break;
			case "grab":
				echo "{ \"file\" : \"".$cam->getFile($_GET['idx'])."\" }";
				break;
			case "addCronJob":
				addCronJob();
				break;
		}
		return;
	}
	
	function addCronJob() {
		$output = shell_exec('crontab -l');
		file_put_contents('/tmp/crontab.txt', $output.'* * * * * NEW_CRON'.PHP_EOL);
		echo exec('crontab /tmp/crontab.txt');
		
	}

	function GetLog() {
		$cam = Cameras::SelectedCamera();
		$Log = array_reverse($cam->LogEntries);
		return $Log;
		
	}
	function SelectedCameraName() {
		$cam = Cameras::SelectedCamera();
		if($cam == null)
			return "No Camera selected";
		return $cam->Name;
	}
?>
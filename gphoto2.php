<?php 

	/*
		Wrapper for calls to gphoto2
		Parameters indicate whether to replace newlines with br tags, 
		or explode out lines to an array, or leave as returned
	*/
	class GPHOTO2 {
		
		public static function GP2_PortCommand($port, $cmd, $AddBRs, $MakeArray) {
			return self::GP2_CommandEx($cmd.' --port '.$port, $AddBRs, $MakeArray);
		}

		public static function GP2_CommandEx($cmd, $AddBRs, $MakeArray) {
			
			error_log($cmd."\r\n", 3, "/var/www/shutterweb/my-errors.log");
			error_log($cmd."\r\n", 3, "/var/www/shutterweb/logging.log");
			$results = shell_exec('gphoto2 --quiet '.$cmd);
			//error_log($results."\r\n", 3, "/var/www/shutterweb/my-errors.log");
			if($AddBRs)
				return str_replace("\n", "<br/>\r\n", $results);
			else if($MakeArray)
				return explode('||',str_replace("\n", '||', $results));
			else
				return $results;
		}
	}

	
	class Cameras {
		
		public static function AllCameras() {
			self::loadCameras();
			return self::$cameras;
		}
		
		private static $loaded = false;
		private static $cameras = array();
		public static function loadCameras() {
			if(!self::$loaded) {
				$retval = shell_exec('gphoto2 --auto-detect');
				$cams = explode("\n", $retval);
				

				if(count($cams) <= 3) {
					unset($_SESSION['selectedCamera']);
					return ;
				}

				if(isset($_SESSION['selectedCamera'])) {
					if(strpos($retval,$_SESSION['selectedCamera']) === false)
						unset($_SESSION['selectedCamera']);
				}
				
				for($i = 2; $i < count($cams); $i++) {
					$cameraPort="";
					$cameraName="";
					$IsUSB =  strrpos($cams[$i], "usb:");
					$IsSerial = strrpos($cams[$i], "serial:");
					if($IsUSB !== false) {
						$cameraName=trim(substr($cams[$i],0,$IsUSB));
						$cameraPort=trim(substr($cams[$i],$IsUSB, strlen($cams[$i]) - $IsUSB));
					}else if($IsSerial !== false) {
						$cameraName=trim(substr($cams[$i],0,$IsSerial));
						$cameraPort=trim(substr($cams[$i],$IsSerial, strlen($cams[$i]) - $IsSerial)) ;
					}
						
					$newcam = null;
					if(apc_exists($cameraPort))
						$newcam = unserialize(apc_fetch($cameraPort)); 
					else {
						if($i == 2 && !isset($_SESSION['selectedCamera']))
							$_SESSION['selectedCamera'] = $cameraPort;
						if(strlen($cameraName) > 0) {
							$newcam = new Camera($cameraPort, $cameraName);
							apc_store($cameraPort, serialize($newcam));
						}
					}
					if($newcam != null)
						self::$cameras[] = $newcam;
				}
				self::$loaded = true;
			}
		}
		public static function GetCamera($port) {
			self::loadCameras();

			return unserialize(apc_fetch($port));
		}
		
		public static function SelectedCamera() {
			self::loadCameras();
			if(isset($_SESSION['selectedCamera'])) {
				foreach(self::$cameras as $cam)
					if($cam->Port == $_SESSION['selectedCamera'])
						return $cam;
			}
			return null;
		}
		
		public static function SelectFirstCamera() {
			self::loadCameras();
			foreach(self::$cameras as $cam) {
				$_SESSION['selectedCamera'] = $cam->Port;
				return $cam;
			}
			
		}
	}

	class Camera {
		
		protected $_abilities;
		protected $_summary;
		protected $_folders;
		protected $_configs; //an array of strings of the camera settings that are available
		protected $_configOptions;// an array of objects (one for each camera setting) that contains the option lists for each shootingmode
		
		public $Port;
		public $Name;
		
		public $LogEntries;
		
		public $Timelapse;

		public function __construct($port, $name) {
			$this->Port = $port;
			$this->Name = $name;
			$this->_configOptions = array();
			$this->LogEntries = array();
			
			error_log("create camera:".$name." ".$port."\r\n", 3, "/var/www/shutterweb/logging.log");
			
			//check if file for camera config exists
			if(file_exists("cameraconfigs/".$this->NameAsFilename().".cfg")) {
				$file = file_get_contents("cameraconfigs/".$this->NameAsFilename().".cfg");
				$tempcam = unserialize($file);
				$this->_abilities = $tempcam->_abilities;
				$this->_summary = $tempcam->_summary;
				$this->_configs = $tempcam->_configs;
				$this->_configOptions = $tempcam->_configOptions;
				
				foreach($this->_configOptions as &$co)
					$co->SetCamera($this);
				$this->LogEntries[] = "Get Camera Settings from file";
							
				error_log("Get Camera Settings from file"."\r\n", 3, "/var/www/shutterweb/logging.log");
			} 
			GPHOTO2::GP2_PortCommand($this->Port,'--set-config capture=on', false, false);
			error_log("Current Shooting mode:".$this->ShootingMode()->Current()."\r\n", 3, "/var/www/shutterweb/logging.log");
			$this->ShootingMode()->ReadCurrentValue();
			error_log("Current Shooting mode:".$this->ShootingMode()->Current()."\r\n", 3, "/var/www/shutterweb/logging.log");
			
		}
		
		public function ShootingMode() {
			
			$sm = $this->Config('/main/capturesettings/shootingmode'); //Canon
			if($sm != null)
				return $sm;
			$sm = $this->Config('/main/capturesettings/expprogram'); //Nikon
			return $sm;
		}
		
		public function Abilities() {
			if(!isset($this->_abilities)) {
				$this->_abilities = GPHOTO2::GP2_PortCommand($this->Port,'--abilities', true, false);
				$this->LogEntries[] = "Get Camera Abilities";
				$this->Persist();
			}
			return $this->_abilities;
		}

		public function Summary() {
			if(!isset($this->_summary)) {
				$this->_summary = GPHOTO2::GP2_PortCommand($this->Port,'--summary', true, false);
				$this->LogEntries[] = "Get Camera Summary";
				$this->Persist();
			}
			return $this->_summary;
		}

		public function GetConfigs() {
			if(!isset($this->_configs)) {
				$settings = GPHOTO2::GP2_PortCommand($this->Port,'--set-config capture=on --list-config', false, true);
				$this->_configs = $settings;
				foreach($settings as &$setting) {
					$cat = substr($setting, 0, strrpos($setting,'/'));
					if($cat  == '/main/capturesettings' || $cat == '/main/imgsettings')
						$this->_configOptions[] = new ConfigOption($setting, $this, false);
				}
				$this->LogEntries[] = "Get Camera Settings";
				$this->Persist();
			}
			return $this->_configOptions;
		}

		public function Config($config) {
			$cfgs = $this->GetConfigs();
			foreach($cfgs as &$setting) {
				if($setting->Setting == $config)
					return $setting;
			}
			return null;
		}

		public function ConfigsInCategory($cat) {
			$cfgs = $this->GetConfigs();
			$_results = array();
			foreach($cfgs as &$setting) {
				if($setting->Category == $cat) {
					if($setting == $this->ShootingMode())
						array_unshift($_results, $setting); //keep the shooting mode at the start of the array
					else 
						$_results[] = $setting;
				}
			}
			return $_results;
		}
		
		public function SetValue($param) {
			if($param != "") {					
				$index = strpos($param, "=");
				$name = substr($param, 0, $index);
				$val= substr($param, $index+1);
				$config = $this->Config($name);
				$opt = $config->GetOption($val);
				$cmdParams = ' --set-config-value '.$name.'="'.$opt->Name.'"';
				
				$result = GPHOTO2::GP2_PortCommand($this->Port, $cmdParams, false, false);
				if(strpos($result, 'error') === false)
					$config->SetCurrent($val);
				else
					return false;
				
			}
			else
				return false;
			
			return true;
		}
		
		public function SetValues($params) {
			if($params != "") {
				$paramList = explode('|',$params);
				$cmdParams = '';
				foreach($paramList as &$param) {
					
					$index = strpos($param, "=");
					$name = substr($param, 0, $index);
					$val= substr($param, $index+1);
					$config = $this->Config($name);
					$opt = $config->GetOption($val);
					$cmdParams .= ' --set-config-value '.$name.'="'.$opt->Name.'"';
					//$config->SetCurrent($val);
				}
				
				$result = GPHOTO2::GP2_PortCommand($this->Port, $cmdParams, true, false);
				if(strpos($result, 'error') === false) {
					foreach($paramList as &$param) {
						$index = strpos($param, "=");
						$name = substr($param, 0, $index);
						$val= substr($param, $index+1);
						$config = $this->Config($name);
						$config->SetCurrent($val);
					}
					return true;
				} else 
					return false;
			}
		}

		public function Capture($saveToCamera, $saveToServer, $saveLocation, $params) {
			$this->SetValues($params);
			$imagefile= date('Y-m-d_H_i_s').'.jpg';
			$cmd = "--capture-image-and-download";//$saveToServer === 'true' ? "--capture-image-and-download" : "--capture-image";
			if($saveToCamera === 'false')
				$cmd .= " --no-keep";

			GPHOTO2::GP2_PortCommand($this->Port, $cmd.' --filename latest.jpg', true, false);
			
			if($saveToServer === 'true') {
				if(!file_exists($saveLocation))
					mkdir($saveLocation, 0777, true);
			
				error_log("Copying latest to ".$saveLocation."/".$imagefile."\r\n", 3, "/var/www/shutterweb/logging.log");
				copy("latest.jpg", $saveLocation."/".$imagefile);
			}
			
			return './photos/'.$imagefile;
		}

		public function StartTimelapse($name, $shotdelay, $shotcount, $saveToCamera, $saveToServer, $saveLocation, $params) {
			$this->SetValues($params);
			$this->Timelapse = new Timelapse($name, $shotdelay, $shotcount, $saveToCamera, $saveToServer, $saveLocation);
			$this->Timelapse->Start($this->Port);
			$this->Persist();
			return $this->Timelapse;
		}
		
		public function Persist() {
			$this->LogEntries = array_reverse(array_slice(array_reverse($this->LogEntries), 0, 99));
			$this->LogEntries[] = "settings written to file";
			apc_store($this->Port, serialize($this));
			file_put_contents("cameraconfigs/".$this->NameAsFilename().".cfg", serialize($this));
		}
		
		public function NameAsFilename() {
			return mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $this->Name);
		}
		
		private function loadFoldersAndFiles() {
			//More information on the files (size, resolution) can be gotten by a call to gphoto without "--quiet"
			$listFilesResult = GPHOTO2::GP2_PortCommand($this->Port,'--list-files', false, true);
			$this->_folders = array();
			$i =1;
			foreach($listFilesResult as &$line) {
		
				$folder = substr($line, 0, strrpos($line,'/')+1);
				$file = substr($line, strrpos($line,'/')+1);
				
				if(!array_key_exists((string)$folder, $this->_folders) && !empty($folder)) 
					$this->_folders[$folder] = array();
				$this->_folders[$folder][] = new CameraFile($i, $folder, $file);
				$i++;
			}
		}
		
		public function getFolders() {
			$this->loadFoldersAndFiles();
			return $this->_folders;
		}
		
		public function getFilesInFolder($folder) {
			$this->loadFoldersAndFiles();
			if(array_key_exists((string)$folder, $this->_folders)) 
				return $this->_folders[(string)$folder];
			else 
				return array();
		}
		
		public function getFileThumbnail($idx) {
			GPHOTO2::GP2_PortCommand($this->Port,'--get-thumbnail '.$idx.' --filename nail.jpg', false, true);
			return "thumb_nail.jpg";
		}
		
		public function getFile($idx) {
			GPHOTO2::GP2_PortCommand($this->Port,'--get-file '.$idx.' --filename grabbed.jpg', false, true);
			return "grabbed.jpg";
		}
	}
	
	class CameraFile {
		public $Index;
		public $FullPath;
		public $Folder;
		public $File;
		
		public function __construct($index, $folder, $file) {
			$this->Index = $index;
			$this->FullPath = $folder.$file;
			$this->Folder = $folder;
			$this->File = $file;
		}
	}
	
	class Timelapse {
		
		public $Name;
		public $Delay;
		public $Count;
		protected $Folder;
		protected $SaveToCamera;
		protected $SaveToServer;
		protected $SaveLocation;
		
		public function __construct($name, $delay, $count, $saveToCamera, $saveToServer, $saveLocation) {
			$this->Name = $name;
			$this->Delay = $delay;
			$this->Count = $count;
			$this->SaveToCamera = $saveToCamera;
			$this->SaveToServer = $saveToServer;
			$this->SaveLocation = $saveLocation;
		}

		public function Start($port) {
			//$cmd = '  -I '.$this->Delay.' -F '.$this->Count.' --capture-image-and-download --keep --filename latest.jpg';
			//GPHOTO2::GP2_CommandEx($cmd.' --port '.$port.' > /dev/null &', false, false);
			//$imagefile= date('Y-m-d_H_i_s').'.jpg';
			$cmd = '  -I '.$this->Delay.' -F '.$this->Count.' --capture-image-and-download';
			if($this->SaveToCamera === 'false')
				$cmd .= " --no-keep";
			if($this->SaveToServer === 'true' && !file_exists($this->SaveLocation."/".$this->Name))
				mkdir($this->SaveLocation."/".$this->Name, 0777, true);
			//echo $this->SaveLocation."/".$this->Name;
			$filename = $this->SaveToServer === 'true' ? $this->SaveLocation."/".$this->Name."/%Y-%m-%d_%H_%M_%S.jpg" : 'latest.jpg';
			$cmd .= ' --filename '.$filename;
			
			//GPHOTO2::GP2_PortCommand($port, $cmd.' --filename latest.jpg', true, false);
			GPHOTO2::GP2_CommandEx($cmd.' --port '.$port.' > /dev/null &', false, false);
			
			//if($this->SaveToServer === 'true') {
			//	error_log("Saving latest.jpg\r\n", 3, "/var/www/shutterweb/logging.log");
			//	copy($this->SaveLocation.date('Y-m-d_H_i_s').'.jpg', "latest.jpg");
			//}
		}
		
		public function Done() {
			//$fi = new FilesystemIterator($this->SaveLocation, FilesystemIterator::SKIP_DOTS);
			//$Done = iterator_count($fi);
			$Done = 0;
			$files = glob($this->SaveLocation."/".$this->Name."/*");
			if ($files){
				$Done = count($files);
			}
			
			return $Done;
		}
	}

	class ConfigOption {
		
		public $Setting;
		public $Loaded = false;
		public $Category;
 
		protected $_camera;
		protected $_label;
		protected $_type;
		protected $_current;
		protected $_options = array(); //key is shootingmode, value is array of options for that shootingmode

		public function Label() { $this->Load(); return $this->_label; }
		public function Type() { $this->Load(); return $this->_type; }
		public function Current() { $this->Load(); return $this->_current; }

		public function Options() { 
			$this->Load(); 
			$shootMode = $this->ShootingModeCurrent();
			return $this->_options[$shootMode]; 
		}
		
		public function SetCurrent($current) { $this->_current = $current; $this->_camera->Persist();}

		public function GetOption($val) {
			$opts = $this->Options();
			$opts = $opts;
			
			foreach($opts as &$opt) {
				if($opt->Value == $val)
					return $opt;
			}
			return null;
		}

		public function __construct($setting, &$camera, $load) {
			$this->Setting = $setting;
			$this->Category = substr($setting, 0, strrpos($setting,'/'));
			$this->_camera = &$camera;
			if($load)
				Load();
		}
		
		public function SetCamera($cam) {
			$this->_camera = &$cam;
		}
		
		protected function ShootingModeCurrent() { 
			if(
				$this->Setting == '/main/capturesettings/shootingmode' 
				|| $this->Setting == '/main/capturesettings/expprogram'
				|| $this->Category == '/main/imgsettings')
				return 0; //for these, shooting mode is self or isn't relevant so always return 0.
			return $this->_camera->ShootingMode()->Current(); 
		}
		
		public function ReadCurrentValue() {
			
			$shootMode = $this->ShootingModeCurrent();
			
			$raw = GPHOTO2::GP2_PortCommand($this->_camera->Port, '--get-config '.$this->Setting, false, true);
			$current = str_replace("Current: ","",$raw[2]);
			if($this->Type() == "RANGE")
				$this->SetCurrent($current);
			else {
				foreach($this->Options() as $opt) {
					if($opt->Name == $current)
						$this->_current = $opt->Value;
					
				}
				
			}
			//$this->_current = $current;
		}

		protected function Load() {
			
			$shootMode = $this->ShootingModeCurrent();
			
			//$this->_camera->LogEntries[] = "Loading Camera Settings Options for ".$shootMode." shooting mode [".$this->_label." - ".$this->Setting."]";
			//$this->_camera->Persist();
			
			if(!array_key_exists($shootMode, $this->_options))
			{
				$raw = GPHOTO2::GP2_PortCommand($this->_camera->Port, '--get-config '.$this->Setting, false, true);
				//$this->Raw = $raw;
				$this->_label = str_replace("Label: ","",$raw[0]);
				$this->_type = str_replace("Type: ","",$raw[1]);
				$current = str_replace("Current: ","",$raw[2]);
				$this->_options[$shootMode] = array();
				
				if($this->Type() == "RANGE") {
					$bottom = str_replace("Bottom: ","",$raw[3]);
					$Top = str_replace("Top: ","",$raw[4]);
					$Step = str_replace("Step: ","",$raw[5]);
	
					for($i = $bottom; $i <= $Top; $i+=$Step)
						$this->_options[$shootMode][] = new ConfigOptionChoice($i, $i);
					$this->_current = $current;
				} else if($this->Type() == "RADIO") {
					for($i = 3; $i < count($raw)-1; $i++) {
						$parsed = str_replace("Choice: ","",$raw[$i]);
						$index = strpos($parsed, " ");
						$name = substr($parsed, $index+1);
						$val = substr($parsed, 0, $index);
						$this->_options[$shootMode][] = new ConfigOptionChoice($name, $val);
						if($name == $current)
							$this->_current = $val;
					}
				}

				$Loaded = true;
				$this->_camera->LogEntries[] = "Saving Camera Settings Options for ".$shootMode." shooting mode [".$this->_label." - ".$this->Setting."]";
				$this->_camera->Persist();
			}
		}
	}

	class ConfigOptionChoice {
		public $Value;
		public $Name;
		public function __construct($name, $value) {
			
			$this->Value = $value;
			$this->Name = $name;
		}
	}
?>
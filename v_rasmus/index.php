<?php
	ini_set('default_charset', 'utf-8');
	require_once "etc/connect.php";
	ob_start();

	$blacklistFolder = ["content", "etc"];

	function checkIsFile($inUrl){
		if(is_dir($_SERVER["DOCUMENT_ROOT"] . $inUrl)){
			if($inUrl == null){
				$inUrl = "/";
			}
			foreach(array("html", "htm", "php", "aps", "apsx", "jpg") AS $ex){
				$out = $_SERVER["DOCUMENT_ROOT"] . $inUrl . "index." . $ex;

				if(is_file($out) && $out != $_SERVER["DOCUMENT_ROOT"] . "/index.php"){
					$inUrl = $inUrl . "index." . $ex;
					return $inUrl;
				}
			}
			return false;
		} elseif(is_file($_SERVER["DOCUMENT_ROOT"] . $inUrl)){
			return $inUrl;
		} else {
			return false;
		}
		return false;
	}

	function blacklistFolder($blacklistFolder, $inUrl){
		error_reporting(0);
		foreach($blacklistFolder as $folder => $filetypes){
			if(is_numeric($folder)){
				$folder = $filetypes;
				$filetypes = null;
			}
			if($filetypes != ""){
				if(is_array($filetypes)){
					for($i = 0; $i < count($filetypes); $i++)
						$ft .= "\.".trim($filetypes[$i])."|";
				} else{
					$fta = preg_split("/[\,\;]/", $filetypes);
					for($i = 0; $i < count($fta); $i++)
						$ft .= "\." . trim($fta[$i]) . "|";
				}
			}
			if(preg_match("/^\/$folder\/.*(\.php|\.html|\.htm|$ft\/)$/i", $inUrl)){
				return "/";
			}
		}
		error_reporting(22527);
		return false;
	}


	error_reporting(0);
	$inUrl = $_SERVER["REDIRECT_URL"];
	$type = 3;
	error_reporting(22527);

	$url = checkIsFile($inUrl);
	if(!$url){
		if(preg_match("/(\.gif)|(\.jpg)|(\.jpeg)|(\.png)$/", strtolower($inUrl))){
			$inUrl = preg_replace("/\.+[a-z]*$/", '', strtolower($inUrl));
		}
		$inUrl = preg_replace("/\.$/", '', $inUrl);
		$inUrl = preg_replace("/\/$/", '', $inUrl);

		$q = $con->prepare("SELECT url_id AS id, kildeUrl AS sUrl, destinationUrl AS dUrl, type FROM Redirect WHERE kildeUrl LIKE (:source) ORDER BY id DESC");
		$q->bindParam(":source", $inUrl);
		$q->execute();
		if($q->rowCount() == 0){
			$url = "/";
			$type = 0;
		} else{
			$row = $q->fetch(PDO::FETCH_ASSOC);
			$inUrl      = $row['sUrl'];
			$url        = $row['dUrl'];
			$type       = $row['type']; // type = 0 redirect, 1 rewrite, 2 image, 3 file
		}
	} else{
		$url = preg_replace("/^\//", "", $url);
		if(preg_match("/(\.gif)|(\.jpg)|(\.jpeg)|(\.png)$/i", strtolower($url))){
			$type = 2;
		} elseif(preg_match("/(\.php)|(\.html)|(\.htm)$/i", strtolower($url))){
			$type = 1;
		}
	}

	if(blacklistFolder($blacklistFolder, $inUrl)){
		$type=0;
	}

	if($url == "")
		die ("url mangler");

	if($type == 0){ // redirect
		$q = "";
		if($_SERVER["QUERY_STRING"] != "")
			$q = "?" . $_SERVER["QUERY_STRING"];

		Header("HTTP/1.1 301 Moved Permanently");
		Header("Location: " . $url . $q);
	} elseif($type == 1){ // rewrite
		error_reporting(0);
		$urlarray = preg_split("/\?/", $url, 2);

		if($urlarray[1] != ""){
			if(preg_match_all("/([a-z0-9]*)\=(.*?)(\&|\$)/", $urlarray[1], $get)){
				foreach($get[1] AS $k => $v){
					if($_GET[$v] == null){
						$_GET[$v] = $get[2][$k];
					}
				}
			}
			if(preg_match_all("/([a-z0-9]*)\=(.*?)(\&|\$)/", $_SERVER['REDIRECT_QUERY_STRING'], $get)){
				foreach($get[1] AS $k => $v){
					$_GET[$v] = $get[2][$k];
				}
			}
			$url = checkIsFile("/" . $urlarray[0]);
			$url = preg_replace("/^\/\//", "", $url);
			$url = preg_replace("/^\//", "", $url);
		} else{
			$url = preg_replace("/^\//", "", $url);
		}
		error_reporting(22527);
		include_once $url;
	} elseif($type == 2){ // image
		header("Content-type: " . mime_content_type($url));
		header("Content-Length: " . filesize($url));
		header("Content-Disposition: filename=\"$url\"");
		readfile($_SERVER["DOCUMENT_ROOT"] . "/" . $url);
	} elseif($type == 3){ // file
		$ext = strtolower(mime_content_type($url));
		$newFilname = $inUrl . $ext;

		switch($ext){ 
			case "css": 
				header('Content-Type: text/css; charset=UTF-8');
				break;
			case "js": 
				header('Content-Type: text/javascript; charset=UTF-8');
				break; 
			case "pdf": 
				header('Content-Type: application/pdf');
				break; 
			case "zip": 
				header("Content-type: application/zip");
				break; 
			case "doc": 
				header("Content-type: application/msword");
				break; 
			case "docx": 
				header("Content-type: application/msword");
				break; 
			case "xls": 
				header("Content-type: application/vnd.ms-excel");
				break; 
			case "xlsx": 
				header("Content-type: application/vnd.ms-excel");
				break; 
			case "ppt": 
				header("Content-type: application/vnd.ms-powerpoint");
				break; 
			case "pptx": 
				header("Content-type: application/vnd.ms-powerpoint");
				break;
			default: 
				header("Content-type: application/force-download"); 
		}
		header("Content-Length: " . filesize($url));
		header("Content-Disposition: filename=\"$newFilname\"");
		header("Cache-Control: no-cache");
		header("Pragma: no-cache");
		readfile($url);
	}
?>
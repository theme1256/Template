<?php
	ini_set('default_charset', 'utf-8');
	ob_start();

	// Checks if the given variable is a file or if an index file is available in that folder
	function checkIsFile($inUrl){
		if(is_dir(__DIR__ . $inUrl)){
			if($inUrl == null){
				$inUrl = "/";
			}
			foreach(["html", "htm", "php"] AS $ex){
				$out = __DIR__ . $inUrl . "index." . $ex;
				if(is_file($out) && $out != __FILE__)
					return $inUrl . "index." . $ex;
			}
		} elseif(is_file(__DIR__ . $inUrl)){
			return $inUrl;
		}
		return false;
	}
	// Checks if a given variable is in a blacklisted folder
	function blacklistFolder(&$blacklistFolder, $inUrl){
		foreach($blacklistFolder as $folder => $filetypes){
			$ft = "";
			if(!empty($filetypes)){
				if(is_array($filetypes))
					$ft = implode("|", $filetypes);
				else
					$ft = $filetypes;
			}
			if(preg_match("/^\/{$folder}\/.*\.(php|html|htm|{$ft}\/)$/i", $inUrl))
				return true;
		}
		return false;
	}
	// Find and compare to regex from database
	function regexTest($url, &$db){
		global $_GET;
		try{
			$q = $db->prepare("SELECT sourceUrl, destinationUrl, type, noindex, url_id FROM redirect WHERE type LIKE (4)");
			$q->execute();
			foreach($q->fetchAll(PDO::FETCH_ASSOC) as $test){
				if(preg_match("/".str_replace("/", "\/", $test['kildeUrl'])."/i", $url, $match)){
					if(strpos($test['destinationUrl'], "?") !== false){
						$tmp = @(explode("?", $test['destinationUrl'])[1]);
						$tmp = explode("&", $tmp);
						foreach($tmp as $param){
							$t = explode("=", $param);
							$_GET[$t[0]] = @($match[intval(str_replace("\$", "", $t[1]))]);
						}
					}
					return $test;
				}
			}
			return false;
		} catch(PDOException $e){
			die("kan ikke udføre regex-URL handling" . $e->getMessage());
		}
	}

	require_once "etc/connect.php";
	$blacklistFolder = ["indhold" => [], "etc" => []];

	$rw_desc = [0 => "redirect", 1 => "rewrite", 2 => "image", 3 => "file", 4 => "regex"];

	$inUrl = @($_SERVER["REDIRECT_URL"]);
	$type = 3;

	$url = checkIsFile($inUrl);
	if(!$url){
		// If it's an image, remove extension
		if(preg_match("/\.(gif|jpg|jpeg|png)$/i", $inUrl)){
			$inUrl = preg_replace("/\.+[a-z]*$/i", '', $inUrl);
		}
		// Remove trailing . and /
		$inUrl = preg_replace("/(\.|\/)$/", '', $inUrl);

		try{
			$q = $con->prepare("
				SELECT
					url_id AS id,
					sourceUrl AS sUrl,
					destinationUrl AS dUrl,
					type,
					noindex
				FROM `Redirect`
				WHERE
					`sourceUrl` LIKE (:inUrl)
				ORDER BY `id` DESC
			");
			$q->bindParam(":inUrl", $inUrl);
			$q->execute();
			if($q->rowCount() == 0){
				// Couldn't find, trying regex
				$tmp = regexTest($inUrl, $con);
				if(is_array($tmp)){
					$type = $tmp['type'];
					$noindex = $tmp['noindex'];
					$url = explode("?", $tmp['destinationUrl'])[0];
					$type = 1;
				} else{
					$url = "/";
					$type = 0;
				}
			} else{
				$row = $q->fetch(PDO::FETCH_ASSOC);
				$inUrl   = $row['sUrl'];
				$url     = $row['dUrl'];
				$type    = $row['type'];
				$noindex = $row['noindex'];
			}
		} catch(PDOException $e){
			die("kan ikke udføre URL handling" . $e->getMessage());
		}
	} else{
		// Remove starting /
		$url = preg_replace("/^\//", "", $url);
		// Check if image or php/html
		if(preg_match("/\.(gif|jpg|jpeg|png)$/i", $url)){
			$type = 2;
		} elseif(preg_match("/\.(php|html|htm)$/i", $url)){
			$type = 1;
		}
	}

	// Stop if url is missing
	if($url == "")
		die("url mangler");

	// Stop access to blacklisted folders
	if(blacklistFolder($blacklistFolder, $inUrl))
		$type = 0;

	// A bit of logging, if not an image or a file
	if(!in_array($type, [2, 3])){
		$q = "";
		if($_SERVER["QUERY_STRING"] != "")
			$q = "?" . $_SERVER["QUERY_STRING"];

		$logArray = [];
		// Fetch from session
		$m = 0;
		if(@sizeof($_SESSION["logurl"]) > 0){
			foreach($_SESSION["logurl"] AS $logkey => $logvalue){
				// Log only 4 previous
				if($m > 4)
					continue;
				$logArray[$m] = $logvalue;
				$m++;
			}
		}
		$logArray[$m] = [
			"HTTP_REFERER" => @($_SERVER["HTTP_REFERER"]),
			"time" => date("Y-m-d H:i:s"),
			"inUrl" => $inUrl . $q,
			"load_file" => $url,
			"load_type" => $type . " | type description: " . $rw_desc[$type]
		];

		$_SESSION["logurl"] = $logArray;
	}

	if($noindex == 1){
		header("X-Robots-Tag: noindex");
		header("Pragma: private");
	} else{
		header("Pragma: public");
	}

	if($type == 0){ // redirect
		header($_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently', true, 301);
		header("Location: " . $url . $_SERVER["QUERY_STRING"]);
	} elseif($type == 1){ // rewrite
		$urlarray = explode("?", $url);

		// If there is something after ?
		if(!empty($urlarray[1])){
			if(preg_match_all("/([a-z0-9]*)\=(.*?)(\&|\$)/", $urlarray[1], $get)){
				foreach($get[1] AS $k => $v){
					if(@($_GET[$v]) == null){
						$_GET[$v] = $get[2][$k];
					}
				}
			}
			if(preg_match_all("/([a-z0-9]*)\=(.*?)(\&|\$)/", @($_SERVER['REDIRECT_QUERY_STRING']), $get)){
				foreach($get[1] AS $k => $v){
					$_GET[$v] = $get[2][$k];
				}
			}
			$url = checkIsFile("/".$urlarray[0]);
			$url = preg_replace("/^\/\//", "", $url);
			$url = preg_replace("/^\//", "", $url);
			require_once $url;
		} else{
			require_once preg_replace("/^\//", "", $url);
		}
	} elseif($type == 2){ // image
		if(preg_match("/^\//", $url))
			$url = __DIR__ . $url;

		header("Expires: Wed, 01 Jan 2018 00:00:00 GMT");
		header("Content-type: " . getimagesize($url)['mime']);
		header("Content-Length: " . filesize($url));
		@header("Content-Disposition: filename=\"" . end(preg_split("/\//", $url)) . "\"");
		header("Cache-Control: max-age=2592000");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", filemtime($url)) . " GMT");
		readfile($url);
	} elseif($type == 3){ // file
		$mime = strtolower(mime_content_type($url));
		if(in_array($mime, ["text/css", "text/javascript", "application/pdf", "application/zip", "application/msword", "application/vnd.ms-excel", "application/vnd.ms-powerpoint"]))
			header("Content-type: ".$mime);
		else
			header("Content-type: application/force-download");

		header("Content-Length: " . filesize($url));
		header("Content-Disposition: filename=\"" . substr($inUrl, 1) . "\"");
		header("Cache-Control: no-cache");
		header("Pragma: no-cache");
		readfile($url);
	}
?>
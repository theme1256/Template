<?php
	\ini_set('default_charset', 'utf-8');
	\ob_start();

	// Finder ud af om den givne variabel er en fil, eller om der ligger en index fil i den mappe, hvis det er en mappe
	function checkIsFile($inUrl) {
		if (\is_dir(__DIR__ . $inUrl)) {
			if ($inUrl == null) {
				$inUrl = "/";
			}
			foreach (["php", "html", "htm"] AS $ex) {
				$out = __DIR__ . $inUrl . "index." . $ex;
				if (\is_file($out) && $out != __FILE__)
					return $inUrl . "index." . $ex;
			}
		} elseif (\is_file(__DIR__ . $inUrl)) {
			return $inUrl;
		} else {
			foreach(["php", "html", "htm"] AS $ex){
				$out = __DIR__ . $inUrl . $ex;
				if (\is_file($out) && $out != __FILE__)
					return $inUrl . $ex;
			}
		}
		return false;
	}
	// Tjekker om den givne variabel befinder sig i en blacklistet mappe
	function blacklistFolder(&$blacklistFolder, $inUrl) {
		foreach($blacklistFolder as $folder => $filetypes) {
			$ft = "";
			if (!empty($filetypes)) {
				if (\is_array($filetypes))
					$ft = \implode("|", $filetypes);
				else
					$ft = $filetypes;
			}
			if (\preg_match("/^\/{$folder}\/.*\.(php|html|htm|{$ft}\/)$/i", $inUrl))
				return true;
		}
		return false;
	}
	// Finde og sammenligner regex versioner af URL fra databasen
	function regexTest($url, &$db) {
		global $_GET;
		try {
			$q = $db->prepare("SELECT sourceUrl, destinationUrl, type, noindex, url_id FROM Redirect WHERE type LIKE (4)");
			$q->execute();
			foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $test) {
				if (\preg_match("/".\str_replace("/", "\/", $test['kildeUrl'])."/i", $url, $match)) {
					if (\strpos($test['destinationUrl'], "?") !== false) {
						$tmp = @(\explode("?", $test['destinationUrl'])[1]);
						$tmp = \explode("&", $tmp);
						foreach ($tmp as $param) {
							$t = \explode("=", $param);
							$_GET[$t[0]] = @($match[\intval(\str_replace("\$", "", $t[1]))]);
						}
					}
					return $test;
				}
			}
			return false;
		} catch (\PDOException $e) {
			die("kan ikke udføre regex-URL handling" . $e->getMessage());
		}
	}

	require_once "etc/common.php";
	$blacklistFolder = ["indhold" => [], "etc" => []];

	$rw_desc = [0 => "redirect", 1 => "rewrite", 2 => "image", 3 => "file", 4 => "regex"];

	$inUrl = @($_SERVER["REDIRECT_URL"]);
	$type = 3;
	$noindex = 1;


	$url = \checkIsFile($inUrl);
	if (!$url) {
		// Hvis det er en gif, jpg, jpeg eller png, fjern extension
		if (\preg_match("/\.(gif|jpg|jpeg|png)$/i", $inUrl)) {
			$inUrl = \preg_replace("/\.+[a-z]*$/i", '', $inUrl);
		}
		// Fjern afsluttende . eller /
		$inUrl = \preg_replace("/(\.|\/)$/", '', $inUrl);

		try {
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
			if ($q->rowCount() == 0) {
				// inUrl kunne ikke findes, tjekker om en regexUrl matcher den i stedet
				$tmp = \regexTest($inUrl, $con);
				if (\is_array($tmp)) {
					$type = 1;
					$noindex = $tmp['noindex'];
					$url = \explode("?", $tmp['destinationUrl'])[0];
				} else {
					$url = "/";
					$type = 0;
				}
			} else {
				$row = $q->fetch(\PDO::FETCH_ASSOC);
				$inUrl   = $row['sUrl'];
				$url     = $row['dUrl'];
				$type    = $row['type'];
				$noindex = $row['noindex'];
			}
		} catch (\PDOException $e) {
			die("kan ikke udføre URL handling" . $e->getMessage());
		}
	} else {
		// Fjern begyndende /
		$url = \preg_replace("/^\//", "", $url);
		// Tjek om det er et billede eller en php/html side og sæt type
		if(\preg_match("/\.(gif|jpg|jpeg|png)$/i", $url)) {
			$type = 2;
		} elseif(\preg_match("/\.(php|html|htm)$/i", $url)) {
			$type = 1;
		}
	}

	// Hvis der på dette tidspunkt ikke er en URL, så er der sket en kæmpe fejl
	if ($url == "")
		die("url mangler");

	// Gør så det ikke er muligt at tilgå filer i blacklistede mapper
	if (\blacklistFolder($blacklistFolder, $inUrl))
		$type = 0;

	// Lidt log, hvis det ikke er et billede
	if ($type != 2) {
		$q = "";
		if ($_SERVER["QUERY_STRING"] != "")
			$q = "?" . $_SERVER["QUERY_STRING"];

		$logArray = [];
		// Hent tidligere variabler fra session
		$m = 0;
		if (@\sizeof($_SESSION["logurl"]) > 0) {
			foreach ($_SESSION["logurl"] AS $logkey => $logvalue) {
				// Log kun 4 gange bagud
				if ($m > 4)
					continue;
				$logArray[$m] = $logvalue;
				$m++;
			}
		}
		$logArray[$m] = [
			"HTTP_REFERER" => @($_SERVER["HTTP_REFERER"]),
			"time" => \date("Y-m-d H:i:s"),
			"inUrl" => $inUrl . $q,
			"load_file" => $url,
			"load_type" => $type . " | type beskrivelse: " . $rw_desc[$type]
		];

		$_SESSION["logurl"] = $logArray;
	}

	if ($type == 0) { // redirect
		\header($_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently', true, 301);
		\header("Location: " . $url . $_SERVER["QUERY_STRING"]);
	} elseif ($type == 1) { // rewrite
		if ($noindex > 0)
			\header("X-Robots-Tag: noindex");
		$urlarray = \explode("?", $url);

		// Hvis der er noget efter ? i urlen
		if (!empty($urlarray[1])) {
			$tmp = \explode("&", $urlarray[1]);
			foreach ($tmp as $param) {
				$t = \explode("=", $param);
				$_GET[$t[0]] = $t[1];
			}
			$url = \checkIsFile("/".$urlarray[0]);
			$url = \preg_replace("/^\/\//", "", $url);
			$url = \preg_replace("/^\//", "", $url);
			require_once $url;
		} else {
			require_once \preg_replace("/^\//", "", $url);
		}
	} elseif ($type == 2) { // image
		if (\preg_match("/^\//", $url))
			$url = __DIR__ . $url;

		\header("Expires: Wed, 01 Jan 2018 00:00:00 GMT");
		\header("Content-type: " . \getimagesize($url)['mime']);
		\header("Content-Length: " . \filesize($url));
		\header("Content-Disposition: filename=\"" . \end(\preg_split("/\//", $url)) . "\"");
		\header("Cache-Control: max-age=2592000");
		\header("Last-Modified: " . \gmdate("D, d M Y H:i:s", \filemtime($url)) . " GMT");
		\header("Pragma: public");
		\readfile($url);
	} elseif ($type == 3) { // file
		$mime = \strtolower(\mime_content_type($url));
		if (\in_array($mime, ["text/css", "text/javascript", "application/pdf", "application/zip", "application/msword", "application/vnd.ms-excel", "application/vnd.ms-powerpoint"]))
			\header("Content-type: ".$mime);
		else
			\header("Content-type: application/force-download");

		\header("Content-Length: " . \filesize($url));
		\header("Content-Disposition: filename=\"" . \substr($inUrl, 1) . "\"");
		\header("Cache-Control: no-cache");
		\header("Pragma: no-cache");
		\readfile($url);
	}
?>
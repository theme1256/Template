<?php
	// Database stuff
	require_once(__DIR__ . "/connect.php");

	// Plugins
	require_once(__DIR__ . "/plugins/Html2Text/Html2Text.php";)
	require_once(__DIR__ . "/plugins/Mobile-Detect-2.8.24/Mobile_Detect.php";)

	// Constants
	define('HOME', '/');
	define('ASSETS', HOME.'assets/');
	define('CSS', ASSETS.'css/');
	define('JS', ASSETS.'js/');

	// Check device type
	$mobile = new Mobile_Detect();
	define("IOS", 			$mobile->isiOS() ? true : false);
	define("ANDROID", 		$mobile->isAndroidOS() ? true : false);
	define("MOBILE", 		$mobile->isMobile() ? true : false);
	define("TABLET", 		$mobile->isTablet() ? true : false);
	define("onlyScreen",	(TABLET || MOBILE || IOS) ? false : true);
?>
<?php
$cookielifetime = 1209600;

$trackingNumbers = array();
$trackingNumbers_json = array();

$autorefresh = htmlspecialchars(@$_COOKIE["autorefresh"]);

$lang = "en";
$browserlang = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
if(in_array($browserlang,array("no","nb","nn",""))){
	$lang = "no";
}

if (!empty($_COOKIE["trackingNumbers_json"])) {
	$incoming = json_decode($_COOKIE["trackingNumbers_json"], true);
	foreach ($incoming as $package){
		addTrackingNumber($package["trackingnumber"], $package["name"]);
	}
}

if (!empty($_GET["json"])){
	$incoming = json_decode(base64_decode(@$_GET["json"]), true);
	foreach ($incoming as $package)
	addTrackingNumber($package["trackingnumber"], $package["name"]);
}

if (!empty($_COOKIE["trackingNumbers"]) || !empty($_GET["trackingNumbers"]) || !empty($_POST["trackingNumber"])) {
	$input = cleanString(@$_COOKIE["trackingNumbers"] . ";" . @$_GET["trackingNumbers"] . ";" . @$_POST["trackingNumber"]);
	$input = explode(";", $input);
	$trackingNumbers = array_merge($trackingNumbers, $input);

	//Adds dsv values to json
	foreach ($trackingNumbers as $trackingnumber)
	addTrackingNumber($trackingnumber);
}

if (!empty($_GET["remove"])) {
	$get = htmlspecialchars($_GET["remove"]);

	//OLD, for dsv removed 14 days after json implementation
	foreach ($trackingNumbers as $key => $value) {
		if ($value == $get) {
			unset($trackingNumbers[$key]);
		}
	}

	//JSON
	foreach ($trackingNumbers_json as $key => $value) {
		if ($value["trackingnumber"] == $get) {
			unset($trackingNumbers_json[$key]);
		}
	}


	if (empty($trackingNumbers)) {
		setcookie("trackingNumbers", "", time() - 3600);
	}

	if (empty($trackingNumbers_json)) {
		setcookie("trackingNumbers_json", "", time() - 3600);
	}
}

if (!empty($_GET["autorefresh"])) {
	$get = htmlspecialchars($_GET["autorefresh"]);

	if ($get == "flip") {
		$autorefresh = !$autorefresh;
	} elseif ($get == "on") {
		$autorefresh = true;
	} else {
		$autorefresh = false;
	}

	setcookie("autorefresh", $autorefresh, time() + $cookielifetime);
}

if (!empty($_POST["shipmentName"]) && !empty($_POST["trackingnumberToName"])) {
	foreach ($trackingNumbers_json as $packageID => $package) {
		if ($package["trackingnumber"] == $_POST["trackingnumberToName"]) {
			$trackingNumbers_json[$packageID]["name"] = htmlspecialchars($_POST["shipmentName"]);
		}
	}
}

if (!empty($trackingNumbers_json)) {
	//We have clean input. Cleanup not needed.
	setcookie("trackingNumbers_json", json_encode($trackingNumbers_json), time() + $cookielifetime);
}

if (!empty($_GET) || !empty($_POST)) {
	header("Location: /", TRUE, 303);
	exit ;
}

function getTrackingInfo($trNumber) {
	global $lang;

	// 158.39.116.232/
	$json_url = "http://sporing.bring.no/sporing.json?lang=" . $lang . "&q=" . $trNumber;

	$ctx = stream_context_create(array(
		'http' => array(
			'timeout' => 30
		)
	)
);

$json = file_get_contents($json_url, FALSE, $ctx);

//$json = file_get_contents($json_url);

// var_dump($json);

return json_decode($json, TRUE);
}

function getBarcode($data) {
	return "/Barcode39.php?barcode=$data";
}

function getQRCode($data) {
	//$size = "150x150";
	$size = "230x230";
	//$correction = "L&#124;2";
	$encoding = "UTF-8";

	// return "https://chart.googleapis.com/chart?cht=qr&amp;chs=$size&amp;chl=$data&amp;choe=$encoding&amp;chld=$correction";
	return "https://chart.googleapis.com/chart?cht=qr&amp;chs=$size&amp;chl=$data&amp;choe=$encoding";
}

function getFullQRCode($data) {
	return getQRCode(getFullURL($data));
}

function getFullURL($data) {
	return (SSL ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . "/?json=" . $data;
}

function cleanString($data){
	$data = htmlspecialchars($data);
	$data = str_replace(array(",", "+", ":", " ", ".", "\\", "/"), ";", $data);
	return $data;
}

function addTrackingNumber($trackingnumber, $name = ""){
	global $trackingNumbers_json;

	$trackingnumber = trim($trackingnumber);

	if(strlen($trackingnumber) >= 1) {

		$notADupe = true;
		foreach ($trackingNumbers_json as $packageID => $package) {
			if ($package["trackingnumber"] == $trackingnumber)
			$notADupe = false;
		}

		if($notADupe){
			$temp = array("trackingnumber" => $trackingnumber,);

			if (strlen($name) >= 1)
			$temp["name"] = $name;

			$trackingNumbers_json[] = $temp;
		}
	}
}

if($_SERVER["HTTP_HOST"] != URL)
header("Location: http://" . URL . "/?json=" . urlencode(base64_encode(json_encode($trackingNumbers_json))), TRUE, 307);
?>

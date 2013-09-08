<?php
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set("Europe/Oslo");

require 'lang.php';
$lang = "no";

$cookielifetime = 1209600;

$trackingNumbers = array();

$autorefresh = htmlspecialchars($_COOKIE["autorefresh"]);

if (!empty($_COOKIE["trackingNumbers"])) {
	$cookie = cleanString($_COOKIE["trackingNumbers"]);
	$trackingNumbers = array_merge($trackingNumbers, explode(";", $cookie));
}

if (!empty($_GET["trackingNumbers"])) {
	$get = cleanString($_GET["trackingNumbers"]);
	$trackingNumbers = array_merge($trackingNumbers, explode(";", $get));
	header("Location: /", TRUE, 307);
}

if (!empty($_GET["remove"])) {
	$get = htmlspecialchars($_GET["remove"]);

	foreach ($trackingNumbers as $key => $value) {
		if ($value == $get) {
			unset($trackingNumbers[$key]);
		}
	}

	if (empty($trackingNumbers)) {
		setcookie("trackingNumbers", "", time() - 3600);
	}

	header("Location: /", TRUE, 307);
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

	header("Location: /", TRUE, 307);
}

if (!empty($_POST["trackingNumber"])) {
	$post = cleanString($_POST["trackingNumber"]);
	
	$trackingNumbers = array_merge ( $trackingNumbers, explode(";", $post));
}
// array_push($trackingNumbers, "TESTPACKAGE-AT-PICKUPPOINT");

if (!empty($trackingNumbers)) {
	//trim all strings
	$trackingNumbers = array_map('trim', $trackingNumbers);
	//remove null and false
	$trackingNumbers = array_filter($trackingNumbers);
	//remove dupes
	$trackingNumbers = array_unique($trackingNumbers);
	$cookie = implode(";", $trackingNumbers);
	setcookie("trackingNumbers", $cookie, time() + 1209600);
}
if (!empty($_GET)) {
	header("Location: /", TRUE, 307);
	exit ;
}

function getTrackingInfo($trNumber) {
	// 158.39.116.232/
	$json_url = "http://sporing.bring.no/sporing.json?lang=" . $lang . "&q=" . $trNumber;
	
	/*$ctx=stream_context_create(array('http'=>
    	array(
        	'timeout' => 34 // 20 minutes
    	)
	));
	
	$json = file_get_contents($json_url, FALSE, $ctx);*/
	
	$json = file_get_contents($json_url);
	
	// var_dump($json);
	
	return json_decode($json, TRUE);
}

function getBarcode($data) {
	return "/Barcode39.php?barcode=$data";
}

function getQRCode($data) {
	$size = "150x150";
	$correction = "L|2";
	$encoding = "UTF-8";

	return "https://chart.googleapis.com/chart?cht=qr&amp;chs=$size&amp;chl=$data&amp;choe=$encoding&amp;chld=$correction";
}

function getFullQRCode($data) {
	return getQRCode(getFullURL($data));
}

function getFullURL($data) {
	$https = isset($_SERVER['HTTPS']);
	return ($https ? "https://" : "http://") . $_SERVER['SERVER_NAME'] . "/?trackingNumbers=" . $data;
}

function cleanString($data){
	$data = htmlspecialchars($data);
	$data = str_replace(array(",", "+", ":", " ", ".", "\\", "/"), ";", $data);
	return $data;
}
if($_SERVER["SERVER_NAME"] != "www.pakkespor.no")
	header("Location: http://www.pakkespor.no//?trackingNumbers=" . implode(";", $trackingNumbers), TRUE, 307);
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $t["Pakkesporing for Posten/Bring"][$lang]; ?></title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<?php
		if ($autorefresh)
			echo "<meta http-equiv=\"refresh\" content=\"600\">\n";
		?>
		<!-- Bootstrap -->
		<link rel="stylesheet" type="text/css" media="screen" href="/bs/css/bootstrap.min.css" >
		<link rel="stylesheet" type="text/css" media="print" href="/bs/css/bootstrap.min.css">
		<meta name="description" content="<?php echo $t["meta description"][$lang]; ?>">
		<style type="text/css">
			div {
				/*border:1px solid black;*/
			}
			body {
				padding-top: 40px;
				padding-bottom: 40px;
				background-color: #f5f5f5;
			}

			.package {
				-webkit-border-radius: 10px;
				-moz-border-radius: 10px;
				border-radius: 10px;
				background-color: #ffffff;
				margin-top: 5px;
				margin-bottom: 5px;
				padding-right: 15px;
				padding-left: 15px;
				padding-bottom: 15px;
				page-break-after: always;
				page-break-inside: avoid;
			}

			.navbar .nav, .navbar .nav > li {
				float: none;
				display: inline-block;
				*display: inline; /* ie7 fix */
				*zoom: 1; /* hasLayout ie7 trigger */
				vertical-align: top;
			}
			
			.twitter-follow-button {
				margin-bottom: -5px;
			}

			@media print {
				.noPrint {
					display: none;
				}
			}
		</style>
	</head>
	<body>
		<div class="container">
			<!-- Menu -->
			<div class="navbar navbar-static noPrint">
				<div class="plz-center" style="margin: 0 auto; text-align: center; width: 433px;">
					<form class="navbar-search" method="post">
						<div class="input-append">
							<input id="trackingNumber" name="trackingNumber" class="input" type="text" placeholder="<?php echo $t["Sporingsnummer"][$lang]; ?>">
							<button type="submit" class="btn">
								<?php echo $t["S&oslash;k"][$lang]; ?>
							</button>
						</div>
					</form>
					<a href="/?autorefresh=flip" class="btn<?php if ($autorefresh) echo " active"; ?>">
						<i class="icon-repeat"></i>
					</a>
					<ul class="nav">
						<li class="dropdown">
							<button class="btn dropdown-toggle" data-toggle="dropdown" href="#"><i class="icon-info-sign"></i></button>
							<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="dLabel">
								<li><?php echo $t["infotekst"][$lang]; ?></li>
							</ul>
						</li>
						<li class="dropdown">
							<button class="btn dropdown-toggle" data-toggle="dropdown" href="#"><i class="icon-qrcode"></i></button>
							<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="dLabel">
								<li>
									<a href="<?php echo getFullURL(implode(";", $trackingNumbers)) ?>" style="height:150px; margin:0px; padding: 0px; background-image:url('<?php echo getFullQRCode(implode(";", $trackingNumbers)); ?>');"></a>
								</li>
							</ul>
						</li>
					</ul>
				</div>
			</div>
			<?php
			foreach ($trackingNumbers as $trackingNumber) {
				$shipments = getTrackingInfo($trackingNumber);
				// var_dump($trackingNumber);
				// var_dump($shipment);
				// Foreach shipment (usually only 1)
				foreach ($shipments["consignmentSet"] as $shipment){
					?>
					<div class="row-fluid">
						<div class="shipment span12" style="min-width: 433px;">
							<span><?php echo $t["Sending"][$lang]; ?>: <?php echo $shipment["consignmentId"]; ?></span>
							<a href="/?remove=<?php echo $trackingNumber; ?>" type="button" class="close noPrint" aria-hidden="true">&times;</a>
							<?php
							if(!empty($shipment["error"])){
								?>
								<div class="row-fluid">
									<div class="alert span12 alert-error">
										<p><?php echo $t["Feils&oslash;kings info"][$lang]; ?>:</p>
										<pre>Input: <?php 
											echo $trackingNumber . "\n";
											var_dump($shipment);
										?>
										</pre>
									</div>
								</div>
								<?php
								continue;
							}
							foreach ($shipment["packageSet"] as $package){
								?>
								<div class="row-fluid">
									<div class="package span12">
										<h1><?php echo $package["packageNumber"]; ?></h1>
										<div class="row-fluid">
											<div class="span6">
												<table class="table">
													<?php
													if(!empty($package["statusDescription"])){
														?>
														<tr>
															<th><?php echo $t["Status"][$lang]; ?>:</th>
															<td><?php echo $package["statusDescription"]; ?></td>
														</tr>
													<?php }
													if(!empty($package["pickupCode"])){
														?>
														<tr>
															<th><?php echo $t["Hentekode"][$lang]; ?>:</th>
															<td><?php echo $package["pickupCode"]; ?></td>
														</tr>
													<?php } ?>
												</table>
												<div class="accordion-group">
													<div class="accordion-heading">
														<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseDetails<?php echo $package["packageNumber"]; ?>"> <?php echo $t["Detaljer"][$lang]; ?> </a>
													</div>
													<div id="collapseDetails<?php echo $package["packageNumber"]; ?>" class="accordion-body collapse">
														<table class="table">
															<tr>
																<th><?php echo $t["Sendings nummer"][$lang]; ?>:</th>
																<td><?php echo $shipment["consignmentId"]; ?></td>
															</tr>
															<tr>
																<th><?php echo $t["Pakkenummer"][$lang]; ?>:</th>
																<td><?php echo $package["packageNumber"]; ?></td>
															</tr>
															<tr>
																<th><?php echo $t["Antall pakker"][$lang]; ?>:</th>
																<td><?php echo sizeof($shipment["packageSet"]); ?></td>
															</tr>
															<tr>
																<th><?php echo $t["Pakketype"][$lang]; ?>:</th>
																<td><?php echo $package["productName"]; ?></td>
															</tr>
															<tr>
																<th><?php echo $t["Vekt"][$lang]; ?>:</th>
																<td><?php echo $package["weightInKgs"]; ?></td>
															</tr>
															<tr>
																<th><?php echo $t["Avsender"][$lang]; ?>:</th>
																<td><?php echo $package["senderName"]; ?></td>
															</tr>
															<tr>
																<th><?php echo $t["Mottaker"][$lang]; ?>:</th>
																<td><?php
																	if (!empty($package["recipientAddress"]["addressLine1"]))
																		echo $package["recipientAddress"]["addressLine1"] . "<br>";
																	if (!empty($package["recipientAddress"]["addressLine2"]))
																		echo $package["recipientAddress"]["addressLine2"] . "<br>";
																	if (!empty($package["recipientAddress"]["postalCode"]))
																		echo $package["recipientAddress"]["postalCode"];
																	if (!empty($package["recipientAddress"]["postalCode"]) && !empty($package["recipientAddress"]["city"]))
																		echo " ";
																	if (!empty($package["recipientAddress"]["city"]))
																		echo $package["recipientAddress"]["city"] . "<br>";
																	if (!empty($package["countryCode"]))
																		echo $package["countryCode"];
																	if (!empty($package["recipientAddress"]["countryCode"]) && !empty($package["recipientAddress"]["country"]))
																		echo " ";
																	if (!empty($package["country"]))
																		echo $package["country"];
																?></td>
															</tr>
														</table>
													</div>
												</div>
												<div class="accordion-group">
													<div class="accordion-heading">
														<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseShipping<?php echo $package["packageNumber"]; ?>"> <?php echo $t["Bevegelser"][$lang]; ?> </a>
													</div>
													<div id="collapseShipping<?php echo $package["packageNumber"]; ?>" class="accordion-body collapse in">
														<table class="table-striped">
															<tr>
																<th><?php echo $t["Hendelse"][$lang]; ?></th>
																<th><?php echo $t["Tid"][$lang]; ?></th>
																<th><?php echo $t["Sted"][$lang]; ?></th>
															</tr>
															<?php
															foreach ($package["eventSet"] as $event) {
																?>
																<tr>
																	<td><?php echo $event["description"]; ?></td>
																	<td><?php echo date($t["tidsformat"][$lang], strtotime($event["dateIso"])); ?></td>
																	<td><?php
																		$noloc = empty($event["city"]) || empty($event["country"]);
																		echo $event["city"] . ($noloc ? "" : ", ") . $event["country"];
																	?></td>
																</tr>
															<?php } ?>
														</table>
													</div>
												</div>
											</div>
											<div class="span6">
												<ul class="nav nav-tabs" style="margin-bottom: 0px;" id="codes<?php echo $package["packageNumber"]; ?>">
												  <li class="active"><a data-toggle="tab" href="#barcode<?php echo $package["packageNumber"]; ?>"><?php echo $t["Strekkode"][$lang]; ?></a></li>
												  <li><a data-toggle="tab" href="#qr<?php echo $package["packageNumber"]; ?>"><?php echo $t["Sporings QR"][$lang]; ?></a></li>
												  <li><a data-toggle="tab" href="#fullqr<?php echo $package["packageNumber"]; ?>"><?php echo $t["Bokmerke QR"][$lang]; ?></a></li>
												</ul>
												<div class="tab-content">
												  <div class="tab-pane active" style="text-align: center; height: 150px;" id="barcode<?php echo $package["packageNumber"]; ?>">
												  	<img style="border:0px solid black; height: 100px; width: 330px" alt="<?php echo $t["Strekkode for "][$lang]; ?><?php echo $package["packageNumber"]; ?> " src="<?php echo getBarcode($package["packageNumber"]); ?>" />
												  </div>
												  <div class="tab-pane" style="text-align: center; height: 150px;" id="qr<?php echo $package["packageNumber"]; ?>">
												  	<img style="border:0px solid black;" alt="<?php echo $t["QRkode for "][$lang]; ?><?php echo $package["packageNumber"]; ?> " src="<?php echo getQRCode($package["packageNumber"]); ?>" />
												  </div>
												  <div class="tab-pane" style="text-align: center; height: 150px;" id="fullqr<?php echo $package["packageNumber"]; ?>">
												  	<img style="border:0px solid black;" alt="<?php echo $t["QRkode for "][$lang]; ?><?php echo $package["packageNumber"]; ?> " src="<?php echo getFullQRCode($package["packageNumber"]); ?>" />
												  </div>
												</div>
												<script>
													$(function () {
														$('#codes<?php echo $package["packageNumber"]; ?>
														a:last').tab('show');
													})
												</script>
											</div>
										</div>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
			<div style="text-align: center; margin-top: 30px;" class="noPrint">
				<p>
					<?php echo $t["Laget av "][$lang]; ?><a href="http://www.sjefen6.no" target="_blank">@sjefen6</a>
					<a href="https://twitter.com/sjefen6" class="twitter-follow-button" data-show-count="false" data-show-screen-name="false" data-lang="<?php echo $lang; ?>"><?php echo $t["F&oslash;lg"][$lang]; ?></a><br>
					<div class="fb-like" data-href="http://www.pakkespor.no" data-width="75" data-layout="button_count" data-show-faces="false" data-send="false"></div>
					<div style="display: inline-block; width: 60px; height: 25px;">
						<div class="g-plusone" data-size="medium"></div>
					</div>
					<div style="display: inline-block; width: 83px; height: 25px;">
						<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.pakkespor.no" data-text="<?php echo $t["twitter melding"][$lang]; ?>" data-lang="<?php echo $lang; ?>">Tweet</a>
					</div><br>
					<?php echo $t["Sporingsdata levert av "][$lang]; ?> <a href="http://www.posten.no" targer="_blank">posten.no</a>/<a href="http://www.bring.com" targer="_blank">bring.com</a><br>
					<?php echo $t["Kildekode tilgjengelig under "][$lang]; ?> <a href="/LICENSE" targer="_blank">GPL v3.0</a> @ <a href="https://github.com/sjefen6/pakkespor/" target="_blank">github</a> - <a href="/README.md" targer="_blank">readme</a>
					
				</p>
			</div>
		</div><!-- /container -->
		<script src="http://code.jquery.com/jquery-latest.min.js"></script>
		<script src="/bs/js/bootstrap.min.js"></script>
		<script>
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
		
		  ga('create', 'UA-2122863-17', 'pakkespor.no');
		  ga('send', 'pageview');
		
		</script>
		<script type="text/javascript">
			<!--
			google_ad_client ="ca-pub-4079891243190921";
			/* tracking */
			google_ad_slot = "5329852473";
			google_ad_width = 468;
			google_ad_height = 60;
			//-->
		</script>
		<script type="text/javascript"
		src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
		<script>
			! function(d, s, id) {
				var js, fjs = d.getElementsByTagName(s)[0], p = /^http:/.test(d.location) ? 'http' : 'https';
				if (!d.getElementById(id)) {
					js = d.createElement(s);
					js.id = id;
					js.src = p + '://platform.twitter.com/widgets.js';
					fjs.parentNode.insertBefore(js, fjs);
				}
			}(document, 'script', 'twitter-wjs'); 
		</script>
		<div id="fb-root"></div>
		<script>(function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0];
		  if (d.getElementById(id)) return;
		  js = d.createElement(s); js.id = id;
		  js.src = "//connect.facebook.net/nb_NO/all.js#xfbml=1&appId=159452564099998";
		  fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));</script>
		<script type="text/javascript">
		  window.___gcfg = {lang: '<?php echo $lang; ?>'};
		
		  (function() {
		    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
		    po.src = 'https://apis.google.com/js/plusone.js';
		    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
		  })();
		</script>
	</body>
</html>

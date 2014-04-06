<?php
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set("Europe/Oslo");

require 'lang.php';
$lang = "no";

$cookielifetime = 1209600;

$trackingNumbers = array();
$trackingNumbers_json = array();

$autorefresh = htmlspecialchars(@$_COOKIE["autorefresh"]);

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
	return ($https ? "https://" : "http://") . $_SERVER['SERVER_NAME'] . "/?json=" . $data;
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

if($_SERVER["SERVER_NAME"] != "www.pakkespor.no")
	header("Location: http://www.pakkespor.no/?json=" . urlencode(base64_encode(json_encode($trackingNumbers_json))), TRUE, 307);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
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
			}

			.shipment {
				min-width: 380px;
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

				.shipment  {
					page-break-after: always;
					page-break-inside: avoid;
				}

				.shipment:last-child {
					page-break-after: avoid;
				}
			}

			.footerad{
				width:468px;
				height:60px;
			}

			@media (max-width:480px) {
				.footerad {
					width: 320px;
					height: 50px;
					margin-left: -20px;
					margin-right: -20px;
				}
				body, .package {
					padding-right: 0px;
					padding-left: 0px;
					border-radius: 0px;
				}
			}
		</style>
	</head>
	<body>
		<div class="container"> <!-- container -->
			<div class="navbar navbar-static noPrint"> <!-- Menu -->
				<div class="plz-center" style="margin: 0 auto; text-align: center; width: 433px;">
					<form class="navbar-search" method="get">
						<div class="input-append">
							<input id="trackingNumbers" name="trackingNumbers" class="input" type="text" placeholder="<?php echo $t["Sporingsnummer"][$lang]; ?>">
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
							<button class="btn dropdown-toggle" data-toggle="dropdown"><i class="icon-info-sign"></i></button>
							<ul class="dropdown-menu pull-right" role="menu">
								<li><?php echo $t["infotekst"][$lang]; ?></li>
							</ul>
						</li>
						<li class="dropdown">
							<button class="btn dropdown-toggle" data-toggle="dropdown"><i class="icon-qrcode"></i></button>
							<ul class="dropdown-menu pull-right" role="menu">
								<li>
									<a href="<?php
										echo getFullURL(urlencode(base64_encode(json_encode($trackingNumbers_json))));
										?>" style="height:150px; margin:0px; padding: 0px; background-image:url('<?php
										echo getFullQRCode(urlencode(base64_encode(json_encode($trackingNumbers_json))));
										?>');"></a>
								</li>
							</ul>
						</li>
					</ul>
				</div>
			</div> <!-- /Menu --><?php
			foreach ($trackingNumbers_json as $package_object) {
				$shipments = getTrackingInfo($package_object["trackingnumber"]);
				// Foreach shipment (usually only 1)
				foreach ($shipments["consignmentSet"] as $shipment){ ?>

					<div class="row-fluid shipment"> <!-- row -->
						<div class="span12 shipment"> <!-- shipment -->
							<span><?php
								echo $t["Sending"][$lang]; ?>: <?php
								echo (!empty($shipment["consignmentId"]) ? $shipment["consignmentId"] : $package_object["trackingnumber"]);
								echo (!empty($package_object["name"]) ? " - " . htmlspecialchars($package_object["name"]) : "");
							?></span>
							<a href="/?remove=<?php echo $package_object["trackingnumber"]; ?>" class="close noPrint" aria-hidden="true">&times;</a>
							<?php
							if(!empty($shipment["error"])){
								?>
								<div class="row-fluid">
									<div class="alert span12 alert-error">
										<p><?php echo $t["Feils&oslash;kings info"][$lang]; ?>:</p>
										<pre>Input: <?php 
											echo $package_object["trackingnumber"] . "\n";
											var_dump($shipment);
										?>
										</pre>
									</div>
								</div>
								<?php
							} else {
								foreach ($shipment["packageSet"] as $package){
									?>
									<div class="row-fluid"> <!-- row -->
										<div class="package span12"> <!-- package -->
											<h1><?php echo $package["packageNumber"]; ?></h1>
											<div class="row-fluid"> <!-- row -->
												<div class="span6"> <!-- left side -->
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
													<div class="accordion-group"> <!-- details -->
														<div class="accordion-heading">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseDetails<?php echo $package_object["trackingnumber"]; ?>"> <?php echo $t["Detaljer"][$lang]; ?> </a>
														</div>
														<div id="collapseDetails<?php echo $package_object["trackingnumber"]; ?>" class="accordion-body collapse">
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
                                                                                                                	        <tr>
                                                                                                                        	        <th><?php echo $t["Sendingsnavn"][$lang]; ?>:</th>
                                                                                                                                	<td style="padding: 0px;">
																		<form class="form-inline" style="margin-bottom: 0px;" method="post">
																			<input id="shipmentName" name="shipmentName" type="text" class="input-small" placeholder="<?php echo $t["Navn"][$lang]; ?>" value="<?php echo htmlspecialchars($package_object["name"]); ?>">
																			<input id="trackingnumberToName" name="trackingnumberToName" type="hidden" value="<?php echo $package_object["trackingnumber"]; ?>" >
																			<button type="submit" class="btn"><?php echo $t["Lagre"][$lang]; ?></button>
																		</form>
																	</td>
                                	                                                                                        </tr>
															</table>
														</div>
													</div> <!-- details -->
													<div class="accordion-group"> <!-- movements -->
														<div class="accordion-heading">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseShipping<?php echo $package_object["trackingnumber"]; ?>"> <?php echo $t["Bevegelser"][$lang]; ?> </a>
														</div>
														<div id="collapseShipping<?php echo $package_object["trackingnumber"]; ?>" class="accordion-body collapse in">
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
													</div> <!-- movements -->
												</div> <!-- /left side -->
												<div class="span6"> <!-- right side -->
													<ul class="nav nav-tabs" style="margin-bottom: 0px;" id="codes<?php echo $package["packageNumber"]; ?>">
														<li class="active"><a data-toggle="tab" href="#barcode<?php echo $package_object["trackingnumber"]; ?>"><?php echo $t["Strekkode"][$lang]; ?></a></li>
														<li><a data-toggle="tab" href="#qr<?php echo $package_object["trackingnumber"]; ?>"><?php echo $t["Sporings QR"][$lang]; ?></a></li>
														<li><a data-toggle="tab" href="#fullqr<?php echo $package_object["trackingnumber"]; ?>"><?php echo $t["Bokmerke QR"][$lang]; ?></a></li>
													</ul>
													<div class="tab-content">
														<div class="tab-pane active" style="text-align: center; height: 150px;" id="barcode<?php echo $package_object["trackingnumber"]; ?>">
															<img style="border:0px solid black; height: 100px; width: 330px" alt="<?php echo $t["Strekkode for "][$lang]; ?><?php echo $package_object["trackingnumber"]; ?> " src="<?php echo getBarcode($package["packageNumber"]); ?>" />
														</div>
														<div class="tab-pane" style="text-align: center; height: 150px;" id="qr<?php echo $package_object["trackingnumber"]; ?>">
												 			<img style="border:0px solid black;" alt="<?php echo $t["QRkode for "][$lang]; echo $package_object["trackingnumber"]; ?> " src="<?php echo getQRCode($package["packageNumber"]); ?>" />
														</div>
														<div class="tab-pane" style="text-align: center; height: 150px;" id="fullqr<?php echo $package_object["trackingnumber"]; ?>">
												 			<img style="border:0px solid black;" alt="<?php
																echo $t["QRkode for "][$lang]; echo $package_object["trackingnumber"];
																?>" src="<?php
																echo getFullQRCode(urlencode(base64_encode(json_encode(array($package_object)))));
																?>" />
														</div>
													</div>
													<script>
														$(function () {
															$('#codes<?php echo $package_object["trackingnumber"]; ?>
															a:last').tab('show');
														})
													</script>
												</div> <!-- /right side -->
											</div> <!-- /row -->
										</div> <!-- /package -->
									</div> <!-- /row -->
								<?php }
							} ?>

						</div> <!-- shipment -->
					</div> <!-- /row -->
				<?php } ?>
			<?php } ?>
			</div><!-- /container -->
		<div style="text-align: center; margin-top: 30px;" class="noPrint"> <!-- footer -->
			<p>
				<?php
				echo $t["Laget av "][$lang];
				?><a href="http://www.sjefen6.no" target="_blank">@sjefen6</a>
				<a href="https://twitter.com/sjefen6" class="twitter-follow-button" data-show-count="false" data-show-screen-name="false" data-lang="<?php
					echo $lang;
					?>"><?php
					echo $t["F&oslash;lg"][$lang];
				?></a>
			</p>
			<div class="fb-like" data-href="http://www.pakkespor.no" data-width="75" data-layout="button_count" data-show-faces="false" data-send="false"></div>
			<div style="display: inline-block; width: 60px; height: 25px;">
				<div class="g-plusone" data-size="medium"></div>
			</div>
			<div style="display: inline-block; width: 83px; height: 25px;">
				<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.pakkespor.no" data-text="<?php
					echo $t["twitter melding"][$lang];
					?>" data-lang="<?php
					echo $lang;
				?>">Tweet</a>
			</div>
			<p>
				<?php echo $t["Sporingsdata levert av "][$lang]; ?><a href="http://www.posten.no" target="_blank">posten.no</a>/<a href="http://www.bring.com" target="_blank">bring.com</a><br>
				<?php echo $t["Kildekode tilgjengelig under "][$lang]; ?><a href="/LICENSE" target="_blank">GPL v3.0</a> @ <a href="https://github.com/sjefen6/pakkespor/" target="_blank">github</a> - <a href="/README.md" target="_blank">readme</a>
			</p>
			<!-- New responsive Adsense -->
			<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
			<!-- tracking -->
			<ins class="adsbygoogle footerad"
			     style="display:inline-block;"
			     data-ad-client="ca-pub-4079891243190921"
			     data-ad-slot="5329852473"></ins>
			<script>
				(adsbygoogle = window.adsbygoogle || []).push({});
			</script>
		</div> <!-- /foooter -->
		<!-- jquery -->
		<script src="http://code.jquery.com/jquery-latest.min.js"></script>
		
		<!-- bootstrap -->
		<script src="/bs/js/bootstrap.min.js"></script>
		
		<!-- Google Analytics -->
		<script>
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
		
		  ga('create', 'UA-2122863-17', 'pakkespor.no');
		  ga('send', 'pageview');
		
		</script>
		
		<!-- twitter -->
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
		
		<!-- facebook -->
		<div id="fb-root"></div>
		<script>(function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0];
		  if (d.getElementById(id)) return;
		  js = d.createElement(s); js.id = id;
		  js.src = "//connect.facebook.net/nb_NO/all.js#xfbml=1&appId=159452564099998";
		  fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));</script>
		
		<!-- Google Plus -->
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

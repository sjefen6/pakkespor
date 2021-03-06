<?php
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set("Europe/Oslo");

require 'config.php';
require 'lang.php';
require 'functions.php';
?>
<!DOCTYPE html>
<html lang="<?=$lang?>">
<head>
	<title><?=$t["Pakkesporing for Posten/Bring"][$lang]?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?=$autorefresh ? "<meta http-equiv=\"refresh\" content=\"600\">" : ""?>
	<!-- Bootstrap -->
	<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.2/css/bootstrap.min.css" >
	<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.2/css/bootstrap-responsive.min.css" >
	<link rel="stylesheet" type="text/css" href="/style.css">
	<link rel="alternate" hreflang="x-default" href="http://<?= URL ?>">
	<meta name="description" content="<?=$t["meta description"][$lang]?>">
	<meta name="flattr:id" content="kxwwvj">
</head>
<body>
	<div class="container"> <!-- container -->
		<div class="navbar navbar-static noPrint"> <!-- Menu -->
			<div class="plz-center" style="margin: 0 auto; text-align: center; width: 433px;">
				<form class="navbar-search" method="get">
					<div class="input-append">
						<input id="trackingNumbers" name="trackingNumbers" class="input" type="text" placeholder="<?=$t["Sporingsnummer"][$lang]?>">
						<button type="submit" class="btn">
							<?=$t["S&oslash;k"][$lang]; ?>
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
							<li><?=$t["infotekst"][$lang]?></li>
						</ul>
					</li>
					<li class="dropdown">
						<button class="btn dropdown-toggle" data-toggle="dropdown"><i class="icon-qrcode"></i></button>
						<ul class="dropdown-menu pull-right" role="menu">
							<li>
								<a href="<?=getFullURL(urlencode(base64_encode(json_encode($trackingNumbers_json))))?>" style="height:230px; margin:0px; padding: 0px; background-image:url('<?=getFullQRCode(urlencode(base64_encode(json_encode($trackingNumbers_json))))?>');"></a>
							</li>
						</ul>
					</li>
				</ul>
			</div>
		</div>
		<!-- /Menu -->
		<?php
		foreach ($trackingNumbers_json as $package_object) {
			$shipments = getTrackingInfo($package_object["trackingnumber"]);
			// Foreach shipment (usually only 1)
			foreach ($shipments["consignmentSet"] as $shipment) {?>
				<div class="row-fluid shipment"> <!-- row -->
					<div class="span12 shipment"> <!-- shipment -->
						<span>
							<?=$t["Sending"][$lang]?>:
							<?=(!empty($shipment["consignmentId"]) ? $shipment["consignmentId"] : $package_object["trackingnumber"])?>
							<?=(!empty($package_object["name"]) ? " - " . htmlspecialchars($package_object["name"]) : "")?>
						</span>
						<a href="/?remove=<?=$package_object["trackingnumber"]; ?>" class="close noPrint" aria-hidden="true">&times;</a>
						<?php
						if(!empty($shipment["error"])){
							?>
							<div class="row-fluid">
								<div class="alert span12 alert-error">
									<p><?=$t["Feils&oslash;kings info"][$lang]?>:</p>
									<pre>Input: <?=$package_object["trackingnumber"]."\n"; var_dump($shipment)?></pre>
									<a href="http://www.postnord.no/verktoy/transport-og-sporingsverktoy/nordisk-sporing#dynamicloading=true&shipmentid=<?= $package_object["trackingnumber"] ?>" target="_blank" class="btn">Pr&oslash;v postnord.no</a>
								</div>
							</div>
							<?php
						} else {
							foreach ($shipment["packageSet"] as $package){
								?>
								<div class="row-fluid"> <!-- row -->
									<div class="package span12"> <!-- package -->
										<h1><?=$package["packageNumber"]?></h1>
										<div class="row-fluid"> <!-- row -->
											<div class="span6"> <!-- left side -->
												<table class="table">
													<?php
													if(!empty($package["statusDescription"])){
														?>
														<tr>
															<th><?=$t["Status"][$lang]?>:</th>
															<td><?=$package["statusDescription"]?></td>
														</tr>
														<?php
													}
													if(!empty($package["pickupCode"])){
														?>
														<tr>
															<th><?=$t["Hentekode"][$lang]?>:</th>
															<td><?=$package["pickupCode"]?></td>
														</tr>
														<?php
													} ?>
												</table>
												<div class="accordion-group"> <!-- details -->
													<div class="accordion-heading">
														<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseDetails<?=$package["packageNumber"]?>"> <?=$t["Detaljer"][$lang]?></a>
													</div>
													<div id="collapseDetails<?=$package["packageNumber"]?>" class="accordion-body collapse">
														<table class="table">
															<tr>
																<th><?=$t["Sendings nummer"][$lang]?>:</th>
																<td><?=$shipment["consignmentId"]?></td>
															</tr>
															<tr>
																<th><?=$t["Pakkenummer"][$lang]?>:</th>
																<td><?=$package["packageNumber"]?></td>
															</tr>
															<tr>
																<th><?=$t["Antall pakker"][$lang]?>:</th>
																<td><?=sizeof($shipment["packageSet"])?></td>
															</tr>
															<tr>
																<th><?=$t["Pakketype"][$lang]?>:</th>
																<td><?=$package["productName"]?></td>
															</tr>
															<tr>
																<th><?=$t["Vekt"][$lang]?>:</th>
																<td><?=$package["weightInKgs"]?></td>
															</tr>
															<tr>
																<th><?=$t["Avsender"][$lang]?>:</th>
																<td><?=$package["senderName"]?></td>
															</tr>
															<tr>
																<th><?=$t["Mottaker"][$lang]?>:</th>
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
																<th><?=$t["Sendingsnavn"][$lang]?>:</th>
																<td style="padding: 0px;">
																	<form class="form-inline" style="margin-bottom: 0px;" method="post">
																		<input class="shipmentName input-small" name="shipmentName" type="text" placeholder="<?=$t["Navn"][$lang]?>" value="<?=htmlspecialchars($package_object["name"])?>">
																		<input class="trackingnumberToName" name="trackingnumberToName" type="hidden" value="<?=$package_object["trackingnumber"]?>" >
																		<button type="submit" class="btn"><?=$t["Lagre"][$lang]?></button>
																	</form>
																</td>
															</tr>
														</table>
													</div>
												</div> <!-- details -->
												<div class="accordion-group"> <!-- movements -->
													<div class="accordion-heading">
														<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseShipping<?=$package["packageNumber"]?>"> <?=$t["Bevegelser"][$lang]?> </a>
													</div>
													<div id="collapseShipping<?=$package["packageNumber"]?>" class="accordion-body collapse in">
														<table class="table-striped">
															<tr>
																<th><?=$t["Hendelse"][$lang]?></th>
																<th><?=$t["Tid"][$lang]?></th>
																<th><?=$t["Sted"][$lang]?></th>
															</tr>
															<?php
															foreach ($package["eventSet"] as $event) {
																?>
																<tr>
																	<td><?=$event["description"]?></td>
																	<td><?=date($t["tidsformat"][$lang], strtotime($event["dateIso"]))?></td>
																	<td><?php
																	$noloc = empty($event["city"]) || empty($event["country"]);
																	echo $event["city"] . ($noloc ? "" : ", ") . $event["country"];
																	?></td>
																</tr>
																<?php
															} ?>
														</table>
													</div>
												</div> <!-- movements -->
											</div> <!-- /left side -->
											<div class="span6"> <!-- right side -->
												<ul class="nav nav-tabs" style="margin-bottom: 0px;" id="codes<?=$package["packageNumber"]?>">
													<li class="active"><a data-toggle="tab" href="#barcode<?=$package["packageNumber"]?>"><?=$t["Strekkode"][$lang]?></a></li>
													<li><a data-toggle="tab" href="#qr<?=$package["packageNumber"]?>"><?=$t["Sporings QR"][$lang]?></a></li>
													<li><a data-toggle="tab" href="#fullqr<?=$package["packageNumber"]?>"><?=$t["Bokmerke QR"][$lang]?></a></li>
												</ul>
												<div class="tab-content">
													<div class="tab-pane active" style="text-align: center; height: 230px;" id="barcode<?=$package["packageNumber"]?>">
														<img style="border:0px solid black; height: 100px; width: 330px" alt="<?=$t["Strekkode for "][$lang]?><?=$package["packageNumber"]?> " src="<?=getBarcode($package["packageNumber"])?>" />
													</div>
													<div class="tab-pane" style="text-align: center; height: 230px;" id="qr<?=$package["packageNumber"]?>">
														<img style="border:0px solid black;" alt="<?=$t["QRkode for "][$lang]?> <?=$package["packageNumber"]?> " src="<?=getQRCode($package["packageNumber"])?>" />
													</div>
													<div class="tab-pane" style="text-align: center; height: 230px;" id="fullqr<?=$package["packageNumber"]?>">
														<img style="border:0px solid black;" alt="<?=$t["QRkode for "][$lang]?><?=$package["packageNumber"]?>" src="<?=getFullQRCode(urlencode(base64_encode(json_encode(array($package_object)))))?>" />
													</div>
												</div>
												<script>
												$(function () {
													$('#codes<?=$package["packageNumber"]?>
													a:last').tab('show');
												})
												</script>
											</div> <!-- /right side -->
										</div> <!-- /row -->
									</div> <!-- /package -->
								</div> <!-- /row -->
								<?php
							}
						} ?>
					</div> <!-- shipment -->
				</div> <!-- /row -->
				<?php
			}
		} ?>
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
		<div class="fb-like" data-href="http://<?= URL ?>" data-width="75" data-layout="button_count" data-show-faces="false" data-send="false"></div>
		<div style="display: inline-block; width: 60px; height: 25px;">
			<div class="g-plusone" data-size="medium"></div>
		</div>
		<div style="display: inline-block; width: 83px; height: 25px;">
			<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://<?= URL ?>" data-text="<?php
			echo $t["twitter melding"][$lang];
			?>" data-lang="<?php
			echo $lang;
			?>">Tweet</a>
		</div>
		<p>
			<?=$t["Sporingsdata levert av "][$lang]?><a href="http://www.posten.no" target="_blank">posten.no</a>/<a href="http://www.bring.com" target="_blank">bring.com</a><br>
			<?=$t["Kildekode tilgjengelig under "][$lang]?><a href="/LICENSE" target="_blank">GPL v3.0</a> @ <a href="https://github.com/sjefen6/pakkespor/" target="_blank">github</a> - <a href="/README.md" target="_blank">readme</a>
		</p>
		<!-- New responsive Adsense -->
		<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
		<!-- tracking -->
		<ins class="adsbygoogle footerad"
		style="display:inline-block;"
		data-ad-client="<?= GAS_AD_CLIENT ?>"
		data-ad-slot="<?= GAS_AD_SLOT ?>"></ins>
		<script>
		(adsbygoogle = window.adsbygoogle || []).push({});
		</script>
	</div> <!-- /foooter -->
	<!-- jquery -->
	<script src="//code.jquery.com/jquery-latest.min.js"></script>

	<!-- bootstrap -->
	<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>

	<!-- Google Analytics -->
	<script>
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	ga('create', '<?= GAN_UA ?>', '<?= GAN_NAME ?>');
	ga('require', 'displayfeatures');
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
	}(document, 'script', 'facebook-jssdk'));
	</script>

	<!-- Google Plus -->
	<script type="text/javascript">
	window.___gcfg = {lang: '<?=$lang?>'};

	(function() {
		var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
		po.src = 'https://apis.google.com/js/plusone.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
	})();
	</script>
</body>
</html>

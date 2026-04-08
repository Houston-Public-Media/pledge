<?php
require_once( '../global.php' );
global $logged_in, $db;
if ( !$logged_in ) {
	header( "HTTP/1.1 403 Unauthorized" );
	header( 'Location: /' );
} ?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,initial-scale=1">
		<title>HPM Pledge Uploader</title>
		<link rel="icon" sizes="48x48" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/icon-48.png">
		<link rel="icon" sizes="96x96" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/icon-96.png">
		<link rel="icon" sizes="144x144" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/icon-144.png">
		<link rel="icon" sizes="192x192" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/icon-192.png">
		<link rel="icon" sizes="256x256" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/icon-256.png">
		<link rel="icon" sizes="384x384" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/icon-384.png">
		<link rel="icon" sizes="512x512" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/icon-512.png">
		<link rel="apple-touch-icon" sizes="57x57" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/apple-touch-icon-57.png">
		<link rel="apple-touch-icon" sizes="60x60" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/apple-touch-icon-60.png">
		<link rel="apple-touch-icon" sizes="72x72" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/apple-touch-icon-72.png">
		<link rel="apple-touch-icon" sizes="76x76" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/apple-touch-icon-76.png">
		<link rel="apple-touch-icon" sizes="114x114" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/apple-touch-icon-114.png">
		<link rel="apple-touch-icon" sizes="120x120" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/apple-touch-icon-120.png">
		<link rel="apple-touch-icon" sizes="152x152" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/apple-touch-icon-152.png">
		<link rel="apple-touch-icon" sizes="167x167" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/apple-touch-icon-167.png">
		<link rel="apple-touch-icon" sizes="180x180" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/apple-touch-icon-180.png">
		<link rel="mask-icon" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/safari-pinned-tab.svg" color="#ff0000">
		<meta name="msapplication-config" content="https://cdn.houstonpublicmedia.org/assets/images/favicon/config.xml" />
		<link rel="manifest" href="https://cdn.houstonpublicmedia.org/assets/images/favicon/manifest.json" />
		<link rel="stylesheet" href="assets/styles.css" />
		<link href="https://app.hpm.io/js/dropzone/css/dropzone.css" rel="stylesheet" />
	</head>
	<body>
		<div class="container">
			<header>
				<h1>Pledge Transaction Uploader</h1>
				<div></div>
				<a href="/" class="upload-link">Return to Dashboard</a>
			</header>
			<div id="inputforms">
				<form action="ingest.php" class="dropzone" id="comp"></form>
			</div>
			<div class="instructions">
				<div>
					<h4>Uploading Instructions</h4>
					<p>Please drag and drop your files into the area above, or click the area above for a file picker.  You can upload as many files as you like, but there is a 100MB limit per uploaded file.</p>
					<h5>Accepted File Formats</h5>
					<ul>
						<li>CSV: .csv</li>
					</ul>
				</div>
				<div>
					<h4>Results</h4>
					<div id="output"></div>
				</div>
			</div>
		</div>
		<script src="https://app.hpm.io/js/dropzone/dropzone.min.js"></script>
		<script>
			let output = document.querySelector("#output");
			Dropzone.options.comp = {
				paramName: "comp",
				acceptedFiles: ".xlsx,.csv",
				maxFilesize: 100,
				init: function() {
					this.on("sending", function(file, xhr, formData) {
						formData.append( "type", file.type );
					});
					this.on("error", function(file, errorMessage) {
						alert( errorMessage );
					});
					this.on("success", function(file, responseText) {
						Array.from(responseText.result).forEach(function(resp) {
							output.innerHTML += '<p>'+resp+'</p>';
							console.log(resp);
						});
					});
				}
			};
		</script>
	</body>
</html>

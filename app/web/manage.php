<?php
require_once( '../global.php' );
global $logged_in, $db;
$_SESSION['nonce'] = hash('sha256', NONCE_TEXT . microtime() );
if ( !$logged_in ) {
	header( "HTTP/1.1 403 Unauthorized" );
	header( 'Location: https://pledge.hpm.io/' );
	die;
}
$pledge_exclude = $nonpledge_exclude = [];
$result = $db->query( "SELECT * FROM exclusions" );
while ( $row = $result->fetchArray( SQLITE3_ASSOC ) ) {
	if ( $row['mode'] === 'pledge' ) {
		$pledge_exclude[] = $row['form'];
	} else {
		$nonpledge_exclude[] = $row['form'];
	}
}
?>
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
				<h1>Pledge Transaction Management</h1>
				<div></div>
				<a href="/" class="upload-link">Return to Dashboard</a>
			</header>
			<div id="instructions">
				<div id="uploads">
					<h4>Uploads</h4>
					<div id="inputforms">
						<form action="ingest.php" class="dropzone" id="comp"></form>
					</div>
					<div id="mode-select-container">
						<label for="mode-select">Select Your Mode:</label>
						<select id="mode-select" name="mode-select">
							<option value="pledge"<?php echo ( $_SESSION['user']['mode'] === 'pledge' ? ' selected' : '' ); ?>>Pledge</option>
							<option value="nonpledge"<?php echo ( $_SESSION['user']['mode'] === 'nonpledge' ? ' selected' : '' ); ?>>Non-Pledge</option>
						</select>
						<button type="button" onclick="saveMode()">Update Mode</button>
					</div>
					<h4>Instructions</h4>
					<p>To upload, please drag and drop your files into the area above, or click for a file picker. You can upload as many files as you like, but there is a 100MB limit per uploaded file.</p>
					<p>You can also select what mode you would like to use when uploading transactions (either "Pledge" or "Non-Pledge"). This will determine what forms are excluded when processing transactions. You can modify those exclusions below.</p>
					<p>Accepted File Format: <strong>CSV (.csv)</strong></p>
				</div>
				<div id="danger-zone">
					<h4>Deletions</h4>
					<p>Use with caution. This section allows you to delete uploaded transactions.</p>
					<div id="buttons">
						<div>
							<button type="button" onclick="sendDelete('cww')">Delete CWW</button>
						</div>
						<div>
							<button type="button" onclick="sendDelete('sb')">Delete Springboard</button>
						</div>
						<div>
							<button type="button" onclick="sendDelete('all')">The Nuclear Option</button>
						</div>
						<div>
							<label for="delete-id" class="screen-reader-text">Delete By ID</label>
							<input type="number" id="delete-id" name="delete-id" />
							<button type="button" onclick="sendDelete('id')">Delete By ID</button>
						</div>
						<div>
							<label for="delete-date" class="screen-reader-text">Delete By Date</label>
							<input type="date" id="delete-date" name="delete-date" />
							<button type="button" onclick="sendDelete('date')">Delete By Day</button>
						</div>
					</div>
				</div>
				<div>
					<h4>Results</h4>
					<div id="output"></div>
				</div>
			</div>
			<div id="exclude-span">
				<h4>Form Exclusions</h4>
				<p>Enter all of the names of forms you want to exclude when processing transactions. Separate each form name with a new line.</p>
				<div id="exclusions">
					<div>
						<h4><label for="pledge-exclusions">Pledge Exclusions</h4>
						<textarea id="pledge-exclusions"><?php echo implode( "\n", $pledge_exclude ); ?></textarea>
					</div>
					<div>
						<h4><label for="non-pledge-exclusions">Non-Pledge Exclusions</h4>
						<textarea id="non-pledge-exclusions"><?php echo implode( "\n", $nonpledge_exclude ); ?></textarea>
						<button type="button" onclick="saveExclude()">Update Exclusions</button>
					</div>
				</div>
			</div>
		</div>
		<script src="https://app.hpm.io/js/dropzone/dropzone.min.js"></script>
		<script>
			const output = document.querySelector("#output");
			const sendDelete = (type) => {
				let extra = '';
				if ( type === 'id' ) {
					extra = document.querySelector('#delete-id').value;
				} else if ( type === 'date' ) {
					 extra = document.querySelector('#delete-date').value;
				}
				if (window.confirm('Are you sure you want to do this? Like, for real?')) {
					fetch('delete.php', {
						method: 'POST',
						body: JSON.stringify({
							type: type,
							nonce: '<?php echo $_SESSION['nonce']; ?>',
							extra: extra
						})
					})
					.then(response => response.json())
					.then(data => {
						output.innerHTML += '<p>'+data.result+'</p>';
						console.log(data.result);
					});
				} else {
					return false;
				}
			};
			const saveExclude = () => {
				const pledge = document.querySelector('textarea#pledge-exclusions').value;
				const nonPledge = document.querySelector('textarea#non-pledge-exclusions').value;
				fetch('exclude.php', {
					method: 'POST',
					body: JSON.stringify({
						nonce: '<?php echo $_SESSION['nonce']; ?>',
						pledge: pledge,
						nonPledge: nonPledge
					})
				})
				.then(response => response.json())
				.then(data => {
					output.innerHTML += '<p>'+data.result+'</p>';
					console.log(data.result);
				});
			};
			const saveMode = () => {
				const mode = document.querySelector('#mode-select').value;
				fetch('mode.php', {
					method: 'POST',
					body: JSON.stringify({
						mode: mode,
						nonce: '<?php echo $_SESSION['nonce']; ?>'
					})
				})
				.then(response => response.json())
				.then(data => {
					output.innerHTML += '<p>'+data.result+'</p>';
					console.log(data.result);
				});
			};
			Dropzone.options.comp = {
				paramName: "comp",
				acceptedFiles: ".csv",
				maxFilesize: 100,
				init: function() {
					this.on("sending", function(file, xhr, formData) {
						formData.append( "type", file.type );
						formData.append( "nonce", '<?php echo $_SESSION['nonce']; ?>' );
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

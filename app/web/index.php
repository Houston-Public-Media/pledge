<?php
require_once( '../global.php' );
global $logged_in, $db;
$end = time();
$start = $end - ( 60 * 60 * 24 * 7 );
$start_get = date( 'Y-m-d\TH:i', $start );
$end_get = date( 'Y-m-d\TH:i', $end );
if ( !empty( $_GET['start'] ) && preg_match( '/[0-9\-T:]+/', $_GET['start'] ) ) {
	$start_get = $_GET['start'];
	$start = strtotime( $start_get );
}
if ( !empty( $_GET['end'] ) && preg_match( '/[0-9\-T:]+/', $_GET['end'] ) ) {
	$end_get = $_GET['end'];
	$end = strtotime( $end_get );
}

$result = $db->query( "SELECT * FROM transactions WHERE date < " . $end . " AND date > " . $start . " ORDER BY date ASC" );
$daily = $dow = $hourly = $transactions = [];
$overall = $overall_extra = 0.00;
$day_of_week = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];
while ( $row = $result->fetchArray( SQLITE3_ASSOC ) ) {
	$id = $row['id'];
	unset( $row['id'] );
	$transactions[ $id ] = $row;
	$dt = getdate( $row['date'] );
	$day = mktime( 0, 0, 0, $dt['mon'], $dt['mday'], $dt['year'] );
	if ( empty( $daily[ $day ] ) ) {
		$daily[ $day ] = [
			'hours' => [],
			'total' => 0.0,
			'total_extra' => 0.0,
			'cww' => 0,
			'sb' => 0
		];
	}
	if ( empty( $daily[ $day ]['hours'][ $dt['hours'] ] ) ) {
		$daily[ $day ]['hours'][ $dt['hours'] ] = [
			'transactions' => [],
			'total' => 0.0,
			'total_extra' => 0.0,
			'cww' => 0,
			'sb' => 0
		];
	}
	if ( empty( $dow[ $dt['wday'] ] ) ) {
		$dow[ $dt['wday'] ] = [
			'hours' => [],
			'total' => 0.00,
			'total_extra' => 0.00,
			'cww' => 0,
			'sb' => 0
		];
	}
	if ( empty( $dow[ $dt['wday'] ]['hours'][ $dt['hours'] ] ) ) {
		$dow[ $dt['wday'] ]['hours'][ $dt['hours'] ] = [
			'transactions' => [],
			'total' => 0.00,
			'total_extra' => 0.00,
			'cww' => 0,
			'sb' => 0
		];
	}
	if ( empty( $hourly[ $dt['hours'] ] ) ) {
		$hourly[ $dt['hours'] ] = [];
	}
	if ( empty( $hourly[ $dt['hours'] ] ) ) {
		$hourly[ $dt['hours'] ] = [
			'transactions' => [],
			'total' => 0.00,
			'total_extra' => 0.00,
			'cww' => 0,
			'sb' => 0
		];
	}
	$daily[ $day ]['hours'][ $dt['hours'] ]['transactions'][] = $id;
	$daily[ $day ]['hours'][ $dt['hours'] ]['total'] += $row['amount'];
	$daily[ $day ]['hours'][ $dt['hours'] ]['total_extra'] += $row['amount_full'];
	$daily[ $day ]['transactions'][] = $id;
	$daily[ $day ]['total'] += $row['amount'];
	$daily[ $day ]['total_extra'] += $row['amount_full'];
	$dow[ $dt['wday'] ]['hours'][ $dt['hours'] ]['transactions'][] = $id;
	$dow[ $dt['wday'] ]['hours'][ $dt['hours'] ]['total'] += $row['amount'];
	$dow[ $dt['wday'] ]['hours'][ $dt['hours'] ]['total_extra'] += $row['amount_full'];
	$dow[ $dt['wday'] ]['transactions'][] = $id;
	$dow[ $dt['wday'] ]['total'] += $row['amount'];
	$dow[ $dt['wday'] ]['total_extra'] += $row['amount_full'];
	$hourly[ $dt['hours'] ]['transactions'][] = $id;
	$hourly[ $dt['hours'] ]['total'] += $row['amount'];
	$hourly[ $dt['hours'] ]['total_extra'] += $row['amount_full'];
	if ( $row['service'] === 'cww' ) {
		$daily[ $day ]['hours'][ $dt['hours'] ]['cww']++;
		$daily[ $day ]['cww']++;
		$dow[ $dt['wday'] ]['hours'][ $dt['hours'] ]['cww']++;
		$dow[ $dt['wday'] ]['cww']++;
		$hourly[ $dt['hours'] ]['cww']++;
	} elseif ( $row['service'] === 'sb' ) {
		$daily[ $day ]['hours'][ $dt['hours'] ]['sb']++;
		$daily[ $day ]['sb']++;
		$dow[ $dt['wday'] ]['hours'][ $dt['hours'] ]['sb']++;
		$dow[ $dt['wday'] ]['sb']++;
		$hourly[ $dt['hours'] ]['sb']++;
	}
	$overall += $row['amount'];
	$overall_extra += $row['amount_full'];
}
$daily = array_reverse( $daily, true );
ksort( $hourly );
ksort( $dow );
$last_id = $last_date = 0;
if ( !empty( $transactions ) ) {
	end( $transactions );
	$last_id = key( $transactions );
	$last_date = $transactions[ $last_id ]['date'];
} ?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,initial-scale=1">
		<title>HPM Pledge Dashboard</title>
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
	</head>
	<body>
		<div class="container">
<?php if ( $logged_in ) { ?>
			<header>
				<h1>Breakdown for <?php echo date( 'F j, Y', $start ) . " to " . date( 'F j, Y', $end ); ?></h1>
				<div>
					<a href="/?start=<?php echo $start_get; ?>&end=<?php echo $end_get; ?>" id="refreshPage" class="hidden"><span class="dot"></span> <span>New Data Available</span></a>
				</div>
				<a href="upload.php" class="upload-link">Upload Files</a>
			</header>
			<form id="date-filters">
				<div>
					<label for="start">Start Date</label>
					<input name="start" id="start" type="datetime-local" value="<?php echo $start_get; ?>" />
				</div>
				<div>
					<label for="end">End Date</label>
					<input name="end" id="end" type="datetime-local" value="<?php echo $end_get; ?>" />
				</div>
				<div>
					<input type="submit" value="Submit" />
				</div>
				<p class="overall-total">Report Total: $<?php echo number_format( $overall, 2 ); ?> ($<?php echo number_format( $overall_extra, 2 ); ?> projected)</p>
			</form>
			<div id="tabs">
				<div id="daily" class="active">Daily</div>
				<div id="hourly">By Hour</div>
				<div id="dow">Day of Week</div>
			</div>
			<div id="daily-bdc" class="breakdown-container active">
				<h3>Breakdown by Daily</h3>
			<?php
				$c = 0;
				foreach ( $daily as $day => $v ) {
					$daytime = date( 'F j, Y', $day );
					$day_id = date( 'Y-m-d', $day );
					$total = $v['sb'] + $v['cww'];
					$money = '$' . number_format( $v['total'], 2 );
					$money_plus = '$' . number_format( $v['total_extra'], 2 );
					//$open = ( $c === 0 ? ' open' : '' );
					$open = '';
					$c++;
					echo <<<EOT
				<details class="transaction-details" id="d{$day_id}"{$open}>
					<summary>
						<div class="transaction-summary">
							<div>{$daytime}</div>
							<div>Transactions: <strong>{$total}</strong> ({$v['cww']} CWW, {$v['sb']} Springboard)</div>
							<div>Total: <strong>{$money}</strong> ({$money_plus} projected)</div>
						</div>
					</summary>
					<div class="summary-wrap">
EOT;
					foreach ( $v['hours'] as $hour => $hv ) {
						echo hour_output( $hv, $hour, $transactions, $day_id );
					}
					echo "</div></details>";
				} ?>
			</div>
			<div id="hourly-bdc" class="breakdown-container">
				<h3>Breakdown by Hour of Day</h3>
			<?php
				foreach ( $hourly as $hour => $hv ) {
					echo hour_output( $hv, $hour, $transactions );
				} ?>
			</div>
			<div id="dow-bdc" class="breakdown-container">
				<h3>Breakdown by Day of Week</h3>
			<?php
				$c = 0;
				foreach ( $dow as $day => $v ) {
					$daytime = $day_of_week[ $day ];
					$day_id = $day;
					$total = $v['sb'] + $v['cww'];
					$money = '$' . number_format( $v['total'], 2 );
					$money_plus = '$' . number_format( $v['total_extra'], 2 );
					//$open = ( $c === 0 ? ' open' : '' );
					$open = '';
					$c++;
					echo <<<EOT
				<details class="transaction-details" id="dow{$day_id}"{$open}>
					<summary>
						<div class="transaction-summary">
							<div>{$daytime}</div>
							<div>Transactions: <strong>{$total}</strong> ({$v['cww']} CWW, {$v['sb']} Springboard)</div>
							<div>Total: <strong>{$money}</strong> ({$money_plus} projected)</div>
						</div>
					</summary>
					<div class="summary-wrap">
EOT;
					foreach ( $v['hours'] as $hour => $hv ) {
						echo hour_output( $hv, $hour, $transactions, 'dow-' . $day_id );
					}
					echo "</div></details>";
				} ?>
			</div>
<?php
	} else { ?>
			<header>
				<h1>HPM Pledge Dashboard</h1>
				<div></div>
				<div></div>
			</header>
			<form id="date-filters" role="form" method="post" action="">
				<div>
					<label for="pin" style="color: white;">Password?</label>
					<input type="password" class="form-control" id="pin" placeholder="What's the secret word?" name="pin" />
				</div>
				<div>
					<input type="submit" value="Submit" />
				</div>
				<div></div>
				<div></div>
			</form>
<?php
	} ?>
		</div>
		<script>
			const checkNew = () => {
				fetch("check.php", {
					method: "post",
					headers: {
						'Accept': 'application/json',
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						start: <?php echo $start; ?>,
						end: <?php echo $end; ?>,
						lastTransId: <?php echo $last_id; ?>,
						lastTransDate: <?php echo $last_date; ?>
					})
				})
				.then((response) => response.json())
				.then((data) => {
					if ( data.refreshPage === 'true' ) {
						let refreshPage = document.querySelector("#refreshPage");
						refreshPage.classList.remove('hidden');
					}
				});
			};
			document.addEventListener('DOMContentLoaded', () => {
				const tabs = document.querySelectorAll("#tabs > div");
				const bdc = document.querySelectorAll(".breakdown-container");
				Array.from(tabs).forEach((tab) => {
					tab.addEventListener('click', (e) => {
						Array.from(tabs).forEach((etab) => {
							etab.classList.remove('active');
						});
						Array.from(bdc).forEach((bc) => {
							bc.classList.remove('active');
						});
						e.target.classList.add('active');
						document.querySelector('#' + e.target.id + '-bdc').classList.add('active');
					});
				});
				setInterval('checkNew()', 30000 );
			});
		</script>
	</body>
</html>

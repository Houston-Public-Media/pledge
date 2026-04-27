<?php
date_default_timezone_set( 'America/Chicago' );
session_start();
$db = new SQLite3( __DIR__ . '/transactions.db' );
$env = file_get_contents( __DIR__ . '/.env' );
define( "NONCE_TEXT", $env );
function check_login(): bool {
	global $db, $_SESSION;
	$logged_in = false;
	if ( !empty( $_SESSION['logged_in'] ) ) {
		$logged_in = true;
	} else {
		if ( ! empty( $_POST['pin'] ) ) {
			$users = $db->query( "SELECT * FROM users WHERE id = 1" );
			$user = $users->fetchArray( SQLITE3_ASSOC );
			if ( password_verify( $_POST['pin'], $user['password'] ) ) {
				$_SESSION['logged_in'] = true;
				$_SESSION['user'] = [
					'id' => $user['id'],
					'username' => $user['username'],
					'mode' => $user['mode']
				];
				$logged_in = true;
			}
		}
	}
	return $logged_in;
}
$logged_in = check_login();

function hour_output( $hv, $hour, $transactions, $day_id = '' ): string {
	$output = '';
	if ( empty ( $day_id ) ) {
		$hour_id = 'hourly' . '-' . $hour;
	} else {
		$hour_id = 'dh' . $day_id . '-' . $hour;
	}
	$hour_total = $hv['cww'] + $hv['sb'];
	$hour_money = '$' . number_format( $hv['total_extra'], 2 );
	$hour_display = $hour . "AM";
	if ( $hour == 0 ) {
		$hour_display = "12AM";
	} elseif ( $hour == 12 ) {
		$hour_display = $hour . "PM";
	} elseif ( $hour > 12 ) {
		$hour_display = ( $hour - 12 ) . "PM";
	}
	$output .= <<<EOT
				<details class="transaction-details" id="{$hour_id}">
					<summary>
						<div class="transaction-summary">
							<div>{$hour_display}</div>
							<div>Transactions: <strong>{$hour_total}</strong> ({$hv['cww']} CWW, {$hv['sb']} Springboard)</div>
							<div>Total: <strong>{$hour_money}</strong></div>
						</div>
					</summary>
					<div class="summary-wrap inner">
						<div class="transaction-grid tgrid-header">
							<div>ID</div>
							<div>Date</div>
							<div>Name</div>
							<div>Donation</div>
							<div>Frequency</div>
							<div>Source</div>
						</div>
EOT;
	foreach ( $hv['transactions'] as $tid ) {
		$ct = $transactions[ $tid ];
		$tdate = date( 'F j, Y H:i', $ct['date'] );
		$name = trim( $ct['first_name'] ) . " " . trim( $ct['last_name'] );
		if ( !empty( $ct['email'] ) ) {
			$name = '<a href="mailto:' . $ct['email'] . '" title="Email ' . $name . '">' . $name . '</a>';
		}
		$donation = '$' . number_format( $ct['amount_full'], 2 );
		$frequency = 'One-Time';
		if ( $ct['frequency'] == 1 ) {
			$frequency = 'Monthly';
		}
		$source = ' <strong>' . ( $ct['service'] == 'sb' ? 'Springboard' : 'Calls Without Walls' ) . '</strong>: ' . $ct['source'];
		$output .= <<<EOT
						<div class="transaction-grid">
							<div>{$tid}</div>
							<div>{$tdate}</div>
							<div>{$name}</div>
							<div>{$donation}</div>
							<div>{$frequency}</div>
							<div>{$source}</div>
						</div>
EOT;
	}
	$output .= "</div></details>";
	return $output;
}
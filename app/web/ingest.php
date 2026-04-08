<?php
require_once( '../global.php' );
global $logged_in, $db;
if ( !$logged_in ) {
	header( "HTTP/1.1 403 Unauthorized" );
	header( 'Location: /' );
}
$formats = [ 'csv' ];
if ( !empty( $_FILES ) ) {
	if ( !empty( $_FILES['comp'] ) ) {
		$filename = $_FILES['comp']['name'];
		$tempFile = $_FILES['comp']['tmp_name'];
	} else {
		header( "HTTP/1.1 500 Server Error" );
		echo "ERROR: No file information passed, please try again.";
		die;
	}
	$sb_skip = [
		"explore the best of pbs with houston pbs passport!: (hpmf main passport donation form)",
		"thank you!: (hpmf member esol donation form)",
		"thank you: (hpmf passport add gift email donation form)",
		"renew your membership: (hpmf r2 renewal donation form)",
		"renew your membership: (hpmf lapsed renewal donation form)"
	];
	$path = pathinfo( $filename );
	if ( !empty( $path['extension'] ) ) {
		if ( !in_array( strtolower( $path['extension'] ), $formats ) ) {
			header( "HTTP/1.1 500 Server Error" );
			echo "ERROR: CSV files only, please.";
			die;
		}
	}
	$c = $uniques = 0;
	$service = '';
	if ( ( $handle = fopen( $tempFile, "r" ) ) !== FALSE ) {
		while ( ( $data = fgetcsv( $handle, 0, ',', '"', '\\' ) ) !== false ) {
			if ( $c === 0 ) {
				if ( $data[0] === 'PledgeID' && $data[1] === 'Title' ) {
					$service = 'cww';
				} elseif ( $data[0] === 'Owner' && $data[1] === 'Transaction ID' ) {
					$service = 'sb';
				}
				$c++;
				continue;
			}
			$trans = [
				'id' => 0,
				'date' => 0,
				'first_name' => '',
				'last_name' => '',
				'email' => '',
				'amount' => 0.00,
				'amount_full' => 0.00,
				'service' => $service,
				'source' => '',
				'frequency' => 0
			];
			if ( $service === 'sb' ) {
				if ( in_array( strtolower( trim( $data[16] ) ), $sb_skip ) ) {
					continue;
				}
				$trans['id'] = $data[1];
				$trans['first_name'] = $data[13];
				$trans['last_name'] = $data[14];
				$trans['email'] = $data[15];
				$trans['source'] = $data[16];
				$amount = (float)str_replace( '$', '', $data[5] );
				$trans['amount'] = round( $amount, 2 );
				if ( $data[7] === 'Monthly' ) {
					$trans['frequency'] = 1;
					$trans['amount_full'] = $amount * 12;
				} else {
					$trans['amount_full'] = $amount;
				}
				$date_xp = explode( ' - ', $data[4] );
				$day = explode( '/', $date_xp[0] );
				$time = explode( ':', $date_xp[1] );
				$trans['date'] = mktime( $time[0], $time[1], 0, $day[0], $day[1], $day[2] );
			} elseif ( $service === 'cww' ) {
				if ( $data[68] === 'No' || empty( $data[66] ) ) {
					continue;
				}
				$trans['id'] = $data[0];
				$trans['first_name'] = $data[2];
				$trans['last_name'] = $data[4];
				$trans['email'] = $data[11];
				$trans['source'] = $data[40];
				$amount = round( $data[67], 2 );
				$trans['amount'] = $amount;
				if ( $data[36] === 'Monthly' ) {
					$trans['frequency'] = 1;
					$trans['amount_full'] = $amount * 12;
				} else {
					$trans['amount_full'] = $amount;
				}
				$trans['date'] = strtotime( $data[25] . ' ' . $data[26] );
			}
			$result = $db->query( "SELECT EXISTS(SELECT 1 FROM transactions WHERE id = " . $trans['id'] . ");" );
			if ( $result->fetchArray()[0] === 0 ) {
				$query = "INSERT INTO transactions(id, date, first_name, last_name, email, amount, service, source, frequency, amount_full) VALUES(:id, :date, :first_name, :last_name, :email, :amount, :service, :source, :frequency, :amount_full)";
				$stmt = $db->prepare( $query );
				$stmt->bindValue( ':id', $trans['id'], SQLITE3_INTEGER );
				$stmt->bindValue( ':date', $trans['date'], SQLITE3_INTEGER );
				$stmt->bindValue( ':first_name', $trans['first_name'] );
				$stmt->bindValue( ':last_name', $trans['last_name'] );
				$stmt->bindValue( ':email', $trans['email'] );
				$stmt->bindValue( ':amount', $trans['amount'], SQLITE3_FLOAT );
				$stmt->bindValue( ':service', $trans['service'] );
				$stmt->bindValue( ':source', $trans['source'] );
				$stmt->bindValue( ':frequency', $trans['frequency'], SQLITE3_INTEGER );
				$stmt->bindValue( ':amount_full', $trans['amount_full'], SQLITE3_FLOAT );
				$result = $stmt->execute();
				$uniques++;
			}
		}
	}
	fclose( $handle );
	if ( $uniques === 1 ) {
		$output = '1 transaction added';
	} else {
		$output = $uniques . ' transactions added';
	}
	$result = [
		'result' => [ '<strong>' . $filename . '</strong>: ' . $output ]
	];
	header( 'Content-type: application/json' );
	echo json_encode( $result );
} else {
	header( "HTTP/1.1 500 Server Error" );
	echo "ERROR: No file information passed, please try again.";
	die;
}
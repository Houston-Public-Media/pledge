<?php
require_once( '../global.php' );
global $logged_in, $db;
if ( !$logged_in ) {
	header( "HTTP/1.1 403 Unauthorized" );
	header( 'Location: https://pledge.hpm.io/' );
	die;
}
$data = json_decode( file_get_contents( 'php://input' ), true );
if (
	empty( $data ) ||
	( empty( $data['type'] ) && empty( $data['nonce'] ) )
) {
	header( "HTTP/1.1 500 Server Error" );
	echo "ERROR: No POST variables passed, please try again.";
	die;
}

if ( !is_string( $data['type'] ) || !is_string( $data['nonce'] ) || !is_string( $data['extra'] ) ) {
	header( "HTTP/1.1 500 Server Error" );
	echo "ERROR: POST variables need to be strings, please try again.";
	die;
}

if ( $data['nonce'] !== $_SESSION['nonce'] ) {
	header( "HTTP/1.1 500 Server Error" );
	echo "ERROR: Something doesn't smell right. Refresh the page and try again.";
	die;
}

$message = '';
if ( $data['type'] === 'cww' ) {
	$result = $db->query( "DELETE FROM transactions WHERE service = 'cww';" );
	if ( $result === false ) {
		header( "HTTP/1.1 500 Server Error" );
		echo 'ERROR: Failed to delete Calls Without Walls transactions. Please try again.';
		die;
	}
	$message = 'CWW transactions deleted';
} elseif ( $data['type'] === 'sb' ) {
	$result = $db->query( "DELETE FROM transactions WHERE service = 'sb';" );
	if ( $result === false ) {
		header( "HTTP/1.1 500 Server Error" );
		echo 'ERROR: Failed to delete Springboard transactions. Please try again.';
		die;
	}
	$message = 'Springboard transactions deleted';
} elseif ( $data['type'] === 'all' ) {
	$result = $db->query( "DELETE FROM transactions;" );
	if ( $result === false ) {
		header( "HTTP/1.1 500 Server Error" );
		echo 'ERROR: Failed to delete transactions. Please try again.';
		die;
	}
	$message = 'All transactions deleted';
} elseif ( $data['type'] === 'id' ) {
	$data['extra'] = intval( $data['extra'] );
	$result = $db->query( "DELETE FROM transactions WHERE id = " . $data['extra'] . ";" );
	if ( $result === false ) {
		header( "HTTP/1.1 500 Server Error" );
		echo 'ERROR: Failed to delete transaction. Please try again.';
		die;
	}
	$message = 'Transaction ID ' . $data['extra'] . ' deleted';
} elseif ( $data['type'] === 'date' ) {
	$date = explode( '-', $data['extra'] );
	$start = mktime( 0, 0, 0, $date[1], $date[2], $date[0] );
	$end = $start + 86400;
	$result = $db->query( "DELETE FROM transactions WHERE date BETWEEN " . $start . " AND " . $end . ";" );
	if ( $result === false ) {
		header( "HTTP/1.1 500 Server Error" );
		echo 'ERROR: Failed to delete transactions. Please try again.';
		die;
	}
	$message = 'Transactions from ' . date( 'F j, Y', $start ) . ' deleted';
}
$result = $db->query( "VACUUM;" );

header( 'Content-type: application/json' );
echo json_encode( [ 'result' => $message ] );

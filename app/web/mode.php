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
	( empty( $data['mode'] ) && empty( $data['nonce'] ) )
) {
	header( "HTTP/1.1 500 Server Error" );
	echo "ERROR: No POST variables passed, please try again.";
	die;
}

if ( !is_string( $data['mode'] ) || !is_string( $data['nonce'] ) ) {
	header( "HTTP/1.1 500 Server Error" );
	echo "ERROR: POST variables need to be strings, please try again.";
	die;
}

if ( $data['nonce'] !== $_SESSION['nonce'] ) {
	header( "HTTP/1.1 500 Server Error" );
	echo "ERROR: Something doesn't smell right. Refresh the page and try again.";
	die;
}

$message = 'Mode remains "' . $data['mode'] . '"';
if ( $_SESSION['user']['mode'] !== $data['mode'] ) {
	$query = "UPDATE users SET mode = ':mode' WHERE id = :id";
	$stmt = $db->prepare( $query );
	$stmt->bindValue( ':id', $_SESSION['user']['id'], SQLITE3_INTEGER );
	$stmt->bindValue( ':mode', $data['mode'] );
	$result = $stmt->execute();
	$_SESSION['user']['mode'] = $data['mode'];
	if ( $result === false ) {
		header( "HTTP/1.1 500 Server Error" );
		echo 'ERROR: Error while saving mode. Please try again.';
		die;
	}
	$message = 'Mode set to "' . $data['mode'] . '". ';
}

header( 'Content-type: application/json' );
echo json_encode( [ 'result' => $message ] );

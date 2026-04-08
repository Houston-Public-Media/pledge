<?php
require_once( '../global.php' );
global $logged_in, $db;
if ( !$logged_in ) {
	header( "HTTP/1.1 403 Unauthorized" );
	die;
}
$data = json_decode( file_get_contents( 'php://input' ), true );
if (
	empty( $data ) ||
	( empty( $data['start'] ) && empty( $data['end'] ) && empty( $data['lastTransId'] ) && empty( $data['lastTransDate'] ) )
) {
	header( "HTTP/1.1 500 Server Error" );
	echo "ERROR: No POST variables passed, please try again.";
	die;
}

if ( !is_int( $data['lastTransId'] ) || !is_int( $data['lastTransDate'] ) || !is_int( $data['start'] ) || !is_int( $data['end'] ) ) {
	header( "HTTP/1.1 500 Server Error" );
	echo "ERROR: POST variables need to be integers, please try again.";
	die;
}

$result = $db->query( "SELECT EXISTS(SELECT 1 FROM transactions WHERE date < " . $data['end'] . " AND date > " . $data['lastTransDate'] . " AND id != " . $data['lastTransId'] . " ORDER BY date ASC);" );
header( 'Content-type: application/json' );
$output = [
	'refreshPage' => 'false'
];
if ( $result->fetchArray()[0] !== 0 ) {
	$output['refreshPage'] = 'true';
}
echo json_encode( $output );
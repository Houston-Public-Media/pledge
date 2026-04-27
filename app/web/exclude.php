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
	empty( $data ) && empty( $data['nonce'] )
) {
	header( "HTTP/1.1 500 Server Error" );
	echo "ERROR: No POST variables passed, please try again.";
	die;
}

if ( !is_string( $data['nonce'] ) || !is_string( $data['pledge'] ) || !is_string( $data['nonPledge'] ) ) {
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
$result = $db->query( "DELETE FROM exclusions;" );
if ( $result === false ) {
	header( "HTTP/1.1 500 Server Error" );
	echo 'ERROR: Error when clearing exclusions table. Please try again.';
	die;
}
$all_forms = [
	'pledge' => explode( "\n", $data['pledge'] ),
	'nonpledge' => explode( "\n", $data['nonPledge'] ),
	'all' => []
];

foreach ( $all_forms as $mode => $forms ) {
	foreach ( $forms as $f ) {
		if ( !empty( $f ) ) {
			$all_forms['all'][] = '"' . $f . '"';
			$query = "INSERT INTO exclusions(form, mode) VALUES(:form, :mode)";
			$stmt = $db->prepare( $query );
			$stmt->bindValue( ':form', $f );
			$stmt->bindValue( ':mode', $mode );
			$result = $stmt->execute();
			if ( $result === false ) {
				header( "HTTP/1.1 500 Server Error" );
				echo 'ERROR: Error when adding exclusion for pledge form ' . $f . '. Please try again.';
				die;
			}
		}
	}
}

$result = $db->query( "DELETE FROM transactions WHERE source IN (" . implode( ',', $all_forms['all'] ) . ")" );
if ( $result === false ) {
	header( "HTTP/1.1 500 Server Error" );
	echo 'ERROR: Error when deleting ingested transactions. Please try again.';
	die;
}

$message .= 'Exclusion tables updated and transactions cleaned up.';
$result = $db->query( "VACUUM;" );

header( 'Content-type: application/json' );
echo json_encode( [ 'result' => $message ] );

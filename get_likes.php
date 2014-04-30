<?php
namespace Like;
require_once 'vendor/facebook/php-sdk/src/facebook.php';
require_once 'utils.php';
require_once 'init_db.php';
header ( 'Content-type: text/html; charset=utf-8' );


function fetch($facebook, $post_id) {
	$url = "/$post_id/likes";
	$likes = [];
	$i = 0;
	echo "Start fetch $post_id likes...<br />";
	flush_buffers ();
	
	do {
		
		try {
			if (has_more_page ( $likes )) {
				$likes = $facebook->api ( $likes ['paging'] ['next'], 'GET' );
			} else {
				$likes = $facebook->api ( $url, 'GET');
			}
			if (array_key_exists ( 'data', $likes )) {
				insert ( $post_id, $likes );
				$i ++;
				$num_of_records = count ( $likes ['data'] );
				echo "\tInserted page $i ($num_of_records records) likes...<br />";
			}
			flush_buffers ();
		} catch ( FacebookApiException $e ) {
			$login_url = $facebook->getLoginUrl ();
			echo 'Please <a href="' . $login_url . '">login.</a><br />';
			flush_buffers ();
			break;
		}
	} while ( has_more_page ( $likes ) );
	if (! has_more_page ( $likes )) {
		echo "End fetch $post_id likes...<br />";
		flush_buffers ();
	}
}

function insert($post_id, $likes){
	global $db;
	global $_LIKE_TABLE;
	
	foreach ( $likes ['data'] as $like ) {
		$stmt = $db->prepare ( "INSERT INTO $_LIKE_TABLE (id, post_id, data) VALUES (?, ? ,?) ON DUPLICATE KEY UPDATE post_id = ?, data = ? " );
		$stmt->bindValue ( 1, $like ['id'] );
		$stmt->bindValue ( 2, $post_id );
		$stmt->bindValue ( 3, json_encode ( $like ) );
		$stmt->bindValue ( 4, $post_id );
		$stmt->bindValue ( 5, json_encode ( $like ) );
		$stmt->execute ();
	}
	
}
?>
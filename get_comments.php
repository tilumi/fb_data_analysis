<?php

namespace Comment;

require_once 'vendor/facebook/php-sdk/src/facebook.php';
require_once 'utils.php';
require_once 'init_db.php';
header ( 'Content-type: text/html; charset=utf-8' );
function fetch($facebook, $post_id) {
	$url = "/$post_id/comments";
	$comments = [ ];
	$i = 0;
	echo "Start fetch $post_id comments...<br />";
	flush_buffers ();
	
	do {
		
		try {
			if (has_more_page ( $comments )) {
				$comments = $facebook->api ( $comments ['paging'] ['next'], 'GET' );
			} else {
				$comments = $facebook->api ( $url, 'GET' );
			}
			if (array_key_exists ( 'data', $comments )) {
				insert ( $post_id, $comments );
				$i ++;
				$num_of_records = count ( $comments ['data'] );
				echo "\tInserted page $i ($num_of_records records) comments...<br />";
			}
			flush_buffers ();
		} catch ( FacebookApiException $e ) {
			$login_url = $facebook->getLoginUrl ();
			echo 'Please <a href="' . $login_url . '">login.</a><br />';
			flush_buffers ();
			break;
		}
	} while ( has_more_page ( $comments ) );
	if (! has_more_page ( $comments )) {
		echo "End fetch $post_id comments...<br />";
		flush_buffers ();
	}
}
function insert($post_id, $comments) {
	global $db;
	global $_COMMENT_TABLE;
	
	foreach ( $comments ['data'] as $comment ) {
		$stmt = $db->prepare ( "INSERT INTO $_COMMENT_TABLE (id, post_id, data) VALUES (?, ? ,?) ON DUPLICATE KEY UPDATE post_id = ?, data = ? " );
		$stmt->bindValue ( 1, $comment ['id'] );
		$stmt->bindValue ( 2, $post_id );
		$stmt->bindValue ( 3, json_encode ( $comment ) );
		$stmt->bindValue ( 4, $post_id );
		$stmt->bindValue ( 5, json_encode ( $comment ) );
		$stmt->execute ();
	}
}
?>
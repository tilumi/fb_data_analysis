<?php
require_once 'vendor/facebook/php-sdk/src/facebook.php';
require_once 'utils.php';
require_once 'init_db.php';
require_once 'get_comments.php';
require_once 'get_likes.php';

header ( 'Content-type: text/html; charset=utf-8' );

$config = array (
		'appId' => '506471126052324',
		'secret' => '444c2fdaa1ecd0753fbb4ee6e7c6038e',
		'allowSignedRequest' => false 
);
$facebook = new Facebook ( $config );

$page_id = $_GET ['page_id'];
if (! $page_id)
	$page_id = '219204214762378';

$post_url = "/$page_id/posts";

$user_id = $facebook->getUser ();

if ($user_id) {
	$day = $_GET ['day'];
	$start = $_GET ['start'];
	$end = $_GET ['end'];
	if ($start && $end) {
		batch_fetch_by_day ( $facebook, $post_url, $start, $end );
	} else if ($day) {
		fetch_by_day ( $facebook, $post_url, $day );
	}
} else {
	
	// No user, print a link for the user to login
	$login_url = $facebook->getLoginUrl ();
	echo 'Please <a href="' . $login_url . '">login.</a>';
}
function batch_fetch_by_day($facebook, $post_url, $start, $end) {
	$current_time = strtotime ( $start );
	$end_time = strtotime ( $end );
	while ( $current_time <= $end_time ) {
		fetch_by_day ( $facebook, $post_url, date ( 'Ymd', $current_time ) );
		$current_time = $current_time + 86400;
		echo "<br>";
		flush_buffers ();
	}
}
function fetch_by_day($facebook, $post_url, $day) {
	$since = strtotime ( $day );
	$until = $since + 86400;
	$posts = [ ];
	$i = 0;
	echo "Start fetch $day data...<br />";
	flush_buffers ();
	do {
		
		try {
			if (has_more_page ( $posts )) {
				$posts = $facebook->api ( $posts ['paging'] ['next'], 'GET' );
			} else {
				$posts = $facebook->api ( $post_url, 'GET', array (
						'since' => $since,
						'until' => $until 
				) );
			}
			if (array_key_exists ( 'data', $posts )) {
				insert ( $posts );
				foreach ($posts['data'] as $post){
					\Comment\fetch($facebook, $post['id']);
					\Like\fetch($facebook, $post['id']);
				}
				$i ++;
				$num_of_records = count ( $posts ['data'] );
				echo "\tInserted page $i ($num_of_records records) data...<br />";
			}
			flush_buffers ();
		} catch ( FacebookApiException $e ) {
			$login_url = $facebook->getLoginUrl ();
			echo 'Please <a href="' . $login_url . '">login.</a><br />';
			flush_buffers ();
			break;
		}
	} while ( has_more_page ( $posts ) );
	if (! has_more_page ( $posts )) {
		echo "End fetch $day data...<br />";
		flush_buffers ();
	}
}
function insert($posts) {
	global $db;
	global $_POST_TABLE;
	
	foreach ( $posts ['data'] as $post ) {
		$stmt = $db->prepare ( "INSERT INTO $_POST_TABLE (id, data, created) VALUES (?, ? ,?) ON DUPLICATE KEY UPDATE data = ?, created = ? " );
		$stmt->bindValue ( 1, $post ['id'] );
		$stmt->bindValue ( 2, json_encode ( $post ) );
		$stmt->bindValue ( 3, strtotime ( $post ['created_time'] ), PDO::PARAM_INT );
		$stmt->bindValue ( 4, json_encode ( $post ) );
		$stmt->bindValue ( 5, strtotime ( $post ['created_time'] ), PDO::PARAM_INT );
		$stmt->execute ();
	}
}
function fetch_all() {
}
function get_earliest_time() {
	global $db;
	global $_POST_TABLE;
	$stmt = $db->prepare ( "SELECT created from $_POST_TABLE ORDER BY created LIMIT 1" );
	$stmt->execute ();
	$result = $stmt->fetchAll ();
	if (count ( $result ) > 0) {
		return $result [0] [0];
	} else {
		return strtotime ( 'now' );
	}
}
?>
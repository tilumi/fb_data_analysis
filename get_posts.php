<?php
require_once ('vendor/facebook/php-sdk/src/facebook.php');

$db = new PDO ( 'mysql:host=localhost;dbname=fb_data_analysis;charset=utf8mb4', 'root', '', array (
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_PERSISTENT => false 
) );
create_tables ( $db );

$config = array (
		'appId' => '506471126052324',
		'secret' => '444c2fdaa1ecd0753fbb4ee6e7c6038e',
		'allowSignedRequest' => false 
);
$facebook = new Facebook ( $config );

$page_id = '219204214762378';
$post_url = "/$page_id/posts";
$post_table = 'post';

$user_id = $facebook->getUser ();

if ($user_id) {
	// main($db);
	$day = $_GET ['day'];
	fetch_by_day ( $facebook, $post_url, $day );
} else {
	
	// No user, print a link for the user to login
	$login_url = $facebook->getLoginUrl ();
	echo 'Please <a href="' . $login_url . '">login.</a>';
}
function fetch_by_day($facebook, $post_url, $day) {
	$since = strtotime ( $day );
	$until = $since + 86400;
	do {
		try {
			$posts = $facebook->api ( $post_url, 'GET', array (
					'since' => $since,
					'until' => $until 
			) );
			insert ( $posts );
		} catch ( FacebookApiException $e ) {
			$login_url = $facebook->getLoginUrl ();
			echo 'Please <a href="' . $login_url . '">login.</a>';
		}
	} while ( !is_last_page ( $posts ) );
}
function insert($posts) {
	global $db;
	global $post_table;
	foreach ( $posts ['data'] as $post ) {
		$stmt = $db->prepare ( "INSERT INTO $post_table (id, data, created) VALUES (?, ? ,?) ON DUPLICATE KEY UPDATE data = ?, created = ? " );
		$stmt->bindValue ( 1, $post ['id'] );
		$stmt->bindValue ( 2, json_encode ( $post ) );
		$stmt->bindValue ( 3, strtotime ( $post ['created_time'] ), PDO::PARAM_INT );
		$stmt->bindValue ( 4, json_encode ( $post ) );
		$stmt->bindValue ( 5, strtotime ( $post ['created_time'] ), PDO::PARAM_INT );
		$stmt->execute ();
	}
}
function fetch_all() {
	$sleep_time = 10;
	$interval_in_day = 30;
	
	// We have a user ID, so probably a logged in user.
	// If not, we'll get an exception, which we handle below.
	
	$until = get_earliest_time ();
	$since = $until - $interval_in_day * 86400;
	$posts = $facebook->api ( $url, 'GET', array (
			'since' => $since,
			'until' => $until 
	) );
	
	try {
		while ( ! is_last_page ( $posts ) ) {
			$until = determine_until_form_all_posts ( $all_posts );
			$since = $until - $interval_in_day * 86400;
			$posts = $facebook->api ( $url, 'GET', array (
					'since' => $since,
					'until' => $until 
			) );
		}
	} catch ( FacebookApiException $e ) {
		$login_url = $facebook->getLoginUrl ();
		echo 'Please <a href="' . $login_url . '">login.</a>';
	}
}
function create_tables($db) {
	$ddl = file_get_contents ( 'ddl.sql' );
	try {
		$stmt = $db->prepare ( $ddl );
		$stmt->execute ();
	} catch ( PDOException $e ) {
		echo $e->getMessage ();
		die ();
	}
	return true;
}
function get_earliest_time() {
	global $db;
	global $post_table;
	$stmt = $db->prepare ( 'SELECT created from $post_table ORDER BY created LIMIT 1' );
	$stmt->execute ();
	$result = $stmt->fetchAll ();
	if (count ( $result ) > 0) {
		return $result [0] [0];
	} else {
		return strtotime ( 'now' );
	}
}
function is_last_page($posts) {
	return ! array_key_exists ( 'paging', $posts ) && array_key_exists ( 'next', $posts ['paging'] );
}
function get_created_time($post) {
	return $post ['created_time'];
}
?>
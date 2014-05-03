<?php
require_once 'vendor/facebook/php-sdk/src/facebook.php';
require_once 'utils.php';
require_once 'init_db.php';
require_once 'get_comments.php';
require_once 'get_likes.php';
require_once 'Post.php';
require 'Job.php';

header ( 'Content-type: text/html; charset=utf-8' );

if(!array_key_exists('job_id', $_GET)){
	die('job_id parameter not set!');
}

$job_id = $_GET['job_id'];

$config = array (
		'appId' => '506471126052324',
		'secret' => '444c2fdaa1ecd0753fbb4ee6e7c6038e',
		'allowSignedRequest' => false
);
$facebook = new Facebook ( $config );

$user_id = $facebook->getUser ();

if ($user_id) {
	$result = Job::findAll($job_id);
	$job = $result->fetch();
	$page_id = null;
	$post_url = null;
	do{
		if($job){
			if(!isset($page_id) || !isset($post_url)){
				$page_id = $job['page_id'];
				$post_url = "/$page_id/posts";
			}
			$day = $job['day'];
			Post::fetch($facebook, $post_url, $day);
			Job::mark_success($job_id, $day);
		}
	}while($job = $result->fetch());
} else {
	
	// No user, print a link for the user to login
	$login_url = $facebook->getLoginUrl ();
	echo 'Please <a href="' . $login_url . '">login.</a>';
}

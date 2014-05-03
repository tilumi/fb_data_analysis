<?php
define ( "JOBS_DIR", "jobs" );
require_once 'init_db.php';
require_once 'Job.php';

if(!array_key_exists('page_id', $_GET)){
	die("page_id parameter not set!");
}

$page_id = $_GET['page_id'];

if (array_key_exists ( "start", $_GET ) && array_key_exists ( "end", $_GET )) {
// 	echo "Start generate job description";
	$job_id = round ( microtime ( true ) * 1000 );
	$current_time = DateTime::createFromFormat ( 'Ymd', $_GET ['start'] );
	while ( $current_time->getTimestamp () <= strtotime ( $_GET ['end'] ) ) {
		Job::insert ( $job_id, $page_id, $current_time->format ( 'Ymd' ) );
		$current_time = $current_time->modify ( '+1 day' );
	}
// 	echo "End generate job description";		
header ( "Location: get_posts.php?job_id=$job_id" );
}	

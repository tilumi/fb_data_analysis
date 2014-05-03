<?php
$_POST_TABLE = 'posts';
$_COMMENT_TABLE = 'comments';
$_LIKE_TABLE = 'likes';
$_JOB_TABLE = 'jobs';

$db = new PDO ( 'mysql:host=localhost;dbname=fb_data_analysis;charset=utf8mb4', 'root', '', array (
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_PERSISTENT => false
) );
create_tables ( $db );

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
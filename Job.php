<?php
class Job{
	
	
	function __construct($id, $page_id, $times){
		
	}
	
	static function findAll($job_id){
		global $db;
		global $_JOB_TABLE;
		$jobs = [];
		$sql = "select * from $_JOB_TABLE where id = $job_id";		
		return $db->query($sql);
							
	}
	
	static function insert($job_id, $page_id, $day_id) {
		global $db;
		global $_JOB_TABLE;
	
		$stmt = $db->prepare ( "INSERT INTO $_JOB_TABLE (id, page_id, day) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE success = ? " );
		$stmt->bindValue ( 1, $job_id );
		$stmt->bindValue ( 2, $page_id );
		$stmt->bindValue ( 3, $day_id, PDO::PARAM_INT );
		$stmt->bindValue ( 4, 0 ,PDO::PARAM_INT);
		$stmt->execute ();
	}
	static function mark_success($job_id, $day_id) {
		global $db;
		global $_JOB_TABLE;
	
		$stmt = $db->prepare ( "UPDATE $_JOB_TABLE SET success = 1 WHERE id = ? AND day = ?" );
		$stmt->bindValue ( 1, $job_id );
		$stmt->bindValue ( 2, $day_id, \PDO::PARAM_INT );
		$stmt->execute ();
	}
}
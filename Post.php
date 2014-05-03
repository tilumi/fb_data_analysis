<?php
class Post {
	function __construct() {
		
	}
	
	public static function batch_fetch_by_day($facebook, $post_url, $start, $end) {
		$current_time = strtotime ( $start );
		$end_time = strtotime ( $end );
		while ( $current_time <= $end_time ) {
			fetch_by_day ( $facebook, $post_url, date ( 'Ymd', $current_time ) );
			$current_time = $current_time + 86400;
			echo "<br>";
			flush_buffers ();
		}
	}
	
	public static function fetch($facebook, $post_url, $day) {
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
					Post::insert ( $posts );
					foreach ( $posts ['data'] as $post ) {
						echo "<div style='padding-left: 20px;'>";
						Comment\fetch ( $facebook, $post ['id'] );
						echo "<br />";
						Like\fetch ( $facebook, $post ['id'] );
						echo "<br />";
						echo "</div>";
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
	public static function insert($posts_json) {
		global $db;
		global $_POST_TABLE;
		
		foreach ( $posts_json ['data'] as $post ) {
			$stmt = $db->prepare ( "INSERT INTO $_POST_TABLE (id, data, created) VALUES (?, ? ,?) ON DUPLICATE KEY UPDATE data = ?, created = ? " );
			$stmt->bindValue ( 1, $post ['id'] );
			$stmt->bindValue ( 2, json_encode ( $post ) );
			$stmt->bindValue ( 3, strtotime ( $post ['created_time'] ), \PDO::PARAM_INT );
			$stmt->bindValue ( 4, json_encode ( $post ) );
			$stmt->bindValue ( 5, strtotime ( $post ['created_time'] ), \PDO::PARAM_INT );
			$stmt->execute ();
		}
	}
	
	
}
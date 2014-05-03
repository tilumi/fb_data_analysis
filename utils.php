<?php

function has_more_page($posts) {
	return (array_key_exists ( 'paging', $posts ) && array_key_exists ( 'next', $posts ['paging'] ));
}

function flush_buffers() {
	ob_flush ();
	flush ();
}

?>
<?php

$VLOG_DEBUG = false;

function vlog($str) {
	global $VLOG_DEBUG;

	if ($VLOG_DEBUG ) {
		error_log("LOG: $str" . PHP_EOL);
	}
}

?>

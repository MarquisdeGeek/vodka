<?php

/**
 * If an error occurs, end the session.
 */
$v->registerState('error', function() {
global $v;
	
	$v->tropo->say("Please try your request again later.");
	$v->tropo->hangup();
	return $v->tropo->renderJSON();
});

?>

<?php

function processOption3($v) {
	$db = new VDB('calls');
	$record = $db->getRecord($v->getCallID());

	$udb = new VDB('users');
	$user = $udb->getRecord(trim($record->user));


	$v->tropo->say("Your option three is " . $user->option3);
}

?>

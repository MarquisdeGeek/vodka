<?php

function processOption2($v) {
	$db = new VDB('calls');
	$record = $db->getRecord($v->getCallID());

	$udb = new VDB('users');
	$user = $udb->getRecord(trim($record->user));


	$v->tropo->say("Your option two is " . $user->option2);
}

?>

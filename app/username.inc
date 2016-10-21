<?php

$v->registerState('username', function() {
global $v;

	$username = trim($v->getLastInput());

	$udb = new VDB('users');
	$user_record = $udb->getRecord($username);

	// TODO Handle case where user doesn't exist
	$v->tropo->say("Thank you ". $user_record->name);

	$db = new VDB('calls');
	$db->setRecord($v->getCallID(), array('user'=>$username));

	generatePasscode($v);

	return $v->tropo->RenderJson();
});


?>

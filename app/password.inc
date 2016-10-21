<?php

$v->registerState('passcheck', function() {
global $v;

	$db = new VDB('calls');
	$record = $db->getRecord($v->getCallID());

	$udb = new VDB('users');
	$user = $udb->getRecord($record->user);
	

	$pc = trim($v->getLastInput());

	if ($user->password == $pc) {
		$record->valid = true;
		$db->setRecord($v->getCallID(), $record);

		generateMainMenu($v);
	} else {
		$v->tropo->say("That is incorrect.");
		generatePasscode($v);
	}

	return $v->tropo->RenderJson();
});


function generatePasscode($v) {

	$options = array("attempts" => 3, "bargein" => true, "choices" => "[4 DIGITS]", "name" => "pcpc", "timeout" => 5);
	$v->tropo->ask("password.", $options);
	$v->tropo->on(array("event" => "continue", "next" => $v->getStateURL("passcheck")));
}

?>

<?php
require('vodka.php');
require('app.php');

global $v;	// not needed, because $v is already global, but reminds us of the app

$obj = $v->createSession('07703', 'Steev');


$json = json_encode($obj);
$state = "start";

while($state) {
	$v->start($json, $state);
	$state = $v->tropo->nextState;
}

?>

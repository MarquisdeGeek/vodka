<?php

$v->registerState('start', function() {
global $v;

	$options = array("attempts" => 3, "bargein" => true, "choices" => "[6 DIGITS]", "name" => "username", "timeout" => 5);
		
	// Ask the user for input, pass in options.
	$v->tropo->ask("Please enter your 6 digit user name", $options);
		
	// Tell Tropo what to do when the user has entered input, or if there is an error.
	$v->tropo->on(array("event" => "continue", "next" => $v->getStateURL("username")));
	//$v->tropo->on(array("event" => "continue", "next" => "test1.php?uri=username", "say" => "An error has occured."));
	
	// Render the JSON for the Tropo WebAPI to consume.
	return $v->tropo->RenderJson();
	
});

?>

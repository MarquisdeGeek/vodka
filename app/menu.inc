<?php

$v->registerState('menuoption', function() {
global $v;

    $option = $v->getLastInput();

	processMenu($v, $option);

	return $v->tropo->RenderJson();
});
			

$v->registerState('menuagain', function() {
global $v;

    $option = $v->getLastInput();

	switch($option) {
		case 0:
			generateMainMenu($v);
			break;

		default:
			processMenu($v, $option);
	}

	return $v->tropo->RenderJson();
});


function generateMainMenu($v) {

	$menu = "";
	
	$menu .= "Option 1, ";
	$menu .= "Option 2, ";
	$menu .= "Option 3, ";

	$options = array("attempts" => 3, "bargein" => true, "choices" => "[1 DIGITS]", "name" => "menuoption", "timeout" => 15);
	$v->tropo->ask("Please select an option,  $menu", $options);

	$v->tropo->on(array("event" => "continue", "next" => $v->getStateURL("menuoption")));
}

			

function processMenu($v, $option) {
	switch($option) {
		case 1:
			processOption1($v);
			break;
		case 2:
			processOption2($v);
			break;
		case 3:
			processOption3($v);
			break;

		case 9:
			$v->tropo->say("Thank you and goodbye");
			// TODO: This doesn't work!?!?
			$v->tropo->hangup();
			return;
	}

	$options = array("attempts" => 3, "bargein" => true, "choices" => "[1 DIGITS]", "name" => "another", "timeout" => 999);
	$v->tropo->ask("Request another service, or press zero to hear the menu,", $options);
	$v->tropo->on(array("event" => "continue", "next" => $v->getStateURL("menuagain")));

}

?>

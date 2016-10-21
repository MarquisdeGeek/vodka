<?php
require_once('lib/tropo.class.php');
require_once('lib/limonade.php');
require_once('inc/db.inc');
require_once('inc/vlog.inc');


if (PHP_VERSION_ID < 50400) {
	echo "Some code uses closures that require 5.4, I believe";
}

class VodkaFakeTropoVoice {
private $_instructionList;
private $_eventList;
private $_lastInput;
public $nextState;

	public function __construct($fname) {
		$this->_instructionList = array();
		$this->_eventList = array();
		$this->fileHandle = fopen($fname, "r");
	}

	public function newState() {
		$this->_instructionList = array();
		$this->_eventList = array();
	}

	public function hangup($msg = '') {
		array_push($this->_instructionList, function() use ($msg) {
			$this->nextState = null;
		});
	}

	public function say($msg) {
		array_push($this->_instructionList, function() use ($msg) {
			print $msg . PHP_EOL;
		});
	}

	public function transfer($new_phone_number) {
		array_push($this->_instructionList, function() use ($new_phone_number) {
			print "Forwarding the call to " . $new_phone_number . PHP_EOL;
		});
	}

	public function record($options) {
		array_push($this->_instructionList, function() use ($options) {
			print "Recording. Will callback to " . $options['url'] . PHP_EOL;
		});
	}

	public function message($msg, $options) {
		array_push($this->_instructionList, function() use ($msg, $options) {
			print "Making call to " . $options['to'] . " with the message " . $msg . PHP_EOL;
		});
	}




	public function ask($msg, $options) {
		array_push($this->_instructionList, function() use ($msg, $options) {
			print $msg . PHP_EOL;
			print ">>> ";

			$this->_lastInput = fgets($this->fileHandle);

			$this->sendEvent('continue', $this->_lastInput);
		});
	}

	public function on($event) {
		array_push($this->_instructionList, function() use ($event) {
			foreach($this->_eventList as $ev) {
				if ($ev['type'] == $event['event']) {
					if (isset($event['say'])) {
						print $event['say'] . PHP_EOL;	// BUGWARN: dupe of SAY above. TODO: add 'in/out' driver for this so it can be replaced with log out and DB/test input;
					} 

					if (isset($event['next'])) {
						$idx = strpos($event['next'], 'uri=');
						$state = substr($event['next'], $idx+4);

//						print "jump to $state  " . $event['next'];
$this->nextState = $state;
					} 
				}
			//print $msg . PHP_EOL;
			}
		});
	}

	public function getLastInput() {
		return $this->_lastInput;
	}


	public function sendEvent($eventType, $eventAnswer) {
		array_push($this->_eventList, array('type' => $eventType, 'answer' => $eventAnswer));
	}

	// TODO: Parse string to 'play' mp3/wav/gsm etc
	public function RenderJson() {
		$this->nextState = null;

		foreach($this->_instructionList as $cmd => $params) {
			$r = call_user_func($params);
		}
	}
}

class VSession {
public $callId;
}

class Vodka {
public $session;
public $tropo;
private $stateList;
public $lastState;
private $resultJSON; // TODO _

	public function __construct() {
		$this->stateList = array();
	}

	private function init($json) {
		$vdb = new VDB('sessions');

		// We explicit open the input stream here to check for
		// remote TONIC (debug) streams.
		if ($json && isset($json)) {
			// BUGWARN: This option is really for just TONIC
			$json = $this->validateSession($json);
		} else {
			$json = file_get_contents("php://input");
		}
		try {
  			// If there is not a session object in the POST body,
  			// then this isn't a new session. Tropo will throw
  			// an exception, so check for that.
  			$session = new Session($json);
			// TODO: Add more information here
			// TODO: Pass to ctor of VSession
			$data = array('callId'=>$session->getCallId(), 'sessionId' => $session->getId(), 'json' => $json );

			if ($vdb->doesRecordExist($data['callId'])) {
				// nop - keep the first session data, as second and subsequent
				// blocks may be incomplete.
			} else {
				$vdb->setRecord($data['callId'], $data);
			}
		} catch (TropoException $e) {
			// This is a normal case, so we don't really need to
			// do anything if we catch this.
			error_log("Tropo exception when creating new sessions : " . $e->getMessage());
		}
		//
		// Sometimes there is no result because, on subsequent Tropo
		// invocations (i.e. after initial call) this information isn't
		// resent.
		$result = json_decode($json);
		vlog($json);

		// Since we don't have all the session data, we recall it from the DB
		$data = $vdb->getRecord($result->result->callId);

		$this->session = new VSession();
		$this->session->callId = $data->callId;

		if (@$result->session->from->network == "TONIC") {
			if ($result->session->from->channel == "VOICE") {
				$infile = $result->session->from->xtonic;
				$infile = isset($infile) ? $infile : "php://stdin";
				$this->tropo = new VodkaFakeTropoVoice($infile);
			} else {
				error_log("Only voice networks are currently supported.");
				//$this->tropo = new VodkaFakeTropoSMS();
			}
		} else {
			$this->tropo = new Tropo();
		}
		//
	}

	public function createSession($phoneID, $phoneName = 'unknown') {
		$callid = uniqid();
		$sessionid = uniqid();

		$session = array('session' => array(
			'accountId' => 0,
			'sessionId' => $sessionid,
			'callId' => $callid,
    		'from' =>
        		array('channel'=>'VOICE', 'id'=>$phoneID, 'name'=>$phoneName, 'network'=>'TONIC', 'xtonic' => null),
			'headers' => array(),
			'id' => $sessionid,
			'initialText' => null,
			'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
    		'to' =>
        		array('channel'=>'VOICE', 'id'=>'00 us 00', 'name'=>'unknown', 'network'=>'PSTN'),
			'userType' => 'HUMAN'
	    ), 'result' => array('sessionId'=>$sessionid, 'callId'=>$callid)
		);

		return $session;
	}


	public function registerState($name, $cbfn) {
		$this->stateList[$name] = $cbfn;
		// dispatch is for limonade services only
		dispatch_post('/' . $name, $this->stateList[$name]); 
	}

	public function processState($name) {
		// only handled by fake tropo, since limonade handles the genuine
		// state methods

		$this->lastState = null;
		if (isset($this->stateList[$name])) {
			$this->stateList[$name]();
			$this->lastState = $name;
		}
	
		$result = null;
		if (method_exists($this->tropo, 'getLastInput')) {
			$result = $this->tropo->getLastInput();
		}
		$this->applyResultSession($result);
	}

	public function applyResultSession($result) {
		if ($result == null) {
			$this->resultJSON = null;
			return;
		}

		$this->resultJSON = json_encode(array('result' => array(
			'sessionId' => 0,
			'actions' => array(
				'value' => $result
				)
			)
		));
	}

	public function getLastInput() {
    	@$result = new Result($this->resultJSON);
    	$answer = $result->getValue();
		return $answer;
	}

	public function getCallID() {
		return $this->session->callId;
	}

	public function getStateURL($name) {
		// The URL is only used when remote servers (e.g. tropo) call us
		// but it's here for unified dev.
		return $_SERVER['PHP_SELF'] . "?uri=" . $name;
	}

	private function validateSession($json) {
		$obj = json_decode($json);
	
		isset($obj->session->id) OR $obj->session->id = '';
		isset($obj->session->accountId) OR $obj->session->accountId = '';
		isset($obj->session->callId) OR $obj->session->callId = uniqid();
		isset($obj->session->timestamp) OR $obj->session->timestamp = '';
		isset($obj->session->initialText) OR $obj->session->initialText = '';

		return json_encode($obj);
	}

	public function start($sessionJSON = null, $initialState = null) {
		$this->init($sessionJSON);
		if (isset($sessionJSON)) {
			$this->processState($initialState);
		} else {// running with limonade
			run();
		}
	}

}

?>

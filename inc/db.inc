<?php
include 'vlog.inc';

class VDB {

	public function __construct($table) {
		$this->folder = "db/" . trim($table) . "/";
		@mkdir($this->folder, 0777, true);
	}

	public function doesRecordExist($id) {
		return file_exists($this->folder . trim($id));
	}

	public function getRecord($id) {
		vlog("Get record $id from " . $this->folder);
		$data = file_get_contents($this->folder . trim($id));

		vlog("  Record data : $data");
		return json_decode($data);
	}

	public function setRecord($id, $data) {
		$filename = $this->folder . trim($id);
		vlog("Changing record $id : $filename");
		file_put_contents($filename, json_encode($data));
	}
}

?>

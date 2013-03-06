<?php

class DevInfo6AppModel extends AppModel {
	public function truncate() {
		if(isset($this->useTable)) {
			$sql = "TRUNCATE TABLE `%s`";
			$this->query(sprintf($sql, $this->useTable));
		}
	}
}
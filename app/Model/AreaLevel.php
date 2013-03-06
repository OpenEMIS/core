<?php
App::uses('AppModel', 'Model');

class AreaLevel extends AppModel {
	public $hasMany = array('Area');

	public function saveAreaLevelData($data) {
		$keys = array();
		$levels = array();
		
		if(isset($data['deleted'])) {
			 unset($data['deleted']);
		}

		// foreach($deleted as $id) {
		// 	$this->delete($id);
		// }
		// pr($data);

		for($i=0; $i<sizeof($data); $i++) {
			$row = $data[$i];
            $name = isset($row['name'])? trim($row['name']): '';
			if(!empty($name)) {
				if($row['id'] == 0) {
				 	$this->create();
				 }
				$save = $this->save(array('AreaLevel' => $row));
				
				if($row['id'] == 0) {
					$keys[strval($i+1)] = $save['AreaLevel']['id'];
				}
			} /*else if($row['id'] > 0 && $row['male'] == 0 && $row['female'] == 0) {
				$this->delete($row['id']);
			}*/
		}
		return $keys;
	}
}

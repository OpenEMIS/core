<?php
App::uses('AppModel', 'Model');

class Area extends AppModel {
	public $validate = array(
		'code' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				 'message' => 'Please enter the code for the Area.'
			),
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'There are duplicate area code.'
			)
		),
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				 'message' => 'Please enter the name for the Area.'
			)
		)
	);
	
	public $belongsTo = array('AreaLevel');
	
	public function fetchSubLevelList($parentId) {

		$children = $this->find('all', array(
			'conditions' => array('Area.parent_id' => $parentId ),
			'fields' => 'GROUP_CONCAT(Area.id) as children'
		));
		$data = $children[0][0]['children'];
		return $data;
	}

	public function getChildren($parentId, $str=null) {
		$children = $this->find('all', array('conditions' => array('Area.parent_id' => $parentId ), 'fields' => 'GROUP_CONCAT(Area.id) as children'));
		$childrenId = $children[0][0]['children'];

		if ($childrenId == "") { return $str; }

		$children = explode(",", $childrenId);
		$str .= $childrenId.",";

		$data = "";
		foreach ($children as $value) {
			$data .= $this->getChildren($value, $str);
		}

		$data = substr($data, 0, strlen($data)-1);
		$values = array_unique(explode(",",$data));
		return implode(",",$values);

	}

	/**
	 * get Area name based on Area Id
	 * @return string 	area name
	 */
	public function getName($id) {
		$data = $this->findById($id);	
		return $data['Area']['name'];
	}
}

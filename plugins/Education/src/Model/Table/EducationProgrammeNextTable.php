<?php
namespace Education\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;

class EducationProgrammeNextTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('EducationProgrammes', ['className' => 'Education.EducationProgrammes', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('NextEducationProgrammes', ['className' => 'Education.EducationProgrammes', 'foreignKey' => 'next_programme_id','dependent' => true, 'cascadeCallbacks' => true]);
	}


}

<?php
namespace Education\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;

class EducationProgrammesNextProgrammesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes', 'foreignKey' => 'education_programme_id']);
		$this->belongsTo('EducationnNextProgrammes', ['className' => 'Education.EducationnNextProgrammes', 'foreignKey' => 'next_programme_id']);
	}
}

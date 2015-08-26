<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;

use App\Model\Table\AppTable;

class EducationSystemsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('EducationLevels', ['className' => 'Education.EducationLevels', 'cascadeCallbacks' => true]);

	}
}

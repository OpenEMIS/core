<?php 
namespace Institution\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;

class UndoBehavior extends Behavior {
	protected $undoAction;
	protected $Students;

	public function initialize(array $config) {
		parent::initialize($config);

		$class = basename(str_replace('\\', '/', get_class($this)));
		$class = str_replace('Undo', '', $class);
		$class = str_replace('Behavior', '', $class);
		$this->_table->addUndoActions($class);
		$this->undoAction = $class;

		$this->Students = TableRegistry::get('Institution.Students');
	}
}

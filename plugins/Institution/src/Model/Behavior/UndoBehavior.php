<?php 
namespace Institution\Model\Behavior;

use Cake\ORM\Behavior;

class UndoBehavior extends Behavior {
	protected $undoAction;

	public function initialize(array $config) {
		parent::initialize($config);

		$class = basename(str_replace('\\', '/', get_class($this)));
		$class = str_replace('Undo', '', $class);
		$class = str_replace('Behavior', '', $class);
		$this->_table->addUndoActions($class);
		$this->undoAction = $class;
	}
}

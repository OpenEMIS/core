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
		$this->hasMany('EducationLevels', ['className' => 'Education.EducationLevels']);
		$this->addBehavior('ControllerAction.Delete');
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		if (empty($this->request->data['transfer_to'])) {
			if ($this->associationCount($this, $id) > 0) {
				$this->Alert->error('general.deleteTransfer.restrictDelete');
				$event->stopPropagation();
				return $this->controller->redirect($this->ControllerAction->url('remove'));
			}
		}
	}
}

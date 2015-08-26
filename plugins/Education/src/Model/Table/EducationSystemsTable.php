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

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		if (empty($this->request->data['transfer_to'])) {
			$this->Alert->error('general.delete.failed');
			$event->stopPropagation();
			return $this->controller->redirect($this->ControllerAction->url('remove'));
		}
	}
}

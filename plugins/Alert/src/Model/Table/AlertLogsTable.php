<?php
namespace Alert\Model\Table;

use Cake\ORM\Entity;
use Cake\Event\Event;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class AlertLogsTable extends AppTable  {
	use OptionsTrait;

	public function initialize(array $config) {
        parent::initialize($config);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder(['created', 'destination', 'method', 'type', 'subject', 'message', 'status']);
	}
}

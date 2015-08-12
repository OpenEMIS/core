<?php
namespace Alert\Model\Table;

use Cake\ORM\Entity;
use Cake\Event\Event;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class SmsResponsesTable extends AppTable  {
	use OptionsTrait;

	public function initialize(array $config) {
        parent::initialize($config);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder(['number', 'message', 'response', 'sent', 'received']);
	}
}

<?php
namespace Alert\Model\Table;

use Cake\ORM\Entity;
use Cake\Event\Event;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class SmsMessagesTable extends AppTable  {
	use OptionsTrait;

	public function initialize(array $config) {
        parent::initialize($config);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('enabled', ['options' => $this->getSelectOptions('general.yesno')]);

		$this->ControllerAction->setFieldOrder(['enabled', 'message']);
	}

	public function onGetEnabled(Event $event, Entity $entity) {
		return $entity->enabled == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}
}

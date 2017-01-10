<?php
namespace Alert\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class SmsMessagesTable extends ControllerActionTable  {
	use OptionsTrait;

	public function initialize(array $config) {
        parent::initialize($config);
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('enabled', ['options' => $this->getSelectOptions('general.yesno')]);

		$this->setFieldOrder(['enabled', 'message']);
	}

	public function onGetEnabled(Event $event, Entity $entity) {
		return $entity->enabled == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}
}

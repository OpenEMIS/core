<?php
namespace Import\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\I18n\Time;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\EventTrait;
use Cake\I18n\I18n;

class ImportLinkBehavior extends Behavior {
	protected $_defaultConfig = [
	];

	public function initialize(array $config) {
		// pr($this->_table->ControllerAction);die;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 1];
		return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		switch ($action) {
			case 'index':
				$url = $this->_table->request->params;
				unset($url['_ext']);
				unset($url['pass']);
				if (array_key_exists('paging', $url)) {
					unset($url['paging']);
				}

				$import['url'] = $url;
				$import['url']['action'] = 'Import'.$this->_table->alias();
				$import['type'] = 'button';
				$import['label'] = '<i class="fa kd-import"></i>';
				$import['attr'] = $attr;
				$import['attr']['title'] = __('Import');

				$toolbarButtons['import'] = $import;
				break;
		}
	}

}

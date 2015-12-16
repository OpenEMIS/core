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
		$importModel = $this->config('import_model');
		if (empty($importModel)) {
			$this->config('import_model', 'Import'.$this->_table->alias());
		};
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 1];
		return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		switch ($action) {
			case 'index':
				if ($buttons['index']['url']['action']=='Surveys') {
					break;
				}
				$this->generateImportButton($toolbarButtons, $attr);
				break;

			case 'view':
				if ($buttons['view']['url']['action']!='Surveys') {
					break;
				}
				$import['url'] = $buttons['view']['url'];
				$import['url']['action'] = 'Import'.$this->_table->alias();
				$import['url'][0] = 'add';
				$import['type'] = 'button';
				$import['label'] = '<i class="fa kd-import"></i>';
				$import['attr'] = $attr;
				$import['attr']['title'] = __('Import');
				unset($import['url']['filter']);

				$toolbarButtons['import'] = $import;
				break;
		}
	}

	private function generateImportButton(ArrayObject $toolbarButtons, array $attr) {
		$url = $this->_table->request->params;
		unset($url['_ext']);
		unset($url['pass']);
		if (array_key_exists('paging', $url)) {
			unset($url['paging']);
		}

		$import['url'] = $url;
		$import['url']['action'] = $this->config('import_model');
		$import['type'] = 'button';
		$import['label'] = '<i class="fa kd-import"></i>';
		$import['attr'] = $attr;
		$import['attr']['title'] = __('Import');

		$toolbarButtons['import'] = $import;
	}

}

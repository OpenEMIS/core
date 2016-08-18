<?php
namespace Configuration\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Utility\Inflector;

class ConfigItemsBehavior extends Behavior {
	private $model;

    public function implementedEvents()
    {
        $events = parent::implementedEvents();

        $events['ControllerAction.Model.index.beforeAction'] = ['callable' => 'indexBeforeAction'];
        if ($this->isCAv4()) {
            $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction'];
        }
        // $events['ControllerAction.Model.addEdit.beforePatch'] = ['callable' => 'addEditBeforePatch'];
        return $events;
    }

    public function initialize(array $config) {
        $this->model = $this->_table;
    }

	private function isCAv4() {
		return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
	}

	public function buildSystemConfigFilters() {
		$toolbarElements = [
			['name' => 'Configuration.controls', 'data' => [], 'options' => []]
		];
		$this->model->controller->set('toolbarElements', $toolbarElements);
        $ConfigItem = TableRegistry::get('Configuration.ConfigItems');
		$typeOptions = array_keys($ConfigItem->find('list', ['keyField' => 'type', 'valueField' => 'type'])->order('type')->toArray());

		$selectedType = $this->model->queryString('type', $typeOptions);

		$buffer = $typeOptions;
		foreach ($buffer as $key => $value) {
			$result = $ConfigItem->find()->where([$ConfigItem->aliasField('type') => $value, $ConfigItem->aliasField('visible') => 1])->count();
			if (!$result) {
				unset($typeOptions[$key]);
			}
		}
		$this->model->request->query['type_value'] = $typeOptions[$selectedType];
		$this->model->advancedSelectOptions($typeOptions, $selectedType);
		$this->model->controller->set('typeOptions', $typeOptions);
	}

	public function checkController()
	{
        $this->buildSystemConfigFilters();
        $typeValue = $this->model->request->query['type_value'];
        $typeValue = Inflector::camelize($typeValue, ' ');
        if (method_exists($this->model->controller, $typeValue)) {
            $this->model->controller->redirect(['plugin' => 'Configuration', 'controller' => 'Configurations', 'action' => $typeValue]);
        }
	}

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Configurations', 'action' => 'index'];
    }

	public function indexBeforeAction(Event $event)
    {
        $this->checkController();

	}
}

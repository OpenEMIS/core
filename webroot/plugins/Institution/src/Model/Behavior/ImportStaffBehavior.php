<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;

class ImportStaffBehavior extends Behavior 
{
    public $importFeatureList = [
        'Institution.Institutions.ImportStaff' => 'Import Staff',
        'Institution.Institutions.ImportStaffSalaries' => 'Import Staff Salaries'
    ];

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.add.beforeAction'] = 'addBeforeAction';
        return $events;
    }

    public function addBeforeAction(Event $event)
    {
        $this->_table->ControllerAction->field('feature');
        $this->_table->ControllerAction->setFieldOrder(['feature', 'select_file']);
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $request = $this->_table->request;
            $plugin = $request->params['plugin'];
            $controller = $request->params['controller'];
            $table = $this->_table->alias();
            $selectedFeature =  $plugin . '.' . $controller . '.' . $table;

            $options = $this->getFeatureOptions();
            $attr['type'] = 'select';
            $attr['options'] = $options;
            $attr['select'] = false;
            $attr['onChangeReload'] = 'changeFeature';
            $attr['value'] = $selectedFeature;
            $attr['attr']['value'] = $selectedFeature;
            return $attr;
        }
    }

    public function addEditOnChangeFeature(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $table = $this->_table;
        $request = $this->_table->request;

        if (isset($data) && isset($data[$table->alias()]) && !is_null($data[$table->alias()]['feature'])) {
            $feature = explode('.', $data[$table->alias()]['feature']) ;
            list($plugin, $controller, $action) = $feature;
            $institutionParams = $request->params['institutionId'];

            $url = [
                'plugin' => $plugin,
                'controller' => $controller,
                'institutionId' => $institutionParams,
                'action' => $action,
                'add'
            ];

            $requestQuery = $request->query;
            if (!empty($requestQuery)) {
                $url = array_merge($url, $requestQuery);
            }
            $this->_table->controller->redirect($url);
        }
    }

    private function getFeatureOptions()
    {
        $acceessControl = $this->_table->AccessControl;
        $featureList = [];

        foreach ($this->importFeatureList as $key => $name) {
            $feature = explode('.', $key);
            list($plugin, $controller, $action) = $feature;

            if ($acceessControl->check([$controller, $action, 'add'])) {
                $featureList[$key] = __($name);
            }
        }
        return $featureList;
    }
}

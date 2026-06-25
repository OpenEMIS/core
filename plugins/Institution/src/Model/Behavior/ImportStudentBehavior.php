<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;

class ImportStudentBehavior extends Behavior 
{
    public $importFeatureList = [
        'Institution.Institutions.ImportStudentAdmission' => 'Import Student',
        'Institution.Institutions.ImportStudentBodyMasses' => 'Import Body Masses',
        'Institution.Institutions.ImportStudentGuardians' => 'Import Student Guardians',
        'Institution.Institutions.ImportStudentExtracurriculars' => 'Import Extracurriculars'
    ];

    public function implementedEvents():array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.add.beforeAction'] = 'addBeforeAction';
        return $events;
    }

    public function addBeforeAction(EventInterface $event)
    {
        $this->_table->ControllerAction->field('feature');
        $this->_table->ControllerAction->setFieldOrder(['feature', 'select_file']);
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $request = $this->_table->request;
            $plugin = $request->getAttribute('params')['plugin'];
            $controller = $request->getAttribute('params')['controller'];
            $table = $this->_table->getAlias();
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

    public function addEditOnChangeFeature(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $table = $this->_table;
        $request = $this->_table->request;

        if (isset($data) && isset($data[$table->getAlias()]) && !is_null($data[$table->getAlias()]['feature'])) {
            $feature = explode('.', $data[$table->getAlias()]['feature']) ;
            list($plugin, $controller, $action) = $feature;
            $institutionParams = $request->params['institutionId'];

            $url = [
                'plugin' => $plugin,
                'controller' => $controller,
                'institutionId' => $institutionParams,
                'action' => $action,
                'add'
            ];

            $requestQuery = $request->getQuery();
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

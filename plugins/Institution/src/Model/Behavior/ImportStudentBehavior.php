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
        $selectedFeature = $data[$table->getAlias()]['feature'] ?? null;

        // POCOR-9896: only redirect to a known import feature
        if (!empty($selectedFeature) && array_key_exists($selectedFeature, $this->importFeatureList)) {
            list($plugin, $controller, $action) = explode('.', $selectedFeature);

            // POCOR-9896: ServerRequest::$params is not accessible in CakePHP 5 (threw -> error404), and the
            // institution is carried in the pass params (/ImportStudentAdmission/add/<encoded>), not in
            // 'institutionId'. Keep the current pass + query and only swap the import action.
            $request = $table->request;
            $pass = (array)$request->getParam('pass');
            if (($pass[0] ?? null) === 'add') {
                array_shift($pass);
            }
            $url = array_merge(
                ['plugin' => $plugin, 'controller' => $controller, 'action' => $action, 'add'],
                $pass,
                $request->getQuery()
            );
            if (!empty($request->getParam('institutionId'))) {
                $url['institutionId'] = $request->getParam('institutionId');
            }

            $event->stopPropagation();
            return $table->controller->redirect($url);
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

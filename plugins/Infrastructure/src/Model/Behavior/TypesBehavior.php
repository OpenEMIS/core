<?php
namespace Infrastructure\Model\Behavior;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Behavior;
use Cake\Event\EventInterface;

class TypesBehavior extends Behavior
{
    protected $_defaultConfig = [
        'code' => null
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 10];
        $events['ControllerAction.Model.index.beforeQuery'] = ['callable' => 'indexBeforeQuery', 'priority' => 1];
        return $events;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $model = $this->_table;

        if ($model->action == 'index') {
            $request = $model->request; // POCOR-9074
            $selectedLevel = !is_null($request->getQuery('level')) ? $request->getQuery('level') : '-1'; // POCOR-9074
            $InfrastructureLevels = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureLevels');
            $levelDetails = $InfrastructureLevels->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code'
                ])
                ->toArray();
            if(!empty($request)){
                $level = $request->getQuery('level');
                $redirectAction = isset($levelDetails[$level]) ? ucfirst(strtolower($levelDetails[$level])).'Types' : null;

                if ($redirectAction && $redirectAction != $model->getAlias()) { // POCOR-9074
                    // call from general, if room selected, redirect to room types
                    $code = $levelDetails[$selectedLevel];
                    $url = $model->url('index');
                    $url['action'] = $redirectAction;

                    $event->stopPropagation();
                    return $model->controller->redirect($url);
                }
            }

        } else {
            unset($extra['elements']['controls']);
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $model = $this->_table;
        $extra['elements']['controls'] = ['name' => 'Infrastructure.controls', 'data' => [], 'options' => [], 'order' => 1];

        $InfrastructureLevels = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureLevels');
        $levelOptions = $InfrastructureLevels->find('list')->toArray();
        $selectedLevel = $model->request->getQuery('level') ?? -1; // POCOR-9074

        $levelOptions = [-1 => __('Select Level')] + $levelOptions; // POCOR-9074
        $model->advancedSelectOptions($levelOptions, $selectedLevel);
        $model->controller->set(compact('levelOptions', 'selectedLevel'));

        $extra['params']['levelOptions'] = $levelOptions;
        $extra['params']['selectedLevel'] = $selectedLevel;
    }
}

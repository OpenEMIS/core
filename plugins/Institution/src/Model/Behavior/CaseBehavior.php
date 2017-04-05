<?php 
namespace Institution\Model\Behavior;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Routing\Router;

class CaseBehavior extends Behavior
{
	public function initialize(array $config)
	{
		parent::initialize($config);

		$this->_table->belongsToMany('LinkedCases', [
			'className' => 'Institution.InstitutionCases',
			'joinTable' => 'institution_cases_records',
			'foreignKey' => 'record_id',
			'targetForeignKey' => 'institution_case_id',
			'through' => 'Institution.InstitutionCasesRecords',
			'dependent' => true
		]);
	}

	public function implementedEvents()
	{
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
		$events['ControllerAction.Model.index.beforeQuery'] = 'indexBeforeQuery';
		$events['ControllerAction.Model.view.beforeQuery'] = 'viewBeforeQuery';
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
		return $events;
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options)
	{
		$model = $this->_table;

		$broadcaster = $model;
        $listeners = [];
        $listeners[] = TableRegistry::get('Institution.InstitutionCases');

        if (!empty($listeners)) {
            $model->dispatchEventToModels('Model.LinkedRecord.afterSave', [$entity], $broadcaster, $listeners);
        }
	}

	public function onGetLinkedCases(Event $event, Entity $entity)
	{
		$model = $this->_table;

		$linkedCases = [];
		if ($entity->has('linked_cases')) {
			foreach ($entity->linked_cases as $key => $linkedCaseEntity) {
				$primaryKey = $model->LinkedCases->primaryKey();
		        $id = null;
		    	$primaryKeyValue = [];
		    	if (is_array($primaryKey)) {
		    		foreach ($primaryKey as $key) {
						$primaryKeyValue[$key] = $linkedCaseEntity->getOriginal($key);
					}
		    	} else {
		    		$primaryKeyValue[$primaryKey] = $linkedCaseEntity->getOriginal($primaryKey);
		    	}

				$encodedKeys = $model->paramsEncode($primaryKeyValue);
				$id = $encodedKeys;

				$url = $event->subject()->HtmlField->link($linkedCaseEntity->title, [
					'plugin' => 'Institution',
					'controller' => 'Institutions',
					'action' => 'Cases',
					'view',
					$id
				]);

				$linkedCases[] = $url;
			}
		}

		return !empty($linkedCases) ? implode(", ", $linkedCases) : '';
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
    	$model = $this->_table;
    	$model->field('linked_cases', ['type' => 'chosenSelect']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
		$query->contain(['LinkedCases']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
    	$query->contain(['LinkedCases']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
    	$model = $this->_table;
    	$model->field('linked_cases', [
            'type' => 'chosenSelect',
            'before' => 'modified_user_id'
        ]);
    }
}

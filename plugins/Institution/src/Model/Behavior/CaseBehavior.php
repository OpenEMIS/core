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
	}

	public function implementedEvents()
	{
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        $events['ControllerAction.Model.delete.onInitialize'] = 'deleteOnInitialize';
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

	public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
    	$model = $this->_table;
    	$model->field('linked_cases', [
    		'type' => 'custom_linked_cases',
    		'valueClass' => 'table-full-width'
    	]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
    	$showFieldBefore = $entity->has('modified_user_id') ? 'modified_user_id' : 'create__user_id';

    	$model = $this->_table;
    	$model->field('linked_cases', [
    		'type' => 'custom_linked_cases',
    		'valueClass' => 'table-full-width',
    		'before' => $showFieldBefore
    	]);
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
	{
		$linkedCaseQuery = $this->getLinkedCaseQuery($entity);
		$linkedCaseCount = $linkedCaseQuery->count();

		$extra['associatedRecords'][] = ['model' => 'Linked Cases', 'count' => $linkedCaseCount];
    }

	public function onGetCustomLinkedCasesElement(Event $event, $action, $entity, $attr, $options=[])
    {
    	$model = $this->_table;

    	if ($action == 'index') {
    		$linkedCaseQuery = $this->getLinkedCaseQuery($entity);

			$attr['value'] = $linkedCaseQuery->count();
    	} else if ($action == 'view') {
			$tableHeaders = [__('Code'), __('Title'), __('Description')];
			$tableCells = [];

			$linkedCaseQuery = $this->getLinkedCaseQuery($entity);
			$linkedCaseResults = $linkedCaseQuery->all();
			if (!$linkedCaseResults->isEmpty()) {
				foreach ($linkedCaseResults as $key => $caseEntity) {
					$rowData = [];

					$id = $model->getEncodedKeys($caseEntity);
					$url = $event->subject()->Html->link($caseEntity->code, [
						'plugin' => 'Institution',
						'controller' => 'Institutions',
						'action' => 'Cases',
						'view',
						$id
					]);

					$rowData[] = $url;
					$rowData[] = $caseEntity->title;
					$rowData[] = nl2br(htmlspecialchars($caseEntity->description));

					$tableCells[] = $rowData;
				}
			}

			$attr['tableHeaders'] = $tableHeaders;
			$attr['tableCells'] = $tableCells;
    	}

    	return $event->subject()->renderElement('Institution.Cases/linked_cases', ['attr' => $attr]);
    }

    public function getLinkedCaseQuery(Entity $entity)
    {
    	$WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
    	$InstitutionCases = TableRegistry::get('Institution.InstitutionCases');

    	$feature = $WorkflowRules->getFeatureByEntity($entity);
    	$recordId = $entity->id;

    	$query = $InstitutionCases
			->find()
			->matching('LinkedRecords', function ($q) use ($feature, $recordId) {
				return $q->where([
					'feature' => $feature,
					'record_id' => $recordId
				]);
			});

		return $query;
    }
}

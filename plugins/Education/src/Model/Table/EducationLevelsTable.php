<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Http\ServerRequest;

use App\Model\Table\ControllerActionTable;

class EducationLevelsTable extends ControllerActionTable
{
	public function initialize(array $config): void
	{
		parent::initialize($config);
		$this->belongsTo('EducationLevelIsced', ['className' => 'Education.EducationLevelIsced']);
		$this->belongsTo('EducationSystems', ['className' => 'Education.EducationSystems']);
		$this->hasMany('EducationCycles', ['className' => 'Education.EducationCycles']);

		if ($this->behaviors()->has('Reorder')) {
			$reorderBehavior = $this->behaviors()->get('Reorder');
        	$reorderBehavior->setConfig('filter', 'education_system_id');

			// $this->behaviors()->get('Reorder')->config([
			// 	'filter' => 'education_system_id',
			// ]);
		}

		$this->setDeleteStrategy('restrict');
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->fields['education_level_isced_id']['sort'] = ['field' => 'EducationLevelIsced.name'];
		$this->fields['education_system_id']['sort'] = ['field' => 'EducationSystems.name'];

		// Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Education Levels','Education');
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
	}

    public function afterSave(Event $event, Entity $entity, ArrayObject $options): void
    {
        // Skip if triggered internally or no authenticated user
        if (!empty($options['skip_callbacks']) || empty($this->Auth) || empty($this->Auth->user())) {
            return;
        }

        $eventKey = $entity->isNew()
            ? 'education_level_create'
            : 'education_level_update';

        $this->triggerWebhookCommand($entity, $eventKey);
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options): void
    {
        // Skip if no authenticated user
        if (empty($this->Auth) || empty($this->Auth->user())) {
            return;
        }

        $this->triggerWebhookCommand($entity, 'education_level_delete');
    }

    /**
     * Shared webhook trigger for education level events.
     */
    private function triggerWebhookCommand(Entity $entity, string $eventKey): void
    {
        $body = $entity->toArray();

        // Add metadata for delete tracking if applicable
        if ($eventKey === 'education_level_delete') {
            $body['deleted_at'] = date('Y-m-d H:i:s');
            $body['deleted_by'] = $this->Auth->user()['openemis_no']
                ?? $this->Auth->user()['username']
                ?? 'system';
        }

        $Webhooks = TableRegistry::getTableLocator()->get('Configuration.ConfigWebhooks');
        $Webhooks->triggerCommand($eventKey, $body);
    }

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
	{
		$query->where([$this->aliasField('education_system_id') => $entity->education_system_id]);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$serverRequest = $this->request;
		// Academic period filter
	    $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($serverRequest->getQuery('academic_period_id')) ?$serverRequest->getQuery('academic_period_id') : $this->EducationSystems->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        // Education System filter
        $systemOptions = $this->EducationSystems->getSystemOptions($selectedAcademicPeriod);

        if (!empty($systemOptions )) {
        	$selectedSystem = !empty($serverRequest->getQuery('system')) ? $serverRequest->getQuery('system') : key($systemOptions);
        } else {
        	$systemOptions = ['0' => '-- '.__('No Education System').' --'] + $systemOptions;
        	$selectedSystem = !empty($serverRequest->getQuery('system')) ? $serverRequest->getQuery('system') : 0;
        }

        $this->controller->set(compact('systemOptions', 'selectedSystem'));
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where([$this->aliasField('education_system_id') => $selectedSystem])
                        ->order([$this->aliasField('order')]);

        //sort
		$sortList = ['name', 'EducationLevelIsced.name', 'EducationSystems.name'];
		if (array_key_exists('sortWhitelist', $extra['options'])) {
			$sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
		}
		$extra['options']['sortWhitelist'] = $sortList;
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('education_system_id');
		$this->fields['education_level_isced_id']['type'] = 'select';
	}

	public function onUpdateFieldEducationSystemId(Event $event, array $attr, $action, ServerRequest $request)
	{
		list($systemOptions, $selectedSystem) = array_values($this->getSelectOptions());
		$attr['options'] = $systemOptions;
		if ($action == 'add') {
			$attr['default'] = $selectedSystem;
		}

		return $attr;
	}

	public function findWithSystem(Query $query, array $options)
	{
		return $query
			->contain(['EducationSystems'])
			->order(['EducationSystems.order' => 'ASC', $this->aliasField('order') => 'ASC']);
	}

	public function getSelectOptions()
	{
		 //Return all required options and their key
		$systemOptions = $this->EducationSystems
			->find('list')
			->find('visible')
			->find('order')
			->toArray();
		$selectedSystem = !is_null($this->request->getQuery('system')) ? $this->request->getQuery('system') : key($systemOptions);

		return compact('systemOptions', 'selectedSystem');
	}

	public function getEducationLevelOptions($selectedAcademicPeriod)
	{
		$educationSystems = TableRegistry::get('Education.EducationSystems');

		$list = $this
			->find('list', ['keyField' => 'id', 'valueField' => 'system_level_name'])
			->find('visible')
			->contain(['EducationSystems'])
			->where([$educationSystems->aliasField('academic_period_id') => $selectedAcademicPeriod])
			->order([
				$this->EducationSystems->aliasField('order'),
				$this->aliasField('order')
			])
			->toArray();

		return $list;
	}

	public function getLevelOptions($selectedAcademicPeriod = null)
	{
		//POCOR-5973 starts
		$systemOptions = $this->EducationSystems->getSystemOptions($selectedAcademicPeriod);
		if(!empty($systemOptions)){
			$list = $this
					->find('list', ['keyField' => 'id', 'valueField' => 'system_level_name'])
					->find('visible')
					->contain(['EducationSystems'])
					->where([$this->aliasField('education_system_id') . ' IN (' .  implode(',',array_keys($systemOptions)) . ')'])
					->order([
						$this->EducationSystems->aliasField('order'),
						$this->aliasField('order')
					])
					->toArray();
		}else{
			$educationSystems = TableRegistry::get('Education.EducationSystems');
			$list = $this
					->find('list', ['keyField' => 'id', 'valueField' => 'system_level_name'])
					->find('visible')
					->contain(['EducationSystems'])
					->where([$educationSystems->aliasField('academic_period_id') => $selectedAcademicPeriod])
					->order([
						$this->EducationSystems->aliasField('order'),
						$this->aliasField('order')
					])
					->toArray();
		}//POCOR-5973 ends
		return $list;
	}

    public function getLevelOptionsByInstitution($institutionId)
    {
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

        $list = $InstitutionGrades
            ->find()
            ->select(['level_id' => 'EducationLevels.id', 'level_name' => 'EducationLevels.name', 'system_name' => 'EducationSystems.name'])
            ->matching('EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems')
            ->where([$InstitutionGrades->aliasField('institution_id') => $institutionId])
            ->order(['EducationSystems.order', 'EducationLevels.order'])
            ->group(['level_id'])
            ->toArray();

        $returnList = [];
        foreach ($list as $key => $value) {
            $returnList[$value->level_id] = $value->system_name . " - " . $value->level_name;
        }

        return $returnList;
    }

    //updating type of academic period
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        list(,,  $systemOptions, $selectedSystem) = array_values($this->getSelectOptions());
        $attr['options'] = $cycleOptions;
        if ($action == 'add') {
            $attr['default'] = $selectedCycle;
        }

        return $attr;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'name') {
            return __('Name');
        } elseif ($field == 'education_system_id') {
            return __('Education Systems');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'education_level_isced_id') {
            return __('Education Level');
        }elseif ($field == 'visible') {
            return __('Visible');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

}

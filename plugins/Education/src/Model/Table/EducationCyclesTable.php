<?php
namespace Education\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;

use App\Model\Table\ControllerActionTable;

class EducationCyclesTable extends ControllerActionTable
{
	public function initialize(array $config): void
	{
		parent::initialize($config);
		$this->belongsTo('EducationLevels', ['className' => 'Education.EducationLevels','foreignKey' => 'education_level_id']);
        $this->hasMany('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);

		if ($this->behaviors()->has('Reorder')) {
			$reorderBehavior = $this->behaviors()->get('Reorder');
			$reorderBehavior->setConfig('filter', 'education_level_id');
		}
		if ($this->behaviors()->has('ControllerAction')) {
            $controllerActionBehavior = $this->behaviors()->get('ControllerAction');
            $controllerActionBehavior->setConfig(['actions' => ['reorder' => false]]);
        }
        $this->addBehavior('Configuration.CallWebhook', // POCOR-9403
            [
                'entity_create' => 'education_cycle_create',
                'entity_delete' => 'education_cycle_delete',
                'entity_update' => 'education_cycle_update',
                'table_alias' => 'Education.EducationCycles',
                'contain' => []
            ]
        ); // for webhook
		$this->setDeleteStrategy('restrict');
	}

	public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
	{
		$this->fields['education_level_id']['sort'] = ['field' => 'EducationLevels.name'];

		// Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Education Cycles','Education');
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

	public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
	{
		$query->where([$this->aliasField('education_level_id') => $entity->education_level_id]);
	}

	//POCOR-9365 -- start
	public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra) {
        if ($this->hasAssociatedRecords($this, $entity, $extra)) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        }
    }
	//POCOR-9365 -- end

	public function validationDefault(Validator $validator): Validator
	{
		$validator = parent::validationDefault($validator);
		$validator
	        ->add('admission_age', [
                'ruleRange' => [
                    'rule' => ['range', 0, 99]
                ]
            ])
	    ;
		return $validator;
	}

	public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
	{
		$serverRequest = $this->request;
        // Academic period filter
	    $EducationSystems = TableRegistry::getTableLocator()->get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($serverRequest->getQuery('academic_period_id')) ? $serverRequest->getQuery('academic_period_id') : $this->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        //level filter
        $levelOptions = $this->EducationLevels->getEducationLevelOptions($selectedAcademicPeriod);
        if (!empty($levelOptions)) {
        	$selectedLevel = !empty($serverRequest->getQuery('level')) ? $serverRequest->getQuery('level') : key($levelOptions);
        } else {
            $levelOptions = ['0' => '-- '.__('No Education Level').' --'] + $levelOptions;
            $selectedLevel = !empty($serverRequest->getQuery('level')) ? $serverRequest->getQuery('level') : 0;
        }

        $this->controller->set(compact('levelOptions', 'selectedLevel'));
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
		$query->where([$this->aliasField('education_level_id') => $selectedLevel])
                        ->order([$this->aliasField('order') => 'ASC']);

		$sortList = ['name','EducationLevels.name'];
		if (array_key_exists('sortWhitelist', $extra['options'])) {
			$sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
		}
		$extra['options']['sortWhitelist'] = $sortList;
	}

	public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
	{
        $this->field('education_level_id');
		$this->field('admission_age', ['after' => 'name', 'attr' => ['min' => 0, 'max' => 99]]);
	}

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options): void
    {
        // --- andle admission age cascade update ---
        if (!$entity->isNew()) {
            $this->updateAdmissionAgeCascade($entity); // POCOR-9403 cleancoded
        }

    }

    /**
     * Propagate admission age changes to related grades
     */
    private function updateAdmissionAgeCascade(Entity $entity): void
    {
        $original = $entity->extractOriginal(['admission_age']);
        $originalAdmissionAge = $original['admission_age'] ?? null;
        $newAdmissionAge = $entity->admission_age ?? null;

        // Skip if unchanged
        if ($originalAdmissionAge === $newAdmissionAge) {
            return;
        }

        $educationProgrammes = $this->EducationProgrammes->find()
            ->where([$this->EducationProgrammes->aliasField('education_cycle_id') => $entity->id])
            ->all();

        if ($educationProgrammes->isEmpty()) {
            return;
        }

        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');

        foreach ($educationProgrammes as $programme) {
            $grades = $EducationGrades->find()
                ->where([$EducationGrades->aliasField('education_programme_id') => $programme->id])
                ->order([$EducationGrades->aliasField('order')])
                ->all();

            if ($grades->isEmpty()) {
                continue;
            }

            foreach ($grades as $index => $grade) {
                $newGradeAge = $newAdmissionAge + $index;
                $EducationGrades->updateAll(
                    ['admission_age' => $newGradeAge],
                    ['id' => $grade->id]
                );
            }
        }
    }

	public function onUpdateFieldEducationLevelId(EventInterface $event, array $attr, $action, ServerRequest $request)
	{
        //echo $this->ControllerAction; exit;
        list($levelOptions, $selectedLevel) = array_values($this->getSelectOptions());
		$attr['options'] = $levelOptions;
		if ($action == 'add') {
			$attr['default'] = $selectedLevel;
		}

		return $attr;
	}

	public function getSelectOptions()
	{
        // Academic period filter
	    $EducationSystems = TableRegistry::getTableLocator()->get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

		//Return all required options and their key
		$levelOptions = $this->EducationLevels->getLevelOptions($selectedAcademicPeriod);
		$selectedLevel = !is_null($this->request->getQuery('level')) ? $this->request->getQuery('level') : key($levelOptions);

		return compact('levelOptions', 'selectedLevel');
	}

	public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'name') {
            return __('Name');
        } elseif ($field == 'education_level_id') {
            return __('Education Level');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'admission_age') {
            return __('Admission Age');
        }elseif ($field == 'visible') {
            return __('Visible');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}

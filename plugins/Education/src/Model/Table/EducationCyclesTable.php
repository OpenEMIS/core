<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class EducationCyclesTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->belongsTo('EducationLevels', ['className' => 'Education.EducationLevels']);
		$this->hasMany('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);

		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'education_level_id',
			]);
		}

		$this->setDeleteStrategy('restrict');
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->fields['education_level_id']['sort'] = ['field' => 'EducationLevels.name'];
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
	{
		$query->where([$this->aliasField('education_level_id') => $entity->education_level_id]);
	}

	public function validationDefault(Validator $validator)
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

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		// Academic period filter
	    $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        //level filter
        $levelOptions = $this->EducationLevels->getEducationLevelOptions($selectedAcademicPeriod);
        if (!empty($levelOptions)) {
        	$selectedLevel = !empty($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);
        } else {
            $levelOptions = ['0' => '-- '.__('No Education Level').' --'] + $levelOptions;
            $selectedLevel = !empty($this->request->query('level')) ? $this->request->query('level') : 0;
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

	public function addEditBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('education_level_id');
		$this->field('admission_age', ['after' => 'name', 'attr' => ['min' => 0, 'max' => 99]]);
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options)
	{
		// update the admission age in education grade if there is changes on the admission age
		if (!$entity->isNew()) {
			$originalEntity = $entity->extractOriginal(['admission_age']);
			$originalAdmissionAge = $originalEntity['admission_age'];
			$admissionAge = $entity->admission_age;

			if ($originalAdmissionAge != $admissionAge) {
				$educationCycleId = $entity->id;

				$educationProgrammeRecords = $this->EducationProgrammes->find()
					->where([$this->EducationProgrammes->aliasField('education_cycle_id') => $entity->id])
					->all()
				;

				if (!$educationProgrammeRecords->isEmpty()) {
					$EducationGrades = TableRegistry::get('Education.EducationGrades');
					foreach ($educationProgrammeRecords as $programmeKey => $programmeObj) {
						$educationProgrammeId = $programmeObj->id;

						$educationGradeRecords = $EducationGrades->find()
							->where([$EducationGrades->aliasField('education_programme_id') => $educationProgrammeId])
							->order([$EducationGrades->aliasField('order')])
							->all()
						;

						if (!$educationGradeRecords->isEmpty()) {
							foreach ($educationGradeRecords as $gradeKey => $gradeObj) {
								$EducationGrades->updateAll(
									['admission_age' => $admissionAge + $gradeKey],
									['id' => $gradeObj->id] // condition
								);
							}
						}
					}
				}
			}
		}
	}

	public function onUpdateFieldEducationLevelId(Event $event, array $attr, $action, Request $request)
	{
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
	    $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

		//Return all required options and their key
		$levelOptions = $this->EducationLevels->getLevelOptions($selectedAcademicPeriod);
		$selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

		return compact('levelOptions', 'selectedLevel');
	}
}

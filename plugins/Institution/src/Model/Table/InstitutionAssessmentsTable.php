<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class InstitutionAssessmentsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('institution_classes');
		parent::initialize($config);

		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

		$this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades', 'dependent' => true]);
		$this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'dependent' => true]);
		$this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true]);

		$this->behaviors()->get('ControllerAction')->config('actions.add', false);
		$this->behaviors()->get('ControllerAction')->config('actions.search', false);
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('class_number', ['visible' => false]);
		$this->field('staff_id', ['visible' => false]);
		$this->field('institution_shift_id', ['visible' => false]);
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$extra['elements']['controls'] = ['name' => 'Institution.Assessment/controls', 'data' => [], 'options' => [], 'order' => 1];

		$this->field('assessment');
		$this->field('education_grade');
		$this->field('subjects');
		$this->field('male_students');
		$this->field('female_students');

		$this->setFieldOrder(['name', 'assessment', 'academic_period_id', 'education_grade', 'subjects', 'male_students', 'female_students']);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$session = $this->request->session();
		$institutionId = $session->read('Institution.Institutions.id');
		
		$Classes = TableRegistry::get('Institution.InstitutionClasses');
		$ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
		$Assessments = TableRegistry::get('Assessment.Assessments');
		$EducationGrades = TableRegistry::get('Education.EducationGrades');
		$EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

		$query
			->select([
				'institution_class_id' => $ClassGrades->aliasField('institution_class_id'),
				'education_grade_id' => $Assessments->aliasField('education_grade_id'),
				'assessment_id' => $Assessments->aliasField('id'),
				'assessment' => $query->func()->concat([
					$Assessments->aliasField('code') => 'literal',
					" - ",
					$Assessments->aliasField('name') => 'literal'
				])
			])
			->innerJoin(
				[$ClassGrades->alias() => $ClassGrades->table()],
				[$ClassGrades->aliasField('institution_class_id = ') . $this->aliasField('id')]
			)
			->innerJoin(
				[$Assessments->alias() => $Assessments->table()],
				[
					$Assessments->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
					$Assessments->aliasField('education_grade_id = ') . $ClassGrades->aliasField('education_grade_id')
				]
			)
			->innerJoin(
				[$EducationGrades->alias() => $EducationGrades->table()],
				[$EducationGrades->aliasField('id = ') . $Assessments->aliasField('education_grade_id')]
			)
			->innerJoin(
				[$EducationProgrammes->alias() => $EducationProgrammes->table()],
				[$EducationProgrammes->aliasField('id = ') . $EducationGrades->aliasField('education_programme_id')]
			)
			->group([
				$ClassGrades->aliasField('institution_class_id'),
				$Assessments->aliasField('id')
			])
			->order([
				$EducationProgrammes->aliasField('order'),
				$EducationGrades->aliasField('order'),
				$Assessments->aliasField('code'),
				$Assessments->aliasField('name'),
				$this->aliasField('name')
			])
			->autoFields(true)
			;
        
        // For filtering all classes and my classes
        $AccessControl = $this->AccessControl;
        $userId = $session->read('Auth.User.id');
        $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
        if (!$AccessControl->isAdmin()) 
        {
            if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles))
            {
                if (!$AccessControl->check(['Institutions', 'Classes', 'index'], $roles)) 
                {
                    $query->where(['1 = 0'], [], true);
                } else 
                {
                    $query->innerJoin(['InstitutionClasses' => 'institution_classes'], [
                        'InstitutionClasses.id = '.$ClassGrades->aliasField('institution_class_id'),
                        'InstitutionClasses.staff_id' => $userId
                    ]);
                }
            }   
        }
		
		// Academic Periods
		$periodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true, 'isEditable' => true]);
		if (is_null($this->request->query('academic_period_id'))) {
			// default to current Academic Period
			$this->request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
		}
		$selectedPeriod = $this->queryString('academic_period_id', $periodOptions);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noAssessments')),
			'callable' => function($id) use ($Classes, $ClassGrades, $Assessments, $institutionId) {
				return $Classes
					->find()
					->innerJoin(
						[$ClassGrades->alias() => $ClassGrades->table()],
						[
							$ClassGrades->aliasField('institution_class_id = ') . $Classes->aliasField('id')
						]
					)
					->innerJoin(
						[$Assessments->alias() => $Assessments->table()],
						[
							$Assessments->aliasField('academic_period_id = ') . $Classes->aliasField('academic_period_id'),
							$Assessments->aliasField('education_grade_id = ') . $ClassGrades->aliasField('education_grade_id')
						]
					)
					->where([
						$Classes->aliasField('institution_id') => $institutionId,
						$Classes->aliasField('academic_period_id') => $id
					])
					->count();
			}
		]);
		$this->controller->set(compact('periodOptions', 'selectedPeriod'));
		// End
		
		if (!empty($selectedPeriod)) {
			$query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);

			// Assessments
			$assessmentOptions = $Assessments
				->find('list')
				->where([$Assessments->aliasField('academic_period_id') => $selectedPeriod])
				->toArray();
			$assessmentOptions = ['-1' => __('All Assessments')] + $assessmentOptions;
			$selectedAssessment = $this->queryString('assessment_id', $assessmentOptions);
			$this->advancedSelectOptions($assessmentOptions, $selectedAssessment, [
				'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
				'callable' => function($id) use ($Classes, $ClassGrades, $Assessments, $institutionId, $selectedPeriod) {
					if ($id == -1) { return 1; }
					$selectedGrade = $Assessments->get($id)->education_grade_id;
					return $Classes
						->find()
						->innerJoin(
							[$ClassGrades->alias() => $ClassGrades->table()],
							[
								$ClassGrades->aliasField('institution_class_id = ') . $Classes->aliasField('id'),
								$ClassGrades->aliasField('education_grade_id') => $selectedGrade
							]
						)
						->where([
							$Classes->aliasField('institution_id') => $institutionId,
							$Classes->aliasField('academic_period_id') => $selectedPeriod
						])
						->count();
				}
			]);
			$this->controller->set(compact('assessmentOptions', 'selectedAssessment'));
			// End

			if ($selectedAssessment != '-1') {
				$query->where([$Assessments->aliasField('id') => $selectedAssessment]);
			}
		}
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
		if ($field == 'name') {
			return __('Class Name');
		} else {
			return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		}
	}

	public function onGetEducationGrade(Event $event, Entity $entity) {
		$EducationGrades = TableRegistry::get('Education.EducationGrades');
		$grade = $EducationGrades->get($entity->education_grade_id);

		return $grade->programme_grade_name;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
    	$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);pr($buttons);
    	if (isset($buttons['view']['url'])) {
    		$buttons['view']['url'] = [
				'plugin' => $this->controller->plugin,
				'controller' => $this->controller->name,
				'action' => 'Results',
				'class_id' => $entity->institution_class_id,
				'assessment_id' => $entity->assessment_id
			];
		}
    	unset($buttons['edit']);//remove edit action from the action button
    	unset($buttons['remove']);// remove delete action from the action button
    	return $buttons;
    }
}

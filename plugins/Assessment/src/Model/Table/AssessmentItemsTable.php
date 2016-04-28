<?php
namespace Assessment\Model\Table;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use Cake\Network\Session;

class AssessmentItemsTable extends AssessmentsAppTable {

	public function initialize(array $config) 
	{
		parent::initialize($config);
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
		$this->belongsTo('GradingTypes', ['className' => 'Assessment.AssessmentGradingTypes', 'foreignKey' => 'assessment_grading_type_id']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
		$this->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->fields['assessment_id']['type'] = 'hidden';
		$this->fields['id']['type'] = 'hidden';
		$this->fields['education_subject_id']['type'] = 'readonly';
		$this->fields['academic_period_id']['type'] = 'select';
		$this->fields['weight']['type'] = 'string';

		$this->fields['assessment_grading_type_id']['type'] = 'select';
		$this->fields['assessment_grading_type_id']['options'] = $this->GradingTypes->getList()->toArray();
		$this->fields['assessment_grading_type_id']['required'] = true;
		$this->fields['grading_type.result_type'] = [
			'type' => 'string',
			'field' => 'result_type',
		];
		$this->fields['grading_type.pass_mark'] = [
			'type' => 'string',
			'field' => 'pass_mark',
		];
		$this->fields['grading_type.max'] = [
			'type' => 'string',
			'field' => 'max',
		];
	}

	public function getFormFields($action = 'edit') 
	{
		if ($action=='add') {
			return ['education_subject_id'=>'', 'assessment_grading_type_id'=>'', 'weight'=>''];
		} else if ($action=='edit') {
			return ['education_subject_id'=>'', 'assessment_id'=>'', 'assessment_grading_type_id'=>'', 'weight'=>'', 'id'=>''];
		} else {
			return ['education_subject_id'=>'', 'assessment_grading_type_id'=>'', 'grading_type.result_type'=>'', 'grading_type.pass_mark'=>'', 'grading_type.max'=>'', 'weight'=>''];
		}
	}

	public function validationDefault(Validator $validator) 
	{
		$validator
			->requirePresence('assessment_id', 'update')
			->requirePresence('assessment_grading_type_id')
			->allowEmpty('weight')
			->add('weight', 'ruleIsDecimal', [
			    'rule' => ['decimal', null],
			])
			;
		return $validator;
	}

	public function populateAssessmentItemsArray(Entity $entity, $gradeId) 
	{
		$EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
		$gradeSubjects = $EducationGradesSubjects->find()
			->contain('EducationSubjects')
			->where([$EducationGradesSubjects->aliasField('education_grade_id') => $gradeId])
			->toArray();

		$assessmentItems = [];
		foreach ($gradeSubjects as $key => $gradeSubject) {
			if (!empty($gradeSubject->education_subject)) {
				$assessmentItems[] = [
				    'id' => '',
				    'assessment_id' => $entity->id,
					'education_subject_id' => $gradeSubject->education_subject->id,
				    'assessment_grading_type_id' => '',
					'weight' => '',
				];
			}
		}
		return $assessmentItems;
	}

	/**
	 *	Function to get the assessment items id and the subject name and the result type
	 *
	 *	@param integer $assessmentId The assessment ID
	 *
	 *	@return array The array containing the assessment item id, subject name and the result type
	 */
	public function getAssessmentItemSubjects($assessmentId) 
	{
		$subjectList = $this
			->find()
			->matching('EducationSubjects')
			->where([$this->aliasField('assessment_id') => $assessmentId])
			->select([
				'id' => $this->aliasField('id'), 
				'name' => 'EducationSubjects.name', 
				'type' => $this->aliasField('mark_type'),
				'max' => $this->aliasField('max')
			])
			->order(['EducationSubjects.order'])
			->hydrate(false)
			->toArray();
		return $subjectList;
	}

	public function findStaffSubjects(Query $query, array $options) 
	{	
		if (isset($options['class_id'])) {
			$classId = $options['class_id'];
			$session = new Session;
			$userId = $session->read('Auth.User.id');

			$query->where([
					'OR' => [
						// first condition if the current user is a teacher for this subject
						'EXISTS (
							SELECT 1 
							FROM institution_subjects InstitutionSubjects
							INNER JOIN institution_class_subjects InstitutionClassSubjects
								ON InstitutionClassSubjects.institution_class_id = '.$classId.'
								AND InstitutionClassSubjects.institution_subject_id = InstitutionSubjects.id
							INNER JOIN institution_subject_staff InstitutionSubjectStaff
								ON InstitutionSubjectStaff.institution_subject_id = InstitutionSubjects.id
								AND InstitutionSubjectStaff.staff_id = '.$userId.'
							WHERE InstitutionSubjects.education_subject_id = ' . $this->aliasField('education_subject_id') .')',

						// second condition if the current user is the homeroom teacher of the subject class
						'EXISTS (
							SELECT 1 
							FROM institution_classes InstitutionClasses
							INNER JOIN institution_class_subjects InstitutionClassSubjects
								ON InstitutionClassSubjects.institution_class_id = InstitutionClasses.id
							INNER JOIN institution_subjects InstitutionSubjects
								ON InstitutionSubjects.id = InstitutionClassSubjects.institution_subject_id
							WHERE InstitutionClasses.staff_id = '.$userId.' 
								AND InstitutionClasses.id = '.$classId.' 
								AND InstitutionSubjects.education_subject_id = '.$this->aliasField('education_subject_id').')'
					]
				]);

			return $query;
		}
	}
}

<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSubjectStudentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
		$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);

		$this->belongsTo('ClassStudents', [
			'className' => 'Institution.InstitutionClassStudents',
			'foreignKey' => [
				'institution_class_id',
				'student_id'
			],
			'bindingKey' => [
				'institution_class_id',
				'student_id'
			]
		]);

	}

	public function getMaleCountBySubject($subjectId) {
		$gender_id = 1; // male
		$count = $this
			->find()
			->contain('Users')
			->where([$this->Users->aliasField('gender_id') => $gender_id])
			->where([$this->aliasField('institution_subject_id') => $subjectId])
			->count()
		;
		return $count;
	}

	public function getFemaleCountBySubject($subjectId) {
		$gender_id = 2; // female
		$count = $this
			->find()
			->contain('Users')
			->where([$this->Users->aliasField('gender_id') => $gender_id])
			->where([$this->aliasField('institution_subject_id') => $subjectId])
			->count()
		;
		return $count;
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		//PHPOE-2338 - implement afterDelete to delete records in AssessmentItemResultsTable
		// find related classes and grades
		$InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
		$institutionClassData = $InstitutionSubjects->find()
			->contain('InstitutionClasses.InstitutionClassGrades')
			->where([$InstitutionSubjects->aliasField($InstitutionSubjects->primaryKey()) => $entity->institution_subject_id])
			->first()
			;
		$gradeArray = [];
		if (!empty($institutionClassData->institution_classes)) {
			foreach ($institutionClassData->institution_classes as $skey => $svalue) {
				if (!empty($svalue->institution_class_grades)) {
					foreach ($svalue->institution_class_grades as $gkey => $gvalue) {
						$gradeArray[] = $gvalue->education_grade_id;
					}
				}
			}
		}
		$gradeArray = array_unique($gradeArray);

		$AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
		// conditions: 'assessment_item_results' removing from student_id, institution_id, academic_period_id, assessment_item_id->education_subject_id; 
		$deleteAssessmentItemResults = $AssessmentItemResults->find()
			->where([
				$AssessmentItemResults->aliasField('student_id') => $entity->student_id, 
				$AssessmentItemResults->aliasField('institution_id') => $institutionClassData->institution_id, 
				$AssessmentItemResults->aliasField('academic_period_id') => $institutionClassData->academic_period_id, 
				
			])
			;

		if (!empty($gradeArray)) {
			$deleteAssessmentItemResults->matching('AssessmentItems.Assessments', function ($q) use ($gradeArray) {
			    return $q->where(['Assessments.education_grade_id IN ' => $gradeArray]);
			})
			;
		}

		foreach ($deleteAssessmentItemResults as $key => $value) {
			$AssessmentItemResults->delete($value);
		}
	}

}

<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionClassStudentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
		$this->belongsTo('InstitutionSections', ['className' => 'Institution.InstitutionSections']);
	}

	public function getMaleCountBySubject($classId) {
		$gender_id = 1; // male
		$count = $this
			->find()
			->contain('Users')
			->where([$this->Users->aliasField('gender_id') => $gender_id])
			->where([$this->aliasField('institution_class_id') => $classId])
			->count()
		;
		return $count;
	}

	public function getFemaleCountBySubject($classId) {
		$gender_id = 2; // female
		$count = $this
			->find()
			->contain('Users')
			->where([$this->Users->aliasField('gender_id') => $gender_id])
			->where([$this->aliasField('institution_class_id') => $classId])
			->count()
		;
		return $count;
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		//PHPOE-2338 - implement afterDelete to delete records in AssessmentItemResultsTable
		// find related sections and grades
		$InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
		$institutionClassData = $InstitutionClasses->find()
			->contain('InstitutionSections.InstitutionSectionGrades')
			->where([$InstitutionClasses->aliasField($InstitutionClasses->primaryKey()) => $entity->institution_class_id])
			->first()
			;
		$gradeArray = [];
		if (!empty($institutionClassData->institution_sections)) {
			foreach ($institutionClassData->institution_sections as $skey => $svalue) {
				if (!empty($svalue->institution_section_grades)) {
					foreach ($svalue->institution_section_grades as $gkey => $gvalue) {
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

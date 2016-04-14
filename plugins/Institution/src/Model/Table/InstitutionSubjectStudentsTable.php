<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
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

	public function findResults(Query $query, array $options) {
		$institutionId = $options['institution_id'];
		$classId = $options['class_id'];
		$assessmentId = $options['assessment_id'];
		$periodId = $options['academic_period_id'];
		$subjectId = $options['subject_id'];

		$Users = $this->Users;
		$InstitutionSubjects = $this->InstitutionSubjects;
		$ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');

		$query
			->select([
				'uuid' => $ItemResults->aliasField('id'),
				'marks' => $ItemResults->aliasField('marks'),
				'assessment_period_id' => $ItemResults->aliasField('assessment_period_id'),
				'student_id' => $this->aliasField('student_id'),
				'total_mark' => $this->aliasField('total_mark'),
				'openemis_no' => $Users->aliasField('openemis_no'),
				'name' => $query->func()->concat([
					$Users->aliasField('first_name') => 'literal',
					" ",
					$Users->aliasField('last_name') => 'literal'
				]),
				'first_name' => $Users->aliasField('first_name'),
				'middle_name' => $Users->aliasField('middle_name'),
				'third_name' => $Users->aliasField('third_name'),
				'last_name' => $Users->aliasField('last_name')
			])
			->innerJoin(
				[$InstitutionSubjects->alias() => $InstitutionSubjects->table()],
				[
					$InstitutionSubjects->aliasField('id = ') . $this->aliasField('institution_subject_id'),
					$InstitutionSubjects->aliasField('institution_id') => $institutionId,
					$InstitutionSubjects->aliasField('academic_period_id') => $periodId,
					$InstitutionSubjects->aliasField('education_subject_id') => $subjectId
				]
			)
			->leftJoin(
				[$ItemResults->alias() => $ItemResults->table()],
				[
					$ItemResults->aliasField('student_id = ') . $this->aliasField('student_id'),
					$ItemResults->aliasField('assessment_id') => $assessmentId,
					$ItemResults->aliasField('institution_id') => $institutionId,
					$ItemResults->aliasField('academic_period_id') => $periodId,
					$ItemResults->aliasField('education_subject_id') => $subjectId
				]
			)
			->where([
				$this->aliasField('institution_class_id') => $classId
			])
			->group([
				$this->aliasField('student_id'),
				$ItemResults->aliasField('assessment_period_id')
			])
			->order([
				$this->aliasField('student_id')
			])
			;

		return $query;
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

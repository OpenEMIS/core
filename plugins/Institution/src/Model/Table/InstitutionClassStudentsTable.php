<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionClassStudentsTable extends AppTable {
	
	// For reports
	private $assessmentItemResults = [];

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('StudentStatuses',	['className' => 'Student.StudentStatuses']);
		$this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {

		$fields[] = [
			'key' => 'Institutions.code',
			'field' => 'code',
			'type' => 'string',
			'label' => '',
		];

		$fields[] = [
			'key' => 'InstitutionClasses.institution_id',
			'field' => 'institution_id',
			'type' => 'string',
			'label' => '',
		];

    	$sheet = $settings['sheet'];
    	$assessments = $sheet['assessments'];
    	foreach ($assessments as $assessment) {
    		$assessmentName = TableRegistry::get('Assessment.Assessments')->get($assessment)->name;
    		$assessmentSubjects = TableRegistry::get('Assessment.AssessmentItems')->getAssessmentItemSubjects($assessment);

    		foreach($assessmentSubjects as $subject) {
    			$label = __($assessmentName).' - '.__($subject['name']);
    			if ($subject['type'] == 'MARKS') {
    				$label = $label.' ('.$subject['max'].')';
    			}
    			$fields[] = [
	    			'key' => $subject['id'],
	    			'field' => 'assessment_item',
	    			'type' => 'assessment',
					'label' => $label,
					'institutionId' => $sheet['institutionId'],
					'assessmentId' => $assessment,
					'academicPeriodId' => $sheet['academicPeriodId'],
					'result_type' => $subject['type']
	    		];
    		}
    	}
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query) {
    	$query
    		->contain(['InstitutionClasses.Institutions'])
    		->select(['code' => 'Institutions.code', 'institution_id' => 'Institutions.name']);
    }

    public function onExcelRenderAssessment(Event $event, Entity $entity, array $attr) {
    	$studentId = $entity->student_id;
    	$assessmentItemId = $attr['key'];
    	$academicPeriodId = $attr['academicPeriodId'];
    	$institutionId = $attr['institutionId'];
    	$resultType = $attr['result_type'];
    	$assessmentItemResults = $this->assessmentItemResults;
    	if (!(isset($assessmentItemResults[$institutionId][$studentId][$assessmentItemId]))) {
    		$AssessmentItemResultsTable = TableRegistry::get('Assessment.AssessmentItemResults');
    		$this->assessmentItemResults = $AssessmentItemResultsTable->getAssessmentItemResults($institutionId, $academicPeriodId);
    		$assessmentItemResults = $this->assessmentItemResults;
    	}
    	if (isset($assessmentItemResults[$institutionId][$studentId][$assessmentItemId])) {
    		$result = $assessmentItemResults[$institutionId][$studentId][$assessmentItemId];
    		switch($resultType) {
    			case 'MARKS':
    				return '='.$result['marks'];
    				break;
    			case 'GRADES':
					return $result['grade_code'];
    				break;
    		}
    	}
    	return '';
    }

	public function getMaleCountBySection($classId) {
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

	public function getFemaleCountBySection($classId) {
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

	public function autoInsertClassStudent($data) {
		$studentId = $data['student_id'];
		$gradeId = $data['education_grade_id'];
		$classId = $data['institution_class_id'];

		$entity = $this->newEntity($data);

		$existingData = $this
			->find()
			->where(
				[
					$this->aliasField('student_id') => $studentId,
					$this->aliasField('education_grade_id') => $gradeId,
					$this->aliasField('institution_class_id') => $classId
				]
			)
			->first()
		;

		if (!empty($existingData)) {
			$entity->id = $existingData->id;
		}
		$this->save($entity);
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($entity->isNew()) {
			$data = [
				'institution_class_id' => $entity->institution_class_id,
				'student_id' => $entity->student_id
			];
			$this->_autoInsertSubjectStudent($data);
		}
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		// PHPOE-2338 - implement afterDelete in InstitutionClassStudentsTable.php to delete from InstitutionSubjectStudentsTable
		$this->_autoDeleteSubjectStudent($entity);
	}

	private function _autoDeleteSubjectStudent(Entity $entity) {
		$InstitutionSubjectStudentsTable = TableRegistry::get('Institution.InstitutionSubjectStudents');
		$deleteSubjectStudent = $InstitutionSubjectStudentsTable->find()
			->where([
				$InstitutionSubjectStudentsTable->aliasField('student_id') => $entity->student_id,
				$InstitutionSubjectStudentsTable->aliasField('institution_class_id') => $entity->institution_class_id
			])
			->toArray();
			foreach ($deleteSubjectStudent as $key => $value) {
				$InstitutionSubjectStudentsTable->delete($value);
			}
	}

	private function _autoInsertSubjectStudent($data) {
		$Classes = TableRegistry::get('Institution.InstitutionClasses');
		$Subjects = TableRegistry::get('Institution.InstitutionSubjects');
		$SubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');

		$record = $Classes->find()
			->contain([
				'InstitutionSubjects.InstitutionSubjectStudents', 
				'InstitutionSubjects.InstitutionClasses'
			])->where([
				$Classes->aliasField('id') => $data['institution_class_id']
			])->first();

		foreach ($record->institution_subjects as $subject) {
			$student = $Subjects->createVirtualEntity($data['student_id'], $subject, 'students');
			$SubjectStudents->save($student);
		}

	}

}

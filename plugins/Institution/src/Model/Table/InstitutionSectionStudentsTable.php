<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSectionStudentsTable extends AppTable {
	
	// For reports
	private $assessmentItemResults = [];

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionSections', ['className' => 'Institution.InstitutionSections']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('StudentCategories', ['className' => 'FieldOption.StudentCategories']);
		$this->belongsTo('StudentStatuses',	['className' => 'Student.StudentStatuses']);
		$this->hasMany('InstitutionSectionGrades', ['className' => 'Institution.InstitutionSectionGrade']);
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {

		$fields[] = [
			'key' => 'Institutions.code',
			'field' => 'code',
			'type' => 'string',
			'label' => '',
		];

		$fields[] = [
			'key' => 'InstitutionSections.institution_id',
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
    		->contain(['InstitutionSections.Institutions'])
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

	public function getMaleCountBySection($sectionId) {
		$gender_id = 1; // male
		$count = $this
			->find()
			->contain('Users')
			->where([$this->Users->aliasField('gender_id') => $gender_id])
			->where([$this->aliasField('institution_section_id') => $sectionId])
			->count()
		;
		return $count;
	}

	public function getFemaleCountBySection($sectionId) {
		$gender_id = 2; // female
		$count = $this
			->find()
			->contain('Users')
			->where([$this->Users->aliasField('gender_id') => $gender_id])
			->where([$this->aliasField('institution_section_id') => $sectionId])
			->count()
		;
		return $count;
	}

	public function getStudentCategoryList() {
		$query = $this->StudentCategories->getList();
		return $query->toArray();
	}

	public function autoInsertSectionStudent($data) {
		$securityUserId = $data['student_id'];
		$selectedGradeId = $data['education_grade_id'];
		$selectedSectionId = $data['institution_section_id'];

		if(!empty($selectedSectionId)) {
			$autoInsertData = $this->newEntity();

			$existingData = $this
				->find()
				->where(
					[
						$this->aliasField('student_id') => $securityUserId,
						$this->aliasField('education_grade_id') => $selectedGradeId,
						$this->aliasField('institution_section_id') => $selectedSectionId
					]
				)
				->first()
			;

			if(!empty($existingData)) {
				$existingData = $existingData->toArray();
				$autoInsertData->id = $existingData['id'];	
			}
			
			$autoInsertData->student_id = $securityUserId;
			$autoInsertData->education_grade_id = $selectedGradeId;
			$autoInsertData->institution_section_id = $selectedSectionId;

			if ($this->save($autoInsertData)) {
				$this->_autoInsertSubjectStudent($data);
			}
		}
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		// PHPOE-2338 - implement afterDelete in InstitutionSectionStudentsTable.php to delete from InstitutionClassStudentsTable
		$this->_autoDeleteSubjectStudent($entity);
	}

	private function _autoDeleteSubjectStudent(Entity $entity) {
		$InstitutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
		$deleteClassStudent = $InstitutionClassStudentsTable->find()
			->where([
				$InstitutionClassStudentsTable->aliasField('student_id') => $entity->student_id,
				$InstitutionClassStudentsTable->aliasField('institution_section_id') => $entity->institution_section_id
			])
			->toArray();
			foreach ($deleteClassStudent as $key => $value) {
				$InstitutionClassStudentsTable->delete($value);
			}
	}

	private function _autoInsertSubjectStudent($data) {
		$Classes = TableRegistry::get('Institution.InstitutionSections');
		$Subjects = TableRegistry::get('Institution.InstitutionClasses');
		$SubjectStudents = TableRegistry::get('Institution.InstitutionClassStudents');

		$record = $Classes->find()
			->contain([
				'InstitutionClasses.InstitutionClassStudents', 
				'InstitutionClasses.InstitutionSections'
			])->where([
				$Classes->aliasField('id') => $data['institution_section_id']
			])->first();

		foreach ($record->institution_classes as $class) {
			$student = $Subjects->createVirtualEntity($data['student_id'], $class, 'students');
			$SubjectStudents->save($student);
		}

	}

}

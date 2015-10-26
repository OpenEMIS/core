<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteSectionStudentsTable extends AppTable {
	
	// For reports
	private $assessmentItemResults = [];

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('StudentCategories', ['className' => 'FieldOption.StudentCategories']);

		$this->hasMany('InstitutionSiteSectionGrades', ['className' => 'Institution.InstitutionSiteSectionGrade']);
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
    	$sheet = $settings['sheet'];
    	$assessments = $sheet['assessments'];
    	foreach ($assessments as $assessment) {
    		$assessmentName = TableRegistry::get('Assessment.Assessments')->get($assessment)->name;
    		$assessmentSubjects = TableRegistry::get('Assessment.AssessmentItems')->getAssessmentItemSubjects($assessment);

    		foreach($assessmentSubjects as $subject) {
    			$fields[] = [
	    			'key' => $subject['id'],
	    			'field' => 'assessment_item',
	    			'type' => 'assessment',
					'label' => __($assessmentName).' - '.__($subject['name']),
					'institutionId' => $sheet['institutionId'],
					'assessmentId' => $assessment,
					'academicPeriodId' => $sheet['academicPeriodId'],
					'result_type' => $subject['type']
	    		];
    		}
    	}
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
    				return $result['marks'];
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
			->where([$this->aliasField('institution_site_section_id') => $sectionId])
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
			->where([$this->aliasField('institution_site_section_id') => $sectionId])
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
		$selectedSectionId = $data['institution_site_section_id'];

		if(!empty($selectedSectionId)) {
			$autoInsertData = $this->newEntity();

			$existingData = $this
				->find()
				->where(
					[
						$this->aliasField('student_id') => $securityUserId,
						$this->aliasField('education_grade_id') => $selectedGradeId,
						$this->aliasField('institution_site_section_id') => $selectedSectionId
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
			$autoInsertData->institution_site_section_id = $selectedSectionId;
			$autoInsertData->status = 1;

			if ($this->save($autoInsertData)) {
				$this->_autoInsertSubjectStudent($data);
			}
		}
	}

	private function _autoInsertSubjectStudent($data) {
		$Classes = TableRegistry::get('Institution.InstitutionSiteSections');
		$Subjects = TableRegistry::get('Institution.InstitutionSiteClasses');
		$SubjectStudents = TableRegistry::get('Institution.InstitutionSiteClassStudents');

		$record = $Classes->find()
			->contain([
				'InstitutionSiteClasses.InstitutionSiteClassStudents', 
				'InstitutionSiteClasses.InstitutionSiteSections'
			])->where([
				$Classes->aliasField('id') => $data['institution_site_section_id']
			])->first();

		foreach ($record->institution_site_classes as $class) {
			$student = $Subjects->createVirtualEntity($data['student_id'], $class, 'students');
			$SubjectStudents->save($student);
		}

	}

}

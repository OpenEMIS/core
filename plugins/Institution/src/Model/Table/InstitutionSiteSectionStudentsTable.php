<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteSectionStudentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('StudentCategories', ['className' => 'FieldOption.StudentCategories']);

		$this->hasMany('InstitutionSiteSectionGrades', ['className' => 'Institution.InstitutionSiteSectionGrade']);

        $this->addBehavior('HighChart', [
        	'number_of_students_by_grade' => [
        		'_function' => 'getNumberOfStudentsByGrade',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => 'Education']],
				'yAxis' => ['title' => ['text' => 'Total']]
			],
			'institution_site_section_student_grade' => [
        		'_function' => 'getNumberOfStudentsByGradeByInstitution'
			]
		]);

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
		$securityUserId = $data['security_user_id'];
		$selectedGradeId = $data['education_grade_id'];
		$selectedSectionId = $data['institution_site_section_id'];
		$selectedStudentCategoryId = $data['student_category_id'];

		if(!empty($selectedSectionId)) {
			$autoInsertData = $this->newEntity();

			$existingData = $this
				->find()
				->where(
					[
						$this->aliasField('security_user_id') => $securityUserId,
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
			
			$autoInsertData->security_user_id = $securityUserId;
			$autoInsertData->education_grade_id = $selectedGradeId;
			$autoInsertData->institution_site_section_id = $selectedSectionId;
			$autoInsertData->student_category_id = $selectedStudentCategoryId;
			$autoInsertData->status = 1;

			$this->save($autoInsertData);
		}
	}

	public function getNumberOfStudentsByGrade($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];
		foreach ($conditions as $key => $value) {
			$_conditions['InstitutionSiteSections.'.$key] = $value;
		}

		$AcademicPeriod = $this->InstitutionSiteSections->Institutions->InstitutionSiteProgrammes->AcademicPeriods;
		$currentYearId = $AcademicPeriod->getCurrent();
		$currentYear = $AcademicPeriod->get($currentYearId, ['fields'=>'name'])->name;

		$studentsByGradeConditions = [
			'InstitutionSiteSectionStudents.status' => 1,
			'InstitutionSiteSections.academic_period_id' => $currentYearId,
			'EducationGrades.id IS NOT NULL',
			'Genders.name IS NOT NULL'
		];
		$studentsByGradeConditions = array_merge($studentsByGradeConditions, $_conditions);
		$query = $this->find();
		$studentByGrades = $query
			->select([
				'InstitutionSiteSections.institution_site_id',
				'EducationGrades.id',
				'EducationGrades.name',
				'Users.id',
				'Genders.name',
				'total' => $query->func()->count('InstitutionSiteSectionStudents.id')
			])
			->contain([
				'EducationGrades',
				'InstitutionSiteSections',
				'Users.Genders'
			])
			->where($studentsByGradeConditions)
			->group([
				'InstitutionSiteSections.institution_site_id',
				'EducationGrades.id',
				'Genders.name'
			])
			->order(
				'EducationGrades.order'
			)
			->toArray()
			;
		$grades = [];
		
		$genderOptions = $this->Users->Genders->getList();
		$dataSet = array();
		foreach ($genderOptions as $key => $value) {
			$dataSet[$value] = array('name' => __($value), 'data' => array());
		}

		foreach ($studentByGrades as $key => $studentByGrade) {
			$gradeId = $studentByGrade->education_grade->id;
			$gradeName = $studentByGrade->education_grade->name;
			$gradeGender = $studentByGrade->user->gender->name;
			$gradeTotal = $studentByGrade->total;

			$grades[$gradeId] = $gradeName;

			foreach ($dataSet as $dkey => $dvalue) {
				if (!array_key_exists($gradeId, $dataSet[$dkey]['data'])) {
					$dataSet[$dkey]['data'][$gradeId] = 0;
				}
			}
			$dataSet[$gradeGender]['data'][$gradeId] = $gradeTotal;
		}

		$params['options']['subtitle'] = array('text' => 'For Year '. $currentYear);
		$params['options']['xAxis']['categories'] = array_values($grades);
		$params['dataSet'] = $dataSet;

		return $params;
	}

	public function getNumberOfStudentsByGradeByInstitution($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];
		foreach ($conditions as $key => $value) {
			$_conditions['InstitutionSiteSections.'.$key] = $value;
		}

		$studentsByGradeConditions = [
			'InstitutionSiteSectionStudents.status' => 1,
			'EducationGrades.id IS NOT NULL',
		];
		$studentsByGradeConditions = array_merge($studentsByGradeConditions, $_conditions);

		$query = $this->find();
		$studentByGrades = $query
			->select([
				'grade' => 'EducationGrades.name',
				'count' => $query->func()->count('InstitutionSiteSectionStudents.id')
			])
			->contain([
				'EducationGrades',
				'InstitutionSiteSections',
			])
			->where($studentsByGradeConditions)
			->group([
				'EducationGrades.id',
			])
			->toArray();

		$dataSet = [];
		foreach($studentByGrades as $value){
			$dataSet[] = [$value['grade'], $value['count']];
		}
		$params['dataSet'] = $dataSet;

		return $params;
	}

}

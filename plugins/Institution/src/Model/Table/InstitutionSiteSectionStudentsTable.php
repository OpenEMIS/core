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

		if($selectedSectionId != 0) {
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
}
<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
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
}

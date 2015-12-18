<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSectionStudentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionSections', ['className' => 'Institution.InstitutionSections']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('StudentCategories', ['className' => 'FieldOption.StudentCategories']);

		$this->hasMany('InstitutionSectionGrades', ['className' => 'Institution.InstitutionSectionGrade']);
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
			$autoInsertData->status = 1;

			if ($this->save($autoInsertData)) {
				$this->_autoInsertSubjectStudent($data);
			}
		}
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		// PHPOE-2338 - implement afterDelete in InstitutionSectionStudentsTable.php to delete from InstitutionClassStudentsTable
		$InstitutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
		$deleteClassStudent = $InstitutionClassStudentsTable->find()
			->where([
				$InstitutionClassStudentsTable->aliasField('student_id') => $entity->student_id,
				$InstitutionClassStudentsTable->aliasField('institution_section_id') => $entity->institution_section_id
			])
			->toArray();
			;
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

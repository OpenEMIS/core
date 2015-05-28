<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentSectionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_students');
		parent::initialize($config);

		// public $belongsTo = array(
		// 	'Students.Student',
		// 	'InstitutionSiteSection'
		// );
		
		// public $hasMany = array(
		// 	'InstitutionSiteSectionGrade'
		// );
	}

	public function index() {
		$this->controller->set('indexElements', []);
		$this->controller->set('modal', []);


		$alias = $this->alias();
		// pr($alias);
		$studentId = $this->Session->read('Student.id');

		$joins = [
				[
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => [
						"InstitutionSiteSection.id = $alias.institution_site_section_id"
					]
				],
				[
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => [
						"InstitutionSite.id = InstitutionSiteSection.institution_site_id"
					]
				],
				[
					'table' => 'academic_periods',
					'alias' => 'AcademicPeriod',
					'conditions' => [
						"AcademicPeriod.id = InstitutionSiteSection.academic_period_id",
						"AcademicPeriod.visible = 1"
					]
				],
				[
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'type' => 'LEFT',
					'conditions' => [
						"EducationGrade.id = InstitutionSiteSection.education_grade_id"
					]
				],
				[
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => [
						"Staff.id = InstitutionSiteSection.staff_id"
					]
				],
				[
					'table' => 'security_users',
					'alias' => 'SecurityUser',
					'conditions' => [
						"SecurityUser.id = Staff.security_user_id"
					]
				]
			];

		$fields = [
				"$this->alias.*", 'AcademicPeriod.name', 'InstitutionSite.name',
				'InstitutionSiteSection.name', 'EducationGrade.id', 'EducationGrade.name',
				'Staff.*', 'SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.last_name', 'SecurityUser.preferred_name'
			];

		$conditions = [
				"$alias.student_id" => $studentId,
				"$alias.status = 1"
			];
		$order = ["AcademicPeriod.order"];

		$query = $this->find()->hydrate(false)
					->join($joins)
					// ->fields($fields)
					->where($conditions)
					->order($order);

					// pr($query->toArray());
					// die;

		
		// $data = $this->find('all', array(
		// 	'recursive' => -1,
		// 	'fields' => array(
		// 		"$this->alias.*", 'AcademicPeriod.name', 'InstitutionSite.name',
		// 		'InstitutionSiteSection.name', 'EducationGrade.id', 'EducationGrade.name',
		// 		'Staff.*', 'SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.last_name', 'SecurityUser.preferred_name'
		// 	),
		// 	'joins' => array(
		// 		array(
		// 			'table' => 'institution_site_sections',
		// 			'alias' => 'InstitutionSiteSection',
		// 			'conditions' => array(
		// 				"InstitutionSiteSection.id = $alias.institution_site_section_id"
		// 			)
		// 		),
		// 		array(
		// 			'table' => 'institution_sites',
		// 			'alias' => 'InstitutionSite',
		// 			'conditions' => array(
		// 				"InstitutionSite.id = InstitutionSiteSection.institution_site_id"
		// 			)
		// 		),
		// 		array(
		// 			'table' => 'academic_periods',
		// 			'alias' => 'AcademicPeriod',
		// 			'conditions' => array(
		// 				"AcademicPeriod.id = InstitutionSiteSection.academic_period_id",
		// 				"AcademicPeriod.visible = 1"
		// 			)
		// 		),
		// 		array(
		// 			'table' => 'education_grades',
		// 			'alias' => 'EducationGrade',
		// 			'type' => 'LEFT',
		// 			'conditions' => array(
		// 				"EducationGrade.id = InstitutionSiteSection.education_grade_id"
		// 			)
		// 		),
		// 		array(
		// 			'table' => 'staff',
		// 			'alias' => 'Staff',
		// 			'conditions' => array(
		// 				"Staff.id = InstitutionSiteSection.staff_id"
		// 			)
		// 		),
		// 		array(
		// 			'table' => 'security_users',
		// 			'alias' => 'SecurityUser',
		// 			'conditions' => array(
		// 				"SecurityUser.id = Staff.security_user_id"
		// 			)
		// 		)
		// 	),
		// 	'conditions' => array(
		// 		"$alias.student_id" => $studentId,
		// 		"$alias.status = 1"
		// 	),
		// 	'order' => array("AcademicPeriod.order")
		// ));
		
		// foreach($data as $i => $obj) {
		// 	$sectionId = $obj[$this->alias]['institution_site_section_id'];
		// 	if(empty($obj['EducationGrade']['id'])){
		// 		$data[$i]['EducationGrade']['grades'] = $this->InstitutionSiteSectionGrade->getGradesBySection($sectionId);
		// 	}else{
		// 		$data[$i]['EducationGrade']['grades'] = $this->InstitutionSiteSection->getSingleGradeBySection($sectionId);
		// 	}
			
		// 	$data[$i]['Staff']['staff_name'] = ModelHelper::getName($obj['SecurityUser']);
		// }
		
		// if(empty($data)){
		// 	$this->Message->alert('general.noData');
		// }
		
		// $this->setVar(compact('data'));

	}


}
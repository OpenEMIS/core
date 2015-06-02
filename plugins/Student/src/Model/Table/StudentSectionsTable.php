<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentSectionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_section_students');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);

		$this->hasMany('Institution.InstitutionSiteSectionGrade');
	}

	public function index() {
		$this->controller->set('indexElements', []);
		$this->controller->set('modal', []);

		$alias = $this->alias();
		$securityUserID = $this->Session->read('Student.security_user_id');

		$joins = [
				[
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => [
						"InstitutionSiteSection.id = ".$this->aliasField('institution_site_section_id')
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
					'table' => 'security_users',
					'alias' => 'SecurityUser',
					'conditions' => [
						"SecurityUser.id = InstitutionSiteSection.security_user_id"
					]
				]
			];
		// $fields = [
			// 	"$this->alias.*", 'AcademicPeriod.name', 'InstitutionSite.name',
			// 	'InstitutionSiteSection.name', 'EducationGrade.id', 'EducationGrade.name',
			// 	'Staff.*', 'SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.last_name', 'SecurityUser.preferred_name'
			// ];

		$conditions = [
				$this->aliasField('security_user_id') => $securityUserID,
				$this->aliasField('status')." = 1"
			];
		$order = ["AcademicPeriod.order"];

		$query = $this->find()->hydrate(false)
					->join($joins)
					// ->fields($fields)
					->where($conditions)
					->order($order)
					->toArray();

		if(empty($data)){
			$this->Message->alert('general.noData');
		}

		// foreach($data as $i => $obj) {
		// 	$sectionId = $obj[$this->alias]['institution_site_section_id'];
		// 	if(empty($obj['EducationGrade']['id'])){
		// 		$data[$i]['EducationGrade']['grades'] = $this->InstitutionSiteSectionGrade->getGradesBySection($sectionId);
		// 	}else{
		// 		$data[$i]['EducationGrade']['grades'] = $this->InstitutionSiteSection->getSingleGradeBySection($sectionId);
		// 	}	
		// 	$data[$i]['Staff']['staff_name'] = ModelHelper::getName($obj['SecurityUser']);
		// }

		$this->controller->set('data', $query);
	}


}
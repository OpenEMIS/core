<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;

class InstitutionQualityVisitsTable extends AppTable {
	private $_fieldOrder = ['date', 'academic_period_level', 'academic_period_id', 'education_grade_id', 'institution_site_section_id', 'institution_site_class_id', 'security_user_id', 'quality_visit_type_id'];

	public function initialize(array $config) {
		$this->table('institution_site_quality_visits');
		parent::initialize($config);

		$this->belongsTo('QualityVisitTypes', ['className' => 'FieldOption.QualityVisitTypes']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('Sections', ['className' => 'Institution.InstitutionSiteSections', 'foreignKey' => 'institution_site_section_id']);
		$this->belongsTo('Classes', ['className' => 'Institution.InstitutionSiteClasses', 'foreignKey' => 'institution_site_class_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('academic_period_level');
		$this->ControllerAction->field('academic_period_id');
		$this->ControllerAction->field('education_grade_id');
		$this->ControllerAction->field('institution_site_section_id');
		$this->ControllerAction->field('institution_site_class_id');
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['comment']['visible'] = false;
		$this->fields['academic_period_id']['visible'] = false;
		$this->_fieldOrder = ['date', 'education_grade_id', 'institution_site_section_id', 'institution_site_class_id', 'security_user_id', 'quality_visit_type_id'];
	}

	public function onUpdateFieldAcademicPeriodLevel(Event $event, array $attr, $action, Request $request) {
		$levelOptions = $this->AcademicPeriods->Levels
			->getList()
			->toArray();

		$attr['options'] = $levelOptions;
		$attr['onChangeReload'] = true;

		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		$selectedLevel = key($this->fields['academic_period_level']['options']);
		if ($request->is('post')) {
			$selectedLevel = $request->data($this->aliasField('academic_period_level'));
		}

		$periodOptions = $this->AcademicPeriods
			->find('list')
			->find('visible')
			->find('order')
			->where([$this->AcademicPeriods->aliasField('academic_period_level_id') => $selectedLevel])
			->toArray();
		
		$attr['options'] = $periodOptions;
		$attr['onChangeReload'] = true;

		return $attr;
	}
}

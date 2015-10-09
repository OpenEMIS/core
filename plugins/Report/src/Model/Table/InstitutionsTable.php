<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class InstitutionsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_sites');
		parent::initialize($config);
		
		$this->belongsTo('InstitutionSiteLocalities', 		['className' => 'Institution.Localities']);
		$this->belongsTo('InstitutionSiteTypes', 			['className' => 'Institution.Types']);
		$this->belongsTo('InstitutionSiteOwnerships', 		['className' => 'Institution.Ownerships']);
		$this->belongsTo('InstitutionSiteStatuses', 		['className' => 'Institution.Statuses']);
		$this->belongsTo('InstitutionSiteSectors', 			['className' => 'Institution.Sectors']);
		$this->belongsTo('InstitutionSiteProviders', 		['className' => 'Institution.Providers']);
		$this->belongsTo('InstitutionSiteGenders', 			['className' => 'Institution.Genders']);
		$this->belongsTo('Areas', 							['className' => 'Area.Areas']);
		$this->belongsTo('AreaAdministratives', 			['className' => 'Area.AreaAdministratives']);
		
		$this->addBehavior('Excel', ['excludes' => ['security_group_id', 'institution_site_type_id'], 'pages' => false]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.CustomFieldList', [
			'model' => 'Institution.Institutions',
			'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
			'fieldValueClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'foreignKey' => 'institution_site_id', 'dependent' => true, 'cascadeCallbacks' => true],
		]);
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
		$this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
		$this->ControllerAction->field('survey_form', ['type' => 'hidden']);
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		$attr['onChangeReload'] = true;
		return $attr;
	}
	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		if (isset($this->request->data[$this->alias()]['feature'])) {
			$feature = $this->request->data[$this->alias()]['feature'];
			if ($feature == 'Report.InstitutionSurveys') {
				$InstitutionSurveyTable = TableRegistry::get('Institution.InstitutionSurveys');
				$academicPeriodOptions = $InstitutionSurveyTable
					->find('list', [
						'keyField' => 'id',
						'valueField' => 'name'
					])
					->contain(['AcademicPeriods'])
					->select(['id' => 'AcademicPeriods.id', 'name' => 'AcademicPeriods.name'])
					->where([$InstitutionSurveyTable->aliasField('status') => 2])
					->group([$InstitutionSurveyTable->aliasField('academic_period_id')])
					->toArray();
				$attr['options'] = ['' => '-- ' . __('Select Academic Period') . ' --'] + $academicPeriodOptions;
				$attr['onChangeReload'] = true;
				$attr['type'] = 'select';
				return $attr;
			}
		}
	}

	public function onUpdateFieldSurveyForm(Event $event, array $attr, $action, Request $request) {
		if (isset($this->request->data[$this->alias()]['feature']) && isset($this->request->data[$this->alias()]['academic_period_id'])) {
			$feature = $this->request->data[$this->alias()]['feature'];
			$academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
			if ($feature == 'Report.InstitutionSurveys' && !empty($academicPeriodId)) {
				$InstitutionSurveyTable = TableRegistry::get('Institution.InstitutionSurveys');
				$surveyFormOptions = $InstitutionSurveyTable
					->find('list', [
						'keyField' => 'id',
						'valueField' => 'name'
					])
					->contain(['SurveyForms'])
					->select(['id' => 'SurveyForms.id', 'name' => 'SurveyForms.name'])
					->group([
						$InstitutionSurveyTable->aliasField('academic_period_id'), 
						$InstitutionSurveyTable->aliasField('survey_form_id')
					])
					->where([
						$InstitutionSurveyTable->aliasField('status') => 2,
						$InstitutionSurveyTable->aliasField('academic_period_id') => $academicPeriodId
					])
					->toArray();
				$attr['options'] = $surveyFormOptions;
				$attr['type'] = 'select';
				return $attr;
			}
		}
	}

	public function onGetReportName(Event $event, ArrayObject $data) {
		return __('Overview');
	}
}

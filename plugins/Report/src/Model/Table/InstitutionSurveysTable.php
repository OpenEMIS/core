<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class InstitutionSurveysTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_site_surveys');
		parent::initialize($config);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		
		$this->addBehavior('Excel', [
			'pages' => false
		]);
		$this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
		$this->ControllerAction->field('survey_form', ['type' => 'hidden']);
		$this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		$attr['onChangeReload'] = true;
		return $attr;
	}

	public function onUpdateFieldSurveyForm(Event $event, array $attr, $action, Request $request) {
		if (isset($this->request->data[$this->alias()]['feature'])) {
			$feature = $this->request->data[$this->alias()]['feature'];
			if ($feature == 'Report.InstitutionSurveys') {
				$InstitutionSurveyTable = TableRegistry::get('Institution.InstitutionSurveys');
				$surveyFormOptions = $InstitutionSurveyTable
					->find('list', [
						'keyField' => 'id',
						'valueField' => 'name'
					])
					->contain(['SurveyForms'])
					->select(['id' => 'SurveyForms.id', 'name' => 'SurveyForms.name'])
					->group([ 
						$InstitutionSurveyTable->aliasField('survey_form_id')
					])
					->where([
						$InstitutionSurveyTable->aliasField('status') => 2,
					])
					->toArray();
				$attr['options'] = $surveyFormOptions;
				$attr['type'] = 'select';
				return $attr;
			}
		}
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		if (isset($this->request->data[$this->alias()]['feature']) && isset($this->request->data[$this->alias()]['survey_form'])) {
			$feature = $this->request->data[$this->alias()]['feature'];
			$surveyForm = $this->request->data[$this->alias()]['survey_form'];
			if ($feature == 'Report.InstitutionSurveys' && !empty($surveyForm)) {
				$InstitutionSurveyTable = TableRegistry::get('Institution.InstitutionSurveys');
				$academicPeriodOptions = $InstitutionSurveyTable
					->find('list', [
						'keyField' => 'id',
						'valueField' => 'name'
					])
					->contain(['AcademicPeriods'])
					->select(['id' => 'AcademicPeriods.id', 'name' => 'AcademicPeriods.name'])
					->where([
						$InstitutionSurveyTable->aliasField('status') => 2,
						$InstitutionSurveyTable->aliasField('survey_form_id') => $surveyForm
					])
					->group([
						$InstitutionSurveyTable->aliasField('survey_form_id'), 
						$InstitutionSurveyTable->aliasField('academic_period_id')
					])
					->toArray();
				$attr['options'] = ['' => '-- ' . __('Select Academic Period') . ' --'] + $academicPeriodOptions;
				$attr['onChangeReload'] = true;
				$attr['type'] = 'select';
				return $attr;
			}
		}
	}
}

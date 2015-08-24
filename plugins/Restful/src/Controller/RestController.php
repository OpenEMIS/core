<?php
namespace Restful\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class RestController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Questions' => ['className' => 'Survey.SurveyQuestions'],
			'Forms' => ['className' => 'Survey.SurveyForms']
		];
		$this->loadComponent('Paginator');
		$this->loadComponent('Restful.RestSurvey', [
			'models' => [
				// Administration Table
				'Module' => 'CustomField.CustomModules',
				'Field' => 'Survey.SurveyQuestions',
				'FieldOption' => 'Survey.SurveyQuestionChoices',
				'TableColumn' => 'Survey.SurveyTableColumns',
				'TableRow' => 'Survey.SurveyTableRows',
				'Form' => 'Survey.SurveyForms',
				'FormField' => 'Survey.SurveyFormsQuestions',
				// Transaction Table
				'Record' => 'Institution.InstitutionSurveys',
				'FieldValue' => 'Institution.InstitutionSurveyAnswers',
				'TableCell' => 'Institution.InstitutionSurveyTableCells'
			]
		]);
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	$this->Auth->allow();

    	if ($this->request->action == 'survey') {
    		$this->autoRender = false;
    	}
    }

    public function survey() {
    	$this->autoRender = false;
    	$pass = $this->request->params['pass'];
    	$action = 'index';
		if (!empty($pass)) {
			$action = array_shift($pass);
		}

    	if (method_exists($this->RestSurvey, $action)) {
			return call_user_func_array(array($this->RestSurvey, $action), $pass);
		} else {
			return false;
		}
    }
}

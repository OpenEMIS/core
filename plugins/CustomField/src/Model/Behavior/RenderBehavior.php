<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Utility\Inflector;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;

class RenderBehavior extends Behavior {
	protected $fieldTypeCode;
	protected $fieldType;
    private $surveyRules = null;
    private $SurveyRulesTable;

	public function initialize(array $config) {
        parent::initialize($config);

        $class = basename(str_replace('\\', '/', get_class($this)));
		$class = str_replace('Render', '', $class);
		$class = str_replace('Behavior', '', $class);

		$code = strtoupper(Inflector::underscore($class));
		$this->fieldTypeCode = $code;
		$this->fieldType = $class;
        $this->SurveyRulesTable = TableRegistry::get('Survey.SurveyRules');
    }

    public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$eventMap = [
            'Render.on'.$this->fieldType.'Initialize' => 'on'.$this->fieldType.'Initialize',
            'Render.format'.$this->fieldType.'Entity' => 'format'.$this->fieldType.'Entity',
            'Render.patch'.$this->fieldType.'Values' => 'patch'.$this->fieldType.'Values',
            'Render.process'.$this->fieldType.'Values' => 'process'.$this->fieldType.'Values',
            'Render.onSave' => 'onSave',
            'ControllerAction.Model.onUpdateIncludes' => 'onUpdateIncludes',
            'Workflow.updateWorkflowStatus' => 'updateWorkflowStatus'
        ];

        foreach ($eventMap as $event => $method) {
            if (!method_exists($this, $method)) {
                continue;
            }
            $events[$event] = $method;
        }
		return $events;
	}

    protected function processValues(Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $fieldKey = $settings['fieldKey'];
        $valueKey = $settings['valueKey'];

        $customValue = $settings['customValue'];
        $fieldValues = $settings['fieldValues'];

        if (strlen($customValue[$valueKey]) == 0) {
            if (isset($entity->id)) {
                $settings['deleteFieldIds'][] = $customValue[$fieldKey];
            }
        } else {
            $fieldValues[] = $customValue;
        }
        $settings['fieldValues'] = $fieldValues;
    }

    protected function processRelevancyDisabled($entity, $html, $fieldId) {
        if (is_null($this->surveyRules)) {
            $rules = $this->SurveyRulesTable
                ->find()
                ->where([
                    $this->SurveyRulesTable->aliasField('survey_form_id') => $entity->survey_form_id,
                    $this->SurveyRulesTable->aliasField('enabled') => 1
                ])
                ->select([
                    $this->SurveyRulesTable->aliasField('survey_question_id'), 
                    $this->SurveyRulesTable->aliasField('dependent_question_id'),
                    $this->SurveyRulesTable->aliasField('show_options')
                ])
                ->hydrate(false)
                ->toArray();
            foreach ($rules as $rule) {
                $showOptions = json_decode($rule['show_options']);
                $showOptionsJsonArray = '[';
                foreach($showOptions as $option) {
                    $showOptionsJsonArray = $showOptionsJsonArray.$option.',';
                }
                $showOptionsJsonArray = trim($showOptionsJsonArray, ",");
                $showOptionsJsonArray = $showOptionsJsonArray.']';
                $this->surveyRules[$rule['survey_question_id']] = [
                        'dependent_question_id' => $rule['dependent_question_id'],
                        'show_options' => $showOptionsJsonArray
                    ];
            }
        }
        if (isset($this->surveyRules[$fieldId])) {
            $value = '<fieldset 
                ng-disabled="!RelevancyRulesController.showDropdown('.$this->surveyRules[$fieldId]['dependent_question_id'].', '.$this->surveyRules[$fieldId]['show_options'].');" 
                ng-show="RelevancyRulesController.showDropdown('.$this->surveyRules[$fieldId]['dependent_question_id'].', '.$this->surveyRules[$fieldId]['show_options'].');"
                >' . $html;
            $value .= '</fieldset>';

            $html = $value;
        }
        return $html;
    }
}

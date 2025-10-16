<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Utility\Inflector;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class RenderBehavior extends Behavior {
	protected $fieldTypeCode;
	protected $fieldType;
    private $surveyRules = null;
    private $SurveyRulesTable;

	public function initialize(array $config): void {
        parent::initialize($config);

        $class = basename(str_replace('\\', '/', get_class($this)));
		$class = str_replace('Render', '', $class);
		$class = str_replace('Behavior', '', $class);

		$code = strtoupper(Inflector::underscore($class));
		$this->fieldTypeCode = $code;
		$this->fieldType = $class;
        $this->SurveyRulesTable = TableRegistry::getTableLocator()->get('Survey.SurveyRules');
    }

    public function implementedEvents(): array {
    	$events = parent::implementedEvents();
    	$eventMap = [
            'Render.on'.$this->fieldType.'Initialize' => 'on'.$this->fieldType.'Initialize',
            'Render.format'.$this->fieldType.'Entity' => 'format'.$this->fieldType.'Entity',
            'Render.patch'.$this->fieldType.'Values' => 'patch'.$this->fieldType.'Values',
            'Render.process'.$this->fieldType.'Values' => 'process'.$this->fieldType.'Values',
            'Render.onSave' => 'onSave',
            'ControllerAction.Model.onUpdateIncludes' => 'onUpdateIncludes',
            // 'Render.deleteCustomFieldValues' => 'deleteCustomFieldValues'
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

        if (isset($customValue[$valueKey]) && strlen($customValue[$valueKey]) == 0) {
            if (isset($entity->id)) {
                $settings['deleteFieldIds'][] = $customValue[$fieldKey];
            }
        } else {
            $fieldValues[] = $customValue;
        }
        $settings['fieldValues'] = $fieldValues;
    }

    protected function processRelevancyDisabled($entity, $html, $fieldId, &$formHelper, $unlockFields) {
        // POCOR-9105 start
        // POCOR-9147 start
        try {
            // $entity_array = $entity->toArray();
            $entity_array = $entity;
        } catch (\Throwable $e) {
            try {
                $arrentity = $entity;
                $arrentity->unsetProperty('_joinData'); // if exists
                $arrentity->clean(); // resets dirty tracking, optional
                $entity_array = $arrentity->extract($arrentity->visibleProperties());
            } catch (\Throwable $e) {
                $entity_array = [];
            }
        }
        // POCOR-9147 end
        $survey_form_id = $entity_array['survey_form_id'];
        if($survey_form_id == null) {
            $survey_form_id = $entity->survey_form_id;
        }
        if($survey_form_id == null) {
            $survey_form_id = $entity->getOriginal('survey_form_id');
        }
        if(!isset($survey_form_id)) {
            $this->surveyRules = null;
            return $html;
        }
        if (is_null($this->surveyRules)) {
            $rules = $this->SurveyRulesTable
                ->find()
                ->where([
                    $this->SurveyRulesTable->aliasField('survey_form_id') => $survey_form_id,
                    $this->SurveyRulesTable->aliasField('enabled') => 1
                ])
                ->select([
                    $this->SurveyRulesTable->aliasField('survey_form_id'),
                    $this->SurveyRulesTable->aliasField('survey_question_id'),
                    $this->SurveyRulesTable->aliasField('dependent_question_id'),
                    $this->SurveyRulesTable->aliasField('show_options')
                ])
                ->disableHydration()
                ->toArray();
            // POCOR-9105 end
            foreach ($rules as $rule) {
                $showOptionsJsonArray = str_replace('"', '', $rule['show_options']);
                $this->surveyRules[$rule['survey_question_id']] = [
                        'dependent_question_id' => $rule['dependent_question_id'],
                        'show_options' => $showOptionsJsonArray
                    ];
            }
        }
        // field should not be disabled as AuthSecurity will check to ensure that all unlocked fields are present in the POST data
        if (isset($this->surveyRules[$fieldId])) {
            $value = '<fieldset style="clear: both;"
                ng-readonly="!RelevancyRulesController.showDropdown('.$this->surveyRules[$fieldId]['dependent_question_id'].', '.$this->surveyRules[$fieldId]['show_options'].');"
                ng-show="RelevancyRulesController.showDropdown('.$this->surveyRules[$fieldId]['dependent_question_id'].', '.$this->surveyRules[$fieldId]['show_options'].');"
                >' . $html;
            $value .= '</fieldset>';
            foreach ($unlockFields as $field) {
                $formHelper->unlockField($field);
            }
            $html = $value;
        }
        return $html;
    }

    protected function getStepFromParams($params=[]) {
        if (isset($params['precision']) && ($params['precision'] > 0)) {
            $step = '0.';

            for ($i=1; $i <= $params['precision']; $i++) {
                // last precision will be 1
                if ($i == ($params['precision'])) {
                    $step = $step . '1';
                } else {
                    $step = $step . '0';
                }
            }

            return $step;
        }

        return null;
    }

    // POCOR-9332 start
    protected function getMinFromParams(array $params = [])
    {
        $min = null;

        // direct min_value
        if (isset($params['min_value']) && is_numeric($params['min_value'])) {
            $min = +$params['min_value'];
        }

        // range.lower overrides
        if (isset($params['range']) && is_array($params['range']) &&
            isset($params['range']['lower']) && is_numeric($params['range']['lower'])) {
            $min = +$params['range']['lower'];
        }

        return $min;
    }
    // POCOR-9332 start
    protected function getMaxFromParams(array $params = [])
    {
        $max = null;

        // direct max_value
        if (isset($params['max_value']) && is_numeric($params['max_value'])) {
            $max = +$params['max_value'];
        }

        // range.upper overrides
        if (isset($params['range']) && is_array($params['range']) &&
            isset($params['range']['upper']) && is_numeric($params['range']['upper'])) {
            $max = +$params['range']['upper'];
        }

        return $max;
    }
    
    //POCOR-9407
    protected function processValuesFile(Entity $entity, ArrayObject $data, ArrayObject $settings) 
    {
        $fieldKey   = $settings['fieldKey'];
        $valueKey   = $settings['valueKey'];

        $customValue = $settings['customValue'];
        $fieldValues = $settings['fieldValues'];

        $fieldId   = $customValue[$fieldKey] ?? null;
        $fileName  = $customValue[$valueKey] ?? null;

        $existingFileName = null;
        $existingFile     = null;

        // Check if entity already has file + file_name
        if (!empty($entity->custom_field_values)) {
            foreach ($entity->custom_field_values as $cf) {
                if ($cf[$fieldKey] == $fieldId) {
                    $existingFileName = $cf->file_name ?? null;
                    $existingFile     = $cf->file ?? null;
                    break;
                }
            }
        }

        if (empty($fileName) && empty($existingFileName)) {
            // No new file AND no old file → delete
            if (!empty($entity->id)) {
                $settings['deleteFieldIds'][] = $fieldId;
            }
        } else {
            // Preserve old values if no new file uploaded
            if (empty(!$fileName) && !empty($existingFileName)) {
                $customValue[$valueKey] = $existingFileName;
            }
            if (empty($customValue['file']) && !empty($existingFile)) {
                $customValue['file'] = $existingFile;
            }

            $fieldValues[] = $customValue;
        }

        $settings['fieldValues'] = $fieldValues;
    }
}

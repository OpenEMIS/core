<?php
namespace Survey\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFieldsTable;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;
use Cake\Utility\Text;
use Cake\ORM\TableRegistry; //POCOR-9359

class SurveyQuestionsTable extends CustomFieldsTable
{
    protected $fieldTypeFormat = ['OpenEMIS', 'OpenEMIS_Institution'];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->hasMany('CustomFieldOptions', ['className' => 'Survey.SurveyQuestionChoices', 'foreignKey' => 'survey_question_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CustomTableColumns', ['className' => 'Survey.SurveyTableColumns', 'saveStrategy' => 'replace', 'foreignKey' => 'survey_question_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CustomTableRows', ['className' => 'Survey.SurveyTableRows', 'saveStrategy' => 'replace', 'foreignKey' => 'survey_question_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CustomFieldValues', ['className' => 'Institution.InstitutionSurveyAnswers', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CustomTableCells', ['className' => 'Institution.InstitutionSurveyTableCells', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('CustomForms', [
            'className' => 'Survey.SurveyForms',
            'joinTable' => 'survey_forms_questions',
            'foreignKey' => 'survey_question_id',
            'targetForeignKey' => 'survey_form_id',
            'through' => 'Survey.SurveyFormsQuestions',
            'dependent' => true
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Rules' => ['index']
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->add('code', [
                'unique' => [
                    'rule' => ['validateUnique'],
                    'provider' => 'table',
                    'message' => 'This code already exists in the system'
                ]
            ])
            ->notEmpty('name') //POCOR-8635
            ->notEmpty('field_type');//POCOR-8635

        return $validator;
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['code', 'name', 'description', 'field_type', 'is_mandatory', 'is_unique']);
    }

    //POCOR-7742 start
    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('code');
        $this->field('params', ['attr' => ['style' => 'display:none;']]);
    }

    public function editBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('code');
        $this->field('params', ['attr' => ['disabled' => 'disabled']]);
    }
    //POCOR-7742 end
    
    public function onUpdateFieldCode(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
    //    echo "<pre>";print_r($event);die;
        if ($action == 'add') {
            if (!$request->is('post')) {
                $textValue = substr(Text::uuid(), 0, 8);
                $attr['attr']['value'] = $textValue;
            }
            
            return $attr;
        }
    }

    /**
     * @usage  Used to update the name of question in survey_forms_questions table when question name is updated in survey_questions table
     * @author Prajakta
     * @ticket POCOR-9359
     */
    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->isNew() && $entity->isDirty('name')) {
            $surveyFormsQuestionsTable = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');

            $surveyFormsQuestionsTable->updateAll(
                ['name' => $entity->name],
                ['survey_question_id' => $entity->id]
            );
        }
    }


    /*POCOR-6187 starts*/
    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
       $surveyQuestionId = $requestData['SurveyQuestions']['id'];
        if(empty($surveyQuestionId) && isset($this->request->getParam('pass')[1])) {
            $surveyQuestionId = $this->paramsDecode($this->request->getParam('pass')[1])['id'];
        }
        if (!empty($requestData['SurveyQuestions']['custom_field_options'])) {
            $data = $requestData['SurveyQuestions']['custom_field_options'];
            $removeData = $this->CustomFieldOptions->deleteAll([
                                'survey_question_id' => $surveyQuestionId
                            ]);
            foreach ($data as $key => $value) {
                if ($value['visible'] == 1) {
                    $connection = $this->getConnection();
                    $connection->getDriver()->enableAutoQuoting();
                    $newRecords = $this->CustomFieldOptions->newEntity([]);
                    $newRecords->name = $value['name'];
                    $newRecords->visible = 1;
                    $newRecords->is_default = $entity->custom_field_options[$key]->is_default;
                    $newRecords->survey_question_id = $surveyQuestionId;
                    $newRecords->created_user_id = 2;
                    $newRecords->created = date('Y-m-d H:i:s');
                    $this->CustomFieldOptions->save($newRecords); 
                } else {
                    $removeData = $this->CustomFieldOptions->deleteAll([
                                'survey_question_id' => $surveyQuestionId
                    ]);
                }
            }
        }
    }
    /*POCOR-6187 ends*/

    // Start POCOR-5188
    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
		$is_manual_exist = $this->getManualUrl('Administration','Questions','Survey');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
        if($this->action == 'edit' && isset($this->request->getParam('pass')[1])) {
            $surveyQuestionId = $this->paramsDecode($this->request->getParam('pass')[1])['id'];
            $this->field('id', ['value' => $surveyQuestionId]);        
        }
    }
    // End POCOR-5188

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        $a = $this->request->getParam('pass')[0];
        if ($field == 'name') { //POCOR-8635
            return __('Question'); //POCOR-8635
        } elseif ($field == 'code') {
            return __('Code');
        // } elseif ($field == 'name') {
        //     return __('Name');
        } elseif ($field == 'field_type') {
            return __('field Type');
        }  elseif ($field == 'is_mandatory') {
            return __('Is Mandatory');
        } elseif ($field == 'is_unique') {
            return __('Is Unique');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'description') {
            return __('Description');
        }elseif ($field == 'description') {
            return __('Description');
        }elseif ($field == 'params') {
            //POCOR-7742 start
            if($a == 'add' && $module == "SurveyQuestions"){
                $this->field('params', ['attr' => ['class'=>'surveryform']]);
                return '';

            }else{
                return __('Params');
            }
            //POCOR-7742 end
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}

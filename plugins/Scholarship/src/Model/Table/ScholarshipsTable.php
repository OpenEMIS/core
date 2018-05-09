<?php
namespace Scholarship\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\Controller\Component;
use Cake\Validation\Validator;
use Cake\Utility\Security;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\ResultSet;
use Cake\Utility\Inflector;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class ScholarshipsTable extends ControllerActionTable
{
    use OptionsTrait;

    CONST SELECT_FIELD_OF_STUDIES = 1;
    CONST SELECT_ALL_FIELD_OF_STUDIES = '-1';

    private $educationFieldOfStudySelection = [];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('FinancialAssistanceTypes', ['className' => 'Scholarship.FinancialAssistanceTypes', 'foreignKey' => 'scholarship_financial_assistance_type_id']);
        $this->belongsTo('FundingSources', ['className' => 'Scholarship.FundingSources', 'foreignKey' => 'scholarship_funding_source_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('ScholarshipAttachmentTypes', ['className' => 'Scholarship.ScholarshipAttachmentTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ScholarshipApplications', ['className' => 'Scholarship.ScholarshipApplications', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionChoices', ['className' => 'Scholarship.InstitutionChoices', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ApplicationAttachments', ['className' => 'Scholarship.ApplicationAttachments', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->belongsToMany('FieldOfStudies', [
            'className' => 'Education.EducationFieldOfStudies',
            'joinTable' => 'scholarships_field_of_studies',
            'foreignKey' => 'scholarship_id',
            'targetForeignKey' => 'education_field_of_study_id',
            'through' => 'Scholarship.ScholarshipsFieldOfStudies',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->fieldOfStudySelection = $this->getSelectOptions($this->aliasField('field_of_study_selection'));
    }


    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('date_application_close', 'ruleCompareDateReverse', [
                    'rule' => ['compareDateReverse', 'date_application_open', true]
                ]);
        return $validator;
    }


    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupFields();
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra) 
    {
        $entity->field_of_study_selection = self::SELECT_FIELD_OF_STUDIES;
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra) 
    {
        $this->setupFields($entity);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
      //  if (array_key_exists($this->alias(), $requestData)) {
      //      if (isset($requestData[$this->alias()]['position_grades']['_ids']) && empty($requestData[$this->alias()]['position_grades']['_ids'])) {
      //          $requestData[$this->alias()]['position_grades'] = []; 
      //      }
      // }
   }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) 
    {
        $this->setupFields($entity);
    }


    public function onUpdateFieldFieldOfStudySelection(Event $event, array $attr, $action, Request $request) 
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['options'] = $this->fieldOfStudySelection;
            $attr['select'] = false;
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }


    public function afterSave(Event $event, Entity $entity, ArrayObject $options) 
    {
        if ($entity->has('field_of_study_selection') && $entity->field_of_study_selection == self::SELECT_ALL_FIELD_OF_STUDIES) {
            $ScholarshipsFieldOfStudies = TableRegistry::get('Scholarship.ScholarshipsFieldOfStudies');
            $entityId = $entity->id;

            $data = [
                'scholarship_id' => $entityId,
                'education_field_of_study_id' => self::SELECT_ALL_FIELD_OF_STUDIES
            ];

            $ScholarshipsFieldOfStudiesEntity = $ScholarshipsFieldOfStudies->newEntity($data);

            if ($ScholarshipsFieldOfStudies->save($ScholarshipsFieldOfStudiesEntity)) {
            } else {
                $ScholarshipsFieldOfStudies->log($ScholarshipsFieldOfStudiesEntity->errors(), 'debug');
            }
        }
    }


    public function onUpdateFieldFieldOfStudies(Event $event, array $attr, $action, Request $request) 
    {
        $requestData = $request->data;
        $entity = $attr['entity'];
               
        $fieldOfStudyOptions = $this->FieldOfStudies->getList()->toArray();
    
        $fieldOfStudySelection = null;
        if (isset($requestData[$this->alias()]['field_of_study_selection'])) {
            $fieldOfStudySelection = $requestData[$this->alias()]['field_of_study_selection'];
        } else {
            $fieldOfStudySelection = $entity->field_of_study_selection;
        }

        if ($fieldOfStudySelection == self::SELECT_ALL_FIELD_OF_STUDIES) {
            $attr['value'] = self::SELECT_ALL_FIELD_OF_STUDIES;
            $attr['attr']['value'] = __('All Field Of Studies Selected');
            $attr['type'] = 'readonly';
        } else {
            $attr['options'] = $fieldOfStudyOptions;
        }

        return $attr;
    }

    public function onUpdateFieldScholarshipFinancialAssistanceTypeId(Event $event, array $attr, $action, Request $request) 
    {
        if ($action == 'add' || $action == 'edit') { 

            $financialAssistanceTypeOptions = $this->FinancialAssistanceTypes
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code'
                ])
                ->order([$this->FinancialAssistanceTypes->aliasField('id')])
                ->toArray();
              
            $entity = $attr['entity'];
            $financialAssistanceTypeId = $entity->scholarship_financial_assistance_type_id;
            // if (!$entity->isNew()) { // edit not allow to change field type
            //     $attr['type'] = 'readonly';
            //     $attr['value'] = $financialAssistanceTypeId;
            //     $attr['attr']['value'] = $entity->scholarship_financial_assistance_type->name;
            // }

            if (isset($financialAssistanceTypeOptions[$financialAssistanceTypeId])) {
                 switch ($financialAssistanceTypeOptions[$financialAssistanceTypeId]) {
                    case 'SCHOLARSHIP':
                        // No implementation
                        break;
                    case 'LOAN':
                        $this->field('requested_amount', [
                            'after' => 'total_amount'
                        ]);
                        $this->field('interest_rate', [
                            'attr' => ['label' => __('Interest rate %')],
                            'after' => 'requested_amount'
                        ]);
                        $this->field('interest_rate_type', [
                            'after' => 'interest_rate'
                        ]);
                        $this->field('payment_frequency_id', [
                             'after' => 'interest_rate_type'
                        ]);
                        $this->field('loan_term', [
                            'after' => 'payment_frequency_id'
                        ]);
                        break;
                    }
                }
                $attr['onChangeReload'] = true;
            }

        return $attr;
    }

    public function onUpdateFieldBond(Event $event, array $attr, $action, Request $request) 
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['options'] = $this->getBondOptions(20);
        }
        return $attr;
    }



    public function setupFields($entity = null)
    {
        if($this->action == 'index') {
            $this->field('description', ['visible' => false]);
            $this->field('scholarship_financial_assistance_type_id', ['visible' => false]);
            $this->field('scholarship_funding_source_id', ['visible' => false]);
            $this->field('academic_period_id', ['visible' => false]);
            $this->field('total_amount', ['visible' => false]);
            $this->field('requirements', ['visible' => false]);
            $this->field('instructions', ['visible' => false]);  

        } elseif (in_array($this->action, ['add', 'edit', 'view'])) {
       
            $this->field('scholarship_financial_assistance_type_id', [
                'type' => 'select', 
                'attr' => ['label' => __('Financial Assistance Type')],
                'after' => 'description',
                'entity' => $entity
            ]);

             $this->field('scholarship_funding_source_id', [
                'type' => 'select', 
                'attr' => ['label' => __('Funding Source')],
                'after' => 'scholarship_financial_assistance_type_id'
            ]);
               
            $this->field('academic_period_id', [
                'type' => 'select',
                'after' => 'scholarship_funding_source_id'
            ]);

            $this->field('field_of_study_selection', [
                'type' => 'select',
                'visible' => ['index' => false, 'view' => false, 'edit' => true, 'add' => true],
                'after' => 'academic_period_id',
                'entity' => $entity
            ]);
            $this->field('field_of_studies', [
                'type' => 'chosenSelect',
                'placeholder' => __('Select Field Of Studies'),
                'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true],
                'attr' => ['required' => true],
                'entity' => $entity,
                'after' => 'field_of_study_selection'
            ]);
            $this->field('bond', [
                'type' => 'select',
                'after' => 'total_amount'
            ]);      
        } 
    }


    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $params = ['scholarship_id' => $entity->id];

        if (isset($buttons['view']['url'])) {
            $buttons['view']['url'] = $this->ControllerAction->setQueryString($buttons['view']['url'], $params);
        }

        if (isset($buttons['edit']['url'])) {
            $buttons['edit']['url'] = $this->ControllerAction->setQueryString($buttons['edit']['url'], $params);
        }

        return $buttons;
    }

    public function getBondOptions($maxYears)
    {
        $bondOptions = [];

        for ($i=0; $i<$maxYears; $i++) {
            $bondOptions [] = __($i .' Years');
        }

        return $bondOptions;
    }

    public function getAvailableScholarships($options = [])
    {
        $list = [];
        $applicantId = array_key_exists('applicant_id', $options) ? $options['applicant_id'] : '';
        $financialTypeId = array_key_exists('financial_type_id', $options) ? $options['financial_type_id'] : '';

        if ($applicantId && $financialTypeId) {
            $list = $this->find('list')
                ->notMatching('Applications', function ($q) use ($applicantId) {
                        return $q->where([
                            'Applications.applicant_id' => $applicantId
                        ]);
                     })
                ->where([$this->aliasField('scholarship_financial_assistance_type_id') => $financialTypeId])
                ->toArray();
        }
        return $list;
    }


    
}

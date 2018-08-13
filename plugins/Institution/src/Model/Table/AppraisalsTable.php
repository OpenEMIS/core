<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Chronos\Date;
use Cake\Chronos\Chronos;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Session;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

use App\Model\Table\ControllerActionTable;

class AppraisalsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_staff_appraisals');
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms']);
        $this->belongsTo('AppraisalTypes', ['className' => 'StaffAppraisal.AppraisalTypes']);
        $this->belongsTo('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->hasMany('AppraisalTextAnswers', ['className' => 'StaffAppraisal.AppraisalTextAnswers', 'foreignKey' => 'institution_staff_appraisal_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalSliderAnswers', ['className' => 'StaffAppraisal.AppraisalSliderAnswers', 'foreignKey' => 'institution_staff_appraisal_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalDropdownAnswers', ['className' => 'StaffAppraisal.AppraisalDropdownAnswers', 'foreignKey' => 'institution_staff_appraisal_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalNumberAnswers', ['className' => 'StaffAppraisal.AppraisalNumberAnswers', 'foreignKey' => 'institution_staff_appraisal_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('Institution.Appraisal');
        $this->addBehavior('Workflow.Workflow', [
            'model' => 'Institution.StaffAppraisals',
            'actions' => [
                'add' => false,
                'remove' => false,
                'edit' => false
            ],
            'disableWorkflow' => true
        ]);

        // setting this up to be overridden in viewAfterAction(), this code is required for file download
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->Auth->user('id');
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'AppraisalPeriods.AcademicPeriods', 'AppraisalForms',
            'AppraisalTypes'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function () use ($entity) {
            $filename = $entity->file_content;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            $showFunc
        );
        // End
        $this->field('staff_id', ['visible' => false]);
        $this->field('academic_period_id', ['fieldName' => 'appraisal_period.academic_period.name']);
        $this->field('institution_id');
        $this->field('appraisal_period_from');
        $this->field('appraisal_period_to');
        $this->field('appraisal_type_id', ['attr' => ['label' => __('Type')]]);
        $this->field('appraisal_period_id');
        $this->field('appraisal_form_id');
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('comment');
        $this->printAppraisalCustomField($entity->appraisal_form_id, $entity);
        $this->setFieldOrder(['academic_period_id', 'appraisal_type_id', 'appraisal_period_id', 'appraisal_form_id', 'appraisal_period_from', 'appraisal_period_to', 'date_appraised', 'file_content', 'comment']);
    }

    private function printAppraisalCustomField($appraisalFormId, Entity $entity)
    {
        if ($appraisalFormId) {
            $section = null;
            $sectionCount = 0;
            $criteriaCounter = new ArrayObject();
            $staffAppraisalId = $entity->has('id') ? $entity->id : -1;

            // retrieve all form criterias containing results
            $AppraisalFormsCriterias = TableRegistry::get('StaffAppraisal.AppraisalFormsCriterias');
            $formsCriterias = $AppraisalFormsCriterias->find()
                ->contain([
                    'AppraisalCriterias' => [
                        'FieldTypes',
                        'AppraisalSliders',
                        'AppraisalNumbers',
                        'AppraisalDropdownOptions' => ['sort' => ['AppraisalDropdownOptions.order' => 'ASC']]
                    ],
                    'AppraisalTextAnswers' => function ($q) use ($staffAppraisalId) {
                        return $q->where(['AppraisalTextAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                    },
                    'AppraisalSliderAnswers' => function ($q) use ($staffAppraisalId) {
                        return $q->where(['AppraisalSliderAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                    },
                    'AppraisalDropdownAnswers' => function ($q) use ($staffAppraisalId) {
                        return $q->where(['AppraisalDropdownAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                    },
                    'AppraisalNumberAnswers' => function ($q) use ($staffAppraisalId) {
                        return $q->where(['AppraisalNumberAnswers.institution_staff_appraisal_id' => $staffAppraisalId]);
                    }
                ])
                ->where([$AppraisalFormsCriterias->aliasField('appraisal_form_id') => $appraisalFormId])
                ->order($AppraisalFormsCriterias->aliasField('order'))
                ->toArray();

            foreach ($formsCriterias as $key => $formCritieria) {
                $details = new ArrayObject([
                    'appraisal_form_id' => $formCritieria->appraisal_form_id,
                    'appraisal_criteria_id' => $formCritieria->appraisal_criteria_id,
                    'section' => $formCritieria->section,
                    'field_type' => $formCritieria->appraisal_criteria->field_type->code,
                    'criteria_name' => $formCritieria->appraisal_criteria->name,
                    'is_mandatory' => $formCritieria->is_mandatory
                ]);
                if ($section != $details['section']) {
                    $section = $details['section'];
                    $this->field('section' . $sectionCount++, ['type' => 'section', 'title' => $details['section']]);
                }
                $this->appraisalCustomFieldExtra($details, $formCritieria, $criteriaCounter, $entity);
            }
        }
    }
}

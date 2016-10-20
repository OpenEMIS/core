<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Network\Session;
use Cake\I18n\Time;
use Cake\I18n\Date;

use App\Model\Table\ControllerActionTable;

class AppraisalsTable extends ControllerActionTable {

    public function initialize(array $config) {
        $this->table('staff_appraisals');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('AcademicPeriod.AcademicPeriod');
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('from', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'to', false]
                ],

            ])
            ;
    }

    private function setupTabElements() {
        $tabElements = $this->controller->getProfessionalDevelopmentTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        // pr($this->fields);die;
        $this->field('modified_user_id',    ['visible' => ['index' => true, 'view' => true], 'after' => 'final_rating']);
        $this->field('modified',            ['visible' => ['index' => true, 'view' => true], 'after' => 'modified_user_id']);

        $this->setFieldOrder(['staff_appraisal_type_id', 'title', 'from', 'to', 'final_rating', 'comment']);
        $this->setupTabElements();
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('comment', ['visible' => ['index' => false]]);
        $this->field('academic_period_id', ['visible' => ['index' => false]]);
        $this->field('competency_set_id', ['visible' => ['index' => false]]);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable'=>true]);
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->fields['academic_period_id']['onChangeReload'] = 'true';

        // $this->setFieldOrder(['title', 'academic_period_id', 'from', 'to', 'staff_appraisal_type_id', 'competency_set_id', 'final_rating', 'comment']);

    }

    public function addEditAfterAction(Event $event, Entity $entity)
    // public function addEditOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // pr('addEditOnChangePeriod');
        // pr($data);
        if ($this->action == 'add') {
            $dateAttr = ['from' => Time::now(), 'to' => Time::now()];
            $academicPeriodId = $entity->academic_period_id;

            if (!empty($academicPeriodId)) {
                $dateAttr['from'] = $this->AcademicPeriods->get($academicPeriodId)->start_date;
                $dateAttr['to'] = $this->AcademicPeriods->get($academicPeriodId)->end_date;

                // add restriction, only can choose within the selected academic period.
                $this->fields['from']['date_options']['startDate'] = $dateAttr['from']->format('d-m-Y');
                $this->fields['from']['date_options']['endDate'] = $dateAttr['to']->format('d-m-Y');
                $this->fields['to']['date_options']['startDate'] = $dateAttr['from']->format('d-m-Y');
                $this->fields['to']['date_options']['endDate'] = $dateAttr['to']->format('d-m-Y');

                $this->fields['from']['value'] = $dateAttr['from'];
                $this->fields['to']['value'] = $dateAttr['to'];
            }

            // set the value of the 'From' and 'To', also remove the today button
            // $this->fields['from']['value'] = $dateAttr['from'];
            // $this->fields['to']['value'] = $dateAttr['to'];
            $this->fields['from']['date_options']['todayBtn'] = false;
            $this->fields['to']['date_options']['todayBtn'] = false;
        }

        $this->setFieldOrder(['title', 'academic_period_id', 'from', 'to', 'staff_appraisal_type_id', 'competency_set_id', 'final_rating', 'comment']);
    }

    // to rename the field header
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'staff_appraisal_type_id') {
            return __('Type');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetModifiedUserId(Event $event, Entity $entity)
    {
        if($this->action == 'index') {
            $entity['modified_user'] = !empty($entity['modified_user']) ? $entity['modified_user'] : $entity['created_user'];
        }
    }

    public function onGetModified(Event $event, Entity $entity)
    {
        if($this->action == 'index') {
            $entity['modified'] = date('d-M-Y', strtotime(!empty($entity['modified']) ? $entity['modified'] : $entity['created']));
        }
    }
}

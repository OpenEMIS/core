<?php
namespace ReportCard\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class StaffReportCardEmailTable extends ControllerActionTable
{
    private $alertTypeFeatures = [];

	public function initialize(array $config)
    {
        $this->table('staff_profile_templates');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('StaffReportCards', ['className' => 'Institution.StaffReportCards', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('ReportCard.EmailTemplate', [
            'placeholder' => [
                '${staff.openemis_no}' => 'Staff OpenEMIS ID.',
                '${staff.name}' => 'Staff full name.',
                '${staff.first_name}' => 'Staff first name.',
                '${staff.middle_name}' => 'Staff middle name.',
                '${staff.third_name}' => 'Staff third name.',
                '${staff.last_name}' => 'Staff last name.',
                '${staff.preferred_name}' => 'Staff preferred name.',
                '${staff.address}' => 'Staff address.',
                '${staff.postal_code}' => 'Staff postal code.',
                '${staff.date_of_birth}' => 'Staff date of birth.',
                '${staff.identity_number}' => 'Staff identity number.',
                '${academic_period.code}' => 'Academic period code.',
                '${academic_period.name}' => 'Academic period name.',
            ]
        ]);
    }

   public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('academic_period_id')
            ->allowEmpty('start_date')
            ->allowEmpty('end_date');
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        if (array_key_exists('back', $toolbarButtonsArray)) {
            $encodedParam = $this->request->params['pass'][1];

            $backUrl = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'Templates',
                'view',
                $encodedParam
            ];

            $toolbarButtonsArray['back']['url'] = $backUrl;
        }
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $this->setupTabElements($entity);
        $this->setupFields($event, $entity);
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'AcademicPeriods' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ]
            ]);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements($entity);
        $this->setupFields($event, $entity);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->academic_period->name;
        }

        return $attr;
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $startDate = $this->formatDate($entity->start_date);
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->start_date->format('Y-m-d');
            $attr['attr']['value'] = $startDate;
        }

        return $attr;
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $endDate = $this->formatDate($entity->end_date);
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->end_date->format('Y-m-d');
            $attr['attr']['value'] = $endDate;
        }

        return $attr;
    }

    private function setupTabElements($entity)
    {
        $tabElements = $this->controller->getReportCardTab($entity->id);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    private function setupFields(Event $event, Entity $entity)
    {
        $this->field('report_card_information', ['type' => 'section']);
        $this->field('code', ['type' => 'readonly', 'attr' => ['required' => false]]);
        $this->field('name', ['type' => 'readonly', 'attr' => ['required' => false]]);
        $this->field('description', ['attr' => ['disabled' => 'disabled']]);
        $this->field('academic_period_id', ['entity' => $entity]);
        
        $this->field('start_date', ['entity' => $entity]);
        $this->field('end_date', ['entity' => $entity]);

        $this->field('excel_template_name', ['visible' => false]);
        $this->field('excel_template', ['visible' => false]);

        $this->setFieldOrder([
            'report_card_information',
            'code',
            'name',
            'description',
            'academic_period_id',
            'start_date',
            'end_date'
        ]);
    }
	
}

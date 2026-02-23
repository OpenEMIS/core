<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

class InfrastructureUtilityTelephonesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_utility_telephones');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('UtilityTelephoneTypes',   ['className' => 'Institution.UtilityTelephoneTypes', 'foreign_key' => 'utility_telephone_type_id']);
        $this->belongsTo('UtilityTelephoneConditions',   ['className' => 'Institution.UtilityTelephoneConditions', 'foreign_key' => 'utility_telephone_condition_id']);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['InfrastructureUtilityTelephones' =>
                ['institution_id']
            ]
        ]);
        $this->toggle('search', false);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureUtilityTelephones';
        $userType = '';
        $this->controller->changePageHeaderTrips($this, $modelAlias, $userType);
        //POCOR-9475 
        $this->field('start_date',['visible' => false]);
        $this->field('end_date',['visible' => false]);
        $this->field('is_current',['visible' => false]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->requirePresence('utility_telephone_condition_id')
            ->requirePresence('utility_telephone_type_id')
        ;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('utility_telephone_type_id');
        $this->field('utility_telephone_condition_id');
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('comment',['visible' => false]);
        // element control
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $requestQuery = $this->request->getQuery();

        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
        $extra['elements']['control'] = [
            'name' => 'Risks/controls',
            'data' => [
                'encodedQueryString' => $encodedQueryString,
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId
            ],
            'options' => [],
            'order' => 3
        ];
        // end element control
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query
        ->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId'], $this->aliasField('is_current') => 1])
        ->orderDesc($this->aliasField('created'));
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        
        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);

        $this->fields['utility_telephone_type_id']['type'] = 'select';
        $this->field('utility_telephone_type_id', ['attr' => ['label' => __('Type')]]);

        $this->fields['utility_telephone_condition_id']['type'] = 'select';
        $this->field('utility_telephone_condition_id', ['attr' => ['label' => __('Condition')]]);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'comment':
                return __('Comment');
            case 'academic_period_id':
                return __('Academic Period');
            case 'utility_telephone_type_id':
                return __('Type');
            case 'utility_telephone_condition_id':
                return __('Condition');
            case 'modified_user_id':
                return __('Modified By');
            case 'modified':
                return __('Modified On');
            case 'created_user_id':
                return __('Created By');
            case 'created':
                return __('Created On');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //POCOR-9475
    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        //Expire old records for same institution + academic year
        $this->updateAll(
            ['is_current' => false],
            [
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id
            ]
        );

        //Set dates from academic period
        $academicPeriods = TableRegistry::getTableLocator()
            ->get('AcademicPeriod.AcademicPeriods');

        $period = $academicPeriods->find()
            ->select(['start_date', 'end_date'])
            ->where(['id' => $entity->academic_period_id])
            ->first();

        if ($period) {
            $entity->start_date = $period->start_date;
            $entity->end_date   = $period->end_date;
        }

        //Always make new record current
        $entity->is_current = true;
    }

    //POCOR-9475
    public function editBeforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            return;
        }

        //Store original ID BEFORE unsetting
        $originalId = $entity->id;

        //Expire previous current record for that institution + academic year
        $this->updateAll(
            ['is_current' => false],
            [
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id
            ]
        );

        //Convert EDIT into INSERT
        $entity->setNew(true);
        $entity->unset('id');


        //Set academic period dates
        $academicPeriods = TableRegistry::getTableLocator()
            ->get('AcademicPeriod.AcademicPeriods');

        $period = $academicPeriods->find()
            ->select(['start_date', 'end_date'])
            ->where(['id' => $entity->academic_period_id])
            ->first();

        if ($period) {
            $entity->start_date = $period->start_date;
            $entity->end_date   = $period->end_date;
        }

        //Always mark new record current
        $entity->is_current = true;
    }

    //POCOR-9475
    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // Soft delete: mark record inactive
        $this->updateAll(
            ['is_current' => 0],
            ['id' => $entity->id]
        );

        // Stop actual DELETE
        $event->stopPropagation();
        $event->setResult(false);

        $this->Alert->success(
            __('Record has been deactivated successfully.'),
            ['type' => 'string', 'reset' => true]
        );

        return false;
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (isset($buttons['view'])) {
            $queryString = $this->getQueryString();
            $institutionId  = $queryString['institution_id'];
            $recordId  = $entity->id;
            $queryString = $this->paramsEncode(['id' => $institutionId, 'institution_id' => $institutionId, 'record_id' => $recordId]);
            $icon = '<i class="fa fa-history"></i>';
            $buttons['history'] = $buttons['view'];
            $buttons['history']['label'] = $icon . __('History');
            $buttons['history']['url']['plugin'] = 'Institution';
            $buttons['history']['url']['controller'] = 'Institutions';
            $buttons['history']['url']['action'] = 'InfrastructureTelephonesHistory';
            $buttons['history']['url'][0] = 'index';
            $buttons['history']['url'][1] = $queryString;
        }
            
        return $buttons;
    }

    // public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    // {
    //     $encodedString = $this->request->getAttribute('params')['pass'][1];
    //     $query = $this->request->getQuery();
        
    //     $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
    //     $buttons['remove'] = [
    //         'url' => [
    //             'plugin' => 'Institution',
    //             'controller' => 'Institutions',
    //             'action' => 'InfrastructureUtilityTelephones',
    //             '0' => 'remove',
    //             '1' => $encodedString,
    //             '2' => $this->ControllerAction->paramsEncode(['id' => $entity->id]),
    //         ],
    //         'type' => 'button',
    //         'label' => '<i class="fa fa-trash"></i>' . __('Delete'),
    //         'attr' => [
    //                 'role' => 'menuitem',
    //                 'tabindex' => -1,
    //                 'escape' => false,
    //             ]
    //     ];
    //     return $buttons;
    // }

    //POCOR-9475
    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {

        return $this->controller->redirect($this->url('index'));
        
    }


}

<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
use Cake\Network\Request;

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

    public function beforeAction(Event $event, ArrayObject $extra)
    {
    	$modelAlias = 'InfrastructureUtilityTelephones';
        $userType = '';
        $this->controller->changePageHeaderTrips($this, $modelAlias, $userType);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('utility_telephone_condition_id')
            ->requirePresence('utility_telephone_type_id')
        ;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
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

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
        ->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']])
        ->orderDesc($this->aliasField('created'));
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
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

    // public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
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
}

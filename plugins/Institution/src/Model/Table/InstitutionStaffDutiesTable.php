<?php
namespace Institution\Model\Table;
use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class InstitutionStaffDutiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_staff_duties');
        parent::initialize($config);
        $this->belongsTo('StaffDuties', ['className' => 'Institution.StaffDuties', 'foreignKey' => 'staff_duties_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('staff_duties_id', 'ruleRange', [
				'rule' => ['range', -1, 4]
			]);
	}

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        } else if ($field == 'staff_duties_id') {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        } else if ($field == 'staff_id') {
            return __('Staff');
        } else if ($field == 'comment') {
            return __('Comment');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        $this->setFieldOrder(['academic_period_id', 'staff_duties_id', 'staff_id', 'comment']);
    }

    public function onGetStaffId(Event $event, Entity $entity)
    {
        $Users = TableRegistry::get('User.Users');
        $result = $Users
            ->find()
            ->select(['first_name','last_name'])
            ->where(['id' => $entity->staff_id])
            ->first();
        return $entity->staff_id = $entity->staff_id = $entity['user']->openemis_no .' - '.$result->first_name.' '.$result->last_name;
    }

    /******************************************************************************************************************
    **
    ** addEdit action methods
    **
    ******************************************************************************************************************/
    public function addEditBeforeAction(Event $event)
    {

        $this->setFieldOrder([
            'academic_period_id', 'staff_duties_id',
            'staff_id','comment'
        ]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {

        $staffOption = $this->getStaffList();
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
         $this->field('staff_duties_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
         $this->field('staff_id', [
            'type' => 'select',
            'options' => $staffOption
        ]);
    }
    /**
     * Get staff list for drop down
     */
    public function getStaffList () {
        
        $institutionId = $this->request->session()->read('Institution.Institutions.id');
        $Staff = TableRegistry::get('Institution.Staff');
        $staffOptions = array();
        $result = $Staff->find()
                    ->where([$Staff->aliasField('institution_id')=>$institutionId])
                    ->select([
                        'first_name' => 'Users.first_name',
                        'openemis_no' =>'Users.openemis_no',
                        'id' => 'Users.id',
                        'last_name' => 'Users.last_name',
                    ])
                    ->leftJoin(
                    ['Users' => 'security_users'],
                    [
                        'Users.id = '. $Staff->aliasField('staff_id')
                    ]
                    );
            $result->order([$this->Users->aliasField('first_name'), $this->Users->aliasField('last_name')]);
            foreach($result as $val) {

                    $staffOptions[$val->id] = $val->openemis_no .' - '.$val->first_name.' '.$val->last_name;
            } 

            return $staffOptions;
    }

}

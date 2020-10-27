<?php
namespace Institution\Model\Table;
use ArrayObject;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;
use App\Model\Table\AppTable;

class InstitutionStaffDutiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('institution_staff_duties');
        parent::initialize($config);
        
        $this->displayField('academic_period_id');
        $this->displayField('staff_id');
        $this->displayField('institution_id');
       
        $this->displayField('comment');
        $this->primaryKey('id');

        $this->belongsTo('Users', [
        'className' => 'User.Users', 
        'foreignKey' => 'created_user_id'
        ]);

         $this->belongsTo('Staff', [
        'className' => 'Institution.Staff', 
        'foreignKey' => 'staff_id'
        ]);

        $this->toggle('view', true);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('add', false);
       
    }

	public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

		return $validator
			->add('name', 'ruleUnique', [
                'rule' => [
                    'validateUnique', [
                        'scope' => 'institution_id'
                    ]
                ],
				'provider' => 'table'
			]);
    }
    
     public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }


    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // $this->field('academic_period_id', []);
        // $this->field('institution_id', []);
        // $this->field('staff_duties_id', []);
        // $this->field('comment', []);
       // $this->field('female_students', []);

        $this->setFieldOrder([
            'academic_period_id',
            'institution_id',
            //'institution_class',
            'staff_duties_id',
            'comment',
            // 'male_students',
            // 'female_students'
        ]);
    }

    public function findOptionList(Query $query, array $options)
    {
        $institutionId = array_key_exists('institution_id', $options) ? $options['institution_id'] : 0;
        $query->where(['institution_id' => $institutionId]);
        
        return parent::findOptionList($query, $options);
    }
}

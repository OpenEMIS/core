<?php
namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;

class InstitutionStaffDutiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('institution_staff_duties');
        $this->displayField('academic_period_id');
        $this->displayField('staff_id');
        $this->displayField('institution_id');
       
        $this->displayField('comment');
        $this->primaryKey('id');

        $this->belongsTo('Users', [
        'className' => 'User.Users', 
        'foreignKey' => 'created_user_id'
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

    public function findView(Query $query, array $options)
    {
//         foreach($query as $val){
// echo '<pre>';print_r($val);
//         };
//        die;
    }

    public function findOptionList(Query $query, array $options)
    {
        $institutionId = array_key_exists('institution_id', $options) ? $options['institution_id'] : 0;
        $query->where(['institution_id' => $institutionId]);
        
        return parent::findOptionList($query, $options);
    }

    public function getDutiesList($params = [])
    {
        $conditions = array_key_exists('conditions', $params) ? $params['conditions'] : [];
        $withLevels = array_key_exists('withLevels', $params) ? $params['withLevels'] : false;
        $isEditable = array_key_exists('isEditable', $params) ? $params['isEditable'] : null;
        $this->table('staff_duties');
        $data = $this
            ->find('visible')
            //->find('years')
            //->find('editable', ['isEditable' => true])
            ->where($conditions)
            ->toArray();
    print_r($data);die;
        if (!$withLevels) {
            $list = $data;
        } else {
            $list[$level->name] = $data;
        }

        return $list;
    }
}

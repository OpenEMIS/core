<?php
namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class InstitutionTransportProvidersTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->hasMany('InstitutionBuses', ['className' => 'Institution.InstitutionBuses', 'dependent' => true, 'cascadeCallbacks' => true]);
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
        $query->contain([
            'InstitutionBuses' => [
                'TransportStatuses',
                'sort' => [
                    'InstitutionBuses.plate_number' => 'ASC'
                ]
            ]
        ]);

        return $query;
    }

    public function findOptionList(Query $query, array $options)
    {
        $institutionId = array_key_exists('institution_id', $options) ? $options['institution_id'] : 0;
        $query->where(['institution_id' => $institutionId]);
        
        return parent::findOptionList($query, $options);
    }
}

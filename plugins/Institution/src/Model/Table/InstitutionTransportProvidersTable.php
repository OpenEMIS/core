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
        $this->hasMany('InstitutionBuses', ['className' => 'Institution.InstitutionBuses']);
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

    public function findTransportProvidersList(Query $query, array $options)
    {
        $queryString = array_key_exists('querystring', $options) ? $options['querystring'] : [];
        $institutionId = isset($queryString['institution_id']) ? $queryString['institution_id'] : 0;

        $query
            ->find('list')
            ->where([
                $this->aliasField('institution_id') => $institutionId
            ]);

        return $query;
    }
}

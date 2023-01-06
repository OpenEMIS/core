<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class CountriesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('countries');
        parent::initialize($config);

        $this->hasMany('ApplicationInstitutionChoices', ['className' => 'Scholarship.ApplicationInstitutionChoices']);
        
        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('cascade');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->notEmpty('name', 'Please enter a name.');

        return $validator;
    }

    public function findOptionList(Query $query, array $options)
    {
        if(array_key_exists('location_type', $options['querystring'])) {
            
            $locationType = $options['querystring']['location_type'];
            
            $AreaAdministratives = TableRegistry::get('Area.AreaAdministratives');
            $domesticCountry = $AreaAdministratives
                ->find()
                ->where([$AreaAdministratives->aliasField('is_main_country') => 1])
                ->extract('name')
                ->first();

            if($locationType == 'DOMESTIC') {
                $query->where([$this->aliasField('name') => $domesticCountry]);
            } elseif ($locationType == 'INTERNATIONAL') {
                $query->where([$this->aliasField('name <>') => $domesticCountry]);
            }
        }
        
        return parent::findOptionList($query, $options);
    }
}

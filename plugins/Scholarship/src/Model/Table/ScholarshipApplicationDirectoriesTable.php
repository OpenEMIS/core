<?php
namespace Scholarship\Model\Table;

use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class ScholarshipApplicationDirectoriesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);
        $this->entityClass('User.User');

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);


    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator;
    }
}

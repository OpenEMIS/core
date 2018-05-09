<?php
namespace Scholarship\Model\Table;

use Cake\ORM\Query;
use App\Model\Table\AppTable;

class ApplicationDirectoriesTable extends AppTable
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

    public function findIndex(Query $query, array $options)
    {
        $query->where([$this->aliasField('super_admin') => 0]);

        return $query;
    }
}

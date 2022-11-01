<?php
namespace Scholarship\Model\Table;

use Cake\ORM\Query;
use App\Model\Table\AppTable;

class UsersDirectoryTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->entityClass('User.User');
        $this->addBehavior('User.AdvancedNameSearch');
    }

    public function findIndex(Query $query, array $options)
    {
        return $query->where([$this->aliasField('super_admin') => 0]);
    }

    public function findSearch(Query $query, array $options)
    {
        $searchOptions = $options['search'];
        $searchOptions['defaultSearch'] = false; // turn off defaultSearch function in page

        $search = $searchOptions['searchText'];
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['searchTerm' => $search]);
        }
        return $query;
    }
}

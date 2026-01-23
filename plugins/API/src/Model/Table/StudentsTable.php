<?php
namespace API\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use Exception;
use DateTime;

class StudentsTable extends AppTable {
    // POCOR-8578: start
	public function initialize(array $config): void
    {
		$this->setTable('security_users');
		parent::initialize($config);

		$this->hasMany('Identities', ['className' => 'User.Identities']);
        // POCOR-8330 start
        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);
        // POCOR-8330 end
        $this->hasMany('Nationalities', ['className' => 'User.UserNationalities',    'foreignKey' => 'security_user_id', 'dependent' => true]); // POCOR-8224
		$this->addBehavior('API.API');
	}
    // POCOR-8578: end
}

<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class LicenseTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('license_types');
        parent::initialize($config);

		$this->hasMany('LicenseClassifications', ['className' => 'FieldOption.LicenseClassifications', 'foreignKey' => 'license_type_id']);
        $this->hasMany('Licenses', ['className' => 'Staff.Licenses', 'foreignKey' => 'license_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}

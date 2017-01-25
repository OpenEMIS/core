<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class LicenseClassificationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('license_classifications');
        parent::initialize($config);

        $this->belongsToMany('Licenses', [
            'className' => 'Staff.Licenses',
            'joinTable' => 'staff_licenses_classifications',
            'foreignKey' => 'license_classification_id',
            'targetForeignKey' => 'staff_license_id',
            'through' => 'Staff.StaffLicensesClassifications',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('FieldOption.FieldOption');
    }
}

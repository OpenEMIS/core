<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class LicenseClassificationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('license_classifications');
        parent::initialize($config);

        $this->belongsTo('LicenseTypes', ['className' => 'FieldOption.LicenseTypes']);
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

    public function validationDefault(Validator $validator) 
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('name', [
                'ruleUnique' => [
                    'rule' => ['validateUnique', ['scope' => 'license_type_id']],
                    'provider' => 'table'
                ]
            ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $parentFieldOptions = $this->getLicenseTypes();
        $selectedParentFieldOption = $this->queryString('parent_field_option_id', $parentFieldOptions);

        if (!empty($selectedParentFieldOption)) {
            $query->where([$this->aliasField('license_type_id') => $selectedParentFieldOption]);
        }

        $this->controller->set(compact('parentFieldOptions', 'selectedParentFieldOption'));
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('license_type_id');
    }

    public function onUpdateFieldLicenseTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $parentFieldOptions = $this->getLicenseTypes();
            $selectedParentFieldOption = $this->queryString('parent_field_option_id', $parentFieldOptions);

            $attr['type'] = 'readonly';
            $attr['value'] = $selectedParentFieldOption;
            $attr['attr']['value'] = $parentFieldOptions[$selectedParentFieldOption];
        }
        return $attr;
    }

    private function getLicenseTypes()
    {
        $licenseTypes = $this->LicenseTypes
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        return $licenseTypes;
    }
}

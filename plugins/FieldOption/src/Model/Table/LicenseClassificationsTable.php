<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Entity;

class LicenseClassificationsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('license_classifications');
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

    public function validationDefault(Validator $validator): Validator
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

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $parentFieldOptions = $this->getLicenseTypes();
        $selectedParentFieldOption = $this->queryString('parent_field_option_id', $parentFieldOptions);

        if (!empty($selectedParentFieldOption)) {
            $query->where([$this->aliasField('license_type_id') => $selectedParentFieldOption]);
        }

        $this->controller->set(compact('parentFieldOptions', 'selectedParentFieldOption'));
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('license_type_id');
    }

    public function onUpdateFieldLicenseTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
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

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}

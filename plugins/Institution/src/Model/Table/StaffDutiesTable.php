<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;

class StaffDutiesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('staff_duties');
        parent::initialize($config);

        //$this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_locality_id']);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']); // POCOR-9768

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['StaffDuties' =>['id']
            ]
        ]);
    }

    // POCOR-9768: optional security role a duty type can carry. Not required for every duty type,
    // so it's deliberately left out of validationDefault (no requirePresence/notEmptyString).
    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('security_role_id', [
            'type' => 'select',
            'options' => $this->SecurityRoles->getSystemRolesList(),
            'after' => 'name',
            'select' => false
        ]);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra) // POCOR-9768
    {
        $this->field('security_role_id', ['after' => 'name']);
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
            case 'security_role_id': // POCOR-9768
                return __('Security Role');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
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
}

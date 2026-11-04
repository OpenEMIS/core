<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

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
    // POCOR-9878: keep the default '-- Select --' blank option so an assigned role can be removed again.
    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('security_role_id', [
            'type' => 'select',
            'options' => $this->SecurityRoles->getSystemRolesList(),
            'after' => 'name'
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
                return __('Modified User');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created User');
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

    // POCOR-9878: keep staff who already hold this duty in step with its new (or removed) security role
    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->isNew() && $entity->isDirty('security_role_id')
            && $entity->getOriginal('security_role_id') != $entity->security_role_id) {
            TableRegistry::getTableLocator()->get('Institution.InstitutionStaffDuties')->resyncDutyRoles($entity->id);
        }
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
}

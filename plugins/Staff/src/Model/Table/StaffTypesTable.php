<?php
namespace Staff\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;

class StaffTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('staff_types');
        parent::initialize($config);
        
        $this->hasMany('StaffPositionProfiles', ['className' => 'Institution.StaffPositionProfiles']);
        $this->hasMany('Staff', ['className' => 'Institution.Staff']);
        $this->hasMany('StaffTransferIn', ['className' => 'Institution.StaffTransferIn', 'foreignKey' => 'new_staff_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffTransferOut', ['className' => 'Institution.StaffTransferOut', 'foreignKey' => 'previous_staff_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStaff', ['className' => 'Report.InstitutionStaff']);
        $this->hasMany('Positions', ['className' => 'Staff.Positions']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Staff' => ['index']
        ]);
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

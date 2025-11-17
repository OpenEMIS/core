<?php
namespace Student\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;

class StudentVisitPurposeTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('student_visit_purpose_types');
        parent::initialize($config);

        $this->hasMany('StudentVisitRequests', ['className' => 'Student.StudentVisitRequests', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentVisits', ['className' => 'Student.StudentVisits', 'dependent' => true, 'cascadeCallbacks' => true]);
        
        $this->addBehavior('FieldOption.FieldOption');
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

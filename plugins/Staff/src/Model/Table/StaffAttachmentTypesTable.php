<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class StaffAttachmentTypesTable extends ControllerActionTable
{
    // public function initialize(array $config)
    // {
    //     $this->table('staff_training_categories');
    //     parent::initialize($config);

    //     $this->hasMany('StaffTrainings', ['className' => 'Staff.StaffTrainings', 'foreignKey' => 'staff_training_category_id']);

    //     $this->addBehavior('FieldOption.FieldOption');
    // }

    public function initialize(array $config): void
    {
        $this->setTable('staff_attachment_types');
        parent::initialize($config);

        // $this->hasMany('StudentBehaviours', ['className' => 'Student.StudentBehaviours', 'foreignKey' => 'student_behaviour_category_id']);

        // $this->belongsTo('BehaviourClassifications', ['className' => 'Student.BehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index']
        ]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
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

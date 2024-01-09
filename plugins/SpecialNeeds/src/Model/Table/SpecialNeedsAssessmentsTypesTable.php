<?php
namespace SpecialNeeds\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use ArrayObject;
use Cake\ORM\Entity;

class SpecialNeedsAssessmentsTypesTable extends ControllerActionTable
{
    
     
    public function initialize(array $config): void
    {
        $this->setTable('special_need_types');    

        parent::initialize($config);
        
        $this->hasMany('ExaminationCentreSpecialNeeds', ['className' => 'Examination.ExaminationCentreSpecialNeeds', 'foreignKey' => 'special_need_type_id']);
        $this->hasMany('SpecialNeedsAssessments', ['className' => 'SpecialNeeds.SpecialNeedsAssessments', 'foreignKey' => 'special_need_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('SpecialNeedsReferrals', ['className' => 'SpecialNeeds.SpecialNeedsReferrals', 'foreignKey' => 'reason_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'Staff' => ['index', 'add']
        ]);
    }

    
    public function getVisibleNeedTypes(array $options = [])
    {
        $query = $this
            ->find('visible')
            ->find('order')
            ->find('list')
            ->toArray();
        return $query;
    }

    public function beforeFind(Event $event, Query $query){
       return $query->where(['type'=>2]);
    }

    public function beforeAction() {
        $this->field('type', ['type' => 'hidden', 'visible' => ['index' => false, 'view' => false, 'edit' => true, 'add' => true], 'value' => 2]);
        // Start POCOR-7286
        $this->field('name', ['length' => 75]);
        // End POCOR-7286
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

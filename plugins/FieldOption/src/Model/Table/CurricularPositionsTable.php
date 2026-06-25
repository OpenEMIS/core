<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;

/**
 * POCOR-6673
 */
class CurricularPositionsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('curricular_positions');
        parent::initialize($config);
        $this->addBehavior('FieldOption.FieldOption');
        $this->hasMany('InstitutionCurricularStudents', ['className' => 'Institution.InstitutionCurricularStudents', 'foreignKey' => 'curricular_position_id']);

        $this->setDeleteStrategy('restrict');
    }

    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $curricularStudent = TableRegistry::getTableLocator()->get('Institution.InstitutionCurricularStudents'); 
        $checkStudent =  $curricularStudent->find()->where([$curricularStudent->aliasField('curricular_position_id')=>$entity->id])->first();     
             
        if(!empty($checkStudent)){
            $message = __('Its Associated with curricular Student ');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
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

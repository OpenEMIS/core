<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;

/**
 * POCOR-6673
 */
class CurricularPositionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('curricular_positions');
        parent::initialize($config);
        $this->addBehavior('FieldOption.FieldOption');
        $this->hasMany('InstitutionCurricularStudents', ['className' => 'Institution.InstitutionCurricularStudents', 'foreignKey' => 'curricular_position_id']);

        $this->setDeleteStrategy('restrict');
    }

    public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $curricularStudent = TableRegistry::get('institution_curricular_students'); 
        $checkStudent =  $curricularStudent->find()->where([$curricularStudent->aliasField('curricular_position_id')=>$entity->id])->first();     
             
        if(!empty($checkStudent)){
            $message = __('Its Associated with curricular Student ');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
        }
    }
}

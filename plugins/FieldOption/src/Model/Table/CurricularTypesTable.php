<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class CurricularTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('curricular_types');
        parent::initialize($config);
        
        $this->addBehavior('FieldOption.FieldOption');
    }

    public function beforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $institutionCurricular = TableRegistry::get('institution_curriculars'); 
        $checktype =  $institutionCurricular->find()->where([$institutionCurricular->aliasField('curricular_type_id')=>$entity->id])->first();     
             
        if(!empty($checktype)){
            $message = __('Its Associated with institution curricular ');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
        }
    }
}

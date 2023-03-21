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

//POCOR-6673
class CurricularTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('curricular_types');
        parent::initialize($config);
        
        $this->addBehavior('FieldOption.FieldOption');
        $this->hasMany('InstitutionCurriculars', ['className' => 'Institution.InstitutionCurriculars', 'foreignKey' => 'curricular_type_id']);

        $this->setDeleteStrategy('restrict');

    }

    public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $curricularStudent = TableRegistry::get('institution_curriculars'); 
        $checktype =  $curricularStudent->find()->where([$curricularStudent->aliasField('curricular_type_id')=>$entity->id])->first();     
             
        if(!empty($checktype)){
            $message = __('Its Associated with curricular Student ');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
        }
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('category');
        $this->setFieldOrder([
            'name','default','category', 'international_code','national_code']);
    }

    public function onUpdateFieldCategory(Event $event, array $attr, $action, Request $request)
    {
        $categories = array(1 =>'Curricular', 0=>'Extracurricular');
        $entity = $attr['entity'];
        if ($action == 'add') {
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Category') . ' --']+$categories;
            $attr['onChangeReload'] = 'changeStatus';
        }
        elseif ($action == 'edit') {
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Category') . ' --']+$categories;
            $attr['onChangeReload'] = 'changeStatus';
        }
        return $attr;
    }
    public function onGetCategory(Event $event, Entity $entity)
    {
        return $entity->category ? __('Curricular') : __('Extracurricular');
    }
}

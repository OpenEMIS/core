<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\ORM\ResultSet;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;

//POCOR-6673
class CurricularTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('curricular_types');
        parent::initialize($config);
        
        $this->addBehavior('FieldOption.FieldOption');
        $this->hasMany('InstitutionCurriculars', ['className' => 'Institution.InstitutionCurriculars', 'foreignKey' => 'curricular_type_id']);

        $this->setDeleteStrategy('restrict');

    }

    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $curricularStudent = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars'); 
        $checktype =  $curricularStudent->find()->where([$curricularStudent->aliasField('curricular_type_id')=>$entity->id])->first();     
             
        if(!empty($checktype)){
            $message = __('Its Associated with curricular Student ');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
        }
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('category');
        $this->setFieldOrder([
            'name','default','category', 'international_code','national_code']);
    }

    public function onUpdateFieldCategory(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $categories = array(1 =>'Co-Curricular', 0=>'Extracurricular'); //POCOR-7751
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
    public function onGetCategory(EventInterface $event, Entity $entity)
    {
        return $entity->category ? __('Co-Curricular') : __('Extracurricular'); //POCOR-7751
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
            case 'category':
                return __('Category');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}

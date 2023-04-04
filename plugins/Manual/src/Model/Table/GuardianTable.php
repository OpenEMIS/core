<?php
namespace Manual\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\ResultSet;

class GuardianTable extends ControllerActionTable
{
    // private $defaultMarkType;

    public function initialize(array $config)
    {
        $this->table('manuals');
        parent::initialize($config);
        $this->toggle('add', false);
        // $this->toggle('search', false);
        $this->toggle('remove', false);
        $this->toggle('reorder', false);
        $this->removeBehavior('Reorder');
    } 

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $query = $extra['query'];
        // $extra['pagination'] = false;
        // $extra['auto_contain'] = false;
        $query
        // ->find('visible')
        ->select([
            $this->aliasField('id'),
            $this->aliasField('function'),
            $this->aliasField('url'),
            $this->aliasField('module'),
            $this->aliasField('category'),
        ])
        ->where([$this->aliasField('module') => 'Guardian'])
        ->order([
            $this->aliasField('order')
        ])
        ;
        // return $query;
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupField();
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {        
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);   
        $this->field('visible', ['visible' => false]);   
        $this->field('controller', ['visible' => false]);   
        $this->field('module', ['visible' => false]);   
        $this->field('category', ['visible' => true]);   
        $this->field('parent_id', ['visible' => false]);   
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    { 
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);   
        $this->field('visible', ['visible' => false]);   
        $this->field('controller', ['visible' => false]);   
        $this->field('module', ['visible' => false]);   
        $this->field('category', ['visible' => false]);   
        $this->field('parent_id', ['visible' => false]);   
        $this->fields['function']['type'] = 'readonly';
        
    }

    private function setupField(Entity $entity = null)
    {    
        // $this->field('code');
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);   
        $this->field('visible', ['visible' => false]);   
        $this->field('controller', ['visible' => false]);   
        $this->field('module', ['visible' => false]);   
        $this->field('parent_id', ['visible' => false]);   

        $this->setFieldOrder(['category','function','url']);
    }


    public function onGetUrl(Event $event, Entity $entity)
    {
        $link  = $entity['url'];
        if(!empty($link)){
            return $event->subject()->Html->tag(__('a href='. $link .' target="_blank">'.$link.'</a'));
        }else{
            return '';
        }
    }

}

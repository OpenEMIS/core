<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class RecipientActivityStatusesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
    	$this->table('scholarship_recipient_activity_statuses');
        parent::initialize($config);

        $this->hasMany('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => 'scholarship_recipient_activity_status_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
    
    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if($entity->international_code == 'APPLICATION_APPROVED') {
            if (isset($extra['toolbarButtons']['edit'])) {
                unset($extra['toolbarButtons']['edit']);
            }

            if (isset($extra['toolbarButtons']['remove'])) {
                unset($extra['toolbarButtons']['remove']);
            }    
        }   
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
       if($entity->international_code == 'APPLICATION_APPROVED') {
            $url = $this->url('index');
            unset($url[1]);
            return $this->controller->redirect($url);
       }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (array_key_exists('remove', $buttons) && $entity->international_code == 'APPLICATION_APPROVED') {
            unset($buttons['remove']);
        }

        return $buttons;
    }
}

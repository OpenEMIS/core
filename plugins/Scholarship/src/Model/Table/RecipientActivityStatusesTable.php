<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class RecipientActivityStatusesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
    	$this->setTable('scholarship_recipient_activity_statuses');
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

        if (isset($buttons['remove']) && $entity->international_code == 'APPLICATION_APPROVED') {
            unset($buttons['remove']);
        }

        return $buttons;
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

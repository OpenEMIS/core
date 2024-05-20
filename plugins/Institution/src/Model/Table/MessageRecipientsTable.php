<?php

namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use App\Model\Traits\MessagesTrait;
use Cake\Core\Exception\Exception;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\Utility\Security;
use Cake\Network\Session;

/**
 * POCOR-7458 (to develop messaging  functionality)
 * <author>megha.gupta@mail.valuecoders.com</author>
 */
class MessageRecipientsTable extends ControllerActionTable{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);
        $this->toggle('view', false);
        $this->belongsTo('Messaging', ['className' => 'Institution.Messaging','foreignKey'=>"message_id"]);
        $this->belongsTo('SecurityUsers', ['className' => 'Security.SecurityUsers', 'foreignKey' => "recipient_id"]);
    }
    public function indexAfterAction(Event $event, Query $query)
    {
        $tabElements = $this->controller->getMessagingTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'MessageRecipients');
        $this->field('message_id', ['visible' => false]);
        $this->field('recipient_id',['visible' => false]);
        $this->field('openemis_no',['sort'=>true]);
        $this->field('name');
        $this->field('email');
        
    }
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $messageId = $this->Session->read('messageId');
        $query->contain('SecurityUsers');
        $query->where([$this->aliasField('message_id')=>$messageId]);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $row['openemis_no'] = $row->security_user->openemis_no;
                $row['email'] = $row->security_user->email;
                $row['name'] = $row->security_user->first_name." ". $row->security_user->last_name;
                return $row;
            });
        });
    }
}
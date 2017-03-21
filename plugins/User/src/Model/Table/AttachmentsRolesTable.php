<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Utility\Security;
use Cake\Event\Event;
use ArrayObject;

class AttachmentsRolesTable extends AppTable {
    public function initialize(array $config) {
    	$this->table('user_attachments_roles');
        parent::initialize($config);
        
        $this->belongsTo('Attachments', ['className' => 'User.Attachments', 'foreign_key' => 'user_attachment_id']);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {	
        if ($entity->isNew()) {
            $hashString = $entity->user_attachment_id . ',' . $entity->security_role_id;
            $entity->id = Security::hash($hashString, 'sha256');
        }
    }
}
<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;

class AttachmentRolesTable extends AppTable {
    public function initialize(array $config) {
    	$this->table('user_attachments_roles');
        parent::initialize($config);
        
        $this->belongsTo('Attachments', ['className' => 'User.Attachments']);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
    }
}
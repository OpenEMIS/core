<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class StaffBehaviourAttachmentsTable extends AppTable {
   public function initialize(array $config) {
       parent::initialize($config);

       $this->belongsTo('StaffBehaviours', ['className' => 'Institution.StaffBehaviours', 'foreignKey' => 'staff_behaviour_id']);
       
        $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]);
   }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator->requirePresence(['file_name', 'file_content']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(Event $event, $scope, $action, $extra)
    {
        if ($action == 'download') {
            // check for the user permission to download here
            $event->stopPropagation();
            return true;
        }
    }
}
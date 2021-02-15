<?php
namespace Staff\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;
use Restful\Model\Table\RestfulAppTable;

class StaffPayslipsTable extends ControllerActionTable
{
    private $model = null;
    public function initialize(array $config)
    {
        $this->table('staff_payslips');
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StaffPayslips' => ['add']
        ]);
        $this->addBehavior('ControllerAction.FileUpload', ['size' => '2MB', 'contentEditable' => false, 'allowable_file_types' => 'all', 'useDefaultName' => true]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $Users = TableRegistry::get('security_users');
        $user_data= $Users
                    ->find()
                    ->where(['security_users.openemis_no' => $entity->openemis_id])
                    ->first();
        if ((!empty($user_data)  && $user_data->is_staff)) {
            return true;
        }else{
            $entity->errors('identity_number', __('Record not found'));
            return false;
        }  
    }
}

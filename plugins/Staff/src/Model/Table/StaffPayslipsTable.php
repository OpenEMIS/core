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
            'StaffPayslips' => ['index', 'add', 'edit']
        ]);
        $this->addBehavior('ControllerAction.FileUpload', ['size' => '2MB', 'contentEditable' => false, 'allowable_file_types' => 'all', 'useDefaultName' => true]);
    }

    // public function beforeAction(Event $event, ArrayObject $extra)
    // {
    //     $this->field('file_content', ['type' => 'binary', 'visible' => ['edit' => true]]);
    // }

    // public function onGetFileType(Event $event, Entity $entity)
    // {
    //     return $this->getFileTypeForView($entity->file_name);
    // }

    // public function onGetFileContent(Event $event, Entity $entity)
    // {
    //     $fileContent = $entity->file_content;
    //     $value = base64_encode(stream_get_contents($fileContent));//$fileContent;

    //     return $value;
    // }

    // public function beforeAction(Event $event, ArrayObject $extra)
    // {
    //     $this->field('file_content', ['type' => 'binary', 'visible' => ['edit' => true]]);
    // }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $entity = $this->convertBase64ToBinary($entity->);
        // $Users = TableRegistry::get('security_users');
        // $user_data= $Users
        //             ->find()
        //             ->where(['security_users.openemis_no' => $entity->openemis_id])
        //             ->first();
        // if ((!empty($user_data)  && $user_data->is_staff)) {
        //     return true;
        // }else{
        //     $entity->errors('identity_number', __('Record not found'));
        //     return false;
        // }  
    }

    private function convertBase64ToBinary(Entity $entity)
    {
        $table = $this->model;
        $schema = $table->schema();
        $columns = $schema->columns();

        foreach ($columns as $column) {
            $attr = $schema->column($column);
            if ($attr['type'] == 'binary' && $entity->has($column)) {
                if (is_resource($entity->$column)) {
                    $entity->$column = stream_get_contents($entity->$column);
                } else {
                    $value = urldecode($entity->$column);
                    $entity->$column = base64_decode($value);
                }
            }
        }
        return $entity;
    }
}

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
        if(!isset($entity->name)){
            $response["name"][] ="Field Can not be empty";
            $entity->errors($response);
                return false;
        }else if(!isset($entity->openemis_id)){
            $response["openemis_id"][] ="Field Can not be empty";
            $entity->errors($response);
                return false;
        }else if(!isset($entity->file_name)){
            $response["file_name"][] ="Field Can not be empty";
            $entity->errors($response);
                return false;
        }else if(!isset($entity->staff_id)){
            $response["staff_id"][] ="Field Can not be empty";
            $entity->errors($response);
                return false;
        }else if(!isset($entity->file_content)){
            $response["file_content"][] ="Field Can not be empty";
            $entity->errors($response);
                return false;
        }else{
            $apiSecuritiesScopes = TableRegistry::get('AcademicPeriod.ApiSecuritiesScopes');
            $apiSecurities = TableRegistry::get('AcademicPeriod.ApiSecurities');
            $apiSecuritiesData = $apiSecurities->find('all')
                ->select([
                    'ApiSecurities.id','ApiSecurities.name','ApiSecurities.add'
                ])
                ->where([
                    'ApiSecurities.name' => 'Payslips',
                    'ApiSecurities.model' => 'Staff.StaffPayslips'
                ])
                ->first();
            $apiSecuritiesScopesData = $apiSecuritiesScopes->find('all')
                ->select([
                    'ApiSecuritiesScopes.add'
                ])
                ->where([
                    'ApiSecuritiesScopes.api_security_id' => $apiSecuritiesData->id
                ])
                ->first();
            if($apiSecuritiesScopesData->add == 0){
                $response["message"][] ="Api is disabled";
                $entity->errors($response);
                return false;
            }else{
                $Users = TableRegistry::get('security_users');
                $user_data= $Users
                            ->find()
                            ->where(['security_users.openemis_no' => $entity->openemis_id])
                            ->first();
                if ((!empty($user_data)  && $user_data->is_staff)) {
                    return true;
                }else{
                    $response["openemis_id"][] ="Record not found";
                    $entity->errors($response);
                    return false;
                } 
            } 
        }

    }
}

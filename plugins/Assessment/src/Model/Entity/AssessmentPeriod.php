<?php
namespace Assessment\Model\Entity;

use DateTimeInterface;

use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

class AssessmentPeriod extends Entity
{
    protected $_virtual = ['editable', 'code_name'];//POCOR-6513 added code_name virtual

    protected function _getEditable()
    {
        $today = new FrozenDate();
        $dateEnabled = $this->getOriginal('date_enabled');
        $dateDisabled = $this->getOriginal('date_disabled');

        //POCOR-7400 start
        $assessment_period_id=$this->getOriginal('id');
        $user_id=$_SESSION['Auth']['User']['id']; // POCOR-8859
        $super_admin = $_SESSION['Auth']['User']['super_admin'];
        if($super_admin == 1){
            return true;
        }
//        $user_id= $this->created_user_id;
        $SecurityGroupUsersTable=TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        $securityGroupUserData=$SecurityGroupUsersTable->
                               find('all')->where([$SecurityGroupUsersTable->aliasField('security_user_id IS') => $user_id])
                               ->toArray();
        $ids=[];

        foreach( $securityGroupUserData as $key=>$value){
            $ids[]=$value['security_role_id'];
        }
        if(!empty($ids)){ // POCOR-8859
            $ExcludedSecurityRoleTable=TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriodExcludedSecurityRoles');
            $ExcludedSecurityRoleCount=$ExcludedSecurityRoleTable->find('all') // POCOR-8859
                                                               ->where([
                                                                'security_role_id In'=>$ids,
                                                                'security_role_id IN'=>$ids,
                                                                'assessment_period_id'=> $assessment_period_id
                                                               ])
                                                               ->count();

        }

        if($ExcludedSecurityRoleCount > 0){ // POCOR-8859
            return true; // POCOR-8859
        }
        //POCOR-7400 end

        if ($dateEnabled instanceof DateTimeInterface && $dateDisabled instanceof DateTimeInterface) {
             return $today->between($dateEnabled, $dateDisabled);
        }
        return false;
    }

    /**
     * concatenate Assessment Period's code and name
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     * Ticket No - POCOR-6513
     */
    protected function _getCodeName() {
        return $this->code . ' - ' . $this->name;
    }
}

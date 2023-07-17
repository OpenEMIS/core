<?php
namespace Assessment\Model\Entity;

use DateTimeInterface;

use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

class AssessmentPeriod extends Entity
{
    protected $_virtual = ['editable', 'code_name'];//POCOR-6513 added code_name virtual

    protected function _getEditable()
    {
        $today = new Date();
        $dateEnabled = $this->getOriginal('date_enabled');
        $dateDisabled = $this->getOriginal('date_disabled');

        //POCOR-7400 start
        $assessment_period_id=$this->getOriginal('id');
        $user_id=$_SESSION['Auth']['User']['id'];
        $SecurityGroupUsersTable=TableRegistry::get('security_group_users');
        $securityGroupUserData=$SecurityGroupUsersTable->
                               find('all')->where([$SecurityGroupUsersTable->aliasField('security_user_id') => $user_id])
                               ->toArray();
        $ids=[];
        foreach($securityGroupUserData as $key=>$value){
                $ids[]=$value['security_role_id'];
        }
        if($securityGroupUserData){
           
            $ExcludedSecurityRoleTable=TableRegistry::get('assessment_period_excluded_security_roles');
            $ExcludedSecurityRoleEntity=$ExcludedSecurityRoleTable->find('all')
                                                               ->where([
                                                                'security_role_id IN'=>$ids,
                                                                'assessment_period_id'=> $assessment_period_id
                                                               ])
                                                               ->toArray();
                                                              
        }
        if($ExcludedSecurityRoleEntity){
            return true;
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

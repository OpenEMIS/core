<?php
namespace User\Model\Entity;

use Cake\ORM\Entity;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;

class User extends Entity {
    protected $_virtual = ['name', 'name_with_id', 'default_identity_type', 'institution_name', 'student_status'];

    protected function _setPassword($password) {
        return (new DefaultPasswordHasher)->hash($password);
    }

    protected function getNameDefaults() {
        /* To create option field for Administration to set these default values for system wide use */
        return array(
            'middle'    => true,
            'third'     => true,
            'preferred' => false
        );
    }

    protected function getNameKeys($otherNames=[]) {
        $defaults = $this->getNameDefaults();
        $middle = (isset($otherNames['middle'])&&is_bool($otherNames['middle'])&&$otherNames['middle']) ? $otherNames['middle'] : $defaults['middle'];
        $third = (isset($otherNames['third'])&&is_bool($otherNames['third'])&&$otherNames['third']) ? $otherNames['third'] : $defaults['third'];
        $preferred = (isset($otherNames['preferred'])&&is_bool($otherNames['preferred'])&&$otherNames['preferred']) ? $otherNames['preferred'] : $defaults['preferred'];
        return array(
            'first_name'    =>  true,
            'middle_name'   =>  $middle,
            'third_name'    =>  $third,
            'last_name'     =>  true,
            'preferred_name'=>  $preferred
        );
    }

    /**
     * Get a user's fullname based on @getNameKeys settings
     * @todo  -hanafi-    $separator should be configurable instead of hard-coded. To add in config_items for the system's default fullname setting.
     *                    Currently, fullname is concatenating first_name and last_name only.
     * @return string user's fullname
     */
    protected function _getName() {
        $name = '';
        $separator = ' ';
        $keys = $this->getNameKeys();
        foreach($keys as $k=>$v){
            if(isset($this->$k)&&$v){
                if($k!='last_name'){
                    if($k=='preferred_name'){
                        $name .= $separator . '('. $this->$k .')';
                    } else {
                        $name .= $this->$k . $separator;
                    }
                } else {
                    $name .= $this->$k;
                }
            }
        }
        return trim(sprintf('%s', $name));
    }

    /**
     * Calls _getName() and returns the user's fullname prepended with user's openemis_no
     * @return string user's fullname with openemis_no
     */
    protected function _getNameWithId() {
        $name = $this->name;
        return trim(sprintf('%s - %s', $this->openemis_no, $name));
    }

    // public function getNameWithHistory($options=[]){
    //     $name = '';
    //     $separator = (isset($options['separator'])&&strlen($options['separator'])>0) ? $options['separator'] : ' ';
    //     $keys = $this->getNameKeys($options);
    //     foreach($keys as $k=>$v){
    //         if(isset($obj[$k])&&$v){
    //             if($k!='last_name'){
    //                 if($k=='preferred_name'){
    //                     $name .= $separator . '('. $obj[$k] . ((isset($obj['history_'.$k])) ? '<br>'.$obj['history_'.$k] .')' : ')');
    //                 } else {
    //                     $name .= $obj[$k] . ((isset($obj['history_'.$k])) ? '<br>'.$obj['history_'.$k] . $separator : $separator);
    //                 }
    //             } else {
    //                 $name .= $obj[$k] . ((isset($obj['history_'.$k])) ? '<br>'.$obj['history_'.$k] : '');
    //             }
    //         }
    //     }
    //     return (isset($options['openEmisId'])&&is_bool($options['openEmisId'])&&$options['openEmisId']) ? trim(sprintf('%s - %s', $obj['openemis_no'], $name)) : trim(sprintf('%s', $name));
    // }

    protected function _getDefaultIdentityType(){
        $data = "";
        $securityUserId = $this->id;

        $UserIdentities = TableRegistry::get('User.Identities');
        $UserIdentity = $UserIdentities
                ->find()
                ->contain(['Users'])
                ->where(['security_user_id' => $this->id])
                ->first();

        if(!empty($UserIdentity))
            $data = $UserIdentity->number;

        return $data;
    }

    protected function _getInstitutionName(){
        $data = "";
        $securityUserId = $this->id;

        $InstitutionSiteStudents = TableRegistry::get('Institution.InstitutionSiteStudents');
        $InstitutionSite = $InstitutionSiteStudents
                ->find()
                ->contain(['Institutions'])
                ->where(['security_user_id' => $this->id])
                ->first();

        if(!empty($InstitutionSite->institution))
            $data = $InstitutionSite->institution->name;

        return $data;
    }

    protected function _getStudentStatus(){
        $data = "";
        $securityUserId = $this->id;

        $InstitutionSiteStudents = TableRegistry::get('Institution.InstitutionSiteStudents');
        $StudentStatus = $InstitutionSiteStudents
                ->find()
                ->contain(['StudentStatuses'])
                ->where(['security_user_id' => $this->id])
                ->first();
     
        if(!empty($StudentStatus->student_status))
            $data = $StudentStatus->student_status->name;

        return $data;
    }
}

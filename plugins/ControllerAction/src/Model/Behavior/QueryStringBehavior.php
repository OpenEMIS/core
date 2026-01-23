<?php

namespace ControllerAction\Model\Behavior;

use Cake\ORM\Behavior;

class QueryStringBehavior extends Behavior
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function readQueryString($param = null){
        $model = $this->_table;
        if(property_exists($model, 'getQueryString')){
            return $model->getQueryString($param);
        }else{
            return $model->ControllerAction->getQueryString($param);
        }
    }

    public function getInstitutionID()
    {
        $institutionID = $this->readQueryString('institution_id');
        return $institutionID;
    }

    public function getGuardianID()
    {
        $guardianID = $this->readQueryString('guardian_id');
        return $guardianID;
    }

    public function getUserID()
    {
        $userID = $this->readQueryString('security_user_id');
        if (!$userID) {
            $userID = $this->readQueryString('user_id');
        }
        if (!$userID) {
            return null;
        }
        return $userID;
    }

    public function getStaffID()
    {
        $staffID = $this->readQueryString('staff_id');
        return $staffID;
    }

    public function getStudentID()
    {
        $studentID = $this->readQueryString('student_id');
        return $studentID;
    }
}

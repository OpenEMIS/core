<?php
namespace User\Model\Entity;

use Cake\ORM\Entity;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use App\Model\Traits\UserTrait;

class User extends Entity {
	use UserTrait;

    protected $_virtual = ['name', 'name_with_id', 'default_identity_type', 'student_institution_name', 'staff_institution_name', 'student_status', 'staff_status', 'programme_section', 'date_of_birth_formatted'];

    protected function _setPassword($password) {
        return (new DefaultPasswordHasher)->hash($password);
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
				->contain(['IdentityTypes'])
				->where(['security_user_id' => $this->id, 'IdentityTypes.default' => 1])
				->order(['IdentityTypes.default DESC'])
				->first();

		if(!empty($UserIdentity)) {
			$data = $UserIdentity->number;
		}

		return $data;
	}

    protected function _getStudentInstitutionName(){
        $data = "";
        $securityUserId = $this->id;

        $InstitutionSiteStudents = TableRegistry::get('Institution.InstitutionSiteStudents');
        $InstitutionSite = $InstitutionSiteStudents
                ->find()
                ->contain(['Institutions'])
                ->where(['security_user_id' => $securityUserId])
                ->first();

        if(!empty($InstitutionSite->institution))
            $data = $InstitutionSite->institution->name;

        return $data;
    }

    protected function _getStaffInstitutionName(){
        $data = "";
        $securityUserId = $this->id;

        $InstitutionSiteStaff = TableRegistry::get('Institution.InstitutionSiteStaff');
        $InstitutionSite = $InstitutionSiteStaff
                ->find()
                ->contain(['Institutions'])
                ->where(['security_user_id' => $securityUserId])
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
                ->where(['security_user_id' => $securityUserId])
                ->first();
     
        if(!empty($StudentStatus->student_status))
            $data = $StudentStatus->student_status->name;

        return $data;
    }

    protected function _getStaffStatus(){
        $data = "";
        $securityUserId = $this->id;

        $InstitutionSiteStudents = TableRegistry::get('Institution.InstitutionSiteStaff');
        $StaffStatus = $InstitutionSiteStudents
                ->find()
                ->contain(['StaffStatuses'])
                ->where(['security_user_id' => $securityUserId])
                ->first();
     
        if(!empty($StaffStatus->staff_status))
            $data = $StaffStatus->staff_status->name;

        return $data;
    }

    protected function _getProgrammeSection(){
		if ($this->institution_site_students) {
			$education_programme_id = $this->institution_site_students[0]->education_programme_id;
			$institutionId = $this->institution_site_students[0]->institution_site_id;
		}

		$EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
		$query = $EducationProgrammes
			->find()
			->where([$EducationProgrammes->aliasField($EducationProgrammes->primaryKey()) => $education_programme_id])
			->first();
		$educationProgrammeName = ($query)? $query->name: '';

		$InstitutionSiteSectionStudents = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
		$query = $InstitutionSiteSectionStudents->find()
			->where([$InstitutionSiteSectionStudents->aliasField('security_user_id') => $this->id])
			->order($InstitutionSiteSectionStudents->aliasField($InstitutionSiteSectionStudents->primaryKey()).' desc')
			;

		if (isset($institutionId)) {
			$query->contain(
				[
					'InstitutionSiteSections'  => function ($q) use ($institutionId) {
						return $q
							->select(['id', 'name'])
							->where(['InstitutionSiteSections.institution_site_id' => $institutionId]);
						}
				]
			);
		} else {
			$query->contain('InstitutionSiteSections');
		}

		$sectionName = [];
		foreach ($query as $key => $value) {
			if ($value->institution_site_section) {
				if (isset($value->institution_site_section->name)) {
					$sectionName[] = $value->institution_site_section->name;
				}
			}
		}
		// sectionName
		return $educationProgrammeName . '<span class="divider"></span>' . implode(', ', $sectionName);
    }

    protected function _getPosition() {
        $data = "";
        $securityUserId = $this->id;
        $InstitutionSiteStaffTable = TableRegistry::get('Institution.InstitutionSiteStaff');
        $sitestaff =  $InstitutionSiteStaffTable
                      ->find()
                      ->contain(['Positions.StaffPositionTitles'])
                      ->where([$InstitutionSiteStaffTable->aliasField('security_user_id') => $securityUserId])
                      ->first();
        $data = (!empty($sitestaff['position'])) ? $sitestaff['position']['staff_position_title']['name']: "";     
        return $data;
    }

    protected function _getDateOfBirthFormatted(){
        $Users = TableRegistry::get('User.Users');
        return $Users->formatDate($this->date_of_birth);
    } 

}

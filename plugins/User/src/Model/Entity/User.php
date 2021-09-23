<?php
namespace User\Model\Entity;

use Cake\ORM\Entity;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use App\Model\Traits\UserTrait;

class User extends Entity {
    use UserTrait;

    protected $_virtual = ['name', 'name_with_id', 'name_with_id_role', 'default_identity_type', 'has_special_needs'];

    protected function _setPassword($password) {
        if (empty($password)) {
            return null;
        } else {
            return (new DefaultPasswordHasher)->hash($password);
        }
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
            if(isset($this->{$k})&&$v){
                if($k!='last_name'){
                    if($k=='preferred_name'){
                        $name .= $separator . '('. $this->{$k} .')';
                    } else {
                        if (!empty($this->{$k})) {
                            $name .= $this->{$k} . $separator;
                        }
                    }
                } else {
                    $name .= $this->{$k};
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

    //POCOR-5688 starts
    /**
     * Calls _getName() and returns the user's fullname prepended with user's openemis_no and user's role
     * @return string user's fullname with openemis_no and user's role
     */
    protected function _getNameWithIdRole() {
        $name = $this->name;
        $securityUserId = $this->id;
        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
        $userRole = $SecurityGroupUsers
                    ->find()
                    ->select([
                        $SecurityGroupUsers->aliasField('security_role_id'),
                        $SecurityRoles->aliasField('name')
                    ])
                    ->leftJoin([$SecurityRoles->alias() => $SecurityRoles->table()], [
                       'security_role_id = ' . $SecurityRoles->aliasField('id')
                    ])
                    ->order([$SecurityRoles->aliasField('id') => 'ASC'])
                    ->where(['security_user_id' => $this->id])
                    ->group([$SecurityRoles->aliasField('id')]) //POCOR-6231
                    ->toArray(); 
        
        if (!empty($userRole )) { 
            $roles = [];
            foreach ($userRole as $key => $value) {
                $roles[] = $value->SecurityRoles['name'];
            }
        } 

        if (is_array($roles) && !empty($roles)) {
            $rolesUsers = implode(', ', $roles);
        }
        
        return trim(sprintf('%s - %s (%s)', $this->openemis_no, $name, $rolesUsers));
    }
    //POCOR-5688 ends

    protected function _getDefaultIdentityType() {
        $data = "";
        $securityUserId = $this->id;

        $UserIdentities = TableRegistry::get('User.Identities');
        $IdentityTypes = $UserIdentities->IdentityTypes;
        $default_identity_type = $IdentityTypes->getDefaultValue();
        $UserIdentity = $UserIdentities
                ->find()
                ->where(['security_user_id' => $this->id, 'identity_type_id' => $default_identity_type])
                ->first();

        if(!empty($UserIdentity)) {
            $data = $UserIdentity->number;
        }
        return $data;
    }

    protected function _getHasSpecialNeeds()
    {
        if ($this->offsetExists('special_needs')) {
            // If entity already contain SpecialNeeds, skip table registry
            return !empty($this->special_needs);
        } else {
            // If entity do not contain SpecialNeeds, manual table registry and check
            $SpecialNeedsAssessments = TableRegistry::get('SpecialNeeds.SpecialNeedsAssessments');
            return $SpecialNeedsAssessments
                ->exists([$SpecialNeedsAssessments->aliasField('security_user_id') => $this->id]);
        }
    }   
}

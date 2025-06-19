<?php
namespace API\Model\Entity;

use Cake\ORM\Entity;
use App\Model\Traits\UserTrait;

// POCOR-8578
class Student extends Entity {
    use UserTrait;

    protected $_virtual = ['name', 'name_with_id'];


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


}

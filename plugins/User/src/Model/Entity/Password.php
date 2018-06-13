<?php
namespace User\Model\Entity;

use Cake\ORM\Entity;
use Cake\Auth\DefaultPasswordHasher;

class Password extends Entity {
    protected function _setPassword($password) {
        if (empty($password)) {
            return null;
        } else {
            return (new DefaultPasswordHasher)->hash($password);
        }
    }
}

<?php
namespace User\Model\Entity;

use Cake\ORM\Entity;
/**
 * SecurityUserPassword Entity
 *
 * Represents a password history record for a security user. Each record stores
 * the user's previous hashed password whenever the password is updated,
 * enabling password history validation and rotation policies.
 *
 * @property int $id
 * @property int $security_user_id
 * @property string $old_password
 * @property int $created_user_id
 * @property \Cake\I18n\FrozenTime $created
 *
 * @author Ehteram Ahmad
 */
class SecurityUserPassword extends Entity
{
    protected $_accessible = [
        'security_user_id' => true,
        'old_password' => true,
        'created_user_id' => true,
        'created' => true,
    ];
}

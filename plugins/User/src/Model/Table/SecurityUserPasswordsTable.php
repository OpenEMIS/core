<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
/**
 * SecurityUserPasswordsTable
 *
 * This table stores the password history of security users. A new record is
 * created whenever a user's password is changed, allowing enforcement of
 * password rotation and password history policies.
 *
 * Associations:
 * - belongsTo SecurityUsers
 *
 * @author Ehteram Ahmad
 */
class SecurityUserPasswordsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('security_user_passwords');
        $this->setEntityClass('User.SecurityUserPassword');
        parent::initialize($config);

        $this->belongsTo('SecurityUsers', [
            'className' => 'User.Users',
            'foreignKey' => 'security_user_id',
        ]);
    }
}

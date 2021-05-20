<?php

use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR5987 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Backup table
        $this->execute('DROP TABLE IF EXISTS `zz_5987_user_identities`');
        $this->execute('CREATE TABLE `zz_5987_user_identities` LIKE `user_identities`');
        $this->execute('INSERT INTO `zz_5987_user_identities` SELECT * FROM `user_identities`');

        $UserNationalities = TableRegistry::get('User.UserNationalities');
        $Identities = TableRegistry::get('User.Identities');
        $getData = $UserNationalities
                    ->find()
                    ->toArray();
        if (!empty($getData)) {
           foreach ($getData as $value) {
               $userId = $value->security_user_id;
               $nationalityId = $value->nationality_id;
               $countData = $this->fetchAll('SELECT count(*) AS `COUNT` from `user_nationalities` where `security_user_id` = '.$userId.' ');
               if (!empty($countData) && $countData[0]['COUNT'] == 1) {
                   $getUserIdentity = $Identities->find()
                                ->where([$Identities->aliasField('security_user_id') => $userId])->first();
                    if (!empty($getUserIdentity)) {
                        $national = $getUserIdentity->nationality_id;
                        if ($national  != $nationalityId || is_null($national)) {
                            $query = $Identities->query();
                            $result = $query->update()
                                        ->set(['nationality_id' => $nationalityId])
                                        ->where(['security_user_id' => $userId])
                                        ->execute();
                        } else {
                            //do nothing
                        }
                    }
               }
           } 
        }
    }

    //rollback
   public function down()
   {
        $this->execute('DROP TABLE IF EXISTS `user_identities`');
        $this->execute('RENAME TABLE `zz_5987_user_identities` TO `user_identities`');
   }
}

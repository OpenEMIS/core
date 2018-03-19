<?php

use Phinx\Migration\AbstractMigration;

class POCOR4452 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up()
    {
        $contactInstitutionSql = "UPDATE security_functions
        SET `name` = 'Contacts - Institutions '
        WHERE `id` = 1047";

        $contactPeopleSql = "UPDATE security_functions
                SET `name` = 'Contacts - People'
                WHERE `id` = 1083";
        
        $this->execute($contactInstitutionSql);
        $this->execute($contactPeopleSql);     
    }

    public function down()
    {   
        $contactInstitutionSql = "UPDATE security_functions
        SET `name` = 'Contacts'
        WHERE `id` = 1047";

        $contactPeopleSql = "UPDATE security_functions
                SET `name` = 'Contact Persons'
                WHERE `id` = 1083";

        $this->execute($contactInstitutionSql);
        $this->execute($contactPeopleSql);   
    }
}

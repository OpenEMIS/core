<?php

use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;

class Pocor7459 extends AbstractMigration
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

        // institution_assets
        $this->updateFieldOptionsAddFields();
        $this->updateCreateTableAssetMakes();
        $this->updateCreateTableAssetModels();
        $this->updateAddSomeMakesAndModels();
        $this->updateCreateZTable();
        $this->updateRemoveAcademicPeriodId();
        $this->updateRenameColumnNameToDescription();
        $this->updateAddColumnAssetMake();
        $this->updateAddColumnAssetModel();
        $this->updateAddColumnSerialNumber();
        $this->updateAddColumnPurchaseOrder();
        $this->updateAddColumnPurchaseDate();
        $this->updateAddColumnCost();
        $this->updateAddColumnStocktakeDate();
        $this->updateAddColumnLifespan();
        $this->updateAddColumnInstitutionRoomId();
        $this->updateAddColumnUserId();
        $this->updateAddColumnDepreciation();

    }

    public function down()
    {
        $this->rollbackCreateZTableInstitutionAssets();
        $this->rollbackFieldOptionsAddFields();
        $this->rollbackCreateTableAssetModels();
        $this->rollbackCreateTableAssetMakes();
    }

    private function updateCreateZTable()
    {
        try {
            $this->execute('CREATE TABLE `z_7459_institution_assets` LIKE `institution_assets`');
        } catch (\Exception $e) {

        }
        try {
            $this->execute('INSERT IGNORE INTO `z_7459_institution_assets` SELECT * FROM `institution_assets`');
        } catch (\Exception $e) {

        }
    }

    private function rollbackCreateZTableInstitutionAssets()
    {
        try {
            $this->execute('CREATE TABLE `zz_7459_institution_assets` LIKE `institution_assets`');
            $this->execute('INSERT IGNORE INTO `zz_7459_institution_assets` SELECT * FROM `institution_assets`');
        } catch (\Exception $e) {

        }
        $this->dropTable('institution_assets');
        $this->table('z_7459_institution_assets')->rename('institution_assets');
        $this->dropTable('zz_7459_institution_assets');

        try {
            $this->execute("ALTER TABLE institution_assets ADD CONSTRAINT `insti_asset_fk_aca_per_id` FOREIGN KEY (`academic_period_id`) REFERENCES academic_periods(`id`)");
        } catch (\Exception $e) {

        }
        try {
            $this->execute("ALTER TABLE institution_assets ADD CONSTRAINT `insti_asset_fk_ins_id` FOREIGN KEY (`institution_id`) REFERENCES institutions(`id`)");
        } catch (\Exception $e) {

        }
        try {
            $this->execute("ALTER TABLE institution_assets ADD CONSTRAINT `insti_asset_fk_ass_con_id` FOREIGN KEY (`asset_condition_id`) REFERENCES asset_conditions(`id`)");
        } catch (\Exception $e) {

        }
        try {
            $this->execute("ALTER TABLE institution_assets ADD CONSTRAINT `insti_asset_fk_ass_sta_id` FOREIGN KEY (`asset_status_id`) REFERENCES asset_statuses(`id`)");
        } catch (\Exception $e) {

        }
        try {
            $this->execute("ALTER TABLE institution_assets ADD CONSTRAINT `insti_asset_fk_ass_typ_id` FOREIGN KEY (`asset_type_id`) REFERENCES asset_types(`id`)");
        } catch (\Exception $e) {

        }
    }

    private function updateRemoveAcademicPeriodId()
    {
        $table = $this->table('institution_assets');
        try {
            $this->execute("ALTER TABLE institution_assets DROP FOREIGN KEY `insti_asset_fk_aca_per_id`");
        } catch (\Exception $e) {

        }
        try {
            $table->removeIndexByName('academic_period_id'); // Drop the foreign key constraint
        } catch (\Exception $e) {

        }
        try {
            $table->removeColumn('academic_period_id'); // Remove the column
        } catch (\Exception $e) {

        }
        try {
            $table->update();
        } catch (\Exception $e) {

        }
    }

    private function updateRenameColumnNameToDescription()
    {
        $table = $this->table('institution_assets');
        try {
            $table->renameColumn('name', 'description');
        } catch (\Exception $e) {

        }
        try {
            $table->update();
        } catch (\Exception $e) {

        }
    }

    private function updateAddColumnPurchaseDate()
    {
        $table = $this->table('institution_assets');
        try {
            $table->addColumn('purchase_date', 'date', [
                'default' => null,
                'null' => true,
                'after' => 'purchase_order',
            ]);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        try {
            $table->update();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function updateAddColumnAssetMake()
    {
        $table = $this->table('institution_assets');
        try {
            $table->addColumn('asset_make_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'after' => 'description',
            ])->addIndex('asset_make_id');
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        try {
            $table->update();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        try {
            $this->execute("ALTER TABLE institution_assets ADD CONSTRAINT `insti_asset_fk_asset_make_id` FOREIGN KEY (`asset_make_id`) REFERENCES asset_makes(`id`)");
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function updateAddColumnAssetModel()
    {
        $table = $this->table('institution_assets');
        try {
            $table->addColumn('asset_model_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'after' => 'asset_make_id',
            ])->addIndex('asset_model_id');
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        try {
            $table->update();
        } catch (\Exception $e) {

        }
        try {
            $this->execute("ALTER TABLE institution_assets ADD CONSTRAINT `insti_asset_fk_asset_model_id` FOREIGN KEY (`asset_model_id`) REFERENCES asset_models(`id`)");
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function updateAddColumnSerialNumber()
    {
        $table = $this->table('institution_assets');
        try {
            $table->addColumn('serial_number', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
                'after' => 'asset_model_id'
            ]);
        } catch (\Exception $e) {

        }
        try {
            $table->addIndex('serial_number');
        } catch (\Exception $e) {

        }
        try {
            $table->update();
        } catch (\Exception $e) {

        }
    }

    private function updateAddColumnPurchaseOrder()
    {
        $table = $this->table('institution_assets');
        try {
            $table->addColumn('purchase_order', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
                'after' => 'serial_number'
            ]);
        } catch (\Exception $e) {

        }
        try {
            $table->addIndex('purchase_order');
        } catch (\Exception $e) {

        }

        try {
            $table->update();
        } catch (\Exception $e) {

        }
    }

    private function updateAddColumnCost()
    {
        $table = $this->table('institution_assets');
        try {
            $table->addColumn('cost', 'decimal', [
                'default' => null,
                'precision' => 50, // total digit
                'scale' => 2, // digit after decimal point
                'null' => true,
                'after' => 'purchase_order',
            ]);
        } catch (\Exception $e) {

        }
        try {
            $table->update();
        } catch (\Exception $e) {

        }
    }

    private function updateAddColumnStocktakeDate()
    {
        $table = $this->table('institution_assets');
        try {
            $table->addColumn('stocktake_date', 'date', [
                'default' => null,
                'null' => true,
                'after' => 'cost',
            ]);
        } catch (\Exception $e) {

        }
        try {
            $table->addIndex('stocktake_date');
        } catch (\Exception $e) {

        }
        try {
            $table->update();
        } catch (\Exception $e) {

        }
    }

    private function updateAddColumnLifespan()
    {
        $table = $this->table('institution_assets');
        try {
            $table->addColumn('lifespan', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'after' => 'stocktake_date'
            ]);
        } catch (\Exception $e) {

        }
        try {
            $table->update();
        } catch (\Exception $e) {

        }
    }

    private function updateAddColumnInstitutionRoomId()
    {
        $table = $this->table('institution_assets');
        try {
            $table->addColumn('institution_room_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'after' => 'lifespan',
            ])->addIndex('institution_room_id');;
        } catch (\Exception $e) {

        }
        try {
            $table->update();
        } catch (\Exception $e) {

        }
        try {
            $this->execute("ALTER TABLE institution_assets ADD CONSTRAINT `insti_asset_fk_insti_room_id` FOREIGN KEY (`institution_room_id`) REFERENCES institution_rooms(`id`)");
        } catch (\Exception $e) {

        }

    }

    private function updateAddColumnUserId()
    {
        $table = $this->table('institution_assets');
        try {
            $table->addColumn('user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'after' => 'institution_room_id',
            ])->addIndex('user_id');
        } catch (\Exception $e) {

        }
        try {
            $table->update();
        } catch (\Exception $e) {

        }
        try {
            $this->execute("ALTER TABLE institution_assets ADD CONSTRAINT `insti_asset_fk_user_id` FOREIGN KEY (`user_id`) REFERENCES security_users(`id`)");
        } catch (\Exception $e) {

        }
    }

    private function updateAddColumnDepreciation()
    {
        $table = $this->table('institution_assets');
        try {
            $table->addColumn('depreciation', 'decimal', [
                'default' => null,
                'precision' => 50, // total digit
                'scale' => 2, // digit after decimal point
                'null' => true,
                'after' => 'user_id',
            ]);
        } catch (\Exception $e) {

        }
        try {
            $table->update();
        } catch (\Exception $e) {

        }
    }

    private function updateFieldOptionsAddFields()
    {

        try {
            $this->execute('CREATE TABLE `z_7459_field_options` LIKE `field_options`');
        } catch (\Exception $e) {

        }
        try {
            $this->execute('INSERT IGNORE INTO `z_7459_field_options` SELECT * FROM `field_options`');
        } catch (\Exception $e) {

        }

        $order = $this->fetchRow("SELECT `order` FROM `field_options` ORDER BY `id` DESC LIMIT 1");
        $data = [
            [
                'name' => 'Asset Makes',
                'category' => 'Infrastructure',
                'table_name' => 'asset_makes',
                'order' => $order[0] + 1,
                'modified_by' => NULL,
                'modified' => NULL,
                'created_by' => '1',
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Asset Models',
                'category' => 'Infrastructure',
                'table_name' => 'asset_models',
                'order' => $order[0] + 2,
                'modified_by' => NULL,
                'modified' => NULL,
                'created_by' => '1',
                'created' => date('Y-m-d H:i:s'),
            ],
        ];
        try {
        $this->insert('field_options', $data);
        } catch (\Exception $e) {

        }
    }

    private function rollbackFieldOptionsAddFields()
    {
        $this->execute('CREATE TABLE `zz_7459_field_options` LIKE `institution_assets`');
        $this->execute('INSERT INTO `zz_7459_field_options` SELECT * FROM `institution_assets`');

        $this->execute('DROP TABLE IF EXISTS `field_options`');
        $this->execute('RENAME TABLE `z_7459_field_options` TO `field_options`');
        $this->execute('DROP TABLE IF EXISTS `zz_7459_field_options`');
    }

    private function updateCreateTableAssetMakes()
    {
//asset makes
        $table = $this->table('asset_makes', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This is a field option table containing the list of user-defined asset makes (brands) used by institution assets'
        ]);
        try {
            $table
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit' => 50,
                    'null' => false
                ])
                ->addColumn('order', 'integer', [
                    'default' => null,
                    'limit' => 3,
                    'null' => false
                ])
                ->addColumn('visible', 'integer', [
                    'default' => 1,
                    'limit' => 1,
                    'null' => false
                ])
                ->addColumn('editable', 'integer', [
                    'default' => 1,
                    'limit' => 1,
                    'null' => false
                ])
                ->addColumn('default', 'integer', [
                    'default' => 0,
                    'limit' => 1,
                    'null' => false
                ])
                ->addColumn('asset_type_id', 'integer', [
                    'default' => null,
                    'limit' => 11,
                    'null' => false
                ])
                ->addColumn('international_code', 'string', [
                    'default' => null,
                    'limit' => 50,
                    'null' => true
                ])
                ->addColumn('national_code', 'string', [
                    'default' => null,
                    'limit' => 50,
                    'null' => true
                ])
                ->addColumn('modified_user_id', 'integer', [
                    'default' => null,
                    'limit' => 11,
                    'null' => true
                ])
                ->addColumn('modified', 'datetime', [
                    'default' => null,
                    'null' => true
                ])
                ->addColumn('created_user_id', 'integer', [
                    'default' => null,
                    'limit' => 11,
                    'null' => false
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'null' => false
                ])
                ->addIndex('modified_user_id')
                ->addIndex('created_user_id')
                ->addIndex('asset_type_id')
                ->save();
        } catch (\Exception $e) {

        }
        try {
            $this->execute("ALTER TABLE asset_makes ADD CONSTRAINT `asset_make_fk_asset_type_id` FOREIGN KEY (`asset_type_id`) REFERENCES asset_types(`id`)");
        } catch (\Exception $e) {

        }


    }

    private function rollbackCreateTableAssetMakes()
    {
        try {
            $this->execute("ALTER TABLE asset_makes DROP FOREIGN KEY `asset_make_fk_asset_type_id`");
        } catch (\Exception $e) {

        }
        try {
            $this->dropTable('asset_makes');
        } catch (\Exception $e) {

        }
    }

    private function updateCreateTableAssetModels()
    {
//asset makes
        $table = $this->table('asset_models', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This is a field option table containing the list of user-defined asset models (brands) used by institution assets'
        ]);
        try{
        $table->create();
        }catch (\Exception $e){

        }
        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('visible', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('editable', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('default', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('asset_make_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->addIndex('asset_make_id')
            ->save();
        try {
            $this->execute("ALTER TABLE asset_models ADD CONSTRAINT `asset_model_fk_asset_make_id` FOREIGN KEY (`asset_make_id`) REFERENCES asset_types(`id`)");
        } catch (\Exception $e) {

        }


    }

    private function rollbackCreateTableAssetModels()
    {
        try {
            $this->execute("ALTER TABLE asset_models DROP FOREIGN KEY `asset_model_fk_asset_make_id`");
        } catch (\Exception $e) {

        }
        try {
            $this->dropTable('asset_models');
        } catch (\Exception $e) {

        }
    }

    private function updateAddSomeMakesAndModels()
    {
        $has_assets = false;
        $AssetTypes = TableRegistry::get('asset_types');
        $computer = $AssetTypes
            ->find()
            ->select(['id'])
            ->where([
                'name' => 'computer'
            ])
            ->first();
        if ($computer) {
            $computer_id = $computer->id;
            $data1 = [
                [
                    'id' => 1,
                    'name' => 'Apple',
                    'order' => 1,
                    'visible' => 1,
                    'editable' => 1,
                    'default' => 1,
                    'asset_type_id' => $computer_id,
                    'international_code' => NULL,
                    'national_code' => NULL,
                    'modified_user_id' => NULL,
                    'modified' => NULL,
                    'created_user_id' => 1,
                    'created' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 2,
                    'name' => 'Huawei',
                    'order' => 2,
                    'visible' => 1,
                    'editable' => 1,
                    'default' => 0,
                    'asset_type_id' => $computer_id,
                    'international_code' => NULL,
                    'national_code' => NULL,
                    'modified_user_id' => NULL,
                    'modified' => NULL,
                    'created_user_id' => 1,
                    'created' => date('Y-m-d H:i:s')
                ]
            ];
            try {
                $this->insert('asset_makes', $data1);
                $has_assets = true;
            } catch (\Exception $e) {

            }
        }
        if ($has_assets) {

            $data2 = [
                [
                    'id' => 1,
                    'name' => 'Macbook Pro 15',
                    'order' => 1,
                    'visible' => 1,
                    'editable' => 1,
                    'default' => 1,
                    'asset_make_id' => 1,
                    'international_code' => NULL,
                    'national_code' => NULL,
                    'modified_user_id' => NULL,
                    'modified' => NULL,
                    'created_user_id' => 1,
                    'created' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 2,
                    'name' => 'Matebook d15',
                    'order' => 2,
                    'visible' => 1,
                    'editable' => 1,
                    'default' => 0,
                    'asset_make_id' => 2,
                    'international_code' => NULL,
                    'national_code' => NULL,
                    'modified_user_id' => NULL,
                    'modified' => NULL,
                    'created_user_id' => 1,
                    'created' => date('Y-m-d H:i:s')
                ]
            ];

            $this->insert('asset_models', $data2);
        }
    }


    //

}


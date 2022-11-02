<?php

use Phinx\Migration\AbstractMigration;

class POCOR4414 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4414_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_4414_config_items` SELECT * FROM `config_items`');


        $this->execute("UPDATE `config_items` SET `name` = 'Map Center Longitude', `label` = 'Map Center Longitude', `code` = 'map_center_longitude' WHERE `code` = 'google_map_center_longitude'");
        $this->execute("UPDATE `config_items` SET `name` = 'Map Center Latitude', `label` = 'Map Center Latitude', `code` = 'map_center_latitude' WHERE `code` = 'google_map_center_latitude'");
        $this->execute("UPDATE `config_items` SET `name` = 'Map Zoom', `label` = 'Map Zoom', `code` = 'map_zoom' WHERE `code` = 'google_map_zoom'");
    }

    public function down()
    {
        $this->dropTable("config_items");
        $this->table("z_4414_config_items")->rename("config_items");
    }
}

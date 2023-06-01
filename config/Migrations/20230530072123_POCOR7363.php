<?php
use Migrations\AbstractMigration;

class POCOR7363 extends AbstractMigration
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
        // $this->execute('CREATE TABLE `zz_7363_field_options` LIKE `field_options`');
        // $this->execute('INSERT INTO `zz_7363_field_options` SELECT * FROM `field_options`');
       
        // $order = $this->fetchRow("SELECT `order` FROM `field_options` ORDER BY `id` DESC LIMIT 1");
     
        // $data=[
        //         [
        //             'name' => 'Food Types',
        //             'category' => 'Meals',
        //             'table_name' => 'food_types',
        //             'order' => $order[0]+1,
        //             'modified_by' => NULL,
        //             'modified'=>NULL,
        //             'created_by' =>'1',
        //             'created' => date('Y-m-d H:i:s'),
        //         ],
        //         [
        //             'name' => 'Meal Ratings',
        //             'category' => 'Meals',
        //             'table_name' => 'meal_ratings',
        //             'order' => $order[0]+2,
        //             'modified_by' => NULL,
        //             'modified'=>NULL,
        //             'created_by' => '1',
        //             'created' => date('Y-m-d H:i:s'),
        //         ],
        //     ]; 

        //     $this->insert('field_options', $data);

            // $this->execute("CREATE TABLE IF NOT EXISTS `food_types` (
            //     `id` int(11) NOT NULL AUTO_INCREMENT,
            //     `name` varchar(50) NOT NULL,
            //     `order` int(3) DEFAULT NULL,
            //     `visible` int(1) DEFAULT '1',
            //     `editable` int(1) DEFAULT '1',
            //     `default` int(1) DEFAULT '0',
            //     `international_code` varchar(50) DEFAULT NULL,
            //     `national_code` varchar(50) DEFAULT NULL,
            //     `modified_user_id` int(11) DEFAULT NULL,
            //     `modified` datetime DEFAULT NULL,
            //     `created_user_id` int(11) NOT NULL,
            //     `created` datetime NOT NULL,
            //      PRIMARY KEY (`id`)
            //   )  ENGINE=InnoDB DEFAULT CHARSET=utf8");
            
            // $data1=[
            //          [
            //             'id'=>1,
            //             'name' =>'Vegetable',
            //             'order' =>1,
            //             'visible' => 1,
            //             'editable' => 1,
            //             'default' => 1,
            //             'international_code' => NULL,
            //             'national_code'  =>NULL,
            //             'modified_user_id' => NULL,
            //             'modified' =>NULL,
            //             'created_user_id' =>1,
            //             'created'  =>date('Y-m-d H:i:s')
            //          ],
            //          [
            //             'id'=>2,
            //             'name' =>'Meat',
            //             'order' =>2,
            //             'visible' => 1,
            //             'editable' => 1,
            //             'default' => 0,
            //             'international_code' => NULL,
            //             'national_code'  =>NULL,
            //             'modified_user_id' => NULL,
            //             'modified' =>NULL,
            //             'created_user_id' =>1,
            //             'created'  =>date('Y-m-d H:i:s')
            //          ]
            //      ];
            
            // $this->insert('food_types',$data1);

            // $this->execute("CREATE TABLE IF NOT EXISTS `meal_ratings` (
            //     `id` int(11) NOT NULL AUTO_INCREMENT,
            //     `name` varchar(50) NOT NULL,
            //     `order` int(3) DEFAULT NULL,
            //     `visible` int(1) DEFAULT '1',
            //     `editable` int(1) DEFAULT '1',
            //     `default` int(1) DEFAULT '0',
            //     `international_code` varchar(50) DEFAULT NULL,
            //     `national_code` varchar(50) DEFAULT NULL,
            //     `modified_user_id` int(11) DEFAULT NULL,
            //     `modified` datetime DEFAULT NULL,
            //     `created_user_id` int(11) NOT NULL,
            //     `created` datetime NOT NULL,
            //      PRIMARY KEY (`id`)
            //   )  ENGINE=InnoDB DEFAULT CHARSET=utf8");
            
            // $data2=[
            //          [
            //             'id'=>1,
            //             'name' =>1,
            //             'order' =>1,
            //             'visible' => 1,
            //             'editable' => 1,
            //             'default' => 1,
            //             'international_code' => NULL,
            //             'national_code'  =>NULL,
            //             'modified_user_id' => NULL,
            //             'modified' =>NULL,
            //             'created_user_id' =>1,
            //             'created'  =>date('Y-m-d H:i:s')
            //          ],
            //          [
            //             'id'=>2,
            //             'name' =>2,
            //             'order' =>2,
            //             'visible' => 1,
            //             'editable' => 1,
            //             'default' => 0,
            //             'international_code' => NULL,
            //             'national_code'  =>NULL,
            //             'modified_user_id' => NULL,
            //             'modified' =>NULL,
            //             'created_user_id' =>1,
            //             'created'  =>date('Y-m-d H:i:s')
            //          ]
            //      ];
            
            // $this->insert('meal_ratings',$data2);

            

            // $table = $this->table('meal_food_records', [
            //    'collation' => 'utf8mb4_unicode_ci',
            //    'comment' => 'This field options table contains types of meal food type records'
            // ]);
            // $table->addColumn('meal_programmes_id', 'integer', [
            //    'default' => null,
            //    'limit' => 11,
            //    'comment' => 'links to meal_programmes.id',
            //    'null' => true
            // ])
            // ->addColumn('food_type_id', 'integer', [
            //    'default' => null,
            //    'limit' => 11,
            //    'comment' =>'links to food_type.id',
            //    'null' => true
            // ])  
            // ->save(); 
            $this->execute('CREATE TABLE IF NOT EXISTS `meal_food_records` (
               `id` int(11) NOT NULL AUTO_INCREMENT,
               `meal_programmes_id` int(11) ,
               `food_type_id` int(11),
               PRIMARY KEY (`id`),
               FOREIGN KEY (`meal_programmes_id`) REFERENCES `meal_programmes` (`id`),
               FOREIGN KEY (`food_type_id`) REFERENCES `food_types` (`id`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
             ');
           

    }
    
    public function down()
    {
    // $this->execute('DROP TABLE IF EXISTS `field_options`');
    // $this->execute('RENAME TABLE `zz_7363_field_options` TO `field_options`');

   //  $this->execute('DROP TABLE IF EXISTS `food_types`');
   //  $this->execute('DROP TABLE IF EXISTS `meal_ratings`');

    $this->execute('DROP TABLE meal_food_types');
    }

      
    
}
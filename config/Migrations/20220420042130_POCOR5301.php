<?php
use Migrations\AbstractMigration;

class POCOR5301 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
	 * This class is used for add records in list page view.
	 * @author Akshay patodi <akshay.patodi@mail.valuecoders.com>
	 * @ticket POCOR-5301
     */
    public function up()
    {
		
		 // config_items
    	$this->execute('DROP TABLE IF EXISTS `zz_5301_config_items`');
        $this->execute('CREATE TABLE `zz_5301_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_5301_config_items` SELECT * FROM `config_items`');
		
	    // config_item_options
	    $this->execute('DROP TABLE IF EXISTS `zz_5301_config_item_options`');
        $this->execute('CREATE TABLE `zz_5301_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `zz_5301_config_item_options` SELECT * FROM `config_item_options`');
        $this->execute('INSERT INTO `config_items` 
            (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
            (Null, "Default Number of records in List page", "list_page", "System", "Default Number of records in List page", "", "", "50", 1, 1, "Dropdown", "list_page", 1, CURRENT_DATE())');
	    
         // insert data to config_items table
        $configitemoptions = 
		[
            [
                'id' => Null,
                'option_type' => 'list_page',
                'option' => 10,
                'value' => 10,
                'order' => 1,
                'visible' => 1,
                
            ],
            [
                'id' => Null,
                'option_type' => 'list_page',
                'option' => 20,
                'value' => 20,
                'order' => 2,
                'visible' => 1,
            ],
            [
                'id' => Null,
                'option_type' => 'list_page',
                'option' => 30,
                'value' => 30,
                'order' => 3,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'list_page',
                'option' => 40,
                'value' => 40,
                'order' => 4,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'list_page',
                'option' => 50,
                'value' => 50,
                'order' => 5,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'list_page',
                'option' => 100,
                'value' => 100,
                'order' => 6,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'list_page',
                'option' => 200,
                'value' => 200,
                'order' => 7,
                'visible' => 1,
            ],
            
			
        ];

        $this->insert('config_item_options', $configitemoptions);		
    }

    public function down()
    {   
	    // config_items
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_5301_config_items` TO `config_items`');
		
	    // config_item_options
        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `zz_5301_config_item_options` TO `config_item_options`');
    }
}

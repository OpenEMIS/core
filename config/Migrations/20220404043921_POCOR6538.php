<?php
use Migrations\AbstractMigration;

class POCOR6538 extends AbstractMigration
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
		
		 // config_items
    	$this->execute('DROP TABLE IF EXISTS `zz_6538_config_items`');
        $this->execute('CREATE TABLE `zz_6538_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_6538_config_items` SELECT * FROM `config_items`');
		
	    // config_item_options
	    $this->execute('DROP TABLE IF EXISTS `zz_6538_config_item_options`');
        $this->execute('CREATE TABLE `zz_6538_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `zz_6538_config_item_options` SELECT * FROM `config_item_options`');
        $this->execute('INSERT INTO `config_items` 
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
            (6, "Time Zone", "time_zone", "System", "Time Zone", "", "(GMT 00:00)", 1, 1, "Dropdown", "time_zone", 1, CURRENT_DATE())');
	    
         // insert data to config_items table
        $configitemoptions = [
            [
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-12:00) International Date Line West',
                'value' => '(GMT-12:00) International Date Line West',
                'order' => 1,
                'visible' => 1,
                
            ],
            [
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-11:00) Midway Island, Samoa',
                'value' => '(GMT-11:00) Midway Island, Samoa',
                'order' => 2,
                'visible' => 1,
            ],
            [
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-10:00) Hawaii',
                'value' => '(GMT-10:00) Hawaii',
                'order' => 3,
                'visible' => 1,
            ],
            [
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-09:00) Alaska',
                'value' => '(GMT-09:00) Alaska',
                'order' => 4,
                'visible' => 1,
            ],
            [
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-08:00) Pacific Time (US and Canada); Tijuana',
                'value' => '(GMT-08:00) Pacific Time (US and Canada); Tijuana',
                'order' => 5,
                'visible' => 1,
            ],
            [
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-07:00) Mountain Time (US and Canada)',
                'value' => '(GMT-07:00) Mountain Time (US and Canada)',
                'order' => 6,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-07:00) Mountain Time (US and Canada)',
                'value' => '(GMT-07:00) Mountain Time (US and Canada)',
                'order' => 7,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-07:00) Chihuahua, La Paz, Mazatlan',
                'value' => '(GMT-07:00) Chihuahua, La Paz, Mazatlan',
                'order' => 8,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-07:00) Arizona',
                'value' => '(GMT-07:00) Arizona',
                'order' => 9,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-06:00) Central Time (US and Canada',
                'value' => '(GMT-06:00) Central Time (US and Canada',
                'order' => 10,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-06:00) Saskatchewan',
                'value' => '(GMT-06:00) Saskatchewan',
                'order' => 11,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-06:00) Guadalajara, Mexico City, Monterrey',
                'value' => '(GMT-06:00) Guadalajara, Mexico City, Monterrey',
                'order' => 12,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-06:00) Central America',
                'value' =>  '(GMT-06:00) Central America',
                'order' => 13,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-05:00) Eastern Time (US and Canada)',
                'value' => '(GMT-05:00) Eastern Time (US and Canada)',
                'order' => 14,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-05:00) Indiana (East)',
                'value' => '(GMT-05:00) Indiana (East)',
                'order' => 15,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-05:00) Bogota, Lima, Quito',
                'value' => '(GMT-05:00) Bogota, Lima, Quito',
                'order' => 16,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-04:00) Atlantic Time (Canada)',
                'value' => '(GMT-04:00) Atlantic Time (Canada)',
                'order' => 17,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-04:00) Caracas, La Paz',
                'value' => '(GMT-04:00) Caracas, La Paz',
                'order' => 18,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-04:00) Santiago',
                'value' => '(GMT-04:00) Santiago',
                'order' => 19,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-03:30) Newfoundland and Labrador',
                'value' =>  '(GMT-03:30) Newfoundland and Labrador',
                'order' => 20,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-03:00) Brasilia',
                'value' =>  '(GMT-03:00) Brasilia',
                'order' => 21,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-03:00) Buenos Aires, Georgetown',
                'value' => '(GMT-03:00) Buenos Aires, Georgetown',
                'order' => 22,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-03:00) Greenland',
                'value' => '(GMT-03:00) Greenland',
                'order' => 23,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-02:00) Mid-Atlantic',
                'value' => '(GMT-02:00) Mid-Atlantic',
                'order' => 24,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-01:00) Azores',
                'value' => '(GMT-01:00) Azores',
                'order' => 25,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT-01:00) Cape Verde Islands',
                'value' => '(GMT-01:00) Cape Verde Islands',
                'order' => 26,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT) Greenwich Mean Time: Dublin, Edinburgh, Lisbon, London',
                'value' => '(GMT) Greenwich Mean Time: Dublin, Edinburgh, Lisbon, London',
                'order' => 27,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT) Casablanca, Monrovia',
                'value' => '(GMT) Casablanca, Monrovia',
                'order' => 28,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague',
                'value' => '(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague',
                'order' => 29,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+01:00) Sarajevo, Skopje, Warsaw, Zagreb',
                'value' => '(GMT+01:00) Sarajevo, Skopje, Warsaw, Zagreb',
                'order' => 30,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+01:00) Brussels, Copenhagen, Madrid, Paris',
                'value' => '(GMT+01:00) Brussels, Copenhagen, Madrid, Paris',
                'order' => 31,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna',
                'value' => '(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna',
                'order' => 32,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+01:00) West Central Africa',
                'value' =>  '(GMT+01:00) West Central Africa',
                'order' => 33,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+02:00) Bucharest',
                'value' => '(GMT+02:00) Bucharest',
                'order' => 34,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+02:00) Cairo',
                'value' => '(GMT+02:00) Cairo',
                'order' => 35,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+02:00) Helsinki, Kiev, Riga, Sofia, Tallinn, Vilnius',
                'value' => '(GMT+02:00) Helsinki, Kiev, Riga, Sofia, Tallinn, Vilnius',
                'order' => 36,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+02:00) Athens, Istanbul, Minsk',
                'value' => '(GMT+02:00) Athens, Istanbul, Minsk',
                'order' => 37,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+02:00) Jerusalem',
                'value' => '(GMT+02:00) Jerusalem',
                'order' => 38,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+02:00) Harare, Pretoria',
                'value' => '(GMT+02:00) Harare, Pretoria',
                'order' => 39,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+03:00) Moscow, St. Petersburg, Volgograd',
                'value' => '(GMT+03:00) Moscow, St. Petersburg, Volgograd',
                'order' => 40,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+03:00) Kuwait, Riyadh',
                'value' => '(GMT+03:00) Kuwait, Riyadh',
                'order' => 41,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+03:00) Nairobi',
                'value' => '(GMT+03:00) Nairobi',
                'order' => 42,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+03:00) Baghdad',
                'value' =>  '(GMT+03:00) Baghdad',
                'order' => 43,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+03:30) Tehran',
                'value' => '(GMT+03:30) Tehran',
                'order' => 44,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+04:00) Abu Dhabi, Muscat',
                'value' => '(GMT+04:00) Abu Dhabi, Muscat',
                'order' => 45,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+04:00) Baku, Tbilisi, Yerevan',
                'value' => '(GMT+04:00) Baku, Tbilisi, Yerevan',
                'order' => 46,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+04:30) Kabul',
                'value' => '(GMT+04:30) Kabul',
                'order' => 47,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+05:00) Ekaterinburg',
                'value' => '(GMT+05:00) Ekaterinburg',
                'order' => 48,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+05:00) Islamabad, Karachi, Tashkent',
                'value' => '(GMT+05:00) Islamabad, Karachi, Tashkent',
                'order' => 49,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi',
                'value' => '(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi',
                'order' => 50,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+05:45) Kathmandu',
                'value' => '(GMT+05:45) Kathmandu',
                'order' => 51,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+06:00) Astana, Dhaka',
                'value' => '(GMT+06:00) Astana, Dhaka',
                'order' => 52,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+06:00) Sri Jayawardenepura',
                'value' => '(GMT+06:00) Sri Jayawardenepura',
                'order' => 53,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+06:00) Almaty, Novosibirsk',
                'value' => '(GMT+06:00) Almaty, Novosibirsk',
                'order' => 54,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+06:30) Yangon Rangoon',
                'value' => '(GMT+06:30) Yangon Rangoon',
                'order' => 55,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+07:00) Bangkok, Hanoi, Jakarta',
                'value' =>  '(GMT+07:00) Bangkok, Hanoi, Jakarta',
                'order' => 56,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+07:00) Krasnoyarsk',
                'value' => '(GMT+07:00) Krasnoyarsk',
                'order' => 57,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+08:00) Beijing, Chongqing, Hong Kong SAR, Urumqi',
                'value' => '(GMT+08:00) Beijing, Chongqing, Hong Kong SAR, Urumqi',
                'order' => 58,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+08:00) Kuala Lumpur, Singapore',
                'value' => '(GMT+08:00) Kuala Lumpur, Singapore',
                'order' => 59,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+08:00) Taipei',
                'value' => '(GMT+08:00) Taipei',
                'order' => 60,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+08:00) Perth',
                'value' => '(GMT+08:00) Perth',
                'order' => 61,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+08:00) Irkutsk, Ulaanbaatar',
                'value' => '(GMT+08:00) Irkutsk, Ulaanbaatar',
                'order' => 62,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+09:00) Seoul',
                'value' => '(GMT+09:00) Seoul',
                'order' => 63,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+09:00) Osaka, Sapporo, Tokyo',
                'value' =>  '(GMT+09:00) Osaka, Sapporo, Tokyo',
                'order' => 64,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+09:00) Yakutsk',
                'value' =>  '(GMT+09:00) Yakutsk',
                'order' => 65,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+09:30) Darwin',
                'value' => '(GMT+09:30) Darwin',
                'order' => 66,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+09:30) Adelaide',
                'value' => '(GMT+09:30) Adelaide',
                'order' => 67,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+10:00) Canberra, Melbourne, Sydney',
                'value' => '(GMT+10:00) Canberra, Melbourne, Sydney',
                'order' => 68,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+10:00) Brisbane',
                'value' => '(GMT+10:00) Brisbane',
                'order' => 69,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+10:00) Hobart',
                'value' => '(GMT+10:00) Hobart',
                'order' => 70,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+10:00) Vladivostok',
                'value' => '(GMT+10:00) Vladivostok',
                'order' => 71,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+10:00) Guam, Port Moresby',
                'value' => '(GMT+10:00) Guam, Port Moresby',
                'order' => 72,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+11:00) Magadan, Solomon Islands, New Caledonia',
                'value' => '(GMT+11:00) Magadan, Solomon Islands, New Caledonia',
                'order' => 73,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+12:00) Fiji Islands, Kamchatka, Marshall Islands',
                'value' => '(GMT+12:00) Fiji Islands, Kamchatka, Marshall Islands',
                'order' => 74,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => '(GMT+12:00) Auckland, Wellington',
                'value' => '(GMT+12:00) Auckland, Wellington',
                'order' => 75,
                'visible' => 1,
            ],
			[
                'id' => Null,
                'option_type' => 'time_zone',
                'option' => "(GMT+13:00) Nuku'alofa",
                'value' => "(GMT+13:00) Nuku'alofa",
                'order' => 76,
                'visible' => 1,
            ],
			
			
        ];

        $this->insert('config_item_options', $configitemoptions);		
    }

    public function down()
    {   
	    // config_items
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_6538_config_items` TO `config_items`');
		
	    // config_item_options
        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `zz_6538_config_item_options` TO `config_item_options`');
    }
}

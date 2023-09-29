<?php
use Migrations\AbstractMigration;

class POCOR5669 extends AbstractMigration
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
        // Inserting multiple records in config_items table
        $records = [
            [ 
                'name' => 'Latitude Mandatory', 
                'code' => 'latitude_mandatory',
                'type' => 'Coordinates',
                'label' => 'Latitude Mandatory',
                'value' => 1,
                'default_value' => 0,
                'field_type' => 'Dropdown',
                'option_type'=> 'configitems_type_value',
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
            ], 
            [ 
                'name' => 'Longitude Mandatory', 
                'code' => 'longitude_mandatory',
                'type' => 'Coordinates',
                'label' => 'Longitude Mandatory',
                'value' => 1,
                'default_value' => 0,
                'field_type' => 'Dropdown',
                'option_type'=> 'configitems_type_value',
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
            ],
             [ 
                'name' => 'Latitude Minimum', 
                'code' => 'latitude_minimum',
                'type' => 'Coordinates',
                'label' => 'Latitude Minimum',
                'value' => -90,
                'default_value' => -90,
                'field_type' => '',
                'option_type'=> '',
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
            ], 
             [ 
                'name' => 'Latitude Maximum', 
                'code' => 'latitude_maximum',
                'type' => 'Coordinates',
                'label' => 'Latitude Maximum',
                'value' => 90,
                'default_value' => 90,
                'field_type' => '',
                'option_type'=> '',
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
            ], 
             [ 
                'name' => 'Longitude Minimum', 
                'code' => 'longitude_minimum',
                'type' => 'Coordinates',
                'label' => 'Longitude Minimum',
                'value' => -180,
                'default_value' => -180,
                'field_type' => '',
                'option_type'=> '',
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
            ], 
             [ 
                'name' => 'Longitude Maximum', 
                'code' => 'longitude_maximum',
                'type' => 'Coordinates',
                'label' => 'Longitude Maximum',
                'value' => 180,
                'default_value' => 180,
                'field_type' => '',
                'option_type'=> '',
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
            ], 
             [ 
                'name' => 'Latitude Length', 
                'code' => 'latitude_length',
                'type' => 'Coordinates',
                'label' => 'Latitude Length',
                'value' => 7,
                'default_value' => 7,
                'field_type' => '',
                'option_type'=> '',
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
            ], 
             [ 
                'name' => 'Longitude Length', 
                'code' => 'longitude_length',
                'type' => 'Coordinates',
                'label' => 'Longitude Length',
                'value' => 7,
                'default_value' => 7,
                'field_type' => '',
                'option_type'=> '',
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
            ],  
        ];

        $this->insert('config_items', $records);
        // Inserting multiple records in config_items table

        // Inserting multiple records in config_item_options table
        $dataRecords = [   
                [ 
                    'option_type' => 'configitems_type_value', 
                    'option' => 'True',
                    'value' => 1
                ],
                [ 
                    'option_type' => 'configitems_type_value', 
                    'option' => 'False',
                    'value' => 0
                ]
            ];

        $this->insert('config_item_options', $dataRecords);
        // Inserting multiple records in config_item_options table

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 45');

        // Inserting multiple records in security_functions table
        $data = [
            [ 
                'name' => 'Map', 
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'General',
                'parent_id' => 8,
                '_view' => 'InstitutionMaps.index|InstitutionMaps.view',
                '_edit' => 'InstitutionMaps.edit',
                'order' => 46,
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
                  
            ]
        ];
        
        $this->insert('security_functions', $data);
        // Inserting multiple records in security_functions table

    }

    // rollback
    public function down()
    {
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 45');
        $this->execute('DELETE FROM security_functions WHERE name = Map AND controller = Institutions');
        $this->execute('DELETE FROM config_items WHERE type = Coordinates');
        $this->execute('DELETE FROM config_item_options WHERE option_type = configitems_type_value');
    }
}

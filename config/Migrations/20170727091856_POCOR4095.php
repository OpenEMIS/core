<?php

use Phinx\Migration\AbstractMigration;

class POCOR4095 extends AbstractMigration
{
    // commit
    public function up()
    {
        // labels
        $table = $this->table('labels');

        // inserting multiple rows
        $data = [
            [
                'id' => 'baaf8d0e-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionRooms',
                'field' => 'academic_period_id',
                'module_name' => 'Institutions -> Infrastructures -> Rooms',
                'field_name' => 'Academic Period',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'b2e40268-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionRooms',
                'field' => 'code',
                'module_name' => 'Institutions -> Infrastructures -> Rooms',
                'field_name' => 'Code',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'ad02119f-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionRooms',
                'field' => 'name',
                'module_name' => 'Institutions -> Infrastructures -> Rooms',
                'field_name' => 'Name',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'c4b43789-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionRooms',
                'field' => 'room_type_id',
                'module_name' => 'Institutions -> Infrastructures -> Rooms',
                'field_name' => 'Room Type',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'ce6a6ef1-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionRooms',
                'field' => 'infrastructure_condition_id',
                'module_name' => 'Institutions -> Infrastructures -> Rooms',
                'field_name' => 'Infrastructure Condition',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '9740e336-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionFloors',
                'field' => 'academic_period_id',
                'module_name' => 'Institutions -> Infrastructures -> Floors',
                'field_name' => 'Academic Period',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'a0595d43-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionFloors',
                'field' => 'code',
                'module_name' => 'Institutions -> Infrastructures -> Floors',
                'field_name' => 'Code',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'a89f6642-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionFloors',
                'field' => 'name',
                'module_name' => 'Institutions -> Infrastructures -> Floors',
                'field_name' => 'Name',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'ace9335b-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionFloors',
                'field' => 'area',
                'module_name' => 'Institutions -> Infrastructures -> Floors',
                'field_name' => 'Area',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'b1be4de1-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionFloors',
                'field' => 'floor_type_id',
                'module_name' => 'Institutions -> Infrastructures -> Floors',
                'field_name' => 'Floor Type',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'b55814b1-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionFloors',
                'field' => 'infrastructure_condition_id',
                'module_name' => 'Institutions -> Infrastructures -> Floors',
                'field_name' => 'Infrastructure Condition',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '63e74ee6-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionBuildings',
                'field' => 'academic_period_id',
                'module_name' => 'Institutions -> Infrastructures -> Buildings',
                'field_name' => 'Academic Period',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '674aa26b-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionBuildings',
                'field' => 'code',
                'module_name' => 'Institutions -> Infrastructures -> Buildings',
                'field_name' => 'Code',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '6ee87402-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionBuildings',
                'field' => 'name',
                'module_name' => 'Institutions -> Infrastructures -> Buildings',
                'field_name' => 'Name',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '72c8c847-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionBuildings',
                'field' => 'area',
                'module_name' => 'Institutions -> Infrastructures -> Buildings',
                'field_name' => 'Area',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '771b35e8-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionBuildings',
                'field' => 'year_acquired',
                'module_name' => 'Institutions -> Infrastructures -> Buildings',
                'field_name' => 'Year Acquired',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '7a25c1e4-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionBuildings',
                'field' => 'year_disposed',
                'module_name' => 'Institutions -> Infrastructures -> Buildings',
                'field_name' => 'Year Disposed',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '7d2313dd-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionBuildings',
                'field' => 'building_type_id',
                'module_name' => 'Institutions -> Infrastructures -> Buildings',
                'field_name' => 'Building Type',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '7fe9f8e8-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionBuildings',
                'field' => 'infrastructure_ownership_id',
                'module_name' => 'Institutions -> Infrastructures -> Buildings',
                'field_name' => 'Infrastructure Ownership',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '83db120e-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionBuildings',
                'field' => 'infrastructure_condition_id',
                'module_name' => 'Institutions -> Infrastructures -> Buildings',
                'field_name' => 'Infrastructure Condition',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '8d03d552-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionLands',
                'field' => 'academic_period_id',
                'module_name' => 'Institutions -> Infrastructures -> Lands',
                'field_name' => 'Academic Period',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '8fe0c8c5-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionLands',
                'field' => 'code',
                'module_name' => 'Institutions -> Infrastructures -> Lands',
                'field_name' => 'Code',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '92c67f0a-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionLands',
                'field' => 'name',
                'module_name' => 'Institutions -> Infrastructures -> Lands',
                'field_name' => 'Name',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '95c97d25-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionLands',
                'field' => 'area',
                'module_name' => 'Institutions -> Infrastructures -> Lands',
                'field_name' => 'Area',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '9877b9b2-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionLands',
                'field' => 'year_acquired',
                'module_name' => 'Institutions -> Infrastructures -> Lands',
                'field_name' => 'Year Acquired',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '9b7b42b2-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionLands',
                'field' => 'year_disposed',
                'module_name' => 'Institutions -> Infrastructures -> Lands',
                'field_name' => 'Year Disposed',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '9eda890f-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionLands',
                'field' => 'land_type_id',
                'module_name' => 'Institutions -> Infrastructures -> Lands',
                'field_name' => 'Land Type',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'a18f9c93-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionLands',
                'field' => 'infrastructure_ownership_id',
                'module_name' => 'Institutions -> Infrastructures -> Lands',
                'field_name' => 'Infrastructure Ownership',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 'a4bf6b4b-72a4-11e7-95dd-525400b263eb',
                'module' => 'InstitutionLands',
                'field' => 'infrastructure_condition_id',
                'module_name' => 'Institutions -> Infrastructures -> Lands',
                'field_name' => 'Infrastructure Condition',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        // this is a handy shortcut (phinx documentation, http://docs.phinx.org/en/latest/migrations.html)
        $this->insert('labels', $data);
    }

    // rollback
    public function down()
    {
        $this->execute('DELETE FROM labels WHERE id = "baaf8d0e-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "b2e40268-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "ad02119f-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "c4b43789-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "ce6a6ef1-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "9740e336-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "a0595d43-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "a89f6642-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "ace9335b-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "b1be4de1-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "b55814b1-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "63e74ee6-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "674aa26b-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "6ee87402-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "72c8c847-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "771b35e8-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "7a25c1e4-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "7d2313dd-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "7fe9f8e8-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "83db120e-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "8d03d552-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "8fe0c8c5-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "92c67f0a-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "95c97d25-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "9877b9b2-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "9b7b42b2-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "9eda890f-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "a18f9c93-72a4-11e7-95dd-525400b263eb"');
        $this->execute('DELETE FROM labels WHERE id = "a4bf6b4b-72a4-11e7-95dd-525400b263eb"');
    }
}

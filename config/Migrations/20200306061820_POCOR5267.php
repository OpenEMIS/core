<?php

use Migrations\AbstractMigration;

class POCOR5267 extends AbstractMigration
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
		// backup 
        $this->execute('CREATE TABLE `z_5267_api_securities` LIKE `api_securities`');
		$this->execute('INSERT INTO `z_5267_api_securities` SELECT * FROM `api_securities`');
		
		$stmt = $this->query('SELECT * FROM api_securities ORDER BY id DESC limit 1');
        $rows = $stmt->fetchAll();
		$uniqueId = $rows[0]['id'];
		
		$apiSecuritiesData = [
            [
                'id' => $uniqueId + 1,
				'name' => 'Gender',
				'model' => 'Institution.Genders',
				'index' => 1,
				'view' => 1,
				'add' => 0,
				'edit' => 0,
				'delete' => 0,
				'execute' => 0 
            ],
            [ 'id' => $uniqueId + 2, 'name' => 'Institution Types',
				'model' => 'Institution.Types',
				'index' => 1,
				'view' => 1,
				'add' => 0,
				'edit' => 0,
				'delete' => 0,
				'execute' => 0 
            ],
            [ 'id' => $uniqueId + 3, 'name' => 'Institution Providers',
				'model' => 'Institution.Providers',
				'index' => 1,
				'view' => 1,
				'add' => 0,
				'edit' => 0,
				'delete' => 0,
				'execute' => 0 
            ],
            [ 'id' => $uniqueId + 4, 'name' => 'Institution Sectors',
				'model' => 'Institution.Sectors',
				'index' => 1,
				'view' => 1,
				'add' => 0,
				'edit' => 0,
				'delete' => 0,
				'execute' => 0 
            ],
            [ 'id' => $uniqueId + 5, 'name' => 'Institution Ownerships',
				'model' => 'Institution.Ownerships',
				'index' => 1,
				'view' => 1,
				'add' => 0,
				'edit' => 0,
				'delete' => 0,
				'execute' => 0 
            ],
            [ 'id' => $uniqueId + 6, 'name' => 'Institution Localities',
				'model' => 'Institution.Localities',
				'index' => 1,
				'view' => 1,
				'add' => 0,
				'edit' => 0,
				'delete' => 0,
				'execute' => 0 
            ],
            [ 'id' => $uniqueId + 7, 'name' => 'Institution AreaAdministratives',
				'model' => 'Institution.AreaAdministratives',
				'index' => 1,
				'view' => 1,
				'add' => 0,
				'edit' => 0,
				'delete' => 0,
				'execute' => 0 
            ],
            [ 'id' => $uniqueId + 8, 'name' => 'User Nationalities',
				'model' => 'User.Nationalities',
				'index' => 1,
				'view' => 1,
				'add' => 0,
				'edit' => 0,
				'delete' => 0,
				'execute' => 0 
            ],
            [ 'id' => $uniqueId + 9, 'name' => 'Nationality Names',
				'model' => 'User.NationalityNames',
				'index' => 1,
				'view' => 1,
				'add' => 0,
				'edit' => 0,
				'delete' => 0,
				'execute' => 0
            ],
            [ 'id' => $uniqueId + 10, 'name' => 'Create Student',
				'model' => 'Institution.Students',
				'index' => 1,
				'view' => 1,
				'add' => 1,
				'edit' => 1,
				'delete' => 0,
				'execute' => 0
            ],
            [ 'id' => $uniqueId + 11, 'name' => 'Identity Types',
				'model' => 'FieldOption.IdentityTypes',
				'index' => 1,
				'view' => 1,
				'add' => 0,
				'edit' => 0,
				'delete' => 0,
				'execute' => 0
            ],                    
            [ 'id' => $uniqueId + 12, 'name' => 'Areas',
				'model' => 'Area.Areas',
				'index' => 1,
				'view' => 1,
				'add' => 1,
				'edit' => 1,
				'delete' => 0,
				'execute' => 0
            ],                    
            [ 'id' => $uniqueId + 13, 'name' => 'Status',
				'model' => 'Student.StudentStatuses',
				'index' => 1,
				'view' => 1,
				'add' => 1,
				'edit' => 1,
				'delete' => 0,
				'execute' => 0
            ],                    
            [ 'id' => $uniqueId + 14, 'name' => 'AcademicPeriod',
				'model' => 'AcademicPeriod.AcademicPeriods',
				'index' => 1,
				'view' => 1,
				'add' => 1,
				'edit' => 1,
				'delete' => 0,
				'execute' => 0
            ],                    
            [ 'id' => $uniqueId + 15, 'name' => 'Education',
				'model' => 'Education.EducationGrades',
				'index' => 1,
				'view' => 1,
				'add' => 1,
				'edit' => 1,
				'delete' => 0,
				'execute' => 0
            ]
        ];

        $apiSecuritiesTable = $this->table('api_securities');
        $apiSecuritiesTable->insert($apiSecuritiesData);
        $apiSecuritiesTable->saveData();
		
        // end 
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities`');
        $this->execute('RENAME TABLE `z_5267_api_securities` TO `api_securities`');
    }
}

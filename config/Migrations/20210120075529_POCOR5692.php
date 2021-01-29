<?php
use Migrations\AbstractMigration;

class POCOR5692 extends AbstractMigration
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
        //$this->execute('CREATE TABLE `zz_5692_meal_programmes` LIKE `meal_programmes`');
        //$this->execute('INSERT INTO `zz_5692_meal_programmes` SELECT * FROM `meal_programmes`');
        $this->execute('CREATE TABLE `zz_5692_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_5692_import_mapping` SELECT * FROM `import_mapping`');
        // meal_target_types
       $this->execute('CREATE TABLE `meal_target_types` (
                      `id` int(11) NOT NULL,
                      `name` varchar(100) NOT NULL,
                      `order` int(3) DEFAULT NULL,
                      `visible` int(1) DEFAULT NULL,
                      `international_code` varchar(10) DEFAULT NULL,
                      `national_code` varchar(10) DEFAULT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) DEFAULT NULL,
                      `created` datetime DEFAULT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1');

        $this->execute('INSERT INTO `meal_target_types` SELECT * FROM `meal_target_types`');
        $mealTargetTypes = [
            [
                'id' => 1,
                'name' => 'Individual',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'name' => 'Geographic',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3,
                'name' => 'Universal',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('meal_target_types', $mealTargetTypes);
        $this->execute("ALTER TABLE `meal_target_types` ADD PRIMARY KEY (`id`)");
        $this->execute("ALTER TABLE `meal_target_types` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;");

        //meal_status_types

        $this->execute('CREATE TABLE `meal_status_types` (
                  `id` int(11) NOT NULL,
                  `name` varchar(100) NOT NULL,
                  `order` int(3) DEFAULT NULL,
                  `visible` int(1) DEFAULT NULL,
                  `international_code` varchar(10) DEFAULT NULL,
                  `national_code` varchar(10) DEFAULT NULL,
                  `modified_user_id` int(11) DEFAULT NULL,
                  `modified` datetime DEFAULT NULL,
                  `created_user_id` int(11) DEFAULT NULL,
                  `created` datetime DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1');

        $this->execute('INSERT INTO `meal_status_types` SELECT * FROM `meal_status_types`');
        $mealStatusTypes = [
            [
                'id' => 1,
                'name' => 'On Time',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'name' => 'Early',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3,
                'name' => 'Late',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 4,
                'name' => 'Not Delivered',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('meal_status_types', $mealStatusTypes);
        $this->execute("ALTER TABLE `meal_status_types` ADD PRIMARY KEY (`id`)");
        $this->execute("ALTER TABLE `meal_status_types` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;");

        //meal_received
        $this->execute('CREATE TABLE `meal_received` (
              `id` int(11) NOT NULL,
              `code` varchar(100) NOT NULL,
              `name` varchar(250) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT="This table contains meal receiveds types used in student records"');
        $this->execute('INSERT INTO `meal_received` SELECT * FROM `meal_received`');
         $mealreceived = [
                [
                    'id' => 1,
                    'code' => 'None',
                    'name' => 'None'
                ],
                [
                    'id' => 2,
                    'code' => 'Free',
                    'name' => 'Free'
                ],
                [
                    'id' => 3,
                    'code' => 'Paid',
                    'name' => 'Paid'
                ]
            ];
        $this->insert('meal_received', $mealreceived);
        $this->execute("ALTER TABLE `meal_received` ADD PRIMARY KEY (`id`)");
        $this->execute("ALTER TABLE `meal_received` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;");

        // meal_programme_types
        $this->execute('CREATE TABLE `meal_programme_types` (
                      `id` int(11) NOT NULL,
                      `name` varchar(100) NOT NULL,
                      `order` int(3) DEFAULT NULL,
                      `visible` int(1) DEFAULT NULL,
                      `international_code` varchar(10) DEFAULT NULL,
                      `national_code` varchar(10) DEFAULT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) DEFAULT NULL,
                      `created` datetime DEFAULT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1');
        $this->execute('INSERT INTO `meal_programme_types` SELECT * FROM `meal_programme_types`');
        $mealProgrammeTypes = [
            [
                'id' => 1,
                'name' => 'In-school feeding programme',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'name' => 'Take-home rations programme',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3,
                'name' => 'Both programmes',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 4,
                'name' => 'Other',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('meal_programme_types', $mealProgrammeTypes);
        $this->execute("ALTER TABLE `meal_programme_types` ADD PRIMARY KEY (`id`)");
        $this->execute("ALTER TABLE `meal_programme_types` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;");

         // meal_nutritions
        $this->execute('CREATE TABLE `meal_nutritions` (
                  `id` int(11) NOT NULL,
                  `name` varchar(100) NOT NULL,
                  `order` int(3) DEFAULT NULL,
                  `visible` int(1) DEFAULT NULL,
                  `international_code` varchar(10) DEFAULT NULL,
                  `national_code` varchar(10) DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1'); 

        $this->execute('INSERT INTO `meal_nutritions` SELECT * FROM `meal_nutritions`');
        $mealNutritions = [
            [
                'id' => 1,
                'name' => 'Energy',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL            
            ],
            [
                'id' => 2,
                'name' => 'Protein',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL
            ],
            [
                'id' => 3,
                'name' => 'Fat',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL
            ],
            [
                'id' => 4,
                'name' => 'Iodine',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL
            ],
            [
                'id' => 5,
                'name' => 'Iron',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL
            ],
            [
                'id' => 6,
                'name' => 'Niacin',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL
            ],
            [
                'id' => 7,
                'name' => 'Riboflavin',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL
            ],
            [
                'id' => 8,
                'name' => 'Thiamine',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL
            ],
            [
                'id' => 9,
                'name' => 'Vitamin A',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL
            ],
            [
                'id' => 10,
                'name' => 'Vitamin B6',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL
            ],
            [
                'id' => 11,
                'name' => 'Vitamin B9',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL
            ],
            [
                'id' => 12,
                'name' => 'Vitamin B12',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL
            ],
            [
                'id' => 13,
                'name' => 'Vitamin C',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL
            ],
            [
                'id' => 14,
                'name' => 'Zinc',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL
            ]

        ];
        $this->insert('meal_nutritions', $mealNutritions);  
        $this->execute("ALTER TABLE `meal_nutritions` ADD PRIMARY KEY (`id`)");
        $this->execute("ALTER TABLE `meal_nutritions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;"); 

        // meal_implementers
        $this->execute('CREATE TABLE `meal_implementers` (
                      `id` int(11) NOT NULL,
                      `name` varchar(100) NOT NULL,
                      `order` int(3) DEFAULT NULL,
                      `visible` int(1) DEFAULT NULL,
                      `international_code` varchar(10) DEFAULT NULL,
                      `national_code` varchar(10) DEFAULT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) DEFAULT NULL,
                      `created` datetime DEFAULT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1');   

        $this->execute('INSERT INTO `meal_implementers` SELECT * FROM `meal_implementers`');
        $mealImplementer = [
            [
                'id' => 1,
                'name' => 'Government',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'name' => 'World Food Programme',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3,
                'name' => 'Other',
                'order' => 1,
                'visible' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('meal_implementers', $mealImplementer);
        $this->execute("ALTER TABLE `meal_implementers` ADD PRIMARY KEY (`id`)");
        $this->execute("ALTER TABLE `meal_implementers` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;");  

         // meal_benefits
        $table = $this->table('meal_benefits', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of meal benefits'
            ]); 

        $table->addColumn('name', 'string', [
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
        ->save();

        $this->execute("ALTER TABLE `meal_benefits` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");

        // meal_programmes
        $table = $this->table('meal_programmes', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of meal programmes'
            ]);
        $table->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to academic_periods.id',
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ]) 
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('type', 'integer', [
                'default' => 1,
                'limit' => 11,
                'comment' =>'links to meal_programme_types.id',
                'null' => false
            ])
            ->addColumn('trageting', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' =>'links to meal_target_types.id',
                'null' => false
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('amount', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('nutritional_content', 'string', [
                'default' => null,
                'limit' => 50,
                'comment' =>'links to meal_nutritions.id',
                'null' => true
            ]) 
            ->addColumn('implementer', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' =>'links to meal_implementers.id',
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
        ->save();

        $this->execute("ALTER TABLE `meal_programmes` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");

        // institution_meal_programmes
        $table = $this->table('institution_meal_programmes', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of institution meal programmes'
            ]);
        $table->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to academic_periods.id',
                'null' => false
            ])
            ->addColumn('meal_programmes_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to meal_programmes.id',
                'null' => false
            ])
            ->addColumn('date_received', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('quantity', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('delivery_status_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' =>'links to meal_status_types.id',
                'null' => false
            ])
            ->addColumn('comment', 'string', [
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
        ->save();

        $this->execute("ALTER TABLE `institution_meal_programmes` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT"); 

        // institution_meal_students
        $table = $this->table('institution_meal_students', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of institution meal students'
            ]);
        $table->addColumn('student_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to  security_users.id',
                'null' => false
            ]) 
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to academic_periods.id',
                'null' => false
            ])   
            ->addColumn('institution_class_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to institution_classes.id',
                'null' => false
            ]) 
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to instututions.id',
                'null' => false
            ]) 
            ->addColumn('meal_programmes_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to meal_programmes.id',
                'null' => true
            ]) 
            ->addColumn('date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('meal_benefit_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to meal_benefit.id',
                'null' => true
            ])
            ->addColumn('meal_received_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to meal_received.id',
                'null' => true
            ])
            ->addColumn('paid', 'float', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('comment', 'string', [
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

        ->save();

        $this->execute("ALTER TABLE `institution_meal_students` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");   

        // student_meal_marked_records
        $table = $this->table('student_meal_marked_records', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of student meal marked records'
            ]);
        $table->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to instututions.id',
                'null' => false
            ]) 
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to academic_periods.id',
                'null' => false
            ])   
            ->addColumn('institution_class_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to institution_classes.id',
                'null' => false
            ]) 
            ->addColumn('meal_programmes_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to meal_programmes.id',
                'null' => true
            ]) 
            ->addColumn('date', 'date', [
                'default' => null,
                'null' => true
            ])

            ->save();

        $this->execute("ALTER TABLE `student_meal_marked_records` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");  

        //import_mapping
        $data = [
            [
                'model' => 'Institution.InstitutionMealStudents',
                'column_name' => 'date',
                'description' => '( DD/MM/YYYY )',
                'order' => 1,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.InstitutionMealStudents',
                'column_name' => 'meal_programmes_id',
                'description' => 'Code',
                'order' => 3,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Meal',
                'lookup_model' => 'MealProgrammes',
                'lookup_column' => 'code'
            ],
            [
                'model' => 'Institution.InstitutionMealStudents',
                'column_name' => 'student_id',
                'description' => 'OpenEMIS ID',
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Security',
                'lookup_model' => 'Users',
                'lookup_column' => 'openemis_no'
            ],
            [
                'model' => 'Institution.InstitutionMealStudents',
                'column_name' => 'meal_received_id',
                'description' => 'Code',
                'order' => 4,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Meal',
                'lookup_model' => 'MealReceived',
                'lookup_column' => 'code'
            ],
            [
                'model' => 'Institution.InstitutionMealStudents',
                'column_name' => 'meal_benefit_id',
                'description' => 'Name',
                'order' => 5,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Meal',
                'lookup_model' => 'MealBenefit',
                'lookup_column' => 'id'
            ],
            [
                'model' => 'Institution.InstitutionMealStudents',
                'column_name' => 'comment',
                'description' => NULL,
                'order' => 6,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ]
        ];

        $this->insert('import_mapping', $data); 
        $this->execute("ALTER TABLE `meal_programmes` CHANGE `trageting` `targeting` INT(11) NOT NULL COMMENT 'links to meal_target_types.id'"); 

        $this->execute("ALTER TABLE `meal_programmes` DROP `nutritional_content`"); 

        // meal_nutritional_records
        $table = $this->table('meal_nutritional_records', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of meal nutritional records'
            ]);
        $table->addColumn('meal_programmes_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' => 'links to meal_programmes.id',
                'null' => true
            ])
            ->addColumn('nutritional_content_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'comment' =>'links to meal_nutritions.id',
                'null' => true
            ])  
        ->save();

         $this->execute("ALTER TABLE `institution_meal_programmes` CHANGE `quantity` `quantity_received` INT(11) NOT NULL"); 
         $this->execute("ALTER TABLE `institution_meal_programmes` CHANGE `date_received` `date_received` DATE NULL DEFAULT NULL");     
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE meal_target_types');
        $this->execute('DROP TABLE meal_status_types');
        $this->execute('DROP TABLE meal_received');
        $this->execute('DROP TABLE meal_programme_types');
        $this->execute('DROP TABLE meal_nutritions');
        $this->execute('DROP TABLE meal_implementers');
        $this->execute('DROP TABLE meal_benefits');
        $this->execute('DROP TABLE meal_programmes');
        $this->execute('DROP TABLE institution_meal_programmes');
        $this->execute('DROP TABLE institution_meal_students');
        $this->execute('DROP TABLE student_meal_marked_records');
        $this->execute('DROP TABLE meal_nutritional_records');
      
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_5692_import_mapping` TO `import_mapping`');

        $this->execute('DROP TABLE IF EXISTS `meal_programmes`');
        $this->execute('RENAME TABLE `zz_5692_meal_programmes` TO `meal_programmes`');

    }
    
}

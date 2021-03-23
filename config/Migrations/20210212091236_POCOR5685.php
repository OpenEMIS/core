<?php
use Migrations\AbstractMigration;

class POCOR5685 extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_5685_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5685_locale_contents` SELECT * FROM `locale_contents`');
        $this->execute('CREATE TABLE `z_5685_locale_content_translations` LIKE `locale_content_translations`');
        $this->execute('INSERT INTO `z_5685_locale_content_translations` SELECT * FROM `locale_content_translations`');
        // End
        
		
		$localeContent = [

            [
                'en' => 'Calendar',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'People',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Department',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Designation',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Uploaded On',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Profile Name',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Capacity',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Trips',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Schedules',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Schedule',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Timetables',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Interval',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Intervals',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Terms',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Feeders',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Outgoing',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Recipient Institution',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'District',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Incoming',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Feeder Institution',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'No Of Students',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Performance',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Outcomes',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Outcome Template',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Report Queue',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Email Status',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Current Staff',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Duties',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Duty Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Budget',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Amount (PM)',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Income',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Source',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Expenditure',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Need Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Priority',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Projects',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Funding Source',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Contract Date',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Meals',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Meal Programmes',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Date Received',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Quantity Received',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Delivery Status',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Transport',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Buses',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Plate Number',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Bus Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Trip Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Bus',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Repeat',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Committees',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Chairperson',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Wash',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Utilities',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Assets',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
            
        ];
        $this->insert('locale_contents', $localeContent);
        $getLocales1= $this->query("SELECT * FROM locales WHERE `name` = 'العربية' AND `iso` = 'ar'");
        $getLocalesId1 = $getLocales1->fetchAll();
        $localeId1 = $getLocalesId1[0]['id'];
        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Calendar'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'التقويم',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'People'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الأشخاص',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'People'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'القسم',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Department'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'القسم',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Designation'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'التعيين',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Uploaded On'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'تم رفعه إلى ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Profile Name'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'اسم الملف الشخصي ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Capacity'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'السعة',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Trips'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الرحلات',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Schedules'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الجداول',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Schedule'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الجداول',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Timetables'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الجدول الزمني ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Interval'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الفترة',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Intervals'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الفترات',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Terms'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الشروط',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Feeders'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الممولين',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Outgoing'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الصادر',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Recipient Institution'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'المؤسسة المستفيدة ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'District'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'المنطقة',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Incoming'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الوارد',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Feeder Institution'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'المؤسسة المانحة ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'No Of Students'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'عدد الطلاب ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Performance'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الأداء',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Outcomes'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'المخرجات',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Outcome Template'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'نموذج المخرج ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Report Queue'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'قائمة انتظار التقرير',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Email Status'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'حالة البريد الالكتروني ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Current Staff'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الموظفين الحاليين ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Duties'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'المهام',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Duty Type'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'نوع المهمة ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Budget'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الميزانية',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Amount (PM)'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'المبلغ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Income'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الدخل',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Source'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'المصدر',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Expenditure'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'النفقات',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Need Type'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'نوع الأحتياج ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Priority'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الأفضلية',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Projects'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'المشاريع',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Funding Source'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'مصدر التمويل ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Contract Date'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'تاريخ العقد ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Meals'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الوجبات',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Meal Programmes'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'برامج الوجبات ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Date Received'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'البيانات الواردة ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Quantity Received'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الكمية المستلمة ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Delivery Status'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'حالة التسليم ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Transport'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'النقل',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Buses'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الحافلات',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Plate Number'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'رقم اللوحة ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Bus Type'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'نوع الحافلة ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Trip Type'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'نوع الرحلة ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Bus'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الحافلة',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Repeat'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'متكرر',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Committees'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'اللجان',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Chairperson'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الشخص المسؤول ',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Wash'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'نظافة المياه والصرف الصحي',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Utilities'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'المرافق',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //

        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Assets'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الممتلكات',
                        'locale_content_id' => $localeContentsId,
                        'locale_id' => $localeId1,
                        'modified_user_id' => NULL,
                        'modified' => NULL,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $this->insert('locale_content_translations', $locale_content_translations_data);
            }
        }
        //


    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_5685_locale_contents` TO `locale_contents`');
        $this->execute('DROP TABLE IF EXISTS `locale_content_translations`');
        $this->execute('RENAME TABLE `z_5685_locale_content_translations` TO `locale_content_translations`');
    }
}

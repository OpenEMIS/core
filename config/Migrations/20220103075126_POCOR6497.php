<?php
use Migrations\AbstractMigration;

class POCOR6497 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6497_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_6497_locale_contents` SELECT * FROM `locale_contents`');
        $this->execute('CREATE TABLE `z_6497_locale_content_translations` LIKE `locale_content_translations`');
        $this->execute('INSERT INTO `z_6497_locale_content_translations` SELECT * FROM `locale_content_translations`');
        // End


        $localeContent = [

            [
                'en' => 'Student Mark Types',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Complete',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Status Update',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Institution Maps',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Contacts (Institution)',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Institution Contact Persons',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Mobile Number',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Secondary Teachers',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Student User',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Select Date from',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Profile Completeness',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Percent Complete',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Start Date',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'End Date',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Student Body Masses',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'All Areas Level',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'All Areas',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Identities / Nationalities',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Disabled',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Student Attendance',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Staff Attendance',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
            
        ];
        $this->insert('locale_contents', $localeContent);

        $getLocales1= $this->query("SELECT * FROM locales WHERE `name` = 'العربية' AND `iso` = 'ar'");
        $getLocalesId1 = $getLocales1->fetchAll();
        $localeId1 = $getLocalesId1[0]['id'];
        //
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Student Mark Types'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'أنواع علامة الطالب',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Complete'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'إكمال:',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Status Update'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'تحديث الحالة',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Institution Maps'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'خرائط المؤسسة',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Contacts (Institution)'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'جهات الاتصال (المؤسسة)',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Institution Contact Persons'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'جهات الاتصال بالمؤسسة',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Mobile Number'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'رقم الهاتف المحمول',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Secondary Teachers'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'المعلمين الثانوية',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Student User'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'مستخدم الطالب'
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Select Date from'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'حدد التاريخ من',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Profile Completeness'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'اكتمال الملف',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Percent Complete'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'في المئة كاملة:',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Start Date'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'تاريخ البدء',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'End Date'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'تاريخ الانتهاء',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Student Body Masses'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'كتل الجسم الطلابية',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'All Areas Level'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'كل مستوى المناطق',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'All Areas'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'كل الأماكن',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Identities / Nationalities'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الهويات / الجنسيات',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Disabled'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'عاجز',
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

        //////
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Institutions Completeness'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'اكتمال المؤسسات',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Student Attendance'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'حضور الطالب',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Staff Attendance'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'حضور الموظفين',
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
        $getLocaleContents = $this->query("SELECT * FROM locale_contents WHERE `en` = 'Attendances'");
        $getLocaleContentsId = $getLocaleContents->fetchAll();
        if(!empty($getLocaleContentsId)){
            $localeContentsId = $getLocaleContentsId[0]['id'];

            $localeContentTranslations = $this->query("SELECT * FROM locale_content_translations WHERE `locale_content_id` = $localeContentsId");
            $localeContentTranslationsData = $localeContentTranslations->fetchAll();
            if(empty($localeContentTranslationsData)){

                $locale_content_translations_data = [
                    [
                        'translation' => 'الحضور',
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

    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_6497_locale_contents` TO `locale_contents`');
        $this->execute('DROP TABLE IF EXISTS `locale_content_translations`');
        $this->execute('RENAME TABLE `z_6497_locale_content_translations` TO `locale_content_translations`');
    }
}

<?php
namespace App\Model\Traits;

trait OptionsTrait
{
    public function getSelectOptions($code)
    {
        $options = [
            'general' => [
                'active' => [1 => __('Active'), 0 => __('Inactive')],
                'yesno' => [1 => __('Yes'), 0 => __('No')],
                'enabledisable' => [1 => __('Enabled'), 0 => __('Disabled')],
            ],
            'Authentication' => [
                'yesno' => [0 => __('No'), 1 => __('Yes')]
            ],
            'Staff' => [
                'position_types' => [1 => __('Teaching'), 0 => __('Non-Teaching')]
            ],
            'Position' => [
                'types' => ['FULL_TIME' => __('Full-Time'), 'PART_TIME' => __('Part-Time')]
            ],
            'Assessments' => [
                'status' => [0 => __('New'), 1 => __('Draft'), 2 => __('Completed')],
                'types' => [1 => __('Non-Official'), 2 => __('Official')]
            ],
            'AssessmentItems' => [
                'mark_types' => ['MARKS' => __('Marks'), 'GRADES' => __('Grades')]
            ],
            'AssessmentGradingTypes' => [
                'result_type' => ['MARKS' => __('Marks'), 'GRADES' => __('Grades'), 'DURATION' => ('Duration')]
            ],
            'ExaminationGradingTypes' => [
                'result_type' => ['MARKS' => __('Marks'), 'GRADES' => __('Grades')]
            ],
            'CompetencyGradingTypes' => [
                'result_type' => ['STATUS' => __('Status'), 'MARKS' => __('Marks'), 'GRADES' => __('Grades')]
            ],
            'Surveys' => [
                'status' => [0 => __('New'), 1 => __('Draft'), 2 => __('Completed')]
            ],
            'Rubrics' => [
                'types' => [1 => __('Section Break'), 2 => __('Criteria')],
                'status' => [0 => __('New'), 1 => __('Draft'), 2 => __('Completed')]
            ],
            'StaffTransfers' => [
                'institution_type_selection' => [1 => __('Select Institution Types'), '-1' => __('Select All Institution Types')],
                'institution_sector_selection' => [1 => __('Select Institution Sectors'), '-1' => __('Select All Institution Sectors')]
            ],
            'TrainingCourses' => [
                'target_population_selection' => [1 => __('Select Target Populations'), '-1' => __('Select All Target Populations')]
            ],
            'TrainingSessions' => [
                'trainer_types' => ['Staff' => __('Staff'), 'Others' => __('Others')]
            ],
            'StaffPositionTitles' => [
                'position_grade_selection' => [1 => __('Select Position Grades'), '-1' => __('Select All Position Grades')]
            ],
            'StaffTrainingNeeds' => [
                'types' => ['CATALOGUE' => __('Course Catalogue'), 'NEED' => __('Need Category')]
            ],
            'Scholarships' => [
                'field_of_study_selection' => [1 => __('Select Field Of Studies'), '-1' => __('Select All Field Of Studies')],
                'interest_rate' => [0 => __('Fixed'), '1' => __('Variable')]
            ],
            'InstitutionChoices' => [
                'location_type' => ['DOMESTIC' => __('Domestic'), 'REGIONAL' => __('Regional'), 'INTERNATIONAL' => __('International'), 'ONLINE' => __('Online')]
            ],
            'Health' => [
                'blood_types' => [
                    'O+' => 'O+', 'O-' => 'O-',
                    'A+' => 'A+', 'A-' => 'A-',
                    'B+' => 'B+', 'B-' => 'B-',
                    'AB+' => 'AB+', 'AB-' => 'AB-'
                ]
            ],
            'StaffPositionProfiles' => [
                'FTE' => [
                    '0.25' => '25%',
                    '0.5' => '50%',
                    '0.75' => '75%',
                    '1' => '100%'
                ],
            ],
            'RoomTypes' => [
                'classifications' => [0 => __('Non-Classroom'), 1 => __('Classroom')]
            ],
            'Institutions' => [
                'classifications' => [1 => __('Academic Institution'), 2 => __('Non-Academic Institution')]
            ],            
            'InstitutionInfrastructure' => [
                'change_types' => [1 => __('Update Details'), 2 => __('End of Usage'), 3 => __('Change in Type')]
            ],
            'InstitutionAssets' => [
                'accessibility' => [1 => __('Accessible'), 0 => __('Not Accessible')],
                'purpose' => [1 => __('Teaching'), 0 => __('Non-Teaching')]
            ],
            'Shifts' => [
                'types' => [
                    1 => __('Single Shift Owner'),
                    2 => __('Single Shift Occupier'),
                    3 => __('Multiple Shift Owner'),
                    4 => __('Multiple Shift Occupier')
                ]
            ],
            'ExaminationCentres' => [
                'create_as' => [
                    'existing' => __('Existing Institution'),
                    'new' => __('New Examination Centre')
                ]
            ],
            'WorkflowSteps' => [
                'category' => [
                    1 => __('To Do'),
                    2 => __('In Progress'),
                    3 => __('Done')
                ]
            ],
            'WorkflowRules' => [
                'features' => [
                    'StudentAttendances' => [
                        'className' => 'Institution.InstitutionStudentAbsences',
                        'url' => [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'StudentAbsences'
                        ]
                    ],
                    'StudentUnmarkedAttendances' => [
                        'className' => 'Institution.InstitutionStudentUnmarkedAttendances',
                        'url' => [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'StudentUnmarkedAttendances'
                        ]
                    ],
                    'StaffBehaviours' => [
                        'className' => 'Institution.StaffBehaviours',
                        'url' => [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'StaffBehaviours'
                        ]
                    ]
                ]
            ],
            'AlertLogs' => [
                'feature_grouping' => [
                    'general' => __('General'),
                    'workflow' => __('Workflow')
                ]
            ],
            'Alert' => [
                'status_types' => [
                    0 => __('Stop'),
                    1 => __('Running')
                ]
            ],
            'AlertRules' => [
                'LicenseRenewal' => [
                    'before_after' => [
                        1 => __('Days before expiry date'),
                        // 2 => __('Days after expiry date')
                    ]
                ],
                'LicenseValidity' => [
                    'before_after' => [
                        1 => __('Days before expiry date'),
                        2 => __('Days after expiry date')
                    ]
                ],
                'RetirementWarning' => [
                    'before_after' => [
                        // 1 => __('Age before retirement value'),
                        2 => __('Age after retirement value')
                    ]
                ],
                'StaffEmployment' => [
                    'before_after' => [
                        1 => __('Days before employment date'),
                        2 => __('Days after employment date')
                    ]
                ],
                'StaffLeave' => [
                    'before_after' => [
                        1 => __('Days before end of leave date'),
                        // 2 => __('Days after end of leave date')
                    ]
                ],
                'StaffType' => [
                    'before_after' => [
                        1 => __('Days before staff end date'),
                        2 => __('Days after staff end date')
                    ]
                ],
                'ScholarshipApplication' => [
                    'workflow_category' => [
                        1 => __('To Do'),
                        2 => __('In Progress'),
                        3 => __('Done'),
                    ],
                    'before_after' => [
                        1 => __('Days before application close date')
                    ]
                ],
                'ScholarshipDisbursement' => [
                    'before_after' => [
                        1 => __('Days before disbursement date'),
                        2 => __('Days after disbursement date')
                    ]
                ]
            ]
        ];

        $index = explode('.', $code);
        foreach ($index as $i) {
            if (isset($options[$i])) {
                $options = $options[$i];
            } else {
                $options = false;
                break;
            }
        }
        return $options;
    }
}

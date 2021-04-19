<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\AppTable;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

class ConfigItemsTable extends AppTable
{
    use OptionsTrait;

    private $configurations = [];
    private $languagePath;
    private $languageFilePath;

    public function initialize(array $config)
    {
        $this->languagePath = TMP . 'cache' . DS . 'language_menu';
        $this->languageFilePath = TMP . 'cache'. DS . 'language_menu' . DS . 'language';
        parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        $this->belongsTo('ConfigItemOptions', ['className' => 'Configuration.ConfigItemOptions', 'foreignKey'=>'value']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index'],
            'Staff' => ['index'],
            'OpenEMIS_Classroom' => ['index'],
            'Map' => ['index'],
            'ClassStudents' => ['index'],
            'AssociationStudent' => ['index'],
            'SubjectStudents' => ['index']
        ]);
    }

    public function beforeAction(Event $event)
    {
        $this->ControllerAction->field('visible', ['visible' => false]);
        $this->ControllerAction->field('editable', ['visible' => false]);
        $this->ControllerAction->field('field_type', ['visible' => false]);
        $this->ControllerAction->field('option_type', ['visible' => false]);
        $this->ControllerAction->field('code', ['visible' => false]);

        $this->ControllerAction->field('name', ['visible' => ['index'=>true]]);
        $this->ControllerAction->field('default_value', ['visible' => ['view'=>true]]);
        
        if ($this->request->query['type'] == 9) {
          $this->ControllerAction->field('default_value', ['visible' => ['index'=>true]]);
        }

        $this->ControllerAction->field('type', ['visible' => ['view'=>true, 'edit'=>true]]);
        $this->ControllerAction->field('label', ['visible' => ['view'=>true, 'edit'=>true]]);
        $this->ControllerAction->field('value', ['visible' => true]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.AreaLevel.afterDelete'] = 'areaLevelAfterDelete';
        $events['Restful.Model.onAfterFormatResult'] = 'onAfterFormatResult';
        return $events;
    }

    public function onAfterFormatResult(Event $event, $data, ArrayObject $schema, ArrayObject $extra)
    {
        $action = $extra['action'];

        switch ($action) {
            case 'get_value':
                $value = '';
                if ($extra->offsetExists('conditions') && isset($extra['conditions'][$this->aliasField('code')])) {
                    $code = $extra['conditions'][$this->aliasField('code')];
                    $value = $this->value($code);
                }

                return ['value' => $value];
                break;
            default:
                break;
        }
    }

/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        $type = $request->query['type_value'];
        $query
            ->find('visible')
            ->where([$this->aliasField('type') => $type]);
    }


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
    public function editBeforeAction(Event $event)
    {
        $this->fields['type']['type'] = 'readonly';
        $this->fields['label']['type'] = 'readonly';

        $pass = $this->request->param('pass');
        if (is_array($pass) && !empty($pass)) {
            $id = $this->paramsDecode($pass[0]);
            $entity = $this->get($id);
        }
        if (isset($entity)) {
            /**
             * grab validation rules by either record code or record type
             */
            $validationRules = 'validate' . Inflector::camelize($entity->code);
            if (isset($this->{$validationRules})) {
                $this->validator()->add('value', $this->{$validationRules});
            } else {
                $validationRules = 'validate' . Inflector::camelize($entity->type);
                if (isset($this->{$validationRules})) {
                    $this->validator()->add('value', $this->{$validationRules});
                }
            }
        }
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (is_array($data[$this->alias()]['value'])) {
            if ($entity->code == 'openemis_id_prefix') {
                $value = $data[$this->alias()]['value']['prefix'];
                if (isset($data[$this->alias()]['value']['enable'])) {
                    $value .= ','.$data[$this->alias()]['value']['enable'];
                }
                $data[$this->alias()]['value'] = $value;
            }
        }
    }


/******************************************************************************************************************
**
** specific field methods
**
******************************************************************************************************************/

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions)
    {

        if ($entity->code == 'language') {
            if ($entity->value != 'en') {
                $entity = $this->find()
                    ->where([
                        $this->aliasField('code') => 'language_menu',
                        $this->aliasField('type') => 'System'
                    ])
                    ->first();
                $entity->value = 0;
                $this->save($entity);
            }
            $this->deleteLanguageCacheFile();
        } else if ($entity->code == 'language_menu') {
            $this->deleteLanguageCacheFile();
        }
    }

    private function deleteLanguageCacheFile()
    {
        $dir = new Folder($this->languagePath, true);
        $filesAndFolders = $dir->read();
        $files = $filesAndFolders[1];

        if (in_array('language', $files)) {
            $languageFile = new File($this->languageFilePath);
            $languageFile->delete();
        }
        $session = $this->request->session();
        $session->delete('System.language_menu');
    }

    public function onUpdateFieldValue(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['edit', 'add'])) {
            $pass = $request->param('pass');
            if (!empty($pass)) {
                $ids = $this->paramsDecode($pass[0]);
                $entity = $this->get($ids);

                // pr($entity);
                if ($entity->field_type == 'Dropdown') {
                    $exp = explode(':', $entity->option_type);
                    /**
                     * if options list is from a specific table
                     */
                    if (count($exp)>0 && $exp[0]=='database') {
                        $model = Inflector::pluralize($exp[1]);
                        $model = $this->getActualModeLocation($model);
                        $optionTable = TableRegistry::get($model);

                        $listeners = [
                            $optionTable
                        ];

                        $customOptions = new ArrayObject([]);
                        $this->dispatchEventToModels('Model.ConfigItems.populateOptions', [$customOptions], $this, $listeners);
                        
                        if (!empty((array) $customOptions)) {
                            $attr['options'] = $customOptions;
                        } else {
                            $attr['options'] = $optionTable->getList();
                        }

                    /**
                     * if options list is from ConfigItemOptions table
                     */
                    } else {
                        $optionTable = TableRegistry::get('ConfigItemOptions');
                        $options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
                            ->where([
                                'ConfigItemOptions.option_type' => $entity->option_type,
                                'ConfigItemOptions.visible' => 1
                            ])
                            ->toArray();
                        if (in_array($entity->option_type, ['date_format'])) {
                            foreach ($options as $key => $value) {
                                $options[$key] = date($key);
                            }
                        }

                        $attr['options'] = $options;
                    }

                    if (isset($this->request->data[$this->alias()]['value'])) {
                        $attr['onChangeReload'] = true;
                    }
                } else {
                    if ($entity->code == 'start_time') {
                        $attr['type'] = 'time';
                    } else if ($entity->code == 'hours_per_day' || $entity->code == 'days_per_week') {
                        $attr['type'] = 'integer';
                        $attr['attr'] = ['min' => 1];
                    } else if ($entity->type == 'Data Discrepancy') {
                        $attr['type'] = 'integer';
                        $attr['attr'] = ['min' => 0, 'max' => 100];
                    } else if ($entity->type == 'Data Outliers') {
                        $attr['type'] = 'integer';
                        $attr['attr'] = ['min' => 1, 'max' => 100];
                    } else if ($entity->type == 'Student Admission Age') {
                        $attr['type'] = 'integer';
                        $attr['attr'] = ['min' => 1, 'max' => 100];
                    } else if ($entity->code == 'no_of_shifts') {
                        $attr['type'] = 'integer';
                        $attr['attr'] = ['min' => 1, 'max' => 10];
                    } else if ($entity->code == 'training_credit_hour') {
                        $attr['type'] = 'integer';
                        $attr['attr'] = ['min' => 0];
                    } else if ($entity->code == 'openemis_id_prefix') {
                        $attr['type'] = 'element';
                        $attr['element'] = 'Configurations/with_prefix';
                        $attr['data'] = [];
                    } else if ($entity->type == 'Student Settings') {
                        $attr['type'] = 'integer';
                        $attr['attr'] = ['min' => 1, 'max' => 200];
                    } else if ($entity->code == 'latitude_minimum') {
                        $attr['attr'] = ['min' => -90, 'max' => 0];
                    } else if ($entity->code == 'latitude_maximum') {
                        $attr['attr'] = ['min' => 0, 'max' => 90];
                    } else if ($entity->code == 'longitude_minimum') {
                        $attr['attr'] = ['min' => -180, 'max' => 0];
                    } else if ($entity->code == 'longitude_maximum') {
                        $attr['attr'] = ['min' => 0, 'max' => 180];
                    } else if ($entity->code == 'latitude_length') {
                        $attr['type'] = 'integer';
                        $attr['attr'] = ['min' => 1, 'max' => 7];
                    } else if ($entity->code == 'longitude_length') {
                        $attr['type'] = 'integer';
                        $attr['attr'] = ['min' => 1, 'max' => 7];
                    }
                    else if ($entity->code == 'date_time_format') {
                        $attr['type'] = 'date';
                    }
                }
            }
        }
        return $attr;
    }

    public function onGetValue(Event $event, Entity $entity)
    {
        if ($entity->type == 'Custom Validation') {
            $attr['type'] = 'string';
            $event->subject()->HtmlField->includes['configItems'] = [
                'include' => true,
                'js' => [
                    'config'
                ]
            ];
        }
        $value = $this->recordValueForView('value', $entity);
        if (empty($value)) {
            $value = $this->recordValueForView('default_value', $entity);
        }
        return $value;
    }

    public function onGetDefaultValue(Event $event, Entity $entity)
    {
        return $this->recordValueForView('default_value', $entity);
    }

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action)
    {
        $includes['configItems'] = ['include' => true, 'js' => ['config']];
    }


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
    private function recordValueForView($valueField, $entity)
    {
        if ($entity->field_type == 'Dropdown') {
            $exp = explode(':', $entity->option_type);
            /**
             * if options list is from a specific table
             */
            if (count($exp)>0 && $exp[0]=='database') {
                $model = Inflector::pluralize($exp[1]);
                $model = $this->getActualModeLocation($model);
                $optionsModel = TableRegistry::get($model);

                if ($entity->code == 'institution_area_level_id' || $entity->code == 'institution_validate_area_level_id') {
                    // get area level from value
                    $value = $optionsModel->find()
                        ->where([$optionsModel->aliasField('level') => $entity->{$valueField}])
                        ->first();
                } else {
                    $value = $optionsModel->get($entity->{$valueField});
                }

                if (is_object($value)) {
                    return $value->name;
                } else {
                    return $entity->{$valueField};
                }

            /**
             * options list is from ConfigItemOptions table
             */
            }else if ($entity->type == 'Institution Completeness') {
                if ($entity->{$valueField} == 0) {
                 return __('Disabled');
                } else {
                 return __('Enabled');
                }               
            } else if ($entity->type == 'User Completeness') {
                if ($entity->{$valueField} == 0) {
                 return __('Disabled');
                } else {
                 return __('Enabled');
                }               
            } else {
                $optionsModel = TableRegistry::get('Configuration.ConfigItemOptions');
                $value = $optionsModel->find()
                    ->where([
                        'ConfigItemOptions.option_type' => $entity->option_type,
                        'ConfigItemOptions.value' => $entity->{$valueField},
                    ])
                    ->first();
                if (is_object($value)) {
                    if ($entity->code == 'date_format') {
                        return date($entity->{$valueField});
                    } else {
                        return $value->option;
                    }
                } else {
                    return $entity->{$valueField};
                }
            }
        } else if ($entity->code == 'openemis_id_prefix') {
            $exp = explode(',', $entity->{$valueField});
            if (!$exp[1]) {
                return __('Disabled');
            } else {
                return __('Enabled') . ' ('.$exp[0].')';
            }
        } else {
            if ($entity->code == 'time_format' || $entity->code == 'date_format') {
                return date($entity->{$valueField});
            } else {
                return $entity->{$valueField};
            }
        }
    }

    public function value($code)
    {
        $value = '';
        if (array_key_exists($code, $this->configurations)) {
            $value = $this->configurations[$code];
        } else {
            $entity = $this->findByCode($code)->first();
            if (empty($entity)) {
                return false;
            }
            $value = strlen($entity->value) ? $entity->value : $entity->default_value;
            $this->configurations[$code] = $value;
        }
        return $value;
    }

    public function defaultValue($code)
    {
        $value = '';
        if (array_key_exists($code, $this->configurations)) {
            $value = $this->configurations[$code];
        } else {
            $entity = $this->findByCode($code)->first();
            $value = $entity->default;
            $this->configurations[$code] = $value;
        }
        return $value;
    }

    private function getActualModeLocation($model)
    {
        $dir = dirname(__FILE__);
        if (!file_exists($dir . '/' . $model . 'Table.php')) {
            $dir = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/plugins';
            $folders = scandir($dir);
            foreach ($folders as $folder) {
                if (!in_array($folder, ['.', '..', '.DS_Store'])) {
                    if (file_exists($dir . '/' . $folder . '/src/Model/Table/' . $model . 'Table.php')) {
                        $model = $folder .'.'. $model;
                        break;
                    }
                }
            }
        }
        return $model;
    }

    public function areaLevelAfterDelete(Event $event, $areaLevel)
    {
        $entity = $this->findByCode('institution_area_level_id')->first();
        $configValue = strlen($entity->value) ? $entity->value : $entity->default_value;

        // if area level used for institution_area_level_id config is deleted
        if ($areaLevel->level == $configValue) {
            // update institution_area_level_id config to default level
            $entity->value = $entity->default_value;
            $this->save($entity);
        }
    }

    public function getSystemLanguageOptions()
    {
        $dir = new Folder($this->languagePath, true);
        $filesAndFolders = $dir->read();
        $files = $filesAndFolders[1];
        $languageFilePath = $this->languageFilePath;
        $languageFile = new File($languageFilePath, true);
        if (!in_array('language', $files)) {
            $showLanguage = $this->value('language_menu');
            $systemLanguage = $this->value('language');
            $languageArr = ['language_menu' => $showLanguage, 'language' => $systemLanguage];
            $status = $languageFile->write(json_encode($languageArr));
        }
        $languageArr = json_decode($languageFile->read(), true);
        return $languageArr;
    }

    public function getAutoGeneratedPassword()
    {
        $UsersTable = TableRegistry::get('User.Users');
        $ConfigItems = $this;
        $passwordLength = intval($ConfigItems->value('password_min_length')) > 3 ? intval($ConfigItems->value('password_min_length')) : 4;

        $numberOfGroup = 4;
        $sumTo = $passwordLength;

        // Group [0] - Number of lowercase character
        // Group [1] - Number of uppercase character
        // Group [2] - Number of numerical character
        // Group [3] - Number of special character
        $groups = [];

        while (count($groups) != 4) {
            $group = 0;
            $groups = [];
            while (array_sum($groups) != $sumTo) {
                $groups[$group] = mt_rand(1, $sumTo/mt_rand(1, $numberOfGroup));
                if (++$group == $numberOfGroup) {
                    $group = 0;
                }
            }
        }

        $upperCase = intval($ConfigItems->value('password_has_uppercase')) ? $groups[1] : 0;
        $numerical = intval($ConfigItems->value('password_has_number')) ? $groups[2] : 0;
        $specialCharacter = intval($ConfigItems->value('password_has_non_alpha')) ? $groups[3] : 0;
        return $UsersTable->generatePassword($passwordLength, $upperCase, $numerical, $specialCharacter);
    }

/******************************************************************************************************************
**
** value field validation rules based on specific codes
** refer to editBeforeAction() on how these validation rules are loaded dynamically
**
******************************************************************************************************************/

    private $validateSupportEmail = [
        'email' => [
            'rule'  => ['email'],
        ]
    ];

    private $validateWhereIsMySchoolStartLong = [
        'checkLongitude' => [
            'rule'  => ['checkLongitude'],
            'provider' => 'table',
        ]
    ];

    private $validateWhereIsMySchoolStartLat = [
        'checkLatitude' => [
            'rule'  => ['checkLatitude'],
            'provider' => 'table',
        ]
    ];

    private $validateWhereIsMySchoolStartRange = [
        'num' => [
            'rule'  => ['numeric'],
        ],
    ];

    private $validateSmsProviderUrl = [
        'url' => [
            'rule'  => ['url', true],
            'message' => 'Please provide a valid URL with http:// or https://',
        ]
    ];

    private $validateWhereIsMySchoolUrl = [
        'url' => [
            'rule'  => ['url', true],
            'message' => 'Please provide a valid URL with http:// or https://',
        ]
    ];

    private $validateStartTime = [
        'aPValue' => [
            'rule'  => ['amPmValue'],
            'provider' => 'table',
            'last' => true
        ]
    ];

    private $validateLowestYear = [
            'num' => [
                'rule'  => ['numeric'],
                'message' => 'Please provide a valid year',
                'last' => true
            ],
            'bet' => [
                'rule'  => ['range', 1900, 9999],
                'message' => 'Please provide a valid year',
                'last' => true
            ]
    ];

    private $validateHoursPerDay = [
        'num' => [
            'rule'  => ['numeric'],
            'message' => 'Numeric Value should be between 0 to 25',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', 1, 24],
            'message' => 'Numeric Value should be between 0 to 25',
            'last' => true
        ]
    ];

    private $validateDaysPerWeek = [
        'num' => [
            'rule'  => ['numeric'],
            'message' => 'Numeric Value should be between 0 to 8',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', 1, 7],
            'message' => 'Numeric Value should be between 0 to 8',
            'last' => true
        ]
    ];

    private $validateReportDiscrepancyVariationpercent = [
        'num' => [
            'rule'  => 'numeric',
            'message' => 'Numeric Value should be between -1 to 101',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', 0, 100],
            'message' => 'Numeric Value should be between -1 to 101',
            'last' => true
        ]
    ];

    private $validateDataOutliers = [
        'num' => [
            'rule'  => 'numeric',
            'message' => 'Numeric Value should be between 0 to 101',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', 1, 100],
            'message' => 'Numeric Value should be between 0 to 101',
            'last' => true
        ]
    ];

    private $validateStudentAdmissionAge = [
        'num' => [
            'rule'  => 'numeric',
            'message' => 'Numeric Value should be between -1 to 101',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', 0, 100],
            'message' => 'Numeric Value should be between -1 to 101',
            'last' => true
        ]
    ];

    private $validateNoOfShifts = [
        'num' => [
            'rule'  => 'numeric',
            'message' => 'Numeric Value should be between 0 to 11',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', 1, 10],
            'message' => 'Numeric Value should be between 0 to 11',
            'last' => true
        ]
    ];

    private $validateAutomatedStudentDaysAbsent = [
        'num' => [
            'rule'  => 'numeric',
            'message' => 'Numeric Value should be between 0 to 365',
        ],
        'bet' => [
            'rule'  => ['range', 1, 365],
            'message' => 'Numeric Value should be between 0 to 365',
            'last' => true
        ]
    ];

    private $validateTrainingCreditHour = [
        'num' => [
            'rule'  => 'numeric',
            'message' => 'Numeric Value should be between 0 to 1000',
        ],
        'bet' => [
            'rule'  => ['range', 1, 1000],
            'message' => 'Numeric Value should be between 0 to 1000',
            'last' => true
        ]
    ];

    private $validateSmsRetryTime = [
        'num' => [
            'rule'  => 'numeric',
            'message' =>  'Numeric Value should be between 0 to 11',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', 1, 10],
            'message' => 'Numeric Value should be between 0 to 11',
            'last' => true
        ]
    ];

    private $validateSmsRetryWait = [
        'num' => [
            'rule'  => 'numeric',
            'message' =>  'Numeric Value should be between 0 to 61',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', 1, 60],
            'message' => 'Numeric Value should be between 0 to 61',
            'last' => true
        ]
    ];

    private $validatePasswordMinLength = [
        'num' => [
            'rule'  => 'numeric',
            'message' => 'Numeric Value should be between 6 to 50',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', 6, 50],
            'message' => 'Numeric Value should be between 6 to 50',
            'last' => true
        ]
    ];

    private $validateMaxStudentsPerClass = [
        'num' => [
            'rule'  => 'numeric',
            'message' => 'Numeric Value should be between 0 to 200',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', 0, 200],
            'message' => 'Numeric Value should be between 0 to 200',
            'last' => true
        ]
    ];

    private $validateMaxStudentsPerSubject = [
        'num' => [
            'rule'  => 'numeric',
            'message' => 'Numeric Value should be between 0 to 200',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', 0, 200],
            'message' => 'Numeric Value should be between 0 to 200',
            'last' => true
        ],
        'checkMaxStudentsPerSubject' => [
            'rule'  => ['checkMaxStudentsPerSubject'],
            'provider' => 'table'
        ]
    ];

     private $validateLatitudeMinimum = [
        'num' => [
            'rule'  => 'numeric',
            'message' => 'Must Be Numeric Value',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', -99, 0],
            'message' => 'Numeric Value should be between -99 to 0',
            'last' => true
        ]
    ];

    private $validateLatitudeMaximum = [
        'num' => [
            'rule'  => 'numeric',
            'message' => 'Must Be Numeric Value',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', 0, 90],
            'message' => 'Numeric Value should be between 0 to 90',
            'last' => true
        ]
    ];

    private $validateLongitudeMinimum = [
        'num' => [
            'rule'  => 'numeric',
            'message' => 'Must Be Numeric Value',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range',-180, 0],
            'message' => 'Numeric Value should be between -180 to 0',
            'last' => true
        ]
    ];
    private $validateLongitudeMaximum = [
        'num' => [
            'rule'  => 'numeric',
            'message' => 'Must Be Numeric Value',
            'last' => true
        ],
        'bet' => [
            'rule'  => ['range', 0, 180],
            'message' => 'Numeric Value should be between 0 to 180',
            'last' => true
        ]
    ];

}

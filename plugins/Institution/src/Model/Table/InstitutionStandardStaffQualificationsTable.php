<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Log\Log;

/**
 * Get the Staff Qualifications details in excel file with specific tabs
 * @ticket POCOR-6551 <Vikas.rathore@mail.valuecoders.com>
 * this file was newly created for tiket POCOR-6551 by <Vikas.rathore@mail.valuecoders.com>
 */
class InstitutionStandardStaffQualificationsTable extends AppTable
{
    private $_type = [];

    public function initialize(array $config)
    {
        $this->table('staff_qualifications');
        parent::initialize($config);

        // Behaviours
        $this->addBehavior('Excel', [
            'excludes' => ['staff_id'],
            'pages' => false,
            'autoFields' => false
        ]);

        //pocor-6551 add behaviour for calling file related function ie: getFileTypeForView();
        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        //pocor-6551

        $this->addBehavior('Report.ReportList');

        $this->_type = [
            'CATALOGUE' => __('Course Catalogue'),
            'NEED' => __('Need Category'),
        ];
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);

        $controllerName = $this->controller->name;
        $institutions_crumb = __('Institutions');
        $parent_crumb       = __('Statistics');
		$reportName         = __('Standard');
        
        //# START: Crumb
        $this->Navigation->removeCrumb($this->getHeader($this->alias));
        $this->Navigation->addCrumb($institutions_crumb . ' ' . $parent_crumb);
        //# END: Crumb
        $this->controller->set('contentHeader', __($institutions_crumb) . ' ' . $parent_crumb . ' - ' . $reportName);
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
    }

    public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request)
    {
        $session = $this->request->session();
        $institution_id = $session->read('Institution.Institutions.id');
        $request->data[$this->alias()]['current_institution_id'] = $institution_id;
        $request->data[$this->alias()]['institution_id'] = $institution_id;
        if ($action == 'add') {
            $attr['value'] = 'xlsx';
            $attr['attr']['value'] = 'Excel';
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $options = $options = $this->controller->getInstitutionStatisticStandardReportFeature();
        $attr['options'] = $options;
        $attr['onChangeReload'] = true;
        if (!(isset($this->request->data[$this->alias()]['feature']))) {
            $option = $attr['options'];
            reset($option);
            $this->request->data[$this->alias()]['feature'] = key($option);
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature                = $this->request->data[$this->alias()]['feature'];
            $AcademicPeriodTable    = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodOptions  = $AcademicPeriodTable->getYearList();
            $currentPeriod          = $AcademicPeriodTable->getCurrent();
            $attr['options']        = $academicPeriodOptions;
            $attr['type']           = 'select';
            $attr['select']         = false;
            $attr['onChangeReload'] = true;
            if (empty($request->data[$this->alias()]['academic_period_id'])) {
                $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
            }
            return $attr;
        }
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheet_tabs = [
            'Qualifications'
        ];
        foreach($sheet_tabs as $sheet_tab_name) {  
            $sheets[] = [
                'sheetData'   => ['staff_tabs' => $sheet_tab_name],
                'name'        => $sheet_tab_name,
                'table'       => $this,
                'query'       => $this->find(),
                'orientation' => 'landscape'
            ];
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData           = json_decode($settings['process']['params']);
        $sheetData             = $settings['sheet']['sheetData'];
        $sheet_tab_name        = $sheetData['staff_tabs'];
        $academicPeriodId      = $requestData->academic_period_id;
        $institutionId         = $requestData->institution_id;
        $qualificationTitles   = TableRegistry::get('QualificationTitles');
        $qualificationLevel    = TableRegistry::get('QualificationLevels');
        $institutionStaff      = TableRegistry::get('institution_staff');
        $Institutions          = TableRegistry::get('Institution.Institutions');
        $fieldOfStudy          = TableRegistry::get('education_field_of_studies');
        $Users                 = TableRegistry::get('security_users');
        $selectable            = [];
        $group_by              = [];

        // START: JOINs
        $join = [
            'institutionStaff' => [
                'type' => 'inner',
                'table' => 'institution_staff',
                'conditions' => [
                    'institutionStaff.staff_id = ' . $this->aliasField('staff_id')
                ],
            ],
            'Institutions' => [
                'type' => 'inner',
                'table' => 'institutions',
                'conditions' => [
                    'Institutions.id = institutionStaff.institution_id'
                ]
            ],
            'AcademicPeriod' => [
                'type' => 'inner',
                'table' => 'academic_periods',
                'conditions' => [
                    'AcademicPeriod.id = ' . $academicPeriodId
                ]
            ],
        ];

        if ( $sheet_tab_name == 'Qualifications' ) {
            $query
            ->select([
                'institution_code'          => $Institutions->aliasField('code'),
                'institution_name'          => $Institutions->aliasField('name'),
                'openemis_no'               => $Users->aliasField('openemis_no'),
                'name'                      => $Users->find()->func()->concat(['security_users.first_name' => 'literal',"  ",'security_users.last_name' => 'literal']),
                'graduate_year'             => $this->aliasField('graduate_year'),
                'qualification_level'       => $qualificationLevel->aliasField('name'),
                'qualification_title'       => $qualificationTitles->aliasField('name'),
                'document_no'               => $this->aliasField('document_no'),
                'qualification_institution' => $this->aliasField('qualification_institution'),
                'file_name'                 => $this->aliasField('file_name'),
                'field_of_study'            => $fieldOfStudy->aliasField('name'),
            ])
            ->innerJoin(
                [$Users->alias() => $Users->table() ], // Class Object => table_name
                [$Users->aliasField('id = '). $this->aliasField('staff_id'), // Where
            ])
            ->leftJoin(
                [$qualificationTitles->alias() => $qualificationTitles->table()],[
                    $qualificationTitles->aliasField('id = ').$this->aliasField('qualification_title_id')
                ])
            ->leftJoin(
                [$fieldOfStudy->alias() => $fieldOfStudy->table()],[
                    $fieldOfStudy->aliasField('id = ').$this->aliasField('education_field_of_study_id')
                ])
            ->leftJoin(
                [$qualificationLevel->alias() => $qualificationLevel->table()],[
                    $qualificationLevel->aliasField('id = ').$qualificationTitles->aliasField('qualification_level_id')
                ]
            );
        } 

        $query->join($join);

        $query->where([
            'AcademicPeriod.id' => $academicPeriodId,
            'institutionStaff.institution_id' => $institutionId,
            $Users->aliasField('is_staff') => 1
        ]);
        $query->order(['QualificationLevels.order'=>'ASC']); //POCOR-6551
        $query->group($Users->aliasField('openemis_no'));
        
        $_this = $this;

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($_this)
        {
            // for getting file type call function getFileTypeForView
            return $results->map(function ($row) use ($_this)
            {
                $row['file_name'] = ( !empty($row['file_name']) ) ? $_this->getFileTypeForView($row['file_name']) : '' ;
                return $row;
            });
        });
    }

    /**
     * Generate the all Header for sheet tab wise
     */
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType         = TableRegistry::get('FieldOption.IdentityTypes');
        $identity             = $IdentityType->getDefaultEntity();
        $settings['identity'] = $identity;
        $sheetData            = $settings['sheet']['sheetData'];
        $sheet_tab_name       = $sheetData['staff_tabs'];
        $extraField           = [];

        if ( $sheet_tab_name == 'Qualifications' ) {
            $extraField[] = [
                'key'   => '',
                'field' => 'institution_code',
                'type'  => 'string',
                'label' => __('Institution Code'),
            ];
            $extraField[] = [
                'key'   => '',
                'field' => 'institution_name',
                'type'  => 'string',
                'label' => __('Institution Name'),
            ];
            
            $extraField[] = [
                'key'   => 'Users.openemis_no',
                'field' => 'openemis_no',
                'type'  => 'string',
                'label' => __('OpenEMIS ID'),
            ];
            
            $extraField[] = [
                'key'   => '',
                'field' => 'name',
                'type'  => 'string',
                'label' => __('Name')
            ];

            $extraField[] = [
                'key'   => 'graduate_year',
                'field' => 'graduate_year',
                'type'  => 'integer',
                'label' => __('Graduate Year')
            ];
    
            $extraField[] = [
                'key'   => 'qualification_level',
                'field' => 'qualification_level',
                'type'  => 'string',
                'label' => __('Level')
            ];
    
            $extraField[] = [
                'key'   => 'qualification_title',
                'field' => 'qualification_title',
                'type'  => 'string',
                'label' => __('Title')
            ];
    
            $extraField[] = [
                'key'   => 'document_no',
                'field' => 'document_no',
                'type'  => 'string',
                'label' => __('Document No')
            ];
    
            $extraField[] = [
                'key'   => 'qualification_institution',
                'field' => 'qualification_institution',
                'type'  => 'string',
                'label' => __('Institution')
            ];
    
            $extraField[] = [
                'key'   => '',
                'field' => 'file_name',
                'type'  => 'string',
                'label' => __('File Type')
            ];
    
            $extraField[] = [
                'key'   => 'field_of_study',
                'field' => 'field_of_study',
                'type'  => 'string',
                'label' => __('Field Of Study')
            ];
        }

        $fields->exchangeArray($extraField);
    }

}

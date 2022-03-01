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
 * @ticket POCOR-6551
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

    public function onExcelGetFileType(Event $event, Entity $entity)
    {
        return (!empty($entity->file_name))? $this->getFileTypeForView($entity->file_name): '';
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
        $Users                 = TableRegistry::get('security_users');
        $selectable            = [];
        $group_by              = [];

        // START: JOINs
        $join = [
            'Institution' => [
                'type' => 'inner',
                'table' => 'institutions',
                'conditions' => [
                    'Institution.id = ' . $institutionId
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
                'institution_code'          => 'Institution.code',
                'institution_name'          => 'Institution.name',
                'openemis_no'               => $Users->aliasField('openemis_no'),
                'name'                      => $Users->find()->func()->concat(['security_users.first_name' => 'literal',"  ",'security_users.last_name' => 'literal']),
                'graduate_year'             => $this->aliasField('graduate_year'),
                'qualification_level'       => $qualificationLevel->aliasField('name'),
                'qualification_title_id'    => $this->aliasField('qualification_title_id'),
                'document_no'               => $this->aliasField('document_no'),
                'qualification_institution' => $this->aliasField('qualification_institution'),
                'file_name'                 => $this->aliasField('file_name'),
                'file_content'              => $this->aliasField('file_content'),
                'education_field_of_study_id' => $this->aliasField('education_field_of_study_id'),
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
                [$qualificationLevel->alias() => $qualificationLevel->table()],[
                    $qualificationLevel->aliasField('id = ').$qualificationTitles->aliasField('qualification_level_id')
                ]
            );
        } 

        $query->join($join);

        $query->where([
            'AcademicPeriod.id' => $academicPeriodId,
            'Institution.id' => $institutionId,
            $Users->aliasField('is_staff') => 1
        ]);

        // echo $query; die;

        
        // $query->group($group_by)->order([$this->aliasField('first_name'), $this->aliasField('last_name')]);

        /* $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($sheet_tab_name)
        {
            return $results->map(function ($row) use ($sheet_tab_name)
            {
                if ( $sheet_tab_name == 'StaffTrainingNeeds' ) {
                    $row['needs_type']            = ($row['needs_type'] == 'CATALOGUE') ? $this->_type['CATALOGUE'] : $this->_type['NEED'];
                    $row['needs_asssignee_name']  = $row['needs_first_name'] . ' ' .  $row['needs_last_name'];
                    $row['needs_training_course'] = $row['needs_training_course_code'] . ' - ' . $row['needs_training_course_name'];
                }
                $row['security_user_full_name'] = $row['first_name'] . ' ' .  $row['last_name'];
                return $row;
            });
        }); */
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
                'key'   => 'Institution.code',
                'field' => 'institution_code',
                'type'  => 'string',
                'label' => __('Institution Code'),
            ];
            $extraField[] = [
                'key'   => 'Institution.name',
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
                'key'   => 'qualification_title_id',
                'field' => 'qualification_title_id',
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
                'key'   => 'education_field_of_study_id',
                'field' => 'education_field_of_study_id',
                'type'  => 'string',
                'label' => __('Field Of Study')
            ];
        }

        $fields->exchangeArray($extraField);
    }

}

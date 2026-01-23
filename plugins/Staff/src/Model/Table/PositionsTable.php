<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;

use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class PositionsTable extends ControllerActionTable {
    use MessagesTrait;

    public function initialize(array $config): void {
        $this->setTable('institution_staff');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('StaffStatuses', ['className' => 'Staff.StaffStatuses']);
        $this->belongsTo('InstitutionPositions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);

        $this->addBehavior('Historical.Historical', [
            'historicalUrl' => [
                'plugin' => 'Directory',
                'controller' => 'Directories',
                'action' => 'HistoricalStaffPositions'
            ],
            'originUrl' => [
                'action' => 'StaffPosition',
                'type' => 'staff'
            ],
            'model' => 'Historical.HistoricalStaffPositions',
            'allowedController' => ['Directories']
        ]);

        $this->addBehavior('Excel', [
            'pages' => ['index'],
            'autoFields' => false,
            'auto_contain' => false
        ]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->addBehavior('Institution.InstitutionTab');
        $this->addBehavior('Staff.StaffTab');
        $this->addBehavior('Configuration.CallWebhook', // POCOR-9403
            [
                'entity_create' => 'staff_create',
                'entity_delete' => 'staff_delete',
                'entity_update' => 'staff_update',
                'table_alias' => 'Institution.InstitutionStaff',
                'contain' => []
            ]
        ); // for webhook
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Behavior.Historical.index.beforeQuery'] = 'indexHistoricalBeforeQuery';
        return $events;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        // Commet this code for Add export button (POCOR-6135)

        /* if ($this->controller->getName() !== 'Directories') {
            $this->removeBehavior('Excel');
            if (array_key_exists('export', $extra['toolbarButtons'])) {
                unset($extra['toolbarButtons']['export']);
            }
        } */
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $this->dispatchEvent('Excel.Historical.beforeQuery', [$query, $settings], $this);
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.name',
            'field' => 'name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.institution',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.position',
            'field' => 'position_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.staff_type_id',
            'field' => 'staff_type_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.start_date',
            'field' => 'start_date',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.end_date',
            'field' => 'end_date',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.staff_status_id',
            'field' => 'staff_status_id',
            'type' => 'integer',
            'label' => ''
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetName(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'user');
        return $rowEntity->name;
    }

    public function onExcelGetOpenemisNo(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'user');
        return $rowEntity->openemis_no;
    }

    public function onExcelGetInstitutionName(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution');
        return $rowEntity->code_name;
    }

    public function onExcelGetPositionName(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution_position');
        if ($entity->is_historical) {
            return $rowEntity->position_no;
        } else {
            return $rowEntity->name;
        }
    }

    public function onExcelGetStaffTypeId(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'staff_type');
        return $rowEntity->name;
    }

    public function onExcelGetStaffStatusId(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'staff_status');
        return $rowEntity->name;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['start_year']['visible'] = false;
        $this->fields['end_year']['visible'] = false;
        $this->fields['FTE']['visible'] = false;
        $this->fields['security_group_user_id']['visible'] = false;
        $this->fields['staff_position_grade_id']['visible'] = false;//PCOOR-7238
        $this->fields['is_homeroom']['visible'] = false; //POCOR-5070
        $this->field('shift', ['after' => 'institution_position_id']);
        $this->field('staff_id', ['visible' => false]);
        $this->setFieldOrder([
            'institution_id',
            'institution_position_id',
            'staff_type_id',
            'shift',
            'start_date',
            'end_date',
            'staff_status_id'
        ]);

        // Start POCOR-5188
		if($this->request->getParam('controller') == 'Staff'){
			$is_manual_exist = $this->getManualUrl('Institutions','Positions','Staff - Career');
			if(!empty($is_manual_exist)){
				$btnAttr = [
					'class' => 'btn btn-xs btn-default icon-big',
					'data-toggle' => 'tooltip',
					'data-placement' => 'bottom',
					'escape' => false,
					'target'=>'_blank'
				];

				$helpBtn['url'] = $is_manual_exist['url'];
				$helpBtn['type'] = 'button';
				$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
				$helpBtn['attr'] = $btnAttr;
				$helpBtn['attr']['title'] = __('Help');
				$extra['toolbarButtons']['help'] = $helpBtn;
			}
		}elseif($this->request->getParam('controller') == 'Directories'){
			$is_manual_exist = $this->getManualUrl('Directory','Positions','Staff - Career');
			if(!empty($is_manual_exist)){
				$btnAttr = [
					'class' => 'btn btn-xs btn-default icon-big',
					'data-toggle' => 'tooltip',
					'data-placement' => 'bottom',
					'escape' => false,
					'target'=>'_blank'
				];

				$helpBtn['url'] = $is_manual_exist['url'];
				$helpBtn['type'] = 'button';
				$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
				$helpBtn['attr'] = $btnAttr;
				$helpBtn['attr']['title'] = __('Help');
				$extra['toolbarButtons']['help'] = $helpBtn;
			}

		}
		// End POCOR-5188
    }

    public function indexHistoricalBeforeQuery(EventInterface $event, Query $mainQuery, Query $historicalQuery, ArrayObject $selectList, ArrayObject $defaultOrder, ArrayObject $extra)
    {
        $session = $this->request->getSession();

        switch ($this->controller->getName()) {
            case 'Directories':
                $userId = $this->getUserID();
                break;
            case 'Staff':

                $userId = $this->getStaffID();
                break;
            case 'Profiles':
                $userId = $this->Auth->user('id');
                break;
            default:
                $userId = null;
                break;
        }

        if (!is_null($userId)) {
            $extra['auto_contain'] = false;

            $select = [
                $this->aliasField('id'),
                $this->aliasField('is_historical'),
                $this->aliasField('start_date'),
                $this->aliasField('end_date')
            ];
            $selectList->exchangeArray($select);

            $order = ['start_date' => 'DESC'];
            $defaultOrder->exchangeArray($order);

            $mainQuery
                ->select([
                    'id' => $this->aliasField('id'),
                    'start_date' => $this->aliasField('start_date'),
                    'end_date' => $this->aliasField('end_date'),
                    $this->aliasField('institution_id'),
                    $this->Institutions->aliasField('id'),
                    $this->Institutions->aliasField('code'),
                    $this->Institutions->aliasField('name'),
                    $this->InstitutionPositions->aliasField('id'),
                    $this->InstitutionPositions->aliasField('position_no'),
                    $this->InstitutionPositions->aliasField('staff_position_title_id'),
                    $this->StaffTypes->aliasField('id'),
                    $this->StaffTypes->aliasField('name'),
                    $this->StaffStatuses->aliasField('id'),
                    $this->StaffStatuses->aliasField('name'),
                    $this->Users->aliasField('id'),
                    $this->Users->aliasField('first_name'),
                    $this->Users->aliasField('middle_name'),
                    $this->Users->aliasField('third_name'),
                    $this->Users->aliasField('last_name'),
                    $this->Users->aliasField('preferred_name'),
                    $this->Users->aliasField('openemis_no'),
                    'is_historical' => 0
                ], true)
                ->contain([
                    $this->Institutions->getAlias(),
                    $this->InstitutionPositions->getAlias(),
                    $this->StaffTypes->getAlias(),
                    $this->Users->getAlias(),
                    $this->StaffStatuses->getAlias()
                ])
                ->where([
                    $this->aliasField('staff_id') => $userId
                ]);

            $HistoricalTable = $historicalQuery->getRepository();
            $historicalQuery
                ->select([
                    'id' => $HistoricalTable->aliasField('id'),
                    'start_date' => $HistoricalTable->aliasField('start_date'),
                    'end_date' => $HistoricalTable->aliasField('end_date'),
                    'position_institution_id' => '(null)',
                    'institution_id' => 'Institutions.id',
                    'institution_code' => 'Institutions.code',
                    'institution_name' => 'Institutions.name',
                    'position_id' => '(null)',
                    'position_name' => 'StaffPositionTitles.name',
                    'staff_position_title_id' => '(null)',
                    'staff_type_id' => 'StaffTypes.id',
                    'staff_type_name' => 'StaffTypes.name',
                    'staff_status_id' => 'StaffStatuses.id',
                    'staff_status_name' => 'StaffStatuses.name',
                    'user_id' => 'Users.id',
                    'user_first_name' => 'Users.first_name',
                    'user_middle_name' => 'Users.middle_name',
                    'user_third_name' => 'Users.third_name',
                    'user_last_name' => 'Users.last_name',
                    'user_preferred_name' => 'Users.preferred_name',
                    'user_openemis_no' => 'Users.openemis_no',
                    'is_historical' => 1
                ])
                ->contain([
                    'StaffTypes',
                    'StaffStatuses',
                    'Users',
                    'Institutions',
                    'StaffPositionTitles'
                ])
                ->where([
                    $HistoricalTable->aliasField('staff_id') => $userId
                ]);
        }
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons) {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        if (isset($buttons['view'])) {
            if ($entity->is_historical) {
                $rowEntityId = $this->getFieldEntity($entity->is_historical, $entity->id, 'id');
                $buttons = $this->getHistoricalActionButtons($buttons, $rowEntityId);
            } else {
                // POCOR-9426 start
                $institutionEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution');
                $staffEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'user');

                $institutionId = $institutionEntity->id;
                $staffId = $staffEntity->id;

                // $institutionId = $entity->institution->id
                $url = [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'Staff',
                    'view',
                    $this->paramsEncode(['id' => $entity->id,
                        'institution_id' => $institutionId,
                        'staff_id' => $staffId,
                        'user_id' => $staffEntity]),
                    //'institution_id' => $institutionId,
//                    $encodedQueryString
                    // POCOR-9426 end
                ];
                $buttons['view']['url'] = $url;
            }
        }

        return $buttons;
    }

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra) {
        $options = ['type' => 'staff'];
        $tabElements = $this->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }

    public function onGetInstitutionId(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution');
        return $rowEntity->code_name;
    }

    public function onGetInstitutionPositionId(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution_position');
        if ($entity->is_historical) {
            return $rowEntity->position_no;
        } else {
            return $rowEntity->name;
        }
    }

    public function onGetStaffTypeId(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'staff_type');
        return $rowEntity->name;
    }

    public function onGetStaffStatusId(EventInterface $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'staff_status');
        return $rowEntity->name;
    }

    public function onGetShift(EventInterface $event, Entity $entity)
    {
       $institutionStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionStaff');
       $staffId=$institutionStaff->find()->select(['staff_id'])->where(['id' =>$entity->id])->first();
       $staff_id = $this->paramsDecode($this->request->getAttribute('params')['pass'][1])['staff_id'];
       $staff_id = !empty($staff_id) ? $staff_id : $staffId['staff_id'];
       $institutaionStaffid = $entity->id; //POCOR-7185
       $institutionShifts = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts');
       $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionStaff');
       $ShiftOptions = TableRegistry::getTableLocator()->get('Institution.ShiftOptions');
       $institutionStaffShifts = TableRegistry::getTableLocator()->get('Institution.InstitutionStaffShifts');
       $InstitutionPositions = TableRegistry::getTableLocator()->get('Institution.InstitutionPositions');
       //POCOR-7109
       $res = $InstitutionStaff->find()
                ->select(['name' =>  $ShiftOptions->aliasField('name')])
                ->leftJoin([$InstitutionPositions->getAlias() => $InstitutionPositions->getTable()],[
                        $InstitutionPositions->aliasField('id = ') . $InstitutionStaff->aliasField('institution_position_id')
                ])
                ->leftJoin([$ShiftOptions->getAlias() => $ShiftOptions->getTable()],[
                    $ShiftOptions->aliasField('id = ') . $InstitutionPositions->aliasField('shift_id')
                ])
                ->where([$InstitutionStaff->aliasField('staff_id')=> $staff_id,$InstitutionStaff->aliasField('id')=> $institutaionStaffid])
                ->group([$InstitutionPositions->aliasField('shift_id')])
                ->first();
        $shift = '';
        if(empty($res->name)){ //POCOR-7185
            $shift = 'NA';
        }else{
            $shift = $res->name;
        }
        return $shift;
       //POCOR-7109, POCOR-6917 code change due to change column name

       //POCOR-7109,6917 code change due to change column name
        /*$res=$institutionShifts->find()->select(['name'=> 'shift_options.name' ])
                                ->leftJoin(
                                        [$shiftOptions->alias() => $shiftOptions->table()],
                                        [
                                            $shiftOptions->aliasField('id = ') . $institutionShifts->aliasField('shift_option_id')
                                        ]
                                    )
                                    ->leftJoin(
                                        [$institutionStaffShifts->alias() => $institutionStaffShifts->table()],
                                        [
                                            $institutionStaffShifts->aliasField('shift_id = ') . $institutionShifts->aliasField('id')
                                        ]
                                    )


                                ->where([$institutionStaffShifts->aliasField('staff_id')=> $staff_id])->order($institutionShifts->aliasField('id'))->group('shift_options.name')->order('shift_options.name')->toArray();
                                $shift='';
                                foreach ($res as $key => $value) {
                                    $shift.=$value['name'].',';
                                }
                               return  rtrim($shift,',');  */
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'institution_id') {
            return __('Institution');
        } else if ($field == 'staff_type_id') {
            return __('Staff Type');
        } else if ($field == 'shift') {
            return __('Shift');
        } else if ($field == 'staff_status_id') {
            return __('Staff Status');
        } else if ($field == 'start_date') {
            return __('Start Date');
        } else if ($field == 'end_date') {
            return __('End Date');
        } else if ($field == 'modified') {
            return __('Modified');
        } else if ($field == 'modified_user_id') {
            return __('Modified By');
        } else if ($field == 'created') {
            return __('Created');
        } else if ($field == 'created_user_id') {
            return __('Created By');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}

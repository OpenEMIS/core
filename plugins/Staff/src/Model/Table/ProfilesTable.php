<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class ProfilesTable extends ControllerActionTable
{
	// for status
    CONST NEW_REPORT = 1;
    CONST IN_PROGRESS = 2;
    CONST GENERATED = 3;
    CONST PUBLISHED = 4;

	public $fileTypes = [
        'jpeg'  => 'image/jpeg',
        'jpg'   => 'image/jpeg',
        'gif'   => 'image/gif',
        'png'   => 'image/png',
        // 'jpeg'=>'image/pjpeg',
        // 'jpeg'=>'image/x-png'
        'rtf'   => 'text/rtf',
        'txt'   => 'text/plain',
        'csv'   => 'text/csv',
        'pdf'   => 'application/pdf',
        'ppt'   => 'application/vnd.ms-powerpoint',
        'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'doc'   => 'application/msword',
        'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'   => 'application/vnd.ms-excel',
        'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'zip'   => 'application/zip'
    ];

    public function initialize(array $config): void
    {
        $this->setTable('staff_report_cards');
        $this->setPrimaryKey('id'); //POCOR-9584: override composite MySQL PK so CakePHP uses 'id' for view/exists checks

        parent::initialize($config);

		$this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);

		$this->StaffReportCards = TableRegistry::getTableLocator()->get('Institution.StaffReportCards');
        $this->addBehavior('Institution.InstitutionTab');
    }

	public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadExcel'] = 'downloadExcel';
        //START:POCOR-6667
        $events['ControllerAction.Model.viewPDF'] = 'viewPDF';
        //END:POCOR-6667
        $events['ControllerAction.Model.downloadPDF'] = 'downloadPDF';
        return $events;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        //POCOR-9584: start - reset auto-fields so only explicitly defined columns appear;
        //            without this, all schema columns (staff_id, institution_id, academic_period_id, etc.) show as raw IDs
        $this->fields = [];
        //POCOR-9584: end
        $this->field('academic_period');
        $this->field('profile_name');
        $this->field('file_name');
        $this->field('status'); //POCOR-9584: show status (GENERATED / PUBLISHED) now that both are listed
        $this->setFieldOrder([
            'academic_period',
            'profile_name',
            'file_name',
            'status'
        ]);


        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Staff Profile','Staff');
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
		// End POCOR-5188
    }

	public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
		$institutionId = $this->getInstitutionID();
		$staffId = $this->getStaffID(); //POCOR-9584: filter to this staff's own records only

		$AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
		$StaffProfileTemplates = TableRegistry::getTableLocator()->get('ProfileTemplate.StaffProfileTemplates');

		//POCOR-9584: start - show GENERATED (3) and PUBLISHED (4) records, not just PUBLISHED;
		//            records with status=GENERATED are ready to view but not yet formally published
		$where[$this->aliasField('status') . ' IN'] = [self::GENERATED, self::PUBLISHED];
		//POCOR-9584: end
		$where[$this->aliasField('institution_id')] = $institutionId;
		$where[$this->aliasField('staff_id')] = $staffId; //POCOR-9584: only show this staff's own reports

        $query
            ->select([
                'file_name' => $this->aliasField('file_name'),
                'academic_period' => $AcademicPeriods->aliasField('name'),
                'profile_name' => $StaffProfileTemplates->aliasField('name'),
            ])
			->innerJoin([$AcademicPeriods->getAlias() => $AcademicPeriods->getTable()],
                [
                    $AcademicPeriods->aliasField('id = ') . $this->aliasField('academic_period_id'),
                ]
            )
			->innerJoin([$StaffProfileTemplates->getAlias() => $StaffProfileTemplates->getTable()],
                [
                    $StaffProfileTemplates->aliasField('id = ') . $this->aliasField('staff_profile_template_id'),
                ]
            )
            ->enableAutoFields(true)
			->order([
                $this->aliasField('file_name'),
            ])
            ->where($where)
            ->all();

    }

	public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $StaffProfileTemplates = TableRegistry::getTableLocator()->get('ProfileTemplate.StaffProfileTemplates');
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions'); //POCOR-9584: join to get institution name

        $query
            ->select([
                'file_name'       => $this->aliasField('file_name'),
                'academic_period' => $AcademicPeriods->aliasField('name'),
                'profile_name'    => $StaffProfileTemplates->aliasField('name'),
                'institution_name' => $Institutions->aliasField('name'), //POCOR-9584: select institution name
            ])
            ->innerJoin([$AcademicPeriods->getAlias() => $AcademicPeriods->getTable()],
                [$AcademicPeriods->aliasField('id = ') . $this->aliasField('academic_period_id')]
            )
            ->innerJoin([$StaffProfileTemplates->getAlias() => $StaffProfileTemplates->getTable()],
                [$StaffProfileTemplates->aliasField('id = ') . $this->aliasField('staff_profile_template_id')]
            )
            ->innerJoin([$Institutions->getAlias() => $Institutions->getTable()], //POCOR-9584: join institutions
                [$Institutions->aliasField('id = ') . $this->aliasField('institution_id')]
            )
            ->enableAutoFields(true);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        //POCOR-9584: start - reset auto-fields so raw ID columns (staff_profile_template_id,
        //            institution_id, academic_period_id, staff_id) do not appear;
        //            viewBeforeQuery joins provide meaningful names; staff is hidden (shown in page header)
        $this->fields = [];
        //POCOR-9584: end
        $this->field('academic_period');
        $this->field('profile_name');
        $this->field('institution_name'); //POCOR-9584: show institution name instead of institution_id
        $this->field('file_name');
        $this->field('status');
        $this->setFieldOrder([
            'academic_period',
            'profile_name',
            'institution_name',
            'file_name',
            'status'
        ]);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $indexAttr = ['role' => 'menuitem',
        'tabindex' => '-1',
        'escape' => false,
        'target' => '_blank'];
        //echo '<pre>';print_r($entity);die;
		$params = [
			'staff_profile_template_id' => $entity->staff_profile_template_id,
			'staff_id' => $entity->staff_id,
			'institution_id' => $entity->institution_id,
			'academic_period_id' => $entity->academic_period_id,
		];

		//START:POCOR-6667
        $viewPdfUrl = $this->setQueryString($this->url('viewPDF'), $params);
		$buttons['viewPdf'] = [
			'label' => '<i class="fa fa-eye"></i>'.__('View PDF'),
			'attr' => $indexAttr,
			'url' => $viewPdfUrl,

		];
        //END:POCOR-6667
		$downloadPdfUrl = $this->setQueryString($this->url('downloadPDF'), $params);
		$buttons['downloadPdf'] = [
			'label' => '<i class="fa kd-download"></i>'.__('Download PDF'),
			'attr' => $indexAttr,
			'url' => $downloadPdfUrl
		];
        $downloadUrl = $this->setQueryString($this->url('downloadExcel'), $params);
		$buttons['download'] = [
			'label' => '<i class="fa kd-download"></i>'.__('Download Excel'),
			'attr' => $indexAttr,
			'url' => $downloadUrl
		];

        return $buttons;
    }

	public function downloadExcel(EventInterface $event, ArrayObject $extra)
    {
		$model = $this->StaffReportCards;
        $ids = $this->getQueryString();

        if ($model->exists($ids)) {
            $data = $model->find()->where($ids)->first();
            $fileName = $data->file_name;
            $pathInfo = pathinfo($fileName);
            $file = $this->getFile($data->file_content);
            $fileType = 'image/jpg';
            if (array_key_exists($pathInfo['extension'], $this->fileTypes)) {
                $fileType = $this->fileTypes[$pathInfo['extension']];
            }

            // echo '<img src="data:image/jpg;base64,' .   base64_encode($file)  . '" />';

            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: " . $fileType);
            header('Content-Disposition: attachment; filename="' . $fileName . '"');

            echo $file;
        }
        exit();
    }

	public function downloadPDF(EventInterface $event, ArrayObject $extra)
    {
		$model = $this->StaffReportCards;
        $ids = $this->getQueryString();

        if ($model->exists($ids)) {
            $data = $model->find()->where($ids)->first();
            $fileName = $data->file_name;
            $fileNameData = explode(".",$fileName);
			$fileName = $fileNameData[0].'.pdf';
			$pathInfo['extension'] = 'pdf';
            $file = $this->getFile($data->file_content_pdf);
            $fileType = 'image/jpg';
            if (array_key_exists($pathInfo['extension'], $this->fileTypes)) {
                $fileType = $this->fileTypes[$pathInfo['extension']];
            }

            // echo '<img src="data:image/jpg;base64,' .   base64_encode($file)  . '" />';

            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: " . $fileType);
            header('Content-Disposition: attachment; filename="' . $fileName . '"');

            echo $file;
        }
        exit();
    }

    /*
    * Function is created to view PDF in browser
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return file
    * @ticket POCOR-6667
    */

    public function viewPDF(EventInterface $event, ArrayObject $extra)
    {
		$model = $this->StaffReportCards;
        $ids = $this->getQueryString();

        if ($model->exists($ids)) {
            $data = $model->find()->where($ids)->first();
            $fileName = $data->file_name;
            $fileNameData = explode(".",$fileName);
			$fileName = $fileNameData[0].'.pdf';
			$pathInfo['extension'] = 'pdf';
            $file = $this->getFile($data->file_content_pdf);
            $fileType = 'image/jpg';
            if (array_key_exists($pathInfo['extension'], $this->fileTypes)) {
                $fileType = $this->fileTypes[$pathInfo['extension']];
            }

            // echo '<img src="data:image/jpg;base64,' .   base64_encode($file)  . '" />';

            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            // header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: " . $fileType);
            header('Content-Disposition: inline; filename="' . $filename . '"');

            echo $file;
        }
        exit();
    }

	private function getFile($phpResourceFile) {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
    }

    //POCOR-9584: start - display human-readable status label instead of raw integer
    public function onGetStatus(EventInterface $event, Entity $entity)
    {
        $statusMap = [
            self::NEW_REPORT  => __('New'),
            self::IN_PROGRESS => __('In Progress'),
            self::GENERATED   => __('Generated'),
            self::PUBLISHED   => __('Published'),
        ];
        return $statusMap[$entity->status] ?? $entity->status;
    }
    //POCOR-9584: end

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'academic_period') {
            return __('Academic Period');
        } elseif ($field == 'profile_name') {
            return __('Staff Profile Template');
        } elseif ($field == 'institution_name') { //POCOR-9584: institution name label
            return __('Institution');
        } elseif ($field == 'status') {
            return __('Status');
        } elseif ($field == 'file_name') {
            return __('File Name');
        } elseif ($field == 'description') {
            return __('Description');
        } elseif ($field == 'apply_to_all') {
            return __('Apply To All');
        } elseif ($field == 'is_unique') {
            return __('Is Unique');
        } elseif ($field == 'validation_rule') {
            return __('Validation Rule');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }


}

<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
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

    public function initialize(array $config)
    {
        $this->table('staff_report_cards');

        parent::initialize($config);
		
		$this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
		
		$this->StaffReportCards = TableRegistry::get('Institution.StaffReportCards');
    }
	
	public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadExcel'] = 'downloadExcel';
        $events['ControllerAction.Model.downloadPDF'] = 'downloadPDF';
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period');
        $this->field('profile_name');
        $this->field('file_name');
		$this->field('status', ['visible' => false]);
		$this->field('file_content', ['visible' => false]);
		$this->field('file_content_pdf', ['visible' => false]);
		$this->field('started_on', ['visible' => false]);
		$this->field('completed_on', ['visible' => false]);
        $this->setFieldOrder([
            'academic_period',
            'profile_name',
            'file_name'
        ]);
    }
	
	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
		$institutionId = $this->Session->read('Institution.Institutions.id');

		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$StaffProfileTemplates = TableRegistry::get('ProfileTemplate.StaffProfileTemplates');
		
		$where[$this->aliasField('status')] = self::PUBLISHED;
		$where[$this->aliasField('institution_id')] = $institutionId;

        $query
            ->select([
                'file_name' => $this->aliasField('file_name'),
                'academic_period' => $AcademicPeriods->aliasField('name'),
                'profile_name' => $StaffProfileTemplates->aliasField('name'),
            ])
			->innerJoin([$AcademicPeriods->alias() => $AcademicPeriods->table()],
                [
                    $AcademicPeriods->aliasField('id = ') . $this->aliasField('academic_period_id'),
                ]
            )
			->innerJoin([$StaffProfileTemplates->alias() => $StaffProfileTemplates->table()],
                [
                    $StaffProfileTemplates->aliasField('id = ') . $this->aliasField('staff_profile_template_id'),
                ]
            )
            ->autoFields(true)
			->order([
                $this->aliasField('file_name'),
            ])
            ->where($where)
            ->all();

    }
	
	public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$StaffProfileTemplates = TableRegistry::get('ProfileTemplate.StaffProfileTemplates');
				
        $query
            ->select([
                'file_name' => $this->aliasField('file_name'),
                'academic_period' => $AcademicPeriods->aliasField('name'),
                'profile_name' => $StaffProfileTemplates->aliasField('name'),
            ])
			->innerJoin([$AcademicPeriods->alias() => $AcademicPeriods->table()],
                [
                    $AcademicPeriods->aliasField('id = ') . $this->aliasField('academic_period_id'),
                ]
            )
			->innerJoin([$StaffProfileTemplates->alias() => $StaffProfileTemplates->table()],
                [
                    $StaffProfileTemplates->aliasField('id = ') . $this->aliasField('staff_profile_template_id'),
                ]
            )
            ->autoFields(true);
    }
	
    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period');
        $this->field('profile_name');
        $this->field('file_name');
		$this->field('status', ['visible' => false]);
		$this->field('file_content', ['visible' => false]);
		$this->field('file_content_pdf', ['visible' => false]);
		$this->field('started_on', ['visible' => false]);
		$this->field('completed_on', ['visible' => false]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
        //echo '<pre>';print_r($entity);die;
		$params = [
			'staff_profile_template_id' => $entity->staff_profile_template_id,
			'staff_id' => $entity->staff_id,
			'institution_id' => $entity->institution_id,
			'academic_period_id' => $entity->academic_period_id,
		];
			
		$downloadUrl = $this->setQueryString($this->url('downloadExcel'), $params);
		//echo '<pre>';print_r($downloadUrl);die;
		$buttons['download'] = [
			'label' => '<i class="fa kd-download"></i>'.__('Download Excel'),
			'attr' => $indexAttr,
			'url' => $downloadUrl
		];
		$downloadPdfUrl = $this->setQueryString($this->url('downloadPDF'), $params);
		$buttons['downloadPdf'] = [
			'label' => '<i class="fa kd-download"></i>'.__('Download PDF'),
			'attr' => $indexAttr,
			'url' => $downloadPdfUrl
		];

        return $buttons;
    }
	
	public function downloadExcel(Event $event, ArrayObject $extra)
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
	
	public function downloadPDF(Event $event, ArrayObject $extra)
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
	
	private function getFile($phpResourceFile) {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
    }
	
}

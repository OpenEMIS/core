<?php
namespace Student\Model\Table;

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
        $this->setTable('student_report_cards');

        parent::initialize($config);

		$this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);

		$this->InstitutionStudentsProfileTemplates = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentsProfileTemplates');
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
        $this->field('academic_period');
        $this->field('profile_name');
        $this->field('file_name');
		$this->field('status', ['visible' => false]);
		$this->field('education_grade_id', ['visible' => false]);
		$this->field('file_content', ['visible' => false]);
		$this->field('file_content_pdf', ['visible' => false]);
		$this->field('started_on', ['visible' => false]);
		$this->field('completed_on', ['visible' => false]);
        $this->setFieldOrder([
            'academic_period',
            'profile_name',
            'file_name'
        ]);


        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Student Profile','Students');
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

		$AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
		$StudentTemplates = TableRegistry::getTableLocator()->get('ProfileTemplate.StudentTemplates');

		$where[$this->aliasField('institution_id')] = $institutionId;
		$where[$this->aliasField('status')] = self::PUBLISHED;

        $query
            ->select([
                'file_name' => $this->aliasField('file_name'),
                'academic_period' => $AcademicPeriods->aliasField('name'),
                'profile_name' => $StudentTemplates->aliasField('name'),
            ])
			->innerJoin([$AcademicPeriods->getAlias() => $AcademicPeriods->getTable()],
                [
                    $AcademicPeriods->aliasField('id = ') . $this->aliasField('academic_period_id'),
                ]
            )
			->innerJoin([$StudentTemplates->getAlias() => $StudentTemplates->getTable()],
                [
                    $StudentTemplates->aliasField('id = ') . $this->aliasField('student_profile_template_id'),
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
		$StudentTemplates = TableRegistry::getTableLocator()->get('ProfileTemplate.StudentTemplates');

        $query
            ->select([
                'file_name' => $this->aliasField('file_name'),
                'academic_period' => $AcademicPeriods->aliasField('name'),
                'profile_name' => $StudentTemplates->aliasField('name'),
            ])
			->innerJoin([$AcademicPeriods->getAlias() => $AcademicPeriods->getTable()],
                [
                    $AcademicPeriods->aliasField('id = ') . $this->aliasField('academic_period_id'),
                ]
            )
			->innerJoin([$StudentTemplates->getAlias() => $StudentTemplates->getTable()],
                [
                    $StudentTemplates->aliasField('id = ') . $this->aliasField('student_profile_template_id'),
                ]
            )
            ->autoFields(true);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period');
        $this->field('profile_name');
        $this->field('file_name');
		$this->field('status', ['visible' => false]);
		$this->field('file_content', ['visible' => false]);
		$this->field('file_content_pdf', ['visible' => false]);
		$this->field('started_on', ['visible' => false]);
		$this->field('completed_on', ['visible' => false]);
		$this->field('education_grade_id', ['visible' => false]);
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
			'student_profile_template_id' => $entity->student_profile_template_id,
			'student_id' => $entity->student_id,
			'institution_id' => $entity->institution_id,
			'education_grade_id' => $entity->education_grade_id,
			'academic_period_id' => $entity->academic_period_id,
		];
		//START:POCOR-6667
		$viewPdfUrl = $this->setQueryString($this->url('viewPDF'), $params);
		$buttons['viewPdf'] = [
			'label' => '<i class="fa fa-eye"></i>'.__('View PDF'),
			'attr' => $indexAttr,
			'url' => $viewPdfUrl
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
        //POCOR-5191::Start
        $student_profile_security_roles_table = TableRegistry::getTableLocator()->get('Student.StudentProfileSecurityRoles');
        $instituttionnTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $securitygroupusersTable = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        $institutionId = $this->getInstitutionID();
        $insData = $instituttionnTable->get($institutionId);
        $security_group_id = $insData->security_group_id;
        $user_id = $this->Session->read('Auth.User.id');
        $roles = $student_profile_security_roles_table->find()->where(['student_profile_template_id'=> $this->request->getQuery('student_profile_template_id')])->toArray();
        $curr_u_roles = $securitygroupusersTable->find()->where(['security_group_id'=> $security_group_id, 'security_user_id'=>$user_id])->toArray();
        $rolArr = [];
        $rolArrrr = [];
        foreach($roles as $rol){
            $rolArr[] = $rol->security_role_id;
        }

        foreach($curr_u_roles as $curr_uu_roles){
            $rolArrrr[] = $curr_uu_roles->security_role_id;
        }
        $result = array_intersect($rolArrrr, $rolArr);
        $nResult = reset($result);

        if($this->Session->read('Auth.User.super_admin') != 1){
            if(!empty($nResult)){
                if(!in_array($nResult, $rolArr)){
                    unset($buttons);
                }
            }else{
                unset($buttons);
            }
        }
        //POCOR-5191::end
        return $buttons;
    }

	public function downloadExcel(EventInterface $event, ArrayObject $extra)
    {
		$model = $this->InstitutionStudentsProfileTemplates;
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
		$model = $this->InstitutionStudentsProfileTemplates;
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
		$model = $this->InstitutionStudentsProfileTemplates;
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'academic_period':
                return __('Academic Period');
            case 'file_name':
                return __('File Name');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

}

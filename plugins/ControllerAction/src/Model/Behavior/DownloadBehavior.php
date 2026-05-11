<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\Network\Exception\NotFoundException;

class DownloadBehavior extends Behavior
{
    protected $_defaultConfig = [
        'show' => true,
        'name' => 'file_name',
        'content' => 'file_content',
		'folder' => 'export',
        'subfolder' => 'customexcel',
    ];

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
	
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.download'] = 'download';
		$events['ControllerAction.Model.downloadPdf'] = 'downloadPdf';
        return $events;
    }

    public function downloadPdf(EventInterface $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;
        $ids = $model->paramsDecode($model->paramsPass(0));

        if ($model->exists($ids)) {
            $data = $model->get($ids);
			$fileName = $data->{$this->getConfig('name')};
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
	
    public function download(EventInterface $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;
        $controllerName = $model->controller->getName();
        //POCOR-9584: start - Always use only the primary key (id) from the decoded pass param.
        // When navigating from a view page, ControllerAction inserts navigation context at paramsPass(0)
        // (containing staff_id, institution_id, etc.), shifting the button's explicit entity ID to paramsPass(1).
        // From an index page paramsPass(0) holds the entity ID directly (no navigation prefix).
        // In both cases we only ever need `id`; strip everything else to prevent a "column not found" SQL error
        // if extra fields (staff_id, institution_id, …) are accidentally passed as WHERE conditions.
        $passParam1 = $model->paramsPass(1);
        $rawIds = !empty($passParam1)
            ? $model->paramsDecode($passParam1)
            : $model->paramsDecode($model->paramsPass(0));
        $ids = isset($rawIds['id']) ? ['id' => $rawIds['id']] : $rawIds;
        //POCOR-9584: end
        if ($model->exists($ids)) {
            $data = $model->get($ids);
            $fileName = $data->{$this->getConfig('name')};
            $pathInfo = pathinfo($fileName);
            $file = $this->getFile($data->{$this->getConfig('content')});
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

    private function getFile($phpResourceFile)
    {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
    }
}

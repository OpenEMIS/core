<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
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

    public function downloadPdf(Event $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;
        $ids = $model->paramsDecode($model->paramsPass(0));

        if (!$model->exists($ids)) {
            $this->Alert->warning('File not found');
            return $this->controller->redirect($this->referer());
        }

        try {
            $data = $model->get($ids);

            $fileName = $data->{$this->getConfig('name')} ?? 'document.pdf';
            $fileName = pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';

            if (empty($data->file_content_pdf)) {
                throw new \RuntimeException('PDF blob is empty');
            }

            $file = $this->getFile($data->file_content_pdf);

            // Basic PDF validation
            if (empty($file) || strpos($file, '%PDF') !== 0) {
                throw new \RuntimeException('Invalid PDF binary');
            }

            // Correct headers (ONLY ONCE)
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Length: ' . strlen($file));

            echo $file;
            exit();

        } catch (\Throwable $e) {

            Log::warning(sprintf(
                'PDF download failed (id=%s): %s',
                is_array($ids) ? json_encode($ids) : $ids,
                $e->getMessage()
            ));

            // IMPORTANT: clear broken PDF to allow regeneration later
            try {
                $model->updateAll(
                    ['file_content_pdf' => null],
                    $ids
                );
            } catch (\Throwable $dbEx) {
                Log::error('Failed to clear broken PDF blob: ' . $dbEx->getMessage());
            }

            $this->Alert->warning('The PDF is temporarily unavailable and will be regenerated.');
            return $this->controller->redirect($this->referer());
        }
    }

	 public function download(Event $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;
        $controllerName = $model->controller->getName();
        $ids = $model->paramsDecode($model->paramsPass(0));
        if( $model->controller->getName() == 'Directories' || $model->controller->getName() == 'Profiles') {
            $ids =[];
            $params = $model->paramsDecode($model->paramsPass(0));
            $ids['id'] = $params['id'];
        }
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

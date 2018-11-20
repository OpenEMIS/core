<?php
namespace Restful\Controller\Component;

use Exception;
use Cake\Controller\Component;
use Cake\ORM\TableRegistry;

class DownloadFileComponent extends Component
{
    protected $_defaultConfig = [
        'base64Encode' => false
    ];

    public function download($id, $fileNameField, $fileContentField)
    {
        $model = TableRegistry::get($this->request->model);
        $data = $model->get($id);

        $modelSchema = $model->schema();
        if (is_null($modelSchema->column($fileNameField))) {
            throw new Exception("Invalid file name field", 500);
        }

        $fieldContentColumn = $modelSchema->column($fileContentField);
        if (is_null($fieldContentColumn) || $fieldContentColumn['type'] != 'binary') {
            throw new Exception("Invalid file content field", 500);
        }

        if (is_null($data->{$fileContentField})) {
            throw new Exception("File content not found", 404);
        }

        $fileName = $data->{$fileNameField};
        $pathInfo = pathinfo($fileName);
        $file = stream_get_contents($data->{$fileContentField});

        if ($this->config('base64Encode')) {
            $this->_registry->getController()->set([
                'extension' => $pathInfo['extension'],
                'filename' => $fileName,
                'src' => 'data:'.$this->response->getMimeType($pathInfo['extension']).';base64,'. base64_encode($file),
                '_serialize' => ['extension', 'filename', 'src']
            ]);
        } else {
            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: " . $this->response->getMimeType($pathInfo['extension']));
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            echo $file;
            exit();
        }
    }
}

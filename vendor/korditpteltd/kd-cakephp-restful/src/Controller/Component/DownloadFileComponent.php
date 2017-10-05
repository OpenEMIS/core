<?php
namespace Restful\Controller\Component;

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
            return $this->response
                ->withStringBody($file)
                ->type('application/force-download')
                ->type('application/octet-stream')
                ->type($pathInfo['extension'])
                ->download($fileName);
        }
    }
}

<?php
namespace Adaptation\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;

class AdaptationsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['value' => 'content', 'default_value' => 'default_content'],
            'allowable_file_types' => ['jpeg', 'jpg', 'gif', 'png']
        ]);
    }
}

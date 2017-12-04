<?php
namespace Adaptation\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;

class AdaptationsTable extends AppTable
{
    const APPNAME = 1;
    const LOGINBGIMAGE = 2;
    const LOGO = 3;
    const FAVICON = 4;
    const COLOUR = 5;
    const COPYRIGHTNOTICE = 6;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['value' => 'content', 'default_value' => 'default_content'],
            'size' => '2MB',
            'allowable_file_types' => ['jpeg', 'jpg', 'gif', 'png']
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->allowEmpty('content')
            ->allowEmpty('default_content')
            ->allowEmpty('default_value');
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        switch ($data['id']) {
            case self::LOGO:
            case self::LOGINBGIMAGE:
                $this->behaviors()->get('FileUpload')->config([
                    'allowable_file_types' => [
                        'value' => ['jpeg', 'jpg', 'gif', 'png'],
                        'default_value' => ['jpeg', 'jpg', 'gif', 'png']
                    ]
                ]);
                break;
            case self::FAVICON:
                $this->behaviors()->get('FileUpload')->config([
                    'allowable_file_types' => [
                        'value' => ['ico'],
                        'default_value' => ['ico']
                    ]
                ]);
                break;
        }
        if ($data->offsetExists('default_content')) {
            $data->offsetUnset('default_content');
        }
        if ($data->offsetExists('default_value')) {
            $data->offsetUnset('default_value');
        }
    }
}

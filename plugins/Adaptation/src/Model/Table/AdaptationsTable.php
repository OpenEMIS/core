<?php
namespace Adaptation\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;

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

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        Cache::delete('adaptations');
        $configItems = TableRegistry::get('Configuration.ConfigItems');
        $adaptationConfigItemRecord = $configItems->findByCode('adaptations')->first();
        $adaptationConfigItemRecord->value = Time::now()->toUnixString();
        $configItems->save($adaptationConfigItemRecord);
    }
}

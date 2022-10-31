<?php
namespace Theme\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\ORM\Query;

class ThemesTable extends AppTable
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
            'type' => 'image',
            'compression' => 100,
            'size' => '2MB',
            'allowable_file_types' => ['jpeg', 'jpg', 'gif', 'png']
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $themes = $this;
        return $validator
            ->add('value', 'ruleNotHexadecimal', [
                'rule' => function ($value, $context) use ($themes) {
                    if ($context['data']['id'] == $themes::COLOUR) {
                        return !$value || (ctype_xdigit($value) && strlen($value) == 6);
                    } else {
                        return true;
                    }
                },
                'message' => __('Please enter a valid 6 digit hexadecimal code')
            ])
            ->allowEmpty('value')
            ->allowEmpty('content')
            ->allowEmpty('default_content')
            ->allowEmpty('default_value');
    }

    public function findIndex(Query $query, array $options)
    {
        return $query->where([$this->aliasField('name').' <> ' => 'Copyright Notice In Footer']);
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
            case self::COLOUR:
                $data['value'] = strtoupper($data['value']);
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
        Cache::delete('themes');
        $configItems = TableRegistry::get('Configuration.ConfigItems');
        $themeConfigItemRecord = $configItems->findByCode('themes')->first();
        $themeConfigItemRecord->value = Time::now()->toUnixString();
        $configItems->save($themeConfigItemRecord);
    }
}

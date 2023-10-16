<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use App\Model\Table\AppTable;

class LocalesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsToMany('LocaleContents', [
            'through' => 'LocaleContentTranslations',
            'foreignKey' => 'locale_id',
            'targetForeignKey' => 'locale_content_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
                ->add('iso', [
                    'ruleUnique' => [
                        'message' => 'This language already exist.',
                        'rule' => 'validateUnique',
                        'provider' => 'table',
                    ],
                    'ruleMinLength' => [
                        'rule' => ['minLength', 2],
                        'message' => 'Language ISO code should be 2 letters'
                    ]
                ])
              ->add('name', [
                    'ruleUnique' => [
                        'message' => 'This name already exist.',
                        'rule' => 'validateUnique',
                        'provider' => 'table',
                    ]
                ])
              ->notEmpty('direction')
            ;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        // translations won't work with uppercase codes
        $data['iso'] = strtolower($data['iso']);
    }

    public function findAllEditableLocales(Query $query, array $options)
    {
        return $query->where(['editable' => 1]);
    }

    public function findAllLocale(Query $query, array $options)
    {
        return $query
            ->hydrate(false)
            ->formatResults(function ($results) {
                $returnResult = [];
                $results = $results->toArray();
                foreach ($results as $key => $value) {
                    $returnResult[$value['iso']] = [
                        'id' => $value['id'],
                        'name' => $value['name']
                    ];
                }
                return $returnResult;
            });
    }

    public function getLangDir($iso)
    {
        $langDir = $this->find()
            ->where([$this->aliasField('iso') => $iso])
            ->extract('direction')
            ->first();

        return $langDir;
    }
}

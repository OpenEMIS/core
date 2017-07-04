<?php
namespace App\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Log\Log;

use App\Model\Table\AppTable;
use Restful\Model\Entity\Schema;

class LocaleContentsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        // $this->belongsToMany('Locales', [
        //     'through' => 'LocaleContentTranslations',
        //     'foreignKey' => 'locale_content_id',
        //     'targetForeignKey' => 'locale_id',
        //     'dependent' => true,
        //     'cascadeCallbacks' => true
        // ]);

        $this->hasMany('LocaleContentTranslations', ['className' => 'LocaleContentTranslations', 'saveStrategy' => 'replace']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('en', [
                    'ruleUnique' => [
                        'message' => 'This translation already exist.',
                        'rule' => 'validateUnique',
                        'provider' => 'table',
                    ]
                ])
            ;
        return $validator;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $localeTable = TableRegistry::get('Locales');
        $localeList = $localeTable->find('allLocale')->toArray();

        foreach ($data as $key => $value) {
            if ($key != 'id' && $key != 'en' && !strpos($key, '_')) {
                foreach ($localeList as $localekey => $localevalue) {
                    $currentLocale = $localeList[$localekey]['iso'];
                    if ($currentLocale == $key) {
                        $data['locale_content_translations'][] = [
                        'locale_content_id' => $data['id'],
                        'locale_id' => $localeList[$localekey]['id'],
                         'translation' => $data[$currentLocale]
                        ];
                    }
                }
                // pr($data);die;
            }
        }
    }

    public function findIndex(Query $query, array $options)
    {
        $querystring = $options['querystring'];

        $query->select(['LocaleContentTranslations.translation', 'LocaleContents.en', 'LocaleContents.id'])
            ->leftJoinWith('LocaleContentTranslations')
            ->where(['LocaleContentTranslations.locale_id' => $querystring['locale_id']]);

        Log::write('debug', $query->sql());
        return $query;
    }

    public function findView(Query $query, array $options)
    {
        $query->contain(['LocaleContentTranslations.Locales']);
        return $query;
    }

    public function findEdit(Query $query, array $options)
    {
        $query->find('allTranslatedLocale');

        Log::write('debug', $query);
        return $query;
    }

    public function findAllTranslatedLocale(Query $query, array $options)
    {
        return $query
            ->contain(['LocaleContentTranslations.Locales'])
            ->hydrate(false)
            ->formatResults(function ($results) {
                $returnResult = [];
                $results = $results->toArray()[0];
                $returnResult['id'] = $results['id'];
                $returnResult['en'] = $results['en'];
                foreach ($results['locale_content_translations'] as $contentTranslation) {
                    $iso = $contentTranslation['locale']['iso'];
                    $name = $contentTranslation['locale']['name'];

                    $returnResult[$iso.'_'.$name] = $contentTranslation['locale']['name'];
                    $returnResult[$iso] = $contentTranslation['translation'];
                }
                return [$returnResult];
            })
            ;
    }
}

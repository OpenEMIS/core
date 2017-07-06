<?php
namespace App\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\Log\Log;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class LocaleContentsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsToMany('Locales', [
            'through' => 'LocaleContentTranslations',
            'foreignKey' => 'locale_content_id',
            'targetForeignKey' => 'locale_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
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

    public function findIndex(Query $query, array $options)
    {
        $querystring = $options['querystring'];

        $query
            ->contain(['Locales' => function($q) use ($querystring) {
                return $q->where(['Locales.id' => $querystring['locale_id']]);
            }]);
        return $query;
    }

    public function findView(Query $query, array $options)
    {
        $query->contain(['Locales']);
        return $query;
    }

    public function findEdit(Query $query, array $options)
    {
        $query->contain(['Locales']);
        return $query;
    }

}

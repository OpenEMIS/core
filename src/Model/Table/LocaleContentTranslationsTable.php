<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class LocaleContentTranslationsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('LocaleContents', ['className' => 'LocaleContents', 'foreignKey' => 'locale_content_id', 'joinType' => 'INNER']);
        $this->belongsTo('Locales', ['className' => 'Locales', 'foreignKey' => 'locale_id', 'joinType' => 'INNER']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator;
    }
}

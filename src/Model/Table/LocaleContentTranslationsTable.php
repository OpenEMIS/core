<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;

class LocaleContentTranslationsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('LocaleContents', ['className' => 'LocaleContents', 'foreignKey' => 'locale_content_id', 'joinType' => 'INNER']);
        $this->belongsTo('Locales', ['className' => 'Locales', 'foreignKey' => 'locale_id', 'joinType' => 'INNER']);
    }
}

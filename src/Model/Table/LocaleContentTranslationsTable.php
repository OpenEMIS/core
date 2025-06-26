<?php
namespace App\Model\Table;

use App\Model\Table\ControllerActionTable;

class LocaleContentTranslationsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('LocaleContents', ['className' => 'System.LocaleContentsLanguage', 'foreignKey' => 'locale_content_id', 'joinType' => 'INNER']);
        $this->belongsTo('Locales', ['className' => 'System.Locales', 'foreignKey' => 'locale_id', 'joinType' => 'INNER']);
    }
}

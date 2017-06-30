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


class LocaleContentsTable extends AppTable {

    private $localeTable = null;
    private $localeList = null;
    private $localeName = null;

	public function initialize(array $config) {
		parent::initialize($config);

		$this->localeTable = TableRegistry::get('Locales');
    	$this->localeList = $this->localeTable->find('allLocale')->toArray();
		$this->localeName = [];

    	//data massaging 
    	foreach($this->localeList as $localekey => $localevalue)
    	{
			//extracting locale name
			//e.g. [zh] = Chinese
			$this->localeName[$this->localeList[$localekey]['iso']] = $this->localeList[$localekey]['name'];
    	}

        // $this->belongsToMany('Locales', [
        //     'through' => 'LocaleContentTranslations',
        //     'foreignKey' => 'locale_content_id',
        //     'targetForeignKey' => 'locale_id',
        //     'dependent' => true,
        //     'cascadeCallbacks' => true
        // ]);

        $this->hasMany('LocaleContentTranslations', ['className' => 'LocaleContentTranslations', 'saveStrategy' => 'replace']);
	}

	public function validationDefault(Validator $validator) {
    	$validator = parent::validationDefault($validator);

		$validator
			// ->add('en', 'ruleUnique', [
  	// 			'rule' => 'checkUniqueEnglishField'
  	// 		])
  	// 		;

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
    	foreach($data as $key => $value)
    	{
    		if($key != 'id' && $key != 'en' && !strpos($key, '_'))
    		{
	            foreach($this->localeList as $localekey => $localevalue)
	            {
	            	$currentLocale = $this->localeList[$localekey]['iso'];
	            	if($currentLocale == $key)
		            {
		            	$data['locale_content_translations'][] = [
		                'locale_content_id' => $data['id'],
		                'locale_id' => $this->localeList[$localekey]['id'],
			             'translation' => $data[$currentLocale]
	            		];
		            }
            	}
            	// pr($data);die;
    		}

    	}
    }

  //   public function viewUpdateSchema(Event $event, Schema $schema, $entity, ArrayObject $extra)
  //   {
  //   	parent::viewUpdateSchema($event, $schema, $entity, $extra);

  //   	//populating schema dynamically
		// foreach($this->localeName as $namekey => $namevalue)
		// {
		// 	$schema->addNew($namekey)->displayFrom($namekey)->label($namevalue);
		// }
  //   }
 

  //   public function addUpdateSchema(Event $event, Schema $schema, ArrayObject $extra)
  //   {
  //   	//populating schema dynamically
		// foreach($this->localeName as $namekey => $namevalue)
		// {
		// 	$schema->addNew($namekey)->label($namevalue);
		// }
  //   }

 //    public function editUpdateSchema(Event $event, Schema $schema, $entity, ArrayObject $extra)
 //    {
	// 	// $allLocaleResult = $this->find('allLocale')->where([$this->aliasField('id') => $entity->id])->toArray();
	// 	// pr($allLocaleResult);die;

 //    	//populating schema dynamically
	// 	foreach($this->localeName as $namekey => $namevalue)
	// 	{
	// 		$schema->addNew($namekey)->displayFrom($namekey)->label($namevalue);
	// 	}

		
	// }

	public function findView(Query $query, array $options)
	{
		return $query->find('allTranslatedLocale');
	}

	public function findEdit(Query $query, array $options)
	{
		$query->find('allTranslatedLocale');
		// $query->select(['LocaleContents.id', 'LocaleContents.en', 'Locales.id',  'Locales.name' => 'LocaleContentTranslations.translation', 'LocaleContentTranslations.id'])
		// 			->from(['LocaleContents' => 'locale_contents'])
		// 			->join(['Locales' => 'locales'])
		// 			->leftJoin(['LocaleContentTranslations' => 'locale_content_translations'], 
		// 				[
		// 				'LocaleContentTranslations.locale_content_id = LocaleContents.id'
		// 				 ])
		// 			;
			
		// $query
		// // ->from(['LocaleContents' => 'locale_contents'])
		// // ->contain(['LocaleContentTranslations.Locales' => function($q)
		// // 	{
		// // 		$q->select(['LocaleContentTranslations.locale_content_id']);
		// // 		return $q;
		// // 	}])
		// ->join(['Locales' => 'locales'])
		// ->leftJoinWith('LocaleContentTranslations')
		// ->where(['LocaleContentTranslations.locale_content_id ='.$this->aliasField('id'), 'LocaleContentTranslations.locale_id = Locales.id'])
		// ->select(['test' => 'Locales.name','en','Translation' => 'LocaleContentTranslations.translation'])
		// ;


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

<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\i18n\Time;
use Firebase\JWT\JWT;
use Cake\Utility\Security;
use Cake\Core\Configure;

class ExternalDataSourceAttributesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index'],
            'Staff' => ['index']
        ]);
    }

    public function getExternalDataSourceAttributeValues($typeName = null)
    {
        $list = $this->find('list', [
                'groupField' => 'external_data_source_type',
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])->toArray();

        if (!is_null($typeName)) {
            if (isset($list[$typeName])) {
                return $list[$typeName];
            } else {
                return [];
            }
        } else {
            return $list;
        }
    }

    public function findAttributes(Query $query, array $options = [])
    {
        $ConfigItemTable = TableRegistry::get('ConfigItems');
        $externalSourceType = $ConfigItemTable
            ->find()
            ->select([$ConfigItemTable->aliasField('value')])
            ->where([$ConfigItemTable->aliasField('code') => 'external_data_source_type'])
            ->first();

        $externalSourceType = $externalSourceType['value'];

        return $query
            ->find('list', [
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->select([$this->aliasField('value'), $this->aliasField('attribute_field')])
            ->where([
                $this->aliasField('external_data_source_type') => $externalSourceType
            ]);
    }

    public function generateServerAuthorisationToken($clientId, $scope, $tokenUri, $encryptedPrivateKey)
    {
        $keyAndSecret = explode('.', $encryptedPrivateKey);
        $privateKey = '';
        if (count($keyAndSecret) == 2) {
            list($privateKey, $secret) = $keyAndSecret;
            $secret = openssl_private_decrypt($this->urlsafeB64Decode($secret), $protectedKey, Configure::read('Application.private.key'));
            if ($secret) {
                $privateKey = Security::decrypt($this->urlsafeB64Decode($privateKey), $protectedKey);
            }
        }
        $exp = intval(Time::now()->toUnixString()) + 3600;
        $iat = Time::now()->toUnixString();

        $payload = [
            'iss' => $clientId,
            'scope' => $scope,
            'aud' => $tokenUri,
            'exp' => $exp,
            'iat' => $iat
        ];

        return JWT::encode($payload, $privateKey, 'RS256');
    }

    public function findUri(Query $query, array $options = [])
    {
        $ConfigItemTable = TableRegistry::get('ConfigItems');
        $externalSourceType = $ConfigItemTable
            ->find()
            ->select([$ConfigItemTable->aliasField('value')])
            ->where([$ConfigItemTable->aliasField('code') => 'external_data_source_type'])
            ->first();

        $externalSourceType = $externalSourceType['value'];
        $attributeField = isset($options['record_type']) ? $options['record_type'] : null;
        return $query
            ->select([$this->aliasField('value')])
            ->where([
                $this->aliasField('attribute_field') => $attributeField,
                $this->aliasField('external_data_source_type') => $externalSourceType
            ])
            ->first();
    }
}

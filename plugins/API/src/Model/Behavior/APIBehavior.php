<?php
namespace API\Model\Behavior;

use Cake\ORM\Behavior;

class APIBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);
	}

/***************************************************************************************
*
* other methods
*
****************************************************************************************/
    public function getErrorMessage($code, $params = []) {
        if (array_key_exists($code, $this->_errorCodes)) {
            if (!empty($params) && array_key_exists('identity_type', $params)) {
                if (empty($params['identity_type'])) {
                    $description = __(str_replace('{id_field}' , 'OpenEMIS' , $this->_errorCodes[$code]['description']));
                } else {
                    $description = __(str_replace('{id_field}' , trim($params['identity_type'][key($params['identity_type'])]['name']) , $this->_errorCodes[$code]['description']));
                }
            } else if (!empty($params) && array_key_exists('organisation_administrator', $params)) {
                if (empty($params['organisation_administrator'])) {
                    $description = __(str_replace('{organisation_administrator}' , 'Organisation Administrator' , $this->_errorCodes[$code]['description']));
                } else {
                    $description = __(str_replace('{organisation_administrator}' , trim($params['organisation_administrator']) , $this->_errorCodes[$code]['description']));
                }
            } else {
                $description = __($this->_errorCodes[$code]['description']);
            }
            return [
                'code' => $this->_errorCodes[$code]['code'],
                'description' => $description
            ];
        }

        return __('error code not available');
    }

    private $_errorCodes = [
        0 => [
            'code' => null,
            'description' => null
        ],
        1 => [
            'code' => '0x0001',
            'description' => 'Invalid Token'
        ],
        2 => [
            'code' => '0x0002',
            'description' => 'Invalid Token Format'
        ],
        3 => [
            'code' => '0x0003',
            'description' => 'Current user account is inactive - Please contact {organisation_administrator} for details'
            // 'description' => 'Account is inactive - Please contact MOEYS PPRE for details'
        ],
        4 => [
            'code' => '0x0004',
            'description' => 'Invalid {id_field} Number'
        ],
        'openemis_server_error' => [
            'code' => 'OE001',
            'description' => 'Unable to log in to OpenEMIS server - Please contact OpenEMIS server administrator'
        ],
        'openemis_identity_type_config_error' => [
            'code' => 'OE002',
            'description' => 'Identity Types configuration in OpenEMIS system have empty National Code or spaces in National Code - Please contact OpenEMIS system administrator'
        ],
        'openemis_identity_type_not_found' => [
            'code' => 'OE003',
            'description' => 'Requested Identity Type is not found in OpenEMIS system - Please contact OpenEMIS system administrator'
        ],
        'openemis_persona_type_error' => [
            'code' => 'OE004',
            'description' => 'Requested Persona Type is not found in OpenEMIS system - Please contact OpenEMIS system administrator'
        ]
    ];

}

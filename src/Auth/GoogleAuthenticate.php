<?php

namespace App\Auth;

use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Auth\BaseAuthenticate;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;

// 3rd party xlsx writer library
require_once(ROOT . DS . 'vendor' . DS  . 'googlephpapi' . DS . 'src' . DS . 'Google' . DS . 'autoload.php');

class GoogleAuthenticate extends BaseAuthenticate
{

    public function authenticate(Request $request, Response $response)
    {
        $fields = $this->_config['fields'];
        $session = $request->session();
        if ($session->check('Google.tokenData')) {
        	$tokenData = $session->read('Google.tokenData');
            $email = $tokenData['payload']['email'];
            $emailArray = explode('@', $tokenData['payload']['email']);
            $userName = $emailArray[0];
            $hostedDomain = $emailArray[1];
            $configHD = $session->read('Google.hostedDomain');
            // Additional check just in case the hosted domain check fail
            if (strtolower($hostedDomain) != strtolower($configHD)) {
            	return false;
            } else {
            	$isFound = $this->_findUser($userName);

	            // If user is found login, if not do create user logic
	            if ($isFound) {
	                return $isFound;
	            } else {
	            	$client = $session->read('Google.client');
	                $ServiceOAuth2Object = new \Google_Service_Oauth2($client);
	        		$me = $ServiceOAuth2Object->userinfo->get();

	        		// Basic user information gotten from Google
					$lastName = $me->getFamilyName();
					$firstName = $me->getGivenName();
					$gender = $me->getGender();
					$openemisNo = $this->getUniqueOpenemisId();

                    $GenderTable = TableRegistry::get('User.Genders');
                    $genderList = $GenderTable->find('list')->toArray();
                    $userGender = $GenderTable->find()->where([$GenderTable->aliasField('name') => $gender])->first();

                    // Just in case the gender is others
                    if (!empty($userGender)) {
                        $gender = $userGender->id;
                    } else {
                        $gender = key($genderList);
                    }
                    $date = Time::now();
                    // $dateString = $date->format('Y-m-d H:i:s');
					$UsersTable = TableRegistry::get('User.Users');
                    $data = [
                        'username' => $userName,
                        'openemis_no' => $openemisNo,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'gender_id' => $gender,
                        'date_of_birth' => '0000-00-00',
                        'super_admin' => 0,
                        'status' => 1,
                        'created_user_id' => 1,
                        'created' => $date,    
                    ];
                    $userEntity = $UsersTable->newEntity($data);
                    $UsersTable->save($userEntity);
	                return $this->_findUser($userName);
	            }
            }
        } else {
            return false;
        }
    }

    private function getUniqueOpenemisId($options = []) {
        $prefix = '';
        
        if (array_key_exists('model', $options)) {
            switch ($options['model']) {
                case 'Student': case 'Staff': case 'Guardian':
                    $prefix = TableRegistry::get('ConfigItems')->value(strtolower($options['model']).'_prefix');
                    $prefix = explode(",", $prefix);
                    $prefix = ($prefix[1] > 0)? $prefix[0]: '';
                    break;
            }
        }

        $latest = TableRegistry::get('User.Users')->find()
            ->order(TableRegistry::get('User.Users')->aliasField('id').' DESC')
            ->first();

        
        $latestOpenemisNo = $latest->openemis_no;
        $latestOpenemisNo = 0;
        if(empty($prefix)){
            $latestDbStamp = $latestOpenemisNo;
        }else{
            $latestDbStamp = substr($latestOpenemisNo, strlen($prefix));
        }
        
        $currentStamp = time();
        if($latestDbStamp >= $currentStamp){
            $newStamp = $latestDbStamp + 1;
        }else{
            $newStamp = $currentStamp;
        }

        return $prefix.$newStamp;
    }
}

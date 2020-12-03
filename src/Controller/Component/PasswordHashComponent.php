<?php
/**
 * Created by PhpStorm.
 * User: mkseemawat
 * Date: 6/14/2017
 * Time: 10:02 AM
 */

namespace App\Controller\Component;


use Cake\I18n\Time;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class PasswordHashComponent extends Component
{
    /**

     * @param $pure_string
     * @param $encryption_key
     * @return string
     */
    public  function encrypt($pure_string, $secretHash) {

        $iv = substr($secretHash, 0, 16);
        $encryptedMessage = openssl_encrypt($pure_string, "AES-256-CBC", $secretHash, $raw_input = false, $iv);
        $encrypted = base64_encode(
            $encryptedMessage
        );
        return $encrypted;
    }

    /**
     * @param $encrypted_string
     * @param $encryption_key
     * @return string
     */
    public function decrypt($encrypted_string, $secretHash) {

        $iv = substr($secretHash, 0, 16);
        $data = base64_decode($encrypted_string);
        $decryptedMessage = openssl_decrypt($data, "AES-256-CBC", $secretHash, $raw_input = false, $iv);
        $decrypted = rtrim(
            $decryptedMessage
        );
        return $decrypted;
    }
}
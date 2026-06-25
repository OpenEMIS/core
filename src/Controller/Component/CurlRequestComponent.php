<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;

/**
 * CurlRequestComponent
 * Handles sending cURL requests of various types.
 *
 * @author 
 * @ticket POCOR-7509
 */
class CurlRequestComponent extends Component
{
    /**
     * Sends a cURL request.
     *
     * @param string $url The URL to which the request is sent.
     * @param string $method HTTP method (GET, POST, PUT, DELETE). Default is GET.
     * @param array $headers Optional headers for the request.
     * @param array|string $data Optional data for POST/PUT requests.
     * @param array $options Additional cURL options.
     * @return array Contains the response and additional cURL information.
     */


     
     public function makeCurlRequests(string $url, string $method = 'GET', array $headers = [], array $data = []): array
     {
         $ch = curl_init();
 
         // Set common cURL options
         curl_setopt($ch, CURLOPT_URL, $url);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
         
         // Set method-specific options
         switch (strtoupper($method)) {
             case 'POST':
                 curl_setopt($ch, CURLOPT_POST, true);
                 curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                 break;
             case 'PUT':
                 curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                 curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                 break;
             case 'DELETE':
                 curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                 curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                 break;
             default:
                 curl_setopt($ch, CURLOPT_HTTPGET, true);
         }
 
         // Execute the cURL request
         $response = curl_exec($ch);
         $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         $info = curl_getinfo($ch);
         curl_close($ch);
 
        return [
                'data' => $response,
                'info' => $info,
                'statusCode' => $statusCode,
        ];

     }
}

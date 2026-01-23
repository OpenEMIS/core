<?php
namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Http\Client;
use Cake\ORM\TableRegistry;
use Cake\I18n\FrozenTime;

class WebhookCommand extends Command
{

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->out('Initialize Webhook Command ('.FrozenTime::now().')...');

        try {
            $url = $args->getArgument('url');
            $method = strtolower($args->getArgument('method') ?? 'post');

            $serverParamsJson = $args->getArgument('server_params');
            $bodyJson = $args->getArgument('body');
            if (is_string($bodyJson) && str_ends_with($bodyJson, '.json') && file_exists($bodyJson)) {
                $body = json_decode(file_get_contents($bodyJson), true) ?? [];
            } else {
                $body = json_decode($bodyJson, true) ?? [];
            }
            $serverParams = json_decode($serverParamsJson, true) ?? [];

            $headers = [
                'Content-Type' => 'application/json'
            ];
//            $io->out(print_r($serverParams, true));
            $http = new Client();

            // Check for username/password/api_key in serverParams
            $api_url = $serverParams['api_url'];
            if (!empty($serverParams['external'])  &&
                !empty($serverParams['username']) &&
                !empty($serverParams['password']) &&
                !empty($api_url)) {

                $tokenUri = rtrim($api_url, '/') . "/login";

                $api_key = $serverParams['api_key'];
                $tokenRequestBody = [
                    'username' => $serverParams['username'],
                    'password' => $serverParams['password'],
                    'api_key' => $api_key ?? ''
                ];
                $username = $serverParams['username'];
                $password = $serverParams['password'];
                $options = [
                    'headers' => $headers,
                    // other options like 'redirect' => true, 'timeout' => 30 etc
                ];
                $host = parse_url($tokenUri, PHP_URL_HOST);
                $tokenUri = $tokenUri . "?username=$username&password=$password";
                if(isset($api_key) && !empty($api_key)){
                    $tokenUri = $tokenUri . "&api_key=$api_key";
                }
                $insecureHosts = ['127.0.0.1', '::1', 'localhost'];
                if (in_array($host, $insecureHosts, true)) {
                    // Disable SSL verification for local dev only
                    $options['ssl_verify_peer']      = false;
                    $options['ssl_verify_peer_name'] = false;
                    // optionally allow self signed certs (some clients respect this)
                    $options['ssl_allow_self_signed'] = true;
                }

//                $io->out(print_r([$host => $tokenUri, $options], true));

//                $tokenResponse = $http->post($tokenUri, $tokenRequestBody, $options);
//                $decodedResponse = $tokenResponse->getJson();
//                $io->out("Response Code: " . $tokenResponse->getStatusCode());
                $tokenResponse = $http->post($tokenUri,
//                    json_encode($tokenRequestBody),
                    $tokenRequestBody,
                    $options);
//                $io->out(print_r($tokenResponse, true));

                $decodedResponse = $tokenResponse->getJson();

                $io->out("Response Code: " .$tokenResponse->getStatusCode());
//                $io->out(print_r($tokenResponse->getJson(), true));
                if ($tokenResponse->isOk() && isset($decodedResponse['data']['token'])) {
                    $token = $decodedResponse['data']['token'];
                    $headers['Authorization'] = 'Bearer ' . $token;
                } else {
                    $io->err('Token request failed. Aborting.');
                    return self::CODE_ERROR;
                }
            }

            // Perform final request
            $io->out("Sending $method request to $url");
            $options = [
                    'headers' => $headers,
                    'timeout' => 60,
                    'type' => 'json'
                ];
            $host = parse_url($url, PHP_URL_HOST);
            $insecureHosts = ['127.0.0.1', '::1', 'localhost'];
            if (in_array($host, $insecureHosts, true)) {
                // Disable SSL verification for local dev only
                $options['ssl_verify_peer']      = false;
                $options['ssl_verify_peer_name'] = false;
                // optionally allow self signed certs (some clients respect this)
                $options['ssl_allow_self_signed'] = true;
            }
//            $debugPayload = [
//                'url'    => $url,
//                'method' => $method,
//                'body'   => $body,
//                'options'=> $options,
//            ];
//            $io->out(json_encode($debugPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $response = $http->$method($url, $body, $options);


//            $io->out(print_r($response, true));

            $status = $response->getStatusCode();

            $io->out("Response Code: " .$status);
//            $io->out(print_r($response->getJson(), true));

            if (in_array($status, [200, 201, 202, 204])) {
                // the third CLI arg is usually the body param
                $bodyJson = $args->getArgument('body');
                if (is_string($bodyJson) && str_ends_with($bodyJson, '.json') && file_exists($bodyJson)) {
                    unlink($bodyJson);
//                    $io->out("Temp file deleted: $bodyJson");
                }
            }
            $io->out('End Processing Webhook Command ('.FrozenTime::now().')...');

        } catch (\Exception $e) {
            $io->err("Webhook Command > Exception: " . $e->getMessage());
            $io->err("Time: " . FrozenTime::now());
            return self::CODE_ERROR;
        }

        return self::CODE_SUCCESS;
    }

    public function buildOptionParser(\Cake\Console\ConsoleOptionParser $parser): \Cake\Console\ConsoleOptionParser
    {
        return $parser
            ->addArgument('url', [
                'help' => 'Target webhook URL',
                'required' => true
            ])
            ->addArgument('method', [
                'help' => 'HTTP method (get, post, put, delete, etc)',
                'required' => false
            ])
            ->addArgument('body', [
                'help' => 'JSON-encoded body content',
                'required' => false
            ])
            ->addArgument('server_params', [
                'help' => 'JSON-encoded server_params with auth (optional)',
                'required' => false
            ]);
    }
}

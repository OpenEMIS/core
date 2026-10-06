<?php

namespace Webhook\Shell;

use Cake\Console\Shell;
use Cake\Http\Client;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;

class WebhookShell extends Shell
{
    use LocatorAwareTrait;

    public function initialize(): void
    {
        parent::initialize();
    }

    public function main(): void
    {
        $this->out(
            'Initialize Webhook Shell (' . FrozenTime::now() . ')...'
        );

        try {
            // POCOR-6804: START
            $configItems = $this->fetchTable('Configuration.ConfigItems');
            $apiToken = $configItems->value('api_settings');

            $http = new Client();

            $options = [
                'timeout' => 60,
                'headers' => [
                    'Authorization' => $apiToken,
                ],
                'type' => 'json',
            ];
            // POCOR-6804: END

            $url = $this->args[0] ?? null;
            $method = strtolower($this->args[1] ?? 'get');
            $body = $this->args[2] ?? null;

            if (empty($url)) {
                throw new Exception('Webhook URL is required.');
            }

            $this->out($url);
            $this->out($method);

            $response = $http->{$method}(
                $url,
                $body,
                $options
            );

            $this->out(
                'Response code: ' . $response->getStatusCode()
            );

            $this->out(
                'End Processing Webhook Shell (' . FrozenTime::now() . ')...'
            );
        } catch (Exception $e) {
            $this->out('Webhook Shell > Exception:');
            $this->out($e->getMessage());
            $this->out('Time: ' . FrozenTime::now() . '...');
        }
    }
}
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
// POCOR-8915 start
    private function adjustUri($uri)
    {

        return preg_replace('#^/api#', '', $uri);
    }

    /**
     * Log test failures in JSON format.
     */
    private function logFailure($method, $uri, $headers, $data, $response)
    {
        $logFile = storage_path('logs/test_failures.log');

        // Get response content and attempt to decode it.
        $content = $response->getContent();
        $decoded = json_decode($content, true);
        // If decoding was successful and there's a "message" key, use that.
        if (is_array($decoded) && isset($decoded['message'])) {
            $content = $decoded['message'];
        }

        $logData = [
            '🔴 FAILED TEST'  => now()->toDateTimeString(),
            'Method'          => strtoupper($method),
            'Request URI'     => $uri,
            'Headers'         => $headers,
            'Request Data'    => $data,
            'Response Status' => $response->status(),
            'Response Body'   => $content,
            '--------------------------------' => '',
        ];

        File::append($logFile, json_encode($logData, JSON_PRETTY_PRINT) . PHP_EOL);
    }

    /**
     * Log test failures as CSV for uploading to Google Docs.
     *
     * Uses fputcsv to properly escape commas and special characters.
     * The "Request Data (PHP Code)" column is generated using var_export so that
     * it appears as a ready-to-paste PHP code snippet.
     */
    private function logFailureCsv($method, $uri, $headers, $data, $response)
    {
        $csvFile = storage_path('logs/test_failures.csv');
        $isNew   = !file_exists($csvFile) || filesize($csvFile) === 0;

        $handle = fopen($csvFile, 'a');
        if ($handle === false) {
            return;
        }

        // Create CSV header if file is new.
        if ($isNew) {
            fputcsv($handle, [
                'Timestamp',
                'Method',
                'Request URI',
                'Headers',
                'Request Data (PHP Code)',
                'Response Status',
                'Response Body'
            ]);
        }

        // Process the response content to extract only the error message.
        $content = $response->getContent();
        $decoded = json_decode($content, true);
        if (is_array($decoded) && isset($decoded['message'])) {
            $content = $decoded['message'];
        }

        // Convert $data to a PHP code snippet.
        $dataSnippet = '$data = ' . var_export($data, true) . ';';

        $row = [
            now()->toDateTimeString(),
            strtoupper($method),
            $uri,
            json_encode($headers),
            $dataSnippet,
            $response->status(),
            $content,
        ];

        fputcsv($handle, $row);
        fclose($handle);
    }

    private function processRequest($method, $uri, $data = [], $headers = [])
    {
        $adjustedUri = $this->adjustUri($uri);
        $response = parent::$method($adjustedUri, $data, $headers);

        $is_success = in_array($response->status(), [200, 201, 204]);
        $is_failure = !$is_success;

        if ($is_failure) {
            $this->logFailure($method, $adjustedUri, $headers, $data, $response);
            $this->logFailureCsv($method, $adjustedUri, $headers, $data, $response);
            // Output a failure message to STDOUT so the shell script can catch it.
            echo "TEST FAILURE: " . strtoupper($method) . " $adjustedUri returned status " . $response->status() . "\n";
            // Optionally flush output to ensure immediate output:
            flush();
        } else {
            // Optionally, echo success messages too
            echo "TEST SUCCESS: " . strtoupper($method) . " $adjustedUri returned status " . $response->status() . "\n";
            flush();
        }

        return $response;
    }

    public function get($uri, array $headers = [])
    {
        return $this->processRequest(__FUNCTION__, $uri, [], $headers);
    }

    public function post($uri, array $data = [], array $headers = [])
    {
        return $this->processRequest(__FUNCTION__, $uri, $data, $headers);
    }

    public function put($uri, array $data = [], array $headers = [])
    {
        return $this->processRequest(__FUNCTION__, $uri, $data, $headers);
    }

    public function delete($uri, array $data = [], array $headers = [])
    {
        return $this->processRequest(__FUNCTION__, $uri, $data, $headers);
    }

    public function patch($uri, array $data = [], array $headers = [])
    {
        return $this->processRequest(__FUNCTION__, $uri, $data, $headers);
    }

    public function getJson($uri, array $headers = [])
    {
        return $this->processRequest(__FUNCTION__, $uri, [], $headers);
    }

    public function postJson($uri, array $data = [], array $headers = [])
    {
        return $this->processRequest(__FUNCTION__, $uri, $data, $headers);
    }

    public function putJson($uri, array $data = [], array $headers = [])
    {
        return $this->processRequest(__FUNCTION__, $uri, $data, $headers);
    }

    public function deleteJson($uri, array $data = [], array $headers = [])
    {
        return $this->processRequest(__FUNCTION__, $uri, $data, $headers);
    }

    public function patchJson($uri, array $data = [], array $headers = [])
    {
        return $this->processRequest(__FUNCTION__, $uri, $data, $headers);
    }

    public function url($path = '')
    {
        return url($this->adjustUri($path));
    }
    // POCOR-8915 end
}

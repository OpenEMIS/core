<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CheckApiTables extends Command
{
    protected $signature = 'check:api-tables';
    protected $description = 'Check API tables for vulnerabilities and output the results to a CSV file';

    public function handle()
    {
        $inputFilePath = storage_path('app/temp/apis.txt');
        $outputFilePath = storage_path('app/temp/apis_problems.csv');

        if (!File::exists($inputFilePath)) {
            $this->error("Input file not found: {$inputFilePath}");
            return 1;
        }

        $apis = File::lines($inputFilePath)->map(fn($line) => trim($line))->filter()->toArray();

        $vulnerabilities = [];
        foreach ($apis as $api) {
            $table = $this->getTableFromApi($api);
            $this->info("Checking table: {$table}");

            try {
                $columns = $this->getTableColumns($table);
                $primaryKeyColumns = array_keys(array_filter($columns, fn($col) => $col['is_primary']));
//                Log::info($columns);
                $vulnerabilities[] = [
                    'api' => $api,
                    'numeric_non_increment_id_primary_key' => $this->hasNumericNonIncrementPrimaryKey($columns, $primaryKeyColumns),
                    'non_numeric_id_primary_key' => $this->hasNonNumericPrimaryKey($columns, $primaryKeyColumns),
                    'no_id' => $this->hasNoId($columns),
                    'numeric_id_not_primary_key' => $this->hasNumericIdNotPrimaryKey($columns, $primaryKeyColumns),
                    'non_numeric_id_not_primary_key' => $this->hasNonNumericIdNotPrimaryKey($columns, $primaryKeyColumns),
                    'complex_primary_key' => $this->hasComplexPrimaryKey($primaryKeyColumns),
                    'impossible_to_create_new_record' => $this->isImpossibleToCreateNewRecord($columns),
                ];
            } catch (\Exception $e) {
                $this->error("Error checking table: {$table}. Error: " . $e->getMessage());
                $vulnerabilities[] = [
                    'api' => $api,
                    'numeric_non_increment_id_primary_key' => 'error',
                    'non_numeric_id_primary_key' => 'error',
                    'no_id' => 'error',
                    'numeric_id_not_primary_key' => 'error',
                    'non_numeric_id_not_primary_key' => 'error',
                    'complex_primary_key' => 'error',
                    'impossible_to_create_new_record' => 'error',
                ];
            }
        }

        foreach ($vulnerabilities as $key => $vulnerability) {
            // Check if all vulnerability-related keys are empty or false
            if (
                !$vulnerability['numeric_non_increment_id_primary_key'] &&
                !$vulnerability['non_numeric_id_primary_key'] &&
                !$vulnerability['no_id'] &&
                !$vulnerability['numeric_id_not_primary_key'] &&
                !$vulnerability['non_numeric_id_not_primary_key'] &&
                !$vulnerability['complex_primary_key'] &&
                !$vulnerability['impossible_to_create_new_record']
            ) {
                unset($vulnerabilities[$key]); // Unset the row if no vulnerabilities
            }
        }

        $this->generateCsv($vulnerabilities, $outputFilePath);
        $this->info("Vulnerability check completed. Results saved to {$outputFilePath}");
        return 0;
    }

    protected function getTableFromApi($api)
    {
        $apiPath = trim($api, '/');
        $apiPath = str_replace('_', '-', $apiPath);
        if (!str_starts_with($apiPath, 'api/v5/')) {
            $apiPath = 'api/v5/' . $apiPath;
        }
        $segments = explode('/', $apiPath);
        $resourceName = end($segments);
        return str_replace('-', '_', $resourceName);
    }

    private function getTableColumns($tableName): array
    {
        $columns = \DB::select("SHOW COLUMNS FROM {$tableName}");

        $schema = [];
        foreach ($columns as $column) {
            $schema[$column->Field] = [
                'type' => $column->Type,
                'nullable' => $column->Null === 'YES',
                'key' => $column->Key,
                'is_primary' => $column->Key === 'PRI',
                'auto_increment' => $column->Extra === 'auto_increment',
            ];
        }

        // Fetch primary key constraints
        $foreignKeys = \DB::select("
        SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = '{$tableName}'
        AND TABLE_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");

        foreach ($foreignKeys as $fk) {
            $schema[$fk->COLUMN_NAME]['foreign_key'] = [
                'table' => $fk->REFERENCED_TABLE_NAME,
                'column' => $fk->REFERENCED_COLUMN_NAME,
            ];
        }

        return $schema;
    }

    protected function hasNumericNonIncrementPrimaryKey($columns, $primaryKeyColumns)
    {
        foreach ($primaryKeyColumns as $column) {
            $columnInfo = $columns[$column];

            if ($columnInfo) {
                $type = $columnInfo['type'];
                $isAutoIncrement = $columnInfo['auto_increment'];

                if (str_contains($type, 'int') && !$isAutoIncrement) {
                    return json_encode($primaryKeyColumns);
                }
            }
        }
        return false;
    }

    protected function hasNonNumericPrimaryKey($columns, $primaryKeyColumns)
    {
        foreach ($primaryKeyColumns as $column) {
            $type = $columns[$column]['type'];
            if (!str_contains($type, 'int')) {
                return json_encode($primaryKeyColumns);
            }
        }
        return false;
    }

    protected function hasNoId($columns)
    {
        return !array_key_exists('id', $columns);
    }

    protected function hasNumericIdNotPrimaryKey($columns, $primaryKeyColumns)
    {
        foreach ($columns as $column => $attributes) {
            if ($column === 'id' && !in_array($column, $primaryKeyColumns)) {
                $type = $attributes['type'];
                if (str_contains($type, 'int')) {
                    return json_encode($columns[$column]);
                }
            }
        }
        return false;
    }

    protected function hasNonNumericIdNotPrimaryKey($columns, $primaryKeyColumns)
    {
        foreach ($columns as $column => $attributes) {
            if ($column === 'id' && !in_array($column, $primaryKeyColumns)) {
                $type = $attributes['type'];
                if (!str_contains($type, 'int')) {
                    return json_encode($columns[$column]);
                }
            }
        }
        return false;
    }

    protected function hasComplexPrimaryKey($primaryKeyColumns)
    {
        if(count($primaryKeyColumns) > 1){
            return json_encode($primaryKeyColumns);
        }
    }

    protected function isImpossibleToCreateNewRecord($columns)
    {
        $non_empties = [];
        foreach ($columns as $column => $attributes) {
            if (isset($attributes['foreign_key']) && !$attributes['nullable']) {
                $relatedTable = $attributes['foreign_key']['table'];
                $relatedCount = DB::table($relatedTable)->count();
                if ($relatedCount == 0) {
                    $non_empties[] = $relatedTable . ' is empty';
                }
            }
        }
        if (count($non_empties) > 0) {
            return json_encode($non_empties);
        }
        return false;
    }

    protected function generateCsv($vulnerabilities, $filePath)
    {
        $file = fopen($filePath, 'w');
        fputcsv($file, ['api', 'numeric_non_increment_id_primary_key', 'non_numeric_id_primary_key', 'no_id', 'numeric_id_not_primary_key', 'non_numeric_id_not_primary_key', 'complex_primary_key', 'impossible_to_create_new_record']);

        foreach ($vulnerabilities as $vulnerability) {
            fputcsv($file, $vulnerability);
        }

        fclose($file);
    }
}

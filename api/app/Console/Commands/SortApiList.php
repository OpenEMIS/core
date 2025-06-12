<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class SortApiList extends Command
{
    protected $signature = 'sort:api-list';
    protected $description = 'Sort API list based on table dependencies and save to new file';

    public function handle()
    {
        $inputFilePath = storage_path('app/temp/apis_old.txt');
        $outputFilePath = storage_path('app/temp/apis_new.txt');

        if (!File::exists($inputFilePath)) {
            $this->error("Input file not found: {$inputFilePath}");
            return;
        }

        $this->info("Reading API list from: {$inputFilePath}");
        $apis = File::lines($inputFilePath)->map(fn($line) => trim($line))->filter()->toArray();

        $this->info("Original API list:");
        foreach ($apis as $api) {
            $this->info($api);
        }

        $sortedApis = $this->sortApis($apis);

        $this->info("Sorted API list:");
        foreach ($sortedApis as $api) {
            $this->info($api);
        }

        File::put($outputFilePath, implode("\n", $sortedApis));
        $this->info("Sorted API list has been written to {$outputFilePath}");
    }

    private function getTableDependencies()
    {
        $dependencies = [];
        $tables = DB::select('SHOW TABLES');
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            if (preg_match('/^zz?_/', $tableName)) {
                $this->info('Skipping table: ' . $tableName);
                continue;
            }
            $this->info('searching keys for table: ' . $tableName);
            $foreignKeys = DB::select("SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                                       FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                       WHERE REFERENCED_TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$tableName]);
            foreach ($foreignKeys as $foreignKey) {
                $this->info('adding keys for table: ' . $tableName . ' key ' . $foreignKey->REFERENCED_TABLE_NAME);
                $dependencies[$foreignKey->TABLE_NAME][] = $foreignKey->REFERENCED_TABLE_NAME;
            }
        }
        return $dependencies;
    }

    private function sortApis(array $apis)
    {
        $tableNames = array_map(function($api) {
            return str_replace('-', '_', basename($api));
        }, $apis);

        $dependencies = $this->getTableDependencies();
        $sortedTables = [];
        $unsortableTables = [];
        $this->info('Dependencies: ' . json_encode($dependencies));

        while (!empty($tableNames)) {
            $progress = false;
            foreach ($tableNames as $key => $tableName) {
                $this->info('Sorting table ' . $key . ' checking ' . $tableName);
                if (empty($dependencies[$tableName]) || array_diff($dependencies[$tableName], $sortedTables) === []) {
                    $sortedTables[] = $tableName;
                    unset($tableNames[$key]);
                    $progress = true;
                    $this->info('Sorted tables added: ' . $tableName);
                    $this->info('Unsorted tables removed: ' . $key);
                }
                $this->info('Sorted tables size: ' . sizeof($sortedTables));
                $this->info('Unsorted tables size: ' . sizeof($tableNames));
            }

            // Check for circular dependencies
            if (!$progress) {
                $this->error('Circular dependency detected. Unable to sort the following tables: ' . implode(', ', $tableNames));
                $unsortableTables = array_merge($unsortableTables, $tableNames);
                break;
            }
        }

        $sortedApis = array_map(function($tableName) {
            return '/api/v5/' . str_replace('_', '-', $tableName);
        }, array_merge($sortedTables, $unsortableTables));

        return $sortedApis;
    }
}

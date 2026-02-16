<?php
namespace Report\Model\Behavior;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Behavior;
use Cake\Event\EventInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use Cake\Log\Log;

class CsvBehavior extends Behavior
{
    protected $_defaultConfig = [
        'folder' => 'export',
        'default_excludes' => ['password', 'modified_user_id', 'modified', 'created_user_id', 'created'],
        'excludes' => [],
        'pages' => []
    ];

    public function initialize(array $config): void
    {
        $this->setConfig('excludes', array_merge($this->getConfig('default_excludes'), $this->getConfig('excludes')));
        if (!isset($config['filename'])) {
            $this->setConfig('filename', $this->_table->getAlias());
        }

        $folder = WWW_ROOT . $this->getConfig('folder');

        if (!file_exists($folder)) {
            umask(0);
            mkdir($folder, 0777, true);  // POCOR-8787
            Log::write('debug', 'Created folder: ' . $folder);
        }
    }

    public function generateCSV($settings = [])
    {
        $model = $this->_table;

        $_settings = [
            'file' => $this->getConfig('filename') . '_' . date('Ymd') . 'T' . date('His') . '.csv',
            'path' => WWW_ROOT . $this->getConfig('folder') . DS,
            'download' => true,
            'purge' => true,
            'query' => $this->_table->find()
        ];
        $_settings = new ArrayObject(array_merge($_settings, $settings));

        $model->dispatchEvent('ExcelTemplates.Model.onCsvBeforeGenerate', [$_settings], $this);

        // Start: csv filepath
        $filepath = $_settings['path'] . $_settings['file'];
        $_settings['file_path'] = $filepath;
        // End: csv filepath

        // Start: sql filepath
        $process = $_settings['process'];
        $processId = $process->id;

        $sqlFilename = $this->getConfig('filename') . '_' . $processId . '.sql';
        $sqlFilepath = $_settings['path'] . $sqlFilename;
        $_settings['file_path_sql'] = $sqlFilepath;
        // End: sql filepath

        $this->saveSql($_settings);
        $this->createSqlFile($_settings);
        $this->exportToCsv($_settings);
        $this->deleteSqlFile($_settings);

        $model->dispatchEvent('ExcelTemplates.Model.onCsvGenerateComplete', [$_settings], $this);
    }

    private function saveSql($settings)
    {
        $process = $settings['process'];
        $query = $settings['query'];
        $sql = isset($settings['sql']) ? $settings['sql'] : $query->sql();

        // Check if $sql is null or empty
        if ($sql === null || empty($sql)) {
            return;
        }

        // Escape SQL query
        //$sql = $this->escapeSql($sql); //POCOR-8787 

        $ReportProgress = TableRegistry::getTableLocator()->get('Report.ReportProgress');
        $ReportProgress->updateAll(
            ['`sql`' => $sql],
            ['id' => $process->id]
        );

    }

    private function escapeSql($sql)
    {
        // Escape SQL query
        // This is a simplified example, you might need to improve this based on your requirements
        //return "'" . addslashes($sql) . "'";
        return str_replace("'", "''", $sql);
    }

    private function createSqlFile($settings)
    {
        $process = $settings['process'];
        $sqlFilepath = $settings['file_path_sql'];
        $processId = $process->id;

        $this->deleteSqlFile($settings);

        $ReportProgress = TableRegistry::getTableLocator()->get('Report.ReportProgress');

        $sqlFile = new File($sqlFilepath, true, 0777);
        $sqlStatement = $ReportProgress->get($processId)->sql;
        $sqlFile->write($sqlStatement);
    }

    private function exportToCsv($settings)
    {
        $csvFilepath = $settings['file_path'];
        $sqlFilepath = $settings['file_path_sql'];

        // delete file and recreate
        $csvFile = new File($csvFilepath);
        $csvFile->delete();

        $connectionConfig = ConnectionManager::get('default')->config();
        $username = $connectionConfig['username'];
        $password = $connectionConfig['password'];
        $host = isset($connectionConfig['host']) ? $connectionConfig['host'] : null;
        $port = isset($connectionConfig['port']) ? $connectionConfig['port'] : null;
        $database = $connectionConfig['database'];

        $mysqlPath = trim(shell_exec('which mysql'));
        $exportCmd = $mysqlPath;
        //$exportCmd = DS . 'bin'. DS . 'mysql';
        $exportCmd .= ' --user=' . $username;
        $exportCmd .= ' --password=' . $password;
        if (!is_null($host) && strtolower($host) != 'localhost') {
            $exportCmd .= ' --host=' . $host;
        }
        /*POCOR-6403 - added is_numeric condition check port*/
        if (!is_null($port) && is_numeric($port)) {
            $exportCmd .= ' --port=' . $port;
        }
        $exportCmd .= ' --quick';
        $exportCmd .= ' --default-character-set=utf8';
        $exportCmd .= ' ' . $database;
        $exportCmd .= ' < ' . $sqlFilepath;
        $exportCmd .= '| sed -e \'s/\t/\",\"/g;s/^/\"/g;s/$/\"/g\'';
        $exportCmd .= ' > ' . $csvFilepath;
        echo "Export Command: $exportCmd<br>";

        try {
            $pid = exec($exportCmd);
        } catch(\Exception $ex) {
            pr($ex);
            Log::write('error', __METHOD__ . ' exception during csv export : '. $ex);
        }
    }

    private function deleteSqlFile($settings)
    {
        $sqlFilepath = $settings['file_path_sql'];

        // delete file and recreate
        $sqlFile = new File($sqlFilepath);
        $sqlFile->delete();
    }
}

<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\Core\App;
use Cake\Cache\Cache;
use Cake\Datasource\ConnectionManager;
use Cake\Core\Exception\Exception;
use Cake\Database\Connection;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use Cake\Auth\DefaultPasswordHasher;
use App\Form\DatabaseConnectionForm;
use App\Form\DatabaseCreationForm;
use App\Form\SuperAdminCreationForm;

class InstallersController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->Auth->allow(['step1', 'step2', 'step3', 'step4', 'step5']);

    }

    public function step1()
    {
    }

    public function step2()
    {
        $databaseConnection = new DatabaseConnectionForm();
        if ($this->request->is('post') && empty($databaseConnection->errors())) {
            $execute = $databaseConnection->execute($this->request->data);
            // form logic get from the step2b.ctp
            if ($execute) {
                $session = $this->request->session();
                $session->write('db_host', $this->request->data('database_server_host'));
                $session->write('db_port', $this->request->data('database_server_port'));
                $session->write('db_root', $this->request->data('admin_user'));
                $session->write('db_root_pass', $this->request->data('admin_password'));
                $this->redirect(['controller' => 'Installers', 'action' => 'step3']);
            }
        }
        $this->set(compact('databaseConnection'));
    }

    public function step3()
    {
        $databaseCreation = new DatabaseCreationForm();
        if ($this->request->is('post') && empty($databaseCreation->errors())) {
            $databaseCreation->execute($this->request->data);
            // form logic get from the step3b
            $this->redirect(['controller' => 'Installers', 'action' => 'step4']);
        }
        $this->set(compact('databaseCreation'));
    }

    public function step3b()
    {
        $session = $this->getSession();
        $host = $session->read('db_host');
        $port = $session->read('db_port');
        $root = $session->read('db_root');
        $rootpass = $session->read('db_root_pass');
        $requestData = $this->request->getData();
        if (!empty($requestData) && isset($requestData['createDatabase'])) {
            $database = $requestData['database'];
            $username = $requestData['databaseLogin'];
            $dbPassword = $requestData['databasePassword1'];
            $dbConfirm = $requestData['databasePassword2'];
            $src = ROOT . '/config/datasource.php';
            $file = new File($src);

            $headTemplate = "<?php
            return [
                    'Datasources' => [
                        'default' => [
                            'className' => 'Cake\Database\Connection',
                            'driver' => 'Cake\Database\Driver\Mysql',
                            'persistent' => false,
            ";
            $bodyTemplate = [
                            'host' => $host,
                            'username' => $root,
                            'password' => $rootpass,
                            'database' => $database,
             ];
            $footTemplate = "
                            'encoding' => 'utf8',
                            'timezone' => 'UTC',
                            'cacheMetadata' => true,
                            'quoteIdentifiers' => true,
                    ]
                ]
            ];
            ";

            if (!empty($dbPassword) && $dbPassword == $dbConfirm) {
                $file->write($headTemplate);
                foreach ($bodyTemplate as $key => $value) {
                    $file->append("'". $key ."'" . ' => ' . "'". $value ."',");
                }
                $file->append($footTemplate);
                $file->close();

                $connection = $this->getMySqlConnection();

                $this->createDB($connection, $database);
                $this->createDbUser($connection, $host, $username, $dbPassword, $database);
                $this->createDbStructure();
                $session->write('db_user', $username);
                $session->write('db_pass', $dbPassword);
                $session->write('db_name', $database);

                if ($this->getError()) {
                    $this->response->withHeader('Location: '. '/school/api/installers/' . 'step4');
                } else {
                    $this->response->withHeader('Location: '. '/school/api/installers/' . 'step3');
                }
            } else {
                $session->write('error', 'Your database passwords do not match.');
                $this->response->withHeader('Location: ' . '/school/api/installers/' . 'step3');
            }
        }
    }

    public function step4()
    {
        $session = $this->request->session();
        $dbUser = $session->read('db_user');
        $dbHost = $session->read('db_host');
        $dbPassword = $session->read('db_pass');
        $database = $session->read('db_name');

        $superAdminCreation = new SuperAdminCreationForm();
        if ($this->request->is('post') && empty($superAdminCreation->errors())) {
            $execute = $superAdminCreation->execute($this->request->data);
            // form logic get from the step4b
            $this->redirect(['controller' => 'Installers', 'action' => 'step5']);
        }
        $this->set(compact('superAdminCreation'));
    }

    public function step4b()
    {
        $session = $this->getSession();
        $requestData = $this->request->getData();
        if (!empty($requestData) && isset($requestData['createUser'])) {
            $username = $requestData['username'];
            $userPass1 = $requestData['password1'];
            $userPass2 = $requestData['password2'];

            if (!empty($userPass1) && $userPass1 == $userPass2) {
                $connection = ConnectionManager::get('default');
                $this->createUser($connection, $username, $userPass1);
                $session->write('username', $username);
                $session->write('userPass', $userPass1);

                if ($this->getError()) {
                    $this->response->withHeader('Location: '. '/school/api/installers/' . 'step5');
                } else {
                    $this->response->withHeader('Location: '. '/school/api/installers/' . 'step4');
                }
            } else {
                $session->write('error', 'Your account passwords do not match.');
                $this->response->withHeader('Location: '. '/school/api/installers/' . 'step4');
            }
        } else {
            $session->write('error', 'Please enter the account info.');
            $this->response->withHeader('Location: '. '/school/api/installers/' . 'step4');
        }
    }

    public function step5()
    {
    }

    private function createDb($connection, $dbName)
    {
        try {
            $connection->connect();
            $connection->query('DROP DATABASE IF EXISTS ' . $dbName);
            $connection->query('CREATE DATABASE ' . $dbName);
            $connection->disconnect();
        } catch (Exception $ex) {
            $session = $this->getSession();
            if ($this->getError()) {
                $session->write('error', $ex->getMessage());
            }
        }
    }

    private function createDbUser($connection, $host, $user, $password, $dbName)
    {
        try {
            $connection->connect();
            $createUserSQL = "CREATE USER '" . $user."'@'".$host."'" ." IDENTIFIED BY '".$password."'";
            $dropUserSQL = 'DROP USER '. $user.'@'.$host;
            $flushSQL = 'FLUSH PRIVILEGES;';
            $grantSQL = "GRANT CREATE, DROP, DELETE, INSERT, SELECT, UPDATE ON ".$dbName.".* TO '".$user."'@'".$host."' WITH GRANT OPTION";

            $resultSet = $connection->execute('SELECT COUNT(1) AS COUNT FROM mysql.user WHERE User=? AND Host=?', [$user, $host], ['string' , 'string']);
            $count = 0;
            foreach ($resultSet as $row) {
                if (isset($row['COUNT'])) {
                    $count = $row['COUNT'];
                    break;
                }
            }
            if ($count > 0) {
                $connection->query($dropUserSQL);
            }
            $connection->query($createUserSQL);
            $connection->query($flushSQL);
            $connection->query($grantSQL);
            $connection->disconnect();
        } catch (Exception $ex) {
            $session = $this->getSession();
            if ($this->getError()) {
                $session->write('error', $ex->getMessage());
            }
        }
    }

    private function createDbStructure()
    {
        try {
            $connection = ConnectionManager::get('default');
            $dataPath = ROOT . '/sql/Setup/data_v2.sql';
            $structurePath = ROOT . '/sql/Setup/structure.sql';
            $structureSql = file_get_contents($structurePath);
            $dataSql = file_get_contents($dataPath);
            $connection->connect();
            $connection->query($structureSql);
            $connection->query($dataSql);
            $connection->disconnect();
        } catch (Exception $ex) {
            $session = $this->getSession();
            if ($this->getError()) {
                $session->write('error', $ex->getMessage());
            }
        }
    }

    private function createUser($connection, $username, $password)
    {
        $truncate = "TRUNCATE TABLE `security_users`;";
        $insertSQL = "INSERT INTO `security_users` (username, password, openemis_no, first_name, last_name, gender_id, date_of_birth, super_admin, status, created_user_id, created) VALUES (%s)";

        $values = array(
            "'" . $username . "'",
            "'" . $this->password($password) . "'",
            "'admin'",       //openemis_no
            "'System'",         // first_name
            "'Administrator'",  // last_name
            1,                  // gende_id -> default to 1
            "'2000-01-01'",     //date of birth
            1,                  // 1 = super admin
            1,                  // 1 = status
            1,                  // created by
            'NOW()'
        );
        try {
            $connection->connect();
            $connection->query($truncate);
            $connection->query(sprintf($insertSQL, implode(', ', $values)));
            $connection->disconnect();
        } catch (Exception $ex) {
            $session = $this->getSession();
            if ($this->getError()) {
                $session->write('error', $ex->getMessage());
            }
        }
    }

    private function getMySqlConnection()
    {
        $config = [
                    'className' => 'Cake\Database\Connection',
                    'driver' => 'Cake\Database\Driver\Mysql',
                    'persistent' => false,
                    'host' => 'localhost',
                    'username' => 'root',
                    'password' => 'password',
                    'database' => 'mysql',
                    'encoding' => 'utf8',
                    'timezone' => 'UTC',
                    'cacheMetadata' => true,
                    'quoteIdentifiers' => true,
                ];

        $connection = new Connection($config);
        return $connection;
    }

    private function getError()
    {
        $session = $this->getSession();
        $error = $session->read('error');
        if (!isset($error)) {
            return true;
        } else {
            return false;
        }
    }

    private function getSession()
    {
        return $this->request->session();
    }

    private function password($password)
    {
        if (empty($password)) {
            return null;
        } else {
            return (new DefaultPasswordHasher)->hash($password);
        }
    }
}

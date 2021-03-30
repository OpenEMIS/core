<?php
namespace Installer\Form;

require CONFIG . 'snapshot_config.php';
require CONFIG . 'installer_mode_config.php';
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Migrations\Migrations;
use PDO;

/**
 * DatabaseInstaller Form.
 */
class DatabaseConnectionForm extends Form
{
    const CONFIG_TEMPLATE = "<?php
return [
    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => {host},
            'port' => {port},
            'username' => {user},
            'password' => {pass},
            'database' => {database},
            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'quoteIdentifiers' => true,
            //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
        ],
    ],
];
";

    private $app_extra_template = "<?php
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

\$privateKeyPath = CONFIG . 'private.key';
\$publicKeyPath = CONFIG . 'public.key';

\$privateKeyFile = new File(\$privateKeyPath);
\$publicKeyFile = new File(\$publicKeyPath);
\$privateKey = \$privateKeyFile->read();
\$publicKey = \$publicKeyFile->read();

return [
    'Error' => [
        // Application specific error handler
        'exceptionRenderer' => 'App\Error\AppExceptionRenderer'
    ],

    'Cache' => [
        // Application specific labels cache
        'labels' => [
            'className' => 'File',
            'path' => CACHE,
            'probability' => 0,
            'duration' => '+1 month',
            'groups' => ['labels'],
            'url' => env('CACHE_DEFAULT_URL', null)
        ]
    ],

    'Application' => [
        // Generate a private and public key pair using the command line by executing \"openssl genrsa -out private.key 1024\" and \"openssl rsa -in private.key -pubout -out public.key\"
        'private' => [
            'key' => \$privateKey
        ],
        'public' => [
            'key' => \$publicKey
        ]
    ],

    'EmailTransport' => [
        'openemis' => [
            'className' => 'Smtp',
            // The following keys are used in SMTP transports
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'timeout' => 30,
            'username' => 'app@kordit.com',
            'password' => '',
            'client' => null,
            'tls' => true,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],

    'Email' => [
        'openemis' => [
            'transport' => 'openemis',
            'from' => ['app@kordit.com' => 'DoNotReply'],
            //'charset' => 'utf-8',
            //'headerCharset' => 'utf-8',
        ],
    ]
";

    private $app_extra_core_mode = ",'coreMode' => false";    
    private $app_extra_school_mode = ",'schoolMode' => true";
    private $app_extra_census_mode = ",'censusMode' => false";
    private $app_extra_vaccinations_mode = ",'vaccinationsMode' => false";

    private $app_extra_template_end = "];";
    
    /**
     * Builds the schema for the modelless form
     *
     * @param \Cake\Form\Schema $schema From schema
     * @return \Cake\Form\Schema
     */
    protected function _buildSchema(Schema $schema)
    {
        return $schema->addField('database_server_host', ['type' => 'string'])
            ->addField('database_server_port', ['type' => 'string'])
            ->addField('admin_user', ['type' => 'string'])
            ->addField('admin_password', ['type' => 'password'])
            ->addField('username', ['type' => 'string'])
            ->addField('password', ['type' => 'string'])
            ->addField('area_name', ['type' => 'string'])
            ->addField('area_code', ['type' => 'string']);
    }

    /**
     * Form validation builder
     *
     * @param \Cake\Validation\Validator $validator to use against the form
     * @return \Cake\Validation\Validator
     */
    protected function _buildValidator(Validator $validator)
    {
        return $validator
            ->requirePresence('database_server_host')
            ->requirePresence('database_server_port')
            ->requirePresence('database_admin_user')
            ->requirePresence('database_admin_password')
            ->requirePresence('account_password')
            ->requirePresence('retype_password')
            ->add('account_password', [
                'compare' => [
                    'rule' => ['compareWith', 'retype_password'],
                    'message' => 'Passwords entered does not match.'
                ]
            ])
            ->requirePresence('area_code')
            ->requirePresence('area_name');
    }

    /**
     * Defines what to execute once the From is being processed
     *
     * @param array $data Form data.
     * @return bool
     */
    protected function _execute(array $data)
    {
        $host = $data['database_server_host'];
        $port = $data['database_server_port'];
        $root = $data['database_admin_user'];
        $rootPass = $data['database_admin_password'];
        if (APPLICATION_MODE == 'census') {
            $default_db_name = Configure::read('installerCensus') ? 'prd_cen_dmo' : APPLICATION_DB_NAME;
            $default_db_user = Configure::read('installerCensus') ? 'prd_cen_user' : APPLICATION_DB_NAME;
        }else if(APPLICATION_MODE == 'school'){
            $default_db_name = Configure::read('installerSchool') ? 'prd_sch_dmo' : APPLICATION_DB_NAME;
            $default_db_user = Configure::read('installerSchool') ? 'prd_sch_user' : APPLICATION_DB_NAME;
        }else if(APPLICATION_MODE == 'vaccinations'){
            $default_db_name = Configure::read('installerVaccinations') ? 'prd_vac_dmo' : APPLICATION_DB_NAME;
            $default_db_user = Configure::read('installerVaccinations') ? 'prd_vac_user' : APPLICATION_DB_NAME;
        }else{
            $default_db_name = Configure::read('installerCore') ? 'prd_cor_dmo' : APPLICATION_DB_NAME;
            $default_db_user = Configure::read('installerCore') ? 'prd_cor_user' : APPLICATION_DB_NAME;
        }
        $db = isset($data['datasource_db']) ? $data['datasource_db'] : $default_db_name;
        $dbUser = isset($data['datasource_user']) ? $data['datasource_user'] : $default_db_user;
        $dbPassword = isset($data['datasource_password']) ? $data['datasource_password'] : bin2hex(random_bytes(4));
       
        $connectionString = sprintf('mysql:host=%s;port=%d', $host, $port);
        $pdo = new PDO($connectionString, $root, $rootPass);

        $template = str_replace('{host}', "'$host'", self::CONFIG_TEMPLATE);
        $template = str_replace('{port}', "'$port'", $template);
        $template = str_replace('{pass}', "'$dbPassword'", $template);

        $dbFileHandle = fopen(CONFIG . 'datasource.php', 'w');
        $privateKeyHandle = fopen(CONFIG . 'private.key', 'w');
        $publicKeyHandle = fopen(CONFIG . 'public.key', 'w');
        $appExtraHandle = fopen(CONFIG . 'app_extra.php', 'w');
        $dbUserHostPermission = isset($data['datasource_user_host']) ? $data['datasource_user_host'] : $host;
        if ($dbFileHandle && $privateKeyHandle && $publicKeyHandle) {
            $res = openssl_pkey_new(['private_key_bits' => 1024]);
            openssl_pkey_export($res, $privKey);
            fwrite($privateKeyHandle, $privKey);
            fclose($privateKeyHandle);
            $pubKey = openssl_pkey_get_details($res);
            fwrite($publicKeyHandle, $pubKey['key']);
            fclose($publicKeyHandle);
            $app_extra_text = $this->app_extra_template;
            if (Configure::read('installerSchool')) {
                $app_extra_text .= $this->app_extra_school_mode;
            }
            else if (Configure::read('installerCensus')) {
                $app_extra_text .= $this->app_extra_census_mode;
            }
            else if (Configure::read('installerVaccinations')) {
                $app_extra_text .= $this->app_extra_vaccinations_mode;
            }else{
                $app_extra_text .= $this->app_extra_core_mode;
            }
            $app_extra_text .= $this->app_extra_template_end;
            fwrite($appExtraHandle, $app_extra_text);
            $this->createDb($pdo, $db);
            $this->createDbUser($pdo, $dbUserHostPermission, $dbUser, $dbPassword, $db);
            $template = str_replace('{database}', "'$db'", $template);
            $template = str_replace('{user}', "'$dbUser'", $template);
            fwrite($dbFileHandle, $template);
            fclose($dbFileHandle);

            Configure::load('datasource', 'default');
            Configure::load('app_extra', 'default');
            ConnectionManager::config(Configure::consume('Datasources'));
            $connection = ConnectionManager::get('default');
       
            $dbConfig = $connection->config();
            $username = $dbConfig['username']; 
            $host = $dbConfig['host']; 
            $dbname = $dbConfig['database']; 
            $password = $dbConfig['password']; 
            $fileName = DATABASE_DUMP_FILE;

            $result = exec('mysql --user='.$username.' --password='.$password.' --host='.$host.' '.$dbname.' < '.WWW_ROOT.'sql_dump' . DS .$fileName.'.sql');
            $this->createUser($data['account_password']) && $this->createArea($data['area_code'], $data['area_name']);
            /*$sql = mysqli_connect($host, $username, $password, $dbname);
            $sqlSource = file_get_contents(WWW_ROOT.'sql_dump' . DS .$fileName.'.sql');
            mysqli_multi_query($sql,$sqlSource);*/
            Cache::clear(false, '_cake_model_');
            Cache::clear(false, 'themes');
            // $migrations = new Migrations();
            // $source = 'Snapshot' . DS . VERSION;
            // $status = $migrations->status(['source' => $source]);
            // $executed = false;
            // if ($status[0]['status'] == 'down') {
            //     $migrate = $migrations->migrate(['source' => $source]);
            //     if ($migrate) {
            //         $seedSource = 'Snapshot' . DS . VERSION . DS . 'Seeds';
            //         $seedStatus = $migrations->seed(['source' => $seedSource]);
            //         if ($seedStatus) {
            //             // Applying missed out migrations
            //             $executed = $migrations->migrate();
            //             Cache::clear(false, '_cake_model_');
            //             if ($executed) {
            //                 return $this->createUser($data['account_password']) && $this->createArea($data['area_code'], $data['area_name']);
            //             }
            //         }
            //     }
            // }
            return false;
        } else {
            return false;
        }
    }

    private function createUser($password)
    {
        $UserTable = TableRegistry::get('User.Users');
        $data = [
            'id' => 1,
            'username' => 'admin',
            'password' => $password,
            'openemis_no' => 'sysadmin',
            'first_name' => 'System',
            'middle_name' => null,
            'third_name' => null,
            'last_name' => 'Administrator',
            'preferred_name' => null,
            'email' => null,
            'address' => null,
            'postal_code' => null,
            'address_area_id' => null,
            'birthplace_area_id' => null,
            'gender_id' => 1,
            'date_of_birth' => new Date(),
            'date_of_death' => null,
            'nationality_id' => null,
            'identity_type_id' => null,
            'identity_number' => null,
            'external_reference' => null,
            'super_admin' => 1,
            'status' => 1,
            'last_login' => new Date(),
            'photo_name' => null,
            'photo_content' => null,
            'preferred_language' => 'en',
            'is_student' => 0,
            'is_staff' => 0,
            'is_guardian' => 0
        ];

        $entity = $UserTable->newEntity($data, ['validate' => false]);
        return $UserTable->save($entity);
    }

    private function createArea($name, $code)
    {
        $AreasTable = TableRegistry::get('Area.Areas');
        $data = [
            'id' => 1,
            'code' => $code,
            'name' => $name,
            'parent_id' => null,
            'lft' => 1,
            'rght' => 2,
            'area_level_id' => 1,
            'order' => 1,
            'visible' => 1
        ];
        $entity = $AreasTable->newEntity($data);
        return $AreasTable->save($entity);
    }

    private function createDb($pdo, &$db)
    {
        $dbSql = "SELECT 1 FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?;";
        $result = true;
        $counter = 0;
        $newDb = '';
        do {
            if ($counter == 0) {
                $newDb = $db;
                $counter++;
            } else {
                $newDb = $db . '_' . $counter++;
            }
            $dbExists = $pdo->prepare($dbSql);
            $dbExists->execute([$newDb]);
            $result = $dbExists->rowCount();
        } while ($result);
        $db = $newDb;
        $createDbSQL = sprintf("CREATE DATABASE %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", $db);
        $pdo->exec($createDbSQL);
    }

    private function createDbUser($pdo, $host, &$user, $password, $db)
    {
        $host = '%';
        $userSql = "SELECT 1 FROM mysql.user WHERE User = ? AND Host = ?";
        $result = true;
        $counter = 0;
        $newUser = '';
        do {
            if ($counter == 0) {
                $newUser = $user;
                $counter++;
            } else {
                $newUser = $user . '_' . $counter++;
            }
            $userExists = $pdo->prepare($userSql);
            $userExists->execute([$newUser, $host]);
            $result = $userExists->rowCount();
        } while ($result);
        $user = $newUser;
        $createUserSQL = sprintf("CREATE USER '%s'@'%s' IDENTIFIED BY '%s'", $user, $host, $password);
        $flushPriviledges = "FLUSH PRIVILEGES";
        $grantSQL = sprintf("GRANT ALL ON %s.* TO '%s'@'%s'", $db, $user, $host);
        $pdo->exec($createUserSQL);
        $pdo->exec($grantSQL);
        $pdo->exec($flushPriviledges);
    }
}

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
use PDOException;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Core\Configure\Engine\PhpConfig;//POCOR-8308


/**
 * DatabaseInstaller Form.
 */
class DatabaseConnectionForm extends Form
{
    //POCOR-8308 start
    const CONFIG_TEMPLATE="<?php

    return [

        'debug' => filter_var(env('DEBUG',false), FILTER_VALIDATE_BOOLEAN),
        'Security' => [
            'salt' => env('SECURITY_SALT', '444db3ff8e6247fc30dd0d21414066d956d3f6340ff059927b40e4dddc1b880c'),
        ],
    
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
            ],
        ],
        'EmailTransport' => [
            'openemis' => [
                'className' => 'Smtp',
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'timeout' => 30,
                'username' => 'app@openemis.org',
                'password' => '',
                'client' => null,
                'tls' => true,
                'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
            ],
        ],

        'Email' => [
            'openemis' => [
                'transport' => 'openemis',
                'from' => ['app@openemis.org' => 'DoNotReply'],
            ],
        ]
    ];
    "
    ;
       //POCOR-8308 end
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
    protected function _buildSchema(Schema $schema): Schema
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
    protected function _execute(array $data): bool
    {   
        $this->createDefaultConfigurationFiles();//POCOR-9686

        $current_time_limit = ini_get('max_execution_time');
        set_time_limit(300);
        $originalMemoryLimit = ini_get('memory_limit'); //POCOR-8308
        ini_set('memory_limit', '1G'); //POCOR-8308
        $host = $data['database_server_host'];
        $port = $data['database_server_port'];
        $root = $data['database_admin_user'];
        $rootPass = $data['database_admin_password'];
        if (APPLICATION_MODE == 'census') {
            $default_db_name = Configure::read('installerCensus') ? 'prd_cen_dmo' : APPLICATION_DB_NAME;
            $default_db_user = Configure::read('installerCensus') ? 'prd_cen_user' : APPLICATION_DB_USER_NAME;
        }else if(APPLICATION_MODE == 'school'){
            $default_db_name = Configure::read('installerSchool') ? 'prd_school_dmo' : APPLICATION_DB_NAME;
            $default_db_user = Configure::read('installerSchool') ? 'prd_school_user' : APPLICATION_DB_USER_NAME;
        }else if(APPLICATION_MODE == 'vaccinations'){
            $default_db_name = Configure::read('installerVaccinations') ? 'prd_vac_dmo' : APPLICATION_DB_NAME;
            $default_db_user = Configure::read('installerVaccinations') ? 'prd_vac_user' : APPLICATION_DB_USER_NAME;
        }else{
            $default_db_name = Configure::read('installerCore') ? 'prd_cor_dmo' : APPLICATION_DB_NAME;
            $default_db_user = Configure::read('installerCore') ? 'prd_core_user' : APPLICATION_DB_USER_NAME;
        }

        $db = isset($data['datasource_db']) ? $data['datasource_db'] : $default_db_name;
        $dbUser = isset($data['datasource_user']) ? $data['datasource_user'] : $default_db_user;
        $dbPassword = isset($data['datasource_password']) ? $data['datasource_password'] : bin2hex(random_bytes(4));
    
        $connectionString = sprintf('mysql:host=%s;port=%d', $host, $port);
        $pdo = new PDO($connectionString, $root, $rootPass);
        //POCOR-9686 Starts
        // Make sure DDL errors (CREATE USER / GRANT / etc.) are not swallowed
        // silently — otherwise the new account ends up without privileges and
        // the subsequent mysqli_connect() fails with "Access denied for user
        // '...'@'%' to database '...'".
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //POCOR-9686 Ends
        $template = str_replace('{host}', "'$host'", self::CONFIG_TEMPLATE);
        $template = str_replace('{port}', "'$port'", $template);
        $template = str_replace('{pass}', "'$dbPassword'", $template);
        $dbFileHandle = fopen(CONFIG . 'app_local.php', 'w');
        $privateKeyHandle = fopen(CONFIG . 'private.key', 'w');
        $publicKeyHandle = fopen(CONFIG . 'public.key', 'w');
        $appExtraHandle = fopen(CONFIG . 'app_extra.php', 'w');
        $dbUserHostPermission = isset($data['datasource_user_host']) ? $data['datasource_user_host'] : $host;
        if ($dbFileHandle && $privateKeyHandle && $publicKeyHandle) {
            //POCOR-8308 start
            $config = [ 'private_key_bits' => 1024];
            if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
                $opensslConfigPath =  $_SERVER['OPENSSL_CONF'];
                $apachePath = strstr($opensslConfigPath, 'apache', true);
                $config['config']=$apachePath.'apache/conf/openssl.cnf';
            }
            $res = openssl_pkey_new($config);
            $privateKey = '';
            openssl_pkey_export($res, $privateKey, null, $config);
            fwrite($privateKeyHandle, $privateKey);
            fclose($privateKeyHandle);
            $keyDetails = openssl_pkey_get_details($res);
            $publicKey = $keyDetails['key'];
            fwrite($publicKeyHandle, $publicKey);
            //fwrite($publicKeyHandle, $pubKey['key']);//POCOR-9686 commit - we need to use the generated public key, not the one from config template
            fclose($publicKeyHandle);
            //POCOR-8308 end
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
            $pdo_query = "SET GLOBAL sql_mode = REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', '')";
            $stmt = $pdo->prepare($pdo_query);
            $stmt->execute(); 
            $template = str_replace('{database}', "'$db'", $template);
            $template = str_replace('{user}', "'$dbUser'", $template);
            fwrite($dbFileHandle, $template);
            fclose($dbFileHandle);
            //POCOR-9686 start
            // POCOR: keep api/.env in sync with the values we just wrote into
            // config/app_local.php so the Laravel API can connect with the
            // same credentials.
            $this->updateEnvFile($host, (string)$port, $db, $dbUser, $dbPassword);

            // Populate APP_KEY (Laravel) and JWT_SECRET (tymon/jwt-auth) in
            // api/.env. We deliberately generate these natively in PHP rather
            // than shelling out to `php artisan key:generate` / `jwt:secret`
            // because:
            //   * PHP_BINARY under mod_php / mod_fcgid points at the SAPI
            //     module, NOT a usable CLI binary;
            //   * the Apache/PHP-FPM worker has no $PATH and often no
            //     permission to exec from the webroot;
            //   * exec() swallows stderr silently, so a failure leaves an
            //     empty APP_KEY in .env (which then breaks the Laravel API);
            //   * on Windows `cd <path> && ...` requires `cd /d` to switch
            //     drives, which the artisan invocations above did not do.
            // Both values are just random strings (artisan does exactly the
            // same thing internally), so reproducing them in-process is
            // simpler and far more reliable.
            $this->generateApiAppKeyAndJwtSecret();
            // Make absolutely sure the next require/include of app_local.php
            // does not return a stale, OPcache-cached version of the file we
            // just rewrote — that has been the root cause of the
            // "Access denied for user '...'@'%' to database '...'" failures
            // reported from local installs.
            clearstatcache(true, CONFIG . 'app_local.php');
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate(CONFIG . 'app_local.php', true);
                @opcache_invalidate(CONFIG . 'app_extra.php', true);
            }
            //POCOR-9686 end
            //POCOR-8308 start
            $configPath = CONFIG . 'app_local.php';
            if (file_exists($configPath)) {
                Configure::config('app_local', new PhpConfig());
                Configure::load('app_local', 'app_local');
            } else {
                throw new \mysqli_sql_exception("app_local.php not found. Please ensure it exists in your config directory.");
            }

            $datasources = Configure::read('Datasources');
    
            if (!$datasources || !isset($datasources['default'])) {
                throw new \mysqli_sql_exception("Default database configuration not found in app_local.php");
            }

            if (ConnectionManager::getConfig('default')) {
                ConnectionManager::drop('default');
            }
            ConnectionManager::setConfig('default', $datasources['default']);
            //POCOR-8308 end
            $connection = ConnectionManager::get('default');
            $dbConfig = $connection->config();
            $username = $dbConfig['username']; 
            $host = $dbConfig['host']; 
            $dbname = $dbConfig['database']; 
            $password = $dbConfig['password']; 
            $fileName = DATABASE_DUMP_FILE;
            $port= isset($dbConfig['port'])?trim($dbConfig['port']):'3306';//POCOR-8308
            //POCOR-9686 start 
            // Use the same error/exception behaviour as PDO so silent
            // failures here cannot leave the install in a half-applied state.
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = @mysqli_connect($host, $username, $password, $dbname, (int)$port);//POCOR-8308
            if (!$conn) {
                // mysqli_connect_error() carries the real MySQL message
                // (e.g. "Access denied for user 'prd_core_user'@'%' to
                // database 'prd_cor_dmo'") — surface it so the wizard can
                // display a meaningful alert instead of silently failing.
                throw new \mysqli_sql_exception(
                    'Could not connect to the newly created database: '
                    . (mysqli_connect_error() ?: 'unknown error')
                );
            }
            //POCOR-9686 end
            // if (mysqli_connect_errno()) {
            //     echo "Failed to connect to MySQL: " . mysqli_connect_error();
            //     exit();
            //   }
            // $query = '';
            // $sqlScript = file(WWW_ROOT.'sql_dump' . DS .$fileName.'.sql');
            $sqlScript = file(ROOT . DS . 'download' . DS .$fileName.'.sql');
            
            
           
            //POCOR-8308 start
            // foreach ($sqlScript as $line)   {
               
            //     $line= trim($line);
            //     $startWith = substr(trim($line), 0 ,2);
            //     $endWith = substr(trim($line), -1 ,1);
            //     $endWith3 = substr(trim($line), -3 ,3);
               
            //     if (empty($line) || $startWith == '--' || $startWith == '/*' || $startWith == '//'||$endWith3=='*/;') {
            //         continue;
            //     }
            //     if (stripos($line, 'DELIMITER') === 0) {
            //         // Extract the new delimiter
            //         $delimiter = str_replace('DELIMITER ', '', $trimmedLine);
            //         continue; // Skip the delimiter line itself
            //     }
                    
            //     $query = $query . $line;
            //     if ($endWith == ';') {
            //         // $max_allowed_packet=20777216;
            //         mysqli_options($conn,MYSQLI_OPT_CONNECT_TIMEOUT,600);
            //         // mysqli_options($conn, MYSQLI_INIT_COMMAND, "SET GLOBAL max_allowed_packet=$max_allowed_packet");
            //         mysqli_set_charset($conn, 'utf8');
            //         mysqli_query($conn,$query) or die('<div class="error-response sql-import-response">Problem in executing the SQL query <b>' . $query. '</b></div>');
            //         $query= '';     
            //     }
            // }
            $query = '';  // Initialize query storage
            $delimiter = ';';  // Default delimiter is `;`

            // Disable foreign key checks
            if (!mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0;")) {
                die('<div class="error-response sql-import-response">Failed to disable foreign key checks</div>');
            }

            foreach ($sqlScript as $line) {
                // Replace collation type
                $line = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_general_ci', $line);
                
                // Trim the line to remove unnecessary spaces
                $trimmedLine = trim($line);
                $startWith = substr($trimmedLine, 0, 2);
                $endWith = substr($trimmedLine, -strlen($delimiter), strlen($delimiter));
                
                // Skip comments and empty lines
                if (empty($trimmedLine) || $startWith == '--' || $startWith == '/*' || $startWith == '//' || substr($trimmedLine, -3) == '*/;') {
                    continue;
                }

                // Check if the line contains a new DELIMITER
                if (stripos($trimmedLine, 'DELIMITER') === 0) {
                    // Change the delimiter
                    $delimiter = str_replace('DELIMITER ', '', $trimmedLine);
                    continue; // Skip the DELIMITER line itself
                }

                // Skip lines that are just the current delimiter
                if ($trimmedLine === $delimiter) {
                    continue;
                }

                // Append the current line to the query
                $query .= $line . "\n";

                // Execute the query if the line ends with the delimiter
                if (substr($trimmedLine, -strlen($delimiter)) == $delimiter) {
                    // Remove the delimiter from the query
                    $query = str_replace($delimiter, '', $query);

                    // Set MySQL options
                    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 600);
                    mysqli_set_charset($conn, 'utf8');
                    $max_allowed_packet = 20777216;
                    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 600);
                    //mysqli_options($conn, MYSQLI_INIT_COMMAND, "SET GLOBAL max_allowed_packet=$max_allowed_packet");//POCOR-9686 commit - setting max_allowed_packet globally can cause issues in shared hosting environments, so commenting out for now
                    
                    // Execute the query
                    if (!mysqli_query($conn, $query)) {
                        die('<div class="error-response sql-import-response">Problem in executing the SQL query <b>' . $query . '</b></div>');
                    }

                    // Reset the query after execution
                    $query = '';
                }
            }
            //POCOR-8308 end
            // $result = exec('mysql -u'.$username.' -p'.$password.' --host'.$host.' '.$dbname.' < '.WWW_ROOT.'sql_dump' . DS .$fileName.'.sql');
            // $result = exec("/Applications/MAMP/Library/bin/mysql --host=localhost -u$username -p$password $db < prd_cor_zip.sql");
            $this->createUser($data['account_password']) && $this->createArea($data['area_code'], $data['area_name']);
            /*$sql = mysqli_connect($host, $username, $password, $dbname);
            $sqlSource = file_get_contents(WWW_ROOT.'sql_dump' . DS .$fileName.'.sql');
            mysqli_multi_query($sql,$sqlSource);*/
            Cache::clear('_cake_model_');
            // Cache::clear(false, 'themes');//POCOR-8308
            
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
            set_time_limit($current_time_limit);
            ini_set('memory_limit', $originalMemoryLimit);//POCOR-8308
            return true;//POCOR-8308
           
        } else {
            set_time_limit($current_time_limit);
            return false;
        }
       
        
    }

    private function createUser($password)
    {
        $UserTable = TableRegistry::getTableLocator()->get('User.Users');
        $userData = $UserTable
            ->find()
            ->where([$UserTable->aliasField('username') => 'admin'])
            ->first();
        if(!empty($userData)){
            return $UserTable->updateAll(
                ['password' => (new DefaultPasswordHasher)->hash($password)],
                ['id' => $userData->id]
            );
        }
        else{
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
    }


    private function createArea($name, $code)
    {
        $AreasTable = TableRegistry::getTableLocator()->get('Area.Areas');
        $areaData = $AreasTable
            ->find()
            ->where([$AreasTable->aliasField('code') => $code, $AreasTable->aliasField('name') => $name])
            ->first();
        if(!empty($areaData)){
            return $AreasTable->updateAll(
                ['code' => $code, 'name' => $name],
                ['id' => $areaData->id]
            );
        }else{
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
            return $AreasTable->save($entity,['skip_callbacks' => true]);//POCOR-8308
        }
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

    /**
     * POCOR-9686: Refactored createDbUser() to ensure we do not end up with 
     * a silently-created database user that lacks privileges on the new database 
     * due to a silent failure in the CREATE USER
     * Create the application's database account and grant it full access on
     * the freshly-created database.
     *
     * The `$host` argument is the host the wizard caller wants the account
     * to be reachable from. We always use the `%` wildcard for the GRANT
     * pattern so the same account works for both local and remote (e.g.
     * Docker) deployments; the verification step below uses the caller's
     * host to actually re-open a connection and prove the grant works.
     */
    private function createDbUser($pdo, $host, &$user, $password, $db)
    {
        $connectionHost = $host !== '' ? $host : 'localhost';
        $grantHost = '%';

        // remove existing user if exists
        $pdo->exec("DROP USER IF EXISTS '$user'@'$grantHost'");

        // create user
        $pdo->exec("
            CREATE USER '$user'@'$grantHost'
            IDENTIFIED BY '$password'
        ");

        // Make sure the freshly-created database is the current default
        // schema for this PDO connection before granting on it. Without
        // this, on MariaDB 10.4 + PHP mysqlnd the next GRANT statement is
        // silently rolled back later by FLUSH PRIVILEGES (the new
        // privilege never reaches disk before the privilege cache is
        // reloaded), and the subsequent mysqli_connect() fails with
        //   "Access denied for user '<user>'@'%' to database '<db>'"
        $pdo->exec("USE `$db`");

        // grant permissions
        $pdo->exec("
            GRANT ALL PRIVILEGES
            ON `$db`.*
            TO '$user'@'$grantHost'
        ");

        // NOTE: deliberately NOT calling FLUSH PRIVILEGES here.
        //
        // FLUSH PRIVILEGES is only required when the privilege tables are
        // mutated *directly* (INSERT/UPDATE on mysql.user / mysql.db).
        // The standard GRANT / REVOKE DDL we just ran already updates the
        // in-memory caches itself.
        //
        // On MariaDB 10.4 + PHP's mysqlnd specifically, issuing FLUSH
        // PRIVILEGES on the same connection right after
        //   GRANT ALL PRIVILEGES ON db.* TO 'user'@'%'
        // reloads the privilege tables before the grant has been
        // persisted, effectively undoing it. The user then exists with
        // USAGE only, and the subsequent mysqli_connect() to the new
        // database fails with
        //   "Access denied for user 'prd_core_user'@'%' to database
        //    'prd_cor_dmo'"

        // Verify the GRANT actually took effect by opening a short-lived
        // connection AS the new account against the new database. If this
        // fails we bail out with a clear, actionable error message
        // (instead of letting the install limp on and crash later at
        // mysqli_connect() with the same generic "Access denied" we used
        // to see).
        try {
            $verifyPdo = new PDO(
                'mysql:host=' . $connectionHost . ';dbname=' . $db,
                $user,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $verifyPdo = null;
        } catch (\PDOException $verifyErr) {
            throw new \mysqli_sql_exception(
                "Database user '$user'@'$grantHost' was created but cannot "
                . "access database '$db' from host '$connectionHost'. "
                . "Underlying error: " . $verifyErr->getMessage()
            );
        }
    }

    private function createDefaultConfigurationFiles()
    {
        $appLocalDefault = CONFIG . 'app_local_default.php';
        $appLocal = CONFIG . 'app_local.php';

        $defaultEnv = ROOT . DS . 'api' . DS . '.env.example';
        $envFile = ROOT . DS . 'api' . DS . '.env';

        if (!file_exists($appLocal) && file_exists($appLocalDefault)) {
            copy($appLocalDefault, $appLocal);
        }

        if (!file_exists($envFile) && file_exists($defaultEnv)) {
            copy($defaultEnv, $envFile);
        }
    }

    /**
     * Edit the freshly-copied api/.env file with the values the user supplied
     * in the installation wizard. The Laravel sub-application (under /api)
     * reads its database credentials from this file, so it MUST contain the
     * same host/port/database/user/password that we just wrote into
     * config/app_local.php — otherwise the API side of the product cannot
     * connect.
     *
     * @param string $host
     * @param string $port
     * @param string $database
     * @param string $username
     * @param string $password
     * @return void
     */
    private function updateEnvFile(string $host, string $port, string $database, string $username, string $password): void
    {
        // Replace (or append) the keys we control. Anything else the user has
        // configured manually is left untouched.
        $this->writeEnvValues([
            'DB_HOST'     => $host,
            'DB_PORT'     => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
        ]);
    }

    /**
     * Populate APP_KEY (Laravel) and JWT_SECRET (tymon/jwt-auth) in
     * api/.env using values generated natively in PHP — equivalent to
     * what `php artisan key:generate` and `php artisan jwt:secret` would
     * write, without having to shell out to a CLI process from a web
     * request.
     *
     * Existing non-empty values are left alone so repeated installs do
     * not invalidate sessions / tokens that were issued against the old
     * keys.
     *
     * @return void
     */
    private function generateApiAppKeyAndJwtSecret(): void
    {
        $existing = $this->readEnvValues(['APP_KEY', 'JWT_SECRET']);
        $updates = [];

        // APP_KEY: same format Laravel's KeyGenerateCommand emits for the
        // default AES-256-CBC cipher.
        if (empty($existing['APP_KEY']) || $existing['APP_KEY'] === 'base64:') {
            $updates['APP_KEY'] = 'base64:' . base64_encode(random_bytes(32));
        }

        // JWT_SECRET: 64-char alphanumeric string, matching what
        // tymon/jwt-auth's JWTGenerateSecretCommand writes via Str::random(64).
        if (empty($existing['JWT_SECRET'])) {
            $updates['JWT_SECRET'] = $this->randomAlnumString(64);
        }

        if (!empty($updates)) {
            $this->writeEnvValues($updates);
        }
    }

    /**
     * Read a set of keys out of api/.env.
     *
     * The match patterns use `[ \t]*` (instead of `\s*`) around the `=`
     * and `[^\r\n]*` for the value, so we behave identically on Windows
     * (CRLF), Linux (LF) and macOS (LF) — `\s` in PCRE also matches `\r`
     * and `\n`, which can confuse greedy matches on CRLF files.
     *
     * @param array<int,string> $keys
     * @return array<string,string>  Map of key => value (empty string when absent).
     */
    private function readEnvValues(array $keys): array
    {
        $envFile = ROOT . DS . 'api' . DS . '.env';
        $values = array_fill_keys($keys, '');

        if (!file_exists($envFile)) {
            return $values;
        }
        $contents = (string)file_get_contents($envFile);

        foreach ($keys as $key) {
            $pattern = '/^[ \t]*' . preg_quote($key, '/') . '[ \t]*=([^\r\n]*)/m';
            if (preg_match($pattern, $contents, $m)) {
                // Strip surrounding quotes / whitespace if any.
                $values[$key] = trim($m[1], " \t\"'");
            }
        }

        return $values;
    }

    /**
     * Replace (or append) the given KEY=value pairs inside api/.env.
     * Anything else the user has configured manually is left untouched.
     *
     * @param array<string,string> $pairs
     * @return void
     */
    private function writeEnvValues(array $pairs): void
    {
        $envFile = ROOT . DS . 'api' . DS . '.env';
        $defaultEnv = ROOT . DS . 'api' . DS . '.env.example';

        // Make sure the file exists — fall back to the default template if
        // (for any reason) the earlier copy did not happen.
        if (!file_exists($envFile)) {
            if (file_exists($defaultEnv)) {
                copy($defaultEnv, $envFile);
            } else {
                file_put_contents($envFile, "");
            }
        }

        $contents = (string)file_get_contents($envFile);

        // Preserve the dominant line-ending convention of the existing
        // file so we don't mix CRLF and LF when editing a Windows-authored
        // .env on Linux (or vice versa).
        $eol = (strpos($contents, "\r\n") !== false) ? "\r\n" : "\n";

        foreach ($pairs as $key => $value) {
            // `[ \t]*` (not `\s*`) deliberately — `\s` also matches \r/\n
            // in PCRE, which can swallow surrounding line-endings on CRLF
            // files. `[^\r\n]*` for the value side guarantees we only
            // touch the line we care about.
            $pattern = '/^[ \t]*' . preg_quote($key, '/') . '[ \t]*=[^\r\n]*/m';
            $line = $key . '=' . $value;
            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $line, $contents);
            } else {
                $contents = rtrim($contents, "\r\n") . $eol . $line . $eol;
            }
        }

        file_put_contents($envFile, $contents);

        // Make the new values visible to env() inside the same request.
        foreach ($pairs as $key => $value) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
        
    }

    /**
     * Cryptographically-secure random alphanumeric string of the given
     * length — same alphabet Laravel's Illuminate\Support\Str::random() and
     * tymon/jwt-auth's JWTGenerateSecretCommand use.
     *
     * @param int $length
     * @return string
     */
    private function randomAlnumString(int $length): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }// POCOR-9686 Ends
}

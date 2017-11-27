<?php
namespace Installer\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;
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

    const APP_EXTRA_TEMPLATE = "<?php
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
    ],

    'schoolMode' => true
];
";

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
            ->addField('admin_password', ['type' => 'password']);
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
            ->requirePresence('admin_user')
            ->requirePresence('admin_password');
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
        $root = $data['admin_user'];
        $rootPass = $data['admin_password'];

        $db = 'oe_school';
        $dbUser = 'oe_school_user';
        $dbPassword = bin2hex(random_bytes(4));

        $connectionString = sprintf('mysql:host=%s;port=%d', $host, $port);
        $pdo = new PDO($connectionString, $root, $rootPass);
        $template = str_replace('{host}', "'$host'", self::CONFIG_TEMPLATE);
        $template = str_replace('{port}', "'$port'", $template);
        $template = str_replace('{pass}', "'$dbPassword'", $template);
        $dbFileHandle = fopen(CONFIG . 'datasource.php', 'w');
        $privateKeyHandle = fopen(CONFIG . 'private.key', 'w');
        $publicKeyHandle = fopen(CONFIG . 'public.key', 'w');
        $appExtraHandle = fopen(CONFIG . 'app_extra.php', 'w');
        if ($dbFileHandle && $privateKeyHandle && $publicKeyHandle) {
            $res = openssl_pkey_new(['private_key_bits' => 1024]);
            openssl_pkey_export($res, $privKey);
            fwrite($privateKeyHandle, $privKey);
            fclose($privateKeyHandle);
            $pubKey = openssl_pkey_get_details($res);
            fwrite($publicKeyHandle, $pubKey['key']);
            fclose($publicKeyHandle);
            fwrite($appExtraHandle, self::APP_EXTRA_TEMPLATE);
            $this->createDb($pdo, $db);
            $this->createDbUser($pdo, $host, $dbUser, $dbPassword, $db);
            $template = str_replace('{database}', "'$db'", $template);
            $template = str_replace('{user}', "'$dbUser'", $template);
            fwrite($dbFileHandle, $template);
            fclose($dbFileHandle);
            return true;
        } else {
            return false;
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

    private function createDbUser($pdo, $host, &$user, $password, $db)
    {
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

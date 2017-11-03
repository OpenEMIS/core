<?php
/*
@OPENEMIS SCHOOL LICENSE LAST UPDATED ON 2014-01-30

OpenEMIS School
Open School Management Information System

Copyright © 2014 KORD IT. This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation,
either version 3 of the License, or any later version. This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please email contact@openemis.org.
*/

include_once "../config.php";
session_start();

$url = getRoot() . '/installer/';

if (!empty($_POST) && isset($_POST['createDatabase'])) {
    $host = $_SESSION['db_host'];
    $port = $_SESSION['db_port'];
    $root = $_SESSION['db_root'];
    $rootPass = $_SESSION['db_root_pass'];

    $db = $_POST['database'];
    $dbUser = $_POST['databaseLogin'];
    $dbPassword = $_POST['databasePassword1'];
    $dbConfirm = $_POST['databasePassword2'];

    $connectionString = sprintf('mysql:host=%s;port=%d', $host, $port);

    if (!empty($dbPassword) && $dbPassword === $dbConfirm) {
        try {
            $pdo = new PDO($connectionString, $root, $rootPass);
            $template = str_replace('{host}', "'$host'", CONFIG_TEMPLATE);
            $template = str_replace('{port}', "'$port'", $template);
            $template = str_replace('{user}', "'$dbUser'", $template);
            $template = str_replace('{pass}', "'$dbPassword'", $template);
            $template = str_replace('{database}', "'$db'", $template);
            $dbFileHandle = fopen(CONFIG_FILE, 'w');
            if ($dbFileHandle !== false) {
                // fwrite($dbFileHandle, $template);
                fclose($dbFileHandle);
                // createDb($pdo, $db);
                // createDbUser($pdo, $host, $dbUser, $dbPassword, $db);
                // createDbStructure($host, $port, $db, $dbUser, $dbPassword);
                $_SESSION['db_user'] = $dbUser;
                $_SESSION['db_pass'] = $dbPassword;
                $_SESSION['db_name'] = $db;
                header('Location: ' . $url . '?step=4');
            } else {
                $_SESSION['error'] = 'Unable to create configuration file. Please check your folder permissions. <br />' . CONFIG_DIR;
                header('Location: ' . $url . '?step=3');
            }
        } catch (PDOException $ex) {
            $_SESSION['error'] = $ex->getMessage();
            header('Location: ' . $url . '?step=3');
        }
    } else {
        $_SESSION['error'] = 'Your database passwords do not match.';
        header('Location: ' . $url . '?step=3');
    }
} else {
    header('Location: ' . $url . '?step=3');
}

function createDb($pdo, $db)
{
    $dropDbSQL = sprintf("DROP DATABASE IF EXISTS %s", $db);
    $createDbSQL = sprintf("CREATE DATABASE %s", $db);
    $pdo->exec($dropDbSQL);
    $pdo->exec($createDbSQL);
}

function createDbUser($pdo, $host, $user, $password, $db)
{
    $createUserSQL = sprintf("CREATE USER '%s'@'%s'", $user, $host);
    $dropUserSQL = sprintf("DROP USER '%s'@'%s'", $user, $host);
    $passwordSQL = sprintf("UPDATE mysql.user SET Password=PASSWORD('%s') WHERE User='%s' AND Host='%s'; FLUSH PRIVILEGES;", $password, $user, $host);
    $grantSQL = sprintf("GRANT ALL ON %s.* TO '%s'@'%s' WITH GRANT OPTION", $db, $user, $host);
    $resultSet = $pdo->query(sprintf("SELECT COUNT(1) AS COUNT FROM mysql.user WHERE User='%s' AND Host='%s'", $user, $host));
    $count = 0;
    foreach ($resultSet as $row) {
        if (isset($row['COUNT'])) {
            $count = $row['COUNT'];
            break;
        }
    }
    if ($count > 0) {
        $pdo->exec($dropUserSQL);
    }
    $pdo->exec($createUserSQL);
    $pdo->exec($passwordSQL);
    $pdo->exec($grantSQL);
}

function createDbStructure($host, $port, $db, $user, $password)
{
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
    $sql = file_get_contents(INSTALL_SQL);
    $pdo->exec($sql);
}

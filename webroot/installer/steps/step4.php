<?php

include_once "../config.php";
require DIR_ROOT . 'vendor/autoload.php';
include DIR_ROOT . 'config/bootstrap.php';

use Migrations\Migrations;

$url = getRoot() . '/installer/';
if (!isset($_SESSION['db_name']) || !isset($_SESSION['db_user']) || !isset($_SESSION['db_pass'])) {
    header('Location: ' . $url . '?step=3');
} else {
    createDbStructure();
}

function createDbStructure()
{
    $migrations = new Migrations();
    $source = DS . 'Snapshot' . DS . '3.11.0';
    $version = 20171027060236;
    $migrations->migrate(['source' => $source]);
    $migrations->seed(['source' => $source . DS . 'Seeds']);
    $migrations->status(['source' => DS . 'Migrations']);
    $migrations->markMigrated(null, ['target' => (string)($version - 1), 'source' => DS . 'Migrations']);
}
?>

<div class="starter-template">
    <h1>Account setup</h1>
    <p>In order to access OpenEMIS School application, you will need to create an user account.</p>
    <?php
    if (isset($_SESSION['error'])) {
        echo '<p style="color: red">' . $_SESSION['error'] . '</p>';
        unset($_SESSION['error']);
    }
    ?>

    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal formCreateDbUser" method="post" action="steps/step4b.php">
                <input type="hidden" class="form-control" name="username" value="administrator" />
                <div class="form-group">
                    <label for="username" class="col-sm-5 control-label">OpenEMIS School Login</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="username" value="administrator" disabled="disabled" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="password1" class="col-sm-5 control-label">Password</label>
                    <div class="col-sm-5">
                        <input type="password" class="form-control" name="password1" id="password1" autocomplete="off" maxlength="20" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="password2" class="col-sm-5 control-label">Confirm Password</label>
                    <div class="col-sm-5">
                        <input type="password" class="form-control" name="password2" id="password2" autocomplete="off" maxlength="20" />
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-5 col-sm-10">
                        <input type="button" class="btn btn-info" value="Back" onclick="window.location.href='<?php echo $url . '?step=3'; ?>'" />
                        <input type="submit" class="btn btn-success" name="createUser" value="Next" />
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

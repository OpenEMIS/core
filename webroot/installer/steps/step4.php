<?php

include_once "../config.php";
require DIR_ROOT . 'vendor/autoload.php';
include DIR_ROOT . 'config/bootstrap.php';
require CONFIG_DIR . 'snapshot_config.php';
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
    $source = DS . 'Snapshot' . DS . VERSION;
    $migrations->migrate(['source' => $source]);
    $migrations->seed(['source' => $source . DS . 'Seeds']);
    // Applying miss out migrations
    $migrations->migrate(['source' => DS . 'Migrations']);
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
                <input type="hidden" class="form-control" name="username" value="admin" />
                <div class="form-group">
                    <label for="username" class="col-sm-5 control-label">OpenEMIS School Login</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="username" value="admin" disabled="disabled" />
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
                    <label for="country" class="col-sm-5 control-label">Country</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" name="country" id="country" autocomplete="off" maxlength="20" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="code" class="col-sm-5 control-label">Country Code</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" name="code" id="code" autocomplete="off" maxlength="20" />
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

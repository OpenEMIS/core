<?php
$url = getRoot() . '/installer/';
if (!isset($_SESSION['db_host']) || !isset($_SESSION['db_port']) || !isset($_SESSION['db_root']) || !isset($_SESSION['db_root_pass'])) {
    header('Location: ' . $url . '?step=2');
}

$dbName = isset($_SESSION['db_name']) ? $_SESSION['db_name'] : 'oe_school';
$dbUser = isset($_SESSION['db_user']) ? $_SESSION['db_user'] : 'oe_school_user';
unset($_SESSION['db_name']);
unset($_SESSION['db_user']);
unset($_SESSION['db_pass']);
?>

<div class="starter-template">
    <h1>Database setup</h1>
    <p>We have all information we need and we are ready to go. <br />Before continue, back up your database if you need as existing data might be lost.</p>
    <?php
    if (isset($_SESSION['error'])) {
        echo '<p style="color: red">' . $_SESSION['error'] . '</p>';
        unset($_SESSION['error']);
    }
    ?>

<div class="row">
    <div class="col-md-12">
        <form class="form-horizontal" method="post" action="steps/step3b.php">
            <div class="form-group">
                <label for="database" class="col-sm-5 control-label">Database Name</label>
                <div class="col-sm-5">
                    <input type="text" class="form-control" name="database" id="database" value="<?php echo $dbName; ?>" maxlength="20" />
                    <span id="databaseNameInfo" class="help-block">OpenEMIS School database name.</span>
                </div>
            </div>

            <div class="form-group">
                <label for="databaseLogin" class="col-sm-5 control-label">Database Login</label>
                <div class="col-sm-5">
                    <input type="text" class="form-control" name="databaseLogin" id="databaseLogin" value="<?php echo $dbUser; ?>" maxlength="15" />
                    <span id="databaseNameInfo" class="help-block">OpenEMIS School database login.</span>
                </div>
            </div>

            <div class="form-group">
                <label for="databasePassword1" class="col-sm-5 control-label">Database Password</label>
                <div class="col-sm-5">
                    <input type="password" class="form-control" name="databasePassword1" id="databasePassword1" />
                </div>
            </div>

            <div class="form-group">
                <label for="databasePassword2" class="col-sm-5 control-label">Database Password Confirm</label>
                <div class="col-sm-5">
                    <input type="password" class="form-control" name="databasePassword2" id="databasePassword2" />
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-5 col-sm-10">
                    <input type="button" class="btn btn-info" value="Back" onclick="window.location.href='<?php echo $url . '?step=2'; ?>'" />
                    <input type="submit" class="btn btn-success" name="createDatabase" value="Next" />
                </div>
            </div>
        </form>
    </div>
</div>
</div>

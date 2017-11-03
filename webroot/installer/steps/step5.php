<?php
$url = getRoot() . '/installer/?step=4';
$action = getRoot();

unlink(INSTALL_FILE);
?>

<div class="starter-template">
    <h1>Installation Completed</h1>
    <p>You have successfully installed OpenEMIS School. Please click Start to launch OpenEMIS School.</p>

    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal formCreateDbUser" method="post">
                <div class="form-group">
                    <div class="col-sm-offset-5 col-sm-10">
                        <input type="button" class="btn btn-success" name="createUser" value="Start" style="margin-left: 35px;" onclick="window.location.href='<?= $action?>/Users/login'"/>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
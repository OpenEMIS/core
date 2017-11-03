<?php
$host = isset($_SESSION['db_host']) ? $_SESSION['db_host'] : 'localhost';
$port = isset($_SESSION['db_port']) ? $_SESSION['db_port'] : 3306;
$user = isset($_SESSION['db_root']) ? $_SESSION['db_root'] : 'root';
?>

<div class="starter-template">
	<h1>Setting Environment</h1>
	<p>All fields are required and case sensitive.</p>
	<?php
	if(isset($_SESSION['error'])) {
		echo '<p style="color: red">' . $_SESSION['error'] . '</p>';
		unset($_SESSION['error']);
	}
	?>

    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal" method="post" action="steps/step2b.php">
                <div class="form-group">
                    <label for="hostname" class="col-sm-5 control-label">Database Server Host</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" name="hostname" id="hostname" value="<?php echo $host; ?>" />
                        <span class="help-block">MySQL database server (usually localhost or 127.0.0.1)</span>
                    </div>
                </div>
    
                <div class="form-group">
                    <label for="port" class="col-sm-5 control-label">Database Server Port</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" name="port" id="port" value="<?php echo $port; ?>" />
                        <span class="help-block">Port to connect to you MySQL server (usually 3306)</span>
                    </div>
                </div>
        
                <div class="form-group">
                    <label for="username" class="col-sm-5 control-label">Admin Username</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" name="username" id="username" value="<?php echo $user; ?>" />
                        <span id="usernameInfo" class="help-block">Database Privileged Username.</span>
                    </div>
                </div>
    
                <div class="form-group">
                    <label for="password" class="col-sm-5 control-label">Admin Password</label>
                    <div class="col-sm-5">
                        <input type="password" class="form-control" name="password" id="password" />
                        <span id="passwordInfo" class="help-block">Database Privileged Password.</span>
                    </div>
                </div>
        
                <div class="form-group">
                	<div class="col-sm-5 control-label"></div>
                    <div class="col-sm-5">
                        <?php
                        $url = getRoot() . '/installer/?step=1';
                        ?>
						<input type="button" class="btn btn-info" value="Back" onclick="window.location.href='<?php echo $url; ?>'" />
                        <input type="submit" class="btn btn-info" name="database" value="Next">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
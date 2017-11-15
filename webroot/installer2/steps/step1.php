<div class="starter-template">
	<h1>Welcome to OpenEMIS School</h1>
	<div class="row">
		<div class="col-md-10 col-md-offset-1">
			<p class="lead">
				OPENEMIS SCHOOL LICENSE LAST UPDATED ON 2014-01-30<br /><br />
				OpenEMIS SCHOOL<br />
				Open School Management Information System
			</p>
			<p style="text-align: justify;">
				Copyright &copy; <?php echo date('Y'); ?> KORD IT. This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or any later version. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program.  If not, see <a href="http://www.gnu.org/licenses/">GNU</a>.  For more information please wire to <a href="mailto:contact@openemis.org">contact@openemis.org</a>.
            </p>
        </div>
    </div>
    <p>By clicking Next, you agree to the terms stated in the <strong>OpenEmis School License Agreement</strong> above.</p>
    <?php
    $url = getRoot() . '/installer/?step=2';
    $_SESSION['error'] = '';
    ?>
    <a href="<?php echo $url; ?>" class="btn btn-info">Next</a>
</div>

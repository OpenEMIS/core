<div class="starter-template">
	<h1>Database setup</h1>
	<p>We have all information we need and we are ready to go. <br />Before continue, back up your database if you need as existing data might be lost.</p>

<div class="row">
	<div class="col-md-12">

		<?php
            echo $this->Form->create($databaseCreation, ['class' => 'form-horizontal']);
        ?>
        <div class="form-group">
        <?php
            echo $this->Form->input('database_name', ['label' => ['class' => 'col-sm-5 control-label'], 'class' => 'form-control', 'value' => 'oe_school']);
        ?>
        </div>
        <div class="form-group">
        <?php
            echo $this->Form->input('database_login', ['label' => ['class' => 'col-sm-5 control-label'], 'class' => 'form-control', 'value' => 'admin']);
        ?>
        </div>
        <div class="form-group">
        <?php
            echo $this->Form->input('database_password', ['label' => ['class' => 'col-sm-5 control-label'], 'class' => 'form-control', 'type' => 'password']);
        ?>
        </div>
        <div class="form-group">
        <?php
            echo $this->Form->input('database_password_confirm', ['label' => ['class' => 'col-sm-5 control-label'], 'class' => 'form-control', 'type' => 'password']);
        ?>
        </div>
        <div class="form-group">
                <div class="col-sm-5 control-label"></div>
                <div class="col-sm-offset-5 col-sm-10">
                <?= $this->Form->button('Back', ['type' => 'button', 'class' => 'btn btn-info', 'onclick' => "window.location.href='step2'"])?>
                <?= $this->Form->button('Next', ['type' => 'submit', 'class' => 'btn btn-success'])?>
                </div>

        </div>
        <?php
            echo $this->Form->end();
        ?>
	</div>
</div>
</div>
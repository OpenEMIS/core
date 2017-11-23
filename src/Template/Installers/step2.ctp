<div class="starter-template">
	<h1>Setting Environment</h1>
	<p>All fields are required and case sensitive.</p>

    <div class="row">
        <div class="col-md-12">
            <?php
                echo $this->Form->create($databaseConnection, ['class' => 'form-horizontal']);
            ?>
            <div class="form-group">
            <?php
                echo $this->Form->input('database_server_host', ['label' => ['class' => 'col-sm-5 control-label'], 'class' => 'form-control', 'value' => 'localhost']);
            ?>
            </div>
            <div class="form-group">
            <?php
                echo $this->Form->input('database_server_port', ['label' => ['class' => 'col-sm-5 control-label'], 'class' => 'form-control', 'value' => '3306']);
            ?>
            </div>
            <div class="form-group">
            <?php
                echo $this->Form->input('admin_user', ['label' => ['class' => 'col-sm-5 control-label'], 'class' => 'form-control', 'value' => 'root']);
            ?>
            </div>
            <div class="form-group">
            <?php
                echo $this->Form->input('admin_password', ['label' => ['class' => 'col-sm-5 control-label'], 'class' => 'form-control', 'type' => 'password']);
            ?>
            </div>
            <div class="form-group">
                    <div class="col-sm-5 control-label"></div>
                    <div class="col-sm-5">
                    <?= $this->Form->button('Back', ['type' => 'button', 'class' => 'btn btn-info', 'onclick' => "window.location.href='step1'"])?>
                    <?= $this->Form->button('Next', ['type' => 'submit', 'class' => 'btn btn-success'])?>
                    </div>

            </div>
            <?php
                echo $this->Form->end();
            ?>
        </div>
    </div>
</div>
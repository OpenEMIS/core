<?php
$template = $this->ControllerAction->getFormTemplate();
$this->Form->templates($template);
echo $this->ControllerAction->getEditElements($data);

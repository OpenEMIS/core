<?php if (isset($filters)) : ?>

<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
        <?php
        $template = $this->Page->getFormTemplate();
        $this->Form->templates($template);
        $request = $this->request;

        foreach ($filters as $name => $filter) {
            $default = $this->Page->getQueryString($name) !== false ? $this->Page->getQueryString($name) : $filter['value'];
            echo $this->Form->input($name, array(
                'class' => 'form-control',
                'label' => false,
                'options' => $filter['options'],
                'default' => $default,
                'onchange' => "Page.querystring('$name', this.value)"
            ));
        }
        ?>
    </div>
</div>

<?php endif ?>

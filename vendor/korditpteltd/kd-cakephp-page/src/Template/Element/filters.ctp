<?php if (isset($filters)) : ?>

<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
        <?php
        $template = $this->Page->getFormTemplate();
        $this->Form->templates($template);
        $request = $this->request;

        foreach ($filters as $name => $filter) {
            $default = $this->Page->getQueryString($name) !== false ? $this->Page->getQueryString($name) : $filter['defaultOption'];
            echo $this->Form->input($name, array(
                'class' => 'form-control',
                'label' => false,
                'options' => $filter['options'],
                'default' => $default,
                'onchange' => "Page.querystring('$name', this.value)"
            ));
        }

        // $baseUrl = $this->Url->build([
        //     'plugin' => $this->request->params['plugin'],
        //     'controller' => $this->request->params['controller'],
        //     'action' => ' '
        // ]);


        // echo $this->Form->input('field_option', array(
        //     'class' => 'form-control',
        //     'label' => false,
        //     'options' => $fieldOptions,
        //     'default' => $selectedOption,
        //     'url' => $baseUrl
        // ));

        // if (!empty($parentFieldOptions)) {
        //     $baseUrl = trim($baseUrl) . $this->request->params['action'];
        //     echo $this->Form->input('parent_field_option', array(
        //         'class' => 'form-control',
        //         'label' => false,
        //         'options' => $parentFieldOptions,
        //         'default' => $selectedParentFieldOption,
        //         'url' => $baseUrl,
        //         'data-named-key' => 'parent_field_option_id'
        //     ));
        // }
        ?>
    </div>
</div>

<?php endif ?>

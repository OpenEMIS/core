
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                $baseUrl = $this->Url->build($baseUrl);
                $template = $this->ControllerAction->getFormTemplate();
                $this->Form->templates($template);

                if (!empty($itemOptions)) {
                    echo $this->Form->input('items', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $itemOptions,
                        'default' => $selectedItem,
                        'url' => $baseUrl,
                        'data-named-key' => 'item'
                    ));
                }
            ?>
        </div>
    </div>
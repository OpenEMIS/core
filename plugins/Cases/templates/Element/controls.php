<?php if (!empty($featureOptions)) :
    if($this->request->getParam('controller') == "Profiles" && $this->request->getParam('action') == "Cases"){

    }
    else{?>

    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                $baseUrl = $this->Url->build([
                    'plugin' => $this->request->getParam('plugin'),
                    'controller' => $this->request->getParam('controller'),
                    'action' => $this->request->getParam('action'),
                    '0' => 'index',
                    '1' => $encodedQueryString,
                ]);
                $template = $this->ControllerAction->getFormTemplate();
                $this->Form->templates($template);

                if (!empty($featureOptions)) {
                    echo $this->Form->input('feature', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $featureOptions,
                        'default' => $selectedFeature,
                        'url' => $baseUrl,
                        'data-named-key' => 'feature'
                    ));
                }
            ?>
        </div>
    </div>
<?php } endif ?>

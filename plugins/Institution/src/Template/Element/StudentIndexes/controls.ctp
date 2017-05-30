<?php if (!empty($classOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                $url = [
                    'plugin' => $this->request->params['plugin'],
                    'controller' => $this->request->params['controller'],
                    'action' => $this->request->params['action']
                ];

                if (!empty($this->request->pass)) {
                    $url = array_merge($url, $this->request->pass);
                }

                $dataNamedGroup = [];
                if (!empty($this->request->query)) {
                    foreach ($this->request->query as $key => $value) {
                        if (in_array($key, ['class_id']))
                            continue;

                        echo $this->Form->hidden($key, [
                            'value' => $value,
                            'data-named-key' => $key
                        ]);

                        $dataNamedGroup[] = $key;
                    }
                }

                $baseUrl = $this->Url->build($url);

                $template = $this->ControllerAction->getFormTemplate();
                $this->Form->templates($template);

                if (!empty($classOptions)) {
                    $inputOptions = [
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $classOptions,
                        'url' => $baseUrl,
                        'data-named-key' => 'class_id'
                    ];

                    if (!empty($dataNamedGroup)) {
                        $inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
                        $dataNamedGroup[] = 'class_id';
                    }

                    echo $this->Form->input('class', $inputOptions);
                }
            ?>
        </div>
    </div>
<?php endif ?>

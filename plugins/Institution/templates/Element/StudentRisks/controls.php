<?php if (!empty($classOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                $url = [
                    'plugin' => $this->request->getParam('plugin'),
                    'controller' => $this->request->getParam('controller'),
                    'action' => $this->request->getParam('action'),
                    '0' => 'index',
                    '1' => $encodedQueryString,
                ];

                if (!empty($this->request->getParam('pass'))) {
                    $url = array_merge($url, $this->request->getParam('pass'));
                }

                $dataNamedGroup = [];
                if (!empty($this->request->getQuery())) {
                    foreach ($this->request->getQuery() as $key => $value) {
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

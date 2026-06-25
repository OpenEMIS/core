<?php 
if (!empty($filterOptions) || !empty($categoryOptions) ||  !empty($areaOptions) || !empty($periodsOptions) || !empty($monthOptions) ) :  ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php 
                if($this->request->getParam('controller') == 'Scholarships'){
                    $url = [
                        'plugin' => $this->request->getParam('plugin'),
                        'controller' => $this->request->getParam('controller'),
                        'action' => $this->request->getParam('action'),
                        'queryString' => $this->request->getQuery('queryString')
                    ];
                }else{
                    $url = [
                        'plugin' => $this->request->getParam('plugin'),
                        'controller' => $this->request->getParam('controller'),
                        'action' => $this->request->getParam('action')
                    ];
                }
                
                if (!empty($this->request->getParam('pass'))) {
                    $url = array_merge($url, $this->request->getParam('pass'));
                }

                // Hidden fields + data-named-group: JS (app.js change) re-appends these on toolbar select change.
                // Only "filter" was preserved (POCOR-5695), so institution_id / status / etc. were dropped from the URL
                // and school-based screens (e.g. Institution Surveys) lost context — filters looked broken.
                $dataNamedGroup = [];
                $toolbarSelectKeys = ['filter', 'category', 'level', 'area', 'period', 'month'];
                if (!empty($this->request->getQuery())) {
                    foreach ($this->request->getQuery() as $key => $value) {
                        if (in_array($key, $toolbarSelectKeys, true)) {
                            continue;
                        }
                        if (is_array($value)) {
                            continue;
                        }
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

                // Keys of visible toolbar <select>s (app.js change() re-reads each by data-named-key).
                // Each select's data-named-group must list every OTHER toolbar key + preserved query hiddens,
                // or changing category drops filter (and vice versa) when $dataNamedGroup was empty.
                $toolbarCrossKeys = [];
                if (!empty($filterOptions)) {
                    $toolbarCrossKeys[] = 'filter';
                }
                if (!empty($categoryOptions)) {
                    $toolbarCrossKeys[] = 'category';
                }
                if ($this->request->getParam('action') == 'Sessions' || $this->request->getParam('action') == 'Results') {
                    if (!empty($levelOptions)) {
                        $toolbarCrossKeys[] = 'level';
                    }
                    if (!empty($areaOptions)) {
                        $toolbarCrossKeys[] = 'area';
                    }
                    if (!empty($periodsOptions)) {
                        $toolbarCrossKeys[] = 'period';
                    }
                    if (!empty($monthOptions)) {
                        $toolbarCrossKeys[] = 'month';
                    }
                }
                $toolbarCrossGroupFor = function ($omitKey) use ($dataNamedGroup, $toolbarCrossKeys) {
                    $others = array_values(array_diff($toolbarCrossKeys, [$omitKey]));
                    $merged = array_merge($dataNamedGroup, $others);

                    return implode(',', array_unique($merged));
                };

                if (!empty($filterOptions)) {
                    $group = $toolbarCrossGroupFor('filter');
                    $inputOptions = [
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $filterOptions,
                        'url' => $baseUrl,
                        'data-named-key' => 'filter',
                    ];
                    if ($group !== '') {
                        $inputOptions['data-named-group'] = $group;
                    }
                    echo $this->Form->input('filter', $inputOptions);
                }

                if (!empty($categoryOptions)) {
                    $group = $toolbarCrossGroupFor('category');
                    $inputOptions = [
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $categoryOptions,
                        'url' => $baseUrl,
                        'data-named-key' => 'category',
                        'escape' => false
                    ];
                    if ($group !== '') {
                        $inputOptions['data-named-group'] = $group;
                    }
                    echo $this->Form->input('category', $inputOptions);
                }
                //POCOR-5695 starts
                if($this->request->getParam('action') == 'Sessions' || $this->request->getParam('action') == 'Results'){
                    if (!empty($levelOptions)) {
                        $group = $toolbarCrossGroupFor('level');
                        $inputOptions = [
                            'class' => 'form-control',
                            'label' => false,
                            'options' => $levelOptions,
                            'url' => $baseUrl,
                            'data-named-key' => 'level',
                            'escape' => false
                        ];
                        if ($group !== '') {
                            $inputOptions['data-named-group'] = $group;
                        }
                        echo $this->Form->input('level', $inputOptions);
                    }
                    if (!empty($areaOptions)) {
                        $group = $toolbarCrossGroupFor('area');
                        $inputOptions = [
                            'class' => 'form-control',
                            'label' => false,
                            'options' => $areaOptions,
                            'url' => $baseUrl,
                            'data-named-key' => 'area',
                            'escape' => false
                        ];
                        if ($group !== '') {
                            $inputOptions['data-named-group'] = $group;
                        }
                        echo $this->Form->input('area', $inputOptions);
                    }

                    if (!empty($periodsOptions)) {
                        $group = $toolbarCrossGroupFor('period');
                        $inputOptions = [
                            'class' => 'form-control',
                            'label' => false,
                            'options' => $periodsOptions,
                            'url' => $baseUrl,
                            'data-named-key' => 'period',
                            'escape' => false
                        ];
                        if ($group !== '') {
                            $inputOptions['data-named-group'] = $group;
                        }
                        echo $this->Form->input('period', $inputOptions);
                    }

                    if (!empty($monthOptions)) {
                        $group = $toolbarCrossGroupFor('month');
                        $inputOptions = [
                            'class' => 'form-control',
                            'label' => false,
                            'options' => $monthOptions,
                            'url' => $baseUrl,
                            'data-named-key' => 'month',
                            'escape' => false
                        ];
                        if ($group !== '') {
                            $inputOptions['data-named-group'] = $group;
                        }
                        echo $this->Form->input('month', $inputOptions);
                    }
                }
                //POCOR-5695 ends
            ?>
        </div>
    </div>
<?php endif ?>

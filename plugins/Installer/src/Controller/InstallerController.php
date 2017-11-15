<?php
namespace Installer\Controller;

class InstallerController extends AppController
{
    public $helpers = [
        'OpenEmis.Resource'
    ];

    public function initialize()
    {
        $this->set('SystemVersion', '1.0.0');
    }

    public function index()
    {
        $this->viewBuilder()->layout('Installer.default');
    }
}

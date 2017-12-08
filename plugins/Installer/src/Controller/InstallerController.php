<?php
namespace Installer\Controller;

require CONFIG . 'snapshot_config.php';
use Cake\Cache\Cache;
use Exception;
use Installer\Form\DatabaseConnectionForm;
use Installer\Form\SuperAdminCreationForm;
use Migrations\Migrations;
use PDOException;

class InstallerController extends AppController
{
    public $helpers = [
        'OpenEmis.Resource'
    ];

    public function initialize()
    {
        $this->loadComponent('Angular.Angular', [
            'app' => 'OE_Core',
            'modules' => [
                'app.ctrl'
            ]
        ]);

        $this->loadComponent('OpenEmis.OpenEmis', [
            'productName' => 'OpenSMIS',
            'theme' => 'school'
        ]);

        $this->set('SystemVersion', '1.0.0');
        $this->loadComponent('ControllerAction.Alert');
        $this->viewBuilder()->layout('Installer.default');
    }

    public function index()
    {
        if (file_exists(CONFIG . 'datasource.php')) {
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        }
        $action = '1';
        $this->set('action', $action);
    }

    public function step2()
    {
        if (file_exists(CONFIG . 'datasource.php')) {
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        }
        $action = '2';
        $this->set('action', $action);
        $databaseConnection = new DatabaseConnectionForm();
        if ($this->request->is('post') && empty($databaseConnection->errors())) {
            try {
                $execute = $databaseConnection->execute($this->request->data);
                if ($execute) {
                    $this->redirect(['plugin' => 'Installer', 'controller' => 'Installer', 'action' => 'step3']);
                    Cache::clear(false, '_cake_model_');
                }
            } catch (PDOException $e) {
                $this->Alert->error($e->getMessage(), ['type' => 'text']);
            } catch (Exception $e) {
                $this->Alert->error($e->getMessage(), ['type' => 'text']);
            }
        }
        $this->set(compact('databaseConnection'));
        $this->render('index');
    }

    public function step3()
    {
        $session = $this->request->session();
        $migrations = new Migrations();
        $source = 'Snapshot' . DS . VERSION;
        $status = $migrations->status(['source' => $source]);
        if (!file_exists(CONFIG . 'datasource.php')) {
            return $this->redirect(['plugin' => 'Installer', 'controller' => 'Installer', 'action' => 'index']);
        } elseif ($status[0]['status'] == 'down') {
            $migrate = $migrations->migrate(['source' => $source]);
            if ($migrate) {
                $seedSource = 'Snapshot' . DS . VERSION . DS . 'Seeds';
                $seedStatus = $migrations->seed(['source' => $seedSource]);
                if ($seedStatus) {
                    // Applying missed out migrations
                    $migrations->migrate();
                    $session->write('Installer.superAdminCreation', true);
                }
            }
        }

        if (!$session->check('Installer.superAdminCreation')) {
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        }

        $superAdminCreation = new SuperAdminCreationForm();
        if ($this->request->is('post') && empty($superAdminCreation->errors())) {
            $execute = $superAdminCreation->execute($this->request->data);
            if ($execute) {
                $session->delete('Installer.superAdminCreation');
                return $this->redirect(['plugin' => 'Installer', 'controller' => 'Installer', 'action' => 'step4']);
            } else {
                $this->Alert->error('There is an error inserting administrator and country information.', ['type' => 'text']);
            }
        }
        $this->set(compact('superAdminCreation'));
        $action = '3';
        $this->set('action', $action);
        $this->render('index');
    }

    public function step4()
    {
        if (!file_exists(CONFIG . 'datasource.php')) {
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        }
        $action = '4';
        $this->set('action', $action);
        $this->render('index');
    }
}

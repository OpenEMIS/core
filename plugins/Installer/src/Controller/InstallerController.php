<?php
namespace Installer\Controller;

use Exception;
use Installer\Form\DatabaseConnectionForm;
use PDOException;
use Cake\Core\Configure;
use Cake\Log\Log;

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

        $theme = 'core';
        if (Configure::read('installerSchool')) {
            $theme = 'school';
        }

        $this->loadComponent('RequestHandler');
        $this->loadComponent('OpenEmis.OpenEmis', [
            'productName' => Configure::read('productName'),
            'theme' => $theme
        ]);

        $this->set('SystemVersion', '1.0.0');
        $this->set('productName', Configure::read('productName'));
        $this->set('productLongName', Configure::read('productLongName'));
        $this->loadComponent('ControllerAction.Alert');
        $this->viewBuilder()->layout('Installer.default');
    }

    public function index()
    {
        if (file_exists(CONFIG . 'datasource.php')) {
            if ($this->request->param('_ext') != 'json') {
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
            } else {
                $this->set('code', 422);
                $this->set('message', 'Datasource has already been created');
                $this->response->statusCode(422);
            }
        }

        $this->set('code', 200);
        $this->set('message', 'OK');
        $this->set('_serialize', ['message', 'code']);
        $action = '1';
        $this->set('action', $action);
    }

    public function step2()
    {
        if (file_exists(CONFIG . 'datasource.php')) {
            if ($this->request->param('_ext') != 'json') {
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
            } else {
                $this->set('code', 422);
                $this->set('message', 'Datasource has already been created');
                $this->response->statusCode(422);
                $this->set('_serialize', ['message', 'code']);
                return null;
            }
        }
        $action = '2';
        $this->set('action', $action);
        $databaseConnection = new DatabaseConnectionForm();
        if ($this->request->is('post') && empty($databaseConnection->errors())) {
            try {
                $execute = $databaseConnection->execute($this->request->data);
                if ($execute) {
                    if ($this->request->param('_ext') != 'json') {
                        $this->redirect(['plugin' => 'Installer', 'controller' => 'Installer', 'action' => 'step3']);
                    } else {
                        $this->set('code', 200);
                        $this->set('message', 'OK');
                        $this->response->statusCode(200);
                    }
                }
            } catch (PDOException $e) {
                $this->Alert->error($e->getMessage(), ['type' => 'text']);
                $this->set('code', 500);
                $this->set('message', 'PDOException');
                $this->response->statusCode(500);
            } catch (Exception $e) {
                if (file_exists(CONFIG . 'datasource.php')) {
                    if ($this->request->param('_ext') != 'json') {
                        return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
                    } else {
                        $this->set('code', 422);
                        $this->set('message', 'Datasource has already been created');
                        $this->response->statusCode(422);
                        $this->set('_serialize', ['message', 'code']);
                        return null;
                    }
                }
                $this->Alert->error($e->getMessage(), ['type' => 'text']);
                $this->set('code', 500);
                $this->set('message', 'An unknown exception occur');
                $this->response->statusCode(500);
            }
        } elseif ($this->request->param('_ext') == 'json') {
            $this->set('code', 422);
            $this->set('message', 'Form error, please check the fields');
            $this->response->statusCode(422);
        } else {
            $this->set('code', 200);
            $this->set('message', 'OK');
            $this->response->statusCode(200);
        }
        $this->set(compact('databaseConnection'));
        $this->set('_serialize', ['message', 'code']);
        $this->render('index');
    }

    public function step3()
    {
        if (!file_exists(CONFIG . 'datasource.php')) {
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        }
        $action = '3';
        $this->set('action', $action);
        $this->set('code', 200);
        $this->set('message', 'OK');
        $this->set('_serialize', ['message', 'code']);
        $this->response->statusCode(200);
        $this->render('index');
    }
}

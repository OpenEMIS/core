<?php
namespace Installer\Controller;

use Exception;
use Installer\Form\DatabaseConnectionForm;
use PDOException;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Http\ServerRequest;
use Cake\Http\Response;
require CONFIG . 'installer_mode_config.php';

class InstallerController extends AppController
{
    public $helpers = [
        'OpenEmis.Resource'
    ];

    public function initialize(): void
    {
        $this->loadComponent('Angular.Angular', [
            'app' => 'OE_Core',
            'modules' => [
                'app.ctrl'
            ]
        ]);

        $theme = APPLICATION_THEME;
        // if (Configure::read('installerSchool')) {
        //     $theme = 'school';
        // }

        $this->loadComponent('RequestHandler');
        $this->loadComponent('OpenEmis.OpenEmis', [
            'productName' => APPLICATION_NAME,
            'theme' => APPLICATION_THEME
        ]);

        $this->set('SystemVersion', '1.0.0');
        // $this->set('productName', Configure::read('productName'));
        $this->set('productName', APPLICATION_NAME);
        $this->set('productLongName', Configure::read('productLongName'));
        $this->loadComponent('ControllerAction.Alert');
        //$this->viewBuilder()->layout('Installer.default');
        $this->viewBuilder()->setLayout('Installer.default');
    }

    public function index()
    {
       // $request = new ServerRequest();
       // print($this->request->getParam());die;
        if (file_exists(CONFIG . 'app_local.php')) {//POCOR-8308
            if ($this->request->getParam('_ext') != 'json') {
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
            } else {
                $this->set('code', 422);
                $this->set('message', 'Datasource has already been created');
                $this->response->withStatus(422);//POCOR-8308
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
        
        $request = new ServerRequest();
        $response = new Response();
        // echo "<pre>";print_r($response->withStatus());die;
        if (file_exists(CONFIG . 'app_local.php')) {//POCOR-8308
            if ($this->request->getParam('params')['_ext'] != 'json') {
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
            } else {
                $this->set('code', 422);
                $this->set('message', 'app_local has already been created');//POCOR-8308
                $this->response->withStatus(422);//POCOR-8308
                $this->set('_serialize', ['message', 'code']);
                return null;
            }
        }
        if(DATABASE_DUMP_FILE == ''){
            $this->Alert->error('Database not configured properly.', ['type' => 'text']);
            return null;
        }
        if (APPLICATION_MODE_COUNT > 1) {
            $this->Alert->error('Please select only one mode at one time.', ['type' => 'text']);
           $this->set('code', 422);
         return null;
        }
        $action = '2';
        $this->set('action', $action);
        $databaseConnection = new DatabaseConnectionForm();
        if ($this->request->is('post') && empty($databaseConnection->getErrors())) {//POCOR-8308
        // if ($request->is('get')) {
            try {
                $execute = $databaseConnection->execute($this->request->getData());
                if ($execute) {
                    if ($this->request->getParam('_ext') != 'json') {//POCOR-8308
                        $this->redirect(['plugin' => 'Installer', 'controller' => 'Installer', 'action' => 'step3']);
                    } else {
                        $this->set('code', 200);
                        $this->set('message', 'OK');
                        $this->response->withStatus(200);//POCOR-8308
                    }
                }
            } catch (PDOException $e) {
                Log::error(
                    'PDO exception during installation'.$e->getMessage(),
                    ['message'=> $e->getMessage(),'trace' => $e->getTraceAsString()]
                );   //POCOR-8308    
                if (file_exists(CONFIG . 'app_local.php')){
                    unlink(CONFIG . 'app_local.php');
                }//POCOR-8308      
                $this->Alert->error($e->getMessage(), ['type' => 'text']);
                $this->set('code', 500);
                $this->set('message', 'PDOException');
                $this->response->withStatus(500);//POCOR-8308
            } catch (Exception $e) {
                //POCOR-8308 start
                if (file_exists(CONFIG . 'app_local.php')){
                    unlink(CONFIG . 'app_local.php');
                } 
                if ($e instanceof \mysqli_sql_exception) {
                    Log::error(
                        'Other exception during installation'.$e->getMessage(),
                     ['message'=> $e, 'trace' => $e->getTraceAsString()]
                 );
                    $this->Alert->error($e->getMessage(), ['type' => 'text']);
                    $this->set('code', 500);
                    $this->set('message', 'PDOException');
                    $this->response->withStatus(500);//POCOR-8308
                }
                //POCOR-8308 end 
                else{

            
                if (file_exists(CONFIG . 'app_local.php')) {//POCOR-8308
                  
                    if ($this->request->getParam('_ext') != 'json') {
                        // $this->Alert->error($e->getMessage(), ['type' => 'text']);
                        return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
                    } else {
                       
                        $this->set('code', 422);
                        $this->set('message', 'Datasource has already been created');
                        $this->response->withStatus(422);//POCOR-8308
                        $this->set('_serialize', ['message', 'code']);
                        return null;
                    }
                }
                
                $this->Alert->error($e->getMessage(), ['type' => 'text']);
                $this->set('code', 500);
                $this->set('message', 'An unknown exception occur');
                $this->response->withStatus(500);
            }
            }
        } elseif($this->request->getParam('_ext') == 'json')  {//POCOR-8308
           
            $this->set('code', 422);
            $this->set('message', 'Form error, please check the fields');
            $this->response->withStatus(422);//POCOR-8308
        } else {
          
            $this->set('code', 200);
            $this->set('message', 'OK');
            // $this->response->withStatus(200);
            $response->withStatus(200);//POCOR-8308
        }
        $this->set(compact('databaseConnection'));
        $this->set('_serialize', ['message', 'code']);
        $this->render('index');
    }

    public function step3()
    {
        if (!file_exists(CONFIG . 'app_local.php')) {//POCOR-8308
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        }
        $action = '3';
        $this->set('action', $action);
        $this->set('code', 200);
        $this->set('message', 'OK');
        $this->set('_serialize', ['message', 'code']);
        $this->response->withStatus(200);//POCOR-8308
        $this->render('index');
    }
}

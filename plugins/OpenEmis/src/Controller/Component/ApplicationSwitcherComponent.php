<?php
namespace OpenEmis\Controller\Component;

use Cake\Controller\Component;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use Cake\Event\Event;
use Cake\I18n\Time;

class ApplicationSwitcherComponent extends Component {

    private $controller;
    private $productName;

    public function initialize(array $config) {
        $this->productName = $config['productName'];
        $this->controller = $this->_registry->getController();
    }

    public function startup(Event $event) {
        $controller = $this->controller;
        $displayProducts = $this->onUpdateProductList();
        $controller->set('products', $displayProducts);
        $controller->set('showProductList', !empty($displayProducts));
    }

    public function onUpdateProductList()
    {
        $displayProducts = [];
        $session = $this->request->session();
        if (!$session->check('ConfigProductLists.list')) {
            // Change to generic table registry to read from config_product_lists table
            $ConfigProductLists = TableRegistry::get('ConfigProductLists');
            $productListOptions = $ConfigProductLists->find()
                ->select([
                    $ConfigProductLists->aliasField('name'),
                    $ConfigProductLists->aliasField('url'),
                    $ConfigProductLists->aliasField('file_name'),
                    $ConfigProductLists->aliasField('file_content')
                ])
                ->hydrate(false)
                ->toArray();

            $dir = new Folder(WWW_ROOT . 'img' . DS . 'product_list_logo', true);
            $filesAndFolders = $dir->read();
            $files = $filesAndFolders[1];

            foreach ($productListOptions as $product) {
                $name = $product['name'];
                if (!empty($product['url'])) {
                    $icon = 'kd-openemis';
                    $imagePath = '';
                    if (!empty($product['file_name'])) {
                        $imagePath = WWW_ROOT . 'img' . DS . 'product_list_logo' . DS . $product['file_name'];
                    }

                    if (!empty($product['file_name']) && !empty($product['file_content'])) {
                        if (!in_array($product['file_name'], $files)) {
                            $newImage = new File($imagePath, true);
                            $status = $newImage->write(stream_get_contents($product['file_content']));
                            if ($status) {
                                $icon = '';
                            } else {
                                $newImage->delete();
                            }
                        } else {
                            $icon = '';
                        }
                    }

                    $displayProducts[$name] = [
                        'name' => $name,
                        'icon' => $icon,
                        'file_name' => $product['file_name'],
                        'url' => $product['url']
                    ];
                }
            }
            $session->write('ConfigProductLists.list', $displayProducts);
        } else {
            $displayProducts = $session->read('ConfigProductLists.list');
        }

        return $displayProducts;
    }
}

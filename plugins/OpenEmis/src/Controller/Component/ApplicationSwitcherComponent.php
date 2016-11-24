<?php
namespace OpenEmis\Controller\Component;

use Cake\Controller\Component;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use OpenEmis\Model\Traits\ProductListsTrait;
use Cake\Event\Event;

class ApplicationSwitcherComponent extends Component {
    use ProductListsTrait;

    private $controller;

    public function initialize(array $config) {
        $this->controller = $this->_registry->getController();
    }

    public function startup(Event $event) {
        $productList = $this->productList;
        $controller = $this->controller;
        $displayProducts = $this->onUpdateProductList($productList);
        $controller->set('products', $displayProducts);
        $controller->set('showProductList', !empty($displayProducts));
    }

    public function onUpdateProductList(array $productList)
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
                ->toArray();

            $productListData = array_flip(array_column($productListOptions, 'name'));
            $productListData[$this->_productName] = '';
            $productLists = array_diff_key($productList, $productListData);
            foreach ($productLists as $product => $value) {
                $data = [
                    'name' => $product,
                    'url' => '',
                    'deletable' => 0,
                    'created_user_id' => 1
                ];
                $entity = $ConfigProductLists->newEntity($data);
                $ConfigProductLists->save($entity);
            }

            $dir = new Folder(WWW_ROOT . 'img' . DS . 'product_list_logo', true);
            $filesAndFolders = $dir->read();
            $files = $filesAndFolders[1];

            foreach ($productListOptions as $product) {
                $name = $product['name'];
                if (!empty($product['url'])) {
                    if (isset($productList[$name])) {
                        $displayProducts[$name] = [
                            'name' => $productList[$name]['name'],
                            'icon' => $productList[$name]['icon'],
                            'url' => $product['url']
                        ];
                    } else {
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
            }
            $session->write('ConfigProductLists.list', $displayProducts);
        } else {
            $displayProducts = $session->read('ConfigProductLists.list');
        }

        return $displayProducts;
    }
}

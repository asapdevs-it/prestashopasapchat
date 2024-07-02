<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}
error_reporting(error_reporting() & ~E_NOTICE);

class Prestashopasapchat extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'prestashopasapchat';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'asapchat.io';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('asapchat.io');
        $this->description = $this->l('Plugin for asapchat.io');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('PRESTASHOPasapchat_LIVE_MODE', false);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionAttributePostProcess') &&
            $this->registerHook('moduleRoutes');
    }

    public function prepare_order_for_response($order){
        $order = (object) $order;
        // var_dump($order);
        $idlang = intval($order->id_lang);
        $order_id = isset($order->id) ? $order->id : $order->id_order;

        $delivery_details = new Address((int)($order->id_address_delivery));
        $customer = new Customer((int)($delivery_details->id_customer));
        $addressInvoice = new Address(intval($order->id_address_invoice));
        $carrier = new Carrier(intval($order->id_carrier), $idlang);
        $total = $order->total_paid;
        $is_paid = $total <= $order->total_paid_real;
        $products_total = $order->total_products;
        $status_obj = new OrderState($order->current_state,$idlang );
        $status = $status_obj->name;
        // ->getOrderStates(intval($order->id_lang));

		$billing_address = $addressInvoice->address1." ".$addressInvoice->address2.", ".$addressInvoice->postcode." ".$addressInvoice->city;
        $delivery_method = $carrier->name;
		$delivery_address = $delivery_details->address1." ".$delivery_details->address2.", ".$delivery_details->postcode." ".$delivery_details->city;
		$product_list = [];
        $ProductDetailObject = new OrderDetail;
        $items = $ProductDetailObject->getList($order_id);

		foreach ($items as $item) {
			// $product = $item->get_product();
            $product_name = $item['product_name'];
			$sku = $item['product_id'];
			// $sku = $sku ? $sku : $item->get_id();
			$qty = $item['product_quantity'];
			$total_price = $item['total_price_tax_incl'];
			$price = number_format(($total_price/$qty), 2);

			$product_list[] =[
				"name"=>$product_name,
				"sku"=>$sku,
				"price"=>$price,
				"amount"=>$qty,
				"total"=>$total_price,
			];
		}


		return [
			"order_number"=>$order_id,
			"is_paid"=> $is_paid,
            "client_name"=>$customer->firstname." ".$customer->lastname,
			"client_email"=>$customer->email,
			"product_list"=>$product_list,
			"total_price"=>$total,
			"current_status"=>$status,            
		//    //  "statuses_history"=><Array>[
		//    //  "name"=><String>,
		//    //  "date"=><Date format "Y-m-d H=>i=>s">,
		//    //  ],
		//    //  "lading_number"=><String>,
			"client_phone"=>$delivery_details->phone ? $delivery_details->phone : $delivery_details->phone_mobile,
			"delivery_address"=>$delivery_address,
			"billing_address"=>$billing_address,
			"delivery_method"=>$delivery_method,
			"payment_method"=>$order->payment,

            // "addressInvoice"=>$addressInvoice, //dd
            // "delivery_details"=>$delivery_details, //dd
            // "customer"=>$customer, //dd
            // "order"=>$order,//dd
            // "items"=>$items
		   ];
	}

	public function get_order_response($order, $type_shop){
		$order_arr = $this->prepare_order_for_response($order);

		return [
			"message"=>"Success",
			"data"=> $order_arr,
			"type"=> $type_shop 
		];
	}

	public function getOneOrderByNumber($order_number){
		// $order = wc_get_order($order_number);
        $order = new Order($order_number);  
		$type_shop = $this->get_type_shop();

		if(!$order) return [
			"message"=>"Error",
			"data"=>"Empty",
			"type"=> $type_shop 
		];
		return $this->get_order_response($order, $type_shop);
	}

	public function getAllOrdersByEmail($email){	
		$type_shop = $this->get_type_shop();
        
        $orders = [];
        $customer_id_sql = Db::getInstance()->getRow('SELECT id_customer FROM '._DB_PREFIX_.'customer WHERE email = "'.pSQL($email).'"');
        $customer_id = $customer_id_sql_first['id_customer'];
        // $customer_id = Customer::customerExists($email, true);
        if ($customer_id) {
            $customer = new Customer($customer_id);
            $orders = Order::getCustomerOrders($customer_id);
        }

		if(!$orders || !count($orders)) return [
			"message"=>"Error",
			"data"=>"Empty",
			"type"=> $type_shop 
		];

		
		$orders_list = [];

		foreach ($orders as $order) {
			$orders_list[] = $this->prepare_order_for_response($order);
		}
		$response = [
			"message"=>"Success",
			"data"=> $orders_list,
			"type"=> $type_shop 
		];
		return $response;
	}

    public function getProducts($productKey){
        $type_shop = $this->get_type_shop();
        $productKey = pSQL($productKey); 
        $id_lang = Context::getContext()->language->id; // Uzyskanie bieżącego języka

        $sql = "
            SELECT p.id_product, pl.name, pl.description, pl.description_short, pl.link_rewrite, IFNULL(SUM(od.product_quantity), 0) AS sale_count
            FROM " . _DB_PREFIX_ . "product p
            LEFT JOIN " . _DB_PREFIX_ . "product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = $id_lang
            LEFT JOIN " . _DB_PREFIX_ . "order_detail od ON p.id_product = od.product_id
            WHERE (pl.name LIKE '%$productKey%'
            OR pl.description LIKE '%$productKey%'
            OR pl.description_short LIKE '%$productKey%'
            OR pl.link_rewrite LIKE '%$productKey%')
            AND p.active = 1
            GROUP BY p.id_product
            ORDER BY sale_count DESC
            LIMIT 10";

        $products = Db::getInstance()->executeS($sql);
        if(!$products || !count($products)) return [
            "message"=>"Error",
            "data"=>"Empty",
            "type"=> $type_shop 
        ];

        $products_list = [];
        $shopurl = Configuration::get('PS_SHOP_DOMAIN');
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $https = Configuration::get('PS_SSL_ENABLED');
        $shopurl = ($https ? 'https://' : 'http://').$shopurl;
        $link = new Link();

        foreach ($products as $product) {
            $id_product = $product['id_product'];
            $getProduct = new Product($id_product, false, $id_lang);
            // $link = new Link();
            // $url = $link->getProductLink($getProduct);
            $url = $shopurl."/index.php?controller=product&id_product=".$id_product."&id_lang=".$id_lang;

            $skuorean = $getProduct->reference;
            $productTags = Tag::getProductTags($id_product);
            $categories = Product::getProductCategoriesFull($id_product);
            $categories = array_map(function($cat){
                return $cat['name'];
            }, $categories);
            $attributes = $getProduct->getAttributesGroups($id_lang);
            $attributes = array_map(function($attr){
                return $attr['name'];
            }, $attributes);

            // $product_url = $link->getProductLink((int)$id_product);
            // $image = Product::getCover((int)$id_product);
            // $image = new Image($image['id_image']);
            // $imagePath = _PS_BASE_URL_._THEME_PROD_DIR_.$image->getExistingImgPath().".jpg";
            $imagePath = "";
            
            $products_list[] = [
                "id"=>$product['id_product'],
                "name"=>$product['name'],
                "sku"=>$skuorean,
                "price"=>$getProduct->price,
                "image"=>$imagePath,
                "link"=>$url,
                "categories"=> $categories,
                "tags"=> $productTags,
                "attributes"=> $attributes,
                "description"=>$product['description'],
                "description_short"=>$product['description_short'],
            ];

        }

        // $product = wc_get_product($productid);
		// 	$products_list[] = [
		// 		"name"=>$product->get_title(),
		// 		"sku"=>$product->get_sku(),
		// 		"price"=>$product->get_price(),
		// 		"link"=>$product->get_permalink(),
		// 		"image"=>$product->get_image(),
		// 		"description"=>$product->get_description(),
		// 		"short_description"=>$product->get_short_description(),
		// 		"categories"=>$product->get_categories(),
		// 		"tags"=>$product->get_tags(),
		// 		"attributes"=>$product->get_attributes(),


        var_dump($products_list);
        exit;

        
        $response = [
            "message"=>"Success",
            "data"=> $products_list,
            "type"=> $type_shop 
        ];
        return $response;
    }

    public function get_type_shop(){
        return "prestashop";
    }
    
    public function get_api_key(){
        // $value = get_option("api_key_sd");
        $value = Configuration::get('PRESTASHOPasapchat_API_SD', 0);
        return $value;
    }
    
    public function returnError($error = false){      
        // $order = new Order(6);  
        // $test = $this->prepare_order_for_response($order);
        // $test = $this->getAllOrdersByEmail("");
        // $test = $this->getOneOrderByNumber(6);
        $response = [
            "message"=>"Error",
            "data"=>$error ? $error : "Brak route/key/mode",
            "type"=>$this->get_type_shop(),
            // "test"=>$test
        ];
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    }
    
    public function retrieveJsonPostData() {
        $rawData = file_get_contents("php://input");
        parse_str($rawData, $result);
        if(isset($_POST['mode']) && $_POST['mode']) return $_POST;
        
        $data = json_decode($rawData) && json_decode($rawData)->mode ? json_decode($rawData) : $result;
        return (array) $data;
    }

    public function checkConnection($isAuth, $shop_type){
        return [
            "message"=>$isAuth ? "Success" : "Error",
            "data"=>$isAuth ? "Aktywacja prawidłowa" : "Błąd klucza sklepu",
            "type"=>$shop_type
        ];
    }

    public function check_auth($keyBearer, $keySD){
        $isAuth = $keyBearer === $keySD;
        return $isAuth;
    }


    public function hookModuleRoutes($params) {
        $request = $_SERVER['REQUEST_URI'];
        $isRouteSd = strpos( $request, 'asapchat/api' ) !== false;
        if(!$isRouteSd) return;

        $method = $_SERVER['REQUEST_METHOD'];
	    $headers = getallheaders(); 
        $response = [];

        $keyBearer = trim(isset($headers['Authorization']) ? str_replace("Bearer", "", $headers['Authorization']) : "");
        $data = (array) $this->retrieveJsonPostData();
        $mode = isset($data["mode"]) ? $data["mode"] : (isset($_GET["mode"]) ? $_GET["mode"] : false);
        $email = isset($data["email"]) ? $data["email"] : (isset($_GET["email"]) ? $_GET["email"] : false);
        $productKey = isset($data["productKey"]) ? $data["productKey"] : (isset($_GET["productKey"]) ? $_GET["productKey"] : false);

        $order_number = isset($data["order_number"]) ? $data["order_number"] : (isset($_GET["order_number"]) ? $_GET["order_number"] : false);
        $isDebug = isset($data["debug"]) ? $data["debug"] : (isset($_GET["debug"]) ? $_GET["debug"] : false);

        if($isDebug){
            $keySDpresta = $this->get_api_key();
            $isAuth = $this->check_auth($keyBearer, $keySDpresta);

            $response = [
                "method"=> $method,
                "post"=>$_POST,
                "raw"=> $rawData = file_get_contents("php://input"),
                "data"=>$data,
                "headers"=>$headers,
                "mode"=>$mode,
                "email"=>$email,
                "order_number"=>$order_number,
                "keyBearer"=>$keyBearer,
                "keySDpresta"=>$keySDpresta,
                "isAuth"=>$isAuth,
            ];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
            exit;
        }
   
    
        if(!$keyBearer || !$mode) return $this->returnError();
    
        $keySDpresta = $this->get_api_key();
        $isAuth = $this->check_auth($keyBearer, $keySDpresta);
        $shop_type = $this->get_type_shop();

     
    
 
        if(!$isAuth) return $this->returnError("Błąd autoryzacji");

        switch ($mode) {
            case 'checkConnection':
                $response = $this->checkConnection($isAuth, $shop_type);
                break;
    
            case 'getOneOrderByNumber':
                $response = $this->getOneOrderByNumber($order_number);
            break;
    
            case 'getAllOrdersByEmail':
                $response = $this->getAllOrdersByEmail($email);
            break;

            case 'getProducts':
                $response = $this->getProducts($productKey);
            break;
            
            default:
                # code...
                break;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;

        //metoda z hookiem nie dzialala
        // return array(
        //     'module-prestashopasapchat-asapchatApi' => array( 
        //         'controller' => 'asapchatApi', //front controller name
        //         'rule' => '/asapchat/api', //the desired page URL
        //         'keywords' => array(
        //         ),
        //         'params' => array(
        //             'fc' => 'module',
        //             'module' => 'prestashopasapchat', //module name
        //             'controller' => 'asapchatApi'
        //         )
        //     ),
        // );
    }


    public function uninstall()
    {
        Configuration::deleteByName('PRESTASHOPasapchat_LIVE_MODE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitPrestashopasapchatModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('formsd',  $this->renderForm());

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        return $output;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPrestashopasapchatModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Ustawienia asapchat.io'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    // array(
                    //     'type' => 'switch',
                    //     'label' => $this->l('Live mode'),
                    //     'name' => 'PRESTASHOPasapchat_LIVE_MODE',
                    //     'is_bool' => true,
                    //     'desc' => $this->l('Use this module in live mode'),
                    //     'values' => array(
                    //         array(
                    //             'id' => 'active_on',
                    //             'value' => true,
                    //             'label' => $this->l('Enabled')
                    //         ),
                    //         array(
                    //             'id' => 'active_off',
                    //             'value' => false,
                    //             'label' => $this->l('Disabled')
                    //         )
                    //     ),
                    // ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-gear"></i>',
                        'desc' => $this->l('Podaj klucz Baer wygenerowany w asapchat.io'),
                        'name' => 'PRESTASHOPasapchat_API_SD',
                        'label' => $this->l('Klucz Baer asapchat'),
                    ),
                    // array(
                    //     'type' => 'password',
                    //     'name' => 'PRESTASHOPasapchat_ACCOUNT_PASSWORD',
                    //     'label' => $this->l('Password'),
                    // ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'PRESTASHOPasapchat_LIVE_MODE' => Configuration::get('PRESTASHOPasapchat_LIVE_MODE', true),
            'PRESTASHOPasapchat_API_SD' => Configuration::get('PRESTASHOPasapchat_API_SD', 'contact@prestashop.com'),
            'PRESTASHOPasapchat_ACCOUNT_PASSWORD' => Configuration::get('PRESTASHOPasapchat_ACCOUNT_PASSWORD', null),
            'PRESTASHOPasapchat_API_KEY' => Configuration::get('PRESTASHOPasapchat_API_KEY', "przykladowykey"),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookActionAttributePostProcess()
    {
        /* Place your code here. */
        var_dump("tu");
    }
}
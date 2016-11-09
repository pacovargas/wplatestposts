<?php
/**
* 2007-2015 PrestaShop
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
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Wp_latest_posts extends Module
{
    protected $config_form = false;
    private $errores = "";
    private $hayErrores = FALSE;
    private $posts = array();

    public function __construct()
    {
        $this->name = 'wp_latest_posts';
        $this->tab = 'content_management';
        $this->version = '1.0.0';
        $this->author = 'Sauz Web Solutions';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('WP Latest Posts');
        $this->description = $this->l('Show Wordpress latest posts in home');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('WP_LATEST_POSTS_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        Configuration::deleteByName('WP_LATEST_POSTS_LIVE_MODE');

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
        if (((bool)Tools::isSubmit('submitWp_latest_postsModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
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
        $helper->submit_action = 'submitWp_latest_postsModule';
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
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-database"></i>',
                        'desc' => $this->l('Wordpress Database server'),
                        'name' => 'WP_LATEST_POSTS_DB_SERVER',
                        'label' => $this->l('Wordpress database host'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-table"></i>',
                        'desc' => $this->l('Wordpress Database name'),
                        'name' => 'WP_LATEST_POSTS_DB_NAME',
                        'label' => $this->l('Wordpress database name'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-user"></i>',
                        'desc' => $this->l('Wordpress Database username'),
                        'name' => 'WP_LATEST_POSTS_DB_USERNAME',
                        'label' => $this->l('Wordpress database username'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'password',
                        'prefix' => '<i class="icon icon-database"></i>',
                        'desc' => $this->l('Wordpress Database password'),
                        'name' => 'WP_LATEST_POSTS_DB_PASSWORD',
                        'label' => $this->l('Wordpress database password'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Wordpress Database prefix (underscore included if needed)'),
                        'name' => 'WP_LATEST_POSTS_DB_PREFIX',
                        'label' => $this->l('Wordpress database prefix'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Number of posts per row'),
                        'name' => 'WP_LATEST_POSTS_POSTS_PER_ROW',
                        'label' => $this->l('Posts per row'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Number of rows'),
                        'name' => 'WP_LATEST_POSTS_ROWS',
                        'label' => $this->l('Number of rows'),
                    ),
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
            'WP_LATEST_POSTS_DB_SERVER' => Configuration::get('WP_LATEST_POSTS_DB_SERVER', null),
            'WP_LATEST_POSTS_DB_NAME' => Configuration::get('WP_LATEST_POSTS_DB_NAME', null),
            'WP_LATEST_POSTS_DB_USERNAME' => Configuration::get('WP_LATEST_POSTS_DB_USERNAME', null),
            'WP_LATEST_POSTS_DB_PASSWORD' => Configuration::get('WP_LATEST_POSTS_DB_PASSWORD', null),
            'WP_LATEST_POSTS_DB_PREFIX' => Configuration::get('WP_LATEST_POSTS_DB_PREFIX', null),
            'WP_LATEST_POSTS_POSTS_PER_ROW' => Configuration::get('WP_LATEST_POSTS_POSTS_PER_ROW', null),
            'WP_LATEST_POSTS_ROWS' => Configuration::get('WP_LATEST_POSTS_ROWS', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            if($key == "WP_LATEST_POSTS_DB_PASSWORD"){
                if(Tools::getValue($key) != ''){
                    Configuration::updateValue($key, Tools::getValue($key));
                }
            }
            else{
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            // $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        // $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/wp_latest_posts.css');
    }

    public function hookDisplayHome()
    {
        if(!$db = $this->dbConnect()){
            $this->hayErrores = TRUE;
        }
        else{
            $_wp_prefix = Configuration::get('WP_LATEST_POSTS_DB_PREFIX', null);
            $_posts_nbr = 3;
            $posts_table = $_wp_prefix . "posts";
            $postmeta_table = $_wp_prefix . "postmeta";

            $query = "SELECT p.post_title, p.post_content, p.guid as url, i.guid
            FROM $posts_table AS p
            JOIN $postmeta_table AS m ON p.ID = m.post_id
            AND m.meta_key LIKE '_thumbnail_id'
            AND p.post_type LIKE 'post'
            AND p.post_status LIKE 'publish'
            JOIN $posts_table AS i ON i.ID = m.meta_value
            order by p.ID desc
            limit $_posts_nbr";

            if(!$resultado = $db->query($query)){
                $this->hayErrores = TRUE;
                $this->errores .=  "<li>" . $this->l('No hay posts que mostrar') . ": " . $mysqli->connect_error . "</li>";
            }
            else{
                while ($row = $resultado->fetch_assoc()){
                    $this->posts[] = array(
                        'titulo' => $row['post_title'],
                        'texto' => $row['post_content'],
                        'url' => $row['url'],
                        'img_url' => $row['guid']
                    );
                }
            }
        }

        $this->smarty->assign(array(
            'hay_errores' => $this->hayErrores,
            'errores' => $this->errores,
            'posts' => $this->posts
        ));

        return $this->display(__FILE__, 'wp_latest_posts.tpl');
    }

    private function dbConnect(){
        $host = Configuration::get('WP_LATEST_POSTS_DB_SERVER', null);
        $name = Configuration::get('WP_LATEST_POSTS_DB_NAME', null);
        $username =  Configuration::get('WP_LATEST_POSTS_DB_USERNAME', null);
        $password = Configuration::get('WP_LATEST_POSTS_DB_PASSWORD', null);

        $mysqli = new mysqli($host, $username, $password, $name);
        if ($mysqli->connect_errno) {
            $this->errores .=  "<li>" . $this->l('Fallo al conectar a la base de datos') . ": " . $mysqli->connect_error . "</li>";
            return FALSE;
        }
        else{
            return $mysqli;
        }
    }
}
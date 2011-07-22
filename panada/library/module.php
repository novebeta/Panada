<?php defined('THISPATH') or die('Can\'t access directly!');
/**
 * Module class parent.
 * Every module controller should be child of this class.
 *
 * @package	Panada
 * @subpackage	Library
 * @author	Iskandar Soesman
 * @since	Version 0.3
 */

class Panada_module extends Panada {
    
    static public $_module_name;
    
    public function __construct(){
        
        parent::__construct();
        
        spl_autoload_register(array($this, 'component_loader'));
        spl_autoload_register('__autoload');
        
        $this->base_path = GEAR . 'module/' . self::$_module_name . '/';
    }
    
    public function component_loader($class){
        
        $class      = strtolower($class);
        $file_name  = explode('_', $class);
        $file       = str_ireplace($file_name[0].'_'.$file_name[1].'_', '', $class);
        $file       = GEAR . 'module/'. self::$_module_name . '/' . $file_name[1] . '/' . $file .'.php';
        
        include_once $file;
    }
}
//end Panada_module class


/**
 * Module loader.
 * A class for load a module from main controller.
 *
 * @package	Panada
 * @subpackage	Library
 * @author	Iskandar Soesman
 * @since	Version 0.3
 */
class Library_module {
    
    private $class_inctance;
    private $modile_name;
    
    /**
     * Loader for module
     *
     * @param mix String or array for module configuration
     * @return object
     */
    public function __construct($args = false){
        
        if( ! $args )
            return false;
        
        if( is_string($args) ){
            
            $args = array(
                'name' => $args,
                'controller' => 'home'
            );
        }
        
        $default = array(
            'controller' => 'home'
        );
        
        $module = array_merge($default, $args);
        
        $file = 'module/' . $module['name'] . '/controller/' . $module['controller'];
        
        Panada_module::$_module_name = $module['name'];
        $this->modile_name = $module['name'];
        
        $this->load_file($file);
    }
    
    private function load_file($file_path){
        
        $arr = explode('/', $file_path);
        $file_name = end( $arr );
        
        $prefix = $arr[2];
        $file_path = GEAR . $file_path.'.php';
        
        if( ! file_exists( $file_path ) )
            Library_error::_500('<b>Error:</b> No <b>'.$file_name.'</b> file in '.$arr[0].' folder.');
        
        $class = ucwords($this->modile_name).'_'.$prefix.'_'.$file_name;
        
        include_once $file_path;
        
        if( ! class_exists($class) )
            Library_error::_500('<b>Error:</b> No class <b>'.$class.'</b> exists in file '.$file_name.'.');
        
        $this->class_inctance = new $class;
    }
    
    /**
     * Magic method for calling a method from loaded class
     *
     * @param string Method name
     * @param array Method arguments
     * @return mix Method return value
     */
    public function __call($name, $arguments = array() ){
        
        return call_user_func_array( array($this->class_inctance, $name), $arguments );
    }
    
    /**
     * Magic method for getting a property from loaded class
     *
     * @param string Property name
     * @return mix The value of the property
     */
    public function __get($name){
        
        return $this->class_inctance->$name;
    }
    
    /**
     * Magic method for setting a property for loaded class
     *
     * @param string Properties name
     * @param mix the value to store
     * @return void
     */
    public function __set($name, $value){
        
        $this->class_inctance->$name = $value;
    }
    
    /**
     * Hendle routing direct from url. The url
     * should looks: http://website.com/index.php/module_name/controller_name/method_name
     *
     */
    public function public_routing(){
        
        $pan_uri        = new Library_uri();
        $module_name    = $pan_uri->get_class();
        $controller     = $pan_uri->get_method('home');
        $method         = $pan_uri->break_uri_string(3);
        $class          = ucwords($module_name).'_controller_'.$controller;
        
        if( empty($method) )
            $method = 'index';
        
        if( ! $request = $pan_uri->get_requests(4) )
            $request = array();
        
        
        if( ! file_exists( $file = GEAR . 'module/' . $module_name . '/controller/' . $controller . '.php') )
            Library_error::_404();
        
        include_once $file;
        
        Panada_module::$_module_name = $module_name;
        
        $Panada = new $class();
        
        if( ! method_exists($Panada, $method) ){
            
            $request = ( ! empty($request) ) ? array_merge(array($method), $request) : array($method);
            $method = $Panada->config->alias_method;
            
            if( ! method_exists($Panada, $method) )
                Library_error::_404();
        }
        
        call_user_func_array(array($Panada, $method), $request);
    }
    
} // End Library_module
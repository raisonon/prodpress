<?
/*
Plugin Name: MVC App
Plugin URI: http://www.hazlitteastman.com
Version: 1.2
Author: Hazlitt Eastman
Description: MVC application development plugin for WordPress
*/



//  Create php class for plugin
if (!class_exists("mvc_app_plugin")) {
    class mvc_app_plugin {
	 	//constructor
        function __construct() {
        }

	}
}

//  Instantiate the plugin class

if (class_exists("mvc_app_plugin")) {
    $mvc_app_plugin = new mvc_app_plugin();
}



// load app config

$config_path = ABSPATH . 'wp-content/mvc_app/config/config.php';
include($config_path);



//  add custom query variables
function add_query_vars_filter($vars){
  $vars[] = "mvc_app_route";
  $vars[] = "role";
  $vars[] = "user_id";
  return $vars;
}
add_filter('query_vars', 'add_query_vars_filter');



// Include core class files

include('parent_class.php');
include('model_class.php');
include('controller_class.php');
include('view_class.php');
include('helper_class.php');



// enable sessions and kill them at log out

add_action('init', 'start_session', 1);
add_action('wp_logout', 'end_session');
add_action('wp_login', 'end_session');

function start_session() {
    if(!session_id()) {

       	session_start();
    }
}

function end_session() {
    session_destroy ();
}



// add mvc api endpoint

function mvc_api_endpoint() {

    add_rewrite_endpoint( 'mvc_api', EP_ALL );
}
add_action( 'init', 'mvc_api_endpoint' );



// interupt Wordpress loading templates if /mvc_api/ is in the URL

function mvc_api_redirect() {
    global $wp_query;

    // if this is not a request for json or a singular object then bail
    if ( ! isset( $wp_query->query_vars['mvc_api'] ) ) return;

	mvc_app();
    exit;
}
add_action( 'template_redirect', 'mvc_api_redirect' );




// instantiate the routed class

function mvc_app( $route = NULL ) {


	// get the route if not passed to this function
	if ($route == NULL) {
		$route = '';
		if (isset($_GET['mvc_app_route'])) {

			$route = $_GET['mvc_app_route'];
		} else {

			$route = "start";
		}
	}


	// breakdown the route
	$route_slugs = explode('/', $route);


	// if the first slug is empty go to the default route
	if (!isset($route_slugs[0])) {

		$controller_name = "start";

	} else {

		$controller_name = $route_slugs[0];
	}


	// use specified method in the route if not use default
	$method_name = '';
	if (isset($route_slugs[1])) {
		$method_name = $route_slugs[1];
	} else {
		$method_name = 'default';
	}


	// load controller

	$controller_file = ABSPATH . 'wp-content/mvc_app/controllers/' . $controller_name . ".php";

	// check if the controller file exists

	if (file_exists($controller_file) == 1) {

		include $controller_file;

		// instantiate object
		$mvc_app = new $controller_name();

		// check if method exists
		if (($method_name != '') && (method_exists($mvc_app, $method_name)))  {

			// execute method
			$result = $mvc_app->$method_name();
		} else {

			echo "Bunk!";

		}


	} else {

		echo "Doh!";

	}

	if (isset($result)) {

		return $result;

	}

}

//  instantiate the routed class via a shortcode

add_shortcode( 'mvc_app', 'mvc_app' );



<?php
defined( 'ABSPATH' ) or exit;
/*
Plugin Name: bodi0`s Bots visits counter
Plugin URI: 
Description: Count the visits from web spiders, crawlers and bots in your blog. 
Also can count any other visit, the plug-in is looking for patterns in user-agent string, which pattern can be customized.
Version: 0.7
Text Domain: bodi0-bot-counter
Domain Path: /languages
Author: bodi0
Email: budiony@gmail.com
Author URI: 
License: GPL2

		Copyright 2014  bodi0  (email : budiony@gmail.com)
		
		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License, version 2, as 
		published by the Free Software Foundation.
		
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.
		
		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

global $wpdb, $nonce;
/*Plugin file name*/
$plugin = plugin_basename( __FILE__ );
/*Security check*/
$nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
/*Actions*/
add_action('init', 'plugin_internationalization');
/*Export statistics*/
add_action('admin_init', 'call_bot_export');
/*Admin menu page*/
add_action("admin_menu", "bot_admin_actions");
/*Attach bot function to footer*/
add_action("wp_footer","bot");
/*Settings link*/
add_filter( "plugin_action_links_$plugin", 'plugin_add_settings_link' );


/*Action executed when plugin is uninstalled*/
register_uninstall_hook(__FILE__, 'bot_uninstall' );
/*Action executed when plugin is deactivated*/
register_deactivation_hook(__FILE__, 'bot_deactivate');
/*Action executed when plugin is activated*/
register_activation_hook(__FILE__, 'bot_install');


/*SQL table name*/
if(!defined('__TABLE__')) define ('__TABLE__', $wpdb->prefix."bodi0_bot_counter");



// Install plugin
function bot_install() {
		
	global $wpdb;
	
	// Important: Check if current user can install plugins
	if ( !current_user_can( 'activate_plugins' ) )  return;
    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    check_admin_referer( "activate-plugin_{$plugin}" );


	/* Alter, create or update table with dbDelta() [returns: array()]
	/* http://codex.wordpress.org/Creating_Tables_with_Plugins
	 
	Rather than executing an SQL query directly, we'll use the dbDelta function in wp-admin/includes/upgrade.php 
	(we'll have to load this file, as it is not loaded by default).
	
	The dbDelta function examines the current table structure, compares it to the desired table structure, and either adds or modifies
	the table as necessary, so it can be very handy for updates. 
	
	Note that the dbDelta function is rather picky, however. For instance: 
  - You must put each field on its own line in your SQL statement. 
  - You must have two spaces between the words PRIMARY KEY and the definition of your primary key. 
  - You must use the key word KEY rather than its synonym INDEX and you must include at least one KEY. 
  - You must not use any apostrophes or backticks around field names.
	*/
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$sql = 'CREATE TABLE '.__TABLE__ .' (
	id INT(9)  NOT NULL AUTO_INCREMENT,
	bot_name  VARCHAR (20) NOT NULL, 
	bot_mark  VARCHAR (80) NOT NULL,
	bot_visits  INT (9) DEFAULT 0,
	bot_last_visit  TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	ip_address  VARCHAR (20) NULL DEFAULT NULL, 
	PRIMARY KEY  (id)	
	);';
	
	//Compare with dbDelta()
	$result_delta = dbDelta( $sql );
	
	//Check for empty table and only then insert data initially
	$result_empty = $wpdb->get_row('SELECT id FROM '.__TABLE__, ARRAY_A);
	
	if(!empty($result_delta) && empty($result_empty['id'])) {
	//Populate table newly created table only if empty
	$wpdb->query("INSERT INTO ".__TABLE__ ." (bot_name, bot_mark) VALUES 
	('80legs', '80legs'),
	('AddThis.com', 'AddThis.com'),
	('Baiduspider', 'Baiduspider'),
	('Bing', 'bingbot'),
	('bitlybot', 'bitlybot'),
	('CatchBot', 'CatchBot'),
	('Exabot', 'Exabot'),
	('Ezooms', 'Ezooms'),
	('facebookplatform', 'facebookplatform'),
	('Google Bot', 'googlebot'),
	('GrapeshotCrawler', 'GrapeshotCrawler'),
	('Mail.RU_Bot', 'Mail.RU_Bot'),
	('MJ12bot', 'MJ12bot'),
	('MSN Bot', 'MSNBot'),
	('Netseer', 'Netseer'),
	('Proximic', 'proximic'),
	('Qualidator', 'Qualidator'),
	('SeznamBot', 'SeznamBot'),
	('spbot', 'spbot'),
	('Yahoo Slurp', 'yahoo'),
	('Yandex Bot', 'YandexBot')
	");
	}

 if(!empty($wpdb->last_error)) wp_die($wpdb->print_error());
 
}

//Deactivate plugin
function bot_deactivate() {
	global $wpdb;

	// Important: Check if current user can deactivate plugins
	if ( !current_user_can( 'activate_plugins' ) )  return;
    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    check_admin_referer( "deactivate-plugin_{$plugin}" );	
 if(!empty($wpdb->last_error)) wp_die($wpdb->print_error());
} 

//Uninstall plugin
function bot_uninstall() {
	global $wpdb;
	// Important: Check if current user can uninstall plugins
	if ( !current_user_can( 'delete_plugins' ) ) return;
  check_admin_referer( 'bulk-plugins' );  
 
	$wpdb->query('DROP TABLE IF EXISTS '.__TABLE__);
  if(!empty($wpdb->last_error)) wp_die($wpdb->print_error());
}


//Count bots visits
function bot() {
	global $wpdb;

	$bots = $wpdb->get_results('SELECT * FROM '.__TABLE__, ARRAY_A);
	//Cycle
	foreach ($bots as $bot) {
		if(stristr($_SERVER['HTTP_USER_AGENT'], $bot['bot_mark'])) {
			$wpdb->query($wpdb->prepare('UPDATE '.__TABLE__ .' SET bot_visits = bot_visits + 1, ip_address = %s
			WHERE id = %d ', sanitize_text_field($_SERVER['REMOTE_ADDR']), $bot['id']));
		
			//break;
		}
	}
	
}

//Admin panel functions
function bot_menu () {
	// Important: Check if current user is logged
	if ( !is_user_logged_in( ) )  die();
	include_once ("bodi0-bot-admin.php");
}

//Register admin menu
function bot_admin_actions() {
		add_options_page(__("Bot visits counter","bodi0-bot-counter"), __("Bot visits counter","bodi0-bot-counter"), "manage_options" , "bodi0-bot-counter", "bot_menu");	
}

//Translations
function plugin_internationalization() {
  load_plugin_textdomain( 'bodi0-bot-counter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

//Settings link
function plugin_add_settings_link( $links ) {
    
		$settings_link = '<a href="options-general.php?page='.basename( __FILE__ ).'">'.__("Administration","bodi0-bot-counter").'</a>';
  	array_push( $links, $settings_link );
  	return $links;
}

//Custom error handling
//_trigger_error('Some error message', E_USER_ERROR);
 
function _trigger_error($message, $errno) {
	if(isset($_GET['action']) && $_GET['action'] == 'error_scrape') {
		echo '<strong>' . $message . '</strong>';
		exit;
	} else {
		trigger_error($message, $errno);
	}
}
/***************************************************************************************************************************/
//Export statistics as Excel file
function call_bot_export() {
	global $wbdb, $nonce;
	if (isset($_GET['bot-download']) && trim($_GET['bot-download']) =='stats' && ( wp_verify_nonce( $nonce, 'bot-nonce' )) ) {
		//If everything is set - export data
		bot_export();
	}
} 
function bot_export() {
	global $wpdb, $nonce;

	require_once("class.excel.php");
	$query = 'SELECT * FROM '.__TABLE__.' ORDER BY bot_visits DESC';
	$arr = $wpdb->get_results($query, ARRAY_A);
	$field_names = array('Bot name', 'Bot identifier', 'Visits', 'Last visit', 'IP address');
	//Columns count
	$fields_num = count($field_names);
	//Result count
	$count = count($arr);
	$today = 	date("Y-m-d");
	 
	//Make instance of the class
	$exporter = new ExportDataExcel('browser', $filename);
	$exporter->filename = "bot-statistics-".$today;
	//Starts streaming data to web browser
	$exporter->initialize();

	//Put current generated date
	$exporter->addRow(array('Generated on: '.$today)); 

	//Create sheet column titles from database column names
	$exporter->addRow($field_names);
	
	//Get the rest of the data from database
	for ($m=0; $m<$count; $m++) {
		//Pass addRow() an array and it converts it to Excel XML format and sends  it to the browser
		$exporter->addRow(array($arr[$m]['bot_name'],$arr[$m]['bot_mark'],$arr[$m]['bot_visits'],
		$arr[$m]['bot_last_visit'],$arr[$m]['ip_address'] ));
	}

	$exporter->finalize(); //Writes the footer, flushes remaining data to browser.
	exit(); //All done

//Error handling
	if (!empty($wpdb->last_error)) { 	$wpdb->print_error(); }
	}	

/****************************************************************************************************************************/
?>
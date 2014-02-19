<?php
defined( 'ABSPATH' ) or exit;
/*
Plugin`s Administration panel
Author: bodi0
Email: budiony@gmail.com
Version: 0.8
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

// Important: Check if current user is logged
if ( !is_user_logged_in( ) )  die();


//Security check
$nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';

global $wpdb, $wp_filesystem, $is_apache, $is_IIS, $is_iis7, $is_nginx;

//SQL table name
if (!defined('__TABLE__')) define ('__TABLE__', $wpdb->prefix.'bot_counter'); 


/***************************************************************************************************************************/
//Write .htaccess file
if (isset($_POST['bot_banned_ip']) && isset($_POST['bot_banned_mark']) && ( wp_verify_nonce( $nonce, 'bot-nonce' ))) {
	//Proceed only if web server is Apache
	if ($is_apache) {
	//Validate the IP
	$bot_banned_ip = (filter_var($_POST['bot_banned_ip'], FILTER_VALIDATE_IP) ? $_POST['bot_banned_ip'] : '-1');
	//Sanitize bot identifier
	$bot_banned_mark = sanitize_text_field($_POST['bot_banned_mark']);
	//
	$url = wp_nonce_url('?page='.$_GET['page'],'bot-nonce');
	if (false === ($creds = request_filesystem_credentials($url, '', false, false, null) ) ) {
		echo 'WP_Filesystem Error, unable to get credentials';
		return; // stop processing here
	}	

	if ( ! WP_Filesystem($creds) ) {
	request_filesystem_credentials($url, '', true, false, null);
	echo 'WP_Filesystem Error, unable to get credentials';
	return;
	}
	
	//Web server config file name
	$access_file = '.htaccess';
	$backup_file = '.htaccess.bot-counter-backup.txt';
	
	//Initialize variables
	$rewrite_code = '';
	$rewrite_code_exists = false;
	$bot_code_exists = false;
	$success = false;
	$file_contents = NULL;
	$replacement = '';
	//preg_quote() MUST be used for escaping the special characters, 
	//which may be interpreded as regex patterns, the i and s modifiers are mandatory
	$comments_pattern = "~##bbc-start".preg_quote($bot_banned_ip, '/').preg_quote($bot_banned_mark, '/')."(.*?)##bbc-end~is";
	$rewrite_pattern = "~RewriteEngine\s+On~";

	$action_type = (isset($_POST['action-type'])) ? sanitize_text_field($_POST['action-type']) : '';
	$file_operation = NULL;
	
	
	/*LOGIC FOR READING AND UPDATING FILE WITH ACCESS RULES*/
	//Check if file is there
	if (file_exists(get_home_path().'/'.$access_file)) {
		//..and is writeable
		if (is_writeable(get_home_path().'/'.$access_file) && is_readable(get_home_path().'/'.$access_file)) {
			
			//Create/re-create the backup file (overwrite the old one)
			$wp_filesystem->copy(get_home_path().'/'.$access_file, get_home_path().'/'.$backup_file, true, false);
			
			//Get config file contents with predefined mode settings for WP files
			$file_contents = $wp_filesystem->get_contents(get_home_path().'/'.$access_file,	FS_CHMOD_FILE );


			/************************************************************************************************/
			//****************Block item
			if ($action_type == 'block') {
			/*We will write in the .htaccess file no matter if bot is already blocked*/

				/*Setup*/
				$replacement = "RewriteEngine On
##bbc-start{$bot_banned_ip}{$bot_banned_mark}
RewriteCond %{REMOTE_HOST} {$bot_banned_ip} [OR]
RewriteCond %{HTTP_USER_AGENT} {$bot_banned_mark} 
RewriteRule . - [F]
##bbc-end";	
				/*******/
			
			$rewrite_code_exists = preg_match($rewrite_pattern, $file_contents);
			//Check if RewriteEngine code already exists in .htaccess file
			if ($rewrite_code_exists) {
				//Check if bloking rules does not already exists
				$bot_code_exists = preg_match($comments_pattern, $file_contents);

				if ($bot_code_exists===0) {
					//Replace the contents of original .htaccess file only if bot is not already blocked
					$rewrite_code = preg_replace($rewrite_pattern, $replacement, $file_contents);	
				}
				else {
				//Else display error
					$file_operation = 4;
				}
			}
			//If RewriteEngine code do not exists in .htaccess then add it at the end of file
			else {
				$replacement = "\n".'<IfModule mod_rewrite.c>'."\n".$replacement."\n".'</IfModule>'."\n";
				$rewrite_code = $file_contents.$replacement;	
			}
				if ($file_operation != 4)
				//Write changes to the file
				$success = $wp_filesystem->put_contents(get_home_path().'/'.$access_file, $rewrite_code, FS_CHMOD_FILE );
				if ($success) $file_operation = 1;
			}
			
			/***********************************************************************************************/
			//***************Unblock item
			elseif ($action_type == 'unblock') {
				/*Setup*/
				$replacement = '';
				/*******/
				$bot_code_exists = preg_match($comments_pattern, $file_contents);
				if ($bot_code_exists) {
					//Replace the contents of original .htaccess file
					$new_file_contents = preg_replace($comments_pattern, $replacement, $file_contents);	
					//Wrtite changes to the file
					$success = $wp_filesystem->put_contents(get_home_path().'/'.$access_file, $new_file_contents, FS_CHMOD_FILE );
					if ($success) $file_operation = 3;
				}
				else {
					$file_operation = 2;
					}
				
			}
			
			
			/***********************************************************************************************/
			//***************Invalid action	
			else {
				echo '<div id="message" class="updated error">';
				_e("Invalid action type while attempting to update the file ", "bodi0-bot-counter"); echo $access_file;
				echo '</div>';
				$file_operation = 2;
			}
			
			
			//Item was blocked (added) in .htaccess
			if ($file_operation===1) { 
				echo '<div id="message" class="updated">';
				_e($access_file); echo '<strong>'; _e(" was updated.", "bodi0-bot-counter"); echo '</strong>';
				echo '<br/>';
				_e("However it is good idea to manually check if everything is correct and the file is placed in the root folder of your web server. If you experience 500 Internal Server Error condition, then remove the following lines from ","bodi0-bot-counter"); echo $access_file;
				echo "<br/><pre><code>". esc_html("##bbc-start{$bot_banned_ip}{$bot_banned_mark}
RewriteCond %{REMOTE_HOST} {$bot_banned_ip} [OR]
RewriteCond %{HTTP_USER_AGENT} {$bot_banned_mark} 
RewriteRule . - [F]
##bbc-end"). "</code></pre></div>";
			}
			
			//Item was not found in the file .htaccess
			elseif ($file_operation===2) {
				echo '<div id="message" class="updated error">';
				_e("The blocking rules for this bot were not found in file ","bodi0-bot-counter"); echo $access_file; echo '<br/>';
				_e("Did you removed from the file the below comments and content in-between (which were created by the plugin)?","bodi0-bot-counter");
			echo "<br/><code>##bbc-start ... ##bbc-end</code>";
			echo '</div>';
			}
			
			//Item was unblocked (removed) from .htaccess
			elseif ($file_operation===3) {
				echo '<div id="message" class="updated">';
				_e("The blocking rules for this bot were removed from file ","bodi0-bot-counter"); echo $access_file;
				echo '</div>';
				
			}
			
			//Item already exists in file .htacces, no need to add it again
			elseif ($file_operation===4) {
				echo '<div id="message" class="updated error">';
				_e("The blocking rules for this bot already exists in file ","bodi0-bot-counter"); echo $access_file;
				echo '<br/>';
				_e("No modification is necessary.","bodi0-bot-counter");
				echo '</div>';
				
			}
			
			//Unsuccessful .htaccess file update
			else {
				echo '<div id="message" class="updated error">';
				_e("Unable to write the new content to the file ","bodi0-bot-counter"); echo $access_file;
				echo '</div>';				
				}
	}
	
		
		//File system permissions error
		else {
			if ($action_type=='block')
			$rewrite_code = __("Please update this file manually by placing inside the following code: ", "bodi0-bot-counter")
			."<br/><pre><code>". $rewrite_code. "</code></pre>"
			.esc_html("<IfModule mod_rewrite.c>
RewriteEngine On
##bbc-start{$bot_banned_ip}{$bot_banned_mark}
RewriteCond %{REMOTE_HOST} {$bot_banned_ip} [OR]
RewriteCond %{HTTP_USER_AGENT} {$bot_banned_mark} 
RewriteRule . - [F]
##bbc-end
</IfModule>");
			if ($action_type == 'unblock')
			$rewrite_code = __("In this case the system cannot modify it and you should remove the rules manually, for this bot everything in-between comments: ","bodi0-bot-counter")
			. "<pre><code>##bbc-start{$bot_banned_ip}{$bot_banned_mark} and ##bbc-end</code></pre>";


			echo '<div id="message" class="updated error">';
			_e("Wordpress do not have permissions to read/write the file ", "bodi0-bot-counter"); echo $access_file;
			echo '<br/>'.$rewrite_code; 
			echo "</div>";
			}
	}
	

	
	//File does not exists, attempt to create it and write the contents
	elseif (!file_exists(get_home_path().'/'.$access_file)) {
		//Config directives
		$rewrite_code = "
<IfModule mod_rewrite.c>
RewriteEngine On
##bbc-start{$bot_banned_ip}{$bot_banned_mark}
RewriteCond %{REMOTE_HOST} {$bot_banned_ip} [OR]
RewriteCond %{HTTP_USER_AGENT} {$bot_banned_mark} 
RewriteRule . - [F]
##bbc-end
</IfModule>";	
		
		$success = $wp_filesystem->put_contents(get_home_path().'/'.$access_file, $rewrite_code, FS_CHMOD_FILE );
			if ($success) {
				echo '<div id="message" class="updated">';
				echo $access_file; echo '<strong>'; _e(" was created.", "bodi0-bot-counter"); echo '</strong>';
				echo '<br/>';
				_e("The configuration file does not existed and was created by the plugin. It is good idea to manually check if everything is correct and the file is placed in the root folder of your web server. If you experience 500 Internal Server Error condition, then remove the following lines from ","bodi0-bot-counter"); echo $access_file;
				echo "<br/><pre><code>". esc_html($rewrite_code). "</code></pre></div>";
			}
			//Unsuccessful file update
			else {
				echo '<div id="message" class="updated error">';
				_e("Unable to write the new content to the file ","bodi0-bot-counter"); echo $access_file;
				echo '</div>';				
				}

		}
	
	
	//File system permissions error
	else {
		_e("Wordpress File System Error, insufficient permissions for accessing the file ", "bodi0-bot-counter"); echo $access_file;
		}
	}
	
		
	
	//We have IIS/IIS7 web server
	elseif ($is_IIS || $is_iis7) {
		echo '<div id="message" class="updated error">';
		_e("Modifying the web.config file on IIS is not supported. However you can manually change the file contents by writing the correct directives.", "bodi0-bot-counter");
		echo '</div>';
		}
	
	
	
	//We have nginx server
	elseif ($is_nginx) {
		echo '<div id="message" class="updated error">';
		_e("Modifying the config file of nginx server is not supported. However you can manually change the file contents by writing the correct directives.","bodi0-bot-counter");
	echo '</div>';
	}
	
	
	
	//We have unknown web server
	else {
		echo '<div id="message" class="updated error">';
		_e("Modifying the config file of this web server is not supported. However you can manually change the file contents by writing the correct directives.","bodi0-bot-counter");
		echo '</div>';
		}


} //end Write .htaccess file




/*****************************************************************************************************************************/
//Reset statistics
if (isset($_GET['bot-counter']) && $_GET['bot-counter'] == 'reset' && ( wp_verify_nonce( $nonce, 'bot-nonce' )) ) {
	$wpdb->query ( $wpdb->prepare('UPDATE '.__TABLE__ .' SET bot_visits = %d, bot_last_visit = NULL, ip_address = NULL ', 0) );

//Error handling
	if (!empty($wpdb->last_error)) { 	$wpdb->print_error(); } 
	else {?>

<div id="message" class="updated">
  <p>
    <?php _e("Bot visits Stats", "bodi0-bot-counter") ?>
    <strong><?php _e(" were reset","bodi0-bot-counter") ?></strong>.</p>
</div>
<?php 
	}
}	
 

/***************************************************************************************************************************/
//Add new bot
if (isset($_POST['bot-name']) && isset($_POST['bot-mark']) && trim($_POST['bot-name']) !='' && trim($_POST['bot-mark']) !='' 
&& ( wp_verify_nonce( $nonce, 'bot-nonce' )) ) {
	$wpdb->query( $wpdb->prepare("INSERT INTO ".__TABLE__ ." (bot_name, bot_mark, bot_visits, bot_last_visit) 
	VALUES (%s, %s, 0, NULL )", sanitize_text_field($_POST['bot-name']), sanitize_text_field($_POST['bot-mark'])));

//Error handling
	if (!empty($wpdb->last_error)) { 	$wpdb->print_error(); }
	else {?>
<div id="message" class="updated">
  <p>
    <?php _e("New bot", "bodi0-bot-counter"); ?>
    <strong><?php _e("added", "bodi0-bot-counter"); ?></strong>.</p>
</div>
<?php 
	}
}	 

/****************************************************************************************************************************/
//Update a bot
if (isset($_POST['bot-name']) && isset($_POST['bot-id']) && trim($_POST['bot-name']) !='' && !empty($_POST['bot-id']) 
&& ( wp_verify_nonce( $nonce, 'bot-nonce' )) ) {
	$wpdb->query( $wpdb->prepare("UPDATE ".__TABLE__ ." SET bot_name = %s	WHERE id = %d ", sanitize_text_field($_POST['bot-name']), sanitize_text_field($_POST['bot-id'])));

//Error handling
	if (!empty($wpdb->last_error)) { 	$wpdb->print_error(); }
	else {?>
<div id="message" class="updated">
  <p>
    <?php _e("The bot`s name", "bodi0-bot-counter"); ?>
    <strong><?php _e("was updated", "bodi0-bot-counter"); ?></strong>.</p>
</div>
<?php 
	}
}	 

/*****************************************************************************************************************************/
//Delete bot
if (isset($_GET['bot']) && $_GET['bot']=='delete' && !empty($_GET['bot-id']) && ( wp_verify_nonce( $nonce, 'bot-nonce' )) ) {
	$wpdb->query( $wpdb->prepare("DELETE FROM ".__TABLE__ . " WHERE id = %d", $_GET['bot-id']) );
	
	//Error handling
	if (!empty($wpdb->last_error)) { 	$wpdb->print_error(); }
	else {?>
<div id="message" class="updated">
  <p>
    <?php _e("The Bot", "bodi0-bot-counter"); ?>
    <strong><?php _e("was deleted", "bodi0-bot-counter"); ?></strong>.</p>
</div>
<?php 
	} 
}
?>
<style type="text/css">
.geo-info {position: absolute; background: #fff; border:1px solid #ccc;left:75%;width:20%; cursor:pointer;z-index:10000;box-shadow: 0px 3px 5px rgba(0, 0, 0, 0.196) !important}
.geo-info .inner {margin:1em}
a {text-decoration:none !important}
.tablesorter-default .header,
.tablesorter-default .tablesorter-header {
	background-image: url(data:image/gif;base64,R0lGODlhFQAJAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAkAAAIXjI+AywnaYnhUMoqt3gZXPmVg94yJVQAAOw==);
	background-position: center right;
	background-repeat: no-repeat;
	cursor: pointer;
	white-space: normal;
}
.tablesorter-default thead .headerSortUp,
.tablesorter-default thead .tablesorter-headerSortUp,
.tablesorter-default thead .tablesorter-headerAsc {
	background-image: url(data:image/gif;base64,R0lGODlhFQAEAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAQAAAINjI8Bya2wnINUMopZAQA7);
	border-bottom: #000 2px solid;
}
.tablesorter-default thead .headerSortDown,
.tablesorter-default thead .tablesorter-headerSortDown,
.tablesorter-default thead .tablesorter-headerDesc {
	background-image: url(data:image/gif;base64,R0lGODlhFQAEAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAQAAAINjB+gC+jP2ptn0WskLQA7);
	border-bottom: #000 2px solid;
}
.tablesorter-default thead .sorter-false {	background-image: none;	cursor: default;}
@media only screen and (min-width: 700px) and (max-width: 799px) {
div.wrap div.edit {min-width: 660px !important;width: 710px !important;}
}
@media only screen and (min-width: 800px) and (max-width: 844px) {
div.wrap div.edit {min-width: 660px !important;width: 720px !important;}
}
@media only screen and (min-width: 845px) and (max-width: 960px) {
div.wrap div.edit {min-width: 690px !important;width: 726px !important;}
}
@media only screen and (min-width: 961px) and (max-width: 979px) {
div.wrap div.edit {min-width: 690px !important;width: 730px !important;}
}
@media only screen and (min-width: 980px) and (max-width: 999px) {
div.wrap div.edit {min-width: 690px !important;width: 750px !important;}
}
@media only screen and (min-width: 1000px) and (max-width: 1024px) {
div.wrap div.edit {min-width: 690px !important;width: 770px !important;}
}
@media only screen and (min-width: 1025px) and (max-width: 1048px) {
div.wrap div.edit {min-width: 690px !important;width: 790px !important;}
}
@media only screen and (min-width: 1049px) and (max-width: 1080px) {
div.wrap div.edit {min-width: 690px !important;width: 818px !important;}
}
@media only screen and (min-width: 1081px)  {
div.wrap div.edit {min-width: 690px !important;width: 828px !important;}
}



div.wrap div.edit{background-color: #F9F9F9;left: 0px;margin-top: 0px;padding-top: 0px;position: absolute;top: auto;padding-bottom: 0px;padding-left: 10px;margin-left: 4px;margin-bottom: 0px;height:4.4em;white-space: nowrap !important;}

div.wrap div.edit form{margin-top:1.1em;}
.wrap form {display:inline-block !important}
input[type="submit"].unblock{background: #5D824B !important; border: 1 px solid #466238 !important; box-shadow: 1px 1px 1px rgba(0, 0, 0, 0.2) !important; color: white !important;}
input[type="submit"].block, input[type="submit"].block:hover{background: #820B0B !important; border: 1 px solid #670808 !important; box-shadow: 1px 1px 1px rgba(0, 0, 0, 0.2) !important; color: white !important;}
</style>
<div class="wrap">
  <h2>
    <?php _e("Bot visits counter [Administration]","bodi0-bot-counter"); ?>
  </h2>
  <p class="submitbox"><a href="?page=<?php echo $_GET['page']; ?>&amp;bot-counter=reset&amp;_wpnonce=<?php echo wp_create_nonce( 'bot-nonce' ) ?>" class="submitdelete">
    <?php _e("Reset Statistics", "bodi0-bot-counter"); ?>
    </a></p>
  <p>
    <?php _e("The list, by default, is ordered by number of visits, click on table header arrows to re-order","bodi0-bot-counter"); ?>. </p>
    <p><?php _e("If you plan to block/unblock bots, a backup of your old .htaccess file will be created in case something goes wrong, called <code>.htaccess.bot-counter-backup.txt</code> in the same folder as your original file.", "bodi0-bot-counter"); ?></p>
  <p class="alignright"> <a href="?page=<?php echo $_GET['page']?>&amp;_wpnonce=<?php echo wp_create_nonce( 'bot-nonce' ) ?>" class="alignleft">
    <?php _e("Refresh statistics", "bodi0-bot-counter"); ?>
    </a> &nbsp; </p>
  <p class="alignright"> <a href="?page=<?php echo $_GET['page'];?>&amp;bot-download=stats&amp;_wpnonce=<?php echo wp_create_nonce( 'bot-nonce' ) ?>" class="alignleft">
    <?php _e("Export as XML Spreadsheet","bodi0-bot-counter"); ?>
    </a> &nbsp;|&nbsp;</p>
  <?php
//Get all results of visits
$results = $wpdb->get_results('SELECT * FROM '.__TABLE__.' ORDER BY bot_visits DESC', ARRAY_A);
?>
<script type="text/javascript">
var $ = jQuery.noConflict();

/*Get geo location via AJAX call*/
function get_geoinfo(ip_address, toggle_id, content_id) {

var request = $.ajax({
	url: "<?php echo plugin_dir_url( __FILE__ ) ?>bodi0-bot-ajax.php",
  type: "GET",
	global: false,
	cache: false,
	data: { action: 'get_location_info', ip: ip_address, _wpnonce: '<?php echo wp_create_nonce( 'bot-nonce' ) ?>'
 },
  dataType: "html",
	async:true

});
request.done(
function( msg ) {
  $("#"+toggle_id).toggle(200);
	$( "#"+content_id ).html( msg );
});
request.fail(
function( jqXHR, textStatus, ev ) {
  alert( "AJAX request failed: " + textStatus + ev  );
});
	
}
/*Get page rank info via AJAX call*/
function get_pagerank(url, rank_type, content_id) {
var new_url = $("#" + url).val();
var request = $.ajax({
	url: "<?php echo plugin_dir_url( __FILE__ ) ?>bodi0-bot-ajax.php",
  type: "GET",
	global: false,
	cache: false,
	data: { action: rank_type, url: new_url, _wpnonce: '<?php echo wp_create_nonce( 'bot-nonce' ) ?>'
 },
  dataType: "html",
	async:true

});
request.done(
function( msg ) {
	$( "#"+content_id ).html( msg );
});
request.fail(
function( jqXHR, textStatus, ev ) {
  alert( "AJAX request failed: " + textStatus + ev  );
});
	
}

</script> 
  <script type="text/javascript" src="<?php echo plugin_dir_url( __FILE__ ) ?>js/table-sorter.js"></script> 
  <script type="text/javascript">
/*Sort the table*/
$(document).ready(function(){
  $("#bot-table").tablesorter();
});

</script> 
  <script type="text/javascript">
/*Courtesy of http://www.vonloesch.de/node/23 */
/*Filter 2*/
function filter2 (phrase, _id){
	var words = phrase.value.toLowerCase().split(" ");
	var table = document.getElementById(_id);
	var ele;
	for (var r = 1, len = table.rows.length; r < len; r++){
		ele = table.rows[r].innerHTML.replace(/<[^>]+>/g,"");
	        var displayStyle = 'none';
	        for (var i = 0; i < words.length; i++) {
		    if (ele.toLowerCase().indexOf(words[i])>=0)
			displayStyle = '';
		    else {
			displayStyle = 'none';
			break;
		    }
	        }
		table.rows[r].style.display = displayStyle;
	}
}
</script>
  <table style="width: 50% !important" class="allignleft">
    <tr>
      <td><?php _e("Filter (all)", "bodi0-bot-counter"); ?>:
        <input name="filter" id="filter" onkeyup="filter2(this, 'bot-table')" type="text" placeholder="<?php _e("Type here...","bodi0-bot-counter"); ?>" style="vertical-align:middle;width:200px"/>
        <a href="javascript:void(0)" onclick="document.getElementById('filter').value=''; filter2(document.getElementById('filter'), 'bot-table')">
        <?php _e("Reset","bodi0-bot-counter"); ?>
        </a></td>
    </tr>
  </table>
  <table class="widefat" style="min-width:740px !important; max-width:870px !important" id="bot-table">
    <thead>
      <tr>
        <th><?php _e("Bot name","bodi0-bot-counter"); ?>
        </th>
        <th><?php _e("Bot identifier","bodi0-bot-counter"); ?>
        </th>
        <th><?php _e("Visits","bodi0-bot-counter"); ?>
        </th>
        <th><?php _e("Last visit","bodi0-bot-counter"); ?>
        </th>
        <th><?php _e("IP address","bodi0-bot-counter"); ?>
        </th>
        <th class="sorter-false"></th>
      </tr>
    </thead>
    <tbody>
      <?php
//Counters
$i = 0; $j = 0;
//The cycle
foreach($results as $result) {
	//
	echo '<tr class="search-me alternate"><td><strong>'.$result['bot_name'].'</strong>
	<div class="row-actions"><span class="inline hide-if-no-js"><a class="editinline" 
	href="javascript:void(0)" onclick="$(\'#edit-'.$result['id'].'\').show();"
	title="'. __("Edit this item","bodi0-bot-counter").'">'.__("Edit","bodi0-bot-counter").'</a> | </span>
	<span class="inline hide-if-no-js"><a class="editinline"
	href="javascript:void(0)" onclick="$(\'#block-'.$result['id'].'\').show();"
	>'.__("Block/Unblock", "bodi0-bot-counter").'</a></span> | 
	<span class="trash"><a class="submitdelete" 
	href="?page='.esc_html($_GET['page']).'&amp;bot=delete&amp;bot-id='.(int)$result['id'].'&amp;_wpnonce='. wp_create_nonce( 'bot-nonce' ).'" >'. __("Delete","bodi0-bot-counter") .'</a></span></div>
	</td><td>'.$result['bot_mark'].'</td><td>'. $result['bot_visits'].
	'</td><td>'. (!empty($result['bot_last_visit'])? $result['bot_last_visit'] : '').'</td>
	<td>'.(!empty($result['ip_address'])? '<a href="javascript:void(0)" 
	onclick="get_geoinfo(\''.$result['ip_address'].'\', \'toggle'.$result['id'].'\', \'content'.$result['id'].'\');">'.$result['ip_address'].'</a>' : '').'</td>';
	echo '<td><div onclick="$(this).hide()" class="geo-info" style="display:none" id="toggle'.$result['id'].'"><div class="inner" id="content'.$result['id'].'"></div><div class="alignright" style="position:absolute;right:10px;top:10px;">x</div>
	</div>';
?>
    <div id="edit-<?php echo $result['id'] ?>" style="display:none;" class="edit">
      <form method="post" name="editform" action="?page=<?php echo $_GET['page'] ?>">
        <?php  wp_nonce_field( 'bot-nonce' );?>
        <?php _e("New name","bodi0-bot-counter"); ?>
        :
        <input type="text" name="bot-name" id="bot-name" value="<?php echo $result['bot_name']?>" maxlength="20"/>
        (
        <?php _e("up to 20 characters","bodi0-bot-counter"); ?>
        )
        <input type="hidden" name="bot-id" value="<?php echo $result['id']?>" />
        <input type="submit" name="submit" class="button-primary submit" value="<?php _e("Update","bodi0-bot-counter"); ?>"/>
        &nbsp;<a accesskey="c" href="javascript:void(0)" onclick="$('#edit-<?php echo $result['id']?>').hide()" class="button-secondary cancel">
        <?php _e("Cancel","bodi0-bot-counter"); ?>
        </a>
      </form>
    </div>
    <div id="block-<?php echo $result['id']?>" style="display:none;" class="edit">
      <form name="form-block" method="post" action="?page=<?php echo $_GET['page'] ?>">
        <input type="hidden" name="bot_banned_ip" value="<?php echo $result['ip_address']?>" />
        <input type="hidden" name="bot_banned_mark" value="<?php echo $result['bot_mark']?>" />
        <input type="hidden" name="action-type" value="block" />
        <input type="submit" name="submit" class="button-primary block" value="<?php _e("Block the IP address and Bot identifier","bodi0-bot-counter"); ?>" />
        <?php wp_nonce_field('bot-nonce');?>
      </form>
      <form name="form-unblock" method="post" action="?page=<?php echo $_GET['page']?>">
        <input type="hidden" name="bot_banned_ip" value="<?php echo $result['ip_address']?>" />
        <input type="hidden" name="bot_banned_mark" value="<?php echo $result['bot_mark']?>" />
        <input type="hidden" name="action-type" value="unblock" />
        <input type="submit" name="submit" class="button-primary unblock" value="<?php _e("Unblock the IP address and Bot identifier","bodi0-bot-counter");?>"  />
        <?php wp_nonce_field('bot-nonce');?>
        &nbsp;<a accesskey="c" href="javascript:void(0)" onclick="$('#block-<?php echo $result['id']?>').hide()" class="button-secondary cancel">
        <?php _e("Cancel","bodi0-bot-counter"); ?>
        </a>
      </form>
        
    </div>
    <?php
echo '</td></tr>'; //End edit 
//Increase counters
$i++; $j = $j + $result['bot_visits'];
}

?>
      </tbody>
    
    <tfoot>
      <tr>
        <th colspan="6"><strong>
          <?php _e("TOTAL","bodi0-bot-counter"); ?>
          </strong>: <strong><?php echo $i?></strong>, <strong>
          <?php _e("VISITS", "bodi0-bot-counter"); ?>
          </strong>: <strong><?php echo $j;?></strong></th>
      </tr>
    </tfoot>
  </table>
  <br/>
  <a href="javascript:void(0)" onclick="jQuery('#add-bot').toggle(200)">
  <?php _e("Add new Bot","bodi0-bot-counter"); ?>
  </a> | 
  <a href="javascript:void(0)" onclick="jQuery('#pagerank').toggle(200)">
  <?php _e("Get rankings","bodi0-bot-counter"); ?>
  </a>
  <div id="add-bot" style="display:none">
    <form method="post" name="form-add-bot" action="?page=<?php echo $_GET['page'];?>">
      <?php 
//Nonce field
wp_nonce_field( 'bot-nonce' );
?>
      <table style="margin:0;padding:0; width:640px">
        <tr>
          <td style="width:16%"><p>
              <?php _e("Bot name","bodi0-bot-counter"); ?>
              :</p></td>
          <td><p>
              <input type="text" name="bot-name" id="bot-name" value="" maxlength="20"/>
              (
              <?php _e("up to 20 characters, required","bodi0-bot-counter"); ?>
              )</p></td>
        </tr>
        <tr>
          <td><p>
              <?php _e("Bot identifier","bodi0-bot-counter"); ?>
              :</p></td>
          <td><p>
              <input type="text" name="bot-mark" id="bot-mark" value="" maxlength="100"/>
              (
              <?php _e("up to 100 characters, required","bodi0-bot-counter"); ?>
              )</p></td>
        </tr>
        <tr>
          <td colspan="2"><p>
              <input type="submit" name="submit" class="button-primary submit" value="<?php _e("Add new Bot","bodi0-bot-counter"); ?>"/>
              &nbsp;<a accesskey="c" href="javascript:void(0)" onclick="$('#add-bot').hide()" class="button-secondary cancel"><?php _e("Cancel", "bodi0-bot-counter"); ?></a>
            </p></td>
        </tr>
      </table>
    </form>
    <p>*
     <?php _e("Tip: You can also monitor misc web browser visits by defining appropriate user-agent string here, for example &quot;Firefox&quot; or &quot;Chrome&quot;","bodi0-bot-counter"); ?>
      .</p>

  </div>
  <br />
<div id="pagerank" style="display:none">
      <?php 
//Nonce field
wp_nonce_field( 'bot-nonce' );
?><form action="javascript:void(0);" name="form-pagerank">
      <table style="margin:0;padding:0; width:800px">
      <tr>
      <td colspan="2">
      <?php _e("Google: The most popular websites have a PageRank of 10, the least have a PageRank of 0", "bodi0-bot-counter"); ?>. <br />

      <?php _e("Alexa: The lower ranking is, the more popular the website is", "bodi0-bot-counter"); ?>.<br />
<?php _e("Statscrop: The higher the ranking is (5 maximum), the more popular the website is", "bodi0-bot-counter"); ?>. 
      </td>
      </tr>
        <tr>
          <td style="width:10%">
          
              <p>
              <?php _e("URL","bodi0-bot-counter"); ?>
              :</p></td>
          <td><p>
              <input type="text" name="rank-url" id="rank-url" value="<?php echo home_url()?>" maxlength="100" size="35"/> 
              <select name="rank-type" id="rank-type">
              <option value="get_pagerank_google"><?php _e("Get Google page rank","bodi0-bot-counter"); ?></option>
              <option value="get_pagerank_alexa"><?php _e("Get Alexa page rank","bodi0-bot-counter"); ?></option>
              <option value="get_pagerank_statscrop"><?php _e("Get Statscrop rank","bodi0-bot-counter"); ?></option>
              </select>
               <input type="button" name="get-ranking" class="button button-primary" value="<?php _e("Get rankings","bodi0-bot-counter")?>" onclick="get_pagerank('rank-url', $('#rank-type').val(), 'rank-holder');"/>
                      
              &nbsp;<a accesskey="c" href="javascript:void(0)" onclick="$('#pagerank').hide()" class="button-secondary cancel"><?php _e("Cancel", "bodi0-bot-counter"); ?></a>
              

              </p>
              </td>
        </tr>
      <tr><td colspan="2">
      <p><?php _e("Rank", "bodi0-bot-counter"); ?> :
      <strong><span id="rank-holder"> </span></strong></p>
      </td></tr>
      
      </table>
      </form>
    <p>*
      <?php _e("Tip: You can also check the page rank of random web site","bodi0-bot-counter"); ?>
      .</p>
  </div>
  <p>&nbsp;</p>
  <p>
    <?php _e("See the complete user agent string list of","bodi0-bot-counter"); ?>
    <a href="http://user-agent-string.info/list-of-ua/bots" target="_blank">
    <?php _e("bots","bodi0-bot-counter"); ?>
    </a><br />
    <?php _e("Remark: Some of the returned data includes GeoLite data created by MaxMind, available from http://www.maxmind.com", "bodi0-bot-counter"); ?>
  </p>
  <?php _e("If you find this plugin useful, I wont mind if you buy me a beer", "bodi0-bot-counter"); ?>
  :
  <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top" style="display:inline-block !important">
    <input type="hidden" name="cmd" value="_s-xclick"/>
    <input type="hidden" name="hosted_button_id" value="LKG7EXVNPJ7EN"/>
    <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!"  style="vertical-align: middle !important; border:0"/>
  </form>
</div>

<?php
defined( 'ABSPATH' ) or exit;
/*
Plugin`s Administration panel
Author: bodi0
Email: budiony@gmail.com
Version: 0.4
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

global $wpdb;

//SQL table name
if (!defined('__TABLE__')) define ('__TABLE__', $wpdb->prefix.'bot_counter'); 

/*****************************************************************************************************************************/
//Reset statistics
if (isset($_GET['bot-counter']) && $_GET['bot-counter'] == 'reset' && ( wp_verify_nonce( $nonce, 'bot-nonce' )) ) {
	$wpdb->query ( $wpdb->prepare('UPDATE '.__TABLE__ .' SET bot_visits = %d, bot_last_visit = NULL ', 0) );

//Error handling
	if (!empty($wpdb->last_error)) { 	$wpdb->print_error(); } 
	else {?> 
	<div id="message" class="updated">
	<p><?php _e("Bot visits Stats", "bodi0-bot-counter") ?> <strong><?php _e(" were reset","bodi0-bot-counter") ?></strong>.</p></div>
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
	<p><?php _e("New bot", "bodi0-bot-counter"); ?> <strong><?php _e("added", "bodi0-bot-counter"); ?></strong>.</p></div>
	
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
	<p><?php _e("The bot`s name", "bodi0-bot-counter"); ?> <strong><?php _e("was updated", "bodi0-bot-counter"); ?></strong>.</p></div>
	
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
	<p><?php _e("The Bot", "bodi0-bot-counter"); ?> <strong><?php _e("was deleted", "bodi0-bot-counter"); ?></strong>.</p></div>
	
<?php 
	} 
}
?>
<style type="text/css">
.geo-info {position: fixed; background: #fff; border:1px solid #ccc;left:75%;width:20%; cursor:pointer;z-index:10000;box-shadow: 0px 3px 5px rgba(0, 0, 0, 0.196) !important}
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
div.wrap div.edit{background-color: #FFFFFF;left: 0px;margin-top: 0px;padding-top: 0px;position: absolute;top: auto;min-width: 690px !important;width: 707px !important;padding-bottom: 0px;padding-left: 10px;margin-left: 4px;margin-bottom: 0px;height: 4em;}
div.wrap div.edit form{margin-top:0.8em;}
</style>

<div class="wrap">
<h2><?php _e("Bot visits counter [Administration]","bodi0-bot-counter"); ?></h2>
<p class="submitbox"><a href="?page=<?php echo $_GET['page']; ?>&amp;bot-counter=reset" class="submitdelete"><?php _e("Reset Statistics", "bodi0-bot-counter"); ?></a></p>
<p><?php _e("The list, by default, is ordered by number of visits, click on arrow to re-order","bodi0-bot-counter"); ?>. </p>
<p class="alignright"> <a href="?page=bodi0-bot-counter" class="alignleft"><?php _e("Refresh statistics", "bodi0-bot-counter"); ?></a>
&nbsp; </p>
<p class="alignright"> <a href="?page=bodi0-bot-counter&amp;bot-download=stats&amp;_wpnonce=<?php echo wp_create_nonce( 'bot-nonce' ) ?>" class="alignleft"><?php _e("Export as XML Spreadsheet","bodi0-bot-counter"); ?></a> &nbsp;|&nbsp;</p>
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
function filter (term, _id, cellNr) {
	var suche = term.value.toLowerCase();
	var table = document.getElementById(_id);
	var ele;
	
	
	for (var r = 1; r < table.rows.length; r++) {
		ele = table.rows[r].cells[cellNr].innerHTML.replace(/<[^>]+>/g,"");
		
		if (ele.toLowerCase().indexOf(suche)>=0 ) {
			table.rows[r].style.display = '';
			}
		else table.rows[r].style.display = 'none';
	}
}
</script>

<table style="width: 50% !important" class="allignleft">
<tr><td><?php _e("Filter by name", "bodi0-bot-counter"); ?>: 
<input name="filter" id="filter" onkeyup="filter(this, 'bot-table', 0)" type="text" placeholder="<?php _e("Type here...","bodi0-bot-counter"); ?>" style="vertical-align:middle;width:200px"/> <a href="javascript:void(0)" onclick="document.getElementById('filter').value=''; filter(document.getElementById('filter'), 'bot-table', 0)"><?php _e("Reset","bodi0-bot-counter"); ?></a>
</td></tr>
</table>
<table class="widefat" style="min-width:690px !important; max-width:720px !important" id="bot-table">
<thead>
<tr>
<th><?php _e("Bot name","bodi0-bot-counter"); ?>  </th>
<th><?php _e("Bot identifier","bodi0-bot-counter"); ?> </th>
<th><?php _e("Visits","bodi0-bot-counter"); ?> </th>
<th><?php _e("Last visit","bodi0-bot-counter"); ?> </th>
<th><?php _e("IP address","bodi0-bot-counter"); ?> </th>
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
	echo '<tr class="search-me"><td><strong>'.$result['bot_name'].'</strong>
	<div class="row-actions"><span class="inline hide-if-no-js"><a class="editinline" 
	href="javascript:void(0)" onclick="$(\'#edit-'.$result['id'].'\').show();"
	title="'. __("Edit this item","bodi0-bot-counter").'">'.__("Edit","bodi0-bot-counter").'</a> | </span><span class="trash"><a class="submitdelete" 
	href="?page='.esc_html($_GET['page']).'&amp;bot=delete&amp;bot-id='.(int)$result['id'].'&amp;_wpnonce='. wp_create_nonce( 'bot-nonce' ).'" >'. __("Delete","bodi0-bot-counter") .'</a></span></div>
	</td><td>'.$result['bot_mark'].'</td><td class="c">'. $result['bot_visits'].
	'</td><td>'. (!empty($result['bot_last_visit'])? $result['bot_last_visit'] : '').'</td>
	<td>'.(!empty($result['ip_address'])? '<a href="javascript:void(0)" 
	onclick="get_geoinfo(\''.$result['ip_address'].'\', \'toggle'.$result['id'].'\', \'content'.$result['id'].'\');">'.$result['ip_address'].'</a>' : '').'</td>';
	echo '<td><div onclick="$(this).hide()" class="geo-info" style="display:none" id="toggle'.$result['id'].'"><div class="inner" id="content'.$result['id'].'"></div><div class="alignright" style="position:absolute;right:10px;top:10px;">x</div>
	</div>
	<div id="edit-'.$result['id'].'" style="display:none;" class="edit">';
?>
  <form method="post" name="editform" action="?page=<?php echo $_GET['page'] ?>">
  <?php  wp_nonce_field( 'bot-nonce' );?>
  <?php _e("New name","bodi0-bot-counter"); ?>: <input type="text" name="bot-name" id="bot-name" value="<?php echo $result['bot_name']?>" maxlength="20"/> (<?php _e("up to 20 characters","bodi0-bot-counter"); ?>)
  <input type="hidden" name="bot-id" value="<?php echo $result['id']?>" />
  <input type="submit" name="submit" class="button" value="<?php _e("Update","bodi0-bot-counter"); ?>"/>
 &nbsp;<a accesskey="c" href="javascript:void(0)" onclick="$('#edit-<?php echo $result['id']?>').hide()" class="button-secondary cancel"><?php _e("Cancel","bodi0-bot-counter"); ?></a>
  </form>
	
	
<?php
echo '</td></tr>'; //End edit 
//Increase counters
$i++; $j = $j + $result['bot_visits'];
}

?>
</tbody>
<tfoot>
<tr><th colspan="6"><strong><?php _e("TOTAL","bodi0-bot-counter"); ?></strong>: <strong><?php echo $i?></strong>, <strong><?php _e("VISITS"); ?></strong>: <strong><?php echo $j;?></strong></th></tr>
</tfoot>
</table>
<br/>
<a href="javascript:void(0)" onclick="jQuery('#add-bot').toggle(200)"><?php _e("Add new Bot","bodi0-bot-counter"); ?></a>

<div id="add-bot" style="display:none">
<form method="post" name="form" action="?page=<?php echo $_GET['page'];?>">
<?php 
//Nonce field
wp_nonce_field( 'bot-nonce' );
?>
<table style="width:100% !important; margin:0;padding:0">
  <tr>
    <td style="width:12%"><p><?php _e("Bot name","bodi0-bot-counter"); ?>:</p></td><td><p><input type="text" name="bot-name" id="bot-name" value="" maxlength="20"/> (<?php _e("up to 20 characters, required","bodi0-bot-counter"); ?>)</p></td>
    </tr>
    <tr>
    <td><p><?php _e("Bot identifier","bodi0-bot-counter"); ?>:</p></td><td><p><input type="text" name="bot-mark" id="bot-mark" value="" maxlength="100"/> (<?php _e("up to 100 characters, required","bodi0-bot-counter"); ?>)</p></td>
  </tr>
  <tr><td colspan="2"><p><input type="submit" name="submit" class="button" value="<?php _e("Add new Bot","bodi0-bot-counter"); ?>"/></p></td></tr>
</table>

 


</form>
<p>* <?php _e("Tip: You can also monitor misc web browser visits by defining appropriate user-agent string here, for example &quot;Firefox&quot; or &quot;Chrome&quot;","bodi0-bot-counter"); ?>.</p>
</div>
<p><?php _e("See the complete user agent string list of","bodi0-bot-counter"); ?> <a href="http://user-agent-string.info/list-of-ua/bots" target="_blank"><?php _e("bots","bodi0-bot-counter"); ?></a></p>
<p><?php _e("Remark: Some of the returned data includes GeoLite data created by MaxMind, available from http://www.maxmind.com", "bodi0-bot-counter"); ?></p>
<?php _e("If you find this plugin useful, I wont mind if you buy me a beer", "bodi0-bot-counter"); ?>:
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top" style="display:inline-block !important">
<input type="hidden" name="cmd" value="_s-xclick"/>
<input type="hidden" name="hosted_button_id" value="LKG7EXVNPJ7EN"/>
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"  style="vertical-align: middle !important"/>
</form>

</div>


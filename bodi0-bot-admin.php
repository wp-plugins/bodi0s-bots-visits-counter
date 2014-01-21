<?php
defined( 'ABSPATH' ) or exit;
/*
Plugin`s Administration panel
Author: bodi0
Email: budiony@gmail.com
Version: 0.3
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

//Reset statistics
if (isset($_GET['bot-counter']) && $_GET['bot-counter'] == 'reset' && ( wp_verify_nonce( $nonce, 'geo-nonce' )) ) {
	$wpdb->query ( $wpdb->prepare('UPDATE '.__TABLE__ .' SET bot_visits = %d, bot_last_visit = NULL ', 0) );

//Error handling
	if (!empty($wpdb->last_error)) { 	$wpdb->print_error(); } 
	else {?> 
	<div id="message" class="updated">
	<p><?php _e("Bot visits Stats", "bodi0-bot-counter") ?> <strong><?php _e(" were reset","bodi0-bot-counter") ?></strong>.</p></div>
<?php 
	}
}	 

//Add new bot
if (isset($_POST['bot-name']) && isset($_POST['bot-mark']) && trim($_POST['bot-name']) !='' && trim($_POST['bot-mark']) !='' 
&& ( wp_verify_nonce( $nonce, 'geo-nonce' )) ) {
	$wpdb->query( $wpdb->prepare("INSERT INTO ".__TABLE__ ." (bot_name, bot_mark, bot_visits, bot_last_visit) 
	VALUES (%s, %s, 0, NULL )", sanitize_text_field($_POST['bot-name']), sanitize_text_field($_POST['bot-mark'])));

//Error handling
	if (!empty($wpdb->last_error)) { 	$wpdb->print_error(); }
	else {?> 
	<div id="message" class="updated">
	<p><?php _e("New Bot", "bodi0-bot-counter"); ?> <strong><?php _e("added", "bodi0-bot-counter"); ?></strong>.</p></div>
	
<?php 
	}
}	 

//Delete bot
if (isset($_GET['bot']) && $_GET['bot']=='delete' && !empty($_GET['bot-id']) && ( wp_verify_nonce( $nonce, 'geo-nonce' )) ) {
	$wpdb->query( $wpdb->prepare("DELETE FROM ".__TABLE__ . " WHERE id = %d", $_GET['bot-id']) );
	
	//Error handling
	if (!empty($wpdb->last_error)) { 	$wpdb->print_error(); }
	else {?> 
	<div id="message" class="updated">
	<p><?php _e("The Bot was deleted", "bodi0-bot-counter"); ?>.</p></div>
	
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
.tablesorter-default thead .sorter-false {
	background-image: none;
	cursor: default;
}
</style>

<div class="wrap">
<h2><?php _e("Bot visits counter [Admin]","bodi0-bot-counter"); ?></h2>
<p><a href="?page=<?php echo $_GET['page']; ?>&amp;bot-counter=reset"><?php _e("Reset Stats", "bodi0-bot-counter"); ?></a></p>
<p><?php _e("The list, by default, is ordered by number of visits, click on arrow to re-order","bodi0-bot-counter"); ?>. </p>
<p class="alignright"><a href="?page=bodi0-bot-counter" class="alignleft"><?php _e("Refresh statistics", "bodi0-bot-counter"); ?></a>
</p>
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
	data: { action: 'get_location_info', ip: ip_address, _wpnonce: '<?php echo wp_create_nonce( 'geo-nonce' ) ?>'
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
	$('#bot-table thead th a').on('click',function(e){
		if($(this).html=='<strong>&uarr;</strong>') ($(this).html('<strong>&darr;</strong>'));
		else $(this).html('<strong>&uarr;</strong>');
		e.preventDefault();
		});


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
<tr><td><?php _e("Filter list", "bodi0-bot-counter"); ?>: 
<input name="filter" id="filter" onkeyup="filter(this, 'bot-table', 1)" type="text" placeholder="Type here..." style="vertical-align:middle;width:200px"/> <a href="javascript:void(0)" onclick="document.getElementById('filter').value=''; filter(document.getElementById('filter'), 'bot-table', 1)">Reset</a>
</td></tr>
</table>
<table class="widefat" style="min-width:690px !important; max-width:720px !important" id="bot-table">
<thead>
<tr>
<th><?php _e("Bot name","bodi0-bot-counter"); ?> <a href="#" class="arrow"><strong>&harr;</strong></a> </th>
<th><?php _e("Bot identifier","bodi0-bot-counter"); ?> <a href="#" class="arrow"></a></th>
<th><?php _e("Visits","bodi0-bot-counter"); ?> <a href="#" class="arrow"></a></th>
<th><?php _e("Last visit","bodi0-bot-counter"); ?> <a href="#" class="arrow"></a></th>
<th><?php _e("IP address","bodi0-bot-counter"); ?> <a href="#" class="arrow"></a></th>
<th class="sorter-false"><?php _e("Action","bodi0-bot-counter"); ?></th>
<th class="sorter-false"></th>
</tr>
</thead>
<tbody>

<?php
//Counter
$i = 0;
//The cycle
foreach($results as $result) {
	echo '<tr><td class="a"><strong>'.$result['bot_name'].'</strong></td><td>'.$result['bot_mark'].'</td><td class="c">'. $result['bot_visits'].
	'</td><td>'. (!empty($result['bot_last_visit'])? $result['bot_last_visit'] : '').'</td>
	<td>'.(!empty($result['ip_address'])? '<a href="javascript:void(0)" 
	onclick="get_geoinfo(\''.$result['ip_address'].'\', \'toggle'.$result['id'].'\', \'content'.$result['id'].'\');">'.$result['ip_address'].'</a>' : '').'</td>
	<td><a href="?page='.$_GET['page'].'&amp;bot=delete&amp;bot-id='.(int)$result['id'].'&amp;_wpnonce='. wp_create_nonce( 'geo-nonce' ).'" >'. __("Delete","bodi0-bot-counter") .'</a></td>';
	echo '<td style="height:20px !important"><div onclick="$(this).hide()" class="geo-info" style="display:none" id="toggle'.$result['id'].'"><div class="inner" id="content'.$result['id'].'"></div><div class="alignright" style="position:absolute;right:10px;top:10px;">x</div>
	</div></td></tr>';
//Increase counter
$i++;
}

?>
</tbody>
<tfoot>
<tr><th colspan="7"><strong><?php _e("TOTAL"); ?></strong>: <strong><?php echo $i?></strong></th></tr>
</tfoot>
</table>
<br/>
<a href="javascript:void(0)" onclick="jQuery('#add-bot').toggle(200)"><?php _e("Add new Bot","bodi0-bot-counter"); ?></a>

<div id="add-bot" style="display:none">
<form method="post" name="form" action="?page=<?php echo $_GET['page'];?>">
<?php 
//Nonce field
wp_nonce_field( 'geo-nonce' );
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
<p>* <?php _e("Tip: You can also monitor misc web browser visits by defining appropriate user-agent string here, for example &quot;Firefox&quot; or &quot;Chrome&quot;"); ?>.</p>
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


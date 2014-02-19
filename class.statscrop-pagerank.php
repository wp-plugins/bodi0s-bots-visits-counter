<?php
/**
Plugin`s Page rank function calls (using the Statscrop queries)
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


if (!class_exists('get_statscrop_rank')) {
class get_statscrop_rank {
		
	/**
	 * Get the rank from alexa for the given domain
	 * 
	 * @param $domain
	 * The domain to search on
	 */
	public function get_rank($domain){
		//Alexa data URL	
		$url = "http://www.statscrop.com/www/".$domain;
		if (function_exists("curl_init")) {
			//Initialize the Curl  
			$ch = curl_init();  
				
			//Set curl to return the data instead of printing it to the browser.  
			curl_setopt($ch, CURLOPT_USERAGENT, "Opera/9.80 (Windows NT 6.1; WOW64) Presto/2.12.388 Version/12.16");
			curl_setopt($ch, CURLOPT_TRANSFERTEXT, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); 
				
			//Set the URL  
			curl_setopt($ch, CURLOPT_URL, $url);  
				
			//Execute the fetch  
			$data = curl_exec($ch);  
				
			//Close the connection  
			curl_close($ch);  
			}
			elseif (!function_exists('curl_init')) {
				$data = file_get_contents($url);
			}
			else {
				$rank = 'N/A';
			}
			if ($data) {
				//Clean HTML
				try {
					$rank = new DOMDocument();
					$rank->strictErrorChecking = false;
					libxml_use_internal_errors(true);
					//Parse the cleaned HTML
					$rank->loadHTML($data);
					
					//Get all spans
					$spans = $rank->getElementsByTagName('em');
					//First array of elements
					foreach ($spans as $span) {
						//Second array of elements
						foreach ($span->attributes as $attr) {
							$page_rank = ($attr->nodeName=="title") ? $attr->nodeValue : '';
						}
					}
				
					if (!empty($page_rank)) echo $page_rank .=  ' &nbsp;<a href="'.$url.'" target="_blank">More...</a>';
					else echo 'N/A';
			}
		
			catch (DOMException $e) {
				echo $e->getMessage();
			}	
		}
		//Return value
		return (!empty($rank)) ? $rank : 'N/A';
	}

}
}

/*Usage
//Create a new object
$alexa = new get_alexa_rank();

//Get the rank for the domain paulund.co.uk
echo "Rank ".$alexa->get_rank("paulund.co.uk");
*/
?>
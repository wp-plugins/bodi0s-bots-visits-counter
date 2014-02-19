<?php
/**
Plugin`s Page rank function calls (using the Alexa queries)
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


if (!class_exists('get_alexa_rank')) {
class get_alexa_rank {
		
	/**
	 * Get the rank from alexa for the given domain
	 * 
	 * @param $domain
	 * The domain to search on
	 */
	public function get_rank($domain){
		//Alexa data URL	
		$url = "http://data.alexa.com/data?cli=10&dat=snbamz&url=".$domain;
		if (function_exists("curl_init")) {
			//Initialize the Curl  
			$ch = curl_init();  
				
			//Set curl to return the data instead of printing it to the browser.  
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,2); 
				
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
				//Parse xml data
				$xml = new SimpleXMLElement($data);  
				//Get popularity node
				$popularity = $xml->xpath("//POPULARITY");
				//Get the Rank attribute
				$rank = (string)$popularity[0]['TEXT']; 
				
				

			}
		//Return value and show more details
		return (!empty($rank)) ? $rank. ' &nbsp;<a href="'.$url.'" target="_blank">More...</a>' : 'N/A';
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
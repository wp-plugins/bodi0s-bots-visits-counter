<?php
/*
Description: Get location data based on IP address, using geoplugin.net website API
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

if (!class_exists('geo_location')) {
class geo_location {
	//
	var $ip='';
	var $client='';
	var $forward='';
	var $remote='';
	//
	function __construct() {
		$this->client  = isset($_SERVER['HTTP_CLIENT_IP'])? $_SERVER['HTTP_CLIENT_IP'] : '';
		$this->forward = isset($_SERVER['HTTP_X_FORWARDED_FOR'])? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
		$this->remote  = isset($_SERVER['REMOTE_ADDR'])? $_SERVER['REMOTE_ADDR'] : '';
	}
	//
	function __destruct () {
		foreach ($this as $key=>$value) {
			unset($this->$key);
		}
	}
	/*
	Return array of city and country from json decoded file at geoplugin.net
	*/
	function getLocationInfoByIp(){
		
		if(filter_var($this->client, FILTER_VALIDATE_IP)){
			$this->ip = $this->client;
		}elseif(filter_var($this->forward, FILTER_VALIDATE_IP)){
			$this->ip = $this->forward;
		}else{
			$this->ip = $this->remote;
		}
		try {
			$curl_handle=curl_init();
			curl_setopt($curl_handle, CURLOPT_HEADER, 0);
			curl_setopt($curl_handle, CURLOPT_URL,"http://www.geoplugin.net/json.gp?ip=".$this->ip);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
			$ip_data = curl_exec($curl_handle);
			curl_close($curl_handle);
			$ip_data = json_decode($ip_data);
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}   
		
		return $ip_data;
		
	} 
}
}

?>
<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Frdlweb;
use ViaThinkSoft\OIDplus\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusConfig;
use ViaThinkSoft\OIDplus\OIDplusObjectTypePlugin;
use ViaThinkSoft\OIDplus\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\OIDplusObject;
use ViaThinkSoft\OIDplus\OIDplusException; 

class OIDplusObjectTypePluginIanaPen extends OIDplusObjectTypePlugin
implements \ViaThinkSoft\OIDplus\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_4 /* whois*Attributes */
{
	public function whoisObjectAttributes($id, &$out){
	 
		$obj = OIDplusIanaPen::parse($id);
		if(!$obj)return;
		$mail = $this->_mail($obj->getData()['email']);
	//	$out[] = [
		//  'name' => 'ra-email',
	//	  'value' =>  $mail,			
	//	];
		$out[] = [
		  'name' => 'ra-org',
		  'value' => $obj->getData()['org'],			
		];
		$out[] = [
		  'name' => 'ra-name',
		  'value' => $obj->getData()['name'],			
		];
		$mailFound = false;
		foreach($out as $index => $o){
		 if(!is_array($o))continue;	
		 if($o['name'] === 'ra'){
			 $out[$index]['value'] =$mail;
			 $mailFound = true;
			 break; 
		 }
		}			
		if(false === $mailFound){
		  $out[] = [
		    'name' => 'ra',
		    'value' => $mail,			
		   ];			
		}		
	}
	public static function getObjectTypeClassName():string {
		return OIDplusIanaPen::class;
	}
	
	public function whoisRaAttributes($email, &$out) {
		// Interface 1.3.6.1.4.1.37476.2.5.2.3.4
		$id = false;
		foreach($out as $o){
		 if($o['name'] === 'query'){
			 $id = $o['value'];
			 break; 
		 }
		}
		if(false === $id)return;
        $obj = OIDplusIanaPen::parse($id);
		if(!$obj)return;
		//$data = (new OIDplusIanaPen(OIDplusIanaPen::root()))->getFetcher()->get($email);
		$data = $obj->getData();
		if(!is_array($data))return;
		$mail = $this->_mail($data['email']);
		//$out[] = [
		//  'name' => 'ra-email',
		//  'value' => $mail,			
		//];
		$out[] = [
		  'name' => 'ra-org',
		  'value' => $data['org'],			
		];
		$out[] = [
		  'name' => 'ra-name',
		  'value' => $data['name'],			
		];
		
		$mailFound = false;
		foreach($out as $index => $o){
		 if(!is_array($o))continue;
		 if($o['name'] === 'ra'){ 
			 $out[$index]['value'] = $mail;
			 $mailFound = true;
			 break; 
		 }
		}		
		
		if(false === $mailFound){
		  $out[] = [
		    'name' => 'ra',
		    'value' => $mail,			
		   ];			
		}
	}
	
	protected function _mail(string $mail){
		//return OIDplusIanaPen::secmail($mail);
		return str_replace('@', ' & ', $mail);
		//return $mail;
	}
}

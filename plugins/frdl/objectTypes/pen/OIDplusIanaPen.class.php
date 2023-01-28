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
use ViaThinkSoft\OIDplus\OIDplusOid;
use ViaThinkSoft\OIDplus\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusConfig;
use ViaThinkSoft\OIDplus\OIDplusObjectTypePlugin;
use ViaThinkSoft\OIDplus\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\OIDplusObject;
use ViaThinkSoft\OIDplus\OIDplusException; 

class OIDplusIanaPen extends OIDplusObject {
	
	//const PREFIX = 'oid:1.3.6.1.4.1.';
	const PREFIX = '1.3.6.1.4.1.';
	
	protected $pen;
    protected $data = null;
    protected $Fetcher = null;

	public function __construct($pen) {
		// No syntax checks
		$this->pen = $pen; 
	}
	
	public function getDotNotation(){
		return rtrim(self::PREFIX.$this->pen, '.');
	}
	
	public function getCanonicalOid() {
		return $this->getDotNotation();
	}
	public static function secmail($emailaddress){
		$email= $emailaddress;           
		$length = strlen($email); 
		for ($i = 0; $i < $length; $i++){
			$obfuscatedEmail .= "&#" . \ord($email[$i]).";";
		} 
		return $obfuscatedEmail;
	}
	
	public function getFetcher() {
		if(null === $this->Fetcher ){
		  if(!class_exists(\Frdlweb\IanaPenListFetcher::class)){
			if(!file_exists(__DIR__.\DIRECTORY_SEPARATOR.'IanaPenListFetcher.class.php')){
				file_put_contents(__DIR__.\DIRECTORY_SEPARATOR.'IanaPenListFetcher.class.php',
			     file_get_contents(
			      'https://raw.githubusercontent.com/frdl/iana-enterprise-numbers-fetcher/main/src/IanaPenListFetcher.class.php'
			    ));
			}
			
			require_once __DIR__.\DIRECTORY_SEPARATOR.'IanaPenListFetcher.class.php';
		  }						
			
		  $this->Fetcher = new \Frdlweb\IanaPenListFetcher();
		}
		return $this->Fetcher;
	}
	
	public function getData() {
		if(null === $this->data ){ 
			$this->data = !$this->pen || $this->pen === self::root() ? false : $this->getFetcher()->get($this->pen);
		}
		return $this->data;
	}

	public static function parse($node_id) {
		if(is_numeric($node_id)){
			$node_id = self::ns().':'.$node_id;
		}
		@list($namespace, $pen) = explode(':', $node_id, 2);
		if ($pen !== 'oid:1.3.6.1.4.1' 
			&& $pen !== self::root() 
			&& ($namespace !== self::ns() && !is_numeric($pen) )) return false;
		//$pen = $pen !== self::root() && $pen !== 'oid:1.3.6.1.4.1' && $namespace === self::ns() && is_numeric($pen) ? intval($pen) : $pen;
 
		$object = new self($pen);
		if( $pen && $pen !== self::root()  && 
		   !$object->isRoot() && !is_array($object->getData()) )return false;
		
	  return $object;
	}

	public static function objectTypeTitle() {
		return _L('IANA Enterprise Numbers');
	}

	public static function objectTypeTitleShort() {
		return _L('PEN');
	}

	public static function ns() {
	//return 'iana-pen';
		return 'pen';
	}

	public static function root() {
		return self::ns().':';
		//return 'oid:1.3.6.1.4.1';
	}

	public function isRoot() {
		// Ärghühhh????// 
	 
		return// 0===0 ||
			is_array($this->getData()) || 
			$this->nodeId(true) === self::root() 
			 // || $this->pen === self::ns()
				// || $this->pen === self::ns().':' 
				// 	 || $this->pen === 0 
				// 	 || $this->pen === '0'
					//  || $this->pen === 'oid:1.3.6.1.4.1'					
					;
	}
	

	
	public static function exists(string $id) {
		return $id === self::root() || is_array((new self(ltrim($id,self::ns().':')))->getData());
	}
	/**/
	public function getParent()  {
		//everything is "root" here, self reference!!!
		return new OIDplusOid( $this->getDotNotation() );
		//return new OIDplusOid( rtrim('1.3.6.1.4.1.'.$this->pen, '.')	);
		//return $this; 
	}
	public function nodeId($with_ns=true) {
		return $with_ns ? self::root().$this->pen : $this->pen;
	}

	public function addString($str) {
		throw new \Exception('You are not allowed to add a node on this non-authoritive OID-Service. '
		.'You must add this PEN as OID to the system first.');
	}

	public function crudShowId(OIDplusObject $parent) {
		if ($parent->isRoot()) {
			return substr($this->nodeId(), strlen($parent->nodeId()));
		}else{
			return substr($this->nodeId(), strlen($parent->nodeId())+1);
		}
	}

	public function jsTreeNodeName(OIDplusObject $parent = null) {
		if ($parent == null) return $this->objectTypeTitle();
		if ($parent->isRoot()) {
			return substr($this->nodeId(), strlen($parent->nodeId()));
		} else {
			return substr($this->nodeId(), strlen($parent->nodeId())+1);
		}
	}

	public function defaultTitle() {
		return $this->nodeId(true) === self::root() ? self::objectTypeTitle() : $this->getData()['org'].' ('.$this->getCanonicalOid().')';
	}
	public function getTitle() {
		return $this->defaultTitle();
	}
	public function getDescription() {
		return $this->getCanonicalOid().' is a Private Enterprise Number assigned by IANA for '.$this->getData()['org']
			.', it should be listed at https://www.iana.org/assignments/enterprise-numbers/enterprise-numbers .';
	}	
	public function isLeafNode() {
		return true;
	}
	
	public function getRaMail() {
		return $this->getData()['email'];
	}
	
	public function getContentPage(&$title, &$content, &$icon) {
		$icon = file_exists(__DIR__.'/img/main_icon.png') ? OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon.png' : '';
		
	      $title = $this->getTitle();
   
		  //  $content.='Re: '.$this->getRaMail();
		   $content.= '<p>'.$this->getDescription().'</p>';
		
		  foreach($this->getData() as $k => $v){
			  if('email'===$k){
				//$v = str_replace('@', ' & ', $v);  
				  $v = self::secmail($v);
			  }
			  $content.= '<p><legend>'.ucfirst($k).'</legend>'.$v.'</p>'; 
		  }
			
		  $weid = \Frdl\Weid\WeidOidConverter::oid2weid($this->getDotNotation());
		  $content.= '<p><legend>WEID</legend>'.$weid.'</p>'; 
		
		
	 		if ( $this->pen === self::root()  ) {
				if (OIDplus::authUtils()->isAdminLoggedIn()) {
					$content .= '<h2>'._L('Manage root objects').'</h2>';
					$content .= _L('@ToDo PEN Root/List...');
				} else {
					$content .= '<h2>'._L('Available PENs').'</h2>';
				}
				$content .= '%%CRUD%%';
		 	}
		
		/*
		if ($this->isRoot()) {
			$title = self::objectTypeTitle();

			$content  = _L('@ToDo PEN Root/List...');

			if (!$this->isLeafNode()) {
				if (OIDplus::authUtils()->isAdminLoggedIn()) {
					$content .= '<h2>'._L('Manage root objects').'</h2>';
				} else {
					$content .= '<h2>'._L('Available PENs').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		} else {
			$title = $this->getTitle();

			$content = '<h2>'._L('Description').'</h2>%%DESC%%'; // TODO: add more meta information about the object type

               $content .= print_r($this->getData(), true);

			if (!$this->isLeafNode()) {
				if ($this->userHasWriteRights()) {
					$content .= '<h2>'._L('Create or change subordinate objects').'</h2>';
				} else {
					$content .= '<h2>'._L('Subordinate objects').'</h2>';
				}
				$content .= '%%CRUD%%';
			}
		}
		*/
	}
/* */
	
	public function userHasWriteRights($ra_email=null) {			
		return false;		
	}	
    	
	public function userHasParentalWriteRights($ra_email=null) {			
		return false;		
	}	
    
	public function userHasReadRights($ra_email=null) {	 
		return true;     
	}
	
	public function isConfidential() {     
		return false;    
	}
	
	public function getRa() {
		return new OIDplusRA($this->getRaMail());
	}
	
 	 public static function findFitting(string $id) {
		$obj = self::parse($id);
		if (!$obj) return false; 
		return $obj;   
	 } 

	public static function treeIconFilename($mode) {
		return 'img/'.$mode.'_icon16.png';
	}
	//public function distance($to) {
	//	return null; // not implemented
	//}	
}

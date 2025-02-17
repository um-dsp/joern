<?php

$Props = array();
$PropGroups = array();
$List = array();

$propfile = 'data/properties.dta';
$src = file($propfile) or die('CSS syntax definition cannot be read.');

$openProp = false;
$openList = false;
$openName = false;
$openGroup = false;
$openValue = false;
$openDefVal = false;
$openValid = false;
$openPredef = false;
$listName = '';
$ln = 0;

while ( list(,$l) = each($src) ) {
	$ln++;
	$l = trim($l);
	if ($l!='') {
		if ($l=='<prop>') {
			$openProp = true;
			$prop = new CSSProp;
			}
		elseif ($l=='</prop>') {
			$openProp = false;
			$Props[$prop->name] = $prop;
			$PropGroups[$prop->group][] = $prop;
			}
		elseif ($l=='<list>') $openList = true;
		elseif ($l=='</list>') $openList = false;
		elseif ($l=='<name>') $openName = true;
		elseif ($l=='</name>') $openName = false;
		elseif ($l=='<group>') $openGroup = true;
		elseif ($l=='</group>') $openGroup = false;
		elseif ($l=='<value>') $openValue = true;
		elseif ($l=='</value>') $openValue = false;
		elseif ($l=='<valid>') $openValid = true;
		elseif ($l=='</valid>') $openValid = false;
		elseif ($l=='<default>') $openDefVal = true;
		elseif ($l=='</default>') $openDefVal = false;
		elseif ($l=='<predef>') $openPredef = true;
		elseif ($l=='</predef>') $openPredef = false;
		elseif ($openName) {
			if ($openProp) $prop->addName($l);
			elseif ($openList) $listName = $l;
			}
		elseif ($openGroup) $prop->addGroup($l);
		elseif ($openValue) {
			if ($openProp) $prop->addValue($l);
			elseif ($openList) {
				$List[$listName][] = $l;
				}
			}
		elseif ($openValid) $prop->addValid($l);
		elseif ($openDefVal) $prop->addDefVal($l);
		elseif ($openPredef) $prop->addPredef($l);
		}
	}


class CSSProp {

	var $name;
	var $group;
	var $value;
	var $valid;
	var $defVal;
	var $predef;
	
	function addName($n) {
		$this->name .= $n;
		}
	
	function addGroup($n) {
		$this->group .= $n;
		}
	
	function addValue($n) {
		$this->value = $n;
		}
	
	function addValid($n) {
		$this->valid[] = $n;
		}
	
	function addDefVal($n) {
		$this->defVal = $n;
		}
	
	function addPredef($n) {
		$this->predef[] = $n;
		}
	
	function getValueList() {
		global $List;
		while ( list(,$v) = each($this->valid) ) {
			if (!preg_match("/^\[.+\]$/",$v)) {
				if (preg_match("/^@(.+)$/",$v,$match)) {
					if (count($vals)>0 && current($vals)!='---') $vals[] = '---';
					$vals = array_merge($vals,$List[$match[1]]);
					$vals[] = '---';
					}
				else $vals[] = $v;
				}
			}
		if (count($vals)>0 && current($vals)!='---') $vals[] = '---';
		if (count($this->predef)>0) {
			$vals = array_merge($vals,$this->predef);
			$vals[] = '---';
			}
		return $vals;
		}	

	function getDefValue() {
		return $this->defVal;
		}
	}

?>
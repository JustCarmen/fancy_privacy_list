<?php
// Classes and libraries for module system
//
// webtrees: Web based Family History software
// Copyright (C) 2011 webtrees development team.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// $Id: module.php 8218 2010-05-09 07:39:07Z greg $

if (!defined('WT_WEBTREES')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

class fancy_privacy_list_WT_Module extends WT_Module implements WT_Module_Config {

    // Extend WT_Module
    public function getTitle() {
        return WT_I18N::translate('Fancy Privacy List');
    }

    // Extend WT_Module
    public function getDescription() {
        return WT_I18N::translate('This is a module for site admins only. With this module you easily can see on which persons you have set a custom restriction.');
    }	
	
    // Extend WT_Module
    public function modAction($mod_action) {
        switch ($mod_action) {
            case 'admin_config':
                $controller = new WT_Controller_Page;				
                $controller
                    ->requireAdminLogin()
                    ->setPageTitle($this->getTitle())
					->pageHeader()
					->addExternalJavascript(WT_JQUERY_DATATABLES_URL)
					->addInlineJavascript('
						jQuery("head").append("<style>table tr{cursor: pointer}table td{padding-left:10px;padding-right:10px}table th{padding:5px 10px}</style>");
						jQuery.fn.dataTableExt.oSort["unicode-asc"  ]=function(a,b) {return a.replace(/<[^<]*>/, "").localeCompare(b.replace(/<[^<]*>/, ""))};
						jQuery.fn.dataTableExt.oSort["unicode-desc" ]=function(a,b) {return b.replace(/<[^<]*>/, "").localeCompare(a.replace(/<[^<]*>/, ""))};
						var oTable = jQuery("table#privacy_list").dataTable({
							"sDom": \'<"H"pf<"dt-clear">irl>t<"F"pl>\',
							'.WT_I18N::datatablesI18N().',
							"bJQueryUI": true,
							"bAutoWidth":false,
							"bProcessing": true,
							"bFilter": true,						
							"aoColumns": [
								/* 0-ID */    			{"bSortable": true, "sWidth": "5%"},
								/* 1-Surname */			{"iDataSort": 5, "sWidth": "15%"},
								/* 2-Given */    		{"bSortable": true, "sWidth": "20%"},
								/* 3-Restrictions */	{"bSortable": true, "sWidth": "10%"},
								/* 4-Reason */    		{"bSortable": true, "sWidth": "50%"},
								/* 5-SURN */    		{"sType": "unicode", "bVisible": false}
							],
							"aaSorting": [['.('5, "asc"').']],
							"iDisplayLength": 30,
							"sPaginationType": "full_numbers",
							"fnDrawCallback": function() {
								jQuery("tbody tr").click(function(){
									var obj	 = jQuery(this);
									var xref = obj.attr("id");
									if(jQuery("tr#details-" + xref).length > 0) {
										jQuery("tr#details-" + xref).remove();							
									}
									else {
										jQuery.ajax({
											url: "module.php?mod='. $this->getName().'&mod_action=load_data&id=" + xref,
											type: "GET",
											success: function(data) {
												obj.after("<tr id=\"details-" + xref + "\" class=\"" + obj.attr("class") + "\"><td colspan=\"6\">" + data);
											}
										});	
									}
								});
							}
						});							
					');
					
					$html = '					
					<div id="fancy_restrictions_list"><h2>'.$this->getTitle().'</h2>
					<table id="privacy_list" style="width:100%">
						<thead>
							<th><span style="float:left">'.WT_I18N::translate('ID').'</span></th>
							<th><span style="float:left">'.WT_I18N::translate('Surname').'</span></th>
							<th><span style="float:left">'.WT_I18N::translate('Given').'</span></th>
							<th><span style="float:left">'.WT_I18N::translate('Restrictions').'</span></th>
							<th><span style="float:left">'.WT_I18N::translate('Reason').'</span></th>
							<th>SURN</th>
						</thead><tbody>';						
						$names = $this->getAllNames();						
						foreach($names as $name) {
							$xref = $name['ID'];
							$record = WT_Individual::getInstance($xref);	
							$settings = $this->getPrivacySettings($record);	
							
							$html .= '
								<tr id="'.$xref.'">
									<td>'.$xref.'</td>
									<td>'.$name['SURNAME'].'</td>
									<td>'.$name['GIVN'].'</td>
									<td>'.$settings['PRIV'].'</td>
									<td>'.$settings['TEXT'].'</td>
									<td>'./* hidden by datables code */ $name['SURN'].'</td>			
								</tr>';	
						}
						'</tbody></table>';
						echo $html;
			exit;
			case 'load_data':				
				// Generate an AJAX response for datatables to load expanded row
				$xref	= WT_Filter::get('id');
				$record = WT_Individual::getInstance($xref);
				Zend_Session::writeClose();
				header('Content-type: text/html; charset=UTF-8');
				echo '<pre>'.$this->getRecordData($record).'</pre>';
			exit;
			
    	}
    }

    // Implement WT_Module_Config
    public function getConfigLink() {
        return 'module.php?mod=' . $this->getName() . '&amp;mod_action=admin_config';
    }
	
	// Get a list of all the individuals for the choosen gedcom
	private function getAllNames() {
		
		$sql = "SELECT SQL_CACHE n_id, n_surn, n_surname, n_givn FROM `##name` WHERE n_file=? AND n_type=? AND n_surn IS NOT NULL ORDER BY n_sort ASC"; 
		$args = array(WT_GED_ID, 'NAME');
		
		foreach (WT_DB::prepare($sql)->execute($args)->fetchAll() as $row) {
			$list[] = array(
				'ID' 		=> $row->n_id,
				'SURN' 		=> $row->n_surn,
				'SURNAME' 	=> $row->n_surname,
				'GIVN' 		=> $row->n_givn
			);
		}
		return $list;
	}
	
	// This is a copy, with modifications, of the function isDead() in /library/WT/Individual.php
	// It is VERY important that the parameters used in both are identical.
	private function getPrivacySettings($record) {
		global $MAX_ALIVE_AGE;
		
		// First check if this record has a RESN
		$facts = $record->getFacts();		
		foreach ($facts as $fact) {
			if ($fact->getTag() == 'RESN' && $fact->getValue() != 'locked') {
				$RESN=array(
					'none'        => WT_I18N::translate('Show to visitors'), // Not valid GEDCOM, but very useful
					'privacy'     => WT_I18N::translate('Show to members'),
					'confidential'=> WT_I18N::translate('Show to managers')
				);
				foreach($RESN as $key => $value) {
					if($key == $fact->getValue()) {
						$settings = array(
							'PRIV' => WT_I18N::translate($fact->getValue()),
							'TEXT' => WT_I18N::translate('This record has a custom privacy setting: %s', $value)
						);
						return $settings;
					}
				}
			}
		}
		
		// "1 DEAT Y" or "1 DEAT/2 DATE" or "1 DEAT/2 PLAC"
		if (preg_match('/\n1 (?:'.WT_EVENTS_DEAT.')(?: Y|(?:\n[2-9].+)*\n2 (DATE|PLAC) )/', $record)) {
			$settings = array(
				'PRIV' => WT_I18N::translate('None'),
				'TEXT' => WT_I18N::translate('Death is recorded with an unknown date.')
			);
			return $settings;
		}		
		// If any event occured more than $MAX_ALIVE_AGE years ago, then assume the individual is dead
		if (preg_match_all('/\n2 DATE (.+)/', $record->getGedcom(), $date_matches)) {
			foreach ($date_matches[1] as $date_match) {
				$date=new WT_Date($date_match);
				if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365*$MAX_ALIVE_AGE) {
					$settings = array(
						'PRIV' => WT_I18N::translate('None'),
						'TEXT' => WT_I18N::translate('An event occurred in this person\'s life more than %s years ago.', $MAX_ALIVE_AGE)
					);
					return $settings;
				}
			}
			// The individual has one or more dated events.  All are less than $MAX_ALIVE_AGE years ago.
			// If one of these is a birth, the individual must be alive.
			if (preg_match('/\n1 BIRT(?:\n[2-9].+)*\n2 DATE /', $record->getGedcom())) {
				$settings = array(
					'PRIV' => WT_I18N::translate('Living'),
					'TEXT' => 'According to the privacy settings this person is alive.'
				);
				return $settings;
			}
		}
		
		// If we found no conclusive dates then check the dates of close relatives.

		// Check parents (birth and adopted)
		foreach ($record->getChildFamilies(WT_PRIV_HIDE) as $family) {
			foreach ($family->getSpouses(WT_PRIV_HIDE) as $parent) {
				// Assume parents are no more than 45 years older than their children
				preg_match_all('/\n2 DATE (.+)/', $parent->getGedcom(), $date_matches);
				foreach ($date_matches[1] as $date_match) {
					$date=new WT_Date($date_match);
					if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365*($MAX_ALIVE_AGE+45)) {
						$settings = array(
							'PRIV' => WT_I18N::translate('None'),
							'TEXT' => WT_I18N::translate('A parent with a birth date of %s is more than 45 years older than this person.', $date->Display())
						);
						return $settings;
					}				
				}
			}
		}		
		// Check spouses
		foreach ($record->getSpouseFamilies(WT_PRIV_HIDE) as $family) {
			preg_match_all('/\n2 DATE (.+)/', $family->getGedcom(), $date_matches);
			foreach ($date_matches[1] as $date_match) {
				$date=new WT_Date($date_match);
				// Assume marriage occurs after age of 10
				if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365*($MAX_ALIVE_AGE-10)) {
					$settings = array(
						'PRIV' => WT_I18N::translate('None'),
						'TEXT' => WT_I18N::translate('A marriage with a date of %s suggests they were born at least 10 years earlier than that.', $date->Display())
					);
					return $settings;
				}
			}
			// Check spouse dates
			$spouse = $family->getSpouse($record, WT_PRIV_HIDE);
			if ($spouse) {
				preg_match_all('/\n2 DATE (.+)/', $spouse->getGedcom(), $date_matches);
				foreach ($date_matches[1] as $date_match) {
					$date = new WT_Date($date_match);
					// Assume max age difference between spouses of 40 years
					if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365*($MAX_ALIVE_AGE+40)) {
						$settings = array(
							'PRIV' => WT_I18N::translate('None'),
							'TEXT' => WT_I18N::translate('A spouse with a date of %s is more than 40 years older than this person.', $date->Display())
						);
						return $settings;
					}
				}
			}
			// Check child dates
			foreach ($family->getChildren(WT_PRIV_HIDE) as $child) {
				preg_match_all('/\n2 DATE (.+)/', $child->getGedcom(), $date_matches);
				// Assume children born after age of 15
				foreach ($date_matches[1] as $date_match) {
					$date=new WT_Date($date_match);
					if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365*($MAX_ALIVE_AGE-15)) {
						$settings = array(
							'PRIV' => WT_I18N::translate('None'),
							'TEXT' => WT_I18N::translate('A child with a birth date of %s suggests this person was born at least 15 years earlier than that.', $date->Display())
						);
						return $settings;
					}
				}
				// Check grandchildren
				foreach ($child->getSpouseFamilies(WT_PRIV_HIDE) as $child_family) {
					foreach ($child_family->getChildren(WT_PRIV_HIDE) as $grandchild) {
						preg_match_all('/\n2 DATE (.+)/', $grandchild->getGedcom(), $date_matches);
						// Assume grandchildren born after age of 30
						foreach ($date_matches[1] as $date_match) {
							$date=new WT_Date($date_match);
							if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365*($MAX_ALIVE_AGE-30)) {
								$settings = array(
									'PRIV' => WT_I18N::translate('None'),
									'TEXT' => WT_I18N::translate('A grandchild with a birth date of %s suggests this person was born at least 30 years earlier than that.', $date->Display())
								);
								return $settings;
							}
						}
					}
				}
			}
		}
		$settings = array(
			'PRIV' => WT_I18N::translate('Presumably alive'),
			'TEXT' => WT_I18N::translate('This person is presumed to be living because the privacy settings of this person could not be calculated.')
		);
		return $settings;
	}	
	
	private function getRecordData($record) {
		$lines = preg_split('/[\n]+/', $record->getGedcom());
		$gedrec = implode("\n", $lines);		
		return preg_replace(
			"/@([^#@\n]+)@/m",
			'<a href="#" onclick="return edit_raw(\'\\1\');">@\\1@</a>',
			$gedrec
		);
	}
}


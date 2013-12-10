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

	public function __construct() {
		// Load any local user translations
		if (is_dir(WT_MODULES_DIR.$this->getName().'/language')) {
			if (file_exists(WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.mo')) {
				Zend_Registry::get('Zend_Translate')->addTranslation(
					new Zend_Translate('gettext', WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.mo', WT_LOCALE)
				);
			}
			if (file_exists(WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.php')) {
				Zend_Registry::get('Zend_Translate')->addTranslation(
					new Zend_Translate('array', WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.php', WT_LOCALE)
				);
			}
			if (file_exists(WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.csv')) {
				Zend_Registry::get('Zend_Translate')->addTranslation(
					new Zend_Translate('csv', WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.csv', WT_LOCALE)
				);
			}
		}
	}

    // Extend WT_Module
    public function getTitle() {
        return WT_I18N::translate('Fancy Privacy List');
    }

    // Extend WT_Module
    public function getDescription() {
        return WT_I18N::translate('This is a module for site admins only. With this module you easily can see the privacy settings for each individual in your tree.');
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
						jQuery("head").append("<style>tr{cursor: pointer}td{padding-left:10px;padding-right:10px}th{padding:5px 10px}.gedcom-data{cursor:default}</style>");
						jQuery.fn.dataTableExt.oSort["unicode-asc"  ]=function(a,b) {return a.replace(/<[^<]*>/, "").localeCompare(b.replace(/<[^<]*>/, ""))};
						jQuery.fn.dataTableExt.oSort["unicode-desc" ]=function(a,b) {return b.replace(/<[^<]*>/, "").localeCompare(a.replace(/<[^<]*>/, ""))};

						var oTable;
						// open a row with the gedcom data of this person when the row is clicked on
						jQuery("#privacy_list tbody tr").click( function () {
							var xref = jQuery(this).attr("id");
							var rowClass = jQuery(this).attr("class");
							var nTr = this;
							if (oTable.fnIsOpen(nTr)) {
								oTable.fnClose(nTr);
							} else {
								jQuery.get("module.php?mod='. $this->getName().'&mod_action=load_data&id=" + xref, function(data) {
									oTable.fnOpen(nTr, data, "gedcom-data " + rowClass);
								});
							}
						});

						oTable = jQuery("table#privacy_list").dataTable({
							"sDom": \'<"H"pf<"dt-clear">irl>t<"F"pl>\',
							'.WT_I18N::datatablesI18N().',
							"bJQueryUI": true,
							"bAutoWidth":false,
							"bProcessing": true,
							"bFilter": true,
							"aoColumns": [
								/* 0-ID */    			{"iDataSort": 7, "sWidth": "5%"},
								/* 1-Surname */			{"iDataSort": 6, "sWidth": "15%"},
								/* 2-Given name */ 		{"bSortable": true, "sWidth": "15%"},
								/* 3-Status */			{"bSortable": true, "sWidth": "15%"},
								/* 4-Privacy settings */{"bSortable": true, "sWidth": "15%"},
								/* 5-Explanation */ 	{"bSortable": true, "sWidth": "35%"},
								/* 6-SURN */    		{"sType": "unicode", "bVisible": false},
								/* 7-NUMBER */    		{"bVisible": false}
							],
							"aaSorting": [['.('6, "asc"').'], ['.('7, "asc"').']],
							"iDisplayLength": 30,
							"sPaginationType": "full_numbers"
						});
					');

					global $HIDE_LIVE_PEOPLE, $REQUIRE_AUTHENTICATION;
					$REQUIRE_AUTHENTICATION ? $auth = '<br>('.WT_I18N::translate('registered users only').')' : $auth = '';

					$html = '
					<h2>'.$this->getTitle().'</h2>
					<table id="privacy_list" style="width:100%">
						<thead>
							<th><span style="float:left">'.WT_I18N::translate('ID').'</span></th>
							<th><span style="float:left">'.WT_I18N::translate('Surname').'</span></th>
							<th><span style="float:left">'.WT_I18N::translate('Given name').'</span></th>
							<th><span style="float:left">'.WT_I18N::translate('Status').'</span></th>
							<th><span style="float:left">'.WT_I18N::translate('Privacy settings').'</span></th>
							<th><span style="float:left">'.WT_I18N::translate('Explanation').'</span></th>
							<th>SURN</th>
							<th>NUMBER</th>
						</thead><tbody>';
						$names = $this->getAllNames();
						foreach($names as $name) {
							$xref = $name['ID'];
							$record = WT_Individual::getInstance($xref);
							$settings = $this->getPrivacySettings($record);
							$privacy = $HIDE_LIVE_PEOPLE ? $settings['PRIV'] : WT_I18N::translate('Show to visitors').$auth;
							$i = substr($xref, 1);
							$html .= '
								<tr id="'.$xref.'">
									<td>'.$xref.'</td>
									<td>'.$name['SURNAME'].'</td>
									<td>'.$name['GIVN'].'</td>
									<td>'.$settings['STAT'].'</td>
									<td>'.$privacy.'</td>
									<td>'.$settings['TEXT'].'</td>
									<td>'./* hidden by datables code */ $name['SURN'].'</td>
									<td>'./* hidden by datables code */ $i.'</td>
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
		global $MAX_ALIVE_AGE,$SHOW_DEAD_PEOPLE, $KEEP_ALIVE_YEARS_BIRTH, $KEEP_ALIVE_YEARS_DEATH, $SHOW_LIVING_NAMES;
		$SHOW_EST_LIST_DATES = get_gedcom_setting(WT_GED_ID, 'SHOW_EST_LIST_DATES');
		$SHOW_LIVING_NAMES ? $show_name = ' ('.WT_I18N::translate('the name is displayed').')' : $show_name = '';

		$ACCESS_LEVEL=array(
			WT_PRIV_PUBLIC=>WT_I18N::translate('Show to visitors'),
			WT_PRIV_USER  =>WT_I18N::translate('Show to members'),
			WT_PRIV_NONE  =>WT_I18N::translate('Show to managers'),
			WT_PRIV_HIDE  =>WT_I18N::translate('Hide from everyone')
		);

		$keep_alive = false; $keep_alive_birth = false; $keep_alive_death = false;
		if ($KEEP_ALIVE_YEARS_BIRTH) {
			preg_match_all('/\n1 (?:'.WT_EVENTS_BIRT.').*(?:\n[2-9].*)*(?:\n2 DATE (.+))/', $record->getGedcom(), $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$date=new WT_Date($match[1]);
				if ($date->isOK() && $date->gregorianYear()+$KEEP_ALIVE_YEARS_BIRTH > date('Y')) {
					$keep_alive_birth = true;
				}
			}
		}
		if ($KEEP_ALIVE_YEARS_DEATH) {
			preg_match_all('/\n1 (?:'.WT_EVENTS_DEAT.').*(?:\n[2-9].*)*(?:\n2 DATE (.+))/', $record->getGedcom(), $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$date=new WT_Date($match[1]);
				if ($date->isOK() && $date->gregorianYear()+$KEEP_ALIVE_YEARS_DEATH > date('Y')) {
					$keep_alive_death = true;
					break;
				}
			}
		}
		if($keep_alive_birth == true || $keep_alive_death == true) $keep_alive = true;

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
							'STAT' => ucfirst(WT_I18N::translate($fact->getValue())),
							'TEXT' => WT_I18N::translate('This record has a custom privacy setting.'),
							'PRIV' => WT_I18N::translate($value)
						);
						return $settings;
					}
				}
			}
		}

		// Check if this person has a recorded death date.
		if ($death_dates=$record->getAllDeathDates()) {
			$dates = '';
			foreach ($death_dates as $key => $date) {
				if ($key) {
					$dates .= ' | ';
				}
				$dates .= $date->Display();
			}

			$keep_alive_msg = '';
			if($keep_alive_death == true) {
				$keep_alive_msg  = ' '.WT_I18N::translate  /* I18N: %s is a number */ ('That is less than %s years ago.', $KEEP_ALIVE_YEARS_DEATH).' ';
			}
			else {
				if($keep_alive_birth == true) $keep_alive_msg  = ' '.WT_I18N::translate /* I18N: %s is a number */ ('This person was born less then %s years ago.', $KEEP_ALIVE_YEARS_BIRTH);
			}
			$settings = array(
				'STAT' => WT_I18N::translate('Death'),
				'TEXT' => WT_I18N::translate/* I18N: %s is date of death */ ('Died: %s', $dates).'.'.$keep_alive_msg,
				'PRIV' => $keep_alive ?  WT_I18N::translate('Private') : $ACCESS_LEVEL[$SHOW_DEAD_PEOPLE]
			);
			return $settings;
		}

		if (!$record->getEstimatedDeathDate() && $SHOW_EST_LIST_DATES) {
			$settings = array(
				'STAT' => WT_I18N::translate('Presumed death'),
				'TEXT' => WT_I18N::translate /* I18N: %s is a date */ ('An estimated death date has been calculated as %s', $record->getEstimatedDeathDate()->Display()).'.'.$keep_alive_msg,
				'PRIV' => $keep_alive ?  WT_I18N::translate('Private') : $ACCESS_LEVEL[$SHOW_DEAD_PEOPLE]
			);
			return $settings;
		}

		// "1 DEAT Y" or "1 DEAT/2 DATE" or "1 DEAT/2 PLAC"
		if (preg_match('/\n1 (?:'.WT_EVENTS_DEAT.')(?: Y|(?:\n[2-9].+)*\n2 (DATE|PLAC) )/', $record)) {
			$settings = array(
				'STAT' => WT_I18N::translate('Death'),
				'TEXT' => WT_I18N::translate('Death is recorded with an unknown date.'),
				'PRIV' => $ACCESS_LEVEL[$SHOW_DEAD_PEOPLE]
			);
			return $settings;
		}
		// If any event occured more than $MAX_ALIVE_AGE years ago, then assume the individual is dead
		if (preg_match_all('/\n2 DATE (.+)/', $record->getGedcom(), $date_matches)) {
			foreach ($date_matches[1] as $date_match) {
				$date=new WT_Date($date_match);
				if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365*$MAX_ALIVE_AGE) {
					$settings = array(
						'STAT' => WT_I18N::translate('Presumed death'),
						'TEXT' => WT_I18N::translate /* %s is a number */('An event occurred in this person\'s life more than %s years ago.', $MAX_ALIVE_AGE),
						'PRIV' => $ACCESS_LEVEL[$SHOW_DEAD_PEOPLE]
					);
					return $settings;
				}
			}
			// The individual has one or more dated events.  All are less than $MAX_ALIVE_AGE years ago.
			// If one of these is a birth, the individual must be alive.
			if (preg_match('/\n1 BIRT(?:\n[2-9].+)*\n2 DATE /', $record->getGedcom())) {
				$settings = array(
					'STAT' => WT_I18N::translate('Living'),
					'TEXT' => WT_I18N::translate('According to the privacy settings this person is alive.'),
					'PRIV' => WT_I18N::translate('Private').$show_name
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
							'STAT' => WT_I18N::translate('Presumed death'),
							'TEXT' => WT_I18N::translate('A parent with a birth date of %s is more than 45 years older than this person.', $date->Display()),
							'PRIV' => $ACCESS_LEVEL[$SHOW_DEAD_PEOPLE]
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
						'STAT' => WT_I18N::translate('Presumed death'),
						'TEXT' => WT_I18N::translate('A marriage with a date of %s suggests they were born at least 10 years earlier than that.', $date->Display()),
						'PRIV' => $ACCESS_LEVEL[$SHOW_DEAD_PEOPLE]
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
							'STAT' => WT_I18N::translate('Presumed death'),
							'TEXT' => WT_I18N::translate('A spouse with a date of %s is more than 40 years older than this person.', $date->Display()),
							'PRIV' => $ACCESS_LEVEL[$SHOW_DEAD_PEOPLE]
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
							'STAT' => WT_I18N::translate('Presumed death'),
							'TEXT' => WT_I18N::translate('A child with a birth date of %s suggests this person was born at least 15 years earlier than that.', $date->Display()),
							'PRIV' => $ACCESS_LEVEL[$SHOW_DEAD_PEOPLE]
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
									'STAT' => WT_I18N::translate('Presumed death'),
									'TEXT' => WT_I18N::translate('A grandchild with a birth date of %s suggests this person was born at least 30 years earlier than that.', $date->Display()),
									'PRIV' => $ACCESS_LEVEL[$SHOW_DEAD_PEOPLE]
								);
								return $settings;
							}
						}
					}
				}
			}
		}
		$settings = array(
			'STAT' => WT_I18N::translate('Presumably still alive'),
			'TEXT' => WT_I18N::translate('This person is assumed to be alive because the privacy settings of this person could not be calculated.'),
			'PRIV' => WT_I18N::translate('Private').$show_name
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


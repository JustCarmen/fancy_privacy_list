<?php
/*
 * Fancy Privacy List Module
 *
 * webtrees: Web based Family History software
 * Copyright (C) 2014 webtrees development team.
 * Copyright (C) 2014 JustCarmen.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

use WT\Auth;

class fancy_privacy_list_WT_Module extends WT_Module implements WT_Module_Config {

	public function __construct() {
		parent::__construct();
		// Load any local user translations
		if (is_dir(WT_MODULES_DIR.$this->getName().'/language')) {
			if (file_exists(WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.mo')) {
				WT_I18N::addTranslation(
					new Zend_Translate('gettext', WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.mo', WT_LOCALE)
				);
			}
			if (file_exists(WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.php')) {
				WT_I18N::addTranslation(
					new Zend_Translate('array', WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.php', WT_LOCALE)
				);
			}
			if (file_exists(WT_MODULES_DIR.$this->getName().'/language/'.WT_LOCALE.'.csv')) {
				WT_I18N::addTranslation(
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
                    ->restrictAccess(Auth::isAdmin())
                    ->setPageTitle($this->getTitle())
					->pageHeader()
					->addExternalJavascript(WT_JQUERY_DATATABLES_URL)
					->addInlineJavascript('
						function include_css(css_file) {
							var html_doc = document.getElementsByTagName("head")[0];
							var css = document.createElement("link");
							css.setAttribute("rel", "stylesheet");
							css.setAttribute("type", "text/css");
							css.setAttribute("href", css_file);
							html_doc.appendChild(css);
						}
						include_css("'.WT_MODULES_DIR.$this->getName().'/style.css");

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
							dom: \'<"H"pf<"dt-clear">irl>t<"F"pl>\',
							'.WT_I18N::datatablesI18N().',
							jQueryUI: true,
							autoWidth:false,
							processing: true,
							filter: true,
							columns: [
								/* 0-ID */              {dataSort: 7, width: "5%"},
								/* 1-Surname */         {dataSort: 6, width: "15%"},
								/* 2-Given name */      {width: "15%"},
								/* 3-Status */          {width: "15%"},
								/* 4-Privacy settings */{width: "15%"},
								/* 5-Explanation */     {width: "35%"},
								/* 6-SURN */            {type: "unicode", visible: false},
								/* 7-NUMBER */          {visible: false}
							],
							sorting: [['.('6, "asc"').'], ['.('7, "asc"').']],
							pageLength: 30,
							pagingType: "full_numbers"
						});
					');

					global $WT_TREE, $HIDE_LIVE_PEOPLE, $REQUIRE_AUTHENTICATION;

					$html = '
					<h2>'.$this->getTitle().' - '.$WT_TREE->tree_title.'</h2>
					<table id="privacy_list" style="width:100%">
						<thead>
							<tr>
								<th><span style="float:left">'.WT_I18N::translate('ID').'</span></th>
								<th><span style="float:left">'.WT_I18N::translate('Surname').'</span></th>
								<th><span style="float:left">'.WT_I18N::translate('Given name').'</span></th>
								<th><span style="float:left">'.WT_I18N::translate('Status').'</span></th>
								<th><span style="float:left">'.WT_I18N::translate('Privacy settings').'</span></th>
								<th><span style="float:left">'.WT_I18N::translate('Explanation').'</span></th>
								<th>SURN</th>
								<th>NUMBER</th>
							</tr>
						</thead><tbody>';
						$names = $this->getAllNames();
						foreach($names as $name) {
							$xref = $name['ID'];
							$record = WT_Individual::getInstance($xref);
							$settings = $this->getPrivacySettings($record);

							if(!$HIDE_LIVE_PEOPLE && !$settings['RESN']) {
								$auth = $REQUIRE_AUTHENTICATION ? '('.WT_I18N::translate('registered users only').')' : '';
								$settings['PRIV'] = WT_I18N::translate('Show to visitors').$auth;
								$settings['TEXT'] = WT_I18N::translate('You disabled the privacy options for this tree.');
							}

							$i = substr($xref, 1);
							$html .= '
								<tr id="'.$xref.'">
									<td>'.$xref.'</td>
									<td>'.$name['SURNAME'].'</td>
									<td>'.$name['GIVN'].'</td>
									<td>'.$settings['STAT'].'</td>
									<td>'.$settings['PRIV'].'</td>
									<td>'.$settings['TEXT'].'</td>
									<td>'./* hidden by datables code */ $name['SURN'].'</td>
									<td>'./* hidden by datables code */ $i.'</td>
								</tr>';
						}
						$html .= '</tbody></table>';
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

		$sql = "SELECT SQL_CACHE n_id, n_surn, n_surname, n_givn FROM `##name` WHERE n_num=0 AND n_file=? AND n_type=? AND n_surn IS NOT NULL ORDER BY n_sort ASC";
		$args = array(WT_GED_ID, 'NAME');

		foreach (WT_DB::prepare($sql)->execute($args)->fetchAll() as $row) {
			$list[] = array(
				'ID' 		=>$row->n_id,
				'SURN' 		=>$row->n_surn,
				'SURNAME' 	=>$row->n_surname,
				'GIVN' 		=>$row->n_givn
			);
		}
		return $list;
	}

	// This is a copy, with modifications, of the function isDead() in /library/WT/Individual.php
	// It is VERY important that the parameters used in both are identical.
	private function getPrivacySettings($record) {
		global $MAX_ALIVE_AGE, $REQUIRE_AUTHENTICATION, $SHOW_DEAD_PEOPLE, $KEEP_ALIVE_YEARS_BIRTH, $KEEP_ALIVE_YEARS_DEATH, $SHOW_LIVING_NAMES, $WT_TREE;

		$auth = $REQUIRE_AUTHENTICATION ? ' ('.WT_I18N::translate('registered users only').')' : '';
		$SHOW_EST_LIST_DATES = $WT_TREE->getPreference('SHOW_EST_LIST_DATES');

		switch ($SHOW_LIVING_NAMES) {
			case 0:
				$show_name_to = ' (' . WT_I18N::translate('the name is displayed to %s', WT_I18N::translate('managers')) . ')';
				break;
			case 1:
				$show_name_to = ' (' . WT_I18N::translate('the name is displayed to %s', WT_I18N::translate('members')) . ')';
				break;
			case 2:
				$show_name_to = ' (' . WT_I18N::translate('the name is displayed to %s', WT_I18N::translate('visitors') . $auth) . ')';
				break;
			default:
				$show_name_to = '';				
		}

		$ACCESS_LEVEL=array(
			WT_PRIV_PUBLIC=>WT_I18N::translate('Show to visitors').$auth,
			WT_PRIV_USER  =>WT_I18N::translate('Show to members'),
			WT_PRIV_NONE  =>WT_I18N::translate('Show to managers'),
			WT_PRIV_HIDE  =>WT_I18N::translate('Hide from everyone')
		);

		$keep_alive = false; $keep_alive_birth = false; $keep_alive_death = false;
		if ($KEEP_ALIVE_YEARS_BIRTH) {
			$matches = null;
			preg_match_all('/\n1 (?:'.WT_EVENTS_BIRT.').*(?:\n[2-9].*)*(?:\n2 DATE (.+))/', $record->getGedcom(), $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$date=new WT_Date($match[1]);
				if ($date->isOK() && $date->gregorianYear()+$KEEP_ALIVE_YEARS_BIRTH > date('Y')) {
					$keep_alive_birth = true;
				}
			}
		}
		if ($KEEP_ALIVE_YEARS_DEATH) {
			$matches = null;
			preg_match_all('/\n1 (?:'.WT_EVENTS_DEAT.').*(?:\n[2-9].*)*(?:\n2 DATE (.+))/', $record->getGedcom(), $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$date=new WT_Date($match[1]);
				if ($date->isOK() && $date->gregorianYear()+$KEEP_ALIVE_YEARS_DEATH > date('Y')) {
					$keep_alive_death = true;
					break;
				}
			}
		}
		if ($keep_alive_birth == true || $keep_alive_death == true) {
			$keep_alive = true;
		}

		// First check if this record has a RESN
		$facts = $record->getFacts();
		foreach ($facts as $fact) {
			if ($fact->getTag() == 'RESN' && $fact->getValue() != 'locked') {
				$RESN=array(
					'none'			=>array(WT_I18N::translate('None'), $ACCESS_LEVEL[WT_PRIV_PUBLIC]),
					'privacy' 		=>array(WT_I18N::translate('Private'), $ACCESS_LEVEL[WT_PRIV_USER]),
					'confidential'	=>array(WT_I18N::translate('Confidential'), $ACCESS_LEVEL[WT_PRIV_NONE])
				);
				foreach($RESN as $key => $value) {
					if($key == $fact->getValue()) {
						$settings = array(
							'RESN'=>1,
							'STAT'=>$value[0],
							'PRIV'=>$value[1],
							'TEXT'=>WT_I18N::translate('This record has a custom privacy setting.')
						);
						return $settings;
					}
				}
			}
		}

		// Check if this person has a recorded death date.
		$death_dates = $record->getAllDeathDates();
		if ($death_dates) {
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
				if ($keep_alive_birth == true) {
					$keep_alive_msg = ' ' . WT_I18N::translate /* I18N: %s is a number */('This person was born less then %s years ago.', $KEEP_ALIVE_YEARS_BIRTH);
				}
			}
			$settings = array(
				'RESN'=>0,
				'STAT'=>WT_I18N::translate('Death'),
				'PRIV'=>$keep_alive ?  WT_I18N::translate('Private') : $ACCESS_LEVEL[$SHOW_DEAD_PEOPLE],
				'TEXT'=>WT_I18N::translate/* I18N: %s is date of death */ ('Died: %s', $dates).'.'.$keep_alive_msg
			);
			return $settings;
		}

		if (!$record->getEstimatedDeathDate() && $SHOW_EST_LIST_DATES) {
			$settings = array(
				'RESN'=>0,
				'STAT'=>WT_I18N::translate('Presumed death'),
				'PRIV'=>$keep_alive ?  WT_I18N::translate('Private') : $ACCESS_LEVEL[$SHOW_DEAD_PEOPLE],
				'TEXT'=>WT_I18N::translate /* I18N: %s is a date */ ('An estimated death date has been calculated as %s', $record->getEstimatedDeathDate()->Display()).'.'.$keep_alive_msg
			);
			return $settings;
		}

		// "1 DEAT Y" or "1 DEAT/2 DATE" or "1 DEAT/2 PLAC"
		if (preg_match('/\n1 (?:'.WT_EVENTS_DEAT.')(?: Y|(?:\n[2-9].+)*\n2 (DATE|PLAC) )/', $record)) {
			$settings = array(
				'RESN'=>0,
				'STAT'=>WT_I18N::translate('Death'),
				'PRIV'=>$ACCESS_LEVEL[$SHOW_DEAD_PEOPLE],
				'TEXT'=>WT_I18N::translate('Death is recorded with an unknown date.'),
			);
			return $settings;
		}
		// If any event occured more than $MAX_ALIVE_AGE years ago, then assume the individual is dead
		$date_matches = null;
		if (preg_match_all('/\n2 DATE (.+)/', $record->getGedcom(), $date_matches)) {
			foreach ($date_matches[1] as $date_match) {
				$date=new WT_Date($date_match);
				if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365*$MAX_ALIVE_AGE) {
					$settings = array(
						'RESN'=>0,
						'STAT'=>WT_I18N::translate('Presumed death'),
						'PRIV'=>$ACCESS_LEVEL[$SHOW_DEAD_PEOPLE],
						'TEXT'=>WT_I18N::translate /* %s is a number */('An event occurred in this person\'s life more than %s years ago.', $MAX_ALIVE_AGE),
					);
					return $settings;
				}
			}
			// The individual has one or more dated events.  All are less than $MAX_ALIVE_AGE years ago.
			// If one of these is a birth, the individual must be alive.
			if (preg_match('/\n1 BIRT(?:\n[2-9].+)*\n2 DATE /', $record->getGedcom())) {
				$settings = array(
					'RESN'=>0,
					'STAT'=>WT_I18N::translate('Living'),
					'PRIV'=>WT_I18N::translate('Private').$show_name_to,
					'TEXT'=>WT_I18N::translate('According to the privacy settings this person is alive.'),
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
							'RESN'=>0,
							'STAT'=>WT_I18N::translate('Presumed death'),
							'PRIV'=>$ACCESS_LEVEL[$SHOW_DEAD_PEOPLE],
							'TEXT'=>WT_I18N::translate('A parent with a birth date of %s is more than 45 years older than this person.', $date->Display())
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
						'RESN'=>0,
						'STAT'=>WT_I18N::translate('Presumed death'),
						'PRIV'=>$ACCESS_LEVEL[$SHOW_DEAD_PEOPLE],
						'TEXT'=>WT_I18N::translate('A marriage with a date of %s suggests they were born at least 10 years earlier than that.', $date->Display())
					);
					return $settings;
				}
			}
			// Check spouse dates
			$spouse = $family->getSpouse($record);
			if ($spouse) {
				preg_match_all('/\n2 DATE (.+)/', $spouse->getGedcom(), $date_matches);
				foreach ($date_matches[1] as $date_match) {
					$date = new WT_Date($date_match);
					// Assume max age difference between spouses of 40 years
					if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365*($MAX_ALIVE_AGE+40)) {
						$settings = array(
							'RESN'=>0,
							'STAT'=>WT_I18N::translate('Presumed death'),
							'PRIV'=>$ACCESS_LEVEL[$SHOW_DEAD_PEOPLE],
							'TEXT'=>WT_I18N::translate('A spouse with a date of %s is more than 40 years older than this person.', $date->Display())
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
							'RESN'=>0,
							'STAT'=>WT_I18N::translate('Presumed death'),
							'PRIV'=>$ACCESS_LEVEL[$SHOW_DEAD_PEOPLE],
							'TEXT'=>WT_I18N::translate('A child with a birth date of %s suggests this person was born at least 15 years earlier than that.', $date->Display())
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
									'RESN'=>0,
									'STAT'=>WT_I18N::translate('Presumed death'),
									'PRIV'=>$ACCESS_LEVEL[$SHOW_DEAD_PEOPLE],
									'TEXT'=>WT_I18N::translate('A grandchild with a birth date of %s suggests this person was born at least 30 years earlier than that.', $date->Display())
								);
								return $settings;
							}
						}
					}
				}
			}
		}
		$settings = array(
			'RESN'=>0,
			'STAT'=>WT_I18N::translate('Presumably still alive'),
			'PRIV'=>WT_I18N::translate('Private').$show_name_to,
			'TEXT'=>WT_I18N::translate('This person is assumed to be alive because the privacy settings of this person could not be calculated.')
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


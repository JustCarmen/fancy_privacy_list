<?php
/*
 * webtrees: online genealogy
 * Copyright (C) 2016 webtrees development team
 * Copyright (C) 2016 JustCarmen
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace JustCarmen\WebtreesAddOns\FancyPrivacyList;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\Date;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Tree;

/**
 * Class Fancy Privacy List
 */
class FancyPrivacyListClass extends FancyPrivacyListModule {

	// Get a list of all the individuals for the choosen gedcom
	protected function getAllNames(Tree $tree) {

		$sql = "SELECT SQL_CACHE n_id, n_surn, n_surname, n_givn FROM `##name` WHERE n_num = 0 AND n_file = :tree_id AND n_type = 'NAME' AND n_surn IS NOT NULL ORDER BY n_sort ASC";
		$args = array(
			'tree_id' => $tree->getTreeId()
		);

		foreach (Database::prepare($sql)->execute($args)->fetchAll() as $row) {
			$list[] = array(
				'ID'		 => $row->n_id,
				'SURN'		 => $row->n_surn,
				'SURNAME'	 => $row->n_surname,
				'GIVN'		 => $row->n_givn
			);
		}
		return $list;
	}

	// This is a copy, with modifications, of the functions canShowByType() and isDead() in /app/Individual.php
	// It is VERY important that the parameters used in both are identical.
	protected function getPrivacySettings($record) {

		$auth = $record->getTree()->getPreference('REQUIRE_AUTHENTICATION') ? ' (' . I18N::translate('registered users only') . ')' : '';

		switch ($record->getTree()->getPreference('SHOW_LIVING_NAMES')) {
			case 0:
				$show_name_to = ' (' . I18N::translate('the name is displayed to %s', I18N::translate('managers')) . ')';
				break;
			case 1:
				$show_name_to = ' (' . I18N::translate('the name is displayed to %s', I18N::translate('members')) . ')';
				break;
			case 2:
				$show_name_to = ' (' . I18N::translate('the name is displayed to %s', I18N::translate('visitors') . $auth) . ')';
				break;
			default:
				$show_name_to = '';
		}

		$ACCESS_LEVEL = array(
			Auth::PRIV_PRIVATE	 => I18N::translate('Show to visitors') . $auth,
			Auth::PRIV_USER		 => I18N::translate('Show to members'),
			Auth::PRIV_NONE		 => I18N::translate('Show to managers'),
			Auth::PRIV_HIDE		 => I18N::translate('Hide from everyone')
		);

		$keep_alive = false; $keep_alive_birth = false; $keep_alive_death = false;
		if ($record->getTree()->getPreference('KEEP_ALIVE_YEARS_BIRTH')) {
			preg_match_all('/\n1 (?:' . WT_EVENTS_BIRT . ').*(?:\n[2-9].*)*(?:\n2 DATE (.+))/', $record->getGedcom(), $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$date = new Date($match[1]);
				if ($date->isOK() && $date->gregorianYear() + $record->getTree()->getPreference('KEEP_ALIVE_YEARS_BIRTH') > date('Y')) {
					$keep_alive_birth = true;
				}
			}
		}
		if ($record->getTree()->getPreference('KEEP_ALIVE_YEARS_DEATH')) {
			preg_match_all('/\n1 (?:' . WT_EVENTS_DEAT . ').*(?:\n[2-9].*)*(?:\n2 DATE (.+))/', $record->getGedcom(), $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$date = new Date($match[1]);
				if ($date->isOK() && $date->gregorianYear() + $record->getTree()->getPreference('KEEP_ALIVE_YEARS_DEATH') > date('Y')) {
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
				$RESN = array(
					'none'			 => array(I18N::translate('None'), $ACCESS_LEVEL[Auth::PRIV_PRIVATE]),
					'privacy'		 => array(I18N::translate('Private'), $ACCESS_LEVEL[Auth::PRIV_USER]),
					'confidential'	 => array(I18N::translate('Confidential'), $ACCESS_LEVEL[Auth::PRIV_NONE])
				);
				foreach ($RESN as $key => $value) {
					if ($key == $fact->getValue()) {
						$settings = array(
							'RESN'	 => 1,
							'STAT'	 => $value[0],
							'PRIV'	 => $value[1],
							'TEXT'	 => I18N::translate('This record has a custom privacy setting.')
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
			if ($keep_alive_death == true) {
				$keep_alive_msg = ' ' . I18N::translate /* I18N: %s is a number */('That is less than %s years ago.', $record->getTree()->getPreference('KEEP_ALIVE_YEARS_DEATH')) . ' ';
			} else {
				if ($keep_alive_birth == true) {
					$keep_alive_msg = ' ' . I18N::translate /* I18N: %s is a number */('This person was born less then %s years ago.', $record->getTree()->getPreference('KEEP_ALIVE_YEARS_BIRTH'));
				}
			}
			$settings = array(
				'RESN'	 => 0,
				'STAT'	 => I18N::translate('Death'),
				'PRIV'	 => $keep_alive ? I18N::translate('Private') : $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
				'TEXT'	 => I18N::translate/* I18N: %s is date of death */('Died: %s', $dates) . '.' . $keep_alive_msg
			);
			return $settings;
		}

		if (!$record->getEstimatedDeathDate() && $record->getTree()->getPreference('SHOW_EST_LIST_DATES')) {
			$settings = array(
				'RESN'	 => 0,
				'STAT'	 => I18N::translate('Presumed death'),
				'PRIV'	 => $keep_alive ? I18N::translate('Private') : $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
				'TEXT'	 => I18N::translate /* I18N: %s is a date */('An estimated death date has been calculated as %s', $record->getEstimatedDeathDate()->Display()) . '.' . $keep_alive_msg
			);
			return $settings;
		}

		// "1 DEAT Y" or "1 DEAT/2 DATE" or "1 DEAT/2 PLAC"
		if (preg_match('/\n1 (?:' . WT_EVENTS_DEAT . ')(?: Y|(?:\n[2-9].+)*\n2 (DATE|PLAC) )/', $record->getGedcom())) {
			$settings = array(
				'RESN'	 => 0,
				'STAT'	 => I18N::translate('Death'),
				'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
				'TEXT'	 => I18N::translate('Death is recorded with an unknown date.'),
			);
			return $settings;
		}
		// If any event occured more than $record->getTree()->getPreference('MAX_ALIVE_AGE') years ago, then assume the individual is dead
		if (preg_match_all('/\n2 DATE (.+)/', $record->getGedcom(), $date_matches)) {
			foreach ($date_matches[1] as $date_match) {
				$date = new Date($date_match);
				if ($date->isOK() && $date->maximumJulianDay() <= WT_CLIENT_JD - 365 * $record->getTree()->getPreference('MAX_ALIVE_AGE')) {
					$settings = array(
						'RESN'	 => 0,
						'STAT'	 => I18N::translate('Presumed death'),
						'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
						'TEXT'	 => I18N::translate /* %s is a number */('An event occurred in this person\'s life more than %s years ago.', $record->getTree()->getPreference('MAX_ALIVE_AGE')),
					);
					return $settings;
				}
			}
			// The individual has one or more dated events.  All are less than $record->getTree()->getPreference('MAX_ALIVE_AGE') years ago.
			// If one of these is a birth, the individual must be alive.
			if (preg_match('/\n1 BIRT(?:\n[2-9].+)*\n2 DATE /', $record->getGedcom())) {
				$settings = array(
					'RESN'	 => 0,
					'STAT'	 => I18N::translate('Living'),
					'PRIV'	 => I18N::translate('Private') . $show_name_to,
					'TEXT'	 => I18N::translate('According to the privacy settings this person is alive.'),
				);
				return $settings;
			}
		}

		// If we found no conclusive dates then check the dates of close relatives.
		// Check parents (birth and adopted)
		foreach ($record->getChildFamilies(Auth::PRIV_HIDE) as $family) {
			foreach ($family->getSpouses(Auth::PRIV_HIDE) as $parent) {
				// Assume parents are no more than 45 years older than their children
				preg_match_all('/\n2 DATE (.+)/', $parent->getGedcom(), $date_matches);
				foreach ($date_matches[1] as $date_match) {
					$date = new Date($date_match);
					if ($date->isOK() && $date->maximumJulianDay() <= WT_CLIENT_JD - 365 * ($record->getTree()->getPreference('MAX_ALIVE_AGE') + 45)) {
						$settings = array(
							'RESN'	 => 0,
							'STAT'	 => I18N::translate('Presumed death'),
							'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
							'TEXT'	 => I18N::translate('A parent with a birth date of %s is more than 45 years older than this person.', $date->Display())
						);
						return $settings;
					}
				}
			}
		}
		// Check spouses
		foreach ($record->getSpouseFamilies(Auth::PRIV_HIDE) as $family) {
			preg_match_all('/\n2 DATE (.+)/', $family->getGedcom(), $date_matches);
			foreach ($date_matches[1] as $date_match) {
				$date = new Date($date_match);
				// Assume marriage occurs after age of 10
				if ($date->isOK() && $date->maximumJulianDay() <= WT_CLIENT_JD - 365 * ($record->getTree()->getPreference('MAX_ALIVE_AGE') - 10)) {
					$settings = array(
						'RESN'	 => 0,
						'STAT'	 => I18N::translate('Presumed death'),
						'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
						'TEXT'	 => I18N::translate('A marriage with a date of %s suggests they were born at least 10 years earlier than that.', $date->Display())
					);
					return $settings;
				}
			}
			// Check spouse dates
			$spouse = $family->getSpouse($record);
			if ($spouse) {
				preg_match_all('/\n2 DATE (.+)/', $spouse->getGedcom(), $date_matches);
				foreach ($date_matches[1] as $date_match) {
					$date = new Date($date_match);
					// Assume max age difference between spouses of 40 years
					if ($date->isOK() && $date->maximumJulianDay() <= WT_CLIENT_JD - 365 * ($record->getTree()->getPreference('MAX_ALIVE_AGE') + 40)) {
						$settings = array(
							'RESN'	 => 0,
							'STAT'	 => I18N::translate('Presumed death'),
							'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
							'TEXT'	 => I18N::translate('A spouse with a date of %s is more than 40 years older than this person.', $date->Display())
						);
						return $settings;
					}
				}
			}
			// Check child dates
			foreach ($family->getChildren(Auth::PRIV_HIDE) as $child) {
				preg_match_all('/\n2 DATE (.+)/', $child->getGedcom(), $date_matches);
				// Assume children born after age of 15
				foreach ($date_matches[1] as $date_match) {
					$date = new Date($date_match);
					if ($date->isOK() && $date->maximumJulianDay() <= WT_CLIENT_JD - 365 * ($record->getTree()->getPreference('MAX_ALIVE_AGE') - 15)) {
						$settings = array(
							'RESN'	 => 0,
							'STAT'	 => I18N::translate('Presumed death'),
							'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
							'TEXT'	 => I18N::translate('A child with a birth date of %s suggests this person was born at least 15 years earlier than that.', $date->Display())
						);
						return $settings;
					}
				}
				// Check grandchildren
				foreach ($child->getSpouseFamilies(Auth::PRIV_HIDE) as $child_family) {
					foreach ($child_family->getChildren(Auth::PRIV_HIDE) as $grandchild) {
						preg_match_all('/\n2 DATE (.+)/', $grandchild->getGedcom(), $date_matches);
						// Assume grandchildren born after age of 30
						foreach ($date_matches[1] as $date_match) {
							$date = new Date($date_match);
							if ($date->isOK() && $date->maximumJulianDay() <= WT_CLIENT_JD - 365 * ($record->getTree()->getPreference('MAX_ALIVE_AGE') - 30)) {
								$settings = array(
									'RESN'	 => 0,
									'STAT'	 => I18N::translate('Presumed death'),
									'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
									'TEXT'	 => I18N::translate('A grandchild with a birth date of %s suggests this person was born at least 30 years earlier than that.', $date->Display())
								);
								return $settings;
							}
						}
					}
				}
			}
		}
		$settings = array(
			'RESN'	 => 0,
			'STAT'	 => I18N::translate('Presumably still alive'),
			'PRIV'	 => I18N::translate('Private') . $show_name_to,
			'TEXT'	 => I18N::translate('This person is assumed to be alive because the privacy settings of this person could not be calculated.')
		);
		return $settings;
	}

	protected function getRecordData($record) {
		$lines = preg_split('/[\n]+/', $record->getGedcom());
		$gedrec = implode("\n", $lines);
		return preg_replace(
			"/@([^#@\n]+)@/m", '<a href="#" onclick="return edit_raw(\'\\1\');">@\\1@</a>', $gedrec
		);
	}

	protected function getStylesheet() {
		return $this->includeCss($this->directory . '/css/style.css');
	}

	private function includeCss($css) {
		return
			'<script class="fancy-privacy-list-script">
				var newSheet=document.createElement("link");
				newSheet.setAttribute("href","' . $css . '");
				newSheet.setAttribute("type","text/css");
				newSheet.setAttribute("rel","stylesheet");
				newSheet.setAttribute("media","all");
				document.getElementsByTagName("head")[0].appendChild(newSheet);
			</script>';
	}

}

<?php
/*
 * webtrees: online genealogy
 * Copyright (C) 2018 JustCarmen (http://justcarmen.nl)
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
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\File;

/**
 * Class Fancy Privacy Listi
 */
class FancyPrivacyListClass extends FancyPrivacyListModule {

	// This is a copy, with modifications, of the functions canShowByType() and isDead() in /app/Individual.php
	// It is VERY important that the parameters used in both are identical.
	protected function getPrivacySettings($record) {

		$auth = $record->getTree()->getPreference('REQUIRE_AUTHENTICATION') ? ' (' . I18N::translate('registered users only') . ')' : '';

		switch ($record->getTree()->getPreference('SHOW_LIVING_NAMES')) {
			case 0:
				$show_name_to	 = ' (' . I18N::translate('the name is displayed to %s', I18N::translate('managers')) . ')';
				break;
			case 1:
				$show_name_to	 = ' (' . I18N::translate('the name is displayed to %s', I18N::translate('members')) . ')';
				break;
			case 2:
				$show_name_to	 = ' (' . I18N::translate('the name is displayed to %s', I18N::translate('visitors') . $auth) . ')';
				break;
			default:
				$show_name_to	 = '';
		}

		$ACCESS_LEVEL = [
			Auth::PRIV_PRIVATE	 => I18N::translate('Show to visitors') . $auth,
			Auth::PRIV_USER		 => I18N::translate('Show to members'),
			Auth::PRIV_NONE		 => I18N::translate('Show to managers'),
			Auth::PRIV_HIDE		 => I18N::translate('Hide from everyone')
		];

		$keep_alive			 = false; $keep_alive_birth	 = false; $keep_alive_death	 = false;
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
				$RESN = [
					'none'			 => [I18N::translate('None'), $ACCESS_LEVEL[Auth::PRIV_PRIVATE]],
					'privacy'		 => [I18N::translate('Private'), $ACCESS_LEVEL[Auth::PRIV_USER]],
					'confidential'	 => [I18N::translate('Confidential'), $ACCESS_LEVEL[Auth::PRIV_NONE]]
				];
				foreach ($RESN as $key => $value) {
					if ($key == $fact->getValue()) {
						$settings = [
							'RESN'	 => 1,
							'STAT'	 => $value[0],
							'PRIV'	 => $value[1],
							'TEXT'	 => I18N::translate('This record has a custom privacy setting.')
						];
						return $settings;
					}
				}
			}
		}

		// Check if this individual has a recorded death date.
		$death_dates = $record->getAllDeathDates();
		if ($death_dates) {
			$dates = '';
			foreach ($death_dates as $key => $date) {
				if ($key) {
					$dates .= ' | ';
				}
				$dates .= $date->display();
			}

			$keep_alive_msg = '';
			if ($keep_alive_death == true) {
				$keep_alive_msg = ' ' . I18N::plural /* I18N: %s is a number */('That is less than %s year ago.', 'That is less than %s years ago.', $record->getTree()->getPreference('KEEP_ALIVE_YEARS_DEATH'), $record->getTree()->getPreference('KEEP_ALIVE_YEARS_DEATH')) . ' ';
			} else {
				if ($keep_alive_birth == true) {
					$keep_alive_msg = ' ' . I18N::plural /* I18N: %s is a number */('This individual was born less then %s year ago.', 'This individual was born less then %s years ago.', $record->getTree()->getPreference('KEEP_ALIVE_YEARS_BIRTH'), $record->getTree()->getPreference('KEEP_ALIVE_YEARS_BIRTH'));
				}
			}
			$settings = [
				'RESN'	 => 0,
				'STAT'	 => I18N::translate('Death'),
				'PRIV'	 => $keep_alive ? I18N::translate('Private') : $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
				'TEXT'	 => I18N::translate/* I18N: %s is date of death */('Died: %s', $dates) . '.' . $keep_alive_msg
			];
			return $settings;
		}

		if (!$record->getEstimatedDeathDate() && $record->getTree()->getPreference('SHOW_EST_LIST_DATES')) {
			$settings = [
				'RESN'	 => 0,
				'STAT'	 => I18N::translate('Presumed death'),
				'PRIV'	 => $keep_alive ? I18N::translate('Private') : $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
				'TEXT'	 => I18N::translate /* I18N: %s is a date */('An estimated death date has been calculated as %s', $record->getEstimatedDeathDate()->display()) . '.' . $keep_alive_msg
			];
			return $settings;
		}

		// "1 DEAT Y" or "1 DEAT/2 DATE" or "1 DEAT/2 PLAC"
		if (preg_match('/\n1 (?:' . WT_EVENTS_DEAT . ')(?: Y|(?:\n[2-9].+)*\n2 (DATE|PLAC) )/', $record->getGedcom())) {
			$settings = [
				'RESN'	 => 0,
				'STAT'	 => I18N::translate('Death'),
				'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
				'TEXT'	 => I18N::translate('Death is recorded with an unknown date.'),
			];
			return $settings;
		}
		// If any event occured more than $record->getTree()->getPreference('MAX_ALIVE_AGE') years ago, then assume the individual is dead
		if (preg_match_all('/\n2 DATE (.+)/', $record->getGedcom(), $date_matches)) {
			foreach ($date_matches[1] as $date_match) {
				$date = new Date($date_match);
				if ($date->isOK() && $date->maximumJulianDay() <= WT_CLIENT_JD - 365 * $record->getTree()->getPreference('MAX_ALIVE_AGE')) {
					$settings = [
						'RESN'	 => 0,
						'STAT'	 => I18N::translate('Presumed death'),
						'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
						'TEXT'	 => I18N::plural /* %s is a number */('An event occurred in the life of this individual more than %s year ago.', 'An event occurred in the life of this individual more than %s years ago.', $record->getTree()->getPreference('MAX_ALIVE_AGE'), $record->getTree()->getPreference('MAX_ALIVE_AGE')),
					];
					return $settings;
				}
			}
			// The individual has one or more dated events.  All are less than $record->getTree()->getPreference('MAX_ALIVE_AGE') years ago.
			// If one of these is a birth, the individual must be alive.
			if (preg_match('/\n1 BIRT(?:\n[2-9].+)*\n2 DATE /', $record->getGedcom())) {
				$settings = [
					'RESN'	 => 0,
					'STAT'	 => I18N::translate('Living'),
					'PRIV'	 => I18N::translate('Private') . $show_name_to,
					'TEXT'	 => I18N::translate('According to the privacy settings this individual is alive.'),
				];
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
						$settings = [
							'RESN'	 => 0,
							'STAT'	 => I18N::translate('Presumed death'),
							'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
							'TEXT'	 => I18N::translate('A parent with a birth date of %s is more than 45 years older than this individual.', $date->display())
						];
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
					$settings = [
						'RESN'	 => 0,
						'STAT'	 => I18N::translate('Presumed death'),
						'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
						'TEXT'	 => I18N::translate('A marriage with a date of %s suggests they were born at least 10 years earlier than that.', $date->display())
					];
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
						$settings = [
							'RESN'	 => 0,
							'STAT'	 => I18N::translate('Presumed death'),
							'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
							'TEXT'	 => I18N::translate('A spouse with a date of %s is more than 40 years older than this individual.', $date->display())
						];
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
						$settings = [
							'RESN'	 => 0,
							'STAT'	 => I18N::translate('Presumed death'),
							'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
							'TEXT'	 => I18N::translate('A child with a birth date of %s suggests this individual was born at least 15 years earlier than that.', $date->display())
						];
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
								$settings = [
									'RESN'	 => 0,
									'STAT'	 => I18N::translate('Presumed death'),
									'PRIV'	 => $ACCESS_LEVEL[$record->getTree()->getPreference('SHOW_DEAD_PEOPLE')],
									'TEXT'	 => I18N::translate('A grandchild with a birth date of %s suggests this individual was born at least 30 years earlier than that.', $date->display())
								];
								return $settings;
							}
						}
					}
				}
			}
		}
		$settings = [
			'RESN'	 => 0,
			'STAT'	 => I18N::translate('Presumably still alive'),
			'PRIV'	 => I18N::translate('Private') . $show_name_to,
			'TEXT'	 => I18N::translate('This individual is assumed to be alive because the privacy settings could not be calculated.')
		];
		return $settings;
	}

	protected function getRecordData($record) {
    global $WT_TREE;
		$lines	 = preg_split('/[\n]+/', $record->getGedcom());
		$gedrec	 = implode("\n", $lines);
		return preg_replace(
			"/@([^#@\n]+)@/m", '<a href="edit_interface.php?action=editraw&ged=' . $WT_TREE->getName() . '&xref=\\1">@\\1@</a>', $gedrec
		);
	}

  protected function getPrivacyList() {
    global $WT_TREE;

    $data = [];

    $cache_dir = WT_DATA_DIR . 'fpl_cache/';
    $cache_filename = $cache_dir . $WT_TREE->getTreeId() . '-fpltmp.php';

    // use cache file if available and not older than one hour
    if (file_exists($cache_filename) && (time() < filemtime($cache_filename) + 3600)) {
      $data = include($cache_filename);
    }

    if (empty($data)) {
      $sql_select = "SELECT SQL_CACHE n_id as id, n_surn as surn, n_surname as surname, n_givn as givn FROM `##name`";
      $where      = " WHERE n_num = 0 AND n_file = :tree_id AND n_type = 'NAME' AND n_surn IS NOT NULL";
      $order_by   = " ORDER BY n_sort ASC";
      $args       = ['tree_id' => $WT_TREE->getTreeId()];

      // we need all the names from the database. We will limit and order the complete array later
      $rows	 =  Database::prepare($sql_select . $where . $order_by)->execute($args)->fetchAll();

      // Total filtered/unfiltered rows
      $recordsTotal = $recordsFiltered = (int) Database::prepare("SELECT COUNT(*) FROM `##name`" . $where)->execute($args)->fetchOne();

      $data['recordsTotal'] = $recordsTotal;
      $data['recordsFiltered'] = $recordsFiltered;
      
      $privacylist = [];
      foreach ($rows as $row) {
        $record	 = Individual::getInstance($row->id, $WT_TREE);
        if ($record) {
          $settings = $this->getPrivacySettings($record);

          $stat = $settings['STAT'];
          $priv = $settings['PRIV'];
          $text = strip_tags($settings['TEXT']);

          if (!$record->getTree()->getPreference('HIDE_LIVE_PEOPLE') && !$settings['RESN']) {
            $auth = $record->getTree()->getPreference('REQUIRE_AUTHENTICATION') ? '(' . I18N::translate('registered users only') . ')' : '';
            $priv	= I18N::translate('Show to visitors') . $auth;
            $text	= I18N::translate('You disabled the privacy options for this tree.');
          }

          $searchname = str_replace('@N.N.', 'AAAA', $row->surn) . str_replace('@P.N.', 'AAAA', $row->givn);
          $fullname = str_replace(['@N.N.', '@P.N.'], [I18N::translateContext('Unknown surname', '…'), I18N::translateContext('Unknown given name', '…')], $row->surname . ", " . $row->givn);

          $id = $row->id;
          $privacylist[] = [$id, $fullname, $stat, $priv, $text, $searchname];
          $data['privacylist'] = $privacylist;
        }
      }

      // cache the results
      if (!file_exists($cache_dir)) {
        File::mkdir($cache_dir);
      }
      file_put_contents($cache_filename, '<?php return ' . var_export($data,true) . ';?>');
    }
    return $data;
  }

  /**
   * Use Json to load the data into the dataTable
   *
   */
  protected function loadJson() {

    $search = Filter::get('search')['value'];
    $start  = Filter::getInteger('start');
    $length = Filter::getInteger('length');
    $order  = Filter::getArray('order');

    $data = $this->getPrivacyList();

    $privacylist = $data['privacylist'];
    $recordsTotal = $data['recordsTotal'];
    $recordsFiltered = $data['recordsFiltered'];

    if ($search) {
     $filter = [];
     foreach ($privacylist as $key => $column) {
      $found = false;
      foreach ($column as $value) {
        if (stripos($value, $search) !== false) {
         $found = true;
         break;
        }
      }
      if ($found) {
        $filter[$key] = $column;
      }
     }
     $privacylist = $filter;
     $recordsFiltered = count($filter);
    }
    
    if ($order) {
      foreach ($order as $value) {
        $column = $value['column'];
        switch ($value['dir']) {
        case 'asc':
          usort($privacylist, function ($a, $b) use ($column) {
            return strcmp($a[$column], $b[$column]);
          });
          break;
        case 'desc':
         usort($privacylist, function ($a, $b) use ($column) {
            return strcmp($b[$column], $a[$column]);
          });
          break;
        }
      }
    }

    if ($length) {
      $privacylist = array_slice($privacylist, $start, $length);
    }

    header('Content-type: application/json');
    echo json_encode([// See http://www.datatables.net/usage/server-side
      'draw'            => Filter::getInteger('draw'), // String, but always an integer
      'recordsTotal'    => $recordsTotal,
      'recordsFiltered' => $recordsFiltered,
      'data'            => $privacylist
    ]);
    exit;
  }
}

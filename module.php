<?php
namespace Webtrees;

/**
 * webtrees: online genealogy
 * Copyright (C) 2015 webtrees development team
 * Copyright (C) 2015 JustCarmen
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Zend_Translate;

class fancy_privacy_list_WT_Module extends Module implements ModuleConfigInterface {

	public function __construct() {
		parent::__construct();
		// Load any local user translations
		if (is_dir(WT_MODULES_DIR . $this->getName() . '/language')) {
			if (file_exists(WT_MODULES_DIR . $this->getName() . '/language/' . WT_LOCALE . '.mo')) {
				I18N::addTranslation(
					new Zend_Translate('gettext', WT_MODULES_DIR . $this->getName() . '/language/' . WT_LOCALE . '.mo', WT_LOCALE)
				);
			}
			if (file_exists(WT_MODULES_DIR . $this->getName() . '/language/' . WT_LOCALE . '.php')) {
				I18N::addTranslation(
					new Zend_Translate('array', WT_MODULES_DIR . $this->getName() . '/language/' . WT_LOCALE . '.php', WT_LOCALE)
				);
			}
			if (file_exists(WT_MODULES_DIR . $this->getName() . '/language/' . WT_LOCALE . '.csv')) {
				I18N::addTranslation(
					new Zend_Translate('csv', WT_MODULES_DIR . $this->getName() . '/language/' . WT_LOCALE . '.csv', WT_LOCALE)
				);
			}
		}
	}

	// Extend Module
	public function getTitle() {
		return I18N::translate('Fancy Privacy List');
	}

	// Extend Module
	public function getDescription() {
		return I18N::translate('This is a module for site admins only. With this module you easily can see the privacy settings for each individual in your tree.');
	}

	// Extend Module
	public function modAction($mod_action) {
		switch ($mod_action) {
			case 'admin_config':
				$controller = new PageController;
				$controller
					->restrictAccess(Auth::isAdmin())
					->setPageTitle($this->getTitle())
					->pageHeader()
					->addExternalJavascript(WT_JQUERY_DATATABLES_JS_URL)
					->addExternalJavascript(WT_DATATABLES_BOOTSTRAP_JS_URL)
					->addInlineJavascript('
						var oTable;
						// open a row with the gedcom data of this person when the row is clicked on
						jQuery("#privacy_list tbody tr").click( function () {
							var xref = jQuery(this).attr("id");
							var rowClass = jQuery(this).attr("class");
							var nTr = this;
							if (oTable.fnIsOpen(nTr)) {
								oTable.fnClose(nTr);
							} else {
								jQuery.get("module.php?mod=' . $this->getName() . '&mod_action=load_data&id=" + xref, function(data) {
									oTable.fnOpen(nTr, data, "gedcom-data " + rowClass);
								});
							}
						});

						oTable = jQuery("table#privacy_list").dataTable({
							sDom: \'<"pull-left"ip><"pull-right text-right"lf><"dt-clear">rt\',
							' . I18N::datatablesI18N() . ',
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
							sorting: [[' . ('6, "asc"') . '], [' . ('7, "asc"') . ']],
							pageLength: 20,
							pagingType: "full_numbers"
						});
					');

				global $WT_TREE;
				?>

				<ol class="breadcrumb small">
					<li><a href="admin.php"><?php echo I18N::translate('Control panel'); ?></a></li>
					<li><a href="admin_modules.php"><?php echo I18N::translate('Module administration'); ?></a></li>
					<li class="active"><?php echo $controller->getPageTitle(); ?></li>
				</ol>
				<h2><?php echo $this->getTitle(); ?> <small><?php echo $WT_TREE->titleHtml(); ?></small></h2>
				<table id="privacy_list" class="table table-condensed table-bordered table-striped" style="width:100%">
					<thead>
						<tr>
							<th><span style="float:left"><?php echo I18N::translate('ID'); ?></span></th>
							<th><span style="float:left"><?php echo I18N::translate('Surname'); ?></span></th>
							<th><span style="float:left"><?php echo I18N::translate('Given name'); ?></span></th>
							<th><span style="float:left"><?php echo I18N::translate('Status'); ?></span></th>
							<th><span style="float:left"><?php echo I18N::translate('Privacy settings'); ?></span></th>
							<th><span style="float:left"><?php echo I18N::translate('Explanation'); ?></span></th>
							<th>SURN</th>
							<th>NUMBER</th>
						</tr>
					</thead>
					<tbody>
						<?php $names = $this->getAllNames(); ?>
						<?php foreach ($names as $name): ?>
							<?php
							$xref = $name['ID'];
							$record = Individual::getInstance($xref);
							$settings = $this->getPrivacySettings($record);

							if (!$WT_TREE->getPreference('HIDE_LIVE_PEOPLE') && !$settings['RESN']) {
								$auth = $WT_TREE->getPreference('REQUIRE_AUTHENTICATION') ? '(' . I18N::translate('registered users only') . ')' : '';
								$settings['PRIV'] = I18N::translate('Show to visitors') . $auth;
								$settings['TEXT'] = I18N::translate('You disabled the privacy options for this tree.');
							}

							$i = substr($xref, 1);
							?>
							<tr id="<?php echo $xref; ?>">
								<td><?php echo $xref; ?></td>
								<td><?php echo $name['SURNAME']; ?></td>
								<td><?php echo $name['GIVN']; ?></td>
								<td><?php echo $settings['STAT']; ?></td>
								<td><?php echo $settings['PRIV']; ?></td>
								<td><?php echo $settings['TEXT']; ?></td>
								<td><?php echo /* hidden by datables code */ $name['SURN']; ?></td>
								<td><?php echo /* hidden by datables code */ $i; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php
				exit;
			case 'load_data':
				// Generate an AJAX response for datatables to load expanded row
				$xref = Filter::get('id');
				$record = Individual::getInstance($xref);
				Zend_Session::writeClose();
				header('Content-type: text/html; charset=UTF-8');
				echo '<pre>' . $this->getRecordData($record) . '</pre>';
				exit;
		}
	}

	// Implement ModuleConfigInterface
	public function getConfigLink() {
		return 'module.php?mod=' . $this->getName() . '&amp;mod_action=admin_config';
	}

	// Get a list of all the individuals for the choosen gedcom
	private function getAllNames() {

		$sql = "SELECT SQL_CACHE n_id, n_surn, n_surname, n_givn FROM `##name` WHERE n_num = 0 AND n_file = :ged_id AND n_type = 'NAME' AND n_surn IS NOT NULL ORDER BY n_sort ASC";
		$args = array(
			'ged_id' => WT_GED_ID
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

	// This is a copy, with modifications, of the functions canShowByType() and isDead() in /library/WT/Individual.php
	// It is VERY important that the parameters used in both are identical.
	private function getPrivacySettings($record) {
		global $WT_TREE;

		$auth = $WT_TREE->getPreference('REQUIRE_AUTHENTICATION') ? ' (' . I18N::translate('registered users only') . ')' : '';

		switch ($WT_TREE->getPreference('SHOW_LIVING_NAMES')) {
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
			WT_PRIV_PUBLIC	 => I18N::translate('Show to visitors') . $auth,
			WT_PRIV_USER	 => I18N::translate('Show to members'),
			WT_PRIV_NONE	 => I18N::translate('Show to managers'),
			WT_PRIV_HIDE	 => I18N::translate('Hide from everyone')
		);

		$keep_alive = false; $keep_alive_birth = false; $keep_alive_death = false;
		if ($WT_TREE->getPreference('KEEP_ALIVE_YEARS_BIRTH')) {
			preg_match_all('/\n1 (?:' . WT_EVENTS_BIRT . ').*(?:\n[2-9].*)*(?:\n2 DATE (.+))/', $record->getGedcom(), $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$date = new Date($match[1]);
				if ($date->isOK() && $date->gregorianYear() + $WT_TREE->getPreference('KEEP_ALIVE_YEARS_BIRTH') > date('Y')) {
					$keep_alive_birth = true;
				}
			}
		}
		if ($WT_TREE->getPreference('KEEP_ALIVE_YEARS_DEATH')) {
			preg_match_all('/\n1 (?:' . WT_EVENTS_DEAT . ').*(?:\n[2-9].*)*(?:\n2 DATE (.+))/', $record->getGedcom(), $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$date = new Date($match[1]);
				if ($date->isOK() && $date->gregorianYear() + $WT_TREE->getPreference('KEEP_ALIVE_YEARS_DEATH') > date('Y')) {
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
					'none'			 => array(I18N::translate('None'), $ACCESS_LEVEL[WT_PRIV_PUBLIC]),
					'privacy'		 => array(I18N::translate('Private'), $ACCESS_LEVEL[WT_PRIV_USER]),
					'confidential'	 => array(I18N::translate('Confidential'), $ACCESS_LEVEL[WT_PRIV_NONE])
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
				$keep_alive_msg = ' ' . I18N::translate /* I18N: %s is a number */('That is less than %s years ago.', $WT_TREE->getPreference('KEEP_ALIVE_YEARS_DEATH')) . ' ';
			} else {
				if ($keep_alive_birth == true) {
					$keep_alive_msg = ' ' . I18N::translate /* I18N: %s is a number */('This person was born less then %s years ago.', $WT_TREE->getPreference('KEEP_ALIVE_YEARS_BIRTH'));
				}
			}
			$settings = array(
				'RESN'	 => 0,
				'STAT'	 => I18N::translate('Death'),
				'PRIV'	 => $keep_alive ? I18N::translate('Private') : $ACCESS_LEVEL[$WT_TREE->getPreference('SHOW_DEAD_PEOPLE')],
				'TEXT'	 => I18N::translate/* I18N: %s is date of death */('Died: %s', $dates) . '.' . $keep_alive_msg
			);
			return $settings;
		}

		if (!$record->getEstimatedDeathDate() && $WT_TREE->getPreference('SHOW_EST_LIST_DATES')) {
			$settings = array(
				'RESN'	 => 0,
				'STAT'	 => I18N::translate('Presumed death'),
				'PRIV'	 => $keep_alive ? I18N::translate('Private') : $ACCESS_LEVEL[$WT_TREE->getPreference('SHOW_DEAD_PEOPLE')],
				'TEXT'	 => I18N::translate /* I18N: %s is a date */('An estimated death date has been calculated as %s', $record->getEstimatedDeathDate()->Display()) . '.' . $keep_alive_msg
			);
			return $settings;
		}

		// "1 DEAT Y" or "1 DEAT/2 DATE" or "1 DEAT/2 PLAC"
		if (preg_match('/\n1 (?:' . WT_EVENTS_DEAT . ')(?: Y|(?:\n[2-9].+)*\n2 (DATE|PLAC) )/', $record)) {
			$settings = array(
				'RESN'	 => 0,
				'STAT'	 => I18N::translate('Death'),
				'PRIV'	 => $ACCESS_LEVEL[$WT_TREE->getPreference('SHOW_DEAD_PEOPLE')],
				'TEXT'	 => I18N::translate('Death is recorded with an unknown date.'),
			);
			return $settings;
		}
		// If any event occured more than $WT_TREE->getPreference('MAX_ALIVE_AGE') years ago, then assume the individual is dead
		if (preg_match_all('/\n2 DATE (.+)/', $record->getGedcom(), $date_matches)) {
			foreach ($date_matches[1] as $date_match) {
				$date = new Date($date_match);
				if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365 * $WT_TREE->getPreference('MAX_ALIVE_AGE')) {
					$settings = array(
						'RESN'	 => 0,
						'STAT'	 => I18N::translate('Presumed death'),
						'PRIV'	 => $ACCESS_LEVEL[$WT_TREE->getPreference('SHOW_DEAD_PEOPLE')],
						'TEXT'	 => I18N::translate /* %s is a number */('An event occurred in this person\'s life more than %s years ago.', $WT_TREE->getPreference('MAX_ALIVE_AGE')),
					);
					return $settings;
				}
			}
			// The individual has one or more dated events.  All are less than $WT_TREE->getPreference('MAX_ALIVE_AGE') years ago.
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
		foreach ($record->getChildFamilies(WT_PRIV_HIDE) as $family) {
			foreach ($family->getSpouses(WT_PRIV_HIDE) as $parent) {
				// Assume parents are no more than 45 years older than their children
				preg_match_all('/\n2 DATE (.+)/', $parent->getGedcom(), $date_matches);
				foreach ($date_matches[1] as $date_match) {
					$date = new Date($date_match);
					if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365 * ($WT_TREE->getPreference('MAX_ALIVE_AGE') + 45)) {
						$settings = array(
							'RESN'	 => 0,
							'STAT'	 => I18N::translate('Presumed death'),
							'PRIV'	 => $ACCESS_LEVEL[$WT_TREE->getPreference('SHOW_DEAD_PEOPLE')],
							'TEXT'	 => I18N::translate('A parent with a birth date of %s is more than 45 years older than this person.', $date->Display())
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
				$date = new Date($date_match);
				// Assume marriage occurs after age of 10
				if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365 * ($WT_TREE->getPreference('MAX_ALIVE_AGE') - 10)) {
					$settings = array(
						'RESN'	 => 0,
						'STAT'	 => I18N::translate('Presumed death'),
						'PRIV'	 => $ACCESS_LEVEL[$WT_TREE->getPreference('SHOW_DEAD_PEOPLE')],
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
					if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365 * ($WT_TREE->getPreference('MAX_ALIVE_AGE') + 40)) {
						$settings = array(
							'RESN'	 => 0,
							'STAT'	 => I18N::translate('Presumed death'),
							'PRIV'	 => $ACCESS_LEVEL[$WT_TREE->getPreference('SHOW_DEAD_PEOPLE')],
							'TEXT'	 => I18N::translate('A spouse with a date of %s is more than 40 years older than this person.', $date->Display())
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
					$date = new Date($date_match);
					if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365 * ($WT_TREE->getPreference('MAX_ALIVE_AGE') - 15)) {
						$settings = array(
							'RESN'	 => 0,
							'STAT'	 => I18N::translate('Presumed death'),
							'PRIV'	 => $ACCESS_LEVEL[$WT_TREE->getPreference('SHOW_DEAD_PEOPLE')],
							'TEXT'	 => I18N::translate('A child with a birth date of %s suggests this person was born at least 15 years earlier than that.', $date->Display())
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
							$date = new Date($date_match);
							if ($date->isOK() && $date->MaxJD() <= WT_CLIENT_JD - 365 * ($WT_TREE->getPreference('MAX_ALIVE_AGE') - 30)) {
								$settings = array(
									'RESN'	 => 0,
									'STAT'	 => I18N::translate('Presumed death'),
									'PRIV'	 => $ACCESS_LEVEL[$WT_TREE->getPreference('SHOW_DEAD_PEOPLE')],
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

	private function getRecordData($record) {
		$lines = preg_split('/[\n]+/', $record->getGedcom());
		$gedrec = implode("\n", $lines);
		return preg_replace(
			"/@([^#@\n]+)@/m", '<a href="#" onclick="return edit_raw(\'\\1\');">@\\1@</a>', $gedrec
		);
	}

}

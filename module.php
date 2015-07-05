<?php
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
namespace JustCarmen\WebtreesAddOns\FancyPrivacyList;

use Composer\Autoload\ClassLoader;
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use JustCarmen\WebtreesAddOns\FancyPrivacyList\Template\AdminTemplate;

class FancyPrivacyListModule extends AbstractModule implements ModuleConfigInterface {

	public function __construct() {
		parent::__construct('fancy_privacy_list');
		
		// register the namespaces
		$loader = new ClassLoader();
		$loader->addPsr4('JustCarmen\\WebtreesAddOns\\FancyPrivacyList\\', WT_MODULES_DIR . $this->getName() . '/src');
		$loader->register();
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
		global $WT_TREE;
		switch ($mod_action) {
			case 'admin_config':
				$template = new AdminTemplate;
				return $template->pageContent();
			case 'load_data':
				// Generate an AJAX response for datatables to load expanded row
				$xref = Filter::get('id');
				$record = Individual::getInstance($xref, $WT_TREE);

				header('Content-type: text/html; charset=UTF-8');
				echo '<pre>' . $this->getRecordData($record) . '</pre>';
				break;

			default:
				http_response_code(404);
				break;
		}
	}

	// Implement ModuleConfigInterface
	public function getConfigLink() {
		return 'module.php?mod=' . $this->getName() . '&amp;mod_action=admin_config';
	}
}

return new FancyPrivacyListModule;

<?php
/**
 * webtrees: online genealogy
 * Copyright (C) 2018 JustCarmen (http://justcarmen.nl)
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

	const CUSTOM_VERSION	 = '1.7.11';
	const CUSTOM_WEBSITE	 = 'http://www.justcarmen.nl/fancy-modules/fancy-privacy-list/';

	/** @var string location of the fancy Privacy List module files */
	var $directory;

	public function __construct() {
		parent::__construct('fancy_privacy_list');

		$this->directory = WT_STATIC_URL . WT_MODULES_DIR . $this->getName();

		// register the namespaces
		$loader = new ClassLoader();
		$loader->addPsr4('JustCarmen\\WebtreesAddOns\\FancyPrivacyList\\', $this->directory . '/app');
		$loader->register();
	}

	/**
	 * Get the module class.
	 * 
	 * Class functions are called with $this inside the source directory.
	 */
	private function module() {
		return new FancyPrivacyListClass;
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
				$template	 = new AdminTemplate;
				return $template->pageContent();
			case 'load_data':
				// Generate an AJAX response for datatables to load expanded row
				$xref		 = Filter::get('id');
				$record		 = Individual::getInstance($xref, $WT_TREE);

				header('Content-type: text/html; charset=UTF-8');
				echo '<pre>' . $this->module()->getRecordData($record) . '</pre>';
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

  /**
   * Default Fancy script to load a module stylesheet
   *
   * The code to place the stylesheet in the header renders quicker than the default webtrees solution
   * because we do not have to wait until the page is fully loaded
   *
   * @return javascript
   */
  protected function includeCss() {
    return
        '<script>
          var newSheet=document.createElement("link");
          newSheet.setAttribute("rel","stylesheet");
          newSheet.setAttribute("type","text/css");
          newSheet.setAttribute("href","' . $this->directory . '/css/style.css");
          document.getElementsByTagName("head")[0].appendChild(newSheet);
        </script>';
  }

}

return new FancyPrivacyListModule;

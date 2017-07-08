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
namespace JustCarmen\WebtreesAddOns\FancyPrivacyList\Template;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Bootstrap4;
use Fisharebest\Webtrees\Controller\PageController;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use JustCarmen\WebtreesAddOns\FancyPrivacyList\FancyPrivacyListClass;

class AdminTemplate extends FancyPrivacyListClass {

	protected function pageContent() {
		$controller = new PageController;
		return
			$this->pageHeader($controller) .
			$this->pageBody($controller);
	}

	private function pageHeader(PageController $controller) {
		$controller
			->restrictAccess(Auth::isAdmin())
			->setPageTitle($this->getTitle())
			->pageHeader()
			->addExternalJavascript(WT_DATATABLES_BOOTSTRAP_JS_URL)
			->addExternalJavascript(WT_DATATABLES_BOOTSTRAP_JS_URL)
			->addInlineJavascript('
			var oTable;
			// open a row with the gedcom data of this individual when the row is clicked on
			jQuery("#privacy_list tbody tr").click( function () {
				var xref = jQuery(this).data("xref");
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

			var oldStart = 0;
      var layout = "d-flex justify-content-between";
			oTable = jQuery("table#privacy_list").dataTable({
				sDom: \'<"\' + layout + \'"lf><"\' + layout + \'"ip>t<"\' + layout + \'"ip>\',
				' . I18N::datatablesI18N([10, 20, 50, 100, 500, 1000, -1]) . ',
				autoWidth:false,
        processing: true,
				serverSide: true,
				ajax: "module.php?mod=' . $this->getName() . '&mod_action=load_json",
				filter: true,
				columns: [
        	/* 0-ID */              {type: "unicode", visible: false},
					/* 1-Name */            {dataSort: 5, width: "20%"},
					/* 2-Status */          {width: "20%"},
					/* 3-Privacy settings */{width: "35%"},
					/* 4-Explanation */     {width: "25%"},
					/* 5-SURN */            {type: "unicode", visible: false},
				],
				sorting: [' . ('5, "asc"') . '],
				pageLength: 20
			});
		');

		echo $this->includeCss();
	}

	private function pageBody(PageController $controller) {
		global $WT_TREE;

		echo Bootstrap4::breadcrumbs([
			'admin.php'			 => I18N::translate('Control panel'),
			'admin_modules.php'	 => I18N::translate('Module administration'),
			], $controller->getPageTitle());
		?>

		<h1><?= $controller->getPageTitle() ?></h1>
		<p><?= $WT_TREE->getTitle() ?></p>
		<table id="privacy_list" class="table table-condensed table-bordered table-striped" style="width:100%">
			<thead>
				<tr>
          <th class="sr-only"></th>
					<th><?= I18N::translate('Name') ?></th>
					<th><?= I18N::translate('Status') ?></th>
					<th><?= I18N::translate('Privacy settings') ?></th>
					<th><?= I18N::translate('Explanation') ?></th>
          <th class="sr-only"></th>
				</tr>
			</thead>
      <tbody></tbody>
    </table>
		<?php
	}

}

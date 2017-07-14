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
use Fisharebest\Webtrees\Tree;
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
      // Notice DataTable with uppercase to use newest API (lowercase d for older API)
      // See: https://stackoverflow.com/questions/35311380/uncaught-typeerror-cannot-read-property-url-of-undefined-in-datatables
      var layout = "d-flex justify-content-between";
			oTable = $("#privacy-list").DataTable({
				sDom: \'<"\' + layout + \'"lf><"\' + layout + \'"ip>t<"\' + layout + \'"ip>\',
				' . I18N::datatablesI18N([10, 20, 50, 100, 500, 1000, -1]) . ',
				autoWidth:false,
        processing: true,
				serverSide: true,
				ajax: "module.php?mod=' . $this->getName() . '&mod_action=load_json&ged=" + $("#tree").val(),
				filter: true,
        stateSave: true,
        createdRow: function(row, data) {
          $(row).attr("data-xref", data[0]);
        },
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

			// open a row with the gedcom data of this individual when the row is clicked on
			$("#privacy-list").on("click", "tbody tr", function () {
      
        var tr = $(this);
				var row = oTable.row(tr);
        var xref = tr.data("xref");
				
				if (row.child.isShown()) {
					row.child.hide();
          tr.removeClass("shown");
				} else {
					$.get("module.php?mod=' . $this->getName() . '&mod_action=load_data&id=" + xref, function(data) {
            row.child(data).show();
            tr.addClass("shown");
					});
				}
			});

      $("#tree").on("change", function() {
        oTable.ajax.url("module.php?mod=' . $this->getName() . '&mod_action=load_json&ged=" + $(this).val()).load();
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
    <div class="fancy-privacylist-admin">
      <div class="d-inline-flex justify-content-between align-items-center mb-5 mt-3 w-100">
        <h1><?= $controller->getPageTitle() ?></h1>
        <?php if (count(Tree::getAll()) > 1): ?>
        <div class="col-sm-4">
          <?= Bootstrap4::select(Tree::getNameList(), $WT_TREE->getName(), ['id' => 'tree', 'name' => 'NEW_FIB_TREE']) ?>
        </div>
        <?php endif ?>
      </div>
      <table id="privacy-list" class="table table-condensed table-bordered table-striped" style="width:100%">
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
    </div>
		<?php
	}

}

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
namespace JustCarmen\WebtreesAddOns\FancyPrivacyList\Template;

use Fisharebest\Webtrees\Auth;
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
			->addExternalJavascript(WT_JQUERY_DATATABLES_JS_URL)
			->addExternalJavascript(WT_DATATABLES_BOOTSTRAP_JS_URL)
			->addInlineJavascript('
			var oTable;
			// open a row with the gedcom data of this person when the row is clicked on
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
			oTable = jQuery("table#privacy_list").dataTable({
				sDom: \'<"top"<"pull-left"li>fp>rt\',
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

			// correction - turn selectbox into a bootstrap selectbox
			jQuery("select").addClass("form-control");
		');
		echo $this->getStylesheet();
	}

	private function pageBody(PageController $controller) {
		global $WT_TREE;
		$names = $this->getAllNames($WT_TREE);
		?>
		<!-- ADMIN PAGE CONTENT -->
		<ol class="breadcrumb small">
			<li><a href="admin.php"><?php echo I18N::translate('Control panel') ?></a></li>
			<li><a href="admin_modules.php"><?php echo I18N::translate('Module administration') ?></a></li>
			<li class="active"><?php echo $controller->getPageTitle() ?></li>
		</ol>
		<h2><?php echo $this->getTitle(); ?> <small><?php echo $WT_TREE->getTitleHtml() ?></small></h2>
		<table id="privacy_list" class="table table-condensed table-bordered table-striped" style="width:100%">
			<thead>
				<tr>
					<th><span style="float:left"><?php echo I18N::translate('ID') ?></span></th>
					<th><span style="float:left"><?php echo I18N::translate('Surname') ?></span></th>
					<th><span style="float:left"><?php echo I18N::translate('Given name') ?></span></th>
					<th><span style="float:left"><?php echo I18N::translate('Status') ?></span></th>
					<th><span style="float:left"><?php echo I18N::translate('Privacy settings') ?></span></th>
					<th><span style="float:left"><?php echo I18N::translate('Explanation') ?></span></th>
					<th>SURN</th>
					<th>NUMBER</th>
				</tr>
			</thead>

			<?php foreach ($names as $name): ?>
				<?php
				$xref = $name['ID'];
				$record = Individual::getInstance($xref, $WT_TREE);
				if ($record):
					$settings = $this->getPrivacySettings($record);

					if (!$record->getTree()->getPreference('HIDE_LIVE_PEOPLE') && !$settings['RESN']) {
						$auth = $record->getTree()->getPreference('REQUIRE_AUTHENTICATION') ? '(' . I18N::translate('registered users only') . ')' : '';
						$settings['PRIV'] = I18N::translate('Show to visitors') . $auth;
						$settings['TEXT'] = I18N::translate('You disabled the privacy options for this tree.');
					}

					$i = substr($xref, 1);
					?>
					<tr data-xref="<?php echo $xref ?>">
						<td><?php echo $xref ?></td>
						<td><?php echo $name['SURNAME'] ?></td>
						<td><?php echo $name['GIVN'] ?></td>
						<td><?php echo $settings['STAT'] ?></td>
						<td><?php echo $settings['PRIV'] ?></td>
						<td><?php echo $settings['TEXT'] ?></td>
						<td><?php echo /* hidden by datables code */ $name['SURN'] ?></td>
						<td><?php echo /* hidden by datables code */ $i ?></td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>

		</table>
		<?php
	}

}

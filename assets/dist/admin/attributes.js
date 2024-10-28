/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
(() => {
const $ = jQuery;

// add stock column to attributes table as early as possible
if (window.mewzWcas && mewzWcas.attributesTable && Object.keys(mewzWcas.attributesTable.columnData).length) {
  addAttributesTableStockColumn(mewzWcas.attributesTable);
}

// handle form inputs
const $manageStock = $('#mewz_wcas_manage_stock');
const $form = $manageStock.closest('form');
$manageStock.on('change', toggleManageStock);
setTimeout(toggleManageStock);
if ($form.attr('id') === 'addtag') {
  // WP uses the same "click" listener, and it works for submitting with "enter" too
  $form.find('#submit').on('click', onSubmitForm);

  // show columns dynamically when a new term is added
  if (/mewz-wcas-hide-\w+-column/.test($(document.body).attr('class'))) {
    $(document).on('ajaxComplete', onAjaxComplete);
  }
}
function toggleManageStock() {
  $('.form-field-mewz-wcas-hidden').toggleClass('show', $manageStock.prop('checked'));
}
function onSubmitForm() {
  const $fields = $('.form-field-mewz-wcas-hidden');
  if (!$fields.length) return;
  setTimeout(() => {
    if ($form.find('.form-invalid').length) {
      return;
    }
    $manageStock.prop('checked', false);
    $fields.removeClass('show');
    $fields.find('input').val('');
  });
}
function onAjaxComplete(event, xhr, options) {
  if (!xhr || !xhr.responseText) {
    return;
  }
  if (xhr.responseText.indexOf('mewz-wcas-stock-link') !== -1) {
    $(document.body).removeClass('mewz-wcas-hide-stock-column');
  }
  if (xhr.responseText.indexOf('mewz-wcas-term-multiplier') !== -1) {
    $(document.body).removeClass('mewz-wcas-hide-multiplier-column');
  }
}
function addAttributesTableStockColumn(tableData) {
  const $attributesTable = $('.attributes-table');
  if (!$attributesTable.length) return;
  const $headRow = $attributesTable.find('> thead > tr');
  const $th = $headRow.find('th:first-child').clone().text(tableData.columnHeader);
  $headRow.find('th:nth-child(2)').after($th);
  $attributesTable.find('> tbody > tr').each(function () {
    const $tr = $(this);
    const attrId = parseInt($tr.find('.edit a').attr('href').match(/&edit=(\d+)/)[1]);
    $tr.find('td:nth-child(2)').after($('<td/>').html(tableData.columnData[attrId]));
  });
}
})();

// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
// extracted by mini-css-extract-plugin

})();

/******/ })()
;
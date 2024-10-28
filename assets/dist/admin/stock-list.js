/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
(() => {

;// CONCATENATED MODULE: ./admin/stock-list/ui/header-actions.js
const $ = jQuery;
const headerActions = window.mewzWcas && mewzWcas.headerActions || {};
let $importFileInput;
function load() {
  if (headerActions.html) {
    const $actions = $(headerActions.html);
    $actions.insertAfter('.wrap > .page-title-action');
    $('#mewz-wcas-import-button').on('click', handleImportAction);
  }
}
function handleImportAction() {
  const $button = $(this);
  if (!$importFileInput) {
    const $form = $(`<form action="${$button.data('action')}" method="post" enctype="multipart/form-data" style="display: none;"></form>`);
    $importFileInput = $('<input type="file" name="import_file" />');
    $importFileInput.on('change', () => $form.submit());
    $('body').append($form.append($importFileInput));
  }
  $importFileInput[0].click();
}
;// CONCATENATED MODULE: ./admin/stock-list/ui/filter-attribute.js
const filter_attribute_$ = jQuery;
const attributeOptions = window.mewzWcas && mewzWcas.attributeOptions || {};
let $attrFilter, $termFilter;
function filter_attribute_load() {
  $attrFilter = filter_attribute_$('#filter_attribute');
  $termFilter = filter_attribute_$('#filter_term');
  if (!$attrFilter.length || !$termFilter.length) {
    return;
  }
  $attrFilter.on('change', populateTermOptions);

  // prevent prefilled values due to browser bfcache
  $attrFilter.closest('form')[0].reset();
}
function populateTermOptions() {
  const attrId = +$attrFilter.val();
  const options = getTermOptions(attrId);
  $termFilter.find('option:not([value=""])').remove();
  $termFilter.append(options);
  $termFilter.val('');
  $termFilter.prop('hidden', !options.length);
}
function getTermOptions(attributeId) {
  if (!attributeOptions[attributeId]) {
    return [];
  }
  let options = [];
  const terms = attributeOptions[attributeId].terms;
  for (let i = 0; i < terms.length; i++) {
    options.push(filter_attribute_$('<option/>', {
      value: terms[i][0],
      text: terms[i][1]
    }));
  }
  return options;
}
;// CONCATENATED MODULE: ./admin/stock-list/ui/inline-edit.js
const inline_edit_$ = jQuery;
const {
  restUrl,
  restNonce,
  locale
} = window.mewzWcas && mewzWcas.stockListData || {};
let $table;
function inline_edit_load() {
  if (restUrl) {
    $table = inline_edit_$('#the-list');
    $table.on('click', '.inline-edit-controls[data-value] .action-button', inlineEdit);
  }
}
function inlineEdit() {
  const $button = inline_edit_$(this);
  const $edit = $button.parent();
  const $td = $edit.closest('td');
  const action = $button.data('action');
  const currentValue = +$edit.data('value');
  const value = action === 'adjust_quantity' ? '' : currentValue;
  const inputClass = action === 'adjust_quantity' ? 'adjust' : 'edit';
  const placeholder = action.indexOf('quantity') !== -1 ? 0 .toLocaleString(locale, {
    minimumFractionDigits: 2
  }) : 0;
  const $input = makeInput('number', value, placeholder);
  const $inputIcon = inline_edit_$('<span class="icon"></span>');
  const $inputWrap = inline_edit_$(`<span class="inline-edit-input-wrap ${inputClass}"></span>`);
  $td.append($inputWrap.append($input, $inputIcon));
  $input.select();
  $td.addClass('inline-editing');
  $input.on('blur', stopEditing);
  $input.on('keydown', function (e) {
    if (e.key === 'Escape') {
      stopEditing();
    } else if (e.key === 'Enter') {
      e.stopPropagation();
      e.preventDefault();
      handleUpdate();
      return false;
    } else if (e.key === 'Tab') {
      handleUpdate();
      const $tr = $td.parent('tr');
      const buttonSelector = `.inline-edit-controls .action-button[data-action="${action}"]`;
      const $nextButton = getNextButton($tr, buttonSelector, e.shiftKey);
      if ($nextButton) {
        e.preventDefault();
        $nextButton.click();
      }
    }
  });
  let $valueEl, origValueHtml;
  function handleUpdate() {
    const newValue = +$input.val();
    stopEditing();
    if (action === 'adjust_quantity' ? !newValue : newValue === currentValue) {
      return;
    }
    $valueEl = $td.find('> :first-child');
    if (action.startsWith('set_')) {
      origValueHtml = $valueEl.html();
      $valueEl.text(newValue.toLocaleString(locale, {
        maximumSignificantDigits: 20,
        maximumFractionDigits: 20
      }));
    }
    $td.addClass('inline-edit-pending');
    $td.find('.action-button').prop('disabled', true);
    const data = {
      action,
      value: newValue
    };
    ajaxUpdateStock($edit.data('stock-id'), data).done(onSuccess).error(onError).always(onComplete);
  }
  function stopEditing() {
    $inputWrap.remove();
    $td.removeClass('inline-editing');
  }
  function onSuccess(data) {
    if (!data || !('updated' in data) || !('value' in data)) {
      return onError();
    }
    if (data.formatted_quantity) {
      $valueEl.replaceWith(data.formatted_quantity);
    } else {
      $valueEl.text(data.value);
      $valueEl.attr('class', $valueEl.attr('class').replace(/ value-[\w-]+/, ' value-' + data.value));
    }
    $edit.data('value', data.value);
    $td.trigger('mewz_wcas_inline_edited', [data]);
  }
  function onError() {
    if (origValueHtml) {
      $valueEl.html(origValueHtml);
    }
  }
  function onComplete() {
    $td.removeClass('inline-edit-pending');
    $td.find('.action-button').prop('disabled', false);
  }
}
function makeInput(type, value, placeholder) {
  return inline_edit_$('<input/>', {
    type,
    value,
    class: 'inline-edit-input',
    step: 'any',
    lang: locale,
    placeholder,
    enterkeyhint: 'send'
  });
}
function ajaxUpdateStock(stockId, data) {
  return inline_edit_$.ajax({
    url: restUrl + '/inline-edit/' + stockId + '?_locale=user',
    method: 'POST',
    dataType: 'json',
    data,
    beforeSend(xhr) {
      xhr.setRequestHeader('X-WP-Nonce', restNonce);
    }
  });
}
function getNextButton($tr, buttonSelector, prev = false) {
  let $nextRow = getNextRow($tr, prev);
  if ($nextRow.is($tr)) return false;
  let $nextButton = $nextRow.find(buttonSelector);
  if (!$nextButton.length) return false;
  while ($nextButton.prop('disabled')) {
    $nextRow = getNextRow($nextRow, prev);
    if ($nextRow.is($tr)) return false;
    $nextButton = $nextRow.find(buttonSelector);
    if (!$nextButton.length) return false;
  }
  return $nextButton;
}
function getNextRow($tr, prev = false) {
  let $nextRow = prev ? $tr.prev('tr') : $tr.next('tr');
  if (!$nextRow.length) {
    $nextRow = prev ? $tr.siblings('tr:last-of-type') : $tr.siblings('tr:first-of-type');
  }
  return $nextRow;
}
;// CONCATENATED MODULE: ./admin/stock-list/ui/chips.js
function chips_load() {
  const table = document.querySelector('.wp-list-table');
  if (!table) return;
  table.addEventListener('click', e => {
    if (!e.target.dataset.show || e.target.tagName !== 'BUTTON' || !e.target.classList.contains('show-more') || !e.target.classList.contains('mewz-wcas-chip')) {
      return;
    }
    const showchips = e.target.parentNode.querySelectorAll(`.mewz-wcas-chip-${e.target.dataset.show}.hidden`);
    e.target.remove();
    for (const chip of showchips) {
      chip.classList.remove('hidden');
    }
  });
}
;// CONCATENATED MODULE: ./admin/stock-list/ui/confirmations.js
const confirmations_$ = jQuery;
const {
  __,
  sprintf
} = wp.i18n;
function confirmations_load() {
  const $tableList = confirmations_$('#the-list');
  if ($tableList.length) {
    $tableList.on('click', 'tr.status-publish .action-trash', onTableListClick);
  }
}
function onTableListClick(e) {
  const title = confirmations_$(this).closest('tr').find('td.column-title .row-title').text();
  const message = sprintf(__('Are you sure you want to delete %s?'), `"${title}"`);
  if (!confirm(message)) {
    e.preventDefault();
  }
}
;// CONCATENATED MODULE: ./admin/stock-list/ui/tooltips.js
function tooltips_load() {
  jQuery('[rel="tiptip"]').tipTip({
    fadeIn: 50,
    fadeOut: 50,
    delay: 200
  });
}
;// CONCATENATED MODULE: ./admin/stock-list/index.js






load();
filter_attribute_load();
inline_edit_load();
chips_load();
confirmations_load();
tooltips_load();
})();

// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
(() => {
// extracted by mini-css-extract-plugin

})();

/******/ })()
;
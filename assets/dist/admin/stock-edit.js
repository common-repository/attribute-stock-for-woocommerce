/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
(() => {

;// CONCATENATED MODULE: ../../packages/framework/assets/src/lib/util.js
let selectWooInit = false;
jQuery(document.body).one('wc-enhanced-select-init', () => {
  selectWooInit = true;
});
function initSelectWoo(onlyIfInit) {
  if (!onlyIfInit || selectWooInit) {
    jQuery(document.body).trigger('wc-enhanced-select-init');
  }
}
function initTooltips(selector) {
  jQuery(selector).tipTip({
    fadeIn: 50,
    fadeOut: 50,
    delay: 200
  });
}
function detectFieldChanges(container, namePrefix, fn) {
  const form = container.closest('form');

  // if not in a form then assume values have changed
  if (!form) {
    fn(true);
    return;
  }
  setTimeout(() => {
    const valueString = getFormValueString(form, namePrefix);
    form.addEventListener('submit', () => {
      const changed = getFormValueString(form, namePrefix) !== valueString;
      fn(changed);
    });
  }, 300);
}
function getFormValueString(form, namePrefix) {
  const formData = new FormData(form);
  const values = [];
  for (const field of formData.entries()) {
    if (field[0].startsWith(namePrefix)) {
      values.push(field[0], field[1]);
    }
  }
  return JSON.stringify(values);
}
;// CONCATENATED MODULE: ./admin/stock-edit/ui/form-fields.js

const $ = jQuery;
function load() {
  $('#post').on('submit', onSubmit);
  $('#mewz_wcas_internal').on('change', onInternalChange);
  onInternalChange();
  setTimeout(initSelectWoo);
}
function onSubmit() {
  const $statusBox = $('#mewz-wcas-stock-status');
  $statusBox.find('.spinner').addClass('is-active');
  $statusBox.find('#submit').prop('disabled', true);
}
function onInternalChange() {
  const $this = $('#mewz_wcas_internal');
  const internal = $this.prop('checked') || $this.prop('disabled');
  $('#mewz_wcas_product_sku').prop('disabled', internal);
  $('#mewz_wcas_product_image').prop('disabled', internal);
}
;// CONCATENATED MODULE: ./admin/stock-edit/ui/header-actions.js
const header_actions_$ = jQuery;
const headerActions = window.mewzWcas && mewzWcas.headerActions || {};
function header_actions_load() {
  if (headerActions.html) {
    const $actions = header_actions_$(headerActions.html);
    $actions.insertAfter('.wrap > .page-title-action');
  }
}
;// CONCATENATED MODULE: ./admin/stock-edit/ui/tab-indicators.js
const tab_indicators_$ = jQuery;
let tab_indicators_form;
function tab_indicators_load() {
  tab_indicators_form = document.getElementById('post');
  mewzWcas.setTabIndicator = setTabIndicator;
  monitorPanelIndicator('settings');
  monitorPanelIndicator('filters');
}
function setTabIndicator(tab, value) {
  const tabEl = tab_indicators_form.querySelector(`.wc-tabs > .${tab}_tab a`);
  if (!tabEl) return;
  if (value) {
    tabEl.dataset.indicator = value;
  } else {
    delete tabEl.dataset.indicator;
  }
}
function monitorPanelIndicator(tab) {
  const panel = tab_indicators_$(`#${tab}_panel`);
  panel.on('change input', () => {
    updatePanelIndicator(tab, panel);
  });
  updatePanelIndicator(tab, panel);
}
function updatePanelIndicator(tab, panel) {
  const fields = panel.find(':input:not(:disabled, .select2-search__field)');
  let indicator = 0;
  fields.each(function () {
    const field = tab_indicators_$(this);
    const value = field.attr('type') === 'checkbox' ? field.prop('checked') : field.val();
    if (Array.isArray(value)) {
      indicator += value.length;
    } else if (value) {
      indicator++;
    }
  });
  setTabIndicator(tab, indicator);
}
;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/utils.js
/** @returns {void} */
function utils_noop() {}

const identity = (x) => x;

/**
 * @template T
 * @template S
 * @param {T} tar
 * @param {S} src
 * @returns {T & S}
 */
function utils_assign(tar, src) {
	// @ts-ignore
	for (const k in src) tar[k] = src[k];
	return /** @type {T & S} */ (tar);
}

// Adapted from https://github.com/then/is-promise/blob/master/index.js
// Distributed under MIT License https://github.com/then/is-promise/blob/master/LICENSE
/**
 * @param {any} value
 * @returns {value is PromiseLike<any>}
 */
function utils_is_promise(value) {
	return (
		!!value &&
		(typeof value === 'object' || typeof value === 'function') &&
		typeof (/** @type {any} */ (value).then) === 'function'
	);
}

/** @returns {void} */
function add_location(element, file, line, column, char) {
	element.__svelte_meta = {
		loc: { file, line, column, char }
	};
}

function run(fn) {
	return fn();
}

function utils_blank_object() {
	return Object.create(null);
}

/**
 * @param {Function[]} fns
 * @returns {void}
 */
function utils_run_all(fns) {
	fns.forEach(run);
}

/**
 * @param {any} thing
 * @returns {thing is Function}
 */
function utils_is_function(thing) {
	return typeof thing === 'function';
}

/** @returns {boolean} */
function safe_not_equal(a, b) {
	return a != a ? b == b : a !== b || (a && typeof a === 'object') || typeof a === 'function';
}

let src_url_equal_anchor;

/**
 * @param {string} element_src
 * @param {string} url
 * @returns {boolean}
 */
function src_url_equal(element_src, url) {
	if (element_src === url) return true;
	if (!src_url_equal_anchor) {
		src_url_equal_anchor = document.createElement('a');
	}
	// This is actually faster than doing URL(..).href
	src_url_equal_anchor.href = url;
	return element_src === src_url_equal_anchor.href;
}

/** @param {string} srcset */
function split_srcset(srcset) {
	return srcset.split(',').map((src) => src.trim().split(' ').filter(Boolean));
}

/**
 * @param {HTMLSourceElement | HTMLImageElement} element_srcset
 * @param {string | undefined | null} srcset
 * @returns {boolean}
 */
function srcset_url_equal(element_srcset, srcset) {
	const element_urls = split_srcset(element_srcset.srcset);
	const urls = split_srcset(srcset || '');

	return (
		urls.length === element_urls.length &&
		urls.every(
			([url, width], i) =>
				width === element_urls[i][1] &&
				// We need to test both ways because Vite will create an a full URL with
				// `new URL(asset, import.meta.url).href` for the client when `base: './'`, and the
				// relative URLs inside srcset are not automatically resolved to absolute URLs by
				// browsers (in contrast to img.src). This means both SSR and DOM code could
				// contain relative or absolute URLs.
				(src_url_equal(element_urls[i][0], url) || src_url_equal(url, element_urls[i][0]))
		)
	);
}

/** @returns {boolean} */
function not_equal(a, b) {
	return a != a ? b == b : a !== b;
}

/** @returns {boolean} */
function is_empty(obj) {
	return Object.keys(obj).length === 0;
}

/** @returns {void} */
function validate_store(store, name) {
	if (store != null && typeof store.subscribe !== 'function') {
		throw new Error(`'${name}' is not a store with a 'subscribe' method`);
	}
}

function subscribe(store, ...callbacks) {
	if (store == null) {
		for (const callback of callbacks) {
			callback(undefined);
		}
		return utils_noop;
	}
	const unsub = store.subscribe(...callbacks);
	return unsub.unsubscribe ? () => unsub.unsubscribe() : unsub;
}

/**
 * Get the current value from a store by subscribing and immediately unsubscribing.
 *
 * https://svelte.dev/docs/svelte-store#get
 * @template T
 * @param {import('../store/public.js').Readable<T>} store
 * @returns {T}
 */
function get_store_value(store) {
	let value;
	subscribe(store, (_) => (value = _))();
	return value;
}

/** @returns {void} */
function component_subscribe(component, store, callback) {
	component.$$.on_destroy.push(subscribe(store, callback));
}

function create_slot(definition, ctx, $$scope, fn) {
	if (definition) {
		const slot_ctx = get_slot_context(definition, ctx, $$scope, fn);
		return definition[0](slot_ctx);
	}
}

function get_slot_context(definition, ctx, $$scope, fn) {
	return definition[1] && fn ? utils_assign($$scope.ctx.slice(), definition[1](fn(ctx))) : $$scope.ctx;
}

function get_slot_changes(definition, $$scope, dirty, fn) {
	if (definition[2] && fn) {
		const lets = definition[2](fn(dirty));
		if ($$scope.dirty === undefined) {
			return lets;
		}
		if (typeof lets === 'object') {
			const merged = [];
			const len = Math.max($$scope.dirty.length, lets.length);
			for (let i = 0; i < len; i += 1) {
				merged[i] = $$scope.dirty[i] | lets[i];
			}
			return merged;
		}
		return $$scope.dirty | lets;
	}
	return $$scope.dirty;
}

/** @returns {void} */
function update_slot_base(
	slot,
	slot_definition,
	ctx,
	$$scope,
	slot_changes,
	get_slot_context_fn
) {
	if (slot_changes) {
		const slot_context = get_slot_context(slot_definition, ctx, $$scope, get_slot_context_fn);
		slot.p(slot_context, slot_changes);
	}
}

/** @returns {void} */
function update_slot(
	slot,
	slot_definition,
	ctx,
	$$scope,
	dirty,
	get_slot_changes_fn,
	get_slot_context_fn
) {
	const slot_changes = get_slot_changes(slot_definition, $$scope, dirty, get_slot_changes_fn);
	update_slot_base(slot, slot_definition, ctx, $$scope, slot_changes, get_slot_context_fn);
}

/** @returns {any[] | -1} */
function get_all_dirty_from_scope($$scope) {
	if ($$scope.ctx.length > 32) {
		const dirty = [];
		const length = $$scope.ctx.length / 32;
		for (let i = 0; i < length; i++) {
			dirty[i] = -1;
		}
		return dirty;
	}
	return -1;
}

/** @returns {{}} */
function exclude_internal_props(props) {
	const result = {};
	for (const k in props) if (k[0] !== '$') result[k] = props[k];
	return result;
}

/** @returns {{}} */
function compute_rest_props(props, keys) {
	const rest = {};
	keys = new Set(keys);
	for (const k in props) if (!keys.has(k) && k[0] !== '$') rest[k] = props[k];
	return rest;
}

/** @returns {{}} */
function compute_slots(slots) {
	const result = {};
	for (const key in slots) {
		result[key] = true;
	}
	return result;
}

/** @returns {(this: any, ...args: any[]) => void} */
function once(fn) {
	let ran = false;
	return function (...args) {
		if (ran) return;
		ran = true;
		fn.call(this, ...args);
	};
}

function null_to_empty(value) {
	return value == null ? '' : value;
}

function set_store_value(store, ret, value) {
	store.set(value);
	return ret;
}

const utils_has_prop = (obj, prop) => Object.prototype.hasOwnProperty.call(obj, prop);

function action_destroyer(action_result) {
	return action_result && utils_is_function(action_result.destroy) ? action_result.destroy : utils_noop;
}

/** @param {number | string} value
 * @returns {[number, string]}
 */
function utils_split_css_unit(value) {
	const split = typeof value === 'string' && value.match(/^\s*(-?[\d.]+)([^\s]*)\s*$/);
	return split ? [parseFloat(split[1]), split[2] || 'px'] : [/** @type {number} */ (value), 'px'];
}

const utils_contenteditable_truthy_values = (/* unused pure expression or super */ null && (['', true, 1, 'true', 'contenteditable']));

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/environment.js


const is_client = typeof window !== 'undefined';

/** @type {() => number} */
let environment_now = is_client ? () => window.performance.now() : () => Date.now();

let raf = is_client ? (cb) => requestAnimationFrame(cb) : utils_noop;

// used internally for testing
/** @returns {void} */
function set_now(fn) {
	environment_now = fn;
}

/** @returns {void} */
function set_raf(fn) {
	raf = fn;
}

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/loop.js


const tasks = new Set();

/**
 * @param {number} now
 * @returns {void}
 */
function run_tasks(now) {
	tasks.forEach((task) => {
		if (!task.c(now)) {
			tasks.delete(task);
			task.f();
		}
	});
	if (tasks.size !== 0) raf(run_tasks);
}

/**
 * For testing purposes only!
 * @returns {void}
 */
function clear_loops() {
	tasks.clear();
}

/**
 * Creates a new task that runs on each raf frame
 * until it returns a falsy value or is aborted
 * @param {import('./private.js').TaskCallback} callback
 * @returns {import('./private.js').Task}
 */
function loop_loop(callback) {
	/** @type {import('./private.js').TaskEntry} */
	let task;
	if (tasks.size === 0) raf(run_tasks);
	return {
		promise: new Promise((fulfill) => {
			tasks.add((task = { c: callback, f: fulfill }));
		}),
		abort() {
			tasks.delete(task);
		}
	};
}

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/globals.js
/** @type {typeof globalThis} */
const globals =
	typeof window !== 'undefined'
		? window
		: typeof globalThis !== 'undefined'
		? globalThis
		: // @ts-ignore Node typings have this
		  global;

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/ResizeObserverSingleton.js


/**
 * Resize observer singleton.
 * One listener per element only!
 * https://groups.google.com/a/chromium.org/g/blink-dev/c/z6ienONUb5A/m/F5-VcUZtBAAJ
 */
class ResizeObserverSingleton {
	/**
	 * @private
	 * @readonly
	 * @type {WeakMap<Element, import('./private.js').Listener>}
	 */
	_listeners = "WeakMap" in globals ? new WeakMap() : undefined;

	/**
	 * @private
	 * @type {ResizeObserver}
	 */
	_observer = undefined;

	/** @type {ResizeObserverOptions} */
	options;

	/** @param {ResizeObserverOptions} options */
	constructor(options) {
		this.options = options;
	}

	/**
	 * @param {Element} element
	 * @param {import('./private.js').Listener} listener
	 * @returns {() => void}
	 */
	observe(element, listener) {
		this._listeners.set(element, listener);
		this._getObserver().observe(element, this.options);
		return () => {
			this._listeners.delete(element);
			this._observer.unobserve(element); // this line can probably be removed
		};
	}

	/**
	 * @private
	 */
	_getObserver() {
		return (
			this._observer ??
			(this._observer = new ResizeObserver((entries) => {
				for (const entry of entries) {
					ResizeObserverSingleton.entries.set(entry.target, entry);
					this._listeners.get(entry.target)?.(entry);
				}
			}))
		);
	}
}

// Needs to be written like this to pass the tree-shake-test
ResizeObserverSingleton.entries = "WeakMap" in globals ? new WeakMap() : undefined;

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/dom.js




// Track which nodes are claimed during hydration. Unclaimed nodes can then be removed from the DOM
// at the end of hydration without touching the remaining nodes.
let is_hydrating = false;

/**
 * @returns {void}
 */
function start_hydrating() {
	is_hydrating = true;
}

/**
 * @returns {void}
 */
function end_hydrating() {
	is_hydrating = false;
}

/**
 * @param {number} low
 * @param {number} high
 * @param {(index: number) => number} key
 * @param {number} value
 * @returns {number}
 */
function upper_bound(low, high, key, value) {
	// Return first index of value larger than input value in the range [low, high)
	while (low < high) {
		const mid = low + ((high - low) >> 1);
		if (key(mid) <= value) {
			low = mid + 1;
		} else {
			high = mid;
		}
	}
	return low;
}

/**
 * @param {NodeEx} target
 * @returns {void}
 */
function init_hydrate(target) {
	if (target.hydrate_init) return;
	target.hydrate_init = true;
	// We know that all children have claim_order values since the unclaimed have been detached if target is not <head>

	let children = /** @type {ArrayLike<NodeEx2>} */ (target.childNodes);
	// If target is <head>, there may be children without claim_order
	if (target.nodeName === 'HEAD') {
		const my_children = [];
		for (let i = 0; i < children.length; i++) {
			const node = children[i];
			if (node.claim_order !== undefined) {
				my_children.push(node);
			}
		}
		children = my_children;
	}
	/*
	 * Reorder claimed children optimally.
	 * We can reorder claimed children optimally by finding the longest subsequence of
	 * nodes that are already claimed in order and only moving the rest. The longest
	 * subsequence of nodes that are claimed in order can be found by
	 * computing the longest increasing subsequence of .claim_order values.
	 *
	 * This algorithm is optimal in generating the least amount of reorder operations
	 * possible.
	 *
	 * Proof:
	 * We know that, given a set of reordering operations, the nodes that do not move
	 * always form an increasing subsequence, since they do not move among each other
	 * meaning that they must be already ordered among each other. Thus, the maximal
	 * set of nodes that do not move form a longest increasing subsequence.
	 */
	// Compute longest increasing subsequence
	// m: subsequence length j => index k of smallest value that ends an increasing subsequence of length j
	const m = new Int32Array(children.length + 1);
	// Predecessor indices + 1
	const p = new Int32Array(children.length);
	m[0] = -1;
	let longest = 0;
	for (let i = 0; i < children.length; i++) {
		const current = children[i].claim_order;
		// Find the largest subsequence length such that it ends in a value less than our current value
		// upper_bound returns first greater value, so we subtract one
		// with fast path for when we are on the current longest subsequence
		const seq_len =
			(longest > 0 && children[m[longest]].claim_order <= current
				? longest + 1
				: upper_bound(1, longest, (idx) => children[m[idx]].claim_order, current)) - 1;
		p[i] = m[seq_len] + 1;
		const new_len = seq_len + 1;
		// We can guarantee that current is the smallest value. Otherwise, we would have generated a longer sequence.
		m[new_len] = i;
		longest = Math.max(new_len, longest);
	}
	// The longest increasing subsequence of nodes (initially reversed)

	/**
	 * @type {NodeEx2[]}
	 */
	const lis = [];
	// The rest of the nodes, nodes that will be moved

	/**
	 * @type {NodeEx2[]}
	 */
	const to_move = [];
	let last = children.length - 1;
	for (let cur = m[longest] + 1; cur != 0; cur = p[cur - 1]) {
		lis.push(children[cur - 1]);
		for (; last >= cur; last--) {
			to_move.push(children[last]);
		}
		last--;
	}
	for (; last >= 0; last--) {
		to_move.push(children[last]);
	}
	lis.reverse();
	// We sort the nodes being moved to guarantee that their insertion order matches the claim order
	to_move.sort((a, b) => a.claim_order - b.claim_order);
	// Finally, we move the nodes
	for (let i = 0, j = 0; i < to_move.length; i++) {
		while (j < lis.length && to_move[i].claim_order >= lis[j].claim_order) {
			j++;
		}
		const anchor = j < lis.length ? lis[j] : null;
		target.insertBefore(to_move[i], anchor);
	}
}

/**
 * @param {Node} target
 * @param {Node} node
 * @returns {void}
 */
function dom_append(target, node) {
	target.appendChild(node);
}

/**
 * @param {Node} target
 * @param {string} style_sheet_id
 * @param {string} styles
 * @returns {void}
 */
function append_styles(target, style_sheet_id, styles) {
	const append_styles_to = get_root_for_style(target);
	if (!append_styles_to.getElementById(style_sheet_id)) {
		const style = dom_element('style');
		style.id = style_sheet_id;
		style.textContent = styles;
		append_stylesheet(append_styles_to, style);
	}
}

/**
 * @param {Node} node
 * @returns {ShadowRoot | Document}
 */
function get_root_for_style(node) {
	if (!node) return document;
	const root = node.getRootNode ? node.getRootNode() : node.ownerDocument;
	if (root && /** @type {ShadowRoot} */ (root).host) {
		return /** @type {ShadowRoot} */ (root);
	}
	return node.ownerDocument;
}

/**
 * @param {Node} node
 * @returns {CSSStyleSheet}
 */
function append_empty_stylesheet(node) {
	const style_element = dom_element('style');
	// For transitions to work without 'style-src: unsafe-inline' Content Security Policy,
	// these empty tags need to be allowed with a hash as a workaround until we move to the Web Animations API.
	// Using the hash for the empty string (for an empty tag) works in all browsers except Safari.
	// So as a workaround for the workaround, when we append empty style tags we set their content to /* empty */.
	// The hash 'sha256-9OlNO0DNEeaVzHL4RZwCLsBHA8WBQ8toBp/4F5XV2nc=' will then work even in Safari.
	style_element.textContent = '/* empty */';
	append_stylesheet(get_root_for_style(node), style_element);
	return style_element.sheet;
}

/**
 * @param {ShadowRoot | Document} node
 * @param {HTMLStyleElement} style
 * @returns {CSSStyleSheet}
 */
function append_stylesheet(node, style) {
	dom_append(/** @type {Document} */ (node).head || node, style);
	return style.sheet;
}

/**
 * @param {NodeEx} target
 * @param {NodeEx} node
 * @returns {void}
 */
function dom_append_hydration(target, node) {
	if (is_hydrating) {
		init_hydrate(target);
		if (
			target.actual_end_child === undefined ||
			(target.actual_end_child !== null && target.actual_end_child.parentNode !== target)
		) {
			target.actual_end_child = target.firstChild;
		}
		// Skip nodes of undefined ordering
		while (target.actual_end_child !== null && target.actual_end_child.claim_order === undefined) {
			target.actual_end_child = target.actual_end_child.nextSibling;
		}
		if (node !== target.actual_end_child) {
			// We only insert if the ordering of this node should be modified or the parent node is not target
			if (node.claim_order !== undefined || node.parentNode !== target) {
				target.insertBefore(node, target.actual_end_child);
			}
		} else {
			target.actual_end_child = node.nextSibling;
		}
	} else if (node.parentNode !== target || node.nextSibling !== null) {
		target.appendChild(node);
	}
}

/**
 * @param {Node} target
 * @param {Node} node
 * @param {Node} [anchor]
 * @returns {void}
 */
function dom_insert(target, node, anchor) {
	target.insertBefore(node, anchor || null);
}

/**
 * @param {NodeEx} target
 * @param {NodeEx} node
 * @param {NodeEx} [anchor]
 * @returns {void}
 */
function dom_insert_hydration(target, node, anchor) {
	if (is_hydrating && !anchor) {
		dom_append_hydration(target, node);
	} else if (node.parentNode !== target || node.nextSibling != anchor) {
		target.insertBefore(node, anchor || null);
	}
}

/**
 * @param {Node} node
 * @returns {void}
 */
function dom_detach(node) {
	if (node.parentNode) {
		node.parentNode.removeChild(node);
	}
}

/**
 * @returns {void} */
function destroy_each(iterations, detaching) {
	for (let i = 0; i < iterations.length; i += 1) {
		if (iterations[i]) iterations[i].d(detaching);
	}
}

/**
 * @template {keyof HTMLElementTagNameMap} K
 * @param {K} name
 * @returns {HTMLElementTagNameMap[K]}
 */
function dom_element(name) {
	return document.createElement(name);
}

/**
 * @template {keyof HTMLElementTagNameMap} K
 * @param {K} name
 * @param {string} is
 * @returns {HTMLElementTagNameMap[K]}
 */
function element_is(name, is) {
	return document.createElement(name, { is });
}

/**
 * @template T
 * @template {keyof T} K
 * @param {T} obj
 * @param {K[]} exclude
 * @returns {Pick<T, Exclude<keyof T, K>>}
 */
function object_without_properties(obj, exclude) {
	const target = /** @type {Pick<T, Exclude<keyof T, K>>} */ ({});
	for (const k in obj) {
		if (
			has_prop(obj, k) &&
			// @ts-ignore
			exclude.indexOf(k) === -1
		) {
			// @ts-ignore
			target[k] = obj[k];
		}
	}
	return target;
}

/**
 * @template {keyof SVGElementTagNameMap} K
 * @param {K} name
 * @returns {SVGElement}
 */
function svg_element(name) {
	return document.createElementNS('http://www.w3.org/2000/svg', name);
}

/**
 * @param {string} data
 * @returns {Text}
 */
function dom_text(data) {
	return document.createTextNode(data);
}

/**
 * @returns {Text} */
function space() {
	return dom_text(' ');
}

/**
 * @returns {Text} */
function empty() {
	return dom_text('');
}

/**
 * @param {string} content
 * @returns {Comment}
 */
function comment(content) {
	return document.createComment(content);
}

/**
 * @param {EventTarget} node
 * @param {string} event
 * @param {EventListenerOrEventListenerObject} handler
 * @param {boolean | AddEventListenerOptions | EventListenerOptions} [options]
 * @returns {() => void}
 */
function dom_listen(node, event, handler, options) {
	node.addEventListener(event, handler, options);
	return () => node.removeEventListener(event, handler, options);
}

/**
 * @returns {(event: any) => any} */
function prevent_default(fn) {
	return function (event) {
		event.preventDefault();
		// @ts-ignore
		return fn.call(this, event);
	};
}

/**
 * @returns {(event: any) => any} */
function stop_propagation(fn) {
	return function (event) {
		event.stopPropagation();
		// @ts-ignore
		return fn.call(this, event);
	};
}

/**
 * @returns {(event: any) => any} */
function stop_immediate_propagation(fn) {
	return function (event) {
		event.stopImmediatePropagation();
		// @ts-ignore
		return fn.call(this, event);
	};
}

/**
 * @returns {(event: any) => void} */
function dom_self(fn) {
	return function (event) {
		// @ts-ignore
		if (event.target === this) fn.call(this, event);
	};
}

/**
 * @returns {(event: any) => void} */
function trusted(fn) {
	return function (event) {
		// @ts-ignore
		if (event.isTrusted) fn.call(this, event);
	};
}

/**
 * @param {Element} node
 * @param {string} attribute
 * @param {string} [value]
 * @returns {void}
 */
function dom_attr(node, attribute, value) {
	if (value == null) node.removeAttribute(attribute);
	else if (node.getAttribute(attribute) !== value) node.setAttribute(attribute, value);
}
/**
 * List of attributes that should always be set through the attr method,
 * because updating them through the property setter doesn't work reliably.
 * In the example of `width`/`height`, the problem is that the setter only
 * accepts numeric values, but the attribute can also be set to a string like `50%`.
 * If this list becomes too big, rethink this approach.
 */
const always_set_through_set_attribute = (/* unused pure expression or super */ null && (['width', 'height']));

/**
 * @param {Element & ElementCSSInlineStyle} node
 * @param {{ [x: string]: string }} attributes
 * @returns {void}
 */
function set_attributes(node, attributes) {
	// @ts-ignore
	const descriptors = Object.getOwnPropertyDescriptors(node.__proto__);
	for (const key in attributes) {
		if (attributes[key] == null) {
			node.removeAttribute(key);
		} else if (key === 'style') {
			node.style.cssText = attributes[key];
		} else if (key === '__value') {
			/** @type {any} */ (node).value = node[key] = attributes[key];
		} else if (
			descriptors[key] &&
			descriptors[key].set &&
			always_set_through_set_attribute.indexOf(key) === -1
		) {
			node[key] = attributes[key];
		} else {
			dom_attr(node, key, attributes[key]);
		}
	}
}

/**
 * @param {Element & ElementCSSInlineStyle} node
 * @param {{ [x: string]: string }} attributes
 * @returns {void}
 */
function set_svg_attributes(node, attributes) {
	for (const key in attributes) {
		dom_attr(node, key, attributes[key]);
	}
}

/**
 * @param {Record<string, unknown>} data_map
 * @returns {void}
 */
function set_custom_element_data_map(node, data_map) {
	Object.keys(data_map).forEach((key) => {
		set_custom_element_data(node, key, data_map[key]);
	});
}

/**
 * @returns {void} */
function set_custom_element_data(node, prop, value) {
	const lower = prop.toLowerCase(); // for backwards compatibility with existing behavior we do lowercase first
	if (lower in node) {
		node[lower] = typeof node[lower] === 'boolean' && value === '' ? true : value;
	} else if (prop in node) {
		node[prop] = typeof node[prop] === 'boolean' && value === '' ? true : value;
	} else {
		dom_attr(node, prop, value);
	}
}

/**
 * @param {string} tag
 */
function set_dynamic_element_data(tag) {
	return /-/.test(tag) ? set_custom_element_data_map : set_attributes;
}

/**
 * @returns {void}
 */
function xlink_attr(node, attribute, value) {
	node.setAttributeNS('http://www.w3.org/1999/xlink', attribute, value);
}

/**
 * @param {HTMLElement} node
 * @returns {string}
 */
function get_svelte_dataset(node) {
	return node.dataset.svelteH;
}

/**
 * @returns {unknown[]} */
function get_binding_group_value(group, __value, checked) {
	const value = new Set();
	for (let i = 0; i < group.length; i += 1) {
		if (group[i].checked) value.add(group[i].__value);
	}
	if (!checked) {
		value.delete(__value);
	}
	return Array.from(value);
}

/**
 * @param {HTMLInputElement[]} group
 * @returns {{ p(...inputs: HTMLInputElement[]): void; r(): void; }}
 */
function init_binding_group(group) {
	/**
	 * @type {HTMLInputElement[]} */
	let _inputs;
	return {
		/* push */ p(...inputs) {
			_inputs = inputs;
			_inputs.forEach((input) => group.push(input));
		},
		/* remove */ r() {
			_inputs.forEach((input) => group.splice(group.indexOf(input), 1));
		}
	};
}

/**
 * @param {number[]} indexes
 * @returns {{ u(new_indexes: number[]): void; p(...inputs: HTMLInputElement[]): void; r: () => void; }}
 */
function init_binding_group_dynamic(group, indexes) {
	/**
	 * @type {HTMLInputElement[]} */
	let _group = get_binding_group(group);

	/**
	 * @type {HTMLInputElement[]} */
	let _inputs;

	function get_binding_group(group) {
		for (let i = 0; i < indexes.length; i++) {
			group = group[indexes[i]] = group[indexes[i]] || [];
		}
		return group;
	}

	/**
	 * @returns {void} */
	function push() {
		_inputs.forEach((input) => _group.push(input));
	}

	/**
	 * @returns {void} */
	function remove() {
		_inputs.forEach((input) => _group.splice(_group.indexOf(input), 1));
	}
	return {
		/* update */ u(new_indexes) {
			indexes = new_indexes;
			const new_group = get_binding_group(group);
			if (new_group !== _group) {
				remove();
				_group = new_group;
				push();
			}
		},
		/* push */ p(...inputs) {
			_inputs = inputs;
			push();
		},
		/* remove */ r: remove
	};
}

/** @returns {number} */
function to_number(value) {
	return value === '' ? null : +value;
}

/** @returns {any[]} */
function time_ranges_to_array(ranges) {
	const array = [];
	for (let i = 0; i < ranges.length; i += 1) {
		array.push({ start: ranges.start(i), end: ranges.end(i) });
	}
	return array;
}

/**
 * @param {Element} element
 * @returns {ChildNode[]}
 */
function children(element) {
	return Array.from(element.childNodes);
}

/**
 * @param {ChildNodeArray} nodes
 * @returns {void}
 */
function init_claim_info(nodes) {
	if (nodes.claim_info === undefined) {
		nodes.claim_info = { last_index: 0, total_claimed: 0 };
	}
}

/**
 * @template {ChildNodeEx} R
 * @param {ChildNodeArray} nodes
 * @param {(node: ChildNodeEx) => node is R} predicate
 * @param {(node: ChildNodeEx) => ChildNodeEx | undefined} process_node
 * @param {() => R} create_node
 * @param {boolean} dont_update_last_index
 * @returns {R}
 */
function claim_node(nodes, predicate, process_node, create_node, dont_update_last_index = false) {
	// Try to find nodes in an order such that we lengthen the longest increasing subsequence
	init_claim_info(nodes);
	const result_node = (() => {
		// We first try to find an element after the previous one
		for (let i = nodes.claim_info.last_index; i < nodes.length; i++) {
			const node = nodes[i];
			if (predicate(node)) {
				const replacement = process_node(node);
				if (replacement === undefined) {
					nodes.splice(i, 1);
				} else {
					nodes[i] = replacement;
				}
				if (!dont_update_last_index) {
					nodes.claim_info.last_index = i;
				}
				return node;
			}
		}
		// Otherwise, we try to find one before
		// We iterate in reverse so that we don't go too far back
		for (let i = nodes.claim_info.last_index - 1; i >= 0; i--) {
			const node = nodes[i];
			if (predicate(node)) {
				const replacement = process_node(node);
				if (replacement === undefined) {
					nodes.splice(i, 1);
				} else {
					nodes[i] = replacement;
				}
				if (!dont_update_last_index) {
					nodes.claim_info.last_index = i;
				} else if (replacement === undefined) {
					// Since we spliced before the last_index, we decrease it
					nodes.claim_info.last_index--;
				}
				return node;
			}
		}
		// If we can't find any matching node, we create a new one
		return create_node();
	})();
	result_node.claim_order = nodes.claim_info.total_claimed;
	nodes.claim_info.total_claimed += 1;
	return result_node;
}

/**
 * @param {ChildNodeArray} nodes
 * @param {string} name
 * @param {{ [key: string]: boolean }} attributes
 * @param {(name: string) => Element | SVGElement} create_element
 * @returns {Element | SVGElement}
 */
function claim_element_base(nodes, name, attributes, create_element) {
	return claim_node(
		nodes,
		/** @returns {node is Element | SVGElement} */
		(node) => node.nodeName === name,
		/** @param {Element} node */
		(node) => {
			const remove = [];
			for (let j = 0; j < node.attributes.length; j++) {
				const attribute = node.attributes[j];
				if (!attributes[attribute.name]) {
					remove.push(attribute.name);
				}
			}
			remove.forEach((v) => node.removeAttribute(v));
			return undefined;
		},
		() => create_element(name)
	);
}

/**
 * @param {ChildNodeArray} nodes
 * @param {string} name
 * @param {{ [key: string]: boolean }} attributes
 * @returns {Element | SVGElement}
 */
function claim_element(nodes, name, attributes) {
	return claim_element_base(nodes, name, attributes, dom_element);
}

/**
 * @param {ChildNodeArray} nodes
 * @param {string} name
 * @param {{ [key: string]: boolean }} attributes
 * @returns {Element | SVGElement}
 */
function claim_svg_element(nodes, name, attributes) {
	return claim_element_base(nodes, name, attributes, svg_element);
}

/**
 * @param {ChildNodeArray} nodes
 * @returns {Text}
 */
function claim_text(nodes, data) {
	return claim_node(
		nodes,
		/** @returns {node is Text} */
		(node) => node.nodeType === 3,
		/** @param {Text} node */
		(node) => {
			const data_str = '' + data;
			if (node.data.startsWith(data_str)) {
				if (node.data.length !== data_str.length) {
					return node.splitText(data_str.length);
				}
			} else {
				node.data = data_str;
			}
		},
		() => dom_text(data),
		true // Text nodes should not update last index since it is likely not worth it to eliminate an increasing subsequence of actual elements
	);
}

/**
 * @returns {Text} */
function claim_space(nodes) {
	return claim_text(nodes, ' ');
}

/**
 * @param {ChildNodeArray} nodes
 * @returns {Comment}
 */
function claim_comment(nodes, data) {
	return claim_node(
		nodes,
		/** @returns {node is Comment} */
		(node) => node.nodeType === 8,
		/** @param {Comment} node */
		(node) => {
			node.data = '' + data;
			return undefined;
		},
		() => comment(data),
		true
	);
}

function get_comment_idx(nodes, text, start) {
	for (let i = start; i < nodes.length; i += 1) {
		const node = nodes[i];
		if (node.nodeType === 8 /* comment node */ && node.textContent.trim() === text) {
			return i;
		}
	}
	return -1;
}

/**
 * @param {boolean} is_svg
 * @returns {HtmlTagHydration}
 */
function claim_html_tag(nodes, is_svg) {
	// find html opening tag
	const start_index = get_comment_idx(nodes, 'HTML_TAG_START', 0);
	const end_index = get_comment_idx(nodes, 'HTML_TAG_END', start_index + 1);
	if (start_index === -1 || end_index === -1) {
		return new HtmlTagHydration(is_svg);
	}

	init_claim_info(nodes);
	const html_tag_nodes = nodes.splice(start_index, end_index - start_index + 1);
	dom_detach(html_tag_nodes[0]);
	dom_detach(html_tag_nodes[html_tag_nodes.length - 1]);
	const claimed_nodes = html_tag_nodes.slice(1, html_tag_nodes.length - 1);
	if (claimed_nodes.length === 0) {
		return new HtmlTagHydration(is_svg);
	}
	for (const n of claimed_nodes) {
		n.claim_order = nodes.claim_info.total_claimed;
		nodes.claim_info.total_claimed += 1;
	}
	return new HtmlTagHydration(is_svg, claimed_nodes);
}

/**
 * @param {Text} text
 * @param {unknown} data
 * @returns {void}
 */
function set_data(text, data) {
	data = '' + data;
	if (text.data === data) return;
	text.data = /** @type {string} */ (data);
}

/**
 * @param {Text} text
 * @param {unknown} data
 * @returns {void}
 */
function set_data_contenteditable(text, data) {
	data = '' + data;
	if (text.wholeText === data) return;
	text.data = /** @type {string} */ (data);
}

/**
 * @param {Text} text
 * @param {unknown} data
 * @param {string} attr_value
 * @returns {void}
 */
function set_data_maybe_contenteditable(text, data, attr_value) {
	if (~contenteditable_truthy_values.indexOf(attr_value)) {
		set_data_contenteditable(text, data);
	} else {
		set_data(text, data);
	}
}

/**
 * @returns {void} */
function set_input_value(input, value) {
	input.value = value == null ? '' : value;
}

/**
 * @returns {void} */
function set_input_type(input, type) {
	try {
		input.type = type;
	} catch (e) {
		// do nothing
	}
}

/**
 * @returns {void} */
function set_style(node, key, value, important) {
	if (value == null) {
		node.style.removeProperty(key);
	} else {
		node.style.setProperty(key, value, important ? 'important' : '');
	}
}

/**
 * @returns {void} */
function select_option(select, value, mounting) {
	for (let i = 0; i < select.options.length; i += 1) {
		const option = select.options[i];
		if (option.__value === value) {
			option.selected = true;
			return;
		}
	}
	if (!mounting || value !== undefined) {
		select.selectedIndex = -1; // no option should be selected
	}
}

/**
 * @returns {void} */
function select_options(select, value) {
	for (let i = 0; i < select.options.length; i += 1) {
		const option = select.options[i];
		option.selected = ~value.indexOf(option.__value);
	}
}

function select_value(select) {
	const selected_option = select.querySelector(':checked');
	return selected_option && selected_option.__value;
}

function select_multiple_value(select) {
	return [].map.call(select.querySelectorAll(':checked'), (option) => option.__value);
}
// unfortunately this can't be a constant as that wouldn't be tree-shakeable
// so we cache the result instead

/**
 * @type {boolean} */
let crossorigin;

/**
 * @returns {boolean} */
function is_crossorigin() {
	if (crossorigin === undefined) {
		crossorigin = false;
		try {
			if (typeof window !== 'undefined' && window.parent) {
				void window.parent.document;
			}
		} catch (error) {
			crossorigin = true;
		}
	}
	return crossorigin;
}

/**
 * @param {HTMLElement} node
 * @param {() => void} fn
 * @returns {() => void}
 */
function add_iframe_resize_listener(node, fn) {
	const computed_style = getComputedStyle(node);
	if (computed_style.position === 'static') {
		node.style.position = 'relative';
	}
	const iframe = dom_element('iframe');
	iframe.setAttribute(
		'style',
		'display: block; position: absolute; top: 0; left: 0; width: 100%; height: 100%; ' +
			'overflow: hidden; border: 0; opacity: 0; pointer-events: none; z-index: -1;'
	);
	iframe.setAttribute('aria-hidden', 'true');
	iframe.tabIndex = -1;
	const crossorigin = is_crossorigin();

	/**
	 * @type {() => void}
	 */
	let unsubscribe;
	if (crossorigin) {
		iframe.src = "data:text/html,<script>onresize=function(){parent.postMessage(0,'*')}</script>";
		unsubscribe = dom_listen(
			window,
			'message',
			/** @param {MessageEvent} event */ (event) => {
				if (event.source === iframe.contentWindow) fn();
			}
		);
	} else {
		iframe.src = 'about:blank';
		iframe.onload = () => {
			unsubscribe = dom_listen(iframe.contentWindow, 'resize', fn);
			// make sure an initial resize event is fired _after_ the iframe is loaded (which is asynchronous)
			// see https://github.com/sveltejs/svelte/issues/4233
			fn();
		};
	}
	dom_append(node, iframe);
	return () => {
		if (crossorigin) {
			unsubscribe();
		} else if (unsubscribe && iframe.contentWindow) {
			unsubscribe();
		}
		dom_detach(iframe);
	};
}
const resize_observer_content_box = /* @__PURE__ */ new ResizeObserverSingleton({
	box: 'content-box'
});
const resize_observer_border_box = /* @__PURE__ */ new ResizeObserverSingleton({
	box: 'border-box'
});
const resize_observer_device_pixel_content_box = /* @__PURE__ */ new ResizeObserverSingleton(
	{ box: 'device-pixel-content-box' }
);


/**
 * @returns {void} */
function toggle_class(element, name, toggle) {
	// The `!!` is required because an `undefined` flag means flipping the current state.
	element.classList.toggle(name, !!toggle);
}

/**
 * @template T
 * @param {string} type
 * @param {T} [detail]
 * @param {{ bubbles?: boolean, cancelable?: boolean }} [options]
 * @returns {CustomEvent<T>}
 */
function dom_custom_event(type, detail, { bubbles = false, cancelable = false } = {}) {
	return new CustomEvent(type, { detail, bubbles, cancelable });
}

/**
 * @param {string} selector
 * @param {HTMLElement} parent
 * @returns {ChildNodeArray}
 */
function query_selector_all(selector, parent = document.body) {
	return Array.from(parent.querySelectorAll(selector));
}

/**
 * @param {string} nodeId
 * @param {HTMLElement} head
 * @returns {any[]}
 */
function head_selector(nodeId, head) {
	const result = [];
	let started = 0;
	for (const node of head.childNodes) {
		if (node.nodeType === 8 /* comment node */) {
			const comment = node.textContent.trim();
			if (comment === `HEAD_${nodeId}_END`) {
				started -= 1;
				result.push(node);
			} else if (comment === `HEAD_${nodeId}_START`) {
				started += 1;
				result.push(node);
			}
		} else if (started > 0) {
			result.push(node);
		}
	}
	return result;
}
/** */
class HtmlTag {
	/**
	 * @private
	 * @default false
	 */
	is_svg = false;
	/** parent for creating node */
	e = undefined;
	/** html tag nodes */
	n = undefined;
	/** target */
	t = undefined;
	/** anchor */
	a = undefined;
	constructor(is_svg = false) {
		this.is_svg = is_svg;
		this.e = this.n = null;
	}

	/**
	 * @param {string} html
	 * @returns {void}
	 */
	c(html) {
		this.h(html);
	}

	/**
	 * @param {string} html
	 * @param {HTMLElement | SVGElement} target
	 * @param {HTMLElement | SVGElement} anchor
	 * @returns {void}
	 */
	m(html, target, anchor = null) {
		if (!this.e) {
			if (this.is_svg)
				this.e = svg_element(/** @type {keyof SVGElementTagNameMap} */ (target.nodeName));
			/** #7364  target for <template> may be provided as #document-fragment(11) */ else
				this.e = dom_element(
					/** @type {keyof HTMLElementTagNameMap} */ (
						target.nodeType === 11 ? 'TEMPLATE' : target.nodeName
					)
				);
			this.t =
				target.tagName !== 'TEMPLATE'
					? target
					: /** @type {HTMLTemplateElement} */ (target).content;
			this.c(html);
		}
		this.i(anchor);
	}

	/**
	 * @param {string} html
	 * @returns {void}
	 */
	h(html) {
		this.e.innerHTML = html;
		this.n = Array.from(
			this.e.nodeName === 'TEMPLATE' ? this.e.content.childNodes : this.e.childNodes
		);
	}

	/**
	 * @returns {void} */
	i(anchor) {
		for (let i = 0; i < this.n.length; i += 1) {
			dom_insert(this.t, this.n[i], anchor);
		}
	}

	/**
	 * @param {string} html
	 * @returns {void}
	 */
	p(html) {
		this.d();
		this.h(html);
		this.i(this.a);
	}

	/**
	 * @returns {void} */
	d() {
		this.n.forEach(dom_detach);
	}
}

class HtmlTagHydration extends HtmlTag {
	/** @type {Element[]} hydration claimed nodes */
	l = undefined;

	constructor(is_svg = false, claimed_nodes) {
		super(is_svg);
		this.e = this.n = null;
		this.l = claimed_nodes;
	}

	/**
	 * @param {string} html
	 * @returns {void}
	 */
	c(html) {
		if (this.l) {
			this.n = this.l;
		} else {
			super.c(html);
		}
	}

	/**
	 * @returns {void} */
	i(anchor) {
		for (let i = 0; i < this.n.length; i += 1) {
			dom_insert_hydration(this.t, this.n[i], anchor);
		}
	}
}

/**
 * @param {NamedNodeMap} attributes
 * @returns {{}}
 */
function attribute_to_object(attributes) {
	const result = {};
	for (const attribute of attributes) {
		result[attribute.name] = attribute.value;
	}
	return result;
}

const escaped = {
	'"': '&quot;',
	'&': '&amp;',
	'<': '&lt;'
};

const regex_attribute_characters_to_escape = /["&<]/g;

/**
 * Note that the attribute itself should be surrounded in double quotes
 * @param {any} attribute
 */
function escape_attribute(attribute) {
	return String(attribute).replace(regex_attribute_characters_to_escape, (match) => escaped[match]);
}

/**
 * @param {Record<string, string>} attributes
 */
function stringify_spread(attributes) {
	let str = ' ';
	for (const key in attributes) {
		if (attributes[key] != null) {
			str += `${key}="${escape_attribute(attributes[key])}" `;
		}
	}

	return str;
}

/**
 * @param {HTMLElement} element
 * @returns {{}}
 */
function get_custom_elements_slots(element) {
	const result = {};
	element.childNodes.forEach(
		/** @param {Element} node */ (node) => {
			result[node.slot || 'default'] = true;
		}
	);
	return result;
}

function construct_svelte_component(component, props) {
	return new component(props);
}

/**
 * @typedef {Node & {
 * 	claim_order?: number;
 * 	hydrate_init?: true;
 * 	actual_end_child?: NodeEx;
 * 	childNodes: NodeListOf<NodeEx>;
 * }} NodeEx
 */

/** @typedef {ChildNode & NodeEx} ChildNodeEx */

/** @typedef {NodeEx & { claim_order: number }} NodeEx2 */

/**
 * @typedef {ChildNodeEx[] & {
 * 	claim_info?: {
 * 		last_index: number;
 * 		total_claimed: number;
 * 	};
 * }} ChildNodeArray
 */

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/style_manager.js



// we need to store the information for multiple documents because a Svelte application could also contain iframes
// https://github.com/sveltejs/svelte/issues/3624
/** @type {Map<Document | ShadowRoot, import('./private.d.ts').StyleInformation>} */
const managed_styles = new Map();

let active = 0;

// https://github.com/darkskyapp/string-hash/blob/master/index.js
/**
 * @param {string} str
 * @returns {number}
 */
function hash(str) {
	let hash = 5381;
	let i = str.length;
	while (i--) hash = ((hash << 5) - hash) ^ str.charCodeAt(i);
	return hash >>> 0;
}

/**
 * @param {Document | ShadowRoot} doc
 * @param {Element & ElementCSSInlineStyle} node
 * @returns {{ stylesheet: any; rules: {}; }}
 */
function create_style_information(doc, node) {
	const info = { stylesheet: append_empty_stylesheet(node), rules: {} };
	managed_styles.set(doc, info);
	return info;
}

/**
 * @param {Element & ElementCSSInlineStyle} node
 * @param {number} a
 * @param {number} b
 * @param {number} duration
 * @param {number} delay
 * @param {(t: number) => number} ease
 * @param {(t: number, u: number) => string} fn
 * @param {number} uid
 * @returns {string}
 */
function style_manager_create_rule(node, a, b, duration, delay, ease, fn, uid = 0) {
	const step = 16.666 / duration;
	let keyframes = '{\n';
	for (let p = 0; p <= 1; p += step) {
		const t = a + (b - a) * ease(p);
		keyframes += p * 100 + `%{${fn(t, 1 - t)}}\n`;
	}
	const rule = keyframes + `100% {${fn(b, 1 - b)}}\n}`;
	const name = `__svelte_${hash(rule)}_${uid}`;
	const doc = get_root_for_style(node);
	const { stylesheet, rules } = managed_styles.get(doc) || create_style_information(doc, node);
	if (!rules[name]) {
		rules[name] = true;
		stylesheet.insertRule(`@keyframes ${name} ${rule}`, stylesheet.cssRules.length);
	}
	const animation = node.style.animation || '';
	node.style.animation = `${
		animation ? `${animation}, ` : ''
	}${name} ${duration}ms linear ${delay}ms 1 both`;
	active += 1;
	return name;
}

/**
 * @param {Element & ElementCSSInlineStyle} node
 * @param {string} [name]
 * @returns {void}
 */
function style_manager_delete_rule(node, name) {
	const previous = (node.style.animation || '').split(', ');
	const next = previous.filter(
		name
			? (anim) => anim.indexOf(name) < 0 // remove specific animation
			: (anim) => anim.indexOf('__svelte') === -1 // remove all Svelte animations
	);
	const deleted = previous.length - next.length;
	if (deleted) {
		node.style.animation = next.join(', ');
		active -= deleted;
		if (!active) clear_rules();
	}
}

/** @returns {void} */
function clear_rules() {
	raf(() => {
		if (active) return;
		managed_styles.forEach((info) => {
			const { ownerNode } = info.stylesheet;
			// there is no ownerNode if it runs on jsdom.
			if (ownerNode) dom_detach(ownerNode);
		});
		managed_styles.clear();
	});
}

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/animations.js





/**
 * @param {Element & ElementCSSInlineStyle} node
 * @param {import('./private.js').PositionRect} from
 * @param {import('./private.js').AnimationFn} fn
 */
function create_animation(node, from, fn, params) {
	if (!from) return noop;
	const to = node.getBoundingClientRect();
	if (
		from.left === to.left &&
		from.right === to.right &&
		from.top === to.top &&
		from.bottom === to.bottom
	)
		return noop;
	const {
		delay = 0,
		duration = 300,
		easing = linear,
		// @ts-ignore todo: should this be separated from destructuring? Or start/end added to public api and documentation?
		start: start_time = now() + delay,
		// @ts-ignore todo:
		end = start_time + duration,
		tick = noop,
		css
	} = fn(node, { from, to }, params);
	let running = true;
	let started = false;
	let name;
	/** @returns {void} */
	function start() {
		if (css) {
			name = create_rule(node, 0, 1, duration, delay, easing, css);
		}
		if (!delay) {
			started = true;
		}
	}
	/** @returns {void} */
	function stop() {
		if (css) delete_rule(node, name);
		running = false;
	}
	loop((now) => {
		if (!started && now >= start_time) {
			started = true;
		}
		if (started && now >= end) {
			tick(1, 0);
			stop();
		}
		if (!running) {
			return false;
		}
		if (started) {
			const p = now - start_time;
			const t = 0 + 1 * easing(p / duration);
			tick(t, 1 - t);
		}
		return true;
	});
	start();
	tick(0, 1);
	return stop;
}

/**
 * @param {Element & ElementCSSInlineStyle} node
 * @returns {void}
 */
function fix_position(node) {
	const style = getComputedStyle(node);
	if (style.position !== 'absolute' && style.position !== 'fixed') {
		const { width, height } = style;
		const a = node.getBoundingClientRect();
		node.style.position = 'absolute';
		node.style.width = width;
		node.style.height = height;
		add_transform(node, a);
	}
}

/**
 * @param {Element & ElementCSSInlineStyle} node
 * @param {import('./private.js').PositionRect} a
 * @returns {void}
 */
function add_transform(node, a) {
	const b = node.getBoundingClientRect();
	if (a.left !== b.left || a.top !== b.top) {
		const style = getComputedStyle(node);
		const transform = style.transform === 'none' ? '' : style.transform;
		node.style.transform = `${transform} translate(${a.left - b.left}px, ${a.top - b.top}px)`;
	}
}

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/lifecycle.js


let lifecycle_current_component;

/** @returns {void} */
function lifecycle_set_current_component(component) {
	lifecycle_current_component = component;
}

function lifecycle_get_current_component() {
	if (!lifecycle_current_component) throw new Error('Function called outside component initialization');
	return lifecycle_current_component;
}

/**
 * Schedules a callback to run immediately before the component is updated after any state change.
 *
 * The first time the callback runs will be before the initial `onMount`
 *
 * https://svelte.dev/docs/svelte#beforeupdate
 * @param {() => any} fn
 * @returns {void}
 */
function beforeUpdate(fn) {
	lifecycle_get_current_component().$$.before_update.push(fn);
}

/**
 * The `onMount` function schedules a callback to run as soon as the component has been mounted to the DOM.
 * It must be called during the component's initialisation (but doesn't need to live *inside* the component;
 * it can be called from an external module).
 *
 * If a function is returned _synchronously_ from `onMount`, it will be called when the component is unmounted.
 *
 * `onMount` does not run inside a [server-side component](https://svelte.dev/docs#run-time-server-side-component-api).
 *
 * https://svelte.dev/docs/svelte#onmount
 * @template T
 * @param {() => import('./private.js').NotFunction<T> | Promise<import('./private.js').NotFunction<T>> | (() => any)} fn
 * @returns {void}
 */
function onMount(fn) {
	lifecycle_get_current_component().$$.on_mount.push(fn);
}

/**
 * Schedules a callback to run immediately after the component has been updated.
 *
 * The first time the callback runs will be after the initial `onMount`
 *
 * https://svelte.dev/docs/svelte#afterupdate
 * @param {() => any} fn
 * @returns {void}
 */
function afterUpdate(fn) {
	lifecycle_get_current_component().$$.after_update.push(fn);
}

/**
 * Schedules a callback to run immediately before the component is unmounted.
 *
 * Out of `onMount`, `beforeUpdate`, `afterUpdate` and `onDestroy`, this is the
 * only one that runs inside a server-side component.
 *
 * https://svelte.dev/docs/svelte#ondestroy
 * @param {() => any} fn
 * @returns {void}
 */
function onDestroy(fn) {
	lifecycle_get_current_component().$$.on_destroy.push(fn);
}

/**
 * Creates an event dispatcher that can be used to dispatch [component events](https://svelte.dev/docs#template-syntax-component-directives-on-eventname).
 * Event dispatchers are functions that can take two arguments: `name` and `detail`.
 *
 * Component events created with `createEventDispatcher` create a
 * [CustomEvent](https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent).
 * These events do not [bubble](https://developer.mozilla.org/en-US/docs/Learn/JavaScript/Building_blocks/Events#Event_bubbling_and_capture).
 * The `detail` argument corresponds to the [CustomEvent.detail](https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/detail)
 * property and can contain any type of data.
 *
 * The event dispatcher can be typed to narrow the allowed event names and the type of the `detail` argument:
 * ```ts
 * const dispatch = createEventDispatcher<{
 *  loaded: never; // does not take a detail argument
 *  change: string; // takes a detail argument of type string, which is required
 *  optional: number | null; // takes an optional detail argument of type number
 * }>();
 * ```
 *
 * https://svelte.dev/docs/svelte#createeventdispatcher
 * @template {Record<string, any>} [EventMap=any]
 * @returns {import('./public.js').EventDispatcher<EventMap>}
 */
function createEventDispatcher() {
	const component = lifecycle_get_current_component();
	return (type, detail, { cancelable = false } = {}) => {
		const callbacks = component.$$.callbacks[type];
		if (callbacks) {
			// TODO are there situations where events could be dispatched
			// in a server (non-DOM) environment?
			const event = dom_custom_event(/** @type {string} */ (type), detail, { cancelable });
			callbacks.slice().forEach((fn) => {
				fn.call(component, event);
			});
			return !event.defaultPrevented;
		}
		return true;
	};
}

/**
 * Associates an arbitrary `context` object with the current component and the specified `key`
 * and returns that object. The context is then available to children of the component
 * (including slotted content) with `getContext`.
 *
 * Like lifecycle functions, this must be called during component initialisation.
 *
 * https://svelte.dev/docs/svelte#setcontext
 * @template T
 * @param {any} key
 * @param {T} context
 * @returns {T}
 */
function setContext(key, context) {
	lifecycle_get_current_component().$$.context.set(key, context);
	return context;
}

/**
 * Retrieves the context that belongs to the closest parent component with the specified `key`.
 * Must be called during component initialisation.
 *
 * https://svelte.dev/docs/svelte#getcontext
 * @template T
 * @param {any} key
 * @returns {T}
 */
function getContext(key) {
	return lifecycle_get_current_component().$$.context.get(key);
}

/**
 * Retrieves the whole context map that belongs to the closest parent component.
 * Must be called during component initialisation. Useful, for example, if you
 * programmatically create a component and want to pass the existing context to it.
 *
 * https://svelte.dev/docs/svelte#getallcontexts
 * @template {Map<any, any>} [T=Map<any, any>]
 * @returns {T}
 */
function getAllContexts() {
	return lifecycle_get_current_component().$$.context;
}

/**
 * Checks whether a given `key` has been set in the context of a parent component.
 * Must be called during component initialisation.
 *
 * https://svelte.dev/docs/svelte#hascontext
 * @param {any} key
 * @returns {boolean}
 */
function hasContext(key) {
	return lifecycle_get_current_component().$$.context.has(key);
}

// TODO figure out if we still want to support
// shorthand events, or if we want to implement
// a real bubbling mechanism
/**
 * @param component
 * @param event
 * @returns {void}
 */
function bubble(component, event) {
	const callbacks = component.$$.callbacks[event.type];
	if (callbacks) {
		// @ts-ignore
		callbacks.slice().forEach((fn) => fn.call(this, event));
	}
}

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/scheduler.js



const dirty_components = [];
const intros = { enabled: false };
const binding_callbacks = [];

let render_callbacks = [];

const flush_callbacks = [];

const resolved_promise = /* @__PURE__ */ Promise.resolve();

let update_scheduled = false;

/** @returns {void} */
function schedule_update() {
	if (!update_scheduled) {
		update_scheduled = true;
		resolved_promise.then(scheduler_flush);
	}
}

/** @returns {Promise<void>} */
function tick() {
	schedule_update();
	return resolved_promise;
}

/** @returns {void} */
function add_render_callback(fn) {
	render_callbacks.push(fn);
}

/** @returns {void} */
function add_flush_callback(fn) {
	flush_callbacks.push(fn);
}

// flush() calls callbacks in this order:
// 1. All beforeUpdate callbacks, in order: parents before children
// 2. All bind:this callbacks, in reverse order: children before parents.
// 3. All afterUpdate callbacks, in order: parents before children. EXCEPT
//    for afterUpdates called during the initial onMount, which are called in
//    reverse order: children before parents.
// Since callbacks might update component values, which could trigger another
// call to flush(), the following steps guard against this:
// 1. During beforeUpdate, any updated components will be added to the
//    dirty_components array and will cause a reentrant call to flush(). Because
//    the flush index is kept outside the function, the reentrant call will pick
//    up where the earlier call left off and go through all dirty components. The
//    current_component value is saved and restored so that the reentrant call will
//    not interfere with the "parent" flush() call.
// 2. bind:this callbacks cannot trigger new flush() calls.
// 3. During afterUpdate, any updated components will NOT have their afterUpdate
//    callback called a second time; the seen_callbacks set, outside the flush()
//    function, guarantees this behavior.
const seen_callbacks = new Set();

let flushidx = 0; // Do *not* move this inside the flush() function

/** @returns {void} */
function scheduler_flush() {
	// Do not reenter flush while dirty components are updated, as this can
	// result in an infinite loop. Instead, let the inner flush handle it.
	// Reentrancy is ok afterwards for bindings etc.
	if (flushidx !== 0) {
		return;
	}
	const saved_component = lifecycle_current_component;
	do {
		// first, call beforeUpdate functions
		// and update components
		try {
			while (flushidx < dirty_components.length) {
				const component = dirty_components[flushidx];
				flushidx++;
				lifecycle_set_current_component(component);
				update(component.$$);
			}
		} catch (e) {
			// reset dirty state to not end up in a deadlocked state and then rethrow
			dirty_components.length = 0;
			flushidx = 0;
			throw e;
		}
		lifecycle_set_current_component(null);
		dirty_components.length = 0;
		flushidx = 0;
		while (binding_callbacks.length) binding_callbacks.pop()();
		// then, once components are updated, call
		// afterUpdate functions. This may cause
		// subsequent updates...
		for (let i = 0; i < render_callbacks.length; i += 1) {
			const callback = render_callbacks[i];
			if (!seen_callbacks.has(callback)) {
				// ...so guard against infinite loops
				seen_callbacks.add(callback);
				callback();
			}
		}
		render_callbacks.length = 0;
	} while (dirty_components.length);
	while (flush_callbacks.length) {
		flush_callbacks.pop()();
	}
	update_scheduled = false;
	seen_callbacks.clear();
	lifecycle_set_current_component(saved_component);
}

/** @returns {void} */
function update($$) {
	if ($$.fragment !== null) {
		$$.update();
		utils_run_all($$.before_update);
		const dirty = $$.dirty;
		$$.dirty = [-1];
		$$.fragment && $$.fragment.p($$.ctx, dirty);
		$$.after_update.forEach(add_render_callback);
	}
}

/**
 * Useful for example to execute remaining `afterUpdate` callbacks before executing `destroy`.
 * @param {Function[]} fns
 * @returns {void}
 */
function flush_render_callbacks(fns) {
	const filtered = [];
	const targets = [];
	render_callbacks.forEach((c) => (fns.indexOf(c) === -1 ? filtered.push(c) : targets.push(c)));
	targets.forEach((c) => c());
	render_callbacks = filtered;
}

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/transitions.js







/**
 * @type {Promise<void> | null}
 */
let promise;

/**
 * @returns {Promise<void>}
 */
function wait() {
	if (!promise) {
		promise = Promise.resolve();
		promise.then(() => {
			promise = null;
		});
	}
	return promise;
}

/**
 * @param {Element} node
 * @param {INTRO | OUTRO | boolean} direction
 * @param {'start' | 'end'} kind
 * @returns {void}
 */
function dispatch(node, direction, kind) {
	node.dispatchEvent(dom_custom_event(`${direction ? 'intro' : 'outro'}${kind}`));
}

const outroing = new Set();

/**
 * @type {Outro}
 */
let outros;

/**
 * @returns {void} */
function transitions_group_outros() {
	outros = {
		r: 0,
		c: [],
		p: outros // parent group
	};
}

/**
 * @returns {void} */
function transitions_check_outros() {
	if (!outros.r) {
		utils_run_all(outros.c);
	}
	outros = outros.p;
}

/**
 * @param {import('./private.js').Fragment} block
 * @param {0 | 1} [local]
 * @returns {void}
 */
function transitions_transition_in(block, local) {
	if (block && block.i) {
		outroing.delete(block);
		block.i(local);
	}
}

/**
 * @param {import('./private.js').Fragment} block
 * @param {0 | 1} local
 * @param {0 | 1} [detach]
 * @param {() => void} [callback]
 * @returns {void}
 */
function transitions_transition_out(block, local, detach, callback) {
	if (block && block.o) {
		if (outroing.has(block)) return;
		outroing.add(block);
		outros.c.push(() => {
			outroing.delete(block);
			if (callback) {
				if (detach) block.d(1);
				callback();
			}
		});
		block.o(local);
	} else if (callback) {
		callback();
	}
}

/**
 * @type {import('../transition/public.js').TransitionConfig}
 */
const null_transition = { duration: 0 };

/**
 * @param {Element & ElementCSSInlineStyle} node
 * @param {TransitionFn} fn
 * @param {any} params
 * @returns {{ start(): void; invalidate(): void; end(): void; }}
 */
function create_in_transition(node, fn, params) {
	/**
	 * @type {TransitionOptions} */
	const options = { direction: 'in' };
	let config = fn(node, params, options);
	let running = false;
	let animation_name;
	let task;
	let uid = 0;

	/**
	 * @returns {void} */
	function cleanup() {
		if (animation_name) style_manager_delete_rule(node, animation_name);
	}

	/**
	 * @returns {void} */
	function go() {
		const {
			delay = 0,
			duration = 300,
			easing = identity,
			tick = utils_noop,
			css
		} = config || null_transition;
		if (css) animation_name = style_manager_create_rule(node, 0, 1, duration, delay, easing, css, uid++);
		tick(0, 1);
		const start_time = environment_now() + delay;
		const end_time = start_time + duration;
		if (task) task.abort();
		running = true;
		add_render_callback(() => dispatch(node, true, 'start'));
		task = loop_loop((now) => {
			if (running) {
				if (now >= end_time) {
					tick(1, 0);
					dispatch(node, true, 'end');
					cleanup();
					return (running = false);
				}
				if (now >= start_time) {
					const t = easing((now - start_time) / duration);
					tick(t, 1 - t);
				}
			}
			return running;
		});
	}
	let started = false;
	return {
		start() {
			if (started) return;
			started = true;
			style_manager_delete_rule(node);
			if (utils_is_function(config)) {
				config = config(options);
				wait().then(go);
			} else {
				go();
			}
		},
		invalidate() {
			started = false;
		},
		end() {
			if (running) {
				cleanup();
				running = false;
			}
		}
	};
}

/**
 * @param {Element & ElementCSSInlineStyle} node
 * @param {TransitionFn} fn
 * @param {any} params
 * @returns {{ end(reset: any): void; }}
 */
function create_out_transition(node, fn, params) {
	/** @type {TransitionOptions} */
	const options = { direction: 'out' };
	let config = fn(node, params, options);
	let running = true;
	let animation_name;
	const group = outros;
	group.r += 1;
	/** @type {boolean} */
	let original_inert_value;

	/**
	 * @returns {void} */
	function go() {
		const {
			delay = 0,
			duration = 300,
			easing = identity,
			tick = utils_noop,
			css
		} = config || null_transition;

		if (css) animation_name = style_manager_create_rule(node, 1, 0, duration, delay, easing, css);

		const start_time = environment_now() + delay;
		const end_time = start_time + duration;
		add_render_callback(() => dispatch(node, false, 'start'));

		if ('inert' in node) {
			original_inert_value = /** @type {HTMLElement} */ (node).inert;
			node.inert = true;
		}

		loop_loop((now) => {
			if (running) {
				if (now >= end_time) {
					tick(0, 1);
					dispatch(node, false, 'end');
					if (!--group.r) {
						// this will result in `end()` being called,
						// so we don't need to clean up here
						utils_run_all(group.c);
					}
					return false;
				}
				if (now >= start_time) {
					const t = easing((now - start_time) / duration);
					tick(1 - t, t);
				}
			}
			return running;
		});
	}

	if (utils_is_function(config)) {
		wait().then(() => {
			// @ts-ignore
			config = config(options);
			go();
		});
	} else {
		go();
	}

	return {
		end(reset) {
			if (reset && 'inert' in node) {
				node.inert = original_inert_value;
			}
			if (reset && config.tick) {
				config.tick(1, 0);
			}
			if (running) {
				if (animation_name) style_manager_delete_rule(node, animation_name);
				running = false;
			}
		}
	};
}

/**
 * @param {Element & ElementCSSInlineStyle} node
 * @param {TransitionFn} fn
 * @param {any} params
 * @param {boolean} intro
 * @returns {{ run(b: 0 | 1): void; end(): void; }}
 */
function create_bidirectional_transition(node, fn, params, intro) {
	/**
	 * @type {TransitionOptions} */
	const options = { direction: 'both' };
	let config = fn(node, params, options);
	let t = intro ? 0 : 1;

	/**
	 * @type {Program | null} */
	let running_program = null;

	/**
	 * @type {PendingProgram | null} */
	let pending_program = null;
	let animation_name = null;

	/** @type {boolean} */
	let original_inert_value;

	/**
	 * @returns {void} */
	function clear_animation() {
		if (animation_name) style_manager_delete_rule(node, animation_name);
	}

	/**
	 * @param {PendingProgram} program
	 * @param {number} duration
	 * @returns {Program}
	 */
	function init(program, duration) {
		const d = /** @type {Program['d']} */ (program.b - t);
		duration *= Math.abs(d);
		return {
			a: t,
			b: program.b,
			d,
			duration,
			start: program.start,
			end: program.start + duration,
			group: program.group
		};
	}

	/**
	 * @param {INTRO | OUTRO} b
	 * @returns {void}
	 */
	function go(b) {
		const {
			delay = 0,
			duration = 300,
			easing = identity,
			tick = utils_noop,
			css
		} = config || null_transition;

		/**
		 * @type {PendingProgram} */
		const program = {
			start: environment_now() + delay,
			b
		};

		if (!b) {
			// @ts-ignore todo: improve typings
			program.group = outros;
			outros.r += 1;
		}

		if ('inert' in node) {
			if (b) {
				if (original_inert_value !== undefined) {
					// aborted/reversed outro — restore previous inert value
					node.inert = original_inert_value;
				}
			} else {
				original_inert_value = /** @type {HTMLElement} */ (node).inert;
				node.inert = true;
			}
		}

		if (running_program || pending_program) {
			pending_program = program;
		} else {
			// if this is an intro, and there's a delay, we need to do
			// an initial tick and/or apply CSS animation immediately
			if (css) {
				clear_animation();
				animation_name = style_manager_create_rule(node, t, b, duration, delay, easing, css);
			}
			if (b) tick(0, 1);
			running_program = init(program, duration);
			add_render_callback(() => dispatch(node, b, 'start'));
			loop_loop((now) => {
				if (pending_program && now > pending_program.start) {
					running_program = init(pending_program, duration);
					pending_program = null;
					dispatch(node, running_program.b, 'start');
					if (css) {
						clear_animation();
						animation_name = style_manager_create_rule(
							node,
							t,
							running_program.b,
							running_program.duration,
							0,
							easing,
							config.css
						);
					}
				}
				if (running_program) {
					if (now >= running_program.end) {
						tick((t = running_program.b), 1 - t);
						dispatch(node, running_program.b, 'end');
						if (!pending_program) {
							// we're done
							if (running_program.b) {
								// intro — we can tidy up immediately
								clear_animation();
							} else {
								// outro — needs to be coordinated
								if (!--running_program.group.r) utils_run_all(running_program.group.c);
							}
						}
						running_program = null;
					} else if (now >= running_program.start) {
						const p = now - running_program.start;
						t = running_program.a + running_program.d * easing(p / running_program.duration);
						tick(t, 1 - t);
					}
				}
				return !!(running_program || pending_program);
			});
		}
	}
	return {
		run(b) {
			if (utils_is_function(config)) {
				wait().then(() => {
					const opts = { direction: b ? 'in' : 'out' };
					// @ts-ignore
					config = config(opts);
					go(b);
				});
			} else {
				go(b);
			}
		},
		end() {
			clear_animation();
			running_program = pending_program = null;
		}
	};
}

/** @typedef {1} INTRO */
/** @typedef {0} OUTRO */
/** @typedef {{ direction: 'in' | 'out' | 'both' }} TransitionOptions */
/** @typedef {(node: Element, params: any, options: TransitionOptions) => import('../transition/public.js').TransitionConfig} TransitionFn */

/**
 * @typedef {Object} Outro
 * @property {number} r
 * @property {Function[]} c
 * @property {Object} p
 */

/**
 * @typedef {Object} PendingProgram
 * @property {number} start
 * @property {INTRO|OUTRO} b
 * @property {Outro} [group]
 */

/**
 * @typedef {Object} Program
 * @property {number} a
 * @property {INTRO|OUTRO} b
 * @property {1|-1} d
 * @property {number} duration
 * @property {number} start
 * @property {number} end
 * @property {Outro} [group]
 */

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/await_block.js





/**
 * @template T
 * @param {Promise<T>} promise
 * @param {import('./private.js').PromiseInfo<T>} info
 * @returns {boolean}
 */
function handle_promise(promise, info) {
	const token = (info.token = {});
	/**
	 * @param {import('./private.js').FragmentFactory} type
	 * @param {0 | 1 | 2} index
	 * @param {number} [key]
	 * @param {any} [value]
	 * @returns {void}
	 */
	function update(type, index, key, value) {
		if (info.token !== token) return;
		info.resolved = value;
		let child_ctx = info.ctx;
		if (key !== undefined) {
			child_ctx = child_ctx.slice();
			child_ctx[key] = value;
		}
		const block = type && (info.current = type)(child_ctx);
		let needs_flush = false;
		if (info.block) {
			if (info.blocks) {
				info.blocks.forEach((block, i) => {
					if (i !== index && block) {
						group_outros();
						transition_out(block, 1, 1, () => {
							if (info.blocks[i] === block) {
								info.blocks[i] = null;
							}
						});
						check_outros();
					}
				});
			} else {
				info.block.d(1);
			}
			block.c();
			transition_in(block, 1);
			block.m(info.mount(), info.anchor);
			needs_flush = true;
		}
		info.block = block;
		if (info.blocks) info.blocks[index] = block;
		if (needs_flush) {
			flush();
		}
	}
	if (is_promise(promise)) {
		const current_component = get_current_component();
		promise.then(
			(value) => {
				set_current_component(current_component);
				update(info.then, 1, info.value, value);
				set_current_component(null);
			},
			(error) => {
				set_current_component(current_component);
				update(info.catch, 2, info.error, error);
				set_current_component(null);
				if (!info.hasCatch) {
					throw error;
				}
			}
		);
		// if we previously had a then/catch block, destroy it
		if (info.current !== info.pending) {
			update(info.pending, 0);
			return true;
		}
	} else {
		if (info.current !== info.then) {
			update(info.then, 1, info.value, promise);
			return true;
		}
		info.resolved = /** @type {T} */ (promise);
	}
}

/** @returns {void} */
function update_await_block_branch(info, ctx, dirty) {
	const child_ctx = ctx.slice();
	const { resolved } = info;
	if (info.current === info.then) {
		child_ctx[info.value] = resolved;
	}
	if (info.current === info.catch) {
		child_ctx[info.error] = resolved;
	}
	info.block.p(child_ctx, dirty);
}

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/each.js



// general each functions:

function each_ensure_array_like(array_like_or_iterator) {
	return array_like_or_iterator?.length !== undefined
		? array_like_or_iterator
		: Array.from(array_like_or_iterator);
}

// keyed each functions:

/** @returns {void} */
function destroy_block(block, lookup) {
	block.d(1);
	lookup.delete(block.key);
}

/** @returns {void} */
function outro_and_destroy_block(block, lookup) {
	transitions_transition_out(block, 1, 1, () => {
		lookup.delete(block.key);
	});
}

/** @returns {void} */
function fix_and_destroy_block(block, lookup) {
	block.f();
	destroy_block(block, lookup);
}

/** @returns {void} */
function fix_and_outro_and_destroy_block(block, lookup) {
	block.f();
	outro_and_destroy_block(block, lookup);
}

/** @returns {any[]} */
function update_keyed_each(
	old_blocks,
	dirty,
	get_key,
	dynamic,
	ctx,
	list,
	lookup,
	node,
	destroy,
	create_each_block,
	next,
	get_context
) {
	let o = old_blocks.length;
	let n = list.length;
	let i = o;
	const old_indexes = {};
	while (i--) old_indexes[old_blocks[i].key] = i;
	const new_blocks = [];
	const new_lookup = new Map();
	const deltas = new Map();
	const updates = [];
	i = n;
	while (i--) {
		const child_ctx = get_context(ctx, list, i);
		const key = get_key(child_ctx);
		let block = lookup.get(key);
		if (!block) {
			block = create_each_block(key, child_ctx);
			block.c();
		} else if (dynamic) {
			// defer updates until all the DOM shuffling is done
			updates.push(() => block.p(child_ctx, dirty));
		}
		new_lookup.set(key, (new_blocks[i] = block));
		if (key in old_indexes) deltas.set(key, Math.abs(i - old_indexes[key]));
	}
	const will_move = new Set();
	const did_move = new Set();
	/** @returns {void} */
	function insert(block) {
		transitions_transition_in(block, 1);
		block.m(node, next);
		lookup.set(block.key, block);
		next = block.first;
		n--;
	}
	while (o && n) {
		const new_block = new_blocks[n - 1];
		const old_block = old_blocks[o - 1];
		const new_key = new_block.key;
		const old_key = old_block.key;
		if (new_block === old_block) {
			// do nothing
			next = new_block.first;
			o--;
			n--;
		} else if (!new_lookup.has(old_key)) {
			// remove old block
			destroy(old_block, lookup);
			o--;
		} else if (!lookup.has(new_key) || will_move.has(new_key)) {
			insert(new_block);
		} else if (did_move.has(old_key)) {
			o--;
		} else if (deltas.get(new_key) > deltas.get(old_key)) {
			did_move.add(new_key);
			insert(new_block);
		} else {
			will_move.add(old_key);
			o--;
		}
	}
	while (o--) {
		const old_block = old_blocks[o];
		if (!new_lookup.has(old_block.key)) destroy(old_block, lookup);
	}
	while (n) insert(new_blocks[n - 1]);
	utils_run_all(updates);
	return new_blocks;
}

/** @returns {void} */
function validate_each_keys(ctx, list, get_context, get_key) {
	const keys = new Map();
	for (let i = 0; i < list.length; i++) {
		const key = get_key(get_context(ctx, list, i));
		if (keys.has(key)) {
			let value = '';
			try {
				value = `with value '${String(key)}' `;
			} catch (e) {
				// can't stringify
			}
			throw new Error(
				`Cannot have duplicate keys in a keyed each: Keys at index ${keys.get(
					key
				)} and ${i} ${value}are duplicates`
			);
		}
		keys.set(key, i);
	}
}

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/shared/boolean_attributes.js
const _boolean_attributes = /** @type {const} */ ([
	'allowfullscreen',
	'allowpaymentrequest',
	'async',
	'autofocus',
	'autoplay',
	'checked',
	'controls',
	'default',
	'defer',
	'disabled',
	'formnovalidate',
	'hidden',
	'inert',
	'ismap',
	'loop',
	'multiple',
	'muted',
	'nomodule',
	'novalidate',
	'open',
	'playsinline',
	'readonly',
	'required',
	'reversed',
	'selected'
]);

/**
 * List of HTML boolean attributes (e.g. `<input disabled>`).
 * Source: https://html.spec.whatwg.org/multipage/indices.html
 *
 * @type {Set<string>}
 */
const boolean_attributes_boolean_attributes = new Set([..._boolean_attributes]);

/** @typedef {typeof _boolean_attributes[number]} BooleanAttributes */

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/ssr.js






const invalid_attribute_name_character =
	/[\s'">/=\u{FDD0}-\u{FDEF}\u{FFFE}\u{FFFF}\u{1FFFE}\u{1FFFF}\u{2FFFE}\u{2FFFF}\u{3FFFE}\u{3FFFF}\u{4FFFE}\u{4FFFF}\u{5FFFE}\u{5FFFF}\u{6FFFE}\u{6FFFF}\u{7FFFE}\u{7FFFF}\u{8FFFE}\u{8FFFF}\u{9FFFE}\u{9FFFF}\u{AFFFE}\u{AFFFF}\u{BFFFE}\u{BFFFF}\u{CFFFE}\u{CFFFF}\u{DFFFE}\u{DFFFF}\u{EFFFE}\u{EFFFF}\u{FFFFE}\u{FFFFF}\u{10FFFE}\u{10FFFF}]/u;
// https://html.spec.whatwg.org/multipage/syntax.html#attributes-2
// https://infra.spec.whatwg.org/#noncharacter

/** @returns {string} */
function spread(args, attrs_to_add) {
	const attributes = Object.assign({}, ...args);
	if (attrs_to_add) {
		const classes_to_add = attrs_to_add.classes;
		const styles_to_add = attrs_to_add.styles;
		if (classes_to_add) {
			if (attributes.class == null) {
				attributes.class = classes_to_add;
			} else {
				attributes.class += ' ' + classes_to_add;
			}
		}
		if (styles_to_add) {
			if (attributes.style == null) {
				attributes.style = style_object_to_string(styles_to_add);
			} else {
				attributes.style = style_object_to_string(
					merge_ssr_styles(attributes.style, styles_to_add)
				);
			}
		}
	}
	let str = '';
	Object.keys(attributes).forEach((name) => {
		if (invalid_attribute_name_character.test(name)) return;
		const value = attributes[name];
		if (value === true) str += ' ' + name;
		else if (boolean_attributes.has(name.toLowerCase())) {
			if (value) str += ' ' + name;
		} else if (value != null) {
			str += ` ${name}="${value}"`;
		}
	});
	return str;
}

/** @returns {{}} */
function merge_ssr_styles(style_attribute, style_directive) {
	const style_object = {};
	for (const individual_style of style_attribute.split(';')) {
		const colon_index = individual_style.indexOf(':');
		const name = individual_style.slice(0, colon_index).trim();
		const value = individual_style.slice(colon_index + 1).trim();
		if (!name) continue;
		style_object[name] = value;
	}
	for (const name in style_directive) {
		const value = style_directive[name];
		if (value) {
			style_object[name] = value;
		} else {
			delete style_object[name];
		}
	}
	return style_object;
}

const ATTR_REGEX = /[&"]/g;
const CONTENT_REGEX = /[&<]/g;

/**
 * Note: this method is performance sensitive and has been optimized
 * https://github.com/sveltejs/svelte/pull/5701
 * @param {unknown} value
 * @returns {string}
 */
function ssr_escape(value, is_attr = false) {
	const str = String(value);
	const pattern = is_attr ? ATTR_REGEX : CONTENT_REGEX;
	pattern.lastIndex = 0;
	let escaped = '';
	let last = 0;
	while (pattern.test(str)) {
		const i = pattern.lastIndex - 1;
		const ch = str[i];
		escaped += str.substring(last, i) + (ch === '&' ? '&amp;' : ch === '"' ? '&quot;' : '&lt;');
		last = i + 1;
	}
	return escaped + str.substring(last);
}

function escape_attribute_value(value) {
	// keep booleans, null, and undefined for the sake of `spread`
	const should_escape = typeof value === 'string' || (value && typeof value === 'object');
	return should_escape ? ssr_escape(value, true) : value;
}

/** @returns {{}} */
function escape_object(obj) {
	const result = {};
	for (const key in obj) {
		result[key] = escape_attribute_value(obj[key]);
	}
	return result;
}

/** @returns {string} */
function each(items, fn) {
	items = ensure_array_like(items);
	let str = '';
	for (let i = 0; i < items.length; i += 1) {
		str += fn(items[i], i);
	}
	return str;
}

const missing_component = {
	$$render: () => ''
};

function validate_component(component, name) {
	if (!component || !component.$$render) {
		if (name === 'svelte:component') name += ' this={...}';
		throw new Error(
			`<${name}> is not a valid SSR component. You may need to review your build config to ensure that dependencies are compiled, rather than imported as pre-compiled modules. Otherwise you may need to fix a <${name}>.`
		);
	}
	return component;
}

/** @returns {string} */
function debug(file, line, column, values) {
	console.log(`{@debug} ${file ? file + ' ' : ''}(${line}:${column})`); // eslint-disable-line no-console
	console.log(values); // eslint-disable-line no-console
	return '';
}

let on_destroy;

/** @returns {{ render: (props?: {}, { $$slots, context }?: { $$slots?: {}; context?: Map<any, any>; }) => { html: any; css: { code: string; map: any; }; head: string; }; $$render: (result: any, props: any, bindings: any, slots: any, context: any) => any; }} */
function create_ssr_component(fn) {
	function $$render(result, props, bindings, slots, context) {
		const parent_component = current_component;
		const $$ = {
			on_destroy,
			context: new Map(context || (parent_component ? parent_component.$$.context : [])),
			// these will be immediately discarded
			on_mount: [],
			before_update: [],
			after_update: [],
			callbacks: blank_object()
		};
		set_current_component({ $$ });
		const html = fn(result, props, bindings, slots);
		set_current_component(parent_component);
		return html;
	}
	return {
		render: (props = {}, { $$slots = {}, context = new Map() } = {}) => {
			on_destroy = [];
			const result = { title: '', head: '', css: new Set() };
			const html = $$render(result, props, {}, $$slots, context);
			run_all(on_destroy);
			return {
				html,
				css: {
					code: Array.from(result.css)
						.map((css) => css.code)
						.join('\n'),
					map: null // TODO
				},
				head: result.title + result.head
			};
		},
		$$render
	};
}

/** @returns {string} */
function add_attribute(name, value, boolean) {
	if (value == null || (boolean && !value)) return '';
	const assignment = boolean && value === true ? '' : `="${ssr_escape(value, true)}"`;
	return ` ${name}${assignment}`;
}

/** @returns {string} */
function add_classes(classes) {
	return classes ? ` class="${classes}"` : '';
}

/** @returns {string} */
function style_object_to_string(style_object) {
	return Object.keys(style_object)
		.filter((key) => style_object[key] != null && style_object[key] !== '')
		.map((key) => `${key}: ${escape_attribute_value(style_object[key])};`)
		.join(' ');
}

/** @returns {string} */
function add_styles(style_object) {
	const styles = style_object_to_string(style_object);
	return styles ? ` style="${styles}"` : '';
}

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/Component.js






/** @returns {void} */
function bind(component, name, callback) {
	const index = component.$$.props[name];
	if (index !== undefined) {
		component.$$.bound[index] = callback;
		callback(component.$$.ctx[index]);
	}
}

/** @returns {void} */
function create_component(block) {
	block && block.c();
}

/** @returns {void} */
function claim_component(block, parent_nodes) {
	block && block.l(parent_nodes);
}

/** @returns {void} */
function mount_component(component, target, anchor) {
	const { fragment, after_update } = component.$$;
	fragment && fragment.m(target, anchor);
	// onMount happens before the initial afterUpdate
	add_render_callback(() => {
		const new_on_destroy = component.$$.on_mount.map(run).filter(utils_is_function);
		// if the component was destroyed immediately
		// it will update the `$$.on_destroy` reference to `null`.
		// the destructured on_destroy may still reference to the old array
		if (component.$$.on_destroy) {
			component.$$.on_destroy.push(...new_on_destroy);
		} else {
			// Edge case - component was destroyed immediately,
			// most likely as a result of a binding initialising
			utils_run_all(new_on_destroy);
		}
		component.$$.on_mount = [];
	});
	after_update.forEach(add_render_callback);
}

/** @returns {void} */
function destroy_component(component, detaching) {
	const $$ = component.$$;
	if ($$.fragment !== null) {
		flush_render_callbacks($$.after_update);
		utils_run_all($$.on_destroy);
		$$.fragment && $$.fragment.d(detaching);
		// TODO null out other refs, including component.$$ (but need to
		// preserve final state?)
		$$.on_destroy = $$.fragment = null;
		$$.ctx = [];
	}
}

/** @returns {void} */
function make_dirty(component, i) {
	if (component.$$.dirty[0] === -1) {
		dirty_components.push(component);
		schedule_update();
		component.$$.dirty.fill(0);
	}
	component.$$.dirty[(i / 31) | 0] |= 1 << i % 31;
}

// TODO: Document the other params
/**
 * @param {SvelteComponent} component
 * @param {import('./public.js').ComponentConstructorOptions} options
 *
 * @param {import('./utils.js')['not_equal']} not_equal Used to compare props and state values.
 * @param {(target: Element | ShadowRoot) => void} [append_styles] Function that appends styles to the DOM when the component is first initialised.
 * This will be the `add_css` function from the compiled component.
 *
 * @returns {void}
 */
function init(
	component,
	options,
	instance,
	create_fragment,
	not_equal,
	props,
	append_styles = null,
	dirty = [-1]
) {
	const parent_component = lifecycle_current_component;
	lifecycle_set_current_component(component);
	/** @type {import('./private.js').T$$} */
	const $$ = (component.$$ = {
		fragment: null,
		ctx: [],
		// state
		props,
		update: utils_noop,
		not_equal,
		bound: utils_blank_object(),
		// lifecycle
		on_mount: [],
		on_destroy: [],
		on_disconnect: [],
		before_update: [],
		after_update: [],
		context: new Map(options.context || (parent_component ? parent_component.$$.context : [])),
		// everything else
		callbacks: utils_blank_object(),
		dirty,
		skip_bound: false,
		root: options.target || parent_component.$$.root
	});
	append_styles && append_styles($$.root);
	let ready = false;
	$$.ctx = instance
		? instance(component, options.props || {}, (i, ret, ...rest) => {
				const value = rest.length ? rest[0] : ret;
				if ($$.ctx && not_equal($$.ctx[i], ($$.ctx[i] = value))) {
					if (!$$.skip_bound && $$.bound[i]) $$.bound[i](value);
					if (ready) make_dirty(component, i);
				}
				return ret;
		  })
		: [];
	$$.update();
	ready = true;
	utils_run_all($$.before_update);
	// `false` as a special case of no DOM component
	$$.fragment = create_fragment ? create_fragment($$.ctx) : false;
	if (options.target) {
		if (options.hydrate) {
			start_hydrating();
			// TODO: what is the correct type here?
			// @ts-expect-error
			const nodes = children(options.target);
			$$.fragment && $$.fragment.l(nodes);
			nodes.forEach(dom_detach);
		} else {
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			$$.fragment && $$.fragment.c();
		}
		if (options.intro) transitions_transition_in(component.$$.fragment);
		mount_component(component, options.target, options.anchor);
		end_hydrating();
		scheduler_flush();
	}
	lifecycle_set_current_component(parent_component);
}

let SvelteElement;

if (typeof HTMLElement === 'function') {
	SvelteElement = class extends HTMLElement {
		/** The Svelte component constructor */
		$$ctor;
		/** Slots */
		$$s;
		/** The Svelte component instance */
		$$c;
		/** Whether or not the custom element is connected */
		$$cn = false;
		/** Component props data */
		$$d = {};
		/** `true` if currently in the process of reflecting component props back to attributes */
		$$r = false;
		/** @type {Record<string, CustomElementPropDefinition>} Props definition (name, reflected, type etc) */
		$$p_d = {};
		/** @type {Record<string, Function[]>} Event listeners */
		$$l = {};
		/** @type {Map<Function, Function>} Event listener unsubscribe functions */
		$$l_u = new Map();

		constructor($$componentCtor, $$slots, use_shadow_dom) {
			super();
			this.$$ctor = $$componentCtor;
			this.$$s = $$slots;
			if (use_shadow_dom) {
				this.attachShadow({ mode: 'open' });
			}
		}

		addEventListener(type, listener, options) {
			// We can't determine upfront if the event is a custom event or not, so we have to
			// listen to both. If someone uses a custom event with the same name as a regular
			// browser event, this fires twice - we can't avoid that.
			this.$$l[type] = this.$$l[type] || [];
			this.$$l[type].push(listener);
			if (this.$$c) {
				const unsub = this.$$c.$on(type, listener);
				this.$$l_u.set(listener, unsub);
			}
			super.addEventListener(type, listener, options);
		}

		removeEventListener(type, listener, options) {
			super.removeEventListener(type, listener, options);
			if (this.$$c) {
				const unsub = this.$$l_u.get(listener);
				if (unsub) {
					unsub();
					this.$$l_u.delete(listener);
				}
			}
		}

		async connectedCallback() {
			this.$$cn = true;
			if (!this.$$c) {
				// We wait one tick to let possible child slot elements be created/mounted
				await Promise.resolve();
				if (!this.$$cn || this.$$c) {
					return;
				}
				function create_slot(name) {
					return () => {
						let node;
						const obj = {
							c: function create() {
								node = dom_element('slot');
								if (name !== 'default') {
									dom_attr(node, 'name', name);
								}
							},
							/**
							 * @param {HTMLElement} target
							 * @param {HTMLElement} [anchor]
							 */
							m: function mount(target, anchor) {
								dom_insert(target, node, anchor);
							},
							d: function destroy(detaching) {
								if (detaching) {
									dom_detach(node);
								}
							}
						};
						return obj;
					};
				}
				const $$slots = {};
				const existing_slots = get_custom_elements_slots(this);
				for (const name of this.$$s) {
					if (name in existing_slots) {
						$$slots[name] = [create_slot(name)];
					}
				}
				for (const attribute of this.attributes) {
					// this.$$data takes precedence over this.attributes
					const name = this.$$g_p(attribute.name);
					if (!(name in this.$$d)) {
						this.$$d[name] = get_custom_element_value(name, attribute.value, this.$$p_d, 'toProp');
					}
				}
				// Port over props that were set programmatically before ce was initialized
				for (const key in this.$$p_d) {
					if (!(key in this.$$d) && this[key] !== undefined) {
						this.$$d[key] = this[key]; // don't transform, these were set through JavaScript
						delete this[key]; // remove the property that shadows the getter/setter
					}
				}
				this.$$c = new this.$$ctor({
					target: this.shadowRoot || this,
					props: {
						...this.$$d,
						$$slots,
						$$scope: {
							ctx: []
						}
					}
				});

				// Reflect component props as attributes
				const reflect_attributes = () => {
					this.$$r = true;
					for (const key in this.$$p_d) {
						this.$$d[key] = this.$$c.$$.ctx[this.$$c.$$.props[key]];
						if (this.$$p_d[key].reflect) {
							const attribute_value = get_custom_element_value(
								key,
								this.$$d[key],
								this.$$p_d,
								'toAttribute'
							);
							if (attribute_value == null) {
								this.removeAttribute(this.$$p_d[key].attribute || key);
							} else {
								this.setAttribute(this.$$p_d[key].attribute || key, attribute_value);
							}
						}
					}
					this.$$r = false;
				};
				this.$$c.$$.after_update.push(reflect_attributes);
				reflect_attributes(); // once initially because after_update is added too late for first render

				for (const type in this.$$l) {
					for (const listener of this.$$l[type]) {
						const unsub = this.$$c.$on(type, listener);
						this.$$l_u.set(listener, unsub);
					}
				}
				this.$$l = {};
			}
		}

		// We don't need this when working within Svelte code, but for compatibility of people using this outside of Svelte
		// and setting attributes through setAttribute etc, this is helpful
		attributeChangedCallback(attr, _oldValue, newValue) {
			if (this.$$r) return;
			attr = this.$$g_p(attr);
			this.$$d[attr] = get_custom_element_value(attr, newValue, this.$$p_d, 'toProp');
			this.$$c?.$set({ [attr]: this.$$d[attr] });
		}

		disconnectedCallback() {
			this.$$cn = false;
			// In a microtask, because this could be a move within the DOM
			Promise.resolve().then(() => {
				if (!this.$$cn && this.$$c) {
					this.$$c.$destroy();
					this.$$c = undefined;
				}
			});
		}

		$$g_p(attribute_name) {
			return (
				Object.keys(this.$$p_d).find(
					(key) =>
						this.$$p_d[key].attribute === attribute_name ||
						(!this.$$p_d[key].attribute && key.toLowerCase() === attribute_name)
				) || attribute_name
			);
		}
	};
}

/**
 * @param {string} prop
 * @param {any} value
 * @param {Record<string, CustomElementPropDefinition>} props_definition
 * @param {'toAttribute' | 'toProp'} [transform]
 */
function get_custom_element_value(prop, value, props_definition, transform) {
	const type = props_definition[prop]?.type;
	value = type === 'Boolean' && typeof value !== 'boolean' ? value != null : value;
	if (!transform || !props_definition[prop]) {
		return value;
	} else if (transform === 'toAttribute') {
		switch (type) {
			case 'Object':
			case 'Array':
				return value == null ? null : JSON.stringify(value);
			case 'Boolean':
				return value ? '' : null;
			case 'Number':
				return value == null ? null : value;
			default:
				return value;
		}
	} else {
		switch (type) {
			case 'Object':
			case 'Array':
				return value && JSON.parse(value);
			case 'Boolean':
				return value; // conversion already handled above
			case 'Number':
				return value != null ? +value : value;
			default:
				return value;
		}
	}
}

/**
 * @internal
 *
 * Turn a Svelte component into a custom element.
 * @param {import('./public.js').ComponentType} Component  A Svelte component constructor
 * @param {Record<string, CustomElementPropDefinition>} props_definition  The props to observe
 * @param {string[]} slots  The slots to create
 * @param {string[]} accessors  Other accessors besides the ones for props the component has
 * @param {boolean} use_shadow_dom  Whether to use shadow DOM
 * @param {(ce: new () => HTMLElement) => new () => HTMLElement} [extend]
 */
function create_custom_element(
	Component,
	props_definition,
	slots,
	accessors,
	use_shadow_dom,
	extend
) {
	let Class = class extends SvelteElement {
		constructor() {
			super(Component, slots, use_shadow_dom);
			this.$$p_d = props_definition;
		}
		static get observedAttributes() {
			return Object.keys(props_definition).map((key) =>
				(props_definition[key].attribute || key).toLowerCase()
			);
		}
	};
	Object.keys(props_definition).forEach((prop) => {
		Object.defineProperty(Class.prototype, prop, {
			get() {
				return this.$$c && prop in this.$$c ? this.$$c[prop] : this.$$d[prop];
			},
			set(value) {
				value = get_custom_element_value(prop, value, props_definition);
				this.$$d[prop] = value;
				this.$$c?.$set({ [prop]: value });
			}
		});
	});
	accessors.forEach((accessor) => {
		Object.defineProperty(Class.prototype, accessor, {
			get() {
				return this.$$c?.[accessor];
			}
		});
	});
	if (extend) {
		// @ts-expect-error - assigning here is fine
		Class = extend(Class);
	}
	Component.element = /** @type {any} */ (Class);
	return Class;
}

/**
 * Base class for Svelte components. Used when dev=false.
 *
 * @template {Record<string, any>} [Props=any]
 * @template {Record<string, any>} [Events=any]
 */
class SvelteComponent {
	/**
	 * ### PRIVATE API
	 *
	 * Do not use, may change at any time
	 *
	 * @type {any}
	 */
	$$ = undefined;
	/**
	 * ### PRIVATE API
	 *
	 * Do not use, may change at any time
	 *
	 * @type {any}
	 */
	$$set = undefined;

	/** @returns {void} */
	$destroy() {
		destroy_component(this, 1);
		this.$destroy = utils_noop;
	}

	/**
	 * @template {Extract<keyof Events, string>} K
	 * @param {K} type
	 * @param {((e: Events[K]) => void) | null | undefined} callback
	 * @returns {() => void}
	 */
	$on(type, callback) {
		if (!utils_is_function(callback)) {
			return utils_noop;
		}
		const callbacks = this.$$.callbacks[type] || (this.$$.callbacks[type] = []);
		callbacks.push(callback);
		return () => {
			const index = callbacks.indexOf(callback);
			if (index !== -1) callbacks.splice(index, 1);
		};
	}

	/**
	 * @param {Partial<Props>} props
	 * @returns {void}
	 */
	$set(props) {
		if (this.$$set && !is_empty(props)) {
			this.$$.skip_bound = true;
			this.$$set(props);
			this.$$.skip_bound = false;
		}
	}
}

/**
 * @typedef {Object} CustomElementPropDefinition
 * @property {string} [attribute]
 * @property {boolean} [reflect]
 * @property {'String'|'Boolean'|'Number'|'Array'|'Object'} [type]
 */

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/dev.js







/**
 * @template T
 * @param {string} type
 * @param {T} [detail]
 * @returns {void}
 */
function dispatch_dev(type, detail) {
	document.dispatchEvent(custom_event(type, { version: VERSION, ...detail }, { bubbles: true }));
}

/**
 * @param {Node} target
 * @param {Node} node
 * @returns {void}
 */
function append_dev(target, node) {
	dispatch_dev('SvelteDOMInsert', { target, node });
	append(target, node);
}

/**
 * @param {Node} target
 * @param {Node} node
 * @returns {void}
 */
function append_hydration_dev(target, node) {
	dispatch_dev('SvelteDOMInsert', { target, node });
	append_hydration(target, node);
}

/**
 * @param {Node} target
 * @param {Node} node
 * @param {Node} [anchor]
 * @returns {void}
 */
function insert_dev(target, node, anchor) {
	dispatch_dev('SvelteDOMInsert', { target, node, anchor });
	insert(target, node, anchor);
}

/** @param {Node} target
 * @param {Node} node
 * @param {Node} [anchor]
 * @returns {void}
 */
function insert_hydration_dev(target, node, anchor) {
	dispatch_dev('SvelteDOMInsert', { target, node, anchor });
	insert_hydration(target, node, anchor);
}

/**
 * @param {Node} node
 * @returns {void}
 */
function detach_dev(node) {
	dispatch_dev('SvelteDOMRemove', { node });
	detach(node);
}

/**
 * @param {Node} before
 * @param {Node} after
 * @returns {void}
 */
function detach_between_dev(before, after) {
	while (before.nextSibling && before.nextSibling !== after) {
		detach_dev(before.nextSibling);
	}
}

/**
 * @param {Node} after
 * @returns {void}
 */
function detach_before_dev(after) {
	while (after.previousSibling) {
		detach_dev(after.previousSibling);
	}
}

/**
 * @param {Node} before
 * @returns {void}
 */
function detach_after_dev(before) {
	while (before.nextSibling) {
		detach_dev(before.nextSibling);
	}
}

/**
 * @param {Node} node
 * @param {string} event
 * @param {EventListenerOrEventListenerObject} handler
 * @param {boolean | AddEventListenerOptions | EventListenerOptions} [options]
 * @param {boolean} [has_prevent_default]
 * @param {boolean} [has_stop_propagation]
 * @param {boolean} [has_stop_immediate_propagation]
 * @returns {() => void}
 */
function listen_dev(
	node,
	event,
	handler,
	options,
	has_prevent_default,
	has_stop_propagation,
	has_stop_immediate_propagation
) {
	const modifiers =
		options === true ? ['capture'] : options ? Array.from(Object.keys(options)) : [];
	if (has_prevent_default) modifiers.push('preventDefault');
	if (has_stop_propagation) modifiers.push('stopPropagation');
	if (has_stop_immediate_propagation) modifiers.push('stopImmediatePropagation');
	dispatch_dev('SvelteDOMAddEventListener', { node, event, handler, modifiers });
	const dispose = listen(node, event, handler, options);
	return () => {
		dispatch_dev('SvelteDOMRemoveEventListener', { node, event, handler, modifiers });
		dispose();
	};
}

/**
 * @param {Element} node
 * @param {string} attribute
 * @param {string} [value]
 * @returns {void}
 */
function attr_dev(node, attribute, value) {
	attr(node, attribute, value);
	if (value == null) dispatch_dev('SvelteDOMRemoveAttribute', { node, attribute });
	else dispatch_dev('SvelteDOMSetAttribute', { node, attribute, value });
}

/**
 * @param {Element} node
 * @param {string} property
 * @param {any} [value]
 * @returns {void}
 */
function prop_dev(node, property, value) {
	node[property] = value;
	dispatch_dev('SvelteDOMSetProperty', { node, property, value });
}

/**
 * @param {HTMLElement} node
 * @param {string} property
 * @param {any} [value]
 * @returns {void}
 */
function dataset_dev(node, property, value) {
	node.dataset[property] = value;
	dispatch_dev('SvelteDOMSetDataset', { node, property, value });
}

/**
 * @param {Text} text
 * @param {unknown} data
 * @returns {void}
 */
function set_data_dev(text, data) {
	data = '' + data;
	if (text.data === data) return;
	dispatch_dev('SvelteDOMSetData', { node: text, data });
	text.data = /** @type {string} */ (data);
}

/**
 * @param {Text} text
 * @param {unknown} data
 * @returns {void}
 */
function set_data_contenteditable_dev(text, data) {
	data = '' + data;
	if (text.wholeText === data) return;
	dispatch_dev('SvelteDOMSetData', { node: text, data });
	text.data = /** @type {string} */ (data);
}

/**
 * @param {Text} text
 * @param {unknown} data
 * @param {string} attr_value
 * @returns {void}
 */
function set_data_maybe_contenteditable_dev(text, data, attr_value) {
	if (~contenteditable_truthy_values.indexOf(attr_value)) {
		set_data_contenteditable_dev(text, data);
	} else {
		set_data_dev(text, data);
	}
}

function ensure_array_like_dev(arg) {
	if (
		typeof arg !== 'string' &&
		!(arg && typeof arg === 'object' && 'length' in arg) &&
		!(typeof Symbol === 'function' && arg && Symbol.iterator in arg)
	) {
		throw new Error('{#each} only works with iterable values.');
	}
	return ensure_array_like(arg);
}

/**
 * @returns {void} */
function validate_slots(name, slot, keys) {
	for (const slot_key of Object.keys(slot)) {
		if (!~keys.indexOf(slot_key)) {
			console.warn(`<${name}> received an unexpected slot "${slot_key}".`);
		}
	}
}

/**
 * @param {unknown} tag
 * @returns {void}
 */
function validate_dynamic_element(tag) {
	const is_string = typeof tag === 'string';
	if (tag && !is_string) {
		throw new Error('<svelte:element> expects "this" attribute to be a string.');
	}
}

/**
 * @param {undefined | string} tag
 * @returns {void}
 */
function validate_void_dynamic_element(tag) {
	if (tag && is_void(tag)) {
		console.warn(`<svelte:element this="${tag}"> is self-closing and cannot have content.`);
	}
}

function construct_svelte_component_dev(component, props) {
	const error_message = 'this={...} of <svelte:component> should specify a Svelte component.';
	try {
		const instance = new component(props);
		if (!instance.$$ || !instance.$set || !instance.$on || !instance.$destroy) {
			throw new Error(error_message);
		}
		return instance;
	} catch (err) {
		const { message } = err;
		if (typeof message === 'string' && message.indexOf('is not a constructor') !== -1) {
			throw new Error(error_message);
		} else {
			throw err;
		}
	}
}

/**
 * Base class for Svelte components with some minor dev-enhancements. Used when dev=true.
 *
 * Can be used to create strongly typed Svelte components.
 *
 * #### Example:
 *
 * You have component library on npm called `component-library`, from which
 * you export a component called `MyComponent`. For Svelte+TypeScript users,
 * you want to provide typings. Therefore you create a `index.d.ts`:
 * ```ts
 * import { SvelteComponent } from "svelte";
 * export class MyComponent extends SvelteComponent<{foo: string}> {}
 * ```
 * Typing this makes it possible for IDEs like VS Code with the Svelte extension
 * to provide intellisense and to use the component like this in a Svelte file
 * with TypeScript:
 * ```svelte
 * <script lang="ts">
 * 	import { MyComponent } from "component-library";
 * </script>
 * <MyComponent foo={'bar'} />
 * ```
 * @template {Record<string, any>} [Props=any]
 * @template {Record<string, any>} [Events=any]
 * @template {Record<string, any>} [Slots=any]
 * @extends {SvelteComponent<Props, Events>}
 */
class SvelteComponentDev extends SvelteComponent {
	/**
	 * For type checking capabilities only.
	 * Does not exist at runtime.
	 * ### DO NOT USE!
	 *
	 * @type {Props}
	 */
	$$prop_def;
	/**
	 * For type checking capabilities only.
	 * Does not exist at runtime.
	 * ### DO NOT USE!
	 *
	 * @type {Events}
	 */
	$$events_def;
	/**
	 * For type checking capabilities only.
	 * Does not exist at runtime.
	 * ### DO NOT USE!
	 *
	 * @type {Slots}
	 */
	$$slot_def;

	/** @param {import('./public.js').ComponentConstructorOptions<Props>} options */
	constructor(options) {
		if (!options || (!options.target && !options.$$inline)) {
			throw new Error("'target' is a required option");
		}
		super();
	}

	/** @returns {void} */
	$destroy() {
		super.$destroy();
		this.$destroy = () => {
			console.warn('Component was already destroyed'); // eslint-disable-line no-console
		};
	}

	/** @returns {void} */
	$capture_state() {}

	/** @returns {void} */
	$inject_state() {}
}
/**
 * @template {Record<string, any>} [Props=any]
 * @template {Record<string, any>} [Events=any]
 * @template {Record<string, any>} [Slots=any]
 * @deprecated Use `SvelteComponent` instead. See PR for more information: https://github.com/sveltejs/svelte/pull/8512
 * @extends {SvelteComponentDev<Props, Events, Slots>}
 */
class SvelteComponentTyped extends (/* unused pure expression or super */ null && (SvelteComponentDev)) {}

/** @returns {() => void} */
function loop_guard(timeout) {
	const start = Date.now();
	return () => {
		if (Date.now() - start > timeout) {
			throw new Error('Infinite loop detected');
		}
	};
}

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/index.js
















;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/shared/version.js
// generated during release, do not modify

/**
 * The current version, as set in package.json.
 *
 * https://svelte.dev/docs/svelte-compiler#svelte-version
 * @type {string}
 */
const version_VERSION = '4.2.18';
const PUBLIC_VERSION = '4';

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/internal/disclose-version/index.js


if (typeof window !== 'undefined')
	// @ts-ignore
	(window.__svelte || (window.__svelte = { v: new Set() })).v.add(PUBLIC_VERSION);

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/index.js


;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/easing/index.js
/*
Adapted from https://github.com/mattdesl
Distributed under MIT License https://github.com/mattdesl/eases/blob/master/LICENSE.md
*/


/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function backInOut(t) {
	const s = 1.70158 * 1.525;
	if ((t *= 2) < 1) return 0.5 * (t * t * ((s + 1) * t - s));
	return 0.5 * ((t -= 2) * t * ((s + 1) * t + s) + 2);
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function backIn(t) {
	const s = 1.70158;
	return t * t * ((s + 1) * t - s);
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function backOut(t) {
	const s = 1.70158;
	return --t * t * ((s + 1) * t + s) + 1;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function bounceOut(t) {
	const a = 4.0 / 11.0;
	const b = 8.0 / 11.0;
	const c = 9.0 / 10.0;
	const ca = 4356.0 / 361.0;
	const cb = 35442.0 / 1805.0;
	const cc = 16061.0 / 1805.0;
	const t2 = t * t;
	return t < a
		? 7.5625 * t2
		: t < b
		? 9.075 * t2 - 9.9 * t + 3.4
		: t < c
		? ca * t2 - cb * t + cc
		: 10.8 * t * t - 20.52 * t + 10.72;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function bounceInOut(t) {
	return t < 0.5 ? 0.5 * (1.0 - bounceOut(1.0 - t * 2.0)) : 0.5 * bounceOut(t * 2.0 - 1.0) + 0.5;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function bounceIn(t) {
	return 1.0 - bounceOut(1.0 - t);
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function circInOut(t) {
	if ((t *= 2) < 1) return -0.5 * (Math.sqrt(1 - t * t) - 1);
	return 0.5 * (Math.sqrt(1 - (t -= 2) * t) + 1);
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function circIn(t) {
	return 1.0 - Math.sqrt(1.0 - t * t);
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function circOut(t) {
	return Math.sqrt(1 - --t * t);
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function easing_cubicInOut(t) {
	return t < 0.5 ? 4.0 * t * t * t : 0.5 * Math.pow(2.0 * t - 2.0, 3.0) + 1.0;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function cubicIn(t) {
	return t * t * t;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function easing_cubicOut(t) {
	const f = t - 1.0;
	return f * f * f + 1.0;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function elasticInOut(t) {
	return t < 0.5
		? 0.5 * Math.sin(((+13.0 * Math.PI) / 2) * 2.0 * t) * Math.pow(2.0, 10.0 * (2.0 * t - 1.0))
		: 0.5 *
				Math.sin(((-13.0 * Math.PI) / 2) * (2.0 * t - 1.0 + 1.0)) *
				Math.pow(2.0, -10.0 * (2.0 * t - 1.0)) +
				1.0;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function elasticIn(t) {
	return Math.sin((13.0 * t * Math.PI) / 2) * Math.pow(2.0, 10.0 * (t - 1.0));
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function elasticOut(t) {
	return Math.sin((-13.0 * (t + 1.0) * Math.PI) / 2) * Math.pow(2.0, -10.0 * t) + 1.0;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function expoInOut(t) {
	return t === 0.0 || t === 1.0
		? t
		: t < 0.5
		? +0.5 * Math.pow(2.0, 20.0 * t - 10.0)
		: -0.5 * Math.pow(2.0, 10.0 - t * 20.0) + 1.0;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function expoIn(t) {
	return t === 0.0 ? t : Math.pow(2.0, 10.0 * (t - 1.0));
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function expoOut(t) {
	return t === 1.0 ? t : 1.0 - Math.pow(2.0, -10.0 * t);
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function quadInOut(t) {
	t /= 0.5;
	if (t < 1) return 0.5 * t * t;
	t--;
	return -0.5 * (t * (t - 2) - 1);
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function quadIn(t) {
	return t * t;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function quadOut(t) {
	return -t * (t - 2.0);
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function quartInOut(t) {
	return t < 0.5 ? +8.0 * Math.pow(t, 4.0) : -8.0 * Math.pow(t - 1.0, 4.0) + 1.0;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function quartIn(t) {
	return Math.pow(t, 4.0);
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function quartOut(t) {
	return Math.pow(t - 1.0, 3.0) * (1.0 - t) + 1.0;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function quintInOut(t) {
	if ((t *= 2) < 1) return 0.5 * t * t * t * t * t;
	return 0.5 * ((t -= 2) * t * t * t * t + 2);
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function quintIn(t) {
	return t * t * t * t * t;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function quintOut(t) {
	return --t * t * t * t * t + 1;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function sineInOut(t) {
	return -0.5 * (Math.cos(Math.PI * t) - 1);
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function sineIn(t) {
	const v = Math.cos(t * Math.PI * 0.5);
	if (Math.abs(v) < 1e-14) return 1;
	else return 1 - v;
}

/**
 * https://svelte.dev/docs/svelte-easing
 * @param {number} t
 * @returns {number}
 */
function sineOut(t) {
	return Math.sin((t * Math.PI) / 2);
}

;// CONCATENATED MODULE: ../../packages/framework/assets/src/lib/transitions.js

function shift(node, {
  delay = 0,
  duration = 200,
  easing = easing_cubicOut,
  side = 'bottom'
}) {
  if (duration <= 0) {
    return false;
  }
  const style = getComputedStyle(node);
  const height = node.offsetHeight;
  if (height <= 0) {
    return false;
  }
  let margins;
  switch (side) {
    case 'top':
      margins = [style.marginTop, style.marginBottom];
      break;
    case 'bottom':
      margins = [style.marginBottom, style.marginTop];
      break;
    case 'left':
      margins = [style.marginLeft, style.marginRight];
      break;
    case 'right':
      margins = [style.marginRight, style.marginLeft];
      break;
    default:
      return false;
  }
  margins[0] = parseFloat(margins[0]) || 0;
  margins[1] = parseFloat(margins[1]) || 0;
  const total = height + margins[0] + margins[1];
  function css(t, u) {
    const m = margins[0] - total * u;
    return `margin-${side}: ${m}px; opacity: ${t}; z-index: 0;`;
  }
  return {
    delay,
    duration,
    easing,
    css
  };
}
;// CONCATENATED MODULE: ../../packages/framework/assets/src/lib/Select2.svelte
/* packages/framework/assets/src/lib/Select2.svelte generated by Svelte v4.2.18 */






function create_fragment(ctx) {
	let select_1;

	return {
		c() {
			select_1 = dom_element("select");
			dom_attr(select_1, "name", /*name*/ ctx[0]);
			select_1.multiple = /*multiple*/ ctx[1];
			select_1.disabled = /*disabled*/ ctx[2];
		},
		m(target, anchor) {
			dom_insert(target, select_1, anchor);
			/*select_1_binding*/ ctx[8](select_1);
		},
		p(ctx, [dirty]) {
			if (dirty & /*name*/ 1) {
				dom_attr(select_1, "name", /*name*/ ctx[0]);
			}

			if (dirty & /*multiple*/ 2) {
				select_1.multiple = /*multiple*/ ctx[1];
			}

			if (dirty & /*disabled*/ 4) {
				select_1.disabled = /*disabled*/ ctx[2];
			}
		},
		i: utils_noop,
		o: utils_noop,
		d(detaching) {
			if (detaching) {
				dom_detach(select_1);
			}

			/*select_1_binding*/ ctx[8](null);
		}
	};
}

function instance($$self, $$props, $$invalidate) {
	let { handler } = $$props;
	let { name = null } = $$props;
	let { data = [] } = $$props;
	let { multiple = false } = $$props;
	let { disabled = false } = $$props;
	let { placeholder = null } = $$props;
	let { options = {} } = $$props;
	const dispatch = createEventDispatcher();
	let select;
	let mounted = false;

	onMount(() => {
		$$invalidate(4, handler = jQuery(select));
		handler.addClass('wc-enhanced-select');
		handler.data('data', data);
		handler.data('placeholder', placeholder);
		handler.data('width', 'resolve');

		if (options) {
			handler.data(options);
		}

		if (options.init !== false) {
			initSelectWoo(options.init !== true);
		}

		handler.on('change', event => {
			dispatch('change', { handler, event });
		});

		handler.on('select2:close', () => {
			handler.data('select2').$container.removeClass('select2-container--above');
			handler.data('select2').$dropdown.find('> .select2-dropdown').removeClass('select2-dropdown--above');
		});

		if (!multiple) {
			handler.on('select2:open', () => {
				const searchField = handler.data('select2').$dropdown.find('.select2-search__field');

				searchField.one('blur', () => {
					setTimeout(() => searchField.focus(), 10);
				});
			});
		}

		if (options.fixPosition) {
			let reopening = false;

			handler.on('select2:open', () => {
				if (reopening) return;
				handler.selectWoo('close');
				reopening = true;
				handler.selectWoo('open');
				reopening = false;
			});
		}

		setTimeout(() => {
			mounted = true;
		});
	});

	onDestroy(() => {
		handler.selectWoo('destroy');
		handler.remove();
	});

	function updateData(newData) {
		if (!mounted) return;

		if (options.replaceData || !newData || !handler.data('data') || newData.length !== handler.data('data').length || newData.length && newData[0].id !== handler.data('data')[0].id) {
			handler.empty().trigger('change.select2');
			handler.data('data', newData);
			handler.selectWoo();
			return;
		}

		let changed = false;

		handler.find('option').each((i, el) => {
			if (!newData[i]) return false;
			const opt = jQuery(el);
			const selected = newData[i].selected === true;
			const disabled = newData[i].disabled === true;

			if (opt.prop('selected') !== selected) {
				opt.prop('selected', selected);
				changed = true;
			}

			if (opt.prop('disabled') !== disabled) {
				opt.prop('disabled', disabled);
				changed = true;
			}
		});

		if (changed) {
			handler.trigger('change.select2');
			handler.selectWoo();
		}
	}

	function updatePlaceholder(text) {
		if (!mounted) return;
		handler.data('placeholder', text);
		handler.selectWoo();
	}

	function updateOptions(options) {
		if (!mounted) return;

		if (options) {
			handler.data(options);
			handler.selectWoo();
		}
	}

	function select_1_binding($$value) {
		binding_callbacks[$$value ? 'unshift' : 'push'](() => {
			select = $$value;
			$$invalidate(3, select);
		});
	}

	$$self.$$set = $$props => {
		if ('handler' in $$props) $$invalidate(4, handler = $$props.handler);
		if ('name' in $$props) $$invalidate(0, name = $$props.name);
		if ('data' in $$props) $$invalidate(5, data = $$props.data);
		if ('multiple' in $$props) $$invalidate(1, multiple = $$props.multiple);
		if ('disabled' in $$props) $$invalidate(2, disabled = $$props.disabled);
		if ('placeholder' in $$props) $$invalidate(6, placeholder = $$props.placeholder);
		if ('options' in $$props) $$invalidate(7, options = $$props.options);
	};

	$$self.$$.update = () => {
		if ($$self.$$.dirty & /*data*/ 32) {
			$: updateData(data);
		}

		if ($$self.$$.dirty & /*placeholder*/ 64) {
			$: updatePlaceholder(placeholder);
		}

		if ($$self.$$.dirty & /*options*/ 128) {
			$: updateOptions(options);
		}
	};

	return [
		name,
		multiple,
		disabled,
		select,
		handler,
		data,
		placeholder,
		options,
		select_1_binding
	];
}

class Select2 extends SvelteComponent {
	constructor(options) {
		super();

		init(this, options, instance, create_fragment, not_equal, {
			handler: 4,
			name: 0,
			data: 5,
			multiple: 1,
			disabled: 2,
			placeholder: 6,
			options: 7
		});
	}
}

/* harmony default export */ const Select2_svelte = (Select2);
;// CONCATENATED MODULE: ./admin/stock-edit/ui/ComponentsField.svelte
/* assets/src/admin/stock-edit/ui/ComponentsField.svelte generated by Svelte v4.2.18 */







function get_each_context(ctx, list, i) {
	const child_ctx = ctx.slice();
	child_ctx[13] = list[i];
	child_ctx[15] = i;
	return child_ctx;
}

// (105:5) {#if comp.image}
function create_if_block_2(ctx) {
	let div;
	let raw_value = /*comp*/ ctx[13].image + "";

	return {
		c() {
			div = dom_element("div");
			dom_attr(div, "class", "component-image");
		},
		m(target, anchor) {
			dom_insert(target, div, anchor);
			div.innerHTML = raw_value;
		},
		p(ctx, dirty) {
			if (dirty & /*componentList*/ 4 && raw_value !== (raw_value = /*comp*/ ctx[13].image + "")) div.innerHTML = raw_value;;
		},
		d(detaching) {
			if (detaching) {
				dom_detach(div);
			}
		}
	};
}

// (115:5) {#if comp.sku}
function create_if_block_1(ctx) {
	let div;
	let t_value = /*comp*/ ctx[13].sku + "";
	let t;

	return {
		c() {
			div = dom_element("div");
			t = dom_text(t_value);
			dom_attr(div, "class", "component-sku");
		},
		m(target, anchor) {
			dom_insert(target, div, anchor);
			dom_append(div, t);
		},
		p(ctx, dirty) {
			if (dirty & /*componentList*/ 4 && t_value !== (t_value = /*comp*/ ctx[13].sku + "")) set_data(t, t_value);
		},
		d(detaching) {
			if (detaching) {
				dom_detach(div);
			}
		}
	};
}

// (121:5) {#if !comp.enabled}
function create_if_block(ctx) {
	let div;
	let span;
	let span_title_value;

	return {
		c() {
			div = dom_element("div");
			span = dom_element("span");
			dom_attr(span, "class", "component-disabled");
			dom_attr(span, "title", span_title_value = /*i18n*/ ctx[5].disabled);
			dom_attr(div, "class", "component-status");
		},
		m(target, anchor) {
			dom_insert(target, div, anchor);
			dom_append(div, span);
		},
		p: utils_noop,
		d(detaching) {
			if (detaching) {
				dom_detach(div);
			}
		}
	};
}

// (97:3) {#each componentList as comp, i (comp.id)}
function create_each_block(key_1, ctx) {
	let div3;
	let t0;
	let div0;
	let t1_value = /*comp*/ ctx[13].title + "";
	let t1;
	let t2;
	let t3;
	let t4;
	let div1;
	let input;
	let input_name_value;
	let input_value_value;
	let input_title_value;
	let t5;
	let div2;
	let button;
	let button_title_value;
	let t6;
	let div3_transition;
	let current;
	let mounted;
	let dispose;
	let if_block0 = /*comp*/ ctx[13].image && create_if_block_2(ctx);
	let if_block1 = /*comp*/ ctx[13].sku && create_if_block_1(ctx);
	let if_block2 = !/*comp*/ ctx[13].enabled && create_if_block(ctx);

	function click_handler() {
		return /*click_handler*/ ctx[9](/*comp*/ ctx[13]);
	}

	return {
		key: key_1,
		first: null,
		c() {
			div3 = dom_element("div");
			if (if_block0) if_block0.c();
			t0 = space();
			div0 = dom_element("div");
			t1 = dom_text(t1_value);
			t2 = space();
			if (if_block1) if_block1.c();
			t3 = space();
			if (if_block2) if_block2.c();
			t4 = space();
			div1 = dom_element("div");
			input = dom_element("input");
			t5 = space();
			div2 = dom_element("div");
			button = dom_element("button");
			t6 = space();
			dom_attr(div0, "class", "component-title");
			dom_attr(input, "type", "number");
			dom_attr(input, "name", input_name_value = "" + (/*data*/ ctx[4].name + "[" + /*type*/ ctx[1] + "][" + /*comp*/ ctx[13].id + "]"));
			input.value = input_value_value = /*components*/ ctx[0][/*comp*/ ctx[13].id];
			dom_attr(input, "step", "any");
			dom_attr(input, "min", "0");
			dom_attr(input, "placeholder", /*quantityPlaceholder*/ ctx[6]);
			dom_attr(input, "title", input_title_value = /*i18n*/ ctx[5][/*type*/ ctx[1]].quantityTip);
			dom_attr(div1, "class", "component-quantity");
			dom_attr(button, "type", "button");
			dom_attr(button, "class", "remove-component-button");
			dom_attr(button, "title", button_title_value = /*i18n*/ ctx[5].remove);
			dom_attr(div2, "class", "component-actions");
			dom_attr(div3, "class", "component-item");
			toggle_class(div3, "disabled", !/*comp*/ ctx[13].enabled);
			this.first = div3;
		},
		m(target, anchor) {
			dom_insert(target, div3, anchor);
			if (if_block0) if_block0.m(div3, null);
			dom_append(div3, t0);
			dom_append(div3, div0);
			dom_append(div0, t1);
			dom_append(div3, t2);
			if (if_block1) if_block1.m(div3, null);
			dom_append(div3, t3);
			if (if_block2) if_block2.m(div3, null);
			dom_append(div3, t4);
			dom_append(div3, div1);
			dom_append(div1, input);
			dom_append(div3, t5);
			dom_append(div3, div2);
			dom_append(div2, button);
			dom_append(div3, t6);
			current = true;

			if (!mounted) {
				dispose = [
					dom_listen(input, "change", onQuantityChange),
					dom_listen(button, "click", click_handler),
					dom_listen(div3, "introend", afterAddItemTransition)
				];

				mounted = true;
			}
		},
		p(new_ctx, dirty) {
			ctx = new_ctx;

			if (/*comp*/ ctx[13].image) {
				if (if_block0) {
					if_block0.p(ctx, dirty);
				} else {
					if_block0 = create_if_block_2(ctx);
					if_block0.c();
					if_block0.m(div3, t0);
				}
			} else if (if_block0) {
				if_block0.d(1);
				if_block0 = null;
			}

			if ((!current || dirty & /*componentList*/ 4) && t1_value !== (t1_value = /*comp*/ ctx[13].title + "")) set_data(t1, t1_value);

			if (/*comp*/ ctx[13].sku) {
				if (if_block1) {
					if_block1.p(ctx, dirty);
				} else {
					if_block1 = create_if_block_1(ctx);
					if_block1.c();
					if_block1.m(div3, t3);
				}
			} else if (if_block1) {
				if_block1.d(1);
				if_block1 = null;
			}

			if (!/*comp*/ ctx[13].enabled) {
				if (if_block2) {
					if_block2.p(ctx, dirty);
				} else {
					if_block2 = create_if_block(ctx);
					if_block2.c();
					if_block2.m(div3, t4);
				}
			} else if (if_block2) {
				if_block2.d(1);
				if_block2 = null;
			}

			if (!current || dirty & /*type, componentList*/ 6 && input_name_value !== (input_name_value = "" + (/*data*/ ctx[4].name + "[" + /*type*/ ctx[1] + "][" + /*comp*/ ctx[13].id + "]"))) {
				dom_attr(input, "name", input_name_value);
			}

			if (!current || dirty & /*components, componentList*/ 5 && input_value_value !== (input_value_value = /*components*/ ctx[0][/*comp*/ ctx[13].id]) && input.value !== input_value_value) {
				input.value = input_value_value;
			}

			if (!current || dirty & /*type*/ 2 && input_title_value !== (input_title_value = /*i18n*/ ctx[5][/*type*/ ctx[1]].quantityTip)) {
				dom_attr(input, "title", input_title_value);
			}

			if (!current || dirty & /*componentList*/ 4) {
				toggle_class(div3, "disabled", !/*comp*/ ctx[13].enabled);
			}
		},
		i(local) {
			if (current) return;

			if (local) {
				add_render_callback(() => {
					if (!current) return;
					if (!div3_transition) div3_transition = create_bidirectional_transition(div3, shift, { duration: 170 }, true);
					div3_transition.run(1);
				});
			}

			current = true;
		},
		o(local) {
			if (local) {
				if (!div3_transition) div3_transition = create_bidirectional_transition(div3, shift, { duration: 170 }, false);
				div3_transition.run(0);
			}

			current = false;
		},
		d(detaching) {
			if (detaching) {
				dom_detach(div3);
			}

			if (if_block0) if_block0.d();
			if (if_block1) if_block1.d();
			if (if_block2) if_block2.d();
			if (detaching && div3_transition) div3_transition.end();
			mounted = false;
			utils_run_all(dispose);
		}
	};
}

function ComponentsField_svelte_create_fragment(ctx) {
	let p;
	let label;
	let t0_value = /*i18n*/ ctx[5][/*type*/ ctx[1]].label + "";
	let t0;
	let t1;
	let span;
	let span_title_value;
	let t2;
	let select2;
	let p_class_value;
	let t3;
	let div2;
	let div1;
	let div0;
	let each_blocks = [];
	let each_1_lookup = new Map();
	let div1_class_value;
	let current;

	select2 = new Select2_svelte({
			props: {
				multiple: true,
				data: /*stockOptions*/ ctx[3],
				placeholder: /*i18n*/ ctx[5][/*type*/ ctx[1]].addPlaceholder,
				options: { width: 'auto' }
			}
		});

	select2.$on("change", /*onSelect*/ ctx[7]);
	let each_value = each_ensure_array_like(/*componentList*/ ctx[2]);
	const get_key = ctx => /*comp*/ ctx[13].id;

	for (let i = 0; i < each_value.length; i += 1) {
		let child_ctx = get_each_context(ctx, each_value, i);
		let key = get_key(child_ctx);
		each_1_lookup.set(key, each_blocks[i] = create_each_block(key, child_ctx));
	}

	return {
		c() {
			p = dom_element("p");
			label = dom_element("label");
			t0 = dom_text(t0_value);
			t1 = space();
			span = dom_element("span");
			t2 = space();
			create_component(select2.$$.fragment);
			t3 = space();
			div2 = dom_element("div");
			div1 = dom_element("div");
			div0 = dom_element("div");

			for (let i = 0; i < each_blocks.length; i += 1) {
				each_blocks[i].c();
			}

			dom_attr(label, "for", "");
			dom_attr(span, "class", "woocommerce-help-tip");
			dom_attr(span, "title", span_title_value = /*i18n*/ ctx[5][/*type*/ ctx[1]].fieldTip);
			dom_attr(p, "class", p_class_value = "form-field mewz_wcas_" + /*type*/ ctx[1] + "_components_field");
			dom_attr(div0, "class", "component-list-inner");
			dom_attr(div1, "class", div1_class_value = "component-list " + /*type*/ ctx[1] + "-component-list");
			dom_attr(div2, "class", "mewz-wcas-components-section");
			toggle_class(div2, "empty", !/*componentList*/ ctx[2].length);
		},
		m(target, anchor) {
			dom_insert(target, p, anchor);
			dom_append(p, label);
			dom_append(label, t0);
			dom_append(p, t1);
			dom_append(p, span);
			dom_append(p, t2);
			mount_component(select2, p, null);
			dom_insert(target, t3, anchor);
			dom_insert(target, div2, anchor);
			dom_append(div2, div1);
			dom_append(div1, div0);

			for (let i = 0; i < each_blocks.length; i += 1) {
				if (each_blocks[i]) {
					each_blocks[i].m(div0, null);
				}
			}

			current = true;
		},
		p(ctx, [dirty]) {
			if ((!current || dirty & /*type*/ 2) && t0_value !== (t0_value = /*i18n*/ ctx[5][/*type*/ ctx[1]].label + "")) set_data(t0, t0_value);

			if (!current || dirty & /*type*/ 2 && span_title_value !== (span_title_value = /*i18n*/ ctx[5][/*type*/ ctx[1]].fieldTip)) {
				dom_attr(span, "title", span_title_value);
			}

			const select2_changes = {};
			if (dirty & /*stockOptions*/ 8) select2_changes.data = /*stockOptions*/ ctx[3];
			if (dirty & /*type*/ 2) select2_changes.placeholder = /*i18n*/ ctx[5][/*type*/ ctx[1]].addPlaceholder;
			select2.$set(select2_changes);

			if (!current || dirty & /*type*/ 2 && p_class_value !== (p_class_value = "form-field mewz_wcas_" + /*type*/ ctx[1] + "_components_field")) {
				dom_attr(p, "class", p_class_value);
			}

			if (dirty & /*componentList, afterAddItemTransition, i18n, removeComponent, data, type, components, quantityPlaceholder, onQuantityChange*/ 375) {
				each_value = each_ensure_array_like(/*componentList*/ ctx[2]);
				transitions_group_outros();
				each_blocks = update_keyed_each(each_blocks, dirty, get_key, 1, ctx, each_value, each_1_lookup, div0, outro_and_destroy_block, create_each_block, null, get_each_context);
				transitions_check_outros();
			}

			if (!current || dirty & /*type*/ 2 && div1_class_value !== (div1_class_value = "component-list " + /*type*/ ctx[1] + "-component-list")) {
				dom_attr(div1, "class", div1_class_value);
			}

			if (!current || dirty & /*componentList*/ 4) {
				toggle_class(div2, "empty", !/*componentList*/ ctx[2].length);
			}
		},
		i(local) {
			if (current) return;
			transitions_transition_in(select2.$$.fragment, local);

			for (let i = 0; i < each_value.length; i += 1) {
				transitions_transition_in(each_blocks[i]);
			}

			current = true;
		},
		o(local) {
			transitions_transition_out(select2.$$.fragment, local);

			for (let i = 0; i < each_blocks.length; i += 1) {
				transitions_transition_out(each_blocks[i]);
			}

			current = false;
		},
		d(detaching) {
			if (detaching) {
				dom_detach(p);
				dom_detach(t3);
				dom_detach(div2);
			}

			destroy_component(select2);

			for (let i = 0; i < each_blocks.length; i += 1) {
				each_blocks[i].d();
			}
		}
	};
}

let scrolling = false;

function afterAddItemTransition(e) {
	e.target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function onQuantityChange(e) {
	if (+e.target.value <= 0) {
		e.target.value = '';
	}
}

function ComponentsField_svelte_instance($$self, $$props, $$invalidate) {
	let stockOptions;
	let componentList;
	let { type } = $$props;
	let { components } = $$props;
	const data = getContext('data');
	const { i18n } = data;
	const dispatch = createEventDispatcher();
	const quantityPlaceholder = (1).toLocaleString(data.locale, { minimumFractionDigits: 2 });

	function getStockOptions(components) {
		const options = [];

		for (const stock of data.stockList) {
			let text = stock.title;

			if (stock.sku) {
				text += ` [${stock.sku}]`;
			}

			if (!stock.enabled) {
				text = `🛇 ${text}`;
			}

			const opt = { id: stock.id, text };

			if (stock.id in components) {
				opt.disabled = true;
			}

			options.push(opt);
		}

		return options;
	}

	function onSelect(e) {
		const handler = e.detail.handler;
		const selected = handler.val();

		if (selected && selected.length) {
			addComponent(+selected[0]);
			setTimeout(() => handler.focus());
		}
	}

	function addComponent(id) {
		if (id in components) return;
		$$invalidate(0, components[id] = ['', ''], components);
		dispatch('added', { type, id });
	}

	function removeComponent(id) {
		delete components[id];
		$$invalidate(0, components);
	}

	const click_handler = comp => removeComponent(comp.id);

	$$self.$$set = $$props => {
		if ('type' in $$props) $$invalidate(1, type = $$props.type);
		if ('components' in $$props) $$invalidate(0, components = $$props.components);
	};

	$$self.$$.update = () => {
		if ($$self.$$.dirty & /*components*/ 1) {
			// get sorted list of component stock
			$: $$invalidate(3, stockOptions = getStockOptions(components));
		}

		if ($$self.$$.dirty & /*components*/ 1) {
			// get sorted list of component stock
			$: $$invalidate(2, componentList = data.stockList.filter(s => s.id in components));
		}
	};

	return [
		components,
		type,
		componentList,
		stockOptions,
		data,
		i18n,
		quantityPlaceholder,
		onSelect,
		removeComponent,
		click_handler
	];
}

class ComponentsField extends SvelteComponent {
	constructor(options) {
		super();
		init(this, options, ComponentsField_svelte_instance, ComponentsField_svelte_create_fragment, safe_not_equal, { type: 1, components: 0 });
	}
}

/* harmony default export */ const ComponentsField_svelte = (ComponentsField);
;// CONCATENATED MODULE: ./admin/stock-edit/ui/Components.svelte
/* assets/src/admin/stock-edit/ui/Components.svelte generated by Svelte v4.2.18 */







function Components_svelte_create_if_block(ctx) {
	let input;

	return {
		c() {
			input = dom_element("input");
			dom_attr(input, "type", "hidden");
			dom_attr(input, "name", "mewz_wcas_noupdate[components]");
			input.value = "1";
		},
		m(target, anchor) {
			dom_insert(target, input, anchor);
		},
		d(detaching) {
			if (detaching) {
				dom_detach(input);
			}
		}
	};
}

function Components_svelte_create_fragment(ctx) {
	let t0;
	let div0;
	let componentsfield0;
	let updating_components;
	let t1;
	let div1;
	let componentsfield1;
	let updating_components_1;
	let current;
	let if_block = !/*changed*/ ctx[1] && Components_svelte_create_if_block(ctx);

	function componentsfield0_components_binding(value) {
		/*componentsfield0_components_binding*/ ctx[3](value);
	}

	let componentsfield0_props = { type: "parent" };

	if (/*components*/ ctx[0].parent !== void 0) {
		componentsfield0_props.components = /*components*/ ctx[0].parent;
	}

	componentsfield0 = new ComponentsField_svelte({ props: componentsfield0_props });
	binding_callbacks.push(() => bind(componentsfield0, 'components', componentsfield0_components_binding));
	componentsfield0.$on("added", /*onAddedComponent*/ ctx[2]);

	function componentsfield1_components_binding(value) {
		/*componentsfield1_components_binding*/ ctx[4](value);
	}

	let componentsfield1_props = { type: "child" };

	if (/*components*/ ctx[0].child !== void 0) {
		componentsfield1_props.components = /*components*/ ctx[0].child;
	}

	componentsfield1 = new ComponentsField_svelte({ props: componentsfield1_props });
	binding_callbacks.push(() => bind(componentsfield1, 'components', componentsfield1_components_binding));
	componentsfield1.$on("added", /*onAddedComponent*/ ctx[2]);

	return {
		c() {
			if (if_block) if_block.c();
			t0 = space();
			div0 = dom_element("div");
			create_component(componentsfield0.$$.fragment);
			t1 = space();
			div1 = dom_element("div");
			create_component(componentsfield1.$$.fragment);
			dom_attr(div0, "class", "options_group");
			dom_attr(div1, "class", "options_group");
		},
		m(target, anchor) {
			if (if_block) if_block.m(target, anchor);
			dom_insert(target, t0, anchor);
			dom_insert(target, div0, anchor);
			mount_component(componentsfield0, div0, null);
			dom_insert(target, t1, anchor);
			dom_insert(target, div1, anchor);
			mount_component(componentsfield1, div1, null);
			current = true;
		},
		p(ctx, [dirty]) {
			if (!/*changed*/ ctx[1]) {
				if (if_block) {
					
				} else {
					if_block = Components_svelte_create_if_block(ctx);
					if_block.c();
					if_block.m(t0.parentNode, t0);
				}
			} else if (if_block) {
				if_block.d(1);
				if_block = null;
			}

			const componentsfield0_changes = {};

			if (!updating_components && dirty & /*components*/ 1) {
				updating_components = true;
				componentsfield0_changes.components = /*components*/ ctx[0].parent;
				add_flush_callback(() => updating_components = false);
			}

			componentsfield0.$set(componentsfield0_changes);
			const componentsfield1_changes = {};

			if (!updating_components_1 && dirty & /*components*/ 1) {
				updating_components_1 = true;
				componentsfield1_changes.components = /*components*/ ctx[0].child;
				add_flush_callback(() => updating_components_1 = false);
			}

			componentsfield1.$set(componentsfield1_changes);
		},
		i(local) {
			if (current) return;
			transitions_transition_in(componentsfield0.$$.fragment, local);
			transitions_transition_in(componentsfield1.$$.fragment, local);
			current = true;
		},
		o(local) {
			transitions_transition_out(componentsfield0.$$.fragment, local);
			transitions_transition_out(componentsfield1.$$.fragment, local);
			current = false;
		},
		d(detaching) {
			if (detaching) {
				dom_detach(t0);
				dom_detach(div0);
				dom_detach(t1);
				dom_detach(div1);
			}

			if (if_block) if_block.d(detaching);
			destroy_component(componentsfield0);
			destroy_component(componentsfield1);
		}
	};
}

function Components_svelte_instance($$self, $$props, $$invalidate) {
	const data = $$props.data;
	const components = data.components;
	setContext('data', data);
	let changed = false;
	data.stockItems = {};

	for (const stock of data.stockList) {
		data.stockItems[stock.id] = stock;
	}

	onMount(() => {
		setTimeout(() => {
			initTooltips('#components_panel .woocommerce-help-tip');
			const container = document.getElementById('components_panel');
			detectFieldChanges(container, data.name + '[', v => $$invalidate(1, changed = v));
		});
	});

	function onAddedComponent(e) {
		const { type, id } = e.detail;
		const otherType = type === 'parent' ? 'child' : 'parent';
		delete components[otherType][id];
	}

	function componentsfield0_components_binding(value) {
		if ($$self.$$.not_equal(components.parent, value)) {
			components.parent = value;
			$$invalidate(0, components);
		}
	}

	function componentsfield1_components_binding(value) {
		if ($$self.$$.not_equal(components.child, value)) {
			components.child = value;
			$$invalidate(0, components);
		}
	}

	$$self.$$set = $$new_props => {
		$$invalidate(6, $$props = utils_assign(utils_assign({}, $$props), exclude_internal_props($$new_props)));
	};

	$$self.$$.update = () => {
		if ($$self.$$.dirty & /*components*/ 1) {
			$: mewzWcas.setTabIndicator('components', Object.values(components.child).length);
		}
	};

	$$props = exclude_internal_props($$props);

	return [
		components,
		changed,
		onAddedComponent,
		componentsfield0_components_binding,
		componentsfield1_components_binding
	];
}

class Components extends SvelteComponent {
	constructor(options) {
		super();
		init(this, options, Components_svelte_instance, Components_svelte_create_fragment, safe_not_equal, {});
	}
}

/* harmony default export */ const Components_svelte = (Components);
;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte@4.2.18/node_modules/svelte/src/runtime/transition/index.js



/**
 * Animates a `blur` filter alongside an element's opacity.
 *
 * https://svelte.dev/docs/svelte-transition#blur
 * @param {Element} node
 * @param {import('./public').BlurParams} [params]
 * @returns {import('./public').TransitionConfig}
 */
function transition_blur(
	node,
	{ delay = 0, duration = 400, easing = cubicInOut, amount = 5, opacity = 0 } = {}
) {
	const style = getComputedStyle(node);
	const target_opacity = +style.opacity;
	const f = style.filter === 'none' ? '' : style.filter;
	const od = target_opacity * (1 - opacity);
	const [value, unit] = split_css_unit(amount);
	return {
		delay,
		duration,
		easing,
		css: (_t, u) => `opacity: ${target_opacity - od * u}; filter: ${f} blur(${u * value}${unit});`
	};
}

/**
 * Animates the opacity of an element from 0 to the current opacity for `in` transitions and from the current opacity to 0 for `out` transitions.
 *
 * https://svelte.dev/docs/svelte-transition#fade
 * @param {Element} node
 * @param {import('./public').FadeParams} [params]
 * @returns {import('./public').TransitionConfig}
 */
function fade(node, { delay = 0, duration = 400, easing = linear } = {}) {
	const o = +getComputedStyle(node).opacity;
	return {
		delay,
		duration,
		easing,
		css: (t) => `opacity: ${t * o}`
	};
}

/**
 * Animates the x and y positions and the opacity of an element. `in` transitions animate from the provided values, passed as parameters to the element's default values. `out` transitions animate from the element's default values to the provided values.
 *
 * https://svelte.dev/docs/svelte-transition#fly
 * @param {Element} node
 * @param {import('./public').FlyParams} [params]
 * @returns {import('./public').TransitionConfig}
 */
function fly(
	node,
	{ delay = 0, duration = 400, easing = cubicOut, x = 0, y = 0, opacity = 0 } = {}
) {
	const style = getComputedStyle(node);
	const target_opacity = +style.opacity;
	const transform = style.transform === 'none' ? '' : style.transform;
	const od = target_opacity * (1 - opacity);
	const [xValue, xUnit] = split_css_unit(x);
	const [yValue, yUnit] = split_css_unit(y);
	return {
		delay,
		duration,
		easing,
		css: (t, u) => `
			transform: ${transform} translate(${(1 - t) * xValue}${xUnit}, ${(1 - t) * yValue}${yUnit});
			opacity: ${target_opacity - od * u}`
	};
}

/**
 * Slides an element in and out.
 *
 * https://svelte.dev/docs/svelte-transition#slide
 * @param {Element} node
 * @param {import('./public').SlideParams} [params]
 * @returns {import('./public').TransitionConfig}
 */
function slide(node, { delay = 0, duration = 400, easing = easing_cubicOut, axis = 'y' } = {}) {
	const style = getComputedStyle(node);
	const opacity = +style.opacity;
	const primary_property = axis === 'y' ? 'height' : 'width';
	const primary_property_value = parseFloat(style[primary_property]);
	const secondary_properties = axis === 'y' ? ['top', 'bottom'] : ['left', 'right'];
	const capitalized_secondary_properties = secondary_properties.map(
		(e) => `${e[0].toUpperCase()}${e.slice(1)}`
	);
	const padding_start_value = parseFloat(style[`padding${capitalized_secondary_properties[0]}`]);
	const padding_end_value = parseFloat(style[`padding${capitalized_secondary_properties[1]}`]);
	const margin_start_value = parseFloat(style[`margin${capitalized_secondary_properties[0]}`]);
	const margin_end_value = parseFloat(style[`margin${capitalized_secondary_properties[1]}`]);
	const border_width_start_value = parseFloat(
		style[`border${capitalized_secondary_properties[0]}Width`]
	);
	const border_width_end_value = parseFloat(
		style[`border${capitalized_secondary_properties[1]}Width`]
	);
	return {
		delay,
		duration,
		easing,
		css: (t) =>
			'overflow: hidden;' +
			`opacity: ${Math.min(t * 20, 1) * opacity};` +
			`${primary_property}: ${t * primary_property_value}px;` +
			`padding-${secondary_properties[0]}: ${t * padding_start_value}px;` +
			`padding-${secondary_properties[1]}: ${t * padding_end_value}px;` +
			`margin-${secondary_properties[0]}: ${t * margin_start_value}px;` +
			`margin-${secondary_properties[1]}: ${t * margin_end_value}px;` +
			`border-${secondary_properties[0]}-width: ${t * border_width_start_value}px;` +
			`border-${secondary_properties[1]}-width: ${t * border_width_end_value}px;`
	};
}

/**
 * Animates the opacity and scale of an element. `in` transitions animate from an element's current (default) values to the provided values, passed as parameters. `out` transitions animate from the provided values to an element's default values.
 *
 * https://svelte.dev/docs/svelte-transition#scale
 * @param {Element} node
 * @param {import('./public').ScaleParams} [params]
 * @returns {import('./public').TransitionConfig}
 */
function scale(
	node,
	{ delay = 0, duration = 400, easing = cubicOut, start = 0, opacity = 0 } = {}
) {
	const style = getComputedStyle(node);
	const target_opacity = +style.opacity;
	const transform = style.transform === 'none' ? '' : style.transform;
	const sd = 1 - start;
	const od = target_opacity * (1 - opacity);
	return {
		delay,
		duration,
		easing,
		css: (_t, u) => `
			transform: ${transform} scale(${1 - sd * u});
			opacity: ${target_opacity - od * u}
		`
	};
}

/**
 * Animates the stroke of an SVG element, like a snake in a tube. `in` transitions begin with the path invisible and draw the path to the screen over time. `out` transitions start in a visible state and gradually erase the path. `draw` only works with elements that have a `getTotalLength` method, like `<path>` and `<polyline>`.
 *
 * https://svelte.dev/docs/svelte-transition#draw
 * @param {SVGElement & { getTotalLength(): number }} node
 * @param {import('./public').DrawParams} [params]
 * @returns {import('./public').TransitionConfig}
 */
function draw(node, { delay = 0, speed, duration, easing = cubicInOut } = {}) {
	let len = node.getTotalLength();
	const style = getComputedStyle(node);
	if (style.strokeLinecap !== 'butt') {
		len += parseInt(style.strokeWidth);
	}
	if (duration === undefined) {
		if (speed === undefined) {
			duration = 800;
		} else {
			duration = len / speed;
		}
	} else if (typeof duration === 'function') {
		duration = duration(len);
	}
	return {
		delay,
		duration,
		easing,
		css: (_, u) => `
			stroke-dasharray: ${len};
			stroke-dashoffset: ${u * len};
		`
	};
}

/**
 * The `crossfade` function creates a pair of [transitions](https://svelte.dev/docs#template-syntax-element-directives-transition-fn) called `send` and `receive`. When an element is 'sent', it looks for a corresponding element being 'received', and generates a transition that transforms the element to its counterpart's position and fades it out. When an element is 'received', the reverse happens. If there is no counterpart, the `fallback` transition is used.
 *
 * https://svelte.dev/docs/svelte-transition#crossfade
 * @param {import('./public').CrossfadeParams & {
 * 	fallback?: (node: Element, params: import('./public').CrossfadeParams, intro: boolean) => import('./public').TransitionConfig;
 * }} params
 * @returns {[(node: any, params: import('./public').CrossfadeParams & { key: any; }) => () => import('./public').TransitionConfig, (node: any, params: import('./public').CrossfadeParams & { key: any; }) => () => import('./public').TransitionConfig]}
 */
function crossfade({ fallback, ...defaults }) {
	/** @type {Map<any, Element>} */
	const to_receive = new Map();
	/** @type {Map<any, Element>} */
	const to_send = new Map();
	/**
	 * @param {Element} from_node
	 * @param {Element} node
	 * @param {import('./public').CrossfadeParams} params
	 * @returns {import('./public').TransitionConfig}
	 */
	function crossfade(from_node, node, params) {
		const {
			delay = 0,
			duration = (d) => Math.sqrt(d) * 30,
			easing = cubicOut
		} = assign(assign({}, defaults), params);
		const from = from_node.getBoundingClientRect();
		const to = node.getBoundingClientRect();
		const dx = from.left - to.left;
		const dy = from.top - to.top;
		const dw = from.width / to.width;
		const dh = from.height / to.height;
		const d = Math.sqrt(dx * dx + dy * dy);
		const style = getComputedStyle(node);
		const transform = style.transform === 'none' ? '' : style.transform;
		const opacity = +style.opacity;
		return {
			delay,
			duration: is_function(duration) ? duration(d) : duration,
			easing,
			css: (t, u) => `
				opacity: ${t * opacity};
				transform-origin: top left;
				transform: ${transform} translate(${u * dx}px,${u * dy}px) scale(${t + (1 - t) * dw}, ${
				t + (1 - t) * dh
			});
			`
		};
	}

	/**
	 * @param {Map<any, Element>} items
	 * @param {Map<any, Element>} counterparts
	 * @param {boolean} intro
	 * @returns {(node: any, params: import('./public').CrossfadeParams & { key: any; }) => () => import('./public').TransitionConfig}
	 */
	function transition(items, counterparts, intro) {
		return (node, params) => {
			items.set(params.key, node);
			return () => {
				if (counterparts.has(params.key)) {
					const other_node = counterparts.get(params.key);
					counterparts.delete(params.key);
					return crossfade(other_node, node, params);
				}
				// if the node is disappearing altogether
				// (i.e. wasn't claimed by the other list)
				// then we need to supply an outro
				items.delete(params.key);
				return fallback && fallback(node, params, intro);
			};
		};
	}
	return [transition(to_send, to_receive, false), transition(to_receive, to_send, true)];
}

;// CONCATENATED MODULE: ../../node_modules/.pnpm/svelte-collapse@0.1.2/node_modules/svelte-collapse/src/collapse.js

function collapse (node, params) {

    const defaultParams = {
        open: true,
        duration: 0.2,
        easing: 'ease'
    }

    params = Object.assign(defaultParams, params)

    const noop = () => {}
    let transitionEndResolve = noop
    let transitionEndReject = noop

    const listener = node.addEventListener('transitionend', () => {
        transitionEndResolve()
        transitionEndResolve = noop
        transitionEndReject = noop
    })

    // convenience functions
    async function asyncTransitionEnd () {
        return new Promise((resolve, reject) => {
            transitionEndResolve = resolve
            transitionEndReject = reject
        })
    }

    async function nextFrame () {
        return new Promise(requestAnimationFrame)
    }

    function transition () {
        return `height ${params.duration}s ${params.easing}`
    }

    // set initial styles
    node.style.transition = transition()
    node.style.height = params.open ? 'auto' : '0px'

    if (params.open) {
        node.style.overflow = 'visible'
    }
    else {
        node.style.overflow = 'hidden'
    }

    async function enter () {

        // height is already in pixels
        // start the transition
        node.style.height = node.scrollHeight + 'px'

        // wait for transition to end,
        // then switch back to height auto
        try {
            await asyncTransitionEnd()
            node.style.height = 'auto'
            node.style.overflow = 'visible'
        } catch(err) {
            // interrupted by a leave transition
        }

    }

    async function leave () {

        if (node.style.height === 'auto') {

            // temporarily turn transitions off
            node.style.transition = 'none'
            await nextFrame()

            // set height to pixels, and turn transition back on
            node.style.height = node.scrollHeight + 'px'
            node.style.transition = transition()
            await nextFrame()

            // start the transition
            node.style.overflow = 'hidden'
            node.style.height = '0px'

        }
        else {

            // we are interrupting an enter transition
            transitionEndReject()
            node.style.overflow = 'hidden'
            node.style.height = '0px'

        }

    }

    function update (newParams) {
        params = Object.assign(params, newParams)
        params.open ? enter() : leave()
    }

    function destroy () {
        node.removeEventListener('transitionend', listener)
    }

    return { update, destroy }

}
;// CONCATENATED MODULE: ../../packages/framework/assets/src/lib/drag-action.js
/**
 * @param {HTMLElement} node
 * @param {Function} dragStart
 * @param {Function} dragMove
 * @param {Function} dragEnd
 * @param {string} exclude Selector to exclude
 * @param {boolean} enabled
 */
function drag(node, {
  dragStart,
  dragMove,
  dragEnd,
  exclude,
  enabled = true
}) {
  node.addEventListener('mousedown', onStart);
  node.addEventListener('touchstart', onStart, {
    passive: false
  });
  function onStart(event) {
    if (!enabled || exclude && event.target.closest(exclude)) {
      return;
    }
    const pointer = getPointer(event);
    if (!pointer) return;
    event.preventDefault();
    startDrag(event, pointer, {
      dragStart,
      dragMove,
      dragEnd
    });
  }
  return {
    update(newParams) {
      exclude = newParams.exclude;
      enabled = newParams.enabled;
    },
    destroy() {
      node.removeEventListener('mousedown', onStart);
      node.removeEventListener('touchstart', onStart);
    }
  };
}
function startDrag(event, pointer, {
  dragStart,
  dragMove,
  dragEnd
}) {
  let lastEvent = event;
  let touchId;
  let x = pointer.clientX;
  let y = pointer.clientY;
  let startX = x + window.scrollX;
  let startY = y + window.scrollY;
  let moveX = 0;
  let moveY = 0;
  if (event.type === 'mousedown') {
    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onEnd);
  } else {
    touchId = pointer.identifier;
    window.addEventListener('touchmove', onMove);
    window.addEventListener('touchend', onEnd);
  }
  window.addEventListener('scroll', onScroll);
  if (dragStart) {
    dragStart({
      event,
      pointer,
      x,
      y: top,
      startX,
      startY
    });
  }

  /**
   * @param {MouseEvent|TouchEvent} event
   */
  function onMove(event) {
    const pointer = getPointer(event);
    if (!pointer) return;
    lastEvent = event;
    x = pointer.clientX;
    y = pointer.clientY;
    moveX = x + window.scrollX - startX;
    moveY = y + window.scrollY - startY;
    if (dragMove) {
      dragMove({
        event,
        pointer,
        x,
        y: top,
        startX,
        startY,
        moveX,
        moveY
      });
    }
  }

  /**
   * @param {MouseEvent|TouchEvent} event
   */
  function onEnd(event) {
    const pointer = getPointer(event, touchId);
    if (!pointer) return;
    lastEvent = event;
    if (event.type === 'mouseup') {
      window.removeEventListener('mousemove', onMove);
      window.removeEventListener('mouseup', onEnd);
    } else {
      window.removeEventListener('touchmove', onMove);
      window.removeEventListener('touchend', onEnd);
    }
    window.removeEventListener('scroll', onScroll);
    if (dragEnd) {
      dragEnd({
        event,
        pointer,
        x,
        y: top,
        startX,
        startY,
        moveX,
        moveY
      });
    }
  }
  function onScroll() {
    moveX = x + window.scrollX - startX;
    moveY = y + window.scrollY - startY;
    dragMove({
      event: lastEvent,
      pointer,
      x,
      y: top,
      startX,
      startY,
      moveX,
      moveY
    });
  }
}

/**
 * @param {MouseEvent|TouchEvent} event
 * @param {int} touchId
 *
 * @return {MouseEvent|Touch}
 */
function getPointer(event, touchId) {
  if (event instanceof MouseEvent) {
    // primary mouse button only
    return event.button === 0 ? event : null;
  } else if (typeof touchId === 'undefined') {
    // first touch only
    return event.touches.length === 1 ? event.touches[0] : null;
  } else {
    return getFromTouchList(event.changedTouches, touchId);
  }
}

/**
 * @param {TouchList} touchList
 * @param {int} identifier
 *
 * @return {Touch}
 */
function getFromTouchList(touchList, identifier) {
  for (const touch of touchList) {
    if (touch.identifier === identifier) {
      return touch;
    }
  }
  return null;
}
;// CONCATENATED MODULE: ./admin/stock-edit/ui/MatchRule.svelte
/* assets/src/admin/stock-edit/ui/MatchRule.svelte generated by Svelte v4.2.18 */











function MatchRule_svelte_get_each_context(ctx, list, i) {
	const child_ctx = ctx.slice();
	child_ctx[43] = list[i];
	child_ctx[45] = list;
	child_ctx[46] = i;
	const constants_0 = `${/*data*/ child_ctx[8].name}[${/*index*/ child_ctx[3]}][attributes][${/*row*/ child_ctx[43][0]}]`;
	child_ctx[44] = constants_0;
	return child_ctx;
}

function get_each_context_1(ctx, list, i) {
	const child_ctx = ctx.slice();
	child_ctx[47] = list[i];
	child_ctx[49] = i;
	return child_ctx;
}

// (499:3) {#if ruleCount > 1}
function MatchRule_svelte_create_if_block_2(ctx) {
	let span0;
	let t0_value = /*i18n*/ ctx[9].ruleTitle.replace('%s', /*index*/ ctx[3] + 1) + "";
	let t0;
	let t1;
	let span1;

	return {
		c() {
			span0 = dom_element("span");
			t0 = dom_text(t0_value);
			t1 = space();
			span1 = dom_element("span");
			span1.textContent = "—";
			dom_attr(span0, "class", "toolbar-label toolbar-label-title");
			dom_attr(span1, "class", "sep");
		},
		m(target, anchor) {
			dom_insert(target, span0, anchor);
			dom_append(span0, t0);
			dom_insert(target, t1, anchor);
			dom_insert(target, span1, anchor);
		},
		p(ctx, dirty) {
			if (dirty[0] & /*index*/ 8 && t0_value !== (t0_value = /*i18n*/ ctx[9].ruleTitle.replace('%s', /*index*/ ctx[3] + 1) + "")) set_data(t0, t0_value);
		},
		d(detaching) {
			if (detaching) {
				dom_detach(span0);
				dom_detach(t1);
				dom_detach(span1);
			}
		}
	};
}

// (506:9) {#if label.name}
function MatchRule_svelte_create_if_block_1(ctx) {
	let span;
	let t_value = /*label*/ ctx[47].name + "";
	let t;

	return {
		c() {
			span = dom_element("span");
			t = dom_text(t_value);
			dom_attr(span, "class", "name");
		},
		m(target, anchor) {
			dom_insert(target, span, anchor);
			dom_append(span, t);
		},
		p(ctx, dirty) {
			if (dirty[0] & /*attributes, multiplier*/ 3 && t_value !== (t_value = /*label*/ ctx[47].name + "")) set_data(t, t_value);
		},
		d(detaching) {
			if (detaching) {
				dom_detach(span);
			}
		}
	};
}

// (504:3) {#each buildRuleLabels(attributes, multiplier) as label, i (i + label.type)}
function create_each_block_1(key_1, ctx) {
	let span1;
	let t0;
	let span0;
	let t1_value = (/*label*/ ctx[47].value || '') + "";
	let t1;
	let t2;
	let span1_class_value;
	let span1_title_value;
	let span1_rel_value;
	let if_block = /*label*/ ctx[47].name && MatchRule_svelte_create_if_block_1(ctx);

	return {
		key: key_1,
		first: null,
		c() {
			span1 = dom_element("span");
			if (if_block) if_block.c();
			t0 = space();
			span0 = dom_element("span");
			t1 = dom_text(t1_value);
			t2 = space();
			dom_attr(span0, "class", "value");

			dom_attr(span1, "class", span1_class_value = "toolbar-label toolbar-label-" + /*label*/ ctx[47].type + (/*label*/ ctx[47].class
			? ' ' + /*label*/ ctx[47].class
			: ''));

			dom_attr(span1, "title", span1_title_value = /*label*/ ctx[47].title);
			dom_attr(span1, "rel", span1_rel_value = /*label*/ ctx[47].title ? 'tiptip' : null);
			this.first = span1;
		},
		m(target, anchor) {
			dom_insert(target, span1, anchor);
			if (if_block) if_block.m(span1, null);
			dom_append(span1, t0);
			dom_append(span1, span0);
			dom_append(span0, t1);
			dom_append(span1, t2);
		},
		p(new_ctx, dirty) {
			ctx = new_ctx;

			if (/*label*/ ctx[47].name) {
				if (if_block) {
					if_block.p(ctx, dirty);
				} else {
					if_block = MatchRule_svelte_create_if_block_1(ctx);
					if_block.c();
					if_block.m(span1, t0);
				}
			} else if (if_block) {
				if_block.d(1);
				if_block = null;
			}

			if (dirty[0] & /*attributes, multiplier*/ 3 && t1_value !== (t1_value = (/*label*/ ctx[47].value || '') + "")) set_data(t1, t1_value);

			if (dirty[0] & /*attributes, multiplier*/ 3 && span1_class_value !== (span1_class_value = "toolbar-label toolbar-label-" + /*label*/ ctx[47].type + (/*label*/ ctx[47].class
			? ' ' + /*label*/ ctx[47].class
			: ''))) {
				dom_attr(span1, "class", span1_class_value);
			}

			if (dirty[0] & /*attributes, multiplier*/ 3 && span1_title_value !== (span1_title_value = /*label*/ ctx[47].title)) {
				dom_attr(span1, "title", span1_title_value);
			}

			if (dirty[0] & /*attributes, multiplier*/ 3 && span1_rel_value !== (span1_rel_value = /*label*/ ctx[47].title ? 'tiptip' : null)) {
				dom_attr(span1, "rel", span1_rel_value);
			}
		},
		d(detaching) {
			if (detaching) {
				dom_detach(span1);
			}

			if (if_block) if_block.d();
		}
	};
}

// (529:5) {#each attributes as row, rowIndex (row)}
function MatchRule_svelte_create_each_block(key_1, ctx) {
	let div4;
	let div3;
	let div0;
	let select20;
	let t0;
	let div1;
	let input;
	let input_name_value;
	let t1;
	let select21;
	let t2;
	let div2;
	let button;
	let button_title_value;
	let button_disabled_value;
	let t3;
	let div4_transition;
	let current;
	let mounted;
	let dispose;

	function change_handler(...args) {
		return /*change_handler*/ ctx[26](/*row*/ ctx[43], /*each_value*/ ctx[45], /*rowIndex*/ ctx[46], ...args);
	}

	select20 = new Select2_svelte({
			props: {
				data: /*getAttributeOptions*/ ctx[12](/*row*/ ctx[43][0]),
				placeholder: {
					id: '',
					text: /*i18n*/ ctx[9].attributePlaceholder
				},
				options: { init: true, fixPosition: true }
			}
		});

	select20.$on("change", change_handler);

	function change_handler_1(...args) {
		return /*change_handler_1*/ ctx[27](/*row*/ ctx[43], /*each_value*/ ctx[45], /*rowIndex*/ ctx[46], ...args);
	}

	select21 = new Select2_svelte({
			props: {
				name: "" + (/*name*/ ctx[44] + "[]"),
				multiple: true,
				data: /*getTermOptions*/ ctx[13](/*row*/ ctx[43]),
				placeholder: /*row*/ ctx[43][0]
				? /*i18n*/ ctx[9].anyOption.replace('%s', /*data*/ ctx[8].attributes[/*row*/ ctx[43][0]].label)
				: /*i18n*/ ctx[9].termPlaceholder,
				disabled: !/*row*/ ctx[43][0],
				options: {
					init: true,
					width: 'auto',
					fixPosition: true
				}
			}
		});

	select21.$on("change", change_handler_1);

	function click_handler_2() {
		return /*click_handler_2*/ ctx[28](/*rowIndex*/ ctx[46]);
	}

	return {
		key: key_1,
		first: null,
		c() {
			div4 = dom_element("div");
			div3 = dom_element("div");
			div0 = dom_element("div");
			create_component(select20.$$.fragment);
			t0 = space();
			div1 = dom_element("div");
			input = dom_element("input");
			t1 = space();
			create_component(select21.$$.fragment);
			t2 = space();
			div2 = dom_element("div");
			button = dom_element("button");
			t3 = space();
			dom_attr(div0, "class", "select-attribute");
			dom_attr(input, "type", "hidden");
			dom_attr(input, "name", input_name_value = /*name*/ ctx[44]);
			input.value = "";
			dom_attr(div1, "class", "select-terms");
			dom_attr(button, "type", "button");
			dom_attr(button, "class", "icon-button row-remove-button");
			dom_attr(button, "title", button_title_value = /*i18n*/ ctx[9].removeAttribute);
			button.disabled = button_disabled_value = /*attributes*/ ctx[0].length === 1 && !/*row*/ ctx[43].attribute;
			dom_attr(div2, "class", "attribute-row-actions");
			dom_attr(div3, "class", "attribute-row-inner");
			dom_attr(div4, "class", "attribute-row");
			this.first = div4;
		},
		m(target, anchor) {
			dom_insert(target, div4, anchor);
			dom_append(div4, div3);
			dom_append(div3, div0);
			mount_component(select20, div0, null);
			dom_append(div3, t0);
			dom_append(div3, div1);
			dom_append(div1, input);
			dom_append(div1, t1);
			mount_component(select21, div1, null);
			dom_append(div3, t2);
			dom_append(div3, div2);
			dom_append(div2, button);
			dom_append(div4, t3);
			current = true;

			if (!mounted) {
				dispose = dom_listen(button, "click", click_handler_2);
				mounted = true;
			}
		},
		p(new_ctx, dirty) {
			ctx = new_ctx;
			const select20_changes = {};
			if (dirty[0] & /*attributes*/ 1) select20_changes.data = /*getAttributeOptions*/ ctx[12](/*row*/ ctx[43][0]);
			select20.$set(select20_changes);

			if (!current || dirty[0] & /*index, attributes*/ 9 && input_name_value !== (input_name_value = /*name*/ ctx[44])) {
				dom_attr(input, "name", input_name_value);
			}

			const select21_changes = {};
			if (dirty[0] & /*index, attributes*/ 9) select21_changes.name = "" + (/*name*/ ctx[44] + "[]");
			if (dirty[0] & /*attributes*/ 1) select21_changes.data = /*getTermOptions*/ ctx[13](/*row*/ ctx[43]);

			if (dirty[0] & /*attributes*/ 1) select21_changes.placeholder = /*row*/ ctx[43][0]
			? /*i18n*/ ctx[9].anyOption.replace('%s', /*data*/ ctx[8].attributes[/*row*/ ctx[43][0]].label)
			: /*i18n*/ ctx[9].termPlaceholder;

			if (dirty[0] & /*attributes*/ 1) select21_changes.disabled = !/*row*/ ctx[43][0];
			select21.$set(select21_changes);

			if (!current || dirty[0] & /*attributes*/ 1 && button_disabled_value !== (button_disabled_value = /*attributes*/ ctx[0].length === 1 && !/*row*/ ctx[43].attribute)) {
				button.disabled = button_disabled_value;
			}
		},
		i(local) {
			if (current) return;
			transitions_transition_in(select20.$$.fragment, local);
			transitions_transition_in(select21.$$.fragment, local);

			if (local) {
				add_render_callback(() => {
					if (!current) return;
					if (!div4_transition) div4_transition = create_bidirectional_transition(div4, shift, { duration: 130 }, true);
					div4_transition.run(1);
				});
			}

			current = true;
		},
		o(local) {
			transitions_transition_out(select20.$$.fragment, local);
			transitions_transition_out(select21.$$.fragment, local);

			if (local) {
				if (!div4_transition) div4_transition = create_bidirectional_transition(div4, shift, { duration: 130 }, false);
				div4_transition.run(0);
			}

			current = false;
		},
		d(detaching) {
			if (detaching) {
				dom_detach(div4);
			}

			destroy_component(select20);
			destroy_component(select21);
			if (detaching && div4_transition) div4_transition.end();
			mounted = false;
			dispose();
		}
	};
}

// (573:4) {#if attributes.length < totalAttributes}
function MatchRule_svelte_create_if_block(ctx) {
	let div;
	let button;
	let div_transition;
	let current;
	let mounted;
	let dispose;

	return {
		c() {
			div = dom_element("div");
			button = dom_element("button");
			button.textContent = "Add attribute";
			dom_attr(button, "type", "button");
			dom_attr(button, "class", "row-add-button");
			dom_attr(div, "class", "attribute-list-actions");
		},
		m(target, anchor) {
			dom_insert(target, div, anchor);
			dom_append(div, button);
			current = true;

			if (!mounted) {
				dispose = dom_listen(button, "click", /*click_handler_3*/ ctx[29]);
				mounted = true;
			}
		},
		p: utils_noop,
		i(local) {
			if (current) return;

			if (local) {
				add_render_callback(() => {
					if (!current) return;
					if (!div_transition) div_transition = create_bidirectional_transition(div, slide, { duration: 100 }, true);
					div_transition.run(1);
				});
			}

			current = true;
		},
		o(local) {
			if (local) {
				if (!div_transition) div_transition = create_bidirectional_transition(div, slide, { duration: 100 }, false);
				div_transition.run(0);
			}

			current = false;
		},
		d(detaching) {
			if (detaching) {
				dom_detach(div);
			}

			if (detaching && div_transition) div_transition.end();
			mounted = false;
			dispose();
		}
	};
}

function MatchRule_svelte_create_fragment(ctx) {
	let div8;
	let div1;
	let div0;
	let t0;
	let each_blocks_1 = [];
	let each0_lookup = new Map();
	let t1;
	let span1;
	let span0;
	let t2;
	let button0;
	let button0_title_value;
	let drag_action;
	let t3;
	let button1;
	let button1_title_value;
	let t4;
	let button2;
	let button2_title_value;
	let t5;
	let div7;
	let div6;
	let div3;
	let div2;
	let each_blocks = [];
	let each1_lookup = new Map();
	let t6;
	let t7;
	let div5;
	let div4;
	let label_1;
	let t8_value = /*i18n*/ ctx[9].multiplierLabel + "";
	let t8;
	let t9;
	let span2;
	let span2_title_value;
	let t10;
	let input;
	let input_name_value;
	let input_placeholder_value;
	let input_lang_value;
	let collapse_action;
	let div8_style_value;
	let div8_intro;
	let div8_outro;
	let current;
	let mounted;
	let dispose;
	let if_block0 = /*ruleCount*/ ctx[5] > 1 && MatchRule_svelte_create_if_block_2(ctx);
	let each_value_1 = each_ensure_array_like(/*buildRuleLabels*/ ctx[14](/*attributes*/ ctx[0], /*multiplier*/ ctx[1]));
	const get_key = ctx => /*i*/ ctx[49] + /*label*/ ctx[47].type;

	for (let i = 0; i < each_value_1.length; i += 1) {
		let child_ctx = get_each_context_1(ctx, each_value_1, i);
		let key = get_key(child_ctx);
		each0_lookup.set(key, each_blocks_1[i] = create_each_block_1(key, child_ctx));
	}

	let each_value = each_ensure_array_like(/*attributes*/ ctx[0]);
	const get_key_1 = ctx => /*row*/ ctx[43];

	for (let i = 0; i < each_value.length; i += 1) {
		let child_ctx = MatchRule_svelte_get_each_context(ctx, each_value, i);
		let key = get_key_1(child_ctx);
		each1_lookup.set(key, each_blocks[i] = MatchRule_svelte_create_each_block(key, child_ctx));
	}

	let if_block1 = /*attributes*/ ctx[0].length < /*totalAttributes*/ ctx[10] && MatchRule_svelte_create_if_block(ctx);

	return {
		c() {
			div8 = dom_element("div");
			div1 = dom_element("div");
			div0 = dom_element("div");
			if (if_block0) if_block0.c();
			t0 = space();

			for (let i = 0; i < each_blocks_1.length; i += 1) {
				each_blocks_1[i].c();
			}

			t1 = space();
			span1 = dom_element("span");
			span0 = dom_element("span");
			t2 = space();
			button0 = dom_element("button");
			t3 = space();
			button1 = dom_element("button");
			t4 = space();
			button2 = dom_element("button");
			t5 = space();
			div7 = dom_element("div");
			div6 = dom_element("div");
			div3 = dom_element("div");
			div2 = dom_element("div");

			for (let i = 0; i < each_blocks.length; i += 1) {
				each_blocks[i].c();
			}

			t6 = space();
			if (if_block1) if_block1.c();
			t7 = space();
			div5 = dom_element("div");
			div4 = dom_element("div");
			label_1 = dom_element("label");
			t8 = dom_text(t8_value);
			t9 = space();
			span2 = dom_element("span");
			t10 = space();
			input = dom_element("input");
			dom_attr(div0, "class", "toolbar-labels");
			dom_attr(span0, "class", "toolbar-action icon-button expand-button");
			dom_attr(button0, "type", "button");
			dom_attr(button0, "class", "toolbar-action icon-button drag-button");
			dom_attr(button0, "title", button0_title_value = /*i18n*/ ctx[9].dragTip);
			dom_attr(button1, "type", "button");
			dom_attr(button1, "class", "toolbar-action icon-button duplicate-button");
			dom_attr(button1, "title", button1_title_value = /*i18n*/ ctx[9].duplicateRule);
			dom_attr(button2, "type", "button");
			dom_attr(button2, "class", "toolbar-action icon-button remove-button");
			dom_attr(button2, "title", button2_title_value = /*i18n*/ ctx[9].removeRule);
			dom_attr(span1, "class", "match-rule-toolbar-actions");
			dom_attr(div1, "class", "match-rule-toolbar");
			dom_attr(div2, "class", "attribute-rows");
			dom_attr(div3, "class", "attribute-list");
			dom_attr(span2, "class", "woocommerce-help-tip");
			dom_attr(span2, "title", span2_title_value = /*i18n*/ ctx[9].multiplierTip);
			dom_attr(input, "type", "number");
			dom_attr(input, "name", input_name_value = /*optionFieldName*/ ctx[17](/*index*/ ctx[3], 'multiplier'));
			dom_attr(input, "step", "any");
			dom_attr(input, "min", "-1");
			dom_attr(input, "placeholder", input_placeholder_value = /*getMultiplierPlaceholder*/ ctx[22](/*attributes*/ ctx[0]));
			dom_attr(input, "lang", input_lang_value = /*data*/ ctx[8].locale);
			dom_attr(div4, "class", "option option-multiplier");
			dom_attr(div5, "class", "match-rule-options");
			dom_attr(div6, "class", "match-rule-body-inner");
			dom_attr(div7, "class", "match-rule-body");
			dom_attr(div8, "class", "mewz-wcas-match-rule");
			dom_attr(div8, "style", div8_style_value = css(/*dragging*/ ctx[7]));
			toggle_class(div8, "zero-multiplier", /*multiplier*/ ctx[1] != null && /*multiplier*/ ctx[1] !== '' && +/*multiplier*/ ctx[1] === 0);
			toggle_class(div8, "stop-rule", +/*multiplier*/ ctx[1] < 0);
			toggle_class(div8, "open", /*open*/ ctx[2]);
			toggle_class(div8, "dragging", /*dragging*/ ctx[7]);
			toggle_class(div8, "released", /*dragging*/ ctx[7] && /*dragging*/ ctx[7].released);
		},
		m(target, anchor) {
			dom_insert(target, div8, anchor);
			dom_append(div8, div1);
			dom_append(div1, div0);
			if (if_block0) if_block0.m(div0, null);
			dom_append(div0, t0);

			for (let i = 0; i < each_blocks_1.length; i += 1) {
				if (each_blocks_1[i]) {
					each_blocks_1[i].m(div0, null);
				}
			}

			dom_append(div1, t1);
			dom_append(div1, span1);
			dom_append(span1, span0);
			dom_append(span1, t2);
			dom_append(span1, button0);
			dom_append(span1, t3);
			dom_append(span1, button1);
			dom_append(span1, t4);
			dom_append(span1, button2);
			dom_append(div8, t5);
			dom_append(div8, div7);
			dom_append(div7, div6);
			dom_append(div6, div3);
			dom_append(div3, div2);

			for (let i = 0; i < each_blocks.length; i += 1) {
				if (each_blocks[i]) {
					each_blocks[i].m(div2, null);
				}
			}

			dom_append(div3, t6);
			if (if_block1) if_block1.m(div3, null);
			dom_append(div6, t7);
			dom_append(div6, div5);
			dom_append(div5, div4);
			dom_append(div4, label_1);
			dom_append(label_1, t8);
			dom_append(label_1, t9);
			dom_append(label_1, span2);
			dom_append(div4, t10);
			dom_append(div4, input);
			set_input_value(input, /*multiplier*/ ctx[1]);
			/*div8_binding*/ ctx[31](div8);
			current = true;

			if (!mounted) {
				dispose = [
					action_destroyer(drag_action = drag.call(null, button0, {
						dragStart: /*dragStart*/ ctx[19],
						dragMove: /*dragMove*/ ctx[20],
						dragEnd: /*dragEnd*/ ctx[21],
						enabled: /*draggable*/ ctx[4]
					})),
					dom_listen(button1, "click", /*click_handler*/ ctx[24]),
					dom_listen(button2, "click", /*click_handler_1*/ ctx[25]),
					dom_listen(div1, "click", /*onToolbarClick*/ ctx[18]),
					dom_listen(input, "input", /*input_input_handler*/ ctx[30]),
					action_destroyer(collapse_action = collapse.call(null, div7, { open: /*open*/ ctx[2], duration: .17 }))
				];

				mounted = true;
			}
		},
		p(ctx, dirty) {
			if (/*ruleCount*/ ctx[5] > 1) {
				if (if_block0) {
					if_block0.p(ctx, dirty);
				} else {
					if_block0 = MatchRule_svelte_create_if_block_2(ctx);
					if_block0.c();
					if_block0.m(div0, t0);
				}
			} else if (if_block0) {
				if_block0.d(1);
				if_block0 = null;
			}

			if (dirty[0] & /*buildRuleLabels, attributes, multiplier*/ 16387) {
				each_value_1 = each_ensure_array_like(/*buildRuleLabels*/ ctx[14](/*attributes*/ ctx[0], /*multiplier*/ ctx[1]));
				each_blocks_1 = update_keyed_each(each_blocks_1, dirty, get_key, 1, ctx, each_value_1, each0_lookup, div0, destroy_block, create_each_block_1, null, get_each_context_1);
			}

			if (drag_action && utils_is_function(drag_action.update) && dirty[0] & /*draggable*/ 16) drag_action.update.call(null, {
				dragStart: /*dragStart*/ ctx[19],
				dragMove: /*dragMove*/ ctx[20],
				dragEnd: /*dragEnd*/ ctx[21],
				enabled: /*draggable*/ ctx[4]
			});

			if (dirty[0] & /*i18n, attributes, removeAttributeRow, data, index, getTermOptions, getAttributeOptions*/ 78601) {
				each_value = each_ensure_array_like(/*attributes*/ ctx[0]);
				transitions_group_outros();
				each_blocks = update_keyed_each(each_blocks, dirty, get_key_1, 1, ctx, each_value, each1_lookup, div2, outro_and_destroy_block, MatchRule_svelte_create_each_block, null, MatchRule_svelte_get_each_context);
				transitions_check_outros();
			}

			if (/*attributes*/ ctx[0].length < /*totalAttributes*/ ctx[10]) {
				if (if_block1) {
					if_block1.p(ctx, dirty);

					if (dirty[0] & /*attributes*/ 1) {
						transitions_transition_in(if_block1, 1);
					}
				} else {
					if_block1 = MatchRule_svelte_create_if_block(ctx);
					if_block1.c();
					transitions_transition_in(if_block1, 1);
					if_block1.m(div3, null);
				}
			} else if (if_block1) {
				transitions_group_outros();

				transitions_transition_out(if_block1, 1, 1, () => {
					if_block1 = null;
				});

				transitions_check_outros();
			}

			if (!current || dirty[0] & /*index*/ 8 && input_name_value !== (input_name_value = /*optionFieldName*/ ctx[17](/*index*/ ctx[3], 'multiplier'))) {
				dom_attr(input, "name", input_name_value);
			}

			if (!current || dirty[0] & /*attributes*/ 1 && input_placeholder_value !== (input_placeholder_value = /*getMultiplierPlaceholder*/ ctx[22](/*attributes*/ ctx[0]))) {
				dom_attr(input, "placeholder", input_placeholder_value);
			}

			if (dirty[0] & /*multiplier*/ 2 && to_number(input.value) !== /*multiplier*/ ctx[1]) {
				set_input_value(input, /*multiplier*/ ctx[1]);
			}

			if (collapse_action && utils_is_function(collapse_action.update) && dirty[0] & /*open*/ 4) collapse_action.update.call(null, { open: /*open*/ ctx[2], duration: .17 });

			if (!current || dirty[0] & /*dragging*/ 128 && div8_style_value !== (div8_style_value = css(/*dragging*/ ctx[7]))) {
				dom_attr(div8, "style", div8_style_value);
			}

			if (!current || dirty[0] & /*multiplier*/ 2) {
				toggle_class(div8, "zero-multiplier", /*multiplier*/ ctx[1] != null && /*multiplier*/ ctx[1] !== '' && +/*multiplier*/ ctx[1] === 0);
			}

			if (!current || dirty[0] & /*multiplier*/ 2) {
				toggle_class(div8, "stop-rule", +/*multiplier*/ ctx[1] < 0);
			}

			if (!current || dirty[0] & /*open*/ 4) {
				toggle_class(div8, "open", /*open*/ ctx[2]);
			}

			if (!current || dirty[0] & /*dragging*/ 128) {
				toggle_class(div8, "dragging", /*dragging*/ ctx[7]);
			}

			if (!current || dirty[0] & /*dragging*/ 128) {
				toggle_class(div8, "released", /*dragging*/ ctx[7] && /*dragging*/ ctx[7].released);
			}
		},
		i(local) {
			if (current) return;

			for (let i = 0; i < each_value.length; i += 1) {
				transitions_transition_in(each_blocks[i]);
			}

			transitions_transition_in(if_block1);

			if (local) {
				add_render_callback(() => {
					if (!current) return;
					if (div8_outro) div8_outro.end(1);
					div8_intro = create_in_transition(div8, shift, { duration: 170 });
					div8_intro.start();
				});
			}

			current = true;
		},
		o(local) {
			for (let i = 0; i < each_blocks.length; i += 1) {
				transitions_transition_out(each_blocks[i]);
			}

			transitions_transition_out(if_block1);
			if (div8_intro) div8_intro.invalidate();

			if (local) {
				div8_outro = create_out_transition(div8, shift, { duration: 170 });
			}

			current = false;
		},
		d(detaching) {
			if (detaching) {
				dom_detach(div8);
			}

			if (if_block0) if_block0.d();

			for (let i = 0; i < each_blocks_1.length; i += 1) {
				each_blocks_1[i].d();
			}

			for (let i = 0; i < each_blocks.length; i += 1) {
				each_blocks[i].d();
			}

			if (if_block1) if_block1.d();
			/*div8_binding*/ ctx[31](null);
			if (detaching && div8_outro) div8_outro.end();
			mounted = false;
			utils_run_all(dispose);
		}
	};
}

function getSelectedAttr(attributes) {
	const selected = {};

	for (const attr of attributes) {
		if (attr[0]) {
			selected[attr[0]] = true;
		}
	}

	return selected;
}

function css(dragging) {
	if (dragging) {
		return `transform: translateY(${dragging.offset}px);`;
	}
}

function MatchRule_svelte_instance($$self, $$props, $$invalidate) {
	let selectedAttr;
	let { index } = $$props;
	let { attributes } = $$props;
	let { multiplier = '' } = $$props;
	let { mounted = false } = $$props;
	let { open = true } = $$props;
	let { draggable = true } = $$props;
	let { ruleCount = 1 } = $$props;
	const data = getContext('data');
	const i18n = data.i18n;
	const totalAttributes = Object.keys(data.attributes).length;
	const dispatch = createEventDispatcher();
	const onePlaceholder = formatNumber(1, true);
	let ruleEl;
	let dragging = false;

	onMount(() => {
		initTooltips('.mewz-wcas-match-rule .woocommerce-help-tip');
	});

	function getAttributeOptions(attrId) {
		const options = [{ id: '', text: '' }];

		for (const [id, text] of data.attributeOptions) {
			const opt = { id, text };

			if (id === attrId) {
				opt.selected = true;
			} else if (selectedAttr[id]) {
				opt.disabled = true;
			}

			options.push(opt);
		}

		return options;
	}

	function getTermOptions(attr) {
		if (!attr[0]) {
			return [];
		}

		const terms = data.attributes[attr[0]].terms;
		const options = [];
		const termIds = {};

		for (const termId of attr[1]) {
			termIds[termId] = true;
		}

		for (const [id, text] of terms) {
			const opt = { id, text };

			if (termIds[id]) {
				opt.selected = true;
			}

			options.push(opt);
		}

		return options;
	}

	function buildRuleLabels(attributes, multiplier) {
		const labels = [];

		for (const [attrId, termIds] of attributes) {
			if (!attrId || !termIds) {
				continue;
			}

			const termLabels = [];

			if (termIds.length) {
				for (const term of data.attributes[attrId].terms) {
					if (termIds.includes(term[0])) {
						termLabels.push(term[1]);

						if (termLabels.length === termIds.length) {
							break;
						}
					}
				}
			}

			labels.push({
				type: 'attribute',
				name: data.attributes[attrId].label,
				value: termLabels.length ? termLabels.join(', ') : i18n.any
			});
		}

		if (!labels.length) {
			labels.push({ type: 'attribute', value: '...' });
		}

		if (multiplier != null && multiplier !== '' && +multiplier !== 1) {
			if (+multiplier < 0) {
				labels.push({ type: 'stop', title: i18n.stopRuleTip });
				setTimeout(() => initTooltips('.mewz-wcas-match-rule [rel="tiptip"]'));
			} else {
				labels.push({
					type: 'multiplier',
					value: `×${formatNumber(+multiplier)}`
				});
			}
		} else if (+multiplier !== 1) {
			const multiplier = getAttributeMultiplier(attributes);

			if (multiplier != null) {
				let value;

				if (Array.isArray(multiplier)) {
					value = `×${formatNumber(multiplier[0])}–${formatNumber(multiplier[1])}`;
				} else {
					value = `×${formatNumber(multiplier)}`;
				}

				labels.push({
					type: 'multiplier',
					class: 'inherited',
					title: i18n.multiplierInherited,
					value
				});
			}
		}

		return labels;
	}

	function addAttributeRow() {
		const row = [0, []];

		if (attributes) {
			attributes.push(row);
			$$invalidate(0, attributes);
		} else {
			$$invalidate(0, attributes = [row]);
		}

		if (mounted) {
			setTimeout(
				() => {
					const select = ruleEl.querySelector('.attribute-row:last-child .select-attribute select');
					if (select) jQuery(select).focus();
				},
				150
			);
		}
	}

	function removeAttributeRow(rowIndex) {
		attributes.splice(rowIndex, 1);

		if (attributes.length) {
			$$invalidate(0, attributes);
		} else {
			addAttributeRow();
		}
	}

	function attrFieldName(ruleIndex, rowIndex, name, suffix) {
		return `${data.name}[${ruleIndex}][attributes][${rowIndex}][${name}]${suffix}`;
	}

	function optionFieldName(ruleIndex, name) {
		return `${data.name}[${ruleIndex}][${name}]`;
	}

	function isAttributeSelected(attrId, exclRowIndex) {
		for (let i = 0; i < attributes.length; i++) {
			if (i !== exclRowIndex && attributes[i][0] && attributes[i][0] === attrId) {
				return true;
			}
		}

		return false;
	}

	function onToolbarClick(e) {
		if (dragging || e && e.target.closest('button')) {
			return;
		}

		$$invalidate(2, open = !open);
	}

	function dragStart(e) {
		if (dragging) return;

		$$invalidate(7, dragging = {
			height: ruleEl.offsetHeight,
			start: ruleEl.offsetTop,
			offset: 0,
			end: ruleEl.parentNode.offsetHeight,
			list: getDragList(),
			targetIndex: index,
			animatedIndex: index,
			released: false
		});

		dispatch('dragging', [index, { released: false }]);
	}

	function dragMove(e) {
		if (!dragging || dragging.released) {
			return;
		}

		$$invalidate(7, dragging.offset = Math.max(-dragging.start, e.moveY), dragging);
		$$invalidate(7, dragging.offset = Math.min(dragging.offset, dragging.end - dragging.start - dragging.height), dragging);
		$$invalidate(7, dragging.targetIndex = calcDragTargetIndex(), dragging);
		updateDragListOffsets();
	}

	function dragEnd() {
		if (!dragging || dragging.released) {
			return;
		}

		const targetOffset = calcDragTargetOffset();

		if (dragging.offset === targetOffset) {
			if (dragging.targetIndex === index) {
				dispatch('dragging', [index, false]);
				$$invalidate(7, dragging = false);
			} else {
				let animating = dragging.list.some(r => r.animating === true);

				if (animating) {
					setTimeout(dragEndPropagate, 200);
					dispatch('dragging', [index, { released: true }]);
				} else {
					dragEndPropagate();
				}
			}
		} else {
			ruleEl.addEventListener('transitionend', dragEndPropagate);
			$$invalidate(7, dragging.offset = targetOffset, dragging);
			$$invalidate(7, dragging.released = true, dragging);
			dispatch('dragging', [index, { released: true }]);
		}
	}

	function dragEndPropagate(e) {
		if (e && e.target !== ruleEl) {
			return;
		}

		if (dragging.released) {
			ruleEl.removeEventListener('transitionend', dragEndPropagate);
		}

		for (const rule of dragging.list) {
			rule.el.style.transform = '';
		}

		dispatch('dragging', [index, false]);

		if (dragging.targetIndex !== index) {
			dispatch('action', ['shift', index, dragging.targetIndex]);
		}

		$$invalidate(7, dragging = false);
	}

	function getDragList() {
		const ruleEls = ruleEl.parentNode.querySelectorAll('.mewz-wcas-match-rule');
		const list = [];
		let top = 0;

		for (const el of ruleEls) {
			const height = el.offsetHeight;

			list.push({
				el,
				height,
				mid: top + Math.round(height / 2),
				offset: 0
			});

			top += height;
		}

		return list;
	}

	function calcDragTargetIndex() {
		let target = index;
		const top = dragging.start + dragging.offset;

		for (let i = 0; i < dragging.list.length; i++) {
			const m = dragging.list[i].mid;

			if (i < index) {
				if (top < m) {
					return i;
				}
			} else if (i > index) {
				if (top + dragging.height > m) {
					target = i;
				}
			}
		}

		return target;
	}

	function updateDragListOffsets() {
		if (dragging.targetIndex === dragging.animatedIndex) {
			return;
		}

		const t = dragging.targetIndex;

		for (let i = 0; i < dragging.list.length; i++) {
			const rule = dragging.list[i];
			let offset = 0;

			if (t < index) {
				if (i >= t && i < index) {
					offset = dragging.height;
				}
			} else if (t > index) {
				if (i <= t && i > index) {
					offset = -dragging.height;
				}
			}

			if (rule.offset !== offset) {
				rule.el.style.transform = `translateY(${offset}px)`;
				rule.offset = offset;

				if (rule.animating == null) {
					rule.el.addEventListener('transitionend', () => {
						rule.animating = false;
					});
				}

				rule.animating = true;
			}
		}

		$$invalidate(7, dragging.animatedIndex = t, dragging);
	}

	function calcDragTargetOffset() {
		const t = dragging.targetIndex;
		let offset = 0;

		if (t < index) {
			for (let i = t; i < index; i++) {
				offset -= dragging.list[i].height;
			}
		} else if (t > index) {
			for (let i = t; i > index; i--) {
				offset += dragging.list[i].height;
			}
		}

		return offset;
	}

	function getAttributeMultiplier(attributes) {
		const attrTermIds = {};

		for (const row of attributes) {
			attrTermIds[row[0]] = row[1];
		}

		for (const [attrId] of data.attributeOptions) {
			const termIds = attrTermIds[attrId];
			if (!termIds) continue;
			const attribute = data.attributes[attrId];

			if (termIds.length === 1) {
				for (const term of attribute.terms) {
					if (term[0] === termIds[0] && term[2] != null) {
						return +term[2];
					}
				}
			} else {
				let range = [Infinity, 0];
				let hasNull = false;

				for (const term of attribute.terms) {
					if (termIds.length && !termIds.includes(term[0])) {
						continue;
					}

					if (term[2] == null) {
						hasNull = true;
						continue;
					}

					const value = +term[2];
					if (value < range[0]) range[0] = value;
					if (value > range[1]) range[1] = value;
				}

				if (range[0] !== Infinity) {
					if (hasNull && range[0] > 1) {
						range[0] = 1;
					}

					return range[0] === range[1] ? range[0] : range;
				}
			}
		}

		return null;
	}

	function getMultiplierPlaceholder(attributes) {
		const multiplier = getAttributeMultiplier(attributes);

		if (multiplier == null) {
			return onePlaceholder;
		} else if (Array.isArray(multiplier)) {
			return formatNumber(multiplier[0]) + ' – ' + formatNumber(multiplier[1]);
		} else {
			return formatNumber(multiplier, true);
		}
	}

	function formatNumber(number, singleDigitDecimal) {
		const formatOpts = singleDigitDecimal && number < 10
		? { minimumFractionDigits: 2 }
		: {
				maximumSignificantDigits: 20,
				maximumFractionDigits: 20
			};

		return number.toLocaleString(data.locale, formatOpts);
	}

	const click_handler = () => dispatch('action', ['duplicate', index]);
	const click_handler_1 = () => dispatch('action', ['remove', index]);
	const change_handler = (row, each_value, rowIndex, e) => $$invalidate(0, each_value[rowIndex][0] = +e.detail.handler.val(), attributes);
	const change_handler_1 = (row, each_value, rowIndex, e) => $$invalidate(0, each_value[rowIndex][1] = e.detail.handler.val().map(Number), attributes);
	const click_handler_2 = rowIndex => removeAttributeRow(rowIndex);
	const click_handler_3 = () => addAttributeRow();

	function input_input_handler() {
		multiplier = to_number(this.value);
		$$invalidate(1, multiplier);
	}

	function div8_binding($$value) {
		binding_callbacks[$$value ? 'unshift' : 'push'](() => {
			ruleEl = $$value;
			$$invalidate(6, ruleEl);
		});
	}

	$$self.$$set = $$props => {
		if ('index' in $$props) $$invalidate(3, index = $$props.index);
		if ('attributes' in $$props) $$invalidate(0, attributes = $$props.attributes);
		if ('multiplier' in $$props) $$invalidate(1, multiplier = $$props.multiplier);
		if ('mounted' in $$props) $$invalidate(23, mounted = $$props.mounted);
		if ('open' in $$props) $$invalidate(2, open = $$props.open);
		if ('draggable' in $$props) $$invalidate(4, draggable = $$props.draggable);
		if ('ruleCount' in $$props) $$invalidate(5, ruleCount = $$props.ruleCount);
	};

	$$self.$$.update = () => {
		if ($$self.$$.dirty[0] & /*attributes*/ 1) {
			// always keep one attribute row
			$: if (!attributes.length) {
				addAttributeRow();
			}
		}

		if ($$self.$$.dirty[0] & /*attributes*/ 1) {
			// keep an index of selected attributes
			$: selectedAttr = getSelectedAttr(attributes);
		}
	};

	return [
		attributes,
		multiplier,
		open,
		index,
		draggable,
		ruleCount,
		ruleEl,
		dragging,
		data,
		i18n,
		totalAttributes,
		dispatch,
		getAttributeOptions,
		getTermOptions,
		buildRuleLabels,
		addAttributeRow,
		removeAttributeRow,
		optionFieldName,
		onToolbarClick,
		dragStart,
		dragMove,
		dragEnd,
		getMultiplierPlaceholder,
		mounted,
		click_handler,
		click_handler_1,
		change_handler,
		change_handler_1,
		click_handler_2,
		click_handler_3,
		input_input_handler,
		div8_binding
	];
}

class MatchRule extends SvelteComponent {
	constructor(options) {
		super();

		init(
			this,
			options,
			MatchRule_svelte_instance,
			MatchRule_svelte_create_fragment,
			safe_not_equal,
			{
				index: 3,
				attributes: 0,
				multiplier: 1,
				mounted: 23,
				open: 2,
				draggable: 4,
				ruleCount: 5
			},
			null,
			[-1, -1]
		);
	}
}

/* harmony default export */ const MatchRule_svelte = (MatchRule);
;// CONCATENATED MODULE: ./admin/stock-edit/ui/MatchRules.svelte
/* assets/src/admin/stock-edit/ui/MatchRules.svelte generated by Svelte v4.2.18 */







function MatchRules_svelte_get_each_context(ctx, list, i) {
	const child_ctx = ctx.slice();
	child_ctx[26] = list[i];
	child_ctx[27] = list;
	child_ctx[28] = i;
	return child_ctx;
}

// (171:3) {#if removedRules.length}
function MatchRules_svelte_create_if_block_1(ctx) {
	let button;
	let button_title_value;
	let mounted;
	let dispose;

	return {
		c() {
			button = dom_element("button");
			dom_attr(button, "type", "button");
			dom_attr(button, "class", "button restore-button");
			dom_attr(button, "title", button_title_value = /*data*/ ctx[1].i18n.restoreRule);
		},
		m(target, anchor) {
			dom_insert(target, button, anchor);

			if (!mounted) {
				dispose = dom_listen(button, "click", /*restoreRule*/ ctx[11]);
				mounted = true;
			}
		},
		p(ctx, dirty) {
			if (dirty & /*data*/ 2 && button_title_value !== (button_title_value = /*data*/ ctx[1].i18n.restoreRule)) {
				dom_attr(button, "title", button_title_value);
			}
		},
		d(detaching) {
			if (detaching) {
				dom_detach(button);
			}

			mounted = false;
			dispose();
		}
	};
}

// (182:2) {#each rules as rule, ruleIndex (rule)}
function MatchRules_svelte_create_each_block(key_1, ctx) {
	let first;
	let matchrule;
	let updating_mounted;
	let updating_attributes;
	let updating_multiplier;
	let updating_open;
	let current;

	function matchrule_mounted_binding(value) {
		/*matchrule_mounted_binding*/ ctx[14](value);
	}

	function matchrule_attributes_binding(value) {
		/*matchrule_attributes_binding*/ ctx[15](value, /*rule*/ ctx[26]);
	}

	function matchrule_multiplier_binding(value) {
		/*matchrule_multiplier_binding*/ ctx[16](value, /*rule*/ ctx[26]);
	}

	function matchrule_open_binding(value) {
		/*matchrule_open_binding*/ ctx[17](value, /*rule*/ ctx[26]);
	}

	let matchrule_props = {
		index: /*ruleIndex*/ ctx[28],
		draggable: !/*dragging*/ ctx[6],
		ruleCount: /*rules*/ ctx[0].length
	};

	if (/*mounted*/ ctx[5] !== void 0) {
		matchrule_props.mounted = /*mounted*/ ctx[5];
	}

	if (/*rule*/ ctx[26].attributes !== void 0) {
		matchrule_props.attributes = /*rule*/ ctx[26].attributes;
	}

	if (/*rule*/ ctx[26].multiplier !== void 0) {
		matchrule_props.multiplier = /*rule*/ ctx[26].multiplier;
	}

	if (/*rule*/ ctx[26].open !== void 0) {
		matchrule_props.open = /*rule*/ ctx[26].open;
	}

	matchrule = new MatchRule_svelte({ props: matchrule_props });
	binding_callbacks.push(() => bind(matchrule, 'mounted', matchrule_mounted_binding));
	binding_callbacks.push(() => bind(matchrule, 'attributes', matchrule_attributes_binding));
	binding_callbacks.push(() => bind(matchrule, 'multiplier', matchrule_multiplier_binding));
	binding_callbacks.push(() => bind(matchrule, 'open', matchrule_open_binding));
	matchrule.$on("action", /*onRuleAction*/ ctx[10]);
	matchrule.$on("dragging", /*onRuleDragging*/ ctx[9]);

	return {
		key: key_1,
		first: null,
		c() {
			first = empty();
			create_component(matchrule.$$.fragment);
			this.first = first;
		},
		m(target, anchor) {
			dom_insert(target, first, anchor);
			mount_component(matchrule, target, anchor);
			current = true;
		},
		p(new_ctx, dirty) {
			ctx = new_ctx;
			const matchrule_changes = {};
			if (dirty & /*rules*/ 1) matchrule_changes.index = /*ruleIndex*/ ctx[28];
			if (dirty & /*dragging*/ 64) matchrule_changes.draggable = !/*dragging*/ ctx[6];
			if (dirty & /*rules*/ 1) matchrule_changes.ruleCount = /*rules*/ ctx[0].length;

			if (!updating_mounted && dirty & /*mounted*/ 32) {
				updating_mounted = true;
				matchrule_changes.mounted = /*mounted*/ ctx[5];
				add_flush_callback(() => updating_mounted = false);
			}

			if (!updating_attributes && dirty & /*rules*/ 1) {
				updating_attributes = true;
				matchrule_changes.attributes = /*rule*/ ctx[26].attributes;
				add_flush_callback(() => updating_attributes = false);
			}

			if (!updating_multiplier && dirty & /*rules*/ 1) {
				updating_multiplier = true;
				matchrule_changes.multiplier = /*rule*/ ctx[26].multiplier;
				add_flush_callback(() => updating_multiplier = false);
			}

			if (!updating_open && dirty & /*rules*/ 1) {
				updating_open = true;
				matchrule_changes.open = /*rule*/ ctx[26].open;
				add_flush_callback(() => updating_open = false);
			}

			matchrule.$set(matchrule_changes);
		},
		i(local) {
			if (current) return;
			transitions_transition_in(matchrule.$$.fragment, local);
			current = true;
		},
		o(local) {
			transitions_transition_out(matchrule.$$.fragment, local);
			current = false;
		},
		d(detaching) {
			if (detaching) {
				dom_detach(first);
			}

			destroy_component(matchrule, detaching);
		}
	};
}

// (199:1) {#if !changed}
function MatchRules_svelte_create_if_block(ctx) {
	let input;

	return {
		c() {
			input = dom_element("input");
			dom_attr(input, "type", "hidden");
			dom_attr(input, "name", "mewz_wcas_noupdate[rules]");
			input.value = "1";
		},
		m(target, anchor) {
			dom_insert(target, input, anchor);
		},
		d(detaching) {
			if (detaching) {
				dom_detach(input);
			}
		}
	};
}

function MatchRules_svelte_create_fragment(ctx) {
	let div4;
	let div2;
	let div0;
	let button0;
	let t0_value = /*data*/ ctx[1].i18n.newRule + "";
	let t0;
	let t1;
	let span;
	let span_title_value;
	let t2;
	let div1;
	let t3;
	let button1;

	let t4_value = (isAllOpen(/*rules*/ ctx[0])
	? /*data*/ ctx[1].i18n.closeAll
	: /*data*/ ctx[1].i18n.expandAll) + "";

	let t4;
	let button1_class_value;
	let t5;
	let div3;
	let each_blocks = [];
	let each_1_lookup = new Map();
	let t6;
	let current;
	let mounted;
	let dispose;
	let if_block0 = /*removedRules*/ ctx[4].length && MatchRules_svelte_create_if_block_1(ctx);
	let each_value = each_ensure_array_like(/*rules*/ ctx[0]);
	const get_key = ctx => /*rule*/ ctx[26];

	for (let i = 0; i < each_value.length; i += 1) {
		let child_ctx = MatchRules_svelte_get_each_context(ctx, each_value, i);
		let key = get_key(child_ctx);
		each_1_lookup.set(key, each_blocks[i] = MatchRules_svelte_create_each_block(key, child_ctx));
	}

	let if_block1 = !/*changed*/ ctx[7] && MatchRules_svelte_create_if_block(ctx);

	return {
		c() {
			div4 = dom_element("div");
			div2 = dom_element("div");
			div0 = dom_element("div");
			button0 = dom_element("button");
			t0 = dom_text(t0_value);
			t1 = space();
			span = dom_element("span");
			t2 = space();
			div1 = dom_element("div");
			if (if_block0) if_block0.c();
			t3 = space();
			button1 = dom_element("button");
			t4 = dom_text(t4_value);
			t5 = space();
			div3 = dom_element("div");

			for (let i = 0; i < each_blocks.length; i += 1) {
				each_blocks[i].c();
			}

			t6 = space();
			if (if_block1) if_block1.c();
			dom_attr(button0, "type", "button");
			dom_attr(button0, "class", "button add-button");
			dom_attr(span, "class", "woocommerce-help-tip");
			dom_attr(span, "title", span_title_value = /*data*/ ctx[1].i18n.newRuleTip);
			dom_attr(div0, "class", "toolbar-left");
			dom_attr(button1, "type", "button");
			dom_attr(button1, "class", button1_class_value = "button toggle-button " + (isAllOpen(/*rules*/ ctx[0]) ? 'collapse' : 'expand'));
			dom_attr(div1, "class", "toolbar-right");
			dom_attr(div2, "class", "main-toolbar");
			dom_attr(div3, "class", "match-rules-list");
			dom_attr(div4, "class", "mewz-wcas-attribute-rules");
			toggle_class(div4, "dragging", /*dragging*/ ctx[6]);
			toggle_class(div4, "released", /*dragging*/ ctx[6] && /*dragging*/ ctx[6].released);
		},
		m(target, anchor) {
			dom_insert(target, div4, anchor);
			dom_append(div4, div2);
			dom_append(div2, div0);
			dom_append(div0, button0);
			dom_append(button0, t0);
			dom_append(div0, t1);
			dom_append(div0, span);
			dom_append(div2, t2);
			dom_append(div2, div1);
			if (if_block0) if_block0.m(div1, null);
			dom_append(div1, t3);
			dom_append(div1, button1);
			dom_append(button1, t4);
			dom_append(div4, t5);
			dom_append(div4, div3);

			for (let i = 0; i < each_blocks.length; i += 1) {
				if (each_blocks[i]) {
					each_blocks[i].m(div3, null);
				}
			}

			/*div3_binding*/ ctx[18](div3);
			dom_append(div4, t6);
			if (if_block1) if_block1.m(div4, null);
			/*div4_binding*/ ctx[19](div4);
			current = true;

			if (!mounted) {
				dispose = [
					dom_listen(button0, "click", /*click_handler*/ ctx[13]),
					dom_listen(button1, "click", /*toggleAllOpen*/ ctx[12])
				];

				mounted = true;
			}
		},
		p(ctx, [dirty]) {
			if ((!current || dirty & /*data*/ 2) && t0_value !== (t0_value = /*data*/ ctx[1].i18n.newRule + "")) set_data(t0, t0_value);

			if (!current || dirty & /*data*/ 2 && span_title_value !== (span_title_value = /*data*/ ctx[1].i18n.newRuleTip)) {
				dom_attr(span, "title", span_title_value);
			}

			if (/*removedRules*/ ctx[4].length) {
				if (if_block0) {
					if_block0.p(ctx, dirty);
				} else {
					if_block0 = MatchRules_svelte_create_if_block_1(ctx);
					if_block0.c();
					if_block0.m(div1, t3);
				}
			} else if (if_block0) {
				if_block0.d(1);
				if_block0 = null;
			}

			if ((!current || dirty & /*rules, data*/ 3) && t4_value !== (t4_value = (isAllOpen(/*rules*/ ctx[0])
			? /*data*/ ctx[1].i18n.closeAll
			: /*data*/ ctx[1].i18n.expandAll) + "")) set_data(t4, t4_value);

			if (!current || dirty & /*rules*/ 1 && button1_class_value !== (button1_class_value = "button toggle-button " + (isAllOpen(/*rules*/ ctx[0]) ? 'collapse' : 'expand'))) {
				dom_attr(button1, "class", button1_class_value);
			}

			if (dirty & /*rules, dragging, mounted, onRuleAction, onRuleDragging*/ 1633) {
				each_value = each_ensure_array_like(/*rules*/ ctx[0]);
				transitions_group_outros();
				each_blocks = update_keyed_each(each_blocks, dirty, get_key, 1, ctx, each_value, each_1_lookup, div3, outro_and_destroy_block, MatchRules_svelte_create_each_block, null, MatchRules_svelte_get_each_context);
				transitions_check_outros();
			}

			if (!/*changed*/ ctx[7]) {
				if (if_block1) {
					
				} else {
					if_block1 = MatchRules_svelte_create_if_block(ctx);
					if_block1.c();
					if_block1.m(div4, null);
				}
			} else if (if_block1) {
				if_block1.d(1);
				if_block1 = null;
			}

			if (!current || dirty & /*dragging*/ 64) {
				toggle_class(div4, "dragging", /*dragging*/ ctx[6]);
			}

			if (!current || dirty & /*dragging*/ 64) {
				toggle_class(div4, "released", /*dragging*/ ctx[6] && /*dragging*/ ctx[6].released);
			}
		},
		i(local) {
			if (current) return;

			for (let i = 0; i < each_value.length; i += 1) {
				transitions_transition_in(each_blocks[i]);
			}

			current = true;
		},
		o(local) {
			for (let i = 0; i < each_blocks.length; i += 1) {
				transitions_transition_out(each_blocks[i]);
			}

			current = false;
		},
		d(detaching) {
			if (detaching) {
				dom_detach(div4);
			}

			if (if_block0) if_block0.d();

			for (let i = 0; i < each_blocks.length; i += 1) {
				each_blocks[i].d();
			}

			/*div3_binding*/ ctx[18](null);
			if (if_block1) if_block1.d();
			/*div4_binding*/ ctx[19](null);
			mounted = false;
			utils_run_all(dispose);
		}
	};
}

function isAllOpen(rules) {
	return rules.every(set => set.open);
}

function ruleHasData(rule) {
	for (const row of rule.attributes) {
		if (row[0]) {
			return true;
		}
	}

	return false;
}

function MatchRules_svelte_instance($$self, $$props, $$invalidate) {
	const data = $$props.data;
	setContext('data', data);
	let container, rulesListEl;
	let rules = [];
	let removedRules = [];
	let mounted = false;
	let dragging = false;
	let changed = false;

	// set initial match rule data
	const initialRules = data.rules;

	if (initialRules.length) {
		if (initialRules.length === 1) {
			initialRules[0].open = true;
		} else {
			for (let rule of initialRules) {
				rule.open = false;
			}
		}

		rules = initialRules;
	} else {
		newRule();
	}

	// compute attribute select options (sorted by label)
	const attrOptions = [];

	for (let attrId in data.attributes) {
		const attr = data.attributes[attrId];
		attrOptions.push([+attrId, attr.label]);
	}

	data.attributeOptions = attrOptions.sort((a, b) => a[1].localeCompare(b[1]));

	onMount(() => {
		initTooltips('.mewz-wcas-attribute-rules .main-toolbar .woocommerce-help-tip');
		detectFieldChanges(container, data.name + '[', v => $$invalidate(7, changed = v));
		$$invalidate(5, mounted = true);
	});

	function newRule() {
		const rule = {
			attributes: [],
			multiplier: '',
			open: true
		};

		rules.push(rule);
		$$invalidate(0, rules);
		return rule;
	}

	function onRuleDragging(e) {
		const [i, value] = e.detail;
		$$invalidate(6, dragging = value);
	}

	function onRuleAction(e) {
		const [action, index, value] = e.detail;

		if (action === 'duplicate') {
			duplicateRule(index);
		} else if (action === 'remove') {
			removeRule(index);
		} else if (action === 'shift') {
			shiftRule(index, value);
		}
	}

	function duplicateRule(ruleIndex) {
		const copy = JSON.parse(JSON.stringify(rules[ruleIndex]));
		copy.open = true;
		rules.splice(ruleIndex + 1, 0, copy);
		$$invalidate(0, rules);
	}

	function removeRule(ruleIndex) {
		const rule = rules.splice(ruleIndex, 1)[0];
		if (!rule) return;

		if (ruleHasData(rule)) {
			rule.lastIndex = ruleIndex;
			removedRules.push(rule);
			$$invalidate(4, removedRules);
		}

		if (!rules.length) {
			newRule();
		} else {
			$$invalidate(0, rules);
		}
	}

	function restoreRule() {
		const rule = removedRules.pop();

		if (rule) {
			if (rules.length === 1 && !ruleHasData(rules[0])) {
				$$invalidate(0, rules[0] = rule, rules);
			} else {
				rules.splice(rule.lastIndex, 0, rule);
			}

			delete rule.lastIndex;
			$$invalidate(4, removedRules);
			$$invalidate(0, rules);
		}
	}

	function shiftRule(ruleIndex, targetIndex) {
		const rule = rules.splice(ruleIndex, 1)[0];
		rules.splice(targetIndex, 0, rule);
		$$invalidate(0, rules);
	}

	function toggleAllOpen() {
		const allOpen = isAllOpen(rules);
		rules.forEach(rule => rule.open = !allOpen);
		$$invalidate(0, rules);
	}

	const click_handler = () => newRule();

	function matchrule_mounted_binding(value) {
		mounted = value;
		$$invalidate(5, mounted);
	}

	function matchrule_attributes_binding(value, rule) {
		if ($$self.$$.not_equal(rule.attributes, value)) {
			rule.attributes = value;
			$$invalidate(0, rules);
		}
	}

	function matchrule_multiplier_binding(value, rule) {
		if ($$self.$$.not_equal(rule.multiplier, value)) {
			rule.multiplier = value;
			$$invalidate(0, rules);
		}
	}

	function matchrule_open_binding(value, rule) {
		if ($$self.$$.not_equal(rule.open, value)) {
			rule.open = value;
			$$invalidate(0, rules);
		}
	}

	function div3_binding($$value) {
		binding_callbacks[$$value ? 'unshift' : 'push'](() => {
			rulesListEl = $$value;
			$$invalidate(3, rulesListEl);
		});
	}

	function div4_binding($$value) {
		binding_callbacks[$$value ? 'unshift' : 'push'](() => {
			container = $$value;
			$$invalidate(2, container);
		});
	}

	$$self.$$set = $$new_props => {
		$$invalidate(25, $$props = utils_assign(utils_assign({}, $$props), exclude_internal_props($$new_props)));
	};

	$$self.$$.update = () => {
		if ($$self.$$.dirty & /*rules*/ 1) {
			$: mewzWcas.setTabIndicator('attributes', rules.filter(ruleHasData).length);
		}
	};

	$$props = exclude_internal_props($$props);

	return [
		rules,
		data,
		container,
		rulesListEl,
		removedRules,
		mounted,
		dragging,
		changed,
		newRule,
		onRuleDragging,
		onRuleAction,
		restoreRule,
		toggleAllOpen,
		click_handler,
		matchrule_mounted_binding,
		matchrule_attributes_binding,
		matchrule_multiplier_binding,
		matchrule_open_binding,
		div3_binding,
		div4_binding
	];
}

class MatchRules extends SvelteComponent {
	constructor(options) {
		super();
		init(this, options, MatchRules_svelte_instance, MatchRules_svelte_create_fragment, safe_not_equal, {});
	}
}

/* harmony default export */ const MatchRules_svelte = (MatchRules);
;// CONCATENATED MODULE: ./admin/stock-edit/index.js





load();
header_actions_load();
tab_indicators_load();
new Components_svelte({
  target: document.getElementById('components_panel'),
  props: {
    data: mewzWcas.components
  }
});
new MatchRules_svelte({
  target: document.getElementById('attributes_panel'),
  props: {
    data: mewzWcas.matchRules
  }
});
})();

// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
(() => {
// extracted by mini-css-extract-plugin

})();

/******/ })()
;
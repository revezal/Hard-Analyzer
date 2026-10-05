/**
 * Kigos Carritos Abandonados: captura email / nombre / teléfono en el checkout.
 * Funciona con el checkout clásico y con el checkout por bloques de WooCommerce.
 */
(function () {
	'use strict';

	if (typeof KAC === 'undefined') {
		return;
	}

	var FIELDS = {
		email: ['#billing_email', '#email', 'input[name="billing_email"]', 'input[autocomplete="email"]'],
		first_name: ['#billing_first_name', '#billing-first_name', '#shipping-first_name'],
		last_name: ['#billing_last_name', '#billing-last_name', '#shipping-last_name'],
		phone: ['#billing_phone', '#billing-phone', '#shipping-phone']
	};

	var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
	var lastSent = '';
	var timer = null;

	function valueOf(selectors) {
		for (var i = 0; i < selectors.length; i++) {
			var el = document.querySelector(selectors[i]);
			if (el && el.value) {
				return el.value.trim();
			}
		}
		return '';
	}

	function collect() {
		return {
			email: valueOf(FIELDS.email),
			first_name: valueOf(FIELDS.first_name),
			last_name: valueOf(FIELDS.last_name),
			phone: valueOf(FIELDS.phone)
		};
	}

	function send() {
		var data = collect();
		if (!EMAIL_RE.test(data.email)) {
			return;
		}
		var key = JSON.stringify(data);
		if (key === lastSent) {
			return;
		}
		lastSent = key;

		var body = new FormData();
		body.append('action', 'kac_capture');
		body.append('nonce', KAC.nonce);
		Object.keys(data).forEach(function (k) {
			body.append(k, data[k]);
		});

		fetch(KAC.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' }).catch(function () {
			lastSent = '';
		});
	}

	function schedule() {
		clearTimeout(timer);
		timer = setTimeout(send, 800);
	}

	var allSelectors = [].concat(FIELDS.email, FIELDS.first_name, FIELDS.last_name, FIELDS.phone).join(',');

	document.addEventListener('change', function (e) {
		if (e.target && e.target.matches && e.target.matches(allSelectors)) {
			schedule();
		}
	}, true);

	document.addEventListener('focusout', function (e) {
		if (e.target && e.target.matches && e.target.matches(allSelectors)) {
			schedule();
		}
	}, true);

	// Si el navegador autocompletó el email, lo capturamos al cargar.
	window.addEventListener('load', function () {
		setTimeout(send, 1500);
	});
})();

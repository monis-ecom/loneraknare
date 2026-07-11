(function () {
    'use strict';

    function initForm(form) {
        if (form.__nvLeadInit) return;
        form.__nvLeadInit = true;

        var source = form.getAttribute('data-source') === 'lead' ? 'lead' : 'email';
        var btn = form.querySelector('[data-nv-lead-submit]');
        var msg = form.querySelector('[data-nv-lead-msg]');
        var done = form.querySelector('[data-nv-lead-done]');
        var bodyEl = form.querySelector('[data-nv-lead-body]');

        function showErr(t) { if (msg) { msg.textContent = t; msg.className = 'nv-pw-lead__msg is-error'; } }
        function restore() { if (btn) { btn.disabled = false; if (btn.dataset.orig) btn.textContent = btn.dataset.orig; } }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var cfg = window.nvPwLead || {};
            if (msg) { msg.textContent = ''; msg.className = 'nv-pw-lead__msg'; }

            // Honeypot — a real user never fills this.
            var hp = form.querySelector('.nv-pw-lead__hp');
            if (hp && hp.value) { return; }

            var fields = [];
            var email = '';
            var problem = null;

            form.querySelectorAll('[data-nv-field]').forEach(function (inp) {
                var type = inp.getAttribute('data-type') || inp.type || 'text';
                var label = inp.getAttribute('data-label') || inp.name || '';
                var required = inp.hasAttribute('required') || inp.getAttribute('data-required') === '1';
                var val = (inp.value || '').trim();
                if (required && !val && !problem) problem = { inp: inp, why: 'required' };
                if (type === 'email' && val) {
                    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(val) && !problem) problem = { inp: inp, why: 'email' };
                    if (!email) email = val;
                }
                fields.push({ label: label, value: val, type: type });
            });

            if (problem) {
                showErr(problem.why === 'email'
                    ? (cfg.invalidEmail || 'Please enter a valid email address.')
                    : (cfg.required || 'Please fill in all required fields.'));
                try { problem.inp.focus(); } catch (err) {}
                return;
            }
            if (!cfg.ajaxurl) { showErr(cfg.error || 'Something went wrong. Please try again.'); return; }

            var payload = new URLSearchParams();
            payload.set('action', 'nv_pw_lead_submit');
            payload.set('nonce', cfg.nonce || '');
            payload.set('source', source);
            payload.set('page', window.location.href);
            if (hp) payload.set('nv_pw_hp', hp.value || '');
            if (source === 'email') {
                payload.set('email', email || (fields[0] && fields[0].value) || '');
            } else {
                payload.set('fields', JSON.stringify(fields));
            }

            if (btn) { btn.disabled = true; btn.dataset.orig = btn.textContent; btn.textContent = cfg.sending || 'Sending…'; }

            fetch(cfg.ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: payload.toString()
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.success) {
                        var redirect = form.getAttribute('data-redirect');
                        if (redirect) { window.location.href = redirect; return; }
                        if (done) {
                            if (bodyEl) bodyEl.hidden = true;
                            done.hidden = false;
                        } else {
                            form.reset();
                            if (msg) { msg.textContent = form.getAttribute('data-success') || 'Thank you!'; msg.className = 'nv-pw-lead__msg is-ok'; }
                        }
                    } else {
                        showErr((res && res.data && res.data.message) ? res.data.message : (cfg.error || 'Something went wrong. Please try again.'));
                        restore();
                    }
                })
                .catch(function () { showErr(cfg.error || 'Network error. Please try again.'); restore(); });
        });
    }

    function initAll(root) {
        (root || document).querySelectorAll('[data-nv-lead]').forEach(initForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else {
        initAll(document);
    }

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-email-capture.default', function ($scope) { initAll(($scope && $scope[0]) || document); });
        window.elementorFrontend.hooks.addAction('frontend/element_ready/nv-lead-form.default', function ($scope) { initAll(($scope && $scope[0]) || document); });
    }
})();

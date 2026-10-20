(function () {
  // Tabs: Log in / Create account
  var tabs = document.querySelectorAll('[role=tab]');
  function select(name, focus) {
    tabs.forEach(function (t) {
      var on = t.dataset.tab === name;
      t.setAttribute('aria-selected', on ? 'true' : 'false');
      t.tabIndex = on ? 0 : -1;
      document.getElementById('panel-' + t.dataset.tab).hidden = !on;
      if (on && focus) t.focus();
    });
  }
  tabs.forEach(function (t, i) {
    t.tabIndex = t.getAttribute('aria-selected') === 'true' ? 0 : -1;
    t.addEventListener('click', function () { select(t.dataset.tab); });
    t.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
        e.preventDefault();
        select(tabs[(i + 1) % tabs.length].dataset.tab, true);
      }
    });
  });

  // Register: the privacy pop-up appears after the form is filled in, and Continue stays off until the box is ticked.
  var form = document.getElementById('register-form'), dlg = document.getElementById('consent-dialog');
  if (form && dlg && typeof dlg.showModal === 'function') {
    var check = document.getElementById('consent-check'), go = document.getElementById('consent-go'),
        cancel = document.getElementById('consent-cancel'), field = document.getElementById('consent-field');
    form.addEventListener('submit', function (e) {
      if (form.dataset.consented === '1') return;            // already agreed: let it submit
      e.preventDefault();
      if (!form.checkValidity()) { form.reportValidity(); return; }
      check.checked = false; go.disabled = true;
      dlg.showModal();
    });
    check.addEventListener('change', function () { go.disabled = !check.checked; });
    cancel.addEventListener('click', function () { dlg.close(); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    go.addEventListener('click', function () {
      if (!check.checked) return;
      field.value = '1'; form.dataset.consented = '1';
      dlg.close();
      if (form.requestSubmit) form.requestSubmit(); else form.submit();
    });
  }
})();

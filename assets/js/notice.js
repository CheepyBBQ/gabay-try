(function () {
  // Notification pop-up. Opens by itself when the page loads with a message.
  var dlg = document.getElementById('notice-dialog');
  if (!dlg || typeof dlg.showModal !== 'function') return;
  var closeBtn = document.getElementById('notice-close');
  if (closeBtn) closeBtn.addEventListener('click', function () { dlg.close(); });
  dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });

  function open() { if (!dlg.open) dlg.showModal(); }
  window.gabayNotice = { open: open };

  if (!dlg.hasAttribute('data-auto')) return;
  var crisis = document.getElementById('crisis-dialog');
  if (document.body.hasAttribute('data-crisis-open') && crisis) {
    crisis.addEventListener('close', open, { once: true });   // the crisis pop-up always comes first
  } else {
    open();
  }
})();

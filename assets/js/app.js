(function () {
  // Mobile menu
  var t = document.querySelector('.nav-toggle'), n = document.getElementById('site-nav');
  if (t && n) t.addEventListener('click', function () {
    var open = n.classList.toggle('open');
    t.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // Guided breathing: in 4, hold 4, out 6
  var b = document.querySelector('[data-breathing]');
  if (b) {
    var btn = b.querySelector('button'), label = b.querySelector('.breath-label'),
        orb = b.querySelector('.breath-orb'), timer = null, running = false;
    var steps = [['Breathe in', 4, 'in'], ['Hold gently', 4, 'hold'], ['Breathe out slowly', 6, 'out']];
    function run(i) {
      var s = steps[i % 3];
      label.textContent = s[0];
      orb.style.transitionDuration = s[2] === 'hold' ? '0s' : s[1] + 's';
      orb.setAttribute('data-phase', s[2]);
      timer = setTimeout(function () { run(i + 1); }, s[1] * 1000);
    }
    btn.addEventListener('click', function () {
      running = !running;
      if (running) { btn.textContent = 'Stop the exercise'; run(0); }
      else {
        clearTimeout(timer);
        btn.textContent = 'Start the breathing exercise';
        label.textContent = 'Ready when you are';
        orb.setAttribute('data-phase', 'idle');
      }
    });
  }
})();

// Show / hide for masked values (emails) and password fields: <button data-reveal="id">
(function () {
  document.querySelectorAll('[data-reveal]').forEach(function (btn) {
    var target = document.getElementById(btn.dataset.reveal);
    if (!target) return;
    btn.addEventListener('click', function () {
      var shown;
      if (target.tagName === 'INPUT') {
        shown = target.type === 'password';
        target.type = shown ? 'text' : 'password';
      } else {
        var masked = target.querySelector('.masked'), full = target.querySelector('.full');
        shown = full.hidden;
        full.hidden = !shown; masked.hidden = shown;
      }
      btn.textContent = shown ? 'Hide' : 'Show';
      btn.setAttribute('aria-pressed', shown ? 'true' : 'false');
    });
  });
})();

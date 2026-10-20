<?php
// Breathing, grounding and crisis contacts. Shown on the student and counselor dashboards.
function render_toolkit(bool $forStudent = true): void { ?>
<section id="toolkit" aria-labelledby="toolkit-h">
  <h2 id="toolkit-h">Toolkit</h2>
  <p class="muted">Small things that can help you steady yourself. Use whichever feels right.</p>

  <div class="grid-2">
    <section class="panel" data-breathing>
      <h3>Guided breathing</h3>
      <p>Breathe in for 4 seconds, hold for 4, and breathe out for 6. Follow the circle as it grows and shrinks.</p>
      <div class="breath">
        <div class="breath-stage"><div class="breath-orb" data-phase="idle"></div></div>
        <div class="breath-label">Ready when you are</div>
        <button class="btn" type="button">Start the breathing exercise</button>
      </div>
    </section>

    <section class="panel">
      <h3 style="font-size: 0.85rem;">GROUNDING: 5-4-3-2-1</h3>
      <img src="<?= e(url('assets/img/grounding.png')) ?>" style="width: 100%; height:auto;" alt="Grounding: 5-4-3-2-1" class="grounding-img">
    </section>
  </div>

  <section class="panel">
    <h3>Crisis contacts</h3>
    <p><?= $forStudent
        ? 'If you are thinking about hurting yourself, or you feel unsafe, contact one of these right now. You can also tell someone near you.'
        : 'If you or a student you are supporting is in crisis, these are the contacts to use right now.' ?></p>
    <ul class="hotline-list">
      <?php foreach (hotlines() as $l): ?>
        <li><?= icon('phone', 16) ?><span><?= e($l['name']) ?></span> <strong><?= e($l['number']) ?></strong> <span class="muted"><?= e($l['note']) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php if (!HOTLINES_VERIFIED): ?><p class="muted small">These numbers are pending verification by the school.</p><?php endif; ?>
    <?php if ($forStudent): ?><a class="btn btn-quiet" href="<?= e(url('student/book.php')) ?>"><?= icon('calendar') ?>Book a counselor session</a><?php endif; ?>
  </section>
</section>
<?php }

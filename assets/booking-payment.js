(() => {
 document.querySelectorAll('.t4y-payment').forEach(root => {
  const form = root.querySelector('form');
  const choices = [...root.querySelectorAll('[data-t4y-method]')];
  function sync() {
   choices.forEach(card => {
    const selected = card.querySelector('input[type=radio]').checked;
    card.querySelector('.t4y-pay-details').hidden = !selected;
    const select = card.querySelector('select');
    if (select) { select.disabled = !selected; select.required = selected; }
   });
  }
  form.addEventListener('change', sync); sync();
  const failure = form.querySelector('[data-t4y-failure]');
  if (failure) failure.addEventListener('change', () => {
   form.querySelector('[data-t4y-outcome]').value = failure.checked ? 'failed' : 'success';
  });
  const timer = root.querySelector('[data-t4y-hold]');
  const countdown = timer.querySelector('[data-t4y-countdown]');
  const deadline = performance.now() + Number(timer.dataset.t4yHold) * 1000;
  let interval;
  function tick() {
   const seconds = Math.max(0, Math.ceil((deadline - performance.now()) / 1000));
   countdown.textContent = seconds ? `(${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')} remaining)` : '— expired. Reserve again.';
   if (!seconds) { root.querySelector('[data-t4y-submit]').disabled = true; clearInterval(interval); }
  }
  interval = setInterval(tick, 250); tick();
  form.addEventListener('submit', () => { root.querySelector('[data-t4y-submit]').disabled = true; });
 });
})();

'use strict';
// Confirm actions that already opt into confirmation. No hidden form submissions.
document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{
  if(!window.confirm(form.dataset.confirm))event.preventDefault();
}));
// Optional QR display. Ticket validity always comes from the live server.
if(window.QRCode)document.querySelectorAll('[data-qr]').forEach(node=>new QRCode(node,{
  text:node.dataset.qr,width:180,height:180,correctLevel:QRCode.CorrectLevel.M
}));

// Search SICC floor directory without hiding any data permanently.
const floorSearch=document.getElementById('floorRoomSearch');
const floorButtons=[...document.querySelectorAll('.floor-filter')];
const floorSections=[...document.querySelectorAll('[data-floor-section]')];
if(floorSearch&&floorSections.length){
 let selected='all';
 const update=()=>{
  const term=floorSearch.value.trim().toLocaleLowerCase();
  let matches=0;
  floorSections.forEach(section=>{
   const onFloor=selected==='all'||selected===section.dataset.floorSection;
   let visible=0;
   section.querySelectorAll('[data-room-name]').forEach(room=>{
    const show=onFloor&&room.dataset.roomName.includes(term);
    room.hidden=!show;if(show){visible++;matches++;}
   });
   section.hidden=!onFloor||visible===0;
  });
  const empty=document.getElementById('floorSearchEmpty');if(empty)empty.hidden=matches!==0;
 };
 floorSearch.addEventListener('input',update);
 floorButtons.forEach(button=>button.addEventListener('click',()=>{
  selected=button.dataset.floor;
  floorButtons.forEach(item=>{
   const active=item===button;item.classList.toggle('is-active',active);
   item.setAttribute('aria-pressed',String(active));
  });
  update();
 }));
 update();
}

// In-page SICC venue details: no navigation or new tabs, and no untrusted HTML.
const venueModal=document.getElementById('venueDetailsModal');
const venueDataset=document.getElementById('venueDirectoryData');
if(venueModal&&venueDataset){
 let roomDetails={};
 try { roomDetails=JSON.parse(venueDataset.textContent||'{}'); }catch(_error){roomDetails={};}
 const byId=id=>document.getElementById(id);
 venueModal.addEventListener('show.bs.modal',event=>{
  const key=event.relatedTarget?.getAttribute('data-venue-key');
  const room=key?roomDetails[key]:null;
  if(!room)return;
  byId('venueModalName').textContent=room.name;
  byId('venueModalLevel').textContent=`Level ${room.level} · Sabah International Convention Centre`;
  byId('venueModalCategory').textContent=room.category;
  byId('venueModalDescription').textContent=room.description;
  byId('venueModalUses').textContent=room.uses;
  byId('venueModalLocation').textContent=`Level ${room.level}, SICC`;
  byId('venueModalCapacity').textContent=Array.isArray(room.estimate)
    ? `Approx. ${room.estimate[0].toLocaleString()}–${room.estimate[1].toLocaleString()} people`
    : 'Not estimated (outdoor)';
  const media=byId('venueModalImage');
  media.replaceChildren();
  if(room.image){
   const photo=document.createElement('img');
   photo.src=room.image;
   photo.alt=room.name;
   photo.decoding='async';
   media.appendChild(photo);
  }else{
   const placeholder=document.createElement('div');
   placeholder.className='room-tile-placeholder';
   const emblem=document.createElement('span');
   emblem.className='room-tile-placeholder-emblem';emblem.textContent='SICC';
   const floor=document.createElement('span');
   floor.className='room-tile-placeholder-level';floor.textContent=`LEVEL ${String(room.level).padStart(2,'0')}`;
   placeholder.append(emblem,floor);
   media.appendChild(placeholder);
  }
  const body=venueModal.querySelector('.modal-body');
  if(body)body.scrollTop=0;
 });
}
// Keep custom date inputs hidden until a custom range is selected.
document.querySelectorAll('[data-analytics-range]').forEach(select=>{
 const form=select.closest('.analytics-controls');
 const change=()=>{if(form)form.classList.toggle('is-custom',select.value==='custom');};
 select.addEventListener('change',change);change();
});

// Interactive Admin timeline: zoom around the pointer, drag horizontally to pan,
// and request fresh server aggregates whenever the time resolution changes.
// No third-party analytics service and no fabricated data points.
document.querySelectorAll('[data-analytics-zoom]').forEach(root => {
  const plot = root.querySelector('[data-zoom-plot]');
  const visible = root.querySelector('[data-zoom-visible]');
  const feedback = root.querySelector('[data-zoom-feedback]');
  const table = document.querySelector('[data-analytics-detail-rows]');
  const total = document.querySelector('[data-analytics-total]');
  const filters = document.querySelector('.analytics-controls');
  const rangeSelect = filters?.querySelector('[data-analytics-range]');
  const fromInput = filters?.querySelector('[name="from"]');
  const toInput = filters?.querySelector('[name="to"]');
  if (!plot || !visible || !feedback || !table) return;

  const DAY = 86400000;
  const parse = value => Date.parse(`${value}T00:00:00Z`);
  const format = ms => new Date(ms).toISOString().slice(0, 10);
  const min = parse(root.dataset.min);
  const max = parse(root.dataset.max);
  let from = parse(root.dataset.from);
  let to = parse(root.dataset.to);
  const initial = { from, to };
  let timer = 0;
  let request = null;
  let pointer = null;
  let serial = 0;

  const days = (a, b) => Math.round((b - a) / DAY) + 1;
  const clamp = (proposedStart, length) => {
    const whole = Math.max(1, Math.min(days(min, max), Math.round(length)));
    const latestStart = max - (whole - 1) * DAY;
    const start = Math.max(min, Math.min(latestStart, Math.round(proposedStart / DAY) * DAY));
    return [start, start + (whole - 1) * DAY];
  };
  const updateLabel = () => { visible.textContent = `${format(from)} – ${format(to)}`; };
  const syncControls = () => {
    if (rangeSelect) {
      rangeSelect.value = 'custom';
      rangeSelect.closest('.analytics-controls')?.classList.add('is-custom');
    }
    if (fromInput) fromInput.value = format(from);
    if (toInput) toInput.value = format(to);
    // Keep the Back to dashboard link in the currently visible date window.
    const back = document.querySelector('a[href*="page=dashboard"]');
    if (back) {
      const backUrl = new URL(back.href, window.location.href);
      backUrl.searchParams.set('range', 'custom');
      backUrl.searchParams.set('from', format(from));
      backUrl.searchParams.set('to', format(to));
      back.href = backUrl.href;
    }
    // Language links are server-rendered; carry the live zoom window into them.
    document.querySelectorAll('a.language-option').forEach(link => {
      const languageUrl = new URL(link.href, window.location.href);
      languageUrl.searchParams.set('range', 'custom');
      languageUrl.searchParams.set('from', format(from));
      languageUrl.searchParams.set('to', format(to));
      link.href = languageUrl.href;
    });
    // Browser reload, changing language, or copying the link retains the zoomed range.
    const url = new URL(window.location.href);
    url.searchParams.set('range', 'custom');
    url.searchParams.set('from', format(from));
    url.searchParams.set('to', format(to));
    window.history.replaceState(window.history.state, '', url.href);
  };
  const show = message => { feedback.textContent = message || ''; };
  const refresh = async () => {
    request?.abort();
    request = new AbortController();
    const thisRequest = ++serial;
    const url = new URL('index.php', window.location.href);
    url.searchParams.set('page', 'analytics_data');
    url.searchParams.set('chart', root.dataset.chart);
    url.searchParams.set('event_id', root.dataset.event || '0');
    url.searchParams.set('range', 'custom');
    url.searchParams.set('from', format(from));
    url.searchParams.set('to', format(to));
    // Background chart refresh must not extend a privileged session's idle timer.
    url.searchParams.set('poll', '1');
    plot.setAttribute('aria-busy', 'true');
    show(root.dataset.loading || 'Loading chart…');
    try {
      const response = await fetch(url.href, {
        credentials: 'same-origin', cache: 'no-store',
        headers: { Accept: 'application/json' }, signal: request.signal
      });
      const data = await response.json();
      if (!response.ok || data.error) throw new Error(data.error || `HTTP ${response.status}`);
      if (thisRequest !== serial) return;
      // SVG markup is returned only by the same-origin Admin-only PHP renderer,
      // which escapes every dynamic value before including it in the SVG.
      plot.innerHTML = data.svg;
      if (total) total.textContent = data.total;
      table.replaceChildren();
      (data.rows || []).forEach(row => {
        const tr = document.createElement('tr');
        [row.period, row.value].forEach(value => {
          const td = document.createElement('td'); td.textContent = String(value);
          tr.appendChild(td);
        });
        table.appendChild(tr);
      });
      show(`${data.rows.length} ${root.dataset.buckets || 'time periods'} · ${data.granularity}`);
    } catch (error) {
      if (error.name === 'AbortError') return;
      show(`${root.dataset.error || 'Could not update chart'}: ${error.message}`);
    } finally {
      if (thisRequest === serial) plot.removeAttribute('aria-busy');
    }
  };
  const schedule = () => {
    clearTimeout(timer);
    updateLabel();
    syncControls();
    timer = window.setTimeout(refresh, 170);
  };
  const resize = (factor, anchor = 0.5) => {
    const oldLength = days(from, to);
    const newLength = Math.max(1, Math.min(days(min, max), Math.round(oldLength * factor)));
    if (newLength === oldLength) return;
    const origin = from + anchor * (oldLength - 1) * DAY;
    [from, to] = clamp(origin - anchor * (newLength - 1) * DAY, newLength);
    schedule();
  };
  root.querySelectorAll('[data-zoom-action]').forEach(button => {
    button.addEventListener('click', () => {
      const action = button.dataset.zoomAction;
      if (action === 'in') resize(0.55);
      if (action === 'out') resize(1.8);
      if (action === 'reset') { [from, to] = clamp(initial.from, days(initial.from, initial.to)); schedule(); }
      if (action === 'full') { [from, to] = [min, max]; schedule(); }
    });
  });
  plot.addEventListener('wheel', event => {
    // Zoom only when the cursor is over the chart; do not zoom on page scroll elsewhere.
    if (Math.abs(event.deltaY) < 1) return;
    const currentDays = days(from, to);
    if ((event.deltaY < 0 && currentDays <= 1) ||
        (event.deltaY > 0 && currentDays >= days(min, max))) return;
    event.preventDefault();
    const rect = plot.getBoundingClientRect();
    const anchor = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
    resize(event.deltaY < 0 ? 0.75 : 1.35, anchor);
  }, { passive: false });
  plot.addEventListener('dblclick', event => {
    event.preventDefault();
    const rect = plot.getBoundingClientRect();
    resize(0.5, Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width)));
  });
  // Drag-to-pan is mouse/pen only; phones retain normal vertical touch scrolling.
  plot.addEventListener('pointerdown', event => {
    if (event.button !== 0 || event.pointerType === 'touch') return;
    pointer = { x: event.clientX, from, to, moved: false };
    plot.setPointerCapture(event.pointerId);
    plot.classList.add('is-dragging');
  });
  plot.addEventListener('pointermove', event => {
    if (!pointer) return;
    const delta = event.clientX - pointer.x;
    if (Math.abs(delta) > 4) pointer.moved = true;
    if (!pointer.moved) return;
    const length = days(pointer.from, pointer.to);
    const shift = Math.round(-delta / Math.max(1, plot.clientWidth) * length);
    const [previewFrom, previewTo] = clamp(pointer.from + shift * DAY, length);
    visible.textContent = `${format(previewFrom)} – ${format(previewTo)}`;
  });
  const finishPan = event => {
    if (!pointer) return;
    const previous = pointer; pointer = null;
    plot.classList.remove('is-dragging');
    if (!previous.moved) { updateLabel(); return; }
    const delta = event.clientX - previous.x;
    const length = days(previous.from, previous.to);
    const shift = Math.round(-delta / Math.max(1, plot.clientWidth) * length);
    const next = clamp(previous.from + shift * DAY, length);
    if (next[0] !== from || next[1] !== to) { [from, to] = next; schedule(); }
    else updateLabel();
  };
  plot.addEventListener('pointerup', finishPan);
  plot.addEventListener('pointercancel', () => { pointer = null; plot.classList.remove('is-dragging'); updateLabel(); });
  plot.addEventListener('keydown', event => {
    if (event.key === '+' || event.key === '=') { event.preventDefault(); resize(0.55); }
    if (event.key === '-') { event.preventDefault(); resize(1.8); }
    if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
      event.preventDefault();
      const length = days(from, to);
      [from, to] = clamp(from + (event.key === 'ArrowRight' ? 1 : -1) * Math.max(1, Math.round(length / 4)) * DAY, length);
      schedule();
    }
  });
  updateLabel();
});

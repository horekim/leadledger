/**
 * Lead Ledger — the behaviour the design asks for.
 *
 * Everything here is an enhancement: each control it hijacks is a real form or
 * link that still works with JavaScript off. What this adds is the optimistic
 * tick, the dialogs, drag-reordering and Esc-to-close.
 */
(function () {
  'use strict';

  var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var BASE = (document.querySelector('meta[name="base-url"]') || {}).content || '/';

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function post(url, payload) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'fetch',
        'X-CSRF-Token': CSRF
      },
      body: JSON.stringify(Object.assign({ csrf: CSRF }, payload || {}))
    }).then(function (res) {
      return res.json().catch(function () { return { ok: false, error: 'Unexpected reply.' }; })
        .then(function (body) {
          if (!res.ok || !body.ok) { throw new Error(body.error || 'That did not save.'); }
          return body;
        });
    });
  }

  /* ── Owned ticks — optimistic, no confirmation, no toast ───────────────── */

  function ownedLine(count, total) {
    var line = $('.page-head .count-line');
    if (line) { line.textContent = count + ' of ' + total + ' owned'; }
  }

  document.addEventListener('submit', function (ev) {
    var form = ev.target;

    if (form.matches('form[data-toggle]')) {
      ev.preventDefault();
      var card  = form.closest('.mini-card');
      var field = $('[data-owned-field]', form);
      var tick  = $('.tick', form);
      var want  = field.value === '1';

      // Flip first, reconcile with the server after.
      card.classList.toggle('is-owned', want);
      field.value = want ? '0' : '1';
      if (tick) { tick.setAttribute('aria-pressed', want ? 'true' : 'false'); }

      post(form.action, { owned: want ? '1' : '0' })
        .then(function (body) { ownedLine(body.owned_count, body.total); })
        .catch(function (err) {
          card.classList.toggle('is-owned', !want);
          field.value = want ? '1' : '0';
          if (tick) { tick.setAttribute('aria-pressed', want ? 'false' : 'true'); }
          window.alert(err.message);
        });
      return;
    }

    // "Tick whole set" — one request, then repaint from the server.
    if (form.matches('form[data-bulk]')) {
      ev.preventDefault();
      var owned = $('input[name="owned"]', form).value;
      post(form.action, { owned: owned })
        .then(function () { window.location.reload(); })
        .catch(function (err) { window.alert(err.message); });
    }
  });

  /* ── The rail accordion remembers what you left open ───────────────────── */

  var RAIL_KEY = 'll.rail.open';

  function railState() {
    try { return JSON.parse(window.localStorage.getItem(RAIL_KEY) || '{}'); }
    catch (err) { return {}; }
  }

  (function initRail() {
    var ranges = $$('.rail-range');
    if (!ranges.length) { return; }
    var state = railState();

    ranges.forEach(function (el) {
      var key = el.getAttribute('data-range');
      // The range holding the open set always starts expanded.
      if (!el.open && state[key]) { el.open = true; }

      el.addEventListener('toggle', function () {
        var next = railState();
        next[key] = el.open;
        try { window.localStorage.setItem(RAIL_KEY, JSON.stringify(next)); } catch (err) { /* private mode */ }
      });
    });
  }());

  /* ── Dialogs and the drawer ────────────────────────────────────────────── */

  var openStack = [];

  function openDialog(el) {
    el.hidden = false;
    openStack.push(el);
    var first = $('input:not([type=hidden]), button', el);
    if (first) { first.focus(); }
  }

  function closeDialog(el) {
    if (!el) { return; }
    el.hidden = true;
    openStack = openStack.filter(function (x) { return x !== el; });
  }

  function closeTop() {
    closeDialog(openStack[openStack.length - 1]);
  }

  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape' && openStack.length) {
      ev.preventDefault();
      closeTop();
    }
  });

  document.addEventListener('click', function (ev) {
    var closer = ev.target.closest('[data-dialog-close]');
    if (closer) {
      ev.preventDefault();
      closeDialog(closer.closest('[data-dialog]'));
      return;
    }

    // Backdrop click closes the drawer only — the dialogs need an explicit Cancel.
    var drawer = $('#drawer');
    if (drawer && !drawer.hidden && ev.target === drawer) {
      closeDialog(drawer);
    }
  });

  /* ── The range / set editor ────────────────────────────────────────────── */

  var editor = $('#editor');

  function fillEditor(d) {
    var isSet = d.kind === 'set';
    $('[data-editor-kind]', editor).value  = d.kind;
    $('[data-editor-id]', editor).value    = d.id || '';
    $('[data-editor-range]', editor).value = d.range || '';
    $('[data-editor-title]', editor).textContent = d.title;
    $('[data-editor-submit]', editor).textContent = d.submit;

    var codeField = $('[data-editor-code-field]', editor);
    codeField.hidden = !isSet;                   // ranges have no code
    $('[data-editor-code]', editor).value = d.code || '';
    $('[data-editor-code]', editor).required = isSet;
    $('[data-editor-name]', editor).value = d.name || '';

    var danger = $('[data-editor-danger]', editor);
    danger.hidden = !d.deletable;
    danger.dataset.delTitle  = d.delTitle || '';
    danger.dataset.delBody   = d.delBody || '';
    danger.dataset.delSubmit = d.delSubmit || 'Delete';
    danger.dataset.delKind   = d.kind;
    danger.dataset.delId     = d.id || '';
  }

  /* ── The delete confirmation ───────────────────────────────────────────── */

  var confirmBox = $('#confirm');

  function fillConfirm(d) {
    $('[data-confirm-kind]', confirmBox).value = d.kind;
    $('[data-confirm-id]', confirmBox).value = d.id;
    $('[data-confirm-title]', confirmBox).textContent = d.title;
    $('[data-confirm-body]', confirmBox).textContent = d.body;
    $('[data-confirm-submit]', confirmBox).textContent = d.submit;
  }

  document.addEventListener('click', function (ev) {
    var open = ev.target.closest('[data-editor-open]');
    if (open && editor) {
      ev.preventDefault();
      fillEditor({
        kind: open.dataset.kind,
        id: open.dataset.id,
        range: open.dataset.range,
        code: open.dataset.code,
        name: open.dataset.name,
        title: open.dataset.title,
        submit: open.dataset.submit,
        deletable: open.dataset.deletable === '1',
        delTitle: open.dataset.delTitle,
        delBody: open.dataset.delBody,
        delSubmit: open.dataset.delSubmit
      });
      openDialog(editor);
      return;
    }

    // The editor's quiet Delete hands off to the confirmation, it does not delete.
    var handoff = ev.target.closest('[data-editor-delete]');
    if (handoff && confirmBox) {
      ev.preventDefault();
      var danger = handoff.closest('[data-editor-danger]');
      closeDialog(editor);
      fillConfirm({
        kind: danger.dataset.delKind,
        id: danger.dataset.delId,
        title: danger.dataset.delTitle,
        body: danger.dataset.delBody,
        submit: danger.dataset.delSubmit
      });
      openDialog(confirmBox);
      return;
    }

    var ask = ev.target.closest('[data-confirm-open]');
    if (ask && confirmBox) {
      ev.preventDefault();
      ev.stopPropagation();
      fillConfirm({
        kind: ask.dataset.kind,
        id: ask.dataset.id,
        title: ask.dataset.title,
        body: ask.dataset.body,
        submit: ask.dataset.submit
      });
      openDialog(confirmBox);
    }
  });

  /* ── The miniature drawer ──────────────────────────────────────────────── */

  var drawer = $('#drawer');

  if (drawer) {
    var file      = $('[data-drawer-file]', drawer);
    var dropzone  = $('[data-dropzone]', drawer);
    var imgEl     = $('[data-dropzone-img]', drawer);
    var emptyEl   = $('[data-dropzone-empty]', drawer);
    var photoRow  = $('[data-photo-row]', drawer);
    var photoName = $('[data-photo-name]', drawer);
    var removeFl  = $('[data-drawer-remove]', drawer);

    function showPhoto(url, name) {
      if (url) {
        imgEl.hidden = false;
        imgEl.style.backgroundImage = 'url("' + url.replace(/"/g, '%22') + '")';
        emptyEl.hidden = true;
        photoRow.hidden = false;
        photoName.textContent = name || '';
      } else {
        imgEl.hidden = true;
        imgEl.style.backgroundImage = '';
        emptyEl.hidden = false;
        photoRow.hidden = true;
        photoName.textContent = '';
      }
    }

    function takeFile(f) {
      if (!f || f.type.indexOf('image/') !== 0) { return; }
      var dt = new DataTransfer();
      dt.items.add(f);
      file.files = dt.files;
      removeFl.value = '0';
      showPhoto(URL.createObjectURL(f), f.name);
    }

    dropzone.addEventListener('click', function () { file.click(); });
    dropzone.addEventListener('keydown', function (ev) {
      if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); file.click(); }
    });
    file.addEventListener('change', function () { takeFile(file.files[0]); });

    ['dragenter', 'dragover'].forEach(function (name) {
      dropzone.addEventListener(name, function (ev) {
        ev.preventDefault();
        dropzone.classList.add('is-dragging');
      });
    });
    ['dragleave', 'drop'].forEach(function (name) {
      dropzone.addEventListener(name, function (ev) {
        ev.preventDefault();
        dropzone.classList.remove('is-dragging');
      });
    });
    dropzone.addEventListener('drop', function (ev) {
      if (ev.dataTransfer && ev.dataTransfer.files.length) { takeFile(ev.dataTransfer.files[0]); }
    });

    $('[data-photo-replace]', drawer).addEventListener('click', function () { file.click(); });
    $('[data-photo-remove]', drawer).addEventListener('click', function () {
      file.value = '';
      removeFl.value = '1';
      showPhoto('', '');
    });

    document.addEventListener('click', function (ev) {
      var trigger = ev.target.closest('[data-drawer-open]');
      if (!trigger || ev.target.closest('.icon-btn')) { return; }
      ev.preventDefault();

      var isNew = !trigger.dataset.id;
      $('[data-drawer-id]', drawer).value = trigger.dataset.id || '';
      $('[data-drawer-code]', drawer).value = trigger.dataset.code || '';
      $('[data-drawer-name]', drawer).value = trigger.dataset.name || '';
      $('[data-drawer-kicker]', drawer).textContent = isNew ? 'New miniature' : 'Edit miniature';
      $('[data-drawer-title]', drawer).textContent = isNew ? 'New miniature' : (trigger.dataset.name || 'Edit miniature');
      file.value = '';
      removeFl.value = '0';
      showPhoto(trigger.dataset.photo || '', trigger.dataset.photoName || '');

      openDialog(drawer);
    });
  }

  /* ── Admin table rows: click through, and drag to reorder ──────────────── */

  document.addEventListener('click', function (ev) {
    var row = ev.target.closest('tr[data-href]');
    if (row && !ev.target.closest('button, a')) {
      window.location.href = row.dataset.href;
    }
  });

  document.addEventListener('keydown', function (ev) {
    if (ev.key !== 'Enter') { return; }
    var row = ev.target.closest && ev.target.closest('tr[data-href]');
    if (row && ev.target === row) { window.location.href = row.dataset.href; }
  });

  $$('table[data-reorder]').forEach(function (table) {
    var body = $('tbody', table);
    var held = null;

    body.addEventListener('dragstart', function (ev) {
      held = ev.target.closest('tr');
      if (!held) { return; }
      held.classList.add('dragging');
      ev.dataTransfer.effectAllowed = 'move';
      try { ev.dataTransfer.setData('text/plain', held.dataset.id); } catch (err) { /* Safari */ }
    });

    body.addEventListener('dragover', function (ev) {
      if (!held) { return; }
      var over = ev.target.closest('tr');
      if (!over || over === held) { return; }
      ev.preventDefault();
      $$('tr.drop-target', body).forEach(function (r) { r.classList.remove('drop-target'); });
      over.classList.add('drop-target');
    });

    body.addEventListener('drop', function (ev) {
      if (!held) { return; }
      ev.preventDefault();
      var over = ev.target.closest('tr');
      $$('tr.drop-target', body).forEach(function (r) { r.classList.remove('drop-target'); });
      if (!over || over === held) { return; }

      var rows  = $$('tr', body);
      var after = rows.indexOf(held) < rows.indexOf(over);
      over.parentNode.insertBefore(held, after ? over.nextSibling : over);

      var order = $$('tr', body).map(function (r) { return r.dataset.id; });
      post(BASE.replace(/\/$/, '') + '/admin/sets/' + table.dataset.set + '/reorder', { order: order })
        .catch(function (err) { window.alert(err.message); window.location.reload(); });
    });

    body.addEventListener('dragend', function () {
      if (held) { held.classList.remove('dragging'); }
      $$('tr.drop-target', body).forEach(function (r) { r.classList.remove('drop-target'); });
      held = null;
    });
  });
}());

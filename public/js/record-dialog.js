(() => {
    const embedded = window.parent !== window && window.name === 'kathford-record';
    if (embedded) {
        document.documentElement.classList.add('record-embedded');
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !document.querySelector('dialog[open]')) window.parent.postMessage({type:'record-close'}, location.origin); });
        document.addEventListener('input', () => window.parent.postMessage({type:'record-dirty'}, location.origin));
        document.addEventListener('submit', () => window.parent.postMessage({type:'record-saved'}, location.origin));
        return;
    }
    let dialog, frame, opener, dirty = false, changed = false;
    const close = () => {
        if (dirty && !confirm('Close this record? Unsaved changes will be lost.')) return;
        dialog.close(); frame.src = 'about:blank'; document.body.style.overflow = ''; opener?.focus();
        if (changed) location.reload();
    };
    document.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link || event.button || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download')) return;
        const url = new URL(link.href, location.href);
        if (url.origin !== location.origin || !/^\/(activity-forms|rfq|purchase-orders|checklists|payments|payment-authorisations)\/[0-9a-f-]{36}(\/edit|\/compare)?$/.test(url.pathname)) return;
        event.preventDefault(); opener = link; dirty = false; changed = false;
        if (!dialog) {
            dialog = document.createElement('dialog'); dialog.className = 'record-dialog'; dialog.setAttribute('aria-label','Record details');
            dialog.innerHTML = '<div class="record-dialog-bar"><strong>Record details</strong><div><a data-full target="_blank" rel="noopener">Open full page ↗</a><button type="button" aria-label="Close record">✕</button></div></div><iframe name="kathford-record" title="Record details and actions"></iframe><div class="record-dialog-footer"><button type="button">Close</button></div>';
            document.body.append(dialog); frame = dialog.querySelector('iframe');
            dialog.querySelectorAll('button').forEach(b => b.onclick = close);
            dialog.addEventListener('cancel', e => { e.preventDefault(); close(); });
            frame.addEventListener('load', () => {
                try {
                    const doc = frame.contentDocument;
                    if (!doc || frame.contentWindow.location.href === 'about:blank') return;
                    doc.documentElement.classList.add('record-embedded');
                    dialog.querySelector('strong').textContent = doc.title.split(' – ')[0] || 'Record details';
                    dialog.querySelector('[data-full]').href = frame.contentWindow.location.href;
                    dirty = false;
                    if (!doc.querySelector('main')) dialog.querySelector('strong').textContent = 'Unable to load record — open full page to retry';
                } catch (_) { dialog.querySelector('strong').textContent = 'Open full page to continue'; }
            });
        }
        dialog.querySelector('[data-full]').href = url.href; frame.src = url.href; dialog.showModal(); document.body.style.overflow = 'hidden';
    });
    window.addEventListener('message', e => {
        if (e.origin !== location.origin || e.source !== frame?.contentWindow) return;
        if (e.data?.type === 'record-close') close();
        if (e.data?.type === 'record-dirty') dirty = true;
        if (e.data?.type === 'record-saved') { changed = true; dirty = false; }
    });
})();

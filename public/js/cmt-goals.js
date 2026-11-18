(() => {
    const dataNode = document.getElementById('cmt-data');
    if (!dataNode) return;
    const data = JSON.parse(dataNode.textContent), body = document.getElementById('cmt-groups');
    const parent = document.getElementById('cmt-parent'), period = document.getElementById('cmt-period');
    let count = 0;
    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const options = (rows, selected, label) => rows.map(row => `<option value="${esc(row.id)}" ${[].concat(selected ?? []).includes(row.id) ? 'selected' : ''}>${esc(label(row))}</option>`).join('');
    const sourceRows = () => data.sources.find(s => s.id === parent.value)?.rows ?? [];
    function resultHtml(prefix, index, kr = {}) {
        const name = `${prefix}[results][${index}]`;
        return `<tr><td><input aria-label="Key result" name="${name}[title]" value="${esc(kr.title)}" placeholder="Canvas course readiness" required maxlength="200"></td><td><input aria-label="Metric or unit" name="${name}[metric]" value="${esc(kr.metric)}" placeholder="%, count or milestone" maxlength="100"></td><td><input aria-label="Baseline" type="number" min="0" step="0.01" name="${name}[baseline]" value="${esc(kr.baseline)}"></td><td><input aria-label="Semester target" type="number" min="0" step="0.01" name="${name}[target]" value="${esc(kr.target)}"></td><td><button type="button" data-remove-result aria-label="Remove key result" class="text-red-600">Remove</button></td></tr>`;
    }
    function doneLineHtml(value = '') {
        return `<div class="flex items-center gap-2 mb-1" data-done-line><input type="text" class="flex-1" placeholder="e.g. All course outlines uploaded to Canvas" value="${esc(value)}"><button type="button" data-remove-done aria-label="Remove condition" class="text-red-600 text-sm">✕</button></div>`;
    }
    function initDoneLines(detail, initialText) {
        const box = detail.querySelector('[data-done-lines]');
        const hidden = detail.querySelector('[data-done-hidden]');
        const sync = () => { hidden.value = [...box.querySelectorAll('[data-done-line] input')].map(i => i.value.trim()).filter(Boolean).join('\n'); };
        const addLine = (value = '') => {
            box.insertAdjacentHTML('beforeend', doneLineHtml(value));
            const line = box.lastElementChild;
            line.querySelector('input').addEventListener('input', sync);
            line.querySelector('[data-remove-done]').onclick = () => { line.remove(); sync(); };
        };
        const lines = String(initialText ?? '').split('\n').map(l => l.trim()).filter(Boolean);
        (lines.length ? lines : ['']).forEach(addLine);
        detail.querySelector('[data-add-done]').onclick = () => addLine();
        sync();
    }
    function mentionChipHtml(prefix, id) {
        const user = data.users.find(u => u.id === id);
        if (!user) return '';
        return `<span class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2 py-1 text-xs font-medium text-teal-800" data-id="${esc(id)}">${esc(user.name)}<button type="button" data-remove-chip aria-label="Remove ${esc(user.name)}" class="text-teal-600 hover:text-teal-900">×</button><input type="hidden" name="${prefix}[supporting_owner_ids][]" value="${esc(id)}"></span>`;
    }
    function initMentions(detail, prefix, initialIds) {
        const box = detail.querySelector('[data-mention-box]');
        const chips = box.querySelector('[data-mention-chips]');
        const input = box.querySelector('[data-mention-input]');
        const suggestions = box.querySelector('[data-mention-suggestions]');
        const selected = new Set(initialIds || []);
        const renderChips = () => { chips.innerHTML = [...selected].map(id => mentionChipHtml(prefix, id)).join(''); };
        const hideSuggestions = () => { suggestions.classList.add('hidden'); suggestions.innerHTML = ''; };
        const addUser = id => { selected.add(id); renderChips(); input.value = ''; hideSuggestions(); input.focus(); };
        const showSuggestions = () => {
            const q = input.value.replace(/^@/, '').trim().toLowerCase();
            const matches = data.users.filter(u => !selected.has(u.id) && (!q || u.name.toLowerCase().includes(q))).slice(0, 8);
            if (!matches.length) { hideSuggestions(); return; }
            suggestions.innerHTML = matches.map(u => `<button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-teal-50" data-id="${esc(u.id)}">${esc(u.name)}</button>`).join('');
            suggestions.classList.remove('hidden');
        };
        input.addEventListener('input', showSuggestions);
        input.addEventListener('focus', showSuggestions);
        input.addEventListener('keydown', e => {
            if (e.key === 'Escape') hideSuggestions();
            if (e.key === 'Enter') { e.preventDefault(); const first = suggestions.querySelector('button'); if (first) addUser(first.dataset.id); }
        });
        suggestions.addEventListener('mousedown', e => { const btn = e.target.closest('button[data-id]'); if (btn) { e.preventDefault(); addUser(btn.dataset.id); } });
        chips.addEventListener('click', e => { const btn = e.target.closest('[data-remove-chip]'); if (btn) { selected.delete(btn.closest('[data-id]').dataset.id); renderChips(); } });
        document.addEventListener('click', e => { if (!box.contains(e.target)) hideSuggestions(); });
        renderChips();
    }
    function addGoal(g = {}) {
        const index = count++, prefix = `cmt_groups[${index}]`, results = g.results?.length ? g.results : [{}];
        const row = document.createElement('tr'); row.dataset.goal = index;
        const selectedSource=sourceRows().find(r=>r.id===g.source_id);
        const priority=selectedSource?.strategy.priority??'';
        const priorities=[...new Set(sourceRows().map(r=>r.strategy.priority||'Other'))];
        row.innerHTML = `<td class="align-top"><select aria-label="Strategic priority" data-priority required><option value="">Choose priority</option>${priorities.map(p=>`<option ${p===priority?'selected':''} value="${esc(p)}">${esc(p)}</option>`).join('')}</select></td><td class="align-top"><input type="hidden" name="${prefix}[key]" value="${esc(g.key || crypto.randomUUID())}"><select aria-label="Primary strategic link" data-source name="${prefix}[source_id]" required><option value="">Choose strategic goal</option>${options(sourceRows().filter(r=>(r.strategy.priority||'Other')===priority), g.source_id, r => r.strategy.goal || r.title)}</select><p data-target class="text-xs whitespace-pre-line text-gray-500 mt-2"></p></td>
        <td class="align-top"><input aria-label="${esc(data.goalLabel||'CMT semester goal')}" name="${prefix}[title]" value="${esc(g.title)}" required maxlength="200" placeholder="What should this semester achieve?"></td>
        <td class="align-top"><select aria-label="Accountable owner" name="${prefix}[owner_id]"><option value="">Choose owner</option>${options(data.users.filter(u=>!data.departmentGoals || u.department_id===document.querySelector('[name=department_id]')?.value),g.owner_id,r=>r.name)}</select></td>
        <td class="align-top"><input aria-label="Goal due date" type="date" name="${prefix}[due_on]" value="${esc(g.due_on)}"></td><td class="align-top"><button type="button" data-remove-goal class="text-red-600" aria-label="Remove goal">Remove</button></td>`;
        const detail = document.createElement('td'); detail.dataset.details = index;
        detail.innerHTML = `<div class="space-y-3">
            <table class="w-full"><thead><tr><th>Key result *</th><th>Metric / unit</th><th>Baseline</th><th>Target</th><th></th></tr></thead><tbody data-results>${results.map((kr,i)=>resultHtml(prefix,i,kr)).join('')}</tbody></table>
            <button type="button" data-add-result class="text-teal-700">+ Add key result</button>
            <p class="text-xs text-gray-500">Use separate results for each measure. For a completion milestone, use target 1 and describe the evidence in Definition of Done.</p>

            <div class="border-t border-gray-200 pt-3">
                <label class="font-medium">Definition of Done</label>
                <p class="text-xs text-gray-500 mb-2">List each condition that must be true for this goal to count as done.</p>
                <div data-done-lines></div>
                <button type="button" data-add-done class="text-teal-700 text-sm">+ Add condition</button>
                <input type="hidden" name="${prefix}[definition_of_done]" data-done-hidden>
            </div>

            <div class="grid md:grid-cols-2 gap-4 border-t border-gray-200 pt-3">
                <div><label class="font-medium">Deliverables</label><p class="text-xs text-gray-500 mb-1">What will exist by the end of this semester?</p><textarea name="${prefix}[deliverables]" rows="3" placeholder="e.g. Updated syllabi for all first-year courses">${esc(g.deliverables)}</textarea></div>
                <div><label class="font-medium">Monthly milestones</label><p class="text-xs text-gray-500 mb-1">Roughly what happens each month?</p><textarea name="${prefix}[milestones]" rows="3" placeholder="e.g. Month 1: draft, Month 2: review, Month 3: publish">${esc(g.milestones)}</textarea></div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div><label class="font-medium">Proposed tasks</label><p class="text-xs text-gray-500 mb-1">Feeds into the later semester plan.</p><textarea name="${prefix}[proposed_tasks]" rows="3" placeholder="e.g. Assign course leads, schedule review workshop">${esc(g.proposed_tasks)}</textarea></div>
                <div><label class="font-medium">Notes on strategic target</label><p class="text-xs text-gray-500 mb-1">Optional — only needed if this differs from the Strategic Plan's target.</p><textarea name="${prefix}[target_note]" rows="3" placeholder="Explain any variance">${esc(g.target_note)}</textarea></div>
            </div>

            <div class="border-t border-gray-200 pt-3">
                <label class="font-medium">Supporting owners</label>
                <p class="text-xs text-gray-500 mb-2">Type a name to tag a supporting owner. Only tagged owners receive the approval notification.</p>
                <div class="relative border rounded-lg p-2" data-mention-box>
                    <div class="flex flex-wrap gap-1 mb-1" data-mention-chips></div>
                    <input type="text" placeholder="Type @ or a name…" data-mention-input autocomplete="off" class="w-full border-0 p-1 focus:ring-0">
                    <div class="absolute z-10 hidden w-full rounded-lg border bg-white shadow-lg mt-1 max-h-40 overflow-auto" data-mention-suggestions></div>
                </div>
            </div>
        </div>`;

        const context = document.createElement('tr');
        context.dataset.cmtContext = index;
        context.hidden = true;
        body.append(row,context);
        row.insertBefore(detail,row.lastElementChild);
        initDoneLines(detail, g.definition_of_done);
        initMentions(detail, prefix, g.supporting_owner_ids);
        const sections = [...detail.firstElementChild.children];
        const additionalCells = [];
        sections.filter(el => el.tagName === 'DIV').forEach(section => {
            if (section.classList.contains('grid')) additionalCells.push(...section.children);
            else additionalCells.push(section);
        });
        additionalCells.forEach(section => {
            const cell = document.createElement('td'); cell.className = 'align-top';
            cell.append(section); row.insertBefore(cell,row.lastElementChild);
        });
        detail.querySelectorAll('.grid').forEach(el => el.remove());
        let krCount=results.length;
        detail.querySelector('[data-add-result]').onclick=()=>detail.querySelector('[data-results]').insertAdjacentHTML('beforeend',resultHtml(prefix,krCount++));
        detail.onclick=e=>{if(e.target.closest('[data-remove-result]')) {const rows=detail.querySelectorAll('[data-results] tr');if(rows.length>1)e.target.closest('tr').remove();}};
        row.querySelector('[data-remove-goal]').onclick=()=>{if(confirm('Remove this goal from the draft?')){row.remove();context.remove();detail.remove();}};

        row.querySelector('[data-priority]').onchange=()=>{row.querySelector('[data-source]').innerHTML='<option value="">Choose strategic goal</option>'+options(sourceRows().filter(r=>(r.strategy.priority||'Other')===row.querySelector('[data-priority]').value),[],r=>r.strategy.goal||r.title);updateTargets();};
        row.querySelector('[data-source]').onchange=updateTargets;
        updateTargets();
    }
    function updateTargets() {
        const strategy=data.sources.find(s=>s.id===parent.value);
        const semester=period.value===''?-1:Number(period.value);
        body.querySelectorAll('[data-goal]').forEach(row=>{
            const source=sourceRows().find(r=>r.id===row.querySelector('[data-source]').value);
            row.querySelector('[data-target]').textContent=source ? (semester<0 ? 'Choose a semester to see the strategic target.' : source.strategy.targets?.[semester] || 'No target specified in the Strategic Plan for this semester.') : '';
        });
        if (data.departmentGoals) body.querySelectorAll('[data-goal]').forEach(row => {
            const context = body.querySelector(`[data-cmt-context="${row.dataset.goal}"]`);
            const sourceId = row.querySelector('[data-source]').value;
            const selected = strategy?.semesters[semester];
            context.hidden = !sourceId || !selected;
            const matches = (data.cmtContext || []).filter(g => g.strategy_id === parent.value && g.source_id === sourceId && selected && g.starts_on >= selected.starts_on && g.ends_on <= selected.ends_on);
            context.innerHTML = `<td colspan="13" class="bg-teal-50"><details><summary class="text-teal-800 cursor-pointer">CMT goals for this Strategic Goal · ${matches.length} approved</summary>${matches.length ? matches.map(g => `<div class="mt-3"><strong>${esc(g.title)}</strong><p class="text-xs text-gray-500">${esc(g.document_title)}</p><ul class="mt-1">${g.results.map(r => `<li>${esc(r.title)} — Target: ${esc(r.target ?? 'Not set')} ${esc(r.metric)}</li>`).join('')}</ul></div>`).join('') : '<p class="mt-2 text-sm text-gray-500">No approved CMT goal is linked to this Strategic Goal for the selected semester. You can still prepare your department goal.</p>'}</details></td>`;
        });
        const chosen=strategy?.semesters[semester];
        document.getElementById('cmt-period-hint').textContent=chosen ? `${chosen.starts_on} – ${chosen.ends_on} AD\n${chosen.bs}` : '';
    }
    function filterPeriods(initial=false) {
        const strategy=data.sources.find(s=>s.id===parent.value);
        let selected=initial?data.selectedSemester:'';
        if(initial && selected==null && data.selectedPeriod)selected=strategy?.semesters.findIndex(s=>s.starts_on<=data.selectedPeriod.starts_on && s.ends_on>=data.selectedPeriod.ends_on);
        period.innerHTML='<option value="">Choose a semester</option>'+(strategy?.semesters||[]).map((s,i)=>`<option value="${i}" ${String(selected)===String(i)?'selected':''}>${esc(s.label||'Semester '+(i+1))}</option>`).join('');
        period.disabled=!strategy;
    }
    parent.onchange=()=>{
        filterPeriods();body.querySelectorAll('[data-goal]').forEach(row=>{
            const priorities=[...new Set(sourceRows().map(r=>r.strategy.priority||'Other'))];
            row.querySelector('[data-priority]').innerHTML='<option value="">Choose priority</option>'+priorities.map(p=>`<option value="${esc(p)}">${esc(p)}</option>`).join('');
            row.querySelector('[data-source]').innerHTML='<option value="">Choose strategic goal</option>';
        });updateTargets();
    };
    const department=document.querySelector('[name=department_id]');
    if(department)department.onchange=()=>{body.querySelectorAll('[name$="[owner_id]"]').forEach(select=>{const selected=select.value;select.innerHTML='<option value="">Choose owner</option>'+options(data.users.filter(u=>u.department_id===department.value),selected,u=>u.name);});};
    period.onchange=updateTargets;
    document.getElementById('cmt-add').onclick=()=>addGoal();
    filterPeriods(true);(data.groups.length?data.groups:[{}]).forEach(addGoal);
})();

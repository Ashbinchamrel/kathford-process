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
    function addGoal(g = {}) {
        const index = count++, prefix = `cmt_groups[${index}]`, results = g.results?.length ? g.results : [{}];
        const row = document.createElement('tr'); row.dataset.goal = index;
        const selectedSource=sourceRows().find(r=>r.id===g.source_id);
        const priority=selectedSource?.strategy.priority??'';
        const priorities=[...new Set(sourceRows().map(r=>r.strategy.priority||'Other'))];
        row.innerHTML = `<td class="align-top"><select aria-label="Strategic priority" data-priority required><option value="">Choose priority</option>${priorities.map(p=>`<option ${p===priority?'selected':''} value="${esc(p)}">${esc(p)}</option>`).join('')}</select></td><td class="align-top"><input type="hidden" name="${prefix}[key]" value="${esc(g.key || crypto.randomUUID())}"><select aria-label="Primary strategic link" data-source name="${prefix}[source_id]" required><option value="">Choose strategic goal</option>${options(sourceRows().filter(r=>(r.strategy.priority||'Other')===priority), g.source_id, r => r.strategy.goal || r.title)}</select><p data-target class="text-xs whitespace-pre-line text-gray-500 mt-2"></p></td>
        <td class="align-top"><input aria-label="CMT semester goal" name="${prefix}[title]" value="${esc(g.title)}" required maxlength="200" placeholder="What should this semester achieve?"><button type="button" data-expand class="text-teal-700 mt-2" aria-expanded="true">Key results and details ▾</button></td>
        <td class="align-top"><select aria-label="Accountable owner" name="${prefix}[owner_id]"><option value="">Choose owner</option>${options(data.users,g.owner_id,r=>r.name)}</select></td>
        <td class="align-top"><input aria-label="Goal due date" type="date" name="${prefix}[due_on]" value="${esc(g.due_on)}"></td><td class="align-top"><button type="button" data-remove-goal class="text-red-600" aria-label="Remove goal">Remove</button></td>`;
        const detail = document.createElement('tr'); detail.dataset.details = index;
        const textField = (key,label,value) => `<div><label>${label}</label><textarea name="${prefix}[${key}]" rows="3">${esc(value)}</textarea></div>`;
        detail.innerHTML = `<td colspan="6" class="bg-gray-50"><div class="p-3 space-y-3"><table class="w-full"><thead><tr><th>Key result *</th><th>Metric / unit</th><th>Baseline</th><th>Target</th><th></th></tr></thead><tbody data-results>${results.map((kr,i)=>resultHtml(prefix,i,kr)).join('')}</tbody></table><button type="button" data-add-result class="text-teal-700">+ Add key result</button><p class="text-xs text-gray-500">Use separate results for each measure. For a completion milestone, use target 1 and describe the evidence in Definition of Done.</p><div class="grid md:grid-cols-2 gap-4">${textField('definition_of_done','Definition of Done · one condition per line',g.definition_of_done)}${textField('deliverables','Deliverables',g.deliverables)}${textField('milestones','Monthly milestones',g.milestones)}${textField('proposed_tasks','Proposed tasks',g.proposed_tasks)}${textField('target_note','Explanation of differences from strategic targets',g.target_note)}<div><label>Supporting owners</label><details class="border rounded-lg p-2"><summary class="text-teal-700">Select supporting owners</summary><div style="max-height:180px;overflow:auto">${data.users.map(u=>`<label class="flex gap-2 items-center py-1"><input style="width:auto" type="checkbox" name="${prefix}[supporting_owner_ids][]" value="${esc(u.id)}" ${(g.supporting_owner_ids||[]).includes(u.id)?'checked':''}>${esc(u.name)}</label>`).join('')}</div></details><p class="text-xs text-gray-500">Only checked supporting owners receive the approval notification.</p></div></div></div></td>`;
        detail.hidden=!!g.title;row.querySelector('[data-expand]').setAttribute('aria-expanded',String(!detail.hidden));
        body.append(row,detail);
        let krCount=results.length;
        detail.querySelector('[data-add-result]').onclick=()=>detail.querySelector('[data-results]').insertAdjacentHTML('beforeend',resultHtml(prefix,krCount++));
        detail.onclick=e=>{if(e.target.closest('[data-remove-result]')) {const rows=detail.querySelectorAll('[data-results] tr');if(rows.length>1)e.target.closest('tr').remove();}};
        row.querySelector('[data-remove-goal]').onclick=()=>{if(confirm('Remove this goal from the draft?')){row.remove();detail.remove();}};
        row.querySelector('[data-expand]').onclick=e=>{detail.hidden=!detail.hidden;e.target.setAttribute('aria-expanded',String(!detail.hidden));};
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
        document.getElementById('cmt-period-hint').textContent=strategy?.semesters[semester]?.bs||'';
    }
    function filterPeriods(initial=false) {
        const strategy=data.sources.find(s=>s.id===parent.value);
        let selected=initial?data.selectedSemester:'';
        if(initial && selected==null && data.selectedPeriod)selected=strategy?.semesters.findIndex(s=>s.starts_on<=data.selectedPeriod.starts_on && s.ends_on>=data.selectedPeriod.ends_on);
        period.innerHTML='<option value="">Choose a semester</option>'+(strategy?.semesters||[]).map((s,i)=>`<option value="${i}" ${String(selected)===String(i)?'selected':''}>${esc(s.label||'Semester '+(i+1))} · ${esc(s.starts_on)}–${esc(s.ends_on)} AD</option>`).join('');
        period.disabled=!strategy;
    }
    parent.onchange=()=>{
        filterPeriods();body.querySelectorAll('[data-goal]').forEach(row=>{
            const priorities=[...new Set(sourceRows().map(r=>r.strategy.priority||'Other'))];
            row.querySelector('[data-priority]').innerHTML='<option value="">Choose priority</option>'+priorities.map(p=>`<option value="${esc(p)}">${esc(p)}</option>`).join('');
            row.querySelector('[data-source]').innerHTML='<option value="">Choose strategic goal</option>';
        });updateTargets();
    };
    period.onchange=updateTargets;
    document.getElementById('cmt-add').onclick=()=>addGoal();
    filterPeriods(true);(data.groups.length?data.groups:[{}]).forEach(addGoal);
})();

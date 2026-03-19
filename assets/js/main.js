// assets/js/main.js

// ── Flash message auto-dismiss ────────────────────────────────────────
document.querySelectorAll('.flash').forEach(el => {
    setTimeout(() => el.style.opacity = '0', 4000);
    setTimeout(() => el.remove(), 4400);
    el.style.transition = 'opacity 0.4s';
});

// ── Expense Split Builder ─────────────────────────────────────────────
(function () {
    const splitSection = document.getElementById('split-section');
    if (!splitSection) return;

    const tabs       = document.querySelectorAll('.split-tab');
    const totalInput = document.getElementById('total_amount');
    const splitTypeInput = document.getElementById('split_type');
    const totalBar   = document.getElementById('split-total-bar');
    const rows       = document.querySelectorAll('.split-member-row');

    // Switch between equal / custom / percentage
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const mode = tab.dataset.mode;
            splitTypeInput.value = mode;
            updateSplitMode(mode);
        });
    });

    function updateSplitMode(mode) {
        const total = parseFloat(totalInput?.value) || 0;
        const count = rows.length;

        rows.forEach(row => {
            const input = row.querySelector('.split-input');
            const label = row.querySelector('.split-unit');

            if (mode === 'equal') {
                const share = count > 0 ? (total / count).toFixed(2) : '0.00';
                input.value    = share;
                input.readOnly = true;
                input.style.opacity = '0.6';
                if (label) label.textContent = '₹';
                input.name = input.name.replace('percentage', 'amount').replace(/\[.*?\]/, match => match);
            } else if (mode === 'custom') {
                input.readOnly  = false;
                input.style.opacity = '1';
                if (label) label.textContent = '₹';
                input.value = '';
            } else if (mode === 'percentage') {
                input.readOnly  = false;
                input.style.opacity = '1';
                if (label) label.textContent = '%';
                const pct = count > 0 ? (100 / count).toFixed(1) : '0';
                input.value = pct;
            }
        });
        updateTotalBar(mode);
    }

    function updateTotalBar(mode) {
        if (!totalBar) return;
        const total  = parseFloat(totalInput?.value) || 0;
        let   runSum = 0;

        rows.forEach(row => {
            const val = parseFloat(row.querySelector('.split-input').value) || 0;
            runSum += val;
        });

        if (mode === 'equal') {
            totalBar.textContent = `Auto-split equally among ${rows.length} members`;
            totalBar.className   = 'split-total-bar';
        } else if (mode === 'custom') {
            const diff = total - runSum;
            const ok   = Math.abs(diff) < 0.01;
            totalBar.textContent = ok
                ? `✓ Splits add up correctly (₹${runSum.toFixed(2)})`
                : `Total entered: ₹${runSum.toFixed(2)} — ${diff > 0 ? '₹' + diff.toFixed(2) + ' unassigned' : '₹' + Math.abs(diff).toFixed(2) + ' over total'}`;
            totalBar.className   = 'split-total-bar' + (ok ? '' : ' error');
        } else if (mode === 'percentage') {
            const ok = Math.abs(runSum - 100) < 0.1;
            totalBar.textContent = ok
                ? `✓ Percentages add up to 100%`
                : `Total: ${runSum.toFixed(1)}% — must equal 100%`;
            totalBar.className   = 'split-total-bar' + (ok ? '' : ' error');
        }
    }

    // Live update as user types
    rows.forEach(row => {
        row.querySelector('.split-input').addEventListener('input', () => {
            const activeTab = document.querySelector('.split-tab.active');
            updateTotalBar(activeTab ? activeTab.dataset.mode : 'equal');
        });
    });

    // Re-calculate equal splits when total changes
    if (totalInput) {
        totalInput.addEventListener('input', () => {
            const activeTab = document.querySelector('.split-tab.active');
            const mode = activeTab ? activeTab.dataset.mode : 'equal';
            if (mode === 'equal') updateSplitMode('equal');
            else updateTotalBar(mode);
        });
    }

    // Init
    const initialMode = splitTypeInput ? splitTypeInput.value : 'equal';
    const initTab = document.querySelector(`.split-tab[data-mode="${initialMode}"]`);
    if (initTab) initTab.click();
})();

// ── Archive toggle (show/hide archived rows) ──────────────────────────
(function () {
    const toggle = document.getElementById('show-archived');
    if (!toggle) return;
    toggle.addEventListener('change', () => {
        document.querySelectorAll('.row-archived').forEach(row => {
            row.style.display = toggle.checked ? '' : 'none';
        });
    });
    // Hide archived by default
    document.querySelectorAll('.row-archived').forEach(row => {
        row.style.display = 'none';
    });
})();

// ── Confirm prompts for archive/settle ───────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
        if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
});

// ── Chore status quick-update ─────────────────────────────────────────
document.querySelectorAll('.status-select').forEach(sel => {
    sel.addEventListener('change', function () {
        const form = this.closest('form');
        if (form) form.submit();
    });
});

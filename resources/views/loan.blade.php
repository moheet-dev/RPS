<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loan</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #fff; color: #000; margin: 0; padding: 24px; }
        .wrap { max-width: 800px; margin: 0 auto; }
        h1 { font-size: 22px; margin: 0 0 16px; }
        h2 { font-size: 17px; margin: 28px 0 10px; }
        form { margin-bottom: 8px; }
        label { display: block; margin: 10px 0 4px; font-size: 14px; }
        input, select { width: 100%; max-width: 320px; padding: 7px; font-size: 14px; border: 1px solid #999; background: #fff; box-sizing: border-box; }
        button { margin-top: 14px; padding: 8px 16px; font-size: 14px; border: 1px solid #000; background: #fff; cursor: pointer; }
        button:disabled { color: #999; border-color: #999; cursor: default; }
        .message { margin: 10px 0; font-size: 14px; }
        .error { color: #b00020; }
        .success { color: #006400; }
        .summary { border: 1px solid #ccc; padding: 12px; font-size: 14px; line-height: 1.7; }
        .cards { display: flex; flex-wrap: wrap; gap: 10px; }
        .card { border: 1px solid #ccc; padding: 10px 14px; min-width: 120px; font-size: 14px; }
        .card .no { color: #666; font-size: 12px; margin-bottom: 4px; }
        table { border-collapse: collapse; width: 100%; font-size: 14px; }
        th, td { border: 1px solid #ccc; padding: 7px 10px; text-align: left; }
        th { background: #f7f7f7; font-weight: normal; }
        .muted { color: #666; font-size: 14px; }
        hr { border: 0; border-top: 1px solid #ddd; margin: 28px 0 0; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Loan</h1>

    <!-- Create loan -->
    <form id="loan-form">
        <label for="amount">Loan amount</label>
        <input type="number" id="amount" min="1" step="1" required>

        <label for="rate">Annual interest rate (%)</label>
        <input type="number" id="rate" min="0.01" step="0.01" required>

        <label for="installments">Number of installments (months)</label>
        <input type="number" id="installments" min="1" step="1" required>

        <button type="submit" id="loan-submit">Create loan</button>
    </form>
    <div id="loan-message" class="message"></div>

    <!-- Loan details (shown once a loan is loaded) -->
    <div id="loan-section" style="display:none;">
        <hr>
        <h2 id="loan-title"></h2>
        <div class="summary" id="loan-summary"></div>

        <h2>EMI schedule</h2>
        <div class="cards" id="cards"></div>

        <h2>Part payment</h2>
        <form id="pp-form">
            <label for="pp-amount">Part payment amount</label>
            <input type="number" id="pp-amount" min="1" step="1" required>

            <label for="pp-option">Recalculation option</label>
            <select id="pp-option">
                <option value="reduce_emi">Reduce EMI</option>
                <option value="reduce_tenure">Reduce tenure</option>
            </select>

            <button type="submit" id="pp-submit">Make part payment</button>
        </form>
        <div id="pp-message" class="message"></div>

        <h2>Previous part payments</h2>
        <div id="pp-list"></div>
    </div>
</div>

@verbatim
<script>
    const API = '/api';
    let loanId = null;

    const $ = (id) => document.getElementById(id);
    const inr = (n) => '₹' + Number(n).toLocaleString('en-IN');
    const optionLabel = (v) => (v === 'reduce_tenure' ? 'Reduce tenure' : 'Reduce EMI');

    function setMessage(el, text, type) {
        el.textContent = text || '';
        el.className = 'message' + (type ? ' ' + type : '');
    }

    function errorText(data, fallback) {
        if (data && data.errors) {
            return Object.values(data.errors).flat().join(' ');
        }
        return (data && data.message) || fallback;
    }

    async function request(method, path, body) {
        const res = await fetch(API + path, {
            method,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: body ? JSON.stringify(body) : undefined,
        });
        let data = null;
        try { data = await res.json(); } catch (e) {}
        return { ok: res.ok, status: res.status, data };
    }

    function idFromPath() {
        const m = window.location.pathname.match(/\/loan\/(\d+)/);
        return m ? m[1] : null;
    }

    /* ---------- Render ---------- */

    function renderLoan(loan) {
        $('loan-title').textContent = 'Loan #' + loanId;

        const summary = $('loan-summary');
        summary.innerHTML = '';
        [
            ['Outstanding principal', inr(loan.principal_amount)],
            ['Annual interest rate', loan.annual_rate_of_interest + '%'],
            ['EMI', inr(loan.emi)],
            ['Installments remaining', loan.no_of_installments],
        ].forEach(([k, v]) => {
            const row = document.createElement('div');
            row.textContent = k + ': ' + v;
            summary.appendChild(row);
        });

        const cards = $('cards');
        cards.innerHTML = '';
        for (let i = 1; i <= loan.no_of_installments; i++) {
            const card = document.createElement('div');
            card.className = 'card';
            const no = document.createElement('div');
            no.className = 'no';
            no.textContent = 'Installment ' + i;
            const amt = document.createElement('div');
            amt.textContent = inr(loan.emi);
            card.appendChild(no);
            card.appendChild(amt);
            cards.appendChild(card);
        }
    }

    function renderPartPayments(list) {
        const box = $('pp-list');
        box.innerHTML = '';

        if (!list || list.length === 0) {
            const p = document.createElement('div');
            p.className = 'muted';
            p.textContent = 'No part payments yet.';
            box.appendChild(p);
            return;
        }

        const table = document.createElement('table');
        const head = document.createElement('tr');
        ['#', 'Amount', 'Option', 'Date'].forEach((h) => {
            const th = document.createElement('th');
            th.textContent = h;
            head.appendChild(th);
        });
        table.appendChild(head);

        list.forEach((pp, idx) => {
            const tr = document.createElement('tr');
            [
                idx + 1,
                inr(pp.amount),
                optionLabel(pp.recalculation_option),
                new Date(pp.created_at).toLocaleString(),
            ].forEach((val) => {
                const td = document.createElement('td');
                td.textContent = val;
                tr.appendChild(td);
            });
            table.appendChild(tr);
        });
        box.appendChild(table);
    }

    /* ---------- Load ---------- */

    async function loadLoan() {
        const [loanRes, ppRes] = await Promise.all([
            request('GET', '/loan/' + loanId),
            request('GET', '/part-payment/' + loanId),
        ]);

        if (!loanRes.ok) {
            $('loan-section').style.display = 'none';
            setMessage($('loan-message'), errorText(loanRes.data, 'Could not load loan.'), 'error');
            return;
        }

        $('loan-section').style.display = 'block';
        renderLoan(loanRes.data);
        renderPartPayments(ppRes.ok ? ppRes.data.part_payments : []);
    }

    /* ---------- Create loan ---------- */

    $('loan-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = $('loan-submit');
        btn.disabled = true;
        setMessage($('loan-message'), 'Creating loan...');

        const { ok, data } = await request('POST', '/loan', {
            amount: parseInt($('amount').value, 10),
            rate_of_interest: parseFloat($('rate').value),
            no_of_installments: parseInt($('installments').value, 10),
        });

        btn.disabled = false;

        if (!ok) {
            setMessage($('loan-message'), errorText(data, 'Could not create loan.'), 'error');
            return;
        }

        // The create response must include the new loan id
        const newId = data.id ?? data.loan_id ?? (data.loan && data.loan.id);
        if (!newId) {
            setMessage($('loan-message'), 'Loan created, but the response has no loan id to open it.', 'error');
            return;
        }

        loanId = String(newId);
        history.pushState({}, '', '/loan/' + loanId);
        $('loan-form').reset();
        setMessage($('loan-message'), data.message || 'Loan created successfully', 'success');
        $('pp-message').textContent = '';
        await loadLoan();
    });

    /* ---------- Part payment ---------- */

    $('pp-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = $('pp-submit');
        btn.disabled = true;
        setMessage($('pp-message'), 'Processing...');

        const { ok, data } = await request('POST', '/part-payment/' + loanId, {
            amount: parseInt($('pp-amount').value, 10),
            recalculation_option: $('pp-option').value,
        });

        btn.disabled = false;

        if (!ok) {
            setMessage($('pp-message'), errorText(data, 'Could not make part payment.'), 'error');
            return;
        }

        $('pp-form').reset();
        setMessage($('pp-message'), data.message || 'Part payment created successfully', 'success');
        await loadLoan(); // fetch updated loan info + part payment history
    });

    /* ---------- Routing ---------- */

    window.addEventListener('popstate', () => {
        loanId = idFromPath();
        if (loanId) {
            loadLoan();
        } else {
            $('loan-section').style.display = 'none';
        }
    });

    loanId = idFromPath();
    if (loanId) loadLoan();
</script>
@endverbatim
</body>
</html>
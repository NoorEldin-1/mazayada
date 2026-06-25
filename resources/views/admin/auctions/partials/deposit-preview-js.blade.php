{{-- Live participation-deposit preview shared by the create + edit forms.
     Reads the opening price (dinars) and the deposit percentage and renders the
     derived deposit amount into #deposit-preview. Purely informational — the
     server is the source of truth (AdminAuctionController derives deposit_amount). --}}
(function () {
    var openingEl = document.getElementById('opening_price');
    var percentEl = document.getElementById('deposit_percent');
    var previewEl = document.getElementById('deposit-preview');
    if (!openingEl || !percentEl || !previewEl) return;

    var prefix = previewEl.dataset.prefix || '';
    var currency = previewEl.dataset.currency || '';

    function fmt(n) {
        return n.toLocaleString('en-US', { maximumFractionDigits: 2 }).replace(/,/g, ' ');
    }

    // Build the value as the same .money markup the server emits: only the amount
    // is the bidi-isolated LTR token (.amt, via CSS) while the currency (.cur)
    // follows the page direction — so دج sits on the left of the number in Arabic.
    var labelEl = document.createElement('span');
    var valueEl = document.createElement('span');
    valueEl.className = 'money';
    var amtEl = document.createElement('span');
    amtEl.className = 'amt';
    var curEl = document.createElement('span');
    curEl.className = 'cur';
    curEl.textContent = currency;
    valueEl.appendChild(amtEl);
    valueEl.appendChild(document.createTextNode(' '));
    valueEl.appendChild(curEl);

    function render() {
        var opening = parseFloat(openingEl.value);
        var percent = parseFloat(percentEl.value);
        if (isNaN(opening) || isNaN(percent) || opening <= 0) {
            previewEl.textContent = '';
            return;
        }
        var deposit = Math.round(opening * percent) / 100;
        labelEl.textContent = prefix + ' ';
        amtEl.textContent = fmt(deposit);
        previewEl.textContent = '';
        previewEl.appendChild(labelEl);
        previewEl.appendChild(valueEl);
    }

    openingEl.addEventListener('input', render);
    percentEl.addEventListener('input', render);
    render();
})();

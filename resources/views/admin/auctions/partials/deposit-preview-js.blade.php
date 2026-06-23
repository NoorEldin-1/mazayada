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

    // Build the value node once (LTR + bidi-isolated, like the money helper)
    // so the number + currency never jumble against the RTL Arabic label.
    var labelEl = document.createElement('span');
    var valueEl = document.createElement('span');
    valueEl.setAttribute('dir', 'ltr');
    valueEl.style.unicodeBidi = 'isolate';
    valueEl.style.whiteSpace = 'nowrap';

    function render() {
        var opening = parseFloat(openingEl.value);
        var percent = parseFloat(percentEl.value);
        if (isNaN(opening) || isNaN(percent) || opening <= 0) {
            previewEl.textContent = '';
            return;
        }
        var deposit = Math.round(opening * percent) / 100;
        labelEl.textContent = prefix + ' ';
        valueEl.textContent = fmt(deposit) + ' ' + currency;
        previewEl.textContent = '';
        previewEl.appendChild(labelEl);
        previewEl.appendChild(valueEl);
    }

    openingEl.addEventListener('input', render);
    percentEl.addEventListener('input', render);
    render();
})();

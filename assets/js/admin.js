jQuery(document).ready(function($) {

    var currencySymbols = { USD: '$', EUR: '€', GBP: '£', BDT: '৳', INR: '₹', AUD: 'A$', CAD: 'C$' };

    function getSymbol() {
        var cur = $('#mim-admin-currency').val();
        return currencySymbols[cur] || '';
    }

    function recalculate() {
        var grand = 0;
        var sym = getSymbol();
        $('.mim-admin-item-row').each(function() {
            var qty   = parseFloat($(this).find('.mim-admin-item-qty').val())   || 0;
            var price = parseFloat($(this).find('.mim-admin-item-price').val()) || 0;
            var line  = qty * price;
            $(this).find('.mim-admin-line-total').text(sym + line.toFixed(2));
            grand += line;
        });
        $('#mim-admin-grand-total').text(sym + grand.toFixed(2));
    }

    // Live recalculate on change
    $(document).on('input change', '.mim-admin-item-qty, .mim-admin-item-price', recalculate);
    $('#mim-admin-currency').on('change', recalculate);

    // Add Item
    $('#mim-admin-add-item').on('click', function() {
        var index = $('.mim-admin-item-row').length;
        var row = '<tr class="mim-admin-item-row">' +
            '<td><input type="text" name="mim_items[' + index + '][name]" class="widefat mim-admin-item-name"></td>' +
            '<td><select name="mim_items[' + index + '][type]" class="mim-admin-item-type">' +
                '<option value="fixed">Fixed</option>' +
                '<option value="hourly">Hourly</option>' +
            '</select></td>' +
            '<td><input type="number" step="0.01" min="0" name="mim_items[' + index + '][qty]" value="1" class="small-text mim-admin-item-qty"></td>' +
            '<td><input type="number" step="0.01" min="0" name="mim_items[' + index + '][price]" value="0.00" class="small-text mim-admin-item-price"></td>' +
            '<td class="mim-admin-line-total">0.00</td>' +
            '<td><button type="button" class="button mim-admin-remove-item">✕</button></td>' +
        '</tr>';
        $('#mim-admin-items-table tbody').append(row);
        recalculate();
    });

    // Remove Item
    $(document).on('click', '.mim-admin-remove-item', function() {
        if ($('.mim-admin-item-row').length > 1) {
            $(this).closest('tr').remove();
            recalculate();
        } else {
            alert('You must have at least one item.');
        }
    });

    // Initial calc on page load
    recalculate();
});

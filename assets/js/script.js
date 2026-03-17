document.addEventListener('DOMContentLoaded', function() {
    
    // --- Create / Edit Form ---
    const invoiceForm = document.getElementById('mim-invoice-form');
    if ( invoiceForm ) {
        const itemsTable = document.getElementById('mim-items-table').getElementsByTagName('tbody')[0];
        const addItemBtn = document.getElementById('mim-add-item');
        const grandTotalEl = document.getElementById('mim-grand-total');

        // Minimal map for UI live-calc
        const currencySymbols = { USD: '$', EUR: '€', GBP: '£', BDT: '৳', INR: '₹', AUD: 'A$', CAD: 'C$' };

        // Function to recalculate totals
        function calculateTotals() {
            let grandTotal = 0;
            const rows = itemsTable.getElementsByClassName('mim-item-row');
            const currSelect = document.getElementById('mim-payment-currency');
            const sym = currSelect ? (currencySymbols[currSelect.value] || '') : '$';
            
            for ( let row of rows ) {
                const qty = parseFloat( row.querySelector('.mim-item-qty').value ) || 0;
                const price = parseFloat( row.querySelector('.mim-item-price').value ) || 0;
                const rowTotal = qty * price;
                row.querySelector('.mim-line-total').innerText = sym + rowTotal.toFixed(2);
                grandTotal += rowTotal;
            }
            grandTotalEl.innerText = sym + grandTotal.toFixed(2);
        }

        // Add item row event
        addItemBtn.addEventListener('click', function() {
            const index = itemsTable.children.length;
            const tr = document.createElement('tr');
            tr.className = 'mim-item-row';
            tr.innerHTML = `
                <td><input type="text" name="items[${index}][name]" class="mim-item-name" required></td>
                <td>
                    <select name="items[${index}][type]" class="mim-item-type">
                        <option value="fixed">Fixed</option>
                        <option value="hourly">Hourly</option>
                    </select>
                </td>
                <td><input type="number" min="0" step="0.01" name="items[${index}][qty]" value="1" class="mim-item-qty" required></td>
                <td><input type="number" min="0" step="0.01" name="items[${index}][price]" value="0.00" class="mim-item-price" required></td>
                <td class="mim-line-total">0.00</td>
                <td><button type="button" class="mim-btn-remove-item">X</button></td>
            `;
            itemsTable.appendChild(tr);
            calculateTotals();
        });

        // Event delegation for input changes and remove item clicks
        itemsTable.addEventListener('input', function(e) {
            if ( e.target.classList.contains('mim-item-qty') || e.target.classList.contains('mim-item-price') ) {
                calculateTotals();
            }
        });

        const currSelect = document.getElementById('mim-payment-currency');
        if ( currSelect ) {
            currSelect.addEventListener('change', calculateTotals);
        }

        itemsTable.addEventListener('click', function(e) {
            if ( e.target.classList.contains('mim-btn-remove-item') ) {
                if ( itemsTable.children.length > 1 ) {
                    e.target.closest('tr').remove();
                    calculateTotals();
                } else {
                    alert('You must have at least one item.');
                }
            }
        });

        // Initial calculate
        calculateTotals();

        // Submit Form via AJAX
        invoiceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('mim-submit-btn');
            const msgEl = document.getElementById('mim-form-message');
            
            submitBtn.disabled = true;
            submitBtn.innerText = 'Saving...';
            msgEl.style.display = 'none';

            const formData = new FormData(invoiceForm);
            formData.append('action', 'mim_save_invoice');
            formData.append('nonce', mim_ajax.nonce);

            fetch( mim_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then( response => response.json() )
            .then( data => {
                msgEl.style.display = 'inline-block';
                if ( data.success ) {
                    msgEl.style.color = '#46b450';
                    msgEl.innerText = data.data.message;
                    // If new invoice, maybe redirect or clear form
                    if ( ! formData.get('invoice_id') || formData.get('invoice_id') == '0' ) {
                        invoiceForm.reset();
                        calculateTotals();
                    }
                } else {
                    msgEl.style.color = '#dc3232';
                    msgEl.innerText = data.data.message || 'An error occurred.';
                }
            })
            .catch( err => {
                msgEl.style.display = 'inline-block';
                msgEl.style.color = '#dc3232';
                msgEl.innerText = 'Connection error.';
            })
            .finally( () => {
                submitBtn.disabled = false;
                submitBtn.innerText = formData.get('invoice_id') && formData.get('invoice_id') !== '0' ? 'Update Invoice' : 'Save Invoice';
            });
        });
    }

    // --- Dashboard ---
    
    // Status Dropdown Update
    const statusDropdowns = document.querySelectorAll('.mim-status-dropdown');
    statusDropdowns.forEach( dropdown => {
        dropdown.addEventListener('change', function() {
            const invoiceId = this.closest('tr').dataset.id;
            const newStatus = this.value;
            const msgEl = document.getElementById('mim-dashboard-message');

            const formData = new URLSearchParams();
            formData.append('action', 'mim_update_status');
            formData.append('nonce', mim_ajax.nonce);
            formData.append('invoice_id', invoiceId);
            formData.append('status', newStatus);

            fetch( mim_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then( response => response.json() )
            .then( data => {
                msgEl.style.display = 'block';
                if ( data.success ) {
                    msgEl.style.color = '#46b450';
                    msgEl.innerText = 'Status updated successfully.';
                    setTimeout( () => msgEl.style.display = 'none', 3000 );
                } else {
                    msgEl.style.color = '#dc3232';
                    msgEl.innerText = data.data.message || 'Failed to update status.';
                    setTimeout( () => location.reload(), 2000 );
                }
            });
        });
    });

    // Delete Invoice
    const deleteBtns = document.querySelectorAll('.mim-btn-delete');
    deleteBtns.forEach( btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if ( ! confirm('Are you sure you want to delete this invoice?') ) return;

            const invoiceId = this.dataset.id;
            const row = this.closest('tr');
            const msgEl = document.getElementById('mim-dashboard-message');

            const formData = new URLSearchParams();
            formData.append('action', 'mim_delete_invoice');
            formData.append('nonce', mim_ajax.nonce);
            formData.append('invoice_id', invoiceId);

            fetch( mim_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then( response => response.json() )
            .then( data => {
                if ( data.success ) {
                    row.remove();
                    msgEl.style.color = '#46b450';
                    msgEl.style.display = 'block';
                    msgEl.innerText = 'Invoice deleted.';
                    setTimeout( () => msgEl.style.display = 'none', 3000 );
                } else {
                    alert( data.data.message || 'Error deleting invoice.' );
                }
            });
        });
    });

});

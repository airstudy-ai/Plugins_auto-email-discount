jQuery(document).ready(function($) {
    // --- Domain Discounts Repeater (MODIFIED) ---
    let domainCounter = $('#aed-domain-discounts-repeater .aed-repeater-item').length;

    $('#aed-add-domain-rule').on('click', function() {
        var repeater = $('#aed-domain-discounts-repeater');
        // New: Added Admin Note input
        var newItem = `
            <div class="aed-repeater-item">
                <label>Domain:<br/>
                    <input type="text" name="aed_settings_adv[domain_discounts][${domainCounter}][domain]" placeholder="example.com" />
                </label>
                <label>Percentage (%):<br/>
                    <input type="number" name="aed_settings_adv[domain_discounts][${domainCounter}][percentage]" value="10" min="0" max="100" step="0.01" style="width: 70px;" />
                </label>
                <label>One-time:<br/>
                    <input type="checkbox" name="aed_settings_adv[domain_discounts][${domainCounter}][one_time]" value="1" />
                    One-time only
                </label>
                <label>Product IDs:<br/>
                    <input type="text" class="aed-product-ids-input" name="aed_settings_adv[domain_discounts][${domainCounter}][product_ids]" placeholder="e.g. 101, 105" />
                </label>
                <label>Admin Note:<br/>
                    <input type="text" class="aed-product-note-input" name="aed_settings_adv[domain_discounts][${domainCounter}][product_note]" placeholder="e.g. Intro Course" />
                </label>
                <button type="button" class="button aed-remove-rule-domain">Remove</button>
            </div>`;
        repeater.append(newItem);
        domainCounter++; 
    });

    // Domain remove button
    $('body').on('click', '.aed-remove-rule-domain', function() {
        $(this).closest('.aed-repeater-item').remove();
    });

    // --- Specific Email Discounts (MODIFIED for Table UI) ---
    let emailCounter = $('#aed-specific-email-discounts-tbody tr').length;
    var emailTableBody = $('#aed-specific-email-discounts-tbody');

    // MODIFIED: Add Specific Email Rule (Appends a <tr> to the table)
    $('#aed-add-specific-email-rule').on('click', function() {
        // New: Added Admin Note cell
        var newItem = `
            <tr>
                <td>
                    <input type="email" name="aed_settings_adv[specific_email_discounts][${emailCounter}][email]" placeholder="user@example.com" />
                </td>
                <td>
                    <input type="number" name="aed_settings_adv[specific_email_discounts][${emailCounter}][percentage]" value="10" min="0" max="100" step="0.01" />
                </td>
                <td>
                    <label>
                        <input type="checkbox" name="aed_settings_adv[specific_email_discounts][${emailCounter}][one_time]" value="1" />
                        Yes
                    </label>
                </td>
                <td>
                    <input type="text" class="aed-product-ids-input" name="aed_settings_adv[specific_email_discounts][${emailCounter}][product_ids]" placeholder="e.g. 101, 105" />
                </td>
                <td>
                    <input type="text" class="aed-product-note-input" name="aed_settings_adv[specific_email_discounts][${emailCounter}][product_note]" placeholder="e.g. Intro Course" />
                </td>
                <td>
                    <button type="button" class="button aed-remove-rule">Remove</button>
                </td>
            </tr>`;
        emailTableBody.append(newItem);
        emailCounter++; 
    });

    // MODIFIED: Remove Rule (Removes the <tr>)
    $('body').on('click', '.aed-remove-rule', function() {
        $(this).closest('tr').remove(); // Target <tr> for table
    });
    
    // *** MODIFIED: Live Search for Specific Emails Table (Expanded Search) ***
    $('#aed-email-search').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();

        emailTableBody.find('tr').each(function() {
            var row = $(this);
            
            // Get values from all searchable fields
            var emailVal = row.find('input[type="email"]').val().toLowerCase();
            var percentVal = row.find('input[type="number"]').val().toLowerCase();
            var productIdsVal = row.find('input.aed-product-ids-input').val().toLowerCase();
            var adminNoteVal = row.find('input.aed-product-note-input').val().toLowerCase();
            
            // Check if the search term is in ANY of the fields
            if (emailVal.includes(searchTerm) || 
                percentVal.includes(searchTerm) || 
                productIdsVal.includes(searchTerm) || 
                adminNoteVal.includes(searchTerm)) 
            {
                row.show();
            } else {
                row.hide();
            }
        });
    });
});
jQuery(document).ready(function($) {
    // --- Domain Discounts Repeater ---
    let domainCounter = $('#aed-domain-discounts-repeater .aed-repeater-item').length;

    $('#aed-add-domain-rule').on('click', function() {
        var repeater = $('#aed-domain-discounts-repeater');
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

    // --- Specific Email Discounts (Table UI) ---
    let emailCounter = $('#aed-specific-email-discounts-tbody tr').length;
    var emailTableBody = $('#aed-specific-email-discounts-tbody');

    // Add Specific Email Rule (Appends a <tr> to the table)
    $('#aed-add-specific-email-rule').on('click', function() {
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

    // Remove Rule (Removes the <tr>)
    $('body').on('click', '.aed-remove-rule', function() {
        $(this).closest('tr').remove(); // Target <tr> for table
    });
    
    // Live Search for Specific Emails Table
    $('#aed-email-search').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();

        emailTableBody.find('tr').each(function() {
            var row = $(this);
            var emailVal = row.find('input[type="email"]').val().toLowerCase();
            var percentVal = row.find('input[type="number"]').val().toLowerCase();
            var productIdsVal = row.find('input.aed-product-ids-input').val().toLowerCase();
            var adminNoteVal = row.find('input.aed-product-note-input').val().toLowerCase();

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

    // --- (MODIFIED v2.2.0) Product Category-Domain Discounts Repeater ---
    
    // Helper function to build category options
    function getCategoryOptions() {
        var categoryOptions = ''; // Placeholder option will be in the data-placeholder
        if (typeof aedData !== 'undefined' && aedData.productCategories) { 
            aedData.productCategories.forEach(function(category) { 
                categoryOptions += '<option value="' + category.id + '">' + category.name + '</option>';
            });
        }
        return categoryOptions;
    }

    let catDomainCounter = $('#aed-category-domain-discounts-repeater .aed-repeater-item').length;

    $('#aed-add-category-domain-rule').on('click', function() {
        var repeater = $('#aed-category-domain-discounts-repeater');
        var categoryOptions = getCategoryOptions();

        var newItem = `
            <div class="aed-repeater-item">
                <label>Product Categories:<br/> 
                    <select name="aed_settings_adv[category_domain_discounts][${catDomainCounter}][category_ids][]" multiple="multiple" 
                            class="wc-enhanced-select" data-placeholder="Select product categories...">
                        ${categoryOptions}
                    </select>
                    </label>
                <label>Allowed Domain:<br/>
                    <input type="text" name="aed_settings_adv[category_domain_discounts][${catDomainCounter}][domain]" placeholder="example.com" />
                </label>
                <label>Percentage (%):<br/>
                    <input type="number" name="aed_settings_adv[category_domain_discounts][${catDomainCounter}][percentage]" value="10" min="0" max="100" step="0.01" style="width: 70px;" />
                </label>
                <label>One-time:<br/>
                    <input type="checkbox" name="aed_settings_adv[category_domain_discounts][${catDomainCounter}][one_time]" value="1" />
                    One-time only
                </label>
                <label>Admin Note:<br/>
                    <input type="text" class="aed-category-note-input" name="aed_settings_adv[category_domain_discounts][${catDomainCounter}][category_note]" placeholder="e.g. Beginner Courses" style="width: 150px;" />
                </label>
                <button type="button" class="button aed-remove-rule-cat-domain">Remove</button>
            </div>`;
        repeater.append(newItem);
        catDomainCounter++;
        
        // --- (START) NEW v2.2.0: Initialize Select2 on new element ---
        $( document.body ).trigger( 'wc-enhanced-select-init' );
        // --- (END) NEW v2.2.0 ---
    });

    // Category-Domain remove button
    $('body').on('click', '.aed-remove-rule-cat-domain', function() {
        // --- (START) NEW v2.2.0: Destroy Select2 instance ---
        $(this).closest('.aed-repeater-item').find('.wc-enhanced-select').select2('destroy');
        // --- (END) NEW v2.2.0 ---
        $(this).closest('.aed-repeater-item').remove();
    });

    // --- (MODIFIED v2.2.0) Product Category-Specific Email Discounts (Table UI) ---
    let catEmailCounter = $('#aed-category-email-discounts-tbody tr').length;
    var catEmailTableBody = $('#aed-category-email-discounts-tbody');

    // Add Category-Email Rule
    $('#aed-add-category-email-rule').on('click', function() {
        var categoryOptions = getCategoryOptions();
        var newItem = `
            <tr>
                <td>
                    <select name="aed_settings_adv[category_email_discounts][${catEmailCounter}][category_ids][]" multiple="multiple" 
                            class="wc-enhanced-select" data-placeholder="Select product categories...">
                        ${categoryOptions}
                    </select>
                    </td>
                <td>
                    <input type="email" name="aed_settings_adv[category_email_discounts][${catEmailCounter}][email]" placeholder="user@example.com" />
                </td>
                <td>
                    <input type="number" name="aed_settings_adv[category_email_discounts][${catEmailCounter}][percentage]" value="10" min="0" max="100" step="0.01" />
                </td>
                <td>
                    <label>
                        <input type="checkbox" name="aed_settings_adv[category_email_discounts][${catEmailCounter}][one_time]" value="1" />
                        Yes
                    </label>
                </td>
                <td>
                    <input type="text" class="aed-category-note-input" name="aed_settings_adv[category_email_discounts][${catEmailCounter}][category_note]" placeholder="e.g. Intro Course" style="width: 100%;" />
                </td>
                <td>
                    <button type="button" class="button aed-remove-rule-cat-email">Remove</button>
                </td>
            </tr>`;
        catEmailTableBody.append(newItem);
        catEmailCounter++; 
        
        // --- (START) NEW v2.2.0: Initialize Select2 on new element ---
        $( document.body ).trigger( 'wc-enhanced-select-init' );
        // --- (END) NEW v2.2.0 ---
    });

    // Category-Email remove button
    $('body').on('click', '.aed-remove-rule-cat-email', function() {
        // --- (START) NEW v2.2.0: Destroy Select2 instance ---
        $(this).closest('tr').find('.wc-enhanced-select').select2('destroy');
        // --- (END) NEW v2.2.0 ---
        $(this).closest('tr').remove();
    });

    // Live Search for Category-Email Table
    // (This should still work as it reads the underlying select's value)
    $('#aed-category-email-search').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();

        catEmailTableBody.find('tr').each(function() {
            var row = $(this);

            // Get text from selected categories
            var categoryText = row.find('select option:selected').map(function() {
                return $(this).text();
            }).get().join(' ').toLowerCase();

            var emailVal = row.find('input[type="email"]').val().toLowerCase();
            var percentVal = row.find('input[type="number"]').val().toLowerCase();
            var adminNoteVal = row.find('input.aed-category-note-input').val().toLowerCase();

            if (categoryText.includes(searchTerm) ||
                emailVal.includes(searchTerm) ||
                percentVal.includes(searchTerm) ||
                adminNoteVal.includes(searchTerm))
            {
                row.show();
            } else {
                row.hide();
            }
        });
    });

    // --- (START) NEW v2.2.0: Initialize Select2 on existing elements on page load ---
    $( document.body ).trigger( 'wc-enhanced-select-init' );
    // --- (END) NEW v2.2.0 ---
});

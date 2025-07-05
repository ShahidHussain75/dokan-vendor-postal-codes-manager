jQuery(document).ready(function($) {
    // Cache DOM elements
    var $postalCodeSelect = $('select[name="vendor_postal_codes[]"]');
    var $priceContainer = $('#postal-code-prices');
    
    // Initialize Select2 only once with all options
    if ($postalCodeSelect.length) {
        $postalCodeSelect.select2({
            placeholder: 'Select delivery areas',
            allowClear: true,
            width: '100%',
            closeOnSelect: false,
            minimumInputLength: 1,
            language: {
                noResults: function() {
                    return "No postal codes found";
                },
                inputTooShort: function() {
                    return "Please enter at least 1 character";
                }
            },
            templateResult: formatPostalCode,
            templateSelection: formatPostalCode
        }).on('select2:selecting', function(e) {
            // Prevent the dropdown from closing when selecting
            e.preventDefault();
            var data = e.params.args.data;
            var $option = $(data.element);
            
            if (!$option.prop('selected')) {
                $option.prop('selected', true);
                $(this).trigger('change');
            }
            
            updatePriceField(data.id, data.text);
        }).on('select2:unselecting', function(e) {
            // Prevent the dropdown from closing when unselecting
            e.preventDefault();
            var data = e.params.args.data;
            var $option = $(data.element);
            
            if ($option.prop('selected')) {
                $option.prop('selected', false);
                $(this).trigger('change');
                removePriceField(data.id);
            }
        });

        // Initial load of price fields for pre-selected options
        $postalCodeSelect.find('option:selected').each(function() {
            updatePriceField($(this).val(), $(this).text());
        });
    }

    // Format postal code display
    function formatPostalCode(postalCode) {
        if (!postalCode.id) {
            return postalCode.text;
        }
        return $('<span>' + postalCode.text + '</span>');
    }

    // Update single price field
    function updatePriceField(code, text) {
        var existingField = $priceContainer.find('[data-postal-code="' + code + '"]');
        if (existingField.length === 0) {
            var priceValue = window.vendorPostalPrices && window.vendorPostalPrices[code] ? window.vendorPostalPrices[code] : '';
            var priceField = $(
                '<div class="postal-price-field" data-postal-code="' + code + '">' +
                '<label for="price_' + code + '">' + text + '</label>' +
                '<input type="number" step="0.01" min="0" id="price_' + code + '" ' +
                'name="vendor_postal_prices[' + code + ']" value="' + priceValue + '" ' +
                'class="dokan-form-control" placeholder="Enter delivery price">' +
                '</div>'
            );
            $priceContainer.append(priceField);
        }
    }

    // Remove single price field
    function removePriceField(code) {
        $priceContainer.find('[data-postal-code="' + code + '"]').remove();
    }

    // Handle form submission in vendor dashboard
    if (typeof dokan !== 'undefined' && dokan.ajaxurl) {
        $('.dokan-form-group').closest('form').on('submit', function() {
            var selectedPostalCodes = $postalCodeSelect.val();
            if (selectedPostalCodes) {
                if (!$('input[name="vendor_postal_codes_data"]').length) {
                    $(this).append('<input type="hidden" name="vendor_postal_codes_data">');
                }
                $('input[name="vendor_postal_codes_data"]').val(JSON.stringify(selectedPostalCodes));
            }
        });
    }

    // Admin area postal code search with debounce
    var searchTimeout;
    $('#postal_code, #area_name').on('keyup', function() {
        var $this = $(this);
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            var searchTerm = $this.val().toLowerCase();
            var searchField = $this.attr('id');
            
            $('.wp-list-table tbody tr').each(function() {
                var $row = $(this);
                var fieldContent = searchField === 'postal_code' 
                    ? $row.find('td:first').text().toLowerCase()
                    : $row.find('td:eq(1)').text().toLowerCase();
                
                $row.toggle(fieldContent.includes(searchTerm));
            });
        }, 300);
    });

    // Confirm delete action
    $('.button-link-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this postal code?')) {
            e.preventDefault();
        }
    });
}); 



jQuery(document).ready(function($) {
    function calculateTotal() {
        var total_hours = 0;
        var total_price = 0;
        var num_pages = parseInt($("#wcc-num-pages").val()) || 1;
        
        $(".wcc-option-checkbox:checked").each(function() {
            var base_hours = parseFloat($(this).data("hours")) || 0;
            var base_price = parseFloat($(this).data("price")) || 0;
            var multiply = $(this).data("multiply") == 1;
            var is_base_field = $(this).data("base-field") == 1;
            var additional_hours = parseFloat($(this).data("additional-hours")) || 0;
            var additional_price = parseFloat($(this).data("additional-price")) || 0;
            
            var hours = base_hours;
            var price = base_price;
            
            // Two different calculation methods:
            
            // Method 1: Multiply entire cost by number of pages
            // Used for options like "Each Unique Landing Page Design"
            if (multiply) {
                hours *= num_pages;
                price *= num_pages;
            }
            // Method 2: Base cost + additional per extra page (starting from page 2)
            // Used for options that scale incrementally
            else if (is_base_field && num_pages > 1) {
                var extra_pages = num_pages - 1;  // Only count pages beyond the first
                hours += (additional_hours * extra_pages);
                price += (additional_price * extra_pages);
            }
            
            total_hours += hours;
            total_price += price;
        });
        
        // Calculate project timeline
        var days = Math.ceil(total_hours / 24) + 10;
        var min_days = days;
        var max_days = days + 3;
        
        $("#wcc-total-hours").text(total_hours.toFixed(1));
        $("#wcc-total-price").text(total_price.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
        $("#wcc-timeline-days").text(min_days + " - " + max_days + " days");
    }
    
    function updateDisplayedValues() {
        var num_pages = parseInt($("#wcc-num-pages").val()) || 1;
        
        $(".wcc-option-row").each(function() {
            var $checkbox = $(this).find(".wcc-option-checkbox");
            var base_hours = parseFloat($checkbox.data("hours")) || 0;
            var base_price = parseFloat($checkbox.data("price")) || 0;
            var multiply = $checkbox.data("multiply") == 1;
            var is_base_field = $checkbox.data("base-field") == 1;
            var additional_hours = parseFloat($checkbox.data("additional-hours")) || 0;
            var additional_price = parseFloat($checkbox.data("additional-price")) || 0;
            
            var display_hours = base_hours;
            var display_price = base_price;
            
            // Method 1: Multiply by total pages
            if (multiply) {
                display_hours *= num_pages;
                display_price *= num_pages;
            }
            // Method 2: Base + additional per extra page (from page 2 onwards)
            else if (is_base_field && num_pages > 1) {
                var extra_pages = num_pages - 1;
                display_hours += (additional_hours * extra_pages);
                display_price += (additional_price * extra_pages);
            }
            
            $(this).find(".option-hours").text(display_hours.toFixed(1));
            $(this).find(".option-price").text("$" + display_price.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
        });
        
        calculateTotal();
    }
    
    function loadOptions(websiteType) {
        $("#wcc-options-container").html('<div class="wcc-loading">Loading options...</div>');
        
        $.ajax({
            url: wccAjax.ajaxurl,
            type: "POST",
            data: {
                action: "get_calculator_options",
                nonce: wccAjax.nonce,
                website_type: websiteType
            },
            success: function(response) {
                if (response.success && response.data.options) {
                    var options = response.data.options;
                    var html = "";
                    
                    $.each(options, function(index, option) {
                        var disabled = (!option.user_can_toggle && option.default_enabled) ? "disabled" : "";
                        var checked = option.default_enabled ? "checked" : "";
                        var yesNo = option.default_enabled ? "Yes" : "No";
                        
                        // Check if this option should multiply by pages
                        // (only for "landing page" named options)
                        var multiply = option.name.toLowerCase().indexOf("landing page") !== -1 ? "1" : "0";
                        
                        var is_base_field = option.is_base_field || 0;
                        var additional_hours = option.additional_hours || 0;
                        var additional_price = option.additional_price || 0;
                        
                        html += '<div class="wcc-option-row">';
                        html += '<div class="wcc-col-select">';
                        html += '<label class="wcc-toggle ' + disabled + '">';
                        html += '<input type="checkbox" class="wcc-option-checkbox" data-index="' + index + '" ';
                        html += 'data-hours="' + option.hours + '" data-price="' + option.price + '" ';
                        html += 'data-multiply="' + multiply + '" ';
                        html += 'data-base-field="' + is_base_field + '" ';
                        html += 'data-additional-hours="' + additional_hours + '" ';
                        html += 'data-additional-price="' + additional_price + '" ';
                        html += checked + ' ' + disabled + '>';
                        html += '<span class="wcc-toggle-slider"></span>';
                        html += '<span class="wcc-toggle-label">' + yesNo + '</span>';
                        html += '</label></div>';
                        html += '<div class="wcc-col-name">' + option.name + '</div>';
                        html += '<div class="wcc-col-hours option-hours">' + option.hours + '</div>';
                        html += '<div class="wcc-col-price option-price">$' + parseFloat(option.price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + '</div>';
                        html += '</div>';
                    });
                    
                    $("#wcc-options-container").html(html);
                    updateDisplayedValues();
                } else {
                    $("#wcc-options-container").html('<div class="wcc-loading">No options available for this website type.</div>');
                }
            },
            error: function() {
                $("#wcc-options-container").html('<div class="wcc-loading">Error loading options. Please try again.</div>');
            }
        });
    }
    
    $("#wcc-website-type").change(function() {
        var selectedType = $(this).val();
        $("#wcc-calculator-title").text(selectedType + " Website Quote Calculator");
        loadOptions(selectedType);
    });
    
    $(document).on("change", ".wcc-option-checkbox", function() {
        var label = $(this).closest(".wcc-toggle").find(".wcc-toggle-label");
        label.text($(this).is(":checked") ? "Yes" : "No");
        calculateTotal();
    });
    
    $(".wcc-increase").click(function() {
        var input = $("#wcc-num-pages");
        input.val(parseInt(input.val()) + 1);
        updateDisplayedValues();
    });
    
    $(".wcc-decrease").click(function() {
        var input = $("#wcc-num-pages");
        var val = parseInt(input.val());
        if (val > 1) {
            input.val(val - 1);
            updateDisplayedValues();
        }
    });
    
    $("#wcc-num-pages").on("input", function() {
        if ($(this).val() < 1) $(this).val(1);
        updateDisplayedValues();
    });
    
    // Initial calculation
    updateDisplayedValues();
});
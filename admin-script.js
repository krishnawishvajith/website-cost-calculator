jQuery(document).ready(function($) {
    var typeCounter = $(".website-type-row").length;
    
    // Toggle additional fields based on base field checkbox
    $(document).on("change", ".base-field-checkbox", function() {
        var $row = $(this).closest("tr");
        var isChecked = $(this).is(":checked");
        
        $row.find(".additional-field").prop("disabled", !isChecked);
        
        if (!isChecked) {
            $row.find(".additional-field").val(0);
        }
    });
    
    $("#add-website-type").click(function() {
        var newType = "New Type " + (typeCounter + 1);
        $("#website-types-container").append(
            '<div class="website-type-row"><input type="text" name="website_types[]" value="' + newType + '" placeholder="Website Type"><button type="button" class="button remove-type">Remove</button></div>'
        );
        
        // Add new tab
        var newIndex = typeCounter;
        $("#website-type-tabs").append(
            '<button type="button" class="wcc-tab-button" data-tab="tab-' + newIndex + '">' + newType + '</button>'
        );
        
        // Add new tab panel
        $("#website-type-tab-content").append(
            '<div class="wcc-tab-panel" id="tab-' + newIndex + '">' +
            '<h3>' + newType + ' Options</h3>' +
            '<input type="hidden" name="website_type_key[]" value="' + newType + '">' +
            '<div class="wcc-help-text">' +
            '<strong>Base Field:</strong> Check this if the option cost should increase per landing page. ' +
            '<br>Example: If pages = 2, total = Base Hours/Price + Additional Hours/Price' +
            '</div>' +
            '<table class="widefat wcc-options-table"><thead><tr>' +
            '<th style="width: 200px;">Option Name</th>' +
            '<th style="width: 80px;">Base Hours</th>' +
            '<th style="width: 100px;">Base Price ($)</th>' +
            '<th style="width: 80px;">Default On</th>' +
            '<th style="width: 80px;">User Toggle</th>' +
            '<th style="width: 80px;">Base Field</th>' +
            '<th style="width: 90px;">Add. Hours/Page</th>' +
            '<th style="width: 100px;">Add. Price/Page ($)</th>' +
            '<th style="width: 80px;">Action</th>' +
            '</tr></thead>' +
            '<tbody class="calculator-options-container" data-type-index="' + newIndex + '"></tbody></table>' +
            '<button type="button" class="button add-calculator-option" data-type-index="' + newIndex + '">Add Option</button>' +
            '</div>'
        );
        
        typeCounter++;
    });
    
    $(document).on("click", ".remove-type", function() {
        var index = $(this).parent().index();
        $(this).parent().remove();
        $(".wcc-tab-button").eq(index).remove();
        $(".wcc-tab-panel").eq(index).remove();
        
        // Activate first tab if active tab was removed
        if ($(".wcc-tab-button.active").length === 0) {
            $(".wcc-tab-button").first().addClass("active");
            $(".wcc-tab-panel").first().addClass("active");
        }
    });
    
    $(document).on("click", ".wcc-tab-button", function() {
        var tabId = $(this).data("tab");
        $(".wcc-tab-button").removeClass("active");
        $(this).addClass("active");
        $(".wcc-tab-panel").removeClass("active");
        $("#" + tabId).addClass("active");
    });
    
    $(document).on("input", ".website-type-row input", function() {
        var index = $(this).parent().index();
        var newName = $(this).val();
        $(".wcc-tab-button").eq(index).text(newName);
        $(".wcc-tab-panel").eq(index).find("h3").text(newName + " Options");
        $(".wcc-tab-panel").eq(index).find("input[name='website_type_key[]']").val(newName);
    });
    
    $(document).on("click", ".add-calculator-option", function() {
        var typeIndex = $(this).data("type-index");
        var container = $(".calculator-options-container[data-type-index='" + typeIndex + "']");
        var optIndex = container.find("tr").length;
        
        container.append(
            '<tr class="option-row">' +
            '<td><input type="text" name="option_name[' + typeIndex + '][]" class="regular-text" required></td>' +
            '<td><input type="number" name="option_hours[' + typeIndex + '][]" step="0.1" min="0" value="0" required></td>' +
            '<td><input type="number" name="option_price[' + typeIndex + '][]" step="0.01" min="0" value="0" required></td>' +
            '<td><input type="checkbox" name="option_default[' + typeIndex + '][' + optIndex + ']"></td>' +
            '<td><input type="checkbox" name="option_user_toggle[' + typeIndex + '][' + optIndex + ']"></td>' +
            '<td><input type="checkbox" class="base-field-checkbox" name="option_base_field[' + typeIndex + '][' + optIndex + ']"></td>' +
            '<td><input type="number" class="additional-field" name="option_additional_hours[' + typeIndex + '][]" step="0.1" min="0" value="0" disabled></td>' +
            '<td><input type="number" class="additional-field" name="option_additional_price[' + typeIndex + '][]" step="0.01" min="0" value="0" disabled></td>' +
            '<td><button type="button" class="button remove-option">Remove</button></td>' +
            '</tr>'
        );
    });
    
    $(document).on("click", ".remove-option", function() {
        $(this).closest("tr").remove();
    });
});
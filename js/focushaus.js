var focalPointUI = {
    isDragging: false,
    init: function() {
        focalPointUI.handleEvents();

        if (wp.media && wp.media.frame) {
            // Setup marker on media overlay panel, after it opens (WP adds image by AJAX to panel)
            wp.media.frame.on("edit:attachment", function() {
                focalPointUI.setupImage();
            });
            wp.media.frame.on("refresh", function() {
                focalPointUI.setupImage();
            });
        } else {
            // Setup image on a Edit Media page
            focalPointUI.setupImage();
        }
    },

    hasRequiredFields: function() {
        return jQuery(".focal-point").length;
    },

    // Bind the events to the mouse and input fields to update position in real time
    handleEvents: function() {
        // Abort if no ACF installed
        if (typeof acf == "undefined") {
            return false;
        }

        // Only add events to the body (this function should only be fired once from init() above)
        var $panel = jQuery("body.post-type-attachment");

        // Handle drag events
        $panel.on("mousedown", ".focal-point-indicator", function(e) {
            focalPointUI.isDragging = true;
            $panel.addClass("focal-point-dragging");
        });
        $panel.on("mouseup", ".marker-wrap", function(e) {
            focalPointUI.isDragging = false;
            $panel.removeClass("focal-point-dragging");
        });

        // Update marker on input change
        $panel.on("change", ".focal-point input", function(e) {
            focalPointUI.updateIndicator();
        });

        // Update postion indicator on mouse move
        $panel.on("mousemove", ".marker-wrap", function(e) {
            if (focalPointUI.isDragging) {
                // Update position of input fields
                focalPointUI.updateInputFields(e);
                jQuery(".focal-point input").trigger("change");
            }
        });

        // Handle left/right clicking of media navigation
        $panel.on("click", "button.left, button.right", function(e) {
            focalPointUI.setupImage();
        });
    },

    // Add a wraper around the img tag so that we can position the indicator correctly
    setupImage: function() {
        // Abort if no fields
        if (!focalPointUI.hasRequiredFields) return;

        // Abort if already run
        if (jQuery(".marker-wrap").length) return;

        //Add marker-wrap to image (either in media overlay or on edit media page)
        $image = jQuery(
            "#poststuff .wp_attachment_holder .thumbnail, .attachment-details .thumbnail > .details-image"
        ).not(".marker-wrap > img");
        $image.wrap('<div class="marker-wrap"></div>');

        // Add indicator to image
        jQuery(".marker-wrap").append(
            '<div class="focal-point-indicator"></div>'
        );

        // Fire off change event to update position of indicator
        jQuery(".focal-point input").trigger("change");
    },

    // Update the numbers in the input fields from mouse position
    updateInputFields: function(event) {
        // Abort if no fields
        if (!focalPointUI.hasRequiredFields) return;

        var img = jQuery(".marker-wrap > img").get(0);
        var posX = event.offsetX ? event.offsetX : event.pageX - img.offsetLeft;
        var posY = event.offsetY ? event.offsetY : event.pageY - img.offsetTop;

        // Figure out % position of mouse
        var percentX = (posX / img.width) * 100;
        var percentY = (posY / img.height) * 100;

        jQuery("#focal-point-x input").val(percentX.toFixed(1));
        jQuery("#focal-point-y input").val(percentY.toFixed(1));
    },

    // Update CSS on the indicator to put it in the right position over the image
    updateIndicator: function() {
        // Abort if no fields
        if (!focalPointUI.hasRequiredFields) return;

        // Get X/Y postion, default to center center
        var xPos = jQuery("#focal-point-x input").val() || 50;
        var yPos = jQuery("#focal-point-y input").val() || 50;

        jQuery(".marker-wrap .focal-point-indicator").css({
            left: xPos + "%",
            top: yPos + "%"
        });
    }
};
jQuery(window).load(function() {
    focalPointUI.init();
});

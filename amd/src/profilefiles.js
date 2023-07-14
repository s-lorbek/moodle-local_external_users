import jQuery from 'jquery';

export const append = (content) => {
    jQuery(document).ready(function() {
        if (jQuery('.profile_tree').length > 0) {
            jQuery('.profile_tree').append(content);
        }
    });
};
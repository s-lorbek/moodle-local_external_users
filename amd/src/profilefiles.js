import jQuery from 'jquery';

export const append = (content) => {
    jQuery(document).ready(function() {
        let profileTree = jQuery('.profile_tree');
        if (profileTree.length > 0) {
            profileTree.append(content);
        }
    });
};
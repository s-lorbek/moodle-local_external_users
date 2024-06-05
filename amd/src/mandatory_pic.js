import jQuery from 'jquery';

export const init = () => {
    jQuery(document).ready(function () {
        let profileTree = jQuery('#fitem_id_imagefile .form-label-addon a');
        profileTree.append(
            '<i class="icon fa fa-exclamation-circle text-danger fa-fw " title="Erforderlich" ' +
            'role="img" aria-label="Erforderlich"></i>'
        );
    });
};

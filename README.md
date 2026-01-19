# External Users Management (local_external_users)

This Moodle plugin provides a dedicated interface for managing external users who are undergoing an onboarding process. It allows administrators to review, approve, reject, or otherwise manage user registrations from external sources before they gain full access to the system.

## Key Features

* **Onboarding Workflow:** Streamline the process of vetting new users.
* **User Management:** Easily approve or reject pending external accounts.
* **Integration:** Works in conjunction with the `auth_external` plugin to provide a seamless registration and authentication flow.

## Installing via uploaded ZIP file

1. Log in to your Moodle site as an admin and go to *Site administration > Plugins > Install plugins*.
2. Upload the ZIP file with the plugin code. You should only be prompted to add extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually

The plugin can also be installed by putting the contents of this directory into:

`{your/moodle/dirroot}/local/external_users`

Afterwards, log in to your Moodle site as an admin and go to *Site administration > Notifications* to complete the installation.

Alternatively, you can run the following command from your CLI:

```bash
$ php admin/cli/upgrade.php

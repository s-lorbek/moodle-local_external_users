# External Users Management Plugin
## local_external_users

A comprehensive Moodle plugin for managing external user registrations with a complete onboarding and verification workflow. This plugin streamlines the process of vetting new users from external sources and controlling their access to the system.

**Version:** 2.1.0  
**Moodle Compatibility:** 4.0+  
**License:** GNU GPL v3 or later  
**Author:** Stephan Lorbek  

---

## Overview

The External Users Management plugin provides administrators with powerful tools to:

- **Review and manage** external user registrations before granting full access
- **Collect verification documents** (PDF documents, photos, etc.) from users during registration
- **Approve or reject** user applications with customizable messaging
- **Track verification status** with multiple approval tiers (full, limited-time, revoked)
- **Automate workflows** with scheduled validation tasks
- **Redirect unverified users** to the verification page automatically

This plugin is essential for institutions that require a vetting process for external users or partner organizations.

---

## Key Features

### User Management Dashboard
- **Pending Users:** View and manage users awaiting verification
- **Approved Users:** Track verified and limited-approval users
- **Rejected Users:** Review rejected applications with audit trail
- **Quick Actions:** Approve, reject, or modify user status directly from the dashboard

### Verification Workflow
- **Self-Service Portal:** External users can submit verification documents and photos
- **Document Collection:** Collect PDFs, images, and other required files
- **Admin Review:** Comprehensive profile view with all submitted documents
- **Flexible Approval:** Full approval, limited-time approval (configurable semesters), or rejection

###  Time-Based Access Control
- **Semester-Based Limits:** Automatically expire limited approvals at semester end
- **Configurable Dates:** Set custom term end dates or use automatic semester detection
- **Auto-Revocation:** Optional automatic revocation of expired accounts

### Notifications & Communication
- **Custom Messages:** Configure welcome, rejection, and verification emails
- **Admin Alerts:** Notify review team when new submissions arrive
- **User Feedback:** Send comments and reasons for rejection to users

### Security & Access Control
- **Role-Based Permissions:** Separate capabilities for verification and management
- **Audit Trail:** Track all user actions and approvals
- **Policy Integration:** Works seamlessly with Moodle's site policies
- **Session-Based Actions:** All critical actions require session key validation

### Automation
- **Scheduled Tasks:** Background jobs for semester validation and file cleanup
- **Auto-Redirect:** Automatically redirect unverified users to verification page
- **Comment Validation:** Periodic validation of user comments and status

---

## Installation

### Via ZIP Upload (Recommended)

1. Log in to your Moodle site as an administrator
2. Navigate to **Site administration > Plugins > Install plugins**
3. Upload the plugin ZIP file
4. Review the validation report
5. Complete the installation

### Manual Installation

1. Download the plugin files
2. Extract to: `{your/moodle/dirroot}/local/external_users`
3. Log in to your Moodle site as an administrator
4. Go to **Site administration > Notifications**
5. Follow the installation prompts

### Command Line Installation

```bash
cd /path/to/moodle
php admin/cli/upgrade.php
```

---

## Configuration

After installation, configure the plugin at:  
**Site administration > Plugins > Local plugins > External Users Management**

### Basic Settings

| Setting | Description | Default |
|---------|-------------|---------|
| **Show Document Field** | Enable document upload in verification form | Enabled |
| **Required Document** | Make document upload mandatory | Disabled |
| **Show Photo Field** | Enable photo upload in verification form | Enabled |
| **Required Photo** | Make photo upload mandatory | Disabled |
| **Remove Files on Approval** | Automatically delete submitted documents after approval | Disabled |

### Messaging Configuration

| Setting | Description |
|---------|-------------|
| **Signup Email Subject** | Subject line for registration confirmation emails |
| **Signup Email Message** | Body text for registration confirmation emails |
| **Verification Email Subject** | Subject for approval notification emails |
| **Verification Email Message** | Body text for approval notification emails |
| **Review Team Email** | Email address for admin notifications |
| **Rejection Comment Placeholder** | Text shown when explaining rejections to users |

### Advanced Settings

| Setting | Description |
|---------|-------------|
| **Onboarding Description** | HTML content displayed on the verification page |
| **End of Term Date** | Semester/term end date (e.g., `30.09.2024`) |
| **Affiliation Options** | Comma-separated list of user affiliations |
| **Price Categories** | Comma-separated list of pricing/access tiers |
| **Allow Browsing** | Allow unverified users limited browsing access |
| **Redirect Excludes** | URLs to exclude from auto-redirect (comma-separated) |
| **Discount URL** | Link to discount information (included in approval email) |

---

## User Roles and Permissions

### Verification Capability
- **local/external_users:verification**
- Allows users to submit their own verification documents
- Automatically granted to: Students, Teachers, Managers

### Management Capability
- **local/external_users:manage**
- Allows administrators to review and approve user applications
- Automatically granted to: Managers only

---

## Usage Guide

### For External Users

1. **Registration:** User registers through the external authentication method
2. **Verification Redirect:** User is automatically redirected to `/local/external_users/verify.php`
3. **Submit Documents:** User uploads required documents and photos
4. **Await Review:** Administrator reviews the submission
5. **Access Grant:** User receives approval notification and gains access

### For Administrators

1. **Dashboard:** Access `/local/external_users/manage.php` to see overview
2. **Pending Users:** Go to **Manage > Pending** to review waiting submissions
3. **Review Profile:** Click on user to view their complete profile and documents
4. **Take Action:**
   - **Approve:** Grant full immediate access
   - **Limited Approve:** Grant time-limited access (until semester end)
   - **Limited Approve 2:** Grant extended time-limited access (until next semester)
   - **Revoke:** Remove existing approval, return to pending state
   - **Reject:** Deny access and notify user

5. **View Results:** Check **Approved** and **Rejected** tabs to track history

---

## Database Tables

The plugin creates the following custom database tables:

- `local_external_users_files` - Stores references to uploaded verification documents

Custom user profile fields required:
- `external_user` - Boolean flag indicating external user status
- `external_user_verified` - Verification status (0=pending, 1=approved, -1=rejected, or date string for limited approval)
- `external_user_pending` - Flag for users awaiting review
- `external_user_comment` - Admin comments/reasons
- `external_user_affiliation` - User's organizational affiliation
- `eduPersonScopedAffiliation` - SAML/Shibboleth affiliation attribute

---

## Scheduled Tasks

The plugin includes automated background tasks:

### File Cleanup Task
- Removes orphaned verification documents
- Runs: Configurable (default: daily)
- Purpose: Maintain disk space and clean up old submissions

### Semester Validation Task
- Revokes limited approvals that have expired
- Runs: Configurable (default: daily)
- Purpose: Enforce time-based access restrictions

### Comment Validation Task
- Validates and updates user comment fields
- Runs: Configurable (default: daily)
- Purpose: Maintain data integrity

---

## Integration with Other Plugins

### auth_external (Required)
This plugin works exclusively with the `auth_external` authentication plugin to provide complete external user management. The authentication plugin handles login; this plugin manages the onboarding workflow.

### User Profile Fields
The plugin relies on custom user profile fields. Ensure these fields are created in:
**Site administration > Users > User profile fields**

### Shibboleth/SAML (Optional)
If using Shibboleth or SAML authentication, the plugin automatically captures the `eduPersonScopedAffiliation` attribute.

---

## Hook Events

The plugin triggers custom events for external user actions:

- `local_external_users\event\user_submit` - User submitted verification documents
- `local_external_users\event\user_approved` - User approved (full access)
- `local_external_users\event\user_limitedapproved` - User approved (limited time)
- `local_external_users\event\user_rejected` - User rejected
- `local_external_users\event\user_revoked` - User approval revoked
- `local_external_users\event\user_deactivated` - User deactivated
- `local_external_users\event\mail_failed` - Email delivery failed

These events can be monitored and acted upon by other plugins or custom hooks.

---

## Troubleshooting

### Users Not Redirected to Verification Page
- Check that user has `external_user` profile field set to 1
- Verify URL is not in **Redirect Excludes** list
- Ensure user is not already verified (`external_user_verified` = 1 or valid date)
- Check Moodle logs for hook execution errors

### Documents Not Uploading
- Verify **Show Document/Photo** settings are enabled in configuration
- Check file size is below `$CFG->maxbytes` limit
- Ensure context has necessary file system permissions
- Verify PDF validation passes for document uploads

### Email Notifications Not Sent
- Check that review team email is configured
- Verify Moodle's email settings and SMTP configuration
- Check admin notifications email address is valid
- Review Moodle error log for email delivery failures

### Limited Approvals Not Expiring
- Verify semester end date is set correctly (format: `d.m.Y`)
- Check that semester validation scheduled task is enabled and running
- Review task execution logs in **Site administration > Server > Tasks**

---

## Security Considerations

 **Important:** This plugin handles sensitive user information and documents.

- **Access Control:** Only managers can view and manage external user information
- **File Security:** Uploaded documents are stored in Moodle's secure file storage system
- **Session Protection:** All administrative actions require session key validation
- **Audit Trail:** All approvals and rejections are logged as events
- **GDPR Compliance:** Administrators can configure automatic file deletion after approval

---

## Support & Contributing

For bug reports, feature requests, or contributions:

- **Repository:** [GitHub - s-lorbek/moodle-local_external_users](https://github.com/s-lorbek/moodle-local_external_users)
- **Issues:** Report bugs and request features on GitHub
- **License:** GNU General Public License v3.0

---

## License

This plugin is licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html)

You are free to:
- Use the plugin in production
- Modify the code for your needs
- Distribute modified versions (under same license)
- Use commercially without providing source code modifications

---

## Changelog

### Version 2.1.0 (2026-02-13)
- Enhanced verification workflow
- Added semester-based approval limits
- Improved admin dashboard
- Better error handling and validation

### Version 2.0.0 (2022+)
- Initial stable release
- Complete onboarding workflow
- Document verification system

---

## Credits

**Author:** Stephan Lorbek <stephan.lorbek@uni-graz.at>  
**Institution:** University of Graz  
**Copyright:** © 2022-2026

---

**Last Updated:** February 2026

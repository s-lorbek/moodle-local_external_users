<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given;
use local_shopping_cart\local\cartstore;
use local_shopping_cart\shopping_cart;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

/**
 * Behat steps in plugin local_external_users
 *
 * @package    local_external_users
 * @category   test
 * @copyright  2025 Stephan Lorbek <stephan.lorbek@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_external_users extends behat_base {
    /**
     * @Given the :arg1 page is available
     */
    public function thePageIsAvailable($arg1) {
        throw new PendingException();
    }

    /**
     * @Given the following config values are set as:
     */
    public function theFollowingConfigValuesAreSetAs(TableNode $table) {
        throw new PendingException();
    }

    /**
     * @Given the :arg1 custom profile field data for :arg2 is set to :arg3
     */
    public function theCustomProfileFieldDataForIsSetTo($arg1, $arg2, $arg3) {
        throw new PendingException();
    }

    /**
     * @Then I should be on the :arg1 page
     */
    public function iShouldBeOnThePage($arg1) {
        throw new PendingException();
    }

    /**
     * Create custom profile fields from table.
     * @Given /^I create the following custom profile fields:$/
     */
    public function iCreateTheFollowingCustomProfileFields(TableNode $fieldsTable) {

        $rowdata = $fieldsTable->getRows();
        $headings = array_shift($rowdata); // Get column headings (shortname, datatype)

        // Ensure we have the expected headings.
        if (!in_array('shortname', $headings) || !in_array('datatype', $headings)) {
            throw new \Exception("Custom profile fields table must contain 'shortname' and 'datatype' columns.");
        }

        foreach ($rowdata as $row) {
            $data = array_combine($headings, $row);

            // Map column names to variables.
            $shortname = $data['shortname'];
            $datatype = $data['datatype'];

            // 1. Click the "Create new profile field" button/link for the specific datatype.
            $this->getSession()->getPage()->pressButton("Create a new profile field");

            $this->getSession()->getPage()->find('css', "a[data-datatype='$datatype']")->click();

            // 2. Fill in the field creation form.
            // The form fields often use standard IDs/names (e.g., 'shortname', 'name').
            $this->getSession()->getPage()->fillField('shortname', $shortname);

            // We use the shortname as the required 'Name' field if it's not provided.
            $this->getSession()->getPage()->fillField('Name', $shortname);

            // 3. Save the changes.
            $this->getSession()->getPage()->pressButton('Save changes');
            // Optional: Check if the field was created successfully (verify its presence in the table).
        }
    }

    /**
     * Set custom profile field values via direct DB (reliable).
     * @Given /^I set the following custom profile field values:$/
     */
    public function iSetTheFollowingCustomProfileFieldValues(TableNode $dataTable) {
        global $DB;

        $rows = $dataTable->getColumnsHash();

        foreach ($rows as $row) {
            // 1. Get the user once per row.
            $username = $row['username'];
            $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);

            // 2. Iterate through all columns except 'username'.
            foreach ($row as $fieldshortname => $value) {
                if ($fieldshortname === 'username') {
                    continue;
                }

                // 3. Find the custom field definition.
                $field = $DB->get_record('user_info_field', ['shortname' => $fieldshortname], '*', MUST_EXIST);

                // 4. Check if a value already exists for this user and field.
                $record = $DB->get_record('user_info_data', [
                    'userid'  => $user->id,
                    'fieldid' => $field->id,
                ]);

                $datarecord = (object)[
                    'userid'     => $user->id,
                    'fieldid'    => $field->id,
                    'data'       => (string)$value,
                    'dataformat' => 0,
                ];

                if ($record) {
                    $datarecord->id = $record->id;
                    $DB->update_record('user_info_data', $datarecord);
                } else {
                    $DB->insert_record('user_info_data', $datarecord);
                }
            }
        }
    }

    /**
     * @Given /^I wait for the filepicker modal to load$/
     */
    public function i_wait_for_the_filepicker_modal_to_load() {
        // Wait until the 'loading' div is hidden and the repository list is present
        $this->getSession()->wait(10000, "typeof M !== 'undefined' && M.core_filepicker && !document.querySelector('.filepicker-loading')");
    }
}

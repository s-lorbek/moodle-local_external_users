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

/**
 * Defines message providers (types of messages being sent)
 *
 * @package local_external_users
 * @author  2024 Stephan Lorbek <stephan.lorbek@uni-graz.at
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;

class behat_external_users extends behat_base {
    /**
     * @Given I am on the signup page
     */
    public function iamonthesignuppage() {
        $this->visitPath('/login/signup.php');
    }

    /**
     * @When I fill in :field with :value
     */
    public function ifillinfieldwithvalue($field, $value) {
        $this->fillField($field, $value);
    }

    /**
     * @When I press :button
     */
    public function ipress($button) {
        $this->pressButton($button);
    }

    /**
     * @Then I should see :text
     */
    public function ishouldsee($text) {
        $this->assertPageContainsText($text);
    }
}

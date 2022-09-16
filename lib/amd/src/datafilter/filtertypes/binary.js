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
 * Base filter for binary selector ie: (Yes / No).
 *
 * @module     core/datafilter/filtertypes/binary
 * @author     2022 Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Filter from 'core/datafilter/filtertype';
import Selectors from 'core/datafilter/selectors';
import Templates from 'core/templates';
import {get_strings as getStrings} from 'core/str';

const binaryOptions = {
    '0': 'optionone',
    '1': 'optiontwo'
};
export default class extends Filter {
    /**
     * Add the value selector to the filter row.
     *
     */
    async addValueSelector() {
        this.getTextValues().then(() => {
            this.displayBinarySelection();
        });
    }

    /**
     * Text values for select element.
     *
     * @returns {Promise}
     */
    getTextValues() {
        return getStrings([
            {key: 'no'},
            {key: 'yes'},
        ]).then((strings) => {
            this.optionOne = strings[0];
            this.optionTwo = strings[1];
        });
    }

    /**
     * Renders yes/no select input with proper selection.
     *
     */
    displayBinarySelection() {
        // We specify a specific filterset in case there are multiple filtering condition - avoiding glitches.
        const specificFilterSet = this.rootNode.querySelector(Selectors.filter.byName(this.filterType));
        const context = {filtertype: this.filterType, textvalueone: this.optionOne, textvaluetwo: this.optionTwo};
        // Default selection.
        context[binaryOptions[1]] = true;
        // Load any URL parameter.
        if (this.initialValues !== undefined) {
            context[binaryOptions[1]] = false;
            context[binaryOptions[this.initialValues[0]]] = true;
        }
        Templates.render('core/datafilter/filtertypes/binary_selector', context)
        .then((binaryUi, js) => {
            Templates.replaceNodeContents(specificFilterSet.querySelector(Selectors.filter.regions.values), binaryUi, js);
            return;
        }).fail();
    }

    /**
     * Get the list of raw values for this filter type.
     *
     * @returns {Array}
     */
    get values() {
        return this.filterRoot.querySelector(`[data-filterfield="${this.name}"]`).value;
    }

}

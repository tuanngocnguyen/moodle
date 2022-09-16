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
 * Range filter.
 *
 * @module     core/datafilter/filtertypes/range
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Filter from 'core/datafilter/filtertype';
import Selectors from 'core/datafilter/selectors';
import Templates from 'core/templates';
import {get_string as getString} from 'core/str';

const filterRangeOptions = {
    '0': 'optionone',
    '1': 'optiontwo',
    '2': 'optionthree'
};
export default class extends Filter {
    /**
     * Constructor for a new filter.
     *
     * @param {String} filterType The type of filter that this relates to
     * @param {HTMLElement} rootNode The root node for the participants filterset
     * @param {Array} initialValues The initial values for the selector
     * @param {Number} filterRange
     * @param {String} rangeUI range ui type
     */
    constructor(filterType, rootNode, initialValues, filterRange, rangeUI = 'text') {
        super(filterType, rootNode, initialValues);
        this.filterRange = filterRange;
        this.setUpRangeUi(rangeUI);
    }

    /**
     * Do nothing as we have custom set up for range ui
     *
     */
    async addValueSelector() {
        // eslint-disable-line no-empty-function
    }

    /**
     * Renders one or two input based on given context.
     *
     * @param {Object} context Context for mustache containing one or two placeholder.
     */
    async displayRange(context) {
        Templates.render('core/datafilter/filtertypes/range', context)
        .then((rangeUi, js) => {
            Templates.replaceNodeContents(this.filterRoot.querySelector(Selectors.filter.regions.values), rangeUi, js);
            return;
        }).fail();
    }

    /**
     * Adds listenner on filter range region.
     *
     * @param {string} type Type of input desired.
     */
    async rangeListenner(type) {
        const placeholderone = await this.placeholderOne;
        const placeholdertwo = await this.placeholderTwo;
        this.filterRoot.querySelector(Selectors.filter.fields.range).addEventListener('change', () => {
            const context = {
                placeholderone: placeholderone,
                type: type
            };
            if (this.rangetype === 2) {
                context.placeholdertwo = placeholdertwo;
            }
            this.displayRange(context);
        });
    }

    /**
     * Sets up base range UI.
     *
     * @param {string} type Type of input desired.
     */
    async setUpRangeUi(type) {
        const placeholderone = await this.placeholderOne;
        const placeholdertwo = await this.placeholderTwo;
        const context = {
            placeholderone: placeholderone,
            placeholdertwo: placeholdertwo,
            type: type
        };

        const filterRangeContext = {};
        // Default filter range value.
        filterRangeContext[filterRangeOptions[0]] = true;
        if (this.filterRange !== undefined) {
            filterRangeContext[filterRangeOptions[0]] = false;
            filterRangeContext[filterRangeOptions[this.filterRange]] = true;
        }
        // When url parameters loaded supplied - display setup accordingly.
        if (this.initialValues !== undefined) {
            context.initialvalueone = this.initialValues[0];
            // Do not display a second range value if initial value is not between.
            context.placeholdertwo = null;
            if (this.initialValues.length > 1) {
                context.initialvaluetwo = this.initialValues[1];
                // If multiple values are supplied - display two range inputs.
                context.placeholdertwo = placeholdertwo;
            }
        }
        Templates.render('core/datafilter/filtertypes/filter_range', filterRangeContext)
        .then((html, js) => {
            Templates.replaceNodeContents(this.filterRoot.querySelector(Selectors.filter.regions.range), html, js);
            this.displayRange(context)
            .then(() => {
                this.rangeListenner(type);
                return;
            }).catch();
            return;
        })
        .catch();
    }

    /**
     * Get the placeholder for range value one.
     *
     * @return {String} String
     */
    get placeholderOne() {
        return getString('rangestart', 'core_question');
    }

    /**
     * Get the placeholder for range value two.
     *
     * @return {String} String
     */
    get placeholderTwo() {
        return getString('rangeend', 'core_question');
    }

    /**
     * Get the type of range specified.
     *
     * @returns {Number}
     */
    get rangetype() {
        return parseInt(this.filterRoot.querySelector(Selectors.filter.fields.range).value, 10);
    }

    /**
     * Get the list of raw values for this filter type.
     *
     * @returns {Array}
     */
    get rawValues() {
        const rangeValue1 = document.getElementById('rangeValue1').value;
        const values = [rangeValue1];
        if (this.rangetype === 2) {
            const rangeValue2 = document.getElementById('rangeValue2').value;
            values.push(rangeValue2);
        }
        return values;
    }

    /**
     * Get the composed value for this filter.
     *
     * @returns {Object}
     */
    get filterValue() {
        return {
            name: this.name,
            jointype: this.jointype,
            rangetype: this.rangetype,
            values: this.rawValues,
            conditionclass: this.conditionclass,
        };
    }
}

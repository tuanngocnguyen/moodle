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
 * Contain the logic for the add random question modal.
 *
 * @module     mod_quiz/modal_add_random_question
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/notification',
    'core/modal',
    'core/modal_events',
    'core/modal_registry',
    'core/fragment',
    'core/templates',
    'core_form/changechecker',
    'core/ajax',
],
function(
    $,
    Notification,
    Modal,
    ModalEvents,
    ModalRegistry,
    Fragment,
    Templates,
    FormChangeChecker,
    ajax,
) {

    var registered = false;
    var SELECTORS = {
        EXISTING_CATEGORY_CONTAINER: '[data-region="existing-category-container"]',
        EXISTING_CATEGORY_TAB: '#id_existingcategoryheader',
        NEW_CATEGORY_CONTAINER: '[data-region="new-category-container"]',
        NEW_CATEGORY_TAB: '#id_newcategoryheader',
        TAB_CONTENT: '[data-region="tab-content"]',
        ADD_ON_PAGE_FORM_ELEMENT: '[name="addonpage"]',
        ADD_RANDOM_BUTTON: 'input[type="submit"][name="addrandom"]',
        ADD_NEW_CATEGORY_BUTTON: 'input[type="submit"][name="newcategory"]',
        SUBMIT_BUTTON_ELEMENT: 'input[type="submit"][name="addrandom"], input[type="submit"][name="newcategory"]',
        FORM_HEADER: 'legend',
        SELECT_NUMBER_TO_ADD: '#menurandomcount',
        NEW_CATEGORY_ELEMENT: '#categoryname',
        PARENT_CATEGORY_ELEMENT: '#parentcategory',
        FILTER_CONDITION_ELEMENT: '[data-filtercondition]',
        FORM_ELEMENT: '#add_random_question_form',
        MESSAGE_INPUT: '[name="message"]',
    };

    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var ModalAddRandomQuestion = function(root) {
        Modal.call(this, root);
        this.contextId = null;
        this.addOnPageId = null;
        this.category = null;
        this.returnUrl = null;
        this.cmid = null;
        this.loadedForm = false;
    };

    ModalAddRandomQuestion.TYPE = 'mod_quiz-quiz-add-random-question';
    ModalAddRandomQuestion.prototype = Object.create(Modal.prototype);
    ModalAddRandomQuestion.prototype.constructor = ModalAddRandomQuestion;

    /**
     * Save the Moodle context id that the question bank is being
     * rendered in.
     *
     * @method setContextId
     * @param {int} id
     */
    ModalAddRandomQuestion.prototype.setContextId = function(id) {
        this.contextId = id;
    };

    /**
     * Retrieve the saved Moodle context id.
     *
     * @method getContextId
     * @return {int}
     */
    ModalAddRandomQuestion.prototype.getContextId = function() {
        return this.contextId;
    };

    /**
     * Set the id of the page that the question should be added to
     * when the user clicks the add to quiz link.
     *
     * @method setAddOnPageId
     * @param {int} id
     */
    ModalAddRandomQuestion.prototype.setAddOnPageId = function(id) {
        this.addOnPageId = id;
        this.getBody().find(SELECTORS.ADD_ON_PAGE_FORM_ELEMENT).val(id);
    };

    /**
     * Returns the saved page id for the question to be added to.
     *
     * @method getAddOnPageId
     * @return {int}
     */
    ModalAddRandomQuestion.prototype.getAddOnPageId = function() {
        return this.addOnPageId;
    };

    /**
     * Set the category for this form. The category is a comma separated
     * category id and category context id.
     *
     * @method setCategory
     * @param {string} category
     */
    ModalAddRandomQuestion.prototype.setCategory = function(category) {
        this.category = category;
    };

    /**
     * Returns the saved category.
     *
     * @method getCategory
     * @return {string}
     */
    ModalAddRandomQuestion.prototype.getCategory = function() {
        return this.category;
    };

    /**
     * Set the return URL for the form.
     *
     * @method setReturnUrl
     * @param {string} url
     */
    ModalAddRandomQuestion.prototype.setReturnUrl = function(url) {
        this.returnUrl = url;
    };

    /**
     * Returns the return URL for the form.
     *
     * @method getReturnUrl
     * @return {string}
     */
    ModalAddRandomQuestion.prototype.getReturnUrl = function() {
        return this.returnUrl;
    };

    /**
     * Set the course module id for the form.
     *
     * @method setCMID
     * @param {int} id
     */
    ModalAddRandomQuestion.prototype.setCMID = function(id) {
        this.cmid = id;
    };

    /**
     * Returns the course module id for the form.
     *
     * @method getCMID
     * @return {int}
     */
    ModalAddRandomQuestion.prototype.getCMID = function() {
        return this.cmid;
    };

    /**
     * Moves a given form element inside (a child of) a given tab element.
     *
     * Hides the 'legend' (e.g. header) element of the form element because the
     * tab has the name.
     *
     * Moves the submit button into a footer element at the bottom of the form
     * element for styling purposes.
     *
     * @method moveContentIntoTab
     * @param  {jquery} tabContent The form element to move into the tab.
     * @param  {jquey} tabElement The tab element for the form element to move into.
     */
    ModalAddRandomQuestion.prototype.moveContentIntoTab = function(tabContent, tabElement) {
        // Hide the header because the tabs show us which part of the form we're
        // looking at.
        tabContent.find(SELECTORS.FORM_HEADER).addClass('hidden');
        // Move the element inside a tab.
        tabContent.wrap(tabElement);
    };

    /**
     * Empty the tab content container and move all tabs from the form into the
     * tab container element.
     *
     * @method moveTabsIntoTabContent
     * @param  {jquery} form The form element.
     */
    ModalAddRandomQuestion.prototype.moveTabsIntoTabContent = function(form) {
        // Empty it to remove the loading icon.
        var tabContent = this.getBody().find(SELECTORS.TAB_CONTENT).empty();
        // Make sure all tabs are inside the tab content element.
        form.find('[role="tabpanel"]').wrapAll(tabContent);
    };

    /**
     * Load the add random question form in a fragement and perform some transformation
     * on the HTML to convert it into tabs for rendering in the modal.
     *
     * @method loadForm
     * @return {promise} Resolved with form HTML and JS.
     */
    ModalAddRandomQuestion.prototype.loadForm = function() {
        const cmid = this.getCMID();
        const cat = this.getCategory();
        const addonpage = this.getAddOnPageId();
        const returnurl = this.getReturnUrl();

        return Fragment.loadFragment(
            'mod_quiz',
            'add_random_question_form',
            this.getContextId(),
            {
                // querystring: queryString,
                addonpage: addonpage,
                cat: cat,
                returnurl: returnurl,
                cmid: cmid
            }
        )
        .then(function(html, js) {
            var form = $(html);
            var existingCategoryTabContent = form.find(SELECTORS.EXISTING_CATEGORY_TAB);
            var existingCategoryTab = this.getBody().find(SELECTORS.EXISTING_CATEGORY_CONTAINER);
            var newCategoryTabContent = form.find(SELECTORS.NEW_CATEGORY_TAB);
            var newCategoryTab = this.getBody().find(SELECTORS.NEW_CATEGORY_CONTAINER);

            // Transform the form into tabs for better rendering in the modal.
            this.moveContentIntoTab(existingCategoryTabContent, existingCategoryTab);
            this.moveContentIntoTab(newCategoryTabContent, newCategoryTab);
            this.moveTabsIntoTabContent(form);

            Templates.replaceNode(this.getBody().find(SELECTORS.TAB_CONTENT), form, js);
            return;
        }.bind(this))
        .then(function() {
            // Make sure the form change checker is disabled otherwise it'll stop the user from navigating away from the
            // page once the modal is hidden.
            FormChangeChecker.disableAllChecks();

            // Select 'menunumbertoadd' element.
            const numbertoadd = document.querySelector(SELECTORS.SELECT_NUMBER_TO_ADD);
            // Submit buttons.
            const submitbuttons = document.querySelectorAll(SELECTORS.SUBMIT_BUTTON_ELEMENT);

            // Enable/Disable submit button.
            numbertoadd.addEventListener('change', (e) => {
                if (e.target.value != 0) {
                    // Enable submit button.
                    submitbuttons.forEach((button) => {
                        button.disabled = false;
                    });
                } else {
                    // Disable submit button.
                    submitbuttons.forEach((button) => {
                        button.disabled = true;
                    });
                }
            });

            // Add question to quiz.
            const modal = this;
            submitbuttons.forEach((button) => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();

                    const categoryid = cat.split(',')[0];
                    const randomcount = document.querySelector(SELECTORS.SELECT_NUMBER_TO_ADD).value;
                    const filtercondition = document.querySelector(SELECTORS.FILTER_CONDITION_ELEMENT).dataset?.filtercondition;

                    // Add Random questions.
                    let target = e.target.closest(SELECTORS.ADD_RANDOM_BUTTON);
                    if (target) {
                        modal.addQuestions(cmid, categoryid, addonpage, randomcount, filtercondition, '', '');
                        return;
                    }
                    // Add new category if required.
                    target = e.target.closest(SELECTORS.ADD_NEW_CATEGORY_BUTTON);
                    if (target) {
                        let newcategory = document.querySelector(SELECTORS.NEW_CATEGORY_ELEMENT).value;
                        let parentcategory = document.querySelector(SELECTORS.PARENT_CATEGORY_ELEMENT).value;
                        modal.addQuestions(cmid, categoryid, addonpage, randomcount, filtercondition,
                            newcategory, parentcategory);
                        return;
                    }
                });
            });
        }.bind(this))
        .fail(Notification.exception);
    };

    /**
     * Call web service function to add random questions
     *
     * @param {number} cmid course module id
     * @param {number} categoryid Question category
     * @param {number} addonpage the page where random questions will be added to
     * @param {number} randomcount Number of random questions
     * @param {string} filtercondition Filter condition
     * @param {string} newcategory add new category
     * @param {string} parentcategory parent category of new category
     */
    ModalAddRandomQuestion.prototype.addQuestions = function(cmid, categoryid, addonpage, randomcount, filtercondition,
                                                             newcategory, parentcategory) {
        const call = {
            methodname: 'mod_quiz_add_random_question',
            args: {
                cmid: cmid,
                categoryid: categoryid,
                addonpage: addonpage,
                randomcount: randomcount,
                filtercondition: filtercondition,
                newcategory: newcategory,
                parentcategory: parentcategory,
            }
        };
        ajax.call([call])[0]
            .then((response) => {
                const form = document.querySelector(SELECTORS.FORM_ELEMENT);
                const messageInput = form.querySelector(SELECTORS.MESSAGE_INPUT);
                messageInput.value = response.message;
                form.submit();
            })
            .catch(Notification.exception);
    };

    /**
     * Override the modal show function to load the form when this modal is first
     * shown.
     *
     * @method show
     */
    ModalAddRandomQuestion.prototype.show = function() {
        Modal.prototype.show.call(this);

        if (!this.loadedForm) {
            this.loadForm(window.location.search);
            this.loadedForm = true;
        }
    };

    // Automatically register with the modal registry the first time this module is
    // imported so that you can create modals of this type using the modal factory.
    if (!registered) {
        ModalRegistry.register(
            ModalAddRandomQuestion.TYPE,
            ModalAddRandomQuestion,
            'mod_quiz/modal_add_random_question'
        );

        registered = true;
    }

    return ModalAddRandomQuestion;
});

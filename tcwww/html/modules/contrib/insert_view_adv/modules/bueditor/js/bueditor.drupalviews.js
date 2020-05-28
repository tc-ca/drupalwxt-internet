/**
 * @file
 * Defines Views embed button for BUEditor.
 */

(function ($, Drupal, BUE) {
  'use strict';

  var views;

  /**
   * Extend the BUE object to add views support.
   */
  views = function(Editor) {
    var tokenDialog;
    var createTokenDialog;
    var createTokenForm;
    var processTokenField;
    var submitTokenForm;
    var getTokenObjectFromTokenForm;
    var getTokenStringFromTokenObject;

    /**
     * Create a token dialog.
     */
    createTokenDialog = function(fields) {
      var Dialog;

      Dialog = Editor.createDialog('view-token-dialog', null, null, {});
      Dialog.setTitle(BUE.t('View Embed Token'));
      Dialog.setContent(createTokenForm(fields));

      return Dialog;
    };

    /**
     * Create a token form.
     */
    createTokenForm = function(fields) {
      var options = {
        attributes: {
          'class': 'bue-token-form',
        },
        submit: submitTokenForm
      };

      // Prepare fields.
      fields = $.map(fields, processTokenField);

      return BUE.createDialogForm(fields, options);
    };

    /**
     * Processes a token editor field.
     *
     * We add a attr-name data attribute so that we know which fields to
     * process.
     */
    processTokenField = function(field) {
      field = BUE.processField(field);

      // Add attribute name.
      if (field.attributes['data-attr-name'] === undefined) {
        field.attributes['data-attr-name'] = field.name;
      }

      return field;
    };

    /**
     * Submits a token form.
     */
    submitTokenForm = function(form, Popup, Editor) {
      var tokenObject = getTokenObjectFromTokenForm(form);
      var tokenString = getTokenStringFromTokenObject(tokenObject);

      Editor.setSelection(tokenString);
    };

    /**
     * Builds and returns token object derived from field values in a token
     * form.
     */
    getTokenObjectFromTokenForm = function(form) {
      var tokenObject = {
        attributes: {}
      };

      for (let i = 0; i < form.elements.length; i++) {
        var formElement = form.elements[i];
        var attributeName = formElement.getAttribute('data-attr-name');

        if (attributeName !== null) {
          tokenObject.attributes[attributeName] = formElement.value || formElement.getAttribute('data-empty-value');
        }
      }

      return tokenObject;
    };

    /**
     * Builds token string from an token object or from the given token
     * arguments.
     */
    getTokenStringFromTokenObject = function(tokenObject) {
      var tokenAttributes = [];
      var tokenValue;
      var tokenString = '';

      if (typeof tokenObject === 'object') {
        for (let attributeName in tokenObject.attributes) {
          if (tokenObject.attributes[attributeName] !== null) {
            tokenAttributes.push(tokenObject.attributes[attributeName]);
          }
        }

        tokenValue = tokenAttributes.join('=');
        tokenString = '[view:' + tokenValue + ']';
      }

      return tokenString;
    };

    // Generate the dialog which opens the popup and inserts the token.
    tokenDialog = createTokenDialog(
      [
        {
          name: 'view',
          title: BUE.t('View id'),
          required: true
        },
        {
          name: 'display',
          title: BUE.t('Display id'),
          required: false
        },
        {
          name: 'args',
          title: BUE.t('Arguments'),
          required: false
        }
      ]
    );

    tokenDialog.open();
  };

  // Register buttons.
  BUE.registerButtons('bueditor.drupalviews', function() {
    return {
      drupalViews: {
        id: 'drupalviews',
        label: Drupal.t('Views'),
        text: 'Views',
        code: views
      }
    };
  });

})(jQuery, Drupal, BUE);

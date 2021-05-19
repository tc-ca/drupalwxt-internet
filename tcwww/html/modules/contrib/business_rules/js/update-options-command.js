(function ($, window, Drupal, drupalSettings) {

  'use strict';

  Drupal.AjaxCommands.prototype.updateOptionsCommand = function (ajax, response, status) {
    var elementId = response.elementId;
    var options = response.options;
    var formatter = response.formatter;

    if (formatter === 'select') {
      var element = $("select[id^=" + elementId + "]")[0];

      element.options.length = 0;
      for (var i = 0; i <= options.length; i++) {
        if (options.hasOwnProperty(i)) {
          element.options.add(new Option(options[i].value, options[i].key));
        }
      }
    } else {
      var element = document.getElementById(elementId);
      var fieldName = elementId.substr(5);
      if (element.className === 'form-radios') {
        var optionsArray = Array.prototype.slice.call(element.querySelectorAll('.form-radio'));
        var currentSelection = optionsArray
          .filter(radioButton => radioButton.checked)
          .map(radioButton => radioButton.value);
      } else if (element.className === 'form-checkboxes') {
        var optionsArray = Array.prototype.slice.call(element.querySelectorAll('.form-checkbox'));
        var currentSelection = optionsArray
          .filter(checkbox => checkbox.checked)
          .map(checkbox => checkbox.value);
      }
      // Remove the current options
      while (element.firstChild) {
        element.removeChild(element.firstChild);
      }
      if (formatter === 'radios') {
        for (var i = 0; i <= options.length; i++) {
          if (i !== 0) {
            if (options.hasOwnProperty(i)) {
              var div = document.createElement('div');
              div.setAttribute('class', 'js-form-item form-item js-form-type-radio form-type-radio js-form-item-' + fieldName + ' form-item-' + fieldName);
              var input = document.createElement('input');
              input.setAttribute('data-drupal-selector', elementId + '-' + options[i].key);
              input.setAttribute('type', 'radio');
              input.setAttribute('id', elementId + '-' + options[i].key);
              input.setAttribute('name', fieldName.replace(/[-]/g, '_'));
              input.setAttribute('value', options[i].key);
              input.setAttribute('class', 'form-radio');
              if (currentSelection.includes(options[i].key.toString())) {
                input.setAttribute('checked', 'checked');
              }
              var label = document.createElement('label');
              label.setAttribute('for', elementId + '-' + options[i].key);
              label.setAttribute('class', 'option');
              label.appendChild(document.createTextNode(options[i].value));
              div.appendChild(input);
              div.appendChild(document.createTextNode(' '));
              div.appendChild(label);
              element.appendChild(div);
            }
          }
        }
      } else if (formatter === 'checkboxes') {
        // Checkbox list.
        for (var i = 0; i <= options.length; i++) {
          if (i !== 0) {
            if (options.hasOwnProperty(i)) {
              var fieldNameOption = fieldName + '-' + options[i].key;
              var div = document.createElement('div');
              div.setAttribute('class', 'js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-' + fieldNameOption + ' form-item-' + fieldNameOption);
              var input = document.createElement('input');
              input.setAttribute('data-drupal-selector', elementId + '-' + options[i].key);
              input.setAttribute('type', 'checkbox');
              input.setAttribute('id', elementId + '-' + options[i].key);
              input.setAttribute('name', fieldName.replace(/[-]/g, '_') + '[' + options[i].key + ']');
              input.setAttribute('value', options[i].key);
              input.setAttribute('class', 'form-checkbox');
              if (currentSelection.includes(options[i].key.toString())) {
                input.setAttribute('checked', 'checked');
              }
              var label = document.createElement('label');
              label.setAttribute('for', elementId + '-' + options[i].key);
              label.setAttribute('class', 'option');
              label.appendChild(document.createTextNode(options[i].value));
              div.appendChild(input);
              div.appendChild(document.createTextNode(' '));
              div.appendChild(label);
              element.appendChild(div);
            }
          }
        }
      }
    }
    var event = new Event('change');
    element.dispatchEvent(event);
  };

})(jQuery, window, Drupal, drupalSettings);

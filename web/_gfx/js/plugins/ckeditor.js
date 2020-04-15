
(function ($, CKEDITOR) {
    CKEDITOR.plugins.add('kryptoscustomtags', {
        // This plugin requires the Widgets System defined in the 'widget' plugin.
        requires: 'widget',

        // The plugin initialization logic goes inside this method.
        init: function (editor) {
            // Configure CKEditor DTD for custom customblock element.
            // @see https://www.drupal.org/node/2448449#comment-9717735

            var dtd = CKEDITOR.dtd,
                pluginConfig = editor.config.kryptoscustomtags,
                tagName;

            dtd['customblock'] = {'#': 1};
            dtd.$object['customblock'] = 1;
            dtd.$cdata['customblock'] = 1;
            dtd.$block['customblock'] = 1;
            dtd.$nonEditable['customblock'] = 1;
            for (tagName in dtd) {
                if (dtd.hasOwnProperty(tagName)) {
                    if (dtd[tagName].div) {
                        dtd[tagName]['customblock'] = 1;
                    }
                }
            }

            dtd['custom-inline'] = {'#': 1};
            dtd.$object['custom-inline'] = 1;
            dtd.$cdata['custom-inline'] = 1;
            dtd.$inline['custom-inline'] = 1;
            dtd.$nonEditable['custom-inline'] = 1;
            for (tagName in dtd) {
                if (dtd.hasOwnProperty(tagName)) {
                    if (dtd[tagName].span) {
                        dtd[tagName]['custom-inline'] = 1;
                    }
                }
            }

            // Generic command for adding/editing entities of all types.
            0 && editor.addCommand('editkryptoscustomtag', {
                allowedContent: 'customblock[data-embed-button,data-entity-type,data-entity-uuid,data-entity-embed-display,data-entity-embed-settings,data-align,data-caption]',
                requiredContent: 'customblock[data-embed-button,data-entity-type,data-entity-uuid,data-entity-embed-display,data-entity-embed-settings,data-align,data-caption]',
                modes: { wysiwyg : 1 },
                canUndo: true,
                exec: function (editor, data) {
                    data = data || {};

                    var existingElement = getSelectedEmbeddedEntity(editor);

                    var existingValues = {};
                    if (existingElement && existingElement.$ && existingElement.$.firstChild) {
                        var embedDOMElement = existingElement.$.firstChild;
                        // Populate array with the entity's current attributes.
                        var attribute = null, attributeName;
                        for (var key = 0; key < embedDOMElement.attributes.length; key++) {
                            attribute = embedDOMElement.attributes.item(key);
                            attributeName = attribute.nodeName.toLowerCase();
                            if (attributeName.substring(0, 15) === 'data-cke-saved-') {
                                continue;
                            }
                            existingValues[attributeName] = existingElement.data('cke-saved-' + attributeName) || attribute.nodeValue;
                        }
                    }

                    var embed_button_id = data.id ? data.id : existingValues['data-embed-button'];

                    var dialogSettings = {
                        dialogClass: 'entity-select-dialog',
                        resizable: false
                    };

                    var saveCallback = function (values) {
                        var entityElement = editor.document.createElement('customblock');
                        var attributes = values.attributes;
                        for (var key in attributes) {
                            entityElement.setAttribute(key, attributes[key]);
                        }

                        editor.insertHtml(entityElement.getOuterHtml());
                        if (existingElement) {
                            // Detach the behaviors that were attached when the entity content
                            // was inserted.
                            // kryptos delete Drupal.runEmbedBehaviors('detach', existingElement.$);
                            existingElement.remove();
                        }
                    };

                    // Open the entity embed dialog for corresponding EmbedButton.
                    alert('Executing command');
                    // kryptos delete Drupal.ckeditor.openDialog(editor, Drupal.url('entity-embed/dialog/' + editor.config.drupal.format + '/' + embed_button_id), existingValues, saveCallback, dialogSettings);
                }
            });

            // Register the entity embed widget.
            editor.widgets.add('kryptoscustomtag', {
                // Minimum HTML which is required by this widget to work.
                allowedContent: 'div[data-type]',
                requiredContent: 'div[data-type]',

                // Simply recognize the element as our own. The inner markup if fetched
                // and inserted the init() callback, since it requires the actual DOM
                // element.
                upcast: function (element) {
                    if (!element.hasClass('cke-kryptoscustomtags-element-block')) {
                        return;
                    }
                    // Generate an ID for the element, so that we can use the Ajax
                    // framework.
                    element.attributes.id = generateEmbedId();
                    return element;
                },

                // Fetch the rendered entity.
                init: function () {
                    /** @type {CKEDITOR.dom.element} */
                    var element = this.element;

                    $.ajax({
                        url: pluginConfig.elements.blocks[element.getAttribute('data-type')].url,
                        success: function(result) {
                            element.$.innerHTML = result.html;
                        }
                    });
                },

                // Downcast the element.
                downcast: function (element) {
                    // Only keep the wrapping element.
                    element.setHtml('');
                    // Remove the auto-generated ID.
                    delete element.attributes.id;
                    return element;
                }
            });

            // Register the toolbar buttons.
            if (editor.ui.addButton) {
                editor.config.DrupalEntity_buttons = [{
                    id: 'testowy_przycisk',
                    label: 'testowy przycisk',
                    image: '/assets/img/logo-small.jpg'
                }];
                for (var key in editor.config.DrupalEntity_buttons) {
                    var button = editor.config.DrupalEntity_buttons[key];
                    editor.ui.addButton(button.id, {
                        label: button.label,
                        data: button,
                        allowedContent: 'customblock[!data-entity-type,!data-entity-uuid,!data-entity-embed-display,!data-entity-embed-settings,!data-align,!data-caption,!data-embed-button]',
                        click: function(editor) {
                            editor.execCommand('editdrupalentity', this.data);
                        },
                        icon: button.image
                    });
                }
            }

            editor.ui.addRichCombo(button.id, {
                label: 'Dodaj blok',
                title: 'Dodaj blok',
                allowedContent: 'customblock[!data-entity-type,!data-entity-uuid,!data-entity-embed-display,!data-entity-embed-settings,!data-align,!data-caption,!data-embed-button]',
                panel: {
                    css: [ CKEDITOR.skin.getPath( 'editor' ) ].concat( editor.config.contentsCss ),
                    multiSelect: false,
                    attributes: { 'aria-label': 'Dodaj blok' }
                },
                init: function() {
                    var i, elementConfig;

                    this.startGroup('Wybierz blok');

                    for (i in pluginConfig.elements.blocks) {
                        if (pluginConfig.elements.blocks.hasOwnProperty(i)) {
                            elementConfig = pluginConfig.elements.blocks[i];

                            this.add(elementConfig.name, elementConfig.label, elementConfig.name);
                        }
                    }
                },
                onClick: function( value ) {
                    editor.focus();
                    editor.fire('saveSnapshot');
                    var div = editor.document.createElement('div', {
                        attributes: {
                            'data-type': value,
                            class: 'cke-kryptoscustomtags-element-block',
                            height: 200,
                            contentEditable: false
                        }
                    });
                    editor.insertElement(div);
                    var _widget = editor.widgets.initOn( div, 'kryptoscustomtag' );

                }
            });

            // Register context menu option for editing widget.
            if (0 && editor.contextMenu) {
                editor.addMenuGroup('drupalentity');
                editor.addMenuItem('drupalentity', {
                    label: Drupal.t('Edit Entity'),
                    icon: this.path + 'entity.png',
                    command: 'editdrupalentity',
                    group: 'drupalentity'
                });

                editor.contextMenu.addListener(function(element) {
                    if (isEditableEntityWidget(editor, element)) {
                        return { drupalentity: CKEDITOR.TRISTATE_OFF };
                    }
                });
            }

            // Execute widget editing action on double click.
            editor.on('doubleclick', function (evt) {
                var element = getSelectedEmbeddedEntity(editor) || evt.data.element;

                if (isEditableEntityWidget(editor, element)) {
                    editor.execCommand('editdrupalentity');
                }
            });
        }
    });

    /**
     * Get the surrounding drupalentity widget element.
     *
     * @param {CKEDITOR.editor} editor
     */
    function getSelectedEmbeddedEntity(editor) {
        var selection = editor.getSelection();
        var selectedElement = selection.getSelectedElement();
        if (isEditableEntityWidget(editor, selectedElement)) {
            return selectedElement;
        }

        return null;
    }

    /**
     * Checks if the given element is an editable drupalentity widget.
     *
     * @param {CKEDITOR.editor} editor
     * @param {CKEDITOR.htmlParser.element} element
     */
    function isEditableEntityWidget (editor, element) {
        var widget = editor.widgets.getByElement(element, true);
        if (!widget || widget.name !== 'drupalentity') {
            return false;
        }

        var button = $(element.$.firstChild).attr('data-embed-button');
        if (!button) {
            // If there was no data-embed-button attribute, not editable.
            return false;
        }

        // The button itself must be valid.
        return editor.config.DrupalEntity_buttons.hasOwnProperty(button);
    }

    /**
     * Generates unique HTML IDs for the widgets.
     *
     * @returns {string}
     */
    function generateEmbedId() {
        if (typeof generateEmbedId.counter == 'undefined') {
            generateEmbedId.counter = 0;
        }
        return 'entity-embed-' + generateEmbedId.counter++;
    }

})(jQuery, CKEDITOR);

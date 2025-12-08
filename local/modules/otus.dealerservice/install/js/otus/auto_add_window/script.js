(function () {
    'use strict';
    BX.namespace('BX.AddAutoWindow');

    BX.AddAutoWindow = function (client, definedClassAir, currentUserId, gridId, autoData) {
        this.client = client;
        this.definedClassAir = definedClassAir;
        this.gridId = gridId;
        this.grid = null;
        this.popup = null;
        this.form = null;
        this.currentUserId = currentUserId;
        this.autoData = autoData || null;
        this.isEditMode = !!autoData;
    };

    BX.AddAutoWindow.prototype = {
        init: function () {
            this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
            
            var title = this.isEditMode 
                ? BX.message("TITLE_POPUP_EDIT") + (this.autoData.MAKE || '') 
                : BX.message("TITLE_POPUP_ADD") + this.client.name;
                
            var buttonText = this.isEditMode ? BX.message("TITLE_BTN_SAVE") : BX.message("TITLE_BTN_ADD");

            var autoId = this.isEditMode && this.autoData.ID ? this.autoData.ID : 0;
            var popupId = 'add_auto_window_' + autoId;

            var existingPopup = BX.PopupWindowManager.getPopupById(popupId);
            if (existingPopup) {
                existingPopup.close();
                existingPopup.destroy();
            }

            var self = this;

            this.popup = BX.PopupWindowManager.create(popupId, null, {
                content: this.createForm(),
                width: 500,
                overlay: true,
                closeByEsc: true,
                titleBar: title,
                events: {
                    onPopupClose: function() {
                        this.destroy();
                        self.popup = null;
                    }
                },
                buttons: [
                    new BX.PopupWindowButton({
                        text: buttonText,
                        className: 'ui-btn ui-btn-success ' + this.definedClassAir,
                        events: {
                            click: this.submitForm.bind(this)
                        }
                    }),
                    new BX.PopupWindowButton({
                        text: BX.message("TITLE_BTN_CLOSE"),
                        className: 'ui-btn ui-btn-light',
                        events: {
                            click: function () {
                                if (self.popup) {
                                    self.popup.close();
                                }
                            }
                        }
                    })
                ]
            });

            this.popup.show();
        },

        createForm: function() {
            this.form = BX.create('form', {
                props: { className: 'ui-form' },
                style: { padding: '15px' },
                attrs: {
                    id: 'car_form_' + (this.autoData ? this.autoData.ID : this.client.id)
                }
            });

            var fields = [
                { name: 'MAKE', placeholder: BX.message("TITLE_LBL_MAKE"), type: 'text', required: true },
                { name: 'MODEL', placeholder: BX.message("TITLE_LBL_MODEL"), type: 'text', required: true },
                { name: 'YEAR', placeholder: BX.message("TITLE_LBL_YEAR"), type: 'number', required: true },
                { name: 'NUMBER', placeholder: BX.message("TITLE_LBL_NUMBER"), type: 'text', required: true },
                { name: 'COLOR', placeholder: BX.message("TITLE_LBL_COLOR"), type: 'text', required: false },
                { name: 'MILEAGE', placeholder: BX.message("TITLE_LBL_MILEAGE"), type: 'number', required: false }
            ];

            fields.forEach(function(fieldConfig) {
                var fieldContainer = this.createField(fieldConfig);
                this.form.appendChild(fieldContainer);
            }.bind(this));

            this.form.appendChild(
                BX.create('input', {
                    attrs: {
                        type: 'hidden',
                        name: 'CLIENT_ID',
                        value: this.client.id
                    }
                })
            );

            if (this.isEditMode && this.autoData.ID) {
                this.form.appendChild(
                    BX.create('input', {
                        attrs: {
                            type: 'hidden',
                            name: 'ID',
                            value: this.autoData.ID
                        }
                    })
                );
            }

            this.form.appendChild(
                BX.create('input', {
                    attrs: {
                        type: 'hidden',
                        name: 'sessid',
                        value: BX.bitrix_sessid()
                    }
                })
            );

            return this.form;
        },

        createField: function(config) {
            var container = BX.create('div', {
                props: { className: 'ui-ctl-block' },
                style: { marginBottom: '15px', flexFlow: 'column'}
            });

            var label = BX.create('label', {
                props: { className: 'ui-ctl-label' },
                style: { 
                    display: 'block', 
                    marginBottom: '5px',
                    fontWeight: 'bold',
                    fontSize: '13px'
                },
                text: config.placeholder + (config.required ? ' *' : '')
            });

            var inputContainer = BX.create('div', {
                props: { className: 'ui-ctl ui-ctl-textbox ui-ctl-w100' }
            });

            var input = BX.create('input', {
                props: { className: 'ui-ctl-element' },
                attrs: {
                    type: config.type,
                    name: config.name,
                    placeholder: config.placeholder,
                    required: config.required
                },
                style: { width: '100%' }
            });

            if (this.isEditMode && this.autoData[config.name] !== undefined) {
                input.value = this.autoData[config.name];
            }

            if (config.type === 'number') {
                if (config.name === 'YEAR') {
                    input.setAttribute('min', '1900');
                    input.setAttribute('max', new Date().getFullYear() + 1);
                } else if (config.name === 'MILEAGE') {
                    input.setAttribute('min', '0');
                }
            }

            inputContainer.appendChild(input);
            container.appendChild(label);
            container.appendChild(inputContainer);

            if (config.required) {
                BX.bind(input, 'blur', function() {
                    this.validateField(input);
                }.bind(this));
            }

            return container;
        },

        validateField: function(input) {
            if (!input.value.trim()) {
                input.parentNode.classList.add('ui-ctl-danger');
                return false;
            } else {
                input.parentNode.classList.remove('ui-ctl-danger');
                return true;
            }
        },

        validateForm: function() {
            var inputs = this.form.querySelectorAll('input[required]');
            var valid = true;

            for (var i = 0; i < inputs.length; i++) {
                if (!this.validateField(inputs[i])) {
                    valid = false;
                }
            }

            return valid;
        },

        submitForm: function() {
            if (!this.validateForm()) {
                this.showError(BX.message("VALIDATION_ERROR"));
                return;
            }

            var addButton = this.popup.buttons[0];
            var originalText = addButton.textContent;

            var formData = new FormData(this.form);
            var data = {};
            
            for (var pair of formData.entries()) {
                data[pair[0]] = pair[1];
            }

            data.UPDATED_BY_ID = this.currentUserId;

            var action = this.isEditMode ? 'updateAuto' : 'addAuto';
            
            var self = this;

            BX.ajax.runComponentAction('otus.dealerservice:auto.list', action, {
                mode: 'class',
                data: {
                    params: data
                } 
            }).then(function(response) {
                addButton.textContent = originalText;
                addButton.disabled = false;

                if (response.data && response.data.success === true) {
                    if (self.popup) {
                        self.popup.close();
                    }
                    var successMessage = self.isEditMode 
                        ? BX.message("AUTO_SUCCESS_EDIT") 
                        : BX.message("AUTO_SUCCESS_ADD");
                    self.showSuccess(successMessage);
                    
                    if (self.grid) {
                        self.grid.reloadTable();   
                    }
                } else {
                    var errorMessage = self.isEditMode 
                        ? BX.message("ERROR_EDIT_AUTO") 
                        : BX.message("ERROR_ADD_AUTO");
                    
                    if (response.data && response.data.errors && response.data.errors.length > 0) {
                        errorMessage = response.data.errors.join(', ');
                    } else if (response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                    
                    self.showError(errorMessage);
                }
            }.bind(this)).catch(function(response) {
                addButton.textContent = originalText;
                addButton.disabled = false;

                var errorMessage = BX.message("ERROR_SERVER");
                if (response && response.errors) {
                    errorMessage = response.errors.join(', ');
                } else if (response && response.message) {
                    errorMessage = response.message;
                }
                
                self.showError(errorMessage);
            }.bind(this));
        },

        showSuccess: function(message) {
            if (BX.UI.Notification && BX.UI.Notification.Center) {
                BX.UI.Notification.Center.notify({
                    content: message,
                    autoHideDelay: 3000
                });
            } else {
                alert(message);
            }
        },

        showError: function(message) {
            if (BX.UI.Notification && BX.UI.Notification.Center) {
                BX.UI.Notification.Center.notify({
                    content: message,
                    autoHide: true,
                    autoHideDelay: 5000
                });
            } else {
                alert('Ошибка: ' + message);
            }
        }
    };

    BX.AddAutoWindow.edit = function(autoId, client, definedClassAir, currentUserId, gridId) {
        BX.UI.Notification.Center.notify({
            content: BX.message("NOTIFY_LOADED"),
            autoHideDelay: 1000
        });
        
        BX.ajax.runComponentAction('otus.dealerservice:auto.list', 'getAuto', {
            mode: 'class',
            data: {
                id: autoId
            }
        }).then(function(response) {
            if (response.data && response.data.success === true) {
                (new BX.AddAutoWindow(
                    client,
                    definedClassAir,
                    currentUserId,
                    gridId,
                    response.data.data
                )).init();
            } else {
                var errorMessage = BX.message("ERROR_LOADED_DATA");
                if (response.data && response.data.errors && response.data.errors.length > 0) {
                    errorMessage = response.data.errors.join(', ');
                }
                BX.UI.Notification.Center.notify({
                    content: errorMessage,
                    autoHide: true,
                    autoHideDelay: 5000
                });
            }
        }).catch(function(response) {
            BX.UI.Notification.Center.notify({
                content: BX.message("ERROR_LOADED_DATA"),
                autoHide: true,
                autoHideDelay: 5000
            });
        });
    };
})();
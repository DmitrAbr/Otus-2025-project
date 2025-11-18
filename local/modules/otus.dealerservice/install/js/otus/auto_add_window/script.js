(function () {
    'use strict';
    BX.namespace('BX.AddAutoWindow');

    BX.AddAutoWindow = function (client, definedClassAir) {
        this.client = client;
        this.definedClassAir = definedClassAir;
        this.popup = null;
        this.form = null;
    };

    BX.AddAutoWindow.prototype = {
        init: function () {
            this.popup = BX.PopupWindowManager.create('add_auto_window', null, {
                content: this.createForm(),
                width: 500,
                overlay: true,
                closeByEsc: true,
                titleBar: 'Добавление автомобиля для ' + this.client.name,
                buttons: [
                    new BX.PopupWindowButton({
                        text: 'Добавить',
                        className: 'ui-btn ui-btn-success',
                        events: {
                            click: this.submitForm.bind(this)
                        }
                    }),
                    new BX.PopupWindowButton({
                        text: 'Закрыть',
                        className: 'ui-btn ui-btn-light',
                        events: {
                            click: function () {
                                this.popup.close();
                            }.bind(this)
                        }
                    })
                ]
            });

            this.popup.show();
        },

        createForm: function() {
            // Создаем основную форму
            this.form = BX.create('form', {
                props: { className: 'ui-form' },
                style: { padding: '15px' },
                attrs: {
                    id: 'car_form_' + this.client.id
                }
            });

            // Массив полей формы
            var fields = [
                { name: 'mark', placeholder: 'Марка', type: 'text', required: true },
                { name: 'model', placeholder: 'Модель', type: 'text', required: true },
                { name: 'year', placeholder: 'Год выпуска', type: 'number', required: true },
                { name: 'number', placeholder: 'Номер', type: 'text', required: true },
                { name: 'color', placeholder: 'Цвет', type: 'text', required: false },
                { name: 'mileage', placeholder: 'Пробег', type: 'number', required: false }
            ];

            // Добавляем каждое поле в форму
            fields.forEach(function(fieldConfig) {
                var fieldContainer = this.createField(fieldConfig);
                this.form.appendChild(fieldContainer);
            }.bind(this));

            // Добавляем скрытые поля
            this.form.appendChild(
                BX.create('input', {
                    attrs: {
                        type: 'hidden',
                        name: 'client_id',
                        value: this.client.id
                    }
                })
            );

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
            // Создаем контейнер для поля
            var container = BX.create('div', {
                props: { className: 'ui-ctl-block' },
                style: { marginBottom: '15px', flexFlow: 'column'}
            });

            // Создаем лейбл
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

            // Создаем контейнер для инпута
            var inputContainer = BX.create('div', {
                props: { className: 'ui-ctl ui-ctl-textbox ui-ctl-w100' }
            });

            // Создаем сам инпут
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

            // Для числовых полей добавляем дополнительные атрибуты
            if (config.type === 'number') {
                if (config.name === 'year') {
                    input.setAttribute('min', '1900');
                    input.setAttribute('max', new Date().getFullYear() + 1);
                } else if (config.name === 'mileage') {
                    input.setAttribute('min', '0');
                }
            }

            // Собираем поле вместе
            inputContainer.appendChild(input);
            container.appendChild(label);
            container.appendChild(inputContainer);

            // Добавляем обработчик валидации
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
                this.showError('Заполните обязательные поля');
                return;
            }

            // Показываем загрузку на кнопке
            var addButton = this.popup.buttons[0];
            var originalText = addButton.textContent;
            addButton.textContent = "Добавление...";
            addButton.disabled = true;

            // Собираем данные формы в объект
            var formData = new FormData(this.form);
            var data = {};
            
            // Преобразуем FormData в обычный объект
            for (var pair of formData.entries()) {
                data[pair[0]] = pair[1];
            }

            // Отправляем AJAX-запрос
            BX.ajax.runComponentAction('otus.dealerservice:auto.list', 'addAuto', {
                mode: 'class',
                data: {
                    params: data
                }
            }).then(function(response) {
                if (response.data && response.data.success === true) {
                    this.popup.close();
                    this.showSuccess('Автомобиль успешно добавлен');
                    
                    // Обновляем грид если есть ссылка на класс
                    if (this.definedClassAir && typeof this.definedClassAir.reloadGrid === 'function') {
                        this.definedClassAir.reloadGrid();
                    }
                } else {
                    var errorMessage = response.data && response.data.message 
                        ? response.data.message 
                        : 'Ошибка при добавлении автомобиля';
                    this.showError(errorMessage);
                }
            }.bind(this)).catch(function(response) {
                this.showError('Ошибка сети или сервера');
            }.bind(this)).finally(function() {
                // Восстанавливаем кнопку в исходное состояние
                addButton.textContent = originalText;
                addButton.disabled = false;
            }.bind(this));
        },

        showSuccess: function(message) {
            if (BX.UI.Notification && BX.UI.Notification.Center) {
                BX.UI.Notification.Center.notify({
                    content: message,
                    autoHideDelay: 3000
                });
            } else {
                alert(message); // Fallback
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
                alert('Ошибка: ' + message); // Fallback
            }
        }
    };
})();
(function () {
    'use strict';

    BX.namespace('BX.AutoPopup');

    BX.AutoPopup = function (autoId, client) {
        this.autoId = autoId;
        this.client = client;
        this.deals = null;
        this.stages = null;
        this.autoData = null;
        this.popup = null;
        this.loadingDeals = false;
    };

    BX.AutoPopup.prototype = {
        init: function () {
            var self = this;
            
            var autoPromise = this.getAuto();
            var dealsPromise = this.getDeals();
            
            Promise.all([autoPromise, dealsPromise])
                .then(function() {
                    self.showAutoPopup();
                })
                .catch(function(error) {
                    self.showError('Ошибка при загрузке данных: ' + error);
                });
        },

        getAuto: function () {
            var self = this;
            
            return BX.ajax.runComponentAction('otus.dealerservice:auto.list', 'getAuto', {
                mode: 'class',
                data: {
                    id: this.autoId
                }
            }).then(function(response) {
                self.autoData = response.data.data;
                return response.data.data;
            }).catch(function(response) {
                var errorMessage = 'Ошибка при загрузке данных об автомобиле';
                if (response && response.errors) {
                    errorMessage = response.errors.join(', ');
                } else if (response && response.message) {
                    errorMessage = response.message;
                }
                
                self.showError(errorMessage);
                throw new Error(errorMessage);
            });
        },

        getDeals: function() {
            var self = this;
            self.loadingDeals = true;
            
            return BX.ajax.runComponentAction('otus.dealerservice:auto.list', 'getAutoDeals', {
                mode: 'class',
                data: {
                    id: this.autoId
                }
            }).then(function(response) {
                self.deals = response.data.data.deals || [];
                self.stages = response.data.data.stages || {}; 
                self.loadingDeals = false;
                return {
                    deals: self.deals,
                    stages: self.stages
                };
            }).catch(function(response) {
                self.loadingDeals = false;
                var errorMessage = 'Ошибка при загрузке сделок';
                if (response && response.errors) {
                    errorMessage = response.errors.join(', ');
                } else if (response && response.message) {
                    errorMessage = response.message;
                }
                
                self.showError(errorMessage);
                throw new Error(errorMessage);
            });
        },

        // Остальной код остается без изменений...
        showAutoPopup: function() {
            var self = this;
            var popupId = 'auto-popup-' + this.autoId;
            
            if (this.popup) {
                this.popup.close();
            }
            
            this.popup = BX.PopupWindowManager.create(popupId, null, {
                content: this.createAutoContent(),
                width: 800,
                overlay: true,
                closeByEsc: true,
                titleBar: 'Информация об автомобиле',
                className: 'auto-popup-window',
                events: {
                    onPopupClose: function() {
                        this.destroy();
                        self.popup = null;
                    }
                },
                buttons: [
                    new BX.PopupWindowButton({
                        text: 'Закрыть',
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
        
        createAutoContent: function() {
            if (!this.autoData) {
                return BX.create('div', {
                    props: {
                        className: 'auto-popup-error'
                    },
                    text: 'Данные об автомобиле не загружены'
                });
            }
            
            var data = this.autoData;
            var container = BX.create('div', {
                props: {
                    className: 'auto-popup-container'
                },
                children: [
                    this.createHeader(data),
                    this.createContentGrid(data)
                ]
            });
            
            return container;
        },
        
        createHeader: function(data) {
            return BX.create('div', {
                props: {
                    className: 'auto-popup-header'
                },
                children: [
                    BX.create('h3', {
                        props: {
                            className: 'auto-popup-title'
                        },
                        text: data.MAKE + ' ' + data.MODEL + ' - ' + data.NUMBER + ' (' + this.client.name + ')'
                    }),
                    BX.create('div', {
                        props: {
                            className: 'auto-popup-status'
                        },
                        children: [
                            BX.create('span', {
                                props: {
                                    className: 'auto-popup-status-badge auto-popup-status-' + (data.STATUS ? data.STATUS.toLowerCase() : '')
                                },
                                text: this.getStatusText(data.STATUS)
                            })
                        ]
                    })
                ]
            });
        },
        
        createContentGrid: function(data) {
            return BX.create('div', {
                props: {
                    className: 'auto-popup-grid'
                },
                children: [
                    BX.create('div', {
                        props: {
                            className: 'auto-popup-column auto-popup-auto-info'
                        },
                        children: [
                            this.createInfoRow('Год выпуска', data.YEAR || '—'),
                            this.createInfoRow('Цвет', data.COLOR || '—'),
                            this.createInfoRow('Пробег', data.MILEAGE ? this.formatMileage(data.MILEAGE) : '—'),
                            this.createInfoRow('ID', data.ID || '—'),
                            this.createInfoRow('Статус', this.getStatusText(data.STATUS) || '—')
                        ]
                    }),
                    
                    BX.create('div', {
                        props: {
                            className: 'auto-popup-column auto-popup-deals'
                        },
                        children: [
                            BX.create('h4', {
                                props: {
                                    className: 'auto-popup-deals-title'
                                },
                                text: 'Связанные сделки'
                            }),
                            this.createDealsContent()
                        ]
                    })
                ]
            });
        },
        
        createDealsContent: function() {
            if (this.loadingDeals) {
                return BX.create('div', {
                    props: {
                        className: 'auto-popup-loading'
                    },
                    text: 'Загрузка сделок...'
                });
            }
            
            if (!this.deals || this.deals.length === 0) {
                return BX.create('div', {
                    props: {
                        className: 'auto-popup-no-deals'
                    },
                    text: 'Сделок не найдено'
                });
            }
            
            return BX.create('div', {
                props: {
                    className: 'auto-popup-deals-list'
                },
                children: this.deals.map(function(deal, index) {
                    return this.createDealItem(deal, index);
                }.bind(this))
            });
        },
        
        createDealItem: function(deal, index) {
            var self = this;
            
            return BX.create('div', {
                props: {
                    className: 'auto-popup-deal-item'
                },
                children: [
                    BX.create('div', {
                        props: {
                            className: 'auto-popup-deal-header'
                        },
                        children: [
                            BX.create('a', {
                                attrs: {
                                    href: '/crm/deal/details/' + deal.ID + '/',
                                    target: '_blank',
                                    title: 'Открыть сделку'
                                },
                                props: {
                                    className: 'auto-popup-deal-title-link'
                                },
                                children: [
                                    BX.create('span', {
                                        props: {
                                            className: 'auto-popup-deal-title'
                                        },
                                        text: deal.TITLE || 'Сделка без названия'
                                    })
                                ]
                            }),
                            BX.create('span', {
                                text: self.getDealStageText(deal.STAGE_ID)
                            })
                        ]
                    }),
                    
                    BX.create('div', {
                        props: {
                            className: 'auto-popup-deal-info'
                        },
                        children: [
                            this.createDealInfoRow('Дата создания', deal.DATE_CREATE ? this.formatDate(deal.DATE_CREATE) : '—'),
                            this.createDealInfoRow('Сумма', deal.OPPORTUNITY ? this.formatCurrency(deal.OPPORTUNITY, deal.CURRENCY_ID) : '—'),
                            this.createDealInfoRow('Ответственный', deal.ASSIGNED_BY_ID ? this.createUserLink(deal.ASSIGNED_BY_ID, deal.ASSIGNED_BY_FULL_NAME) : '—')
                        ]
                    }),
                    
                    this.createDealProducts(deal.PRODUCTS || [])
                ]
            });
        },
        
        createDealInfoRow: function(label, value) {
            if (value instanceof HTMLElement || value instanceof Text) {
                return BX.create('div', {
                    props: {
                        className: 'auto-popup-deal-info-row'
                    },
                    children: [
                        BX.create('span', {
                            props: {
                                className: 'auto-popup-deal-info-label'
                            },
                            text: label + ': '
                        }),
                        BX.create('span', {
                            props: {
                                className: 'auto-popup-deal-info-value'
                            },
                            children: [value]
                        })
                    ]
                });
            } else {
                return BX.create('div', {
                    props: {
                        className: 'auto-popup-deal-info-row'
                    },
                    children: [
                        BX.create('span', {
                            props: {
                                className: 'auto-popup-deal-info-label'
                            },
                            text: label + ': '
                        }),
                        BX.create('span', {
                            props: {
                                className: 'auto-popup-deal-info-value'
                            },
                            text: value
                        })
                    ]
                });
            }
        },
        
        createUserLink: function(userId, fullName) {
            var link = BX.create('a', {
                attrs: {
                    href: '/company/personal/user/' + userId + '/',
                    target: '_blank',
                    title: 'Профиль пользователя'
                },
                props: {
                    className: 'auto-popup-user-link'
                },
                text: fullName
            });
            
            return link;
        },
        
        createDealProducts: function(products) {
            if (!products || !Array.isArray(products) || products.length === 0) {
                return null;
            }
            
            return BX.create('div', {
                props: {
                    className: 'auto-popup-deal-products'
                },
                children: [
                    BX.create('div', {
                        props: {
                            className: 'auto-popup-deal-products-title'
                        },
                        text: 'Товары:'
                    }),
                    BX.create('ul', {
                        props: {
                            className: 'auto-popup-deal-products-list'
                        },
                        children: products.map(function(product) {
                            return BX.create('li', {
                                props: {
                                    className: 'auto-popup-deal-product'
                                },
                                children: [
                                    BX.create('span', {
                                        props: {
                                            className: 'auto-popup-deal-product-name'
                                        },
                                        text: product.NAME || 'Без названия'
                                    }),
                                    product.QUANTITY ? BX.create('span', {
                                        props: {
                                            className: 'auto-popup-deal-product-quantity'
                                        },
                                        text: ' × ' + parseFloat(product.QUANTITY)
                                    }) : null
                                ].filter(function(item) { return item !== null; })
                            });
                        }.bind(this))
                    })
                ]
            });
        },
        
        getDealStageText: function(stageId) {
            console.log(stageId);
            console.log(this.stages);
            if (!stageId || !this.stages) {
                return stageId || '—';
            }
            
            var stageName = this.stages[stageId];
            
            if (!stageName && stageId.includes(':')) {
                var shortStageId = stageId.split(':')[1];
                stageName = this.stages[shortStageId] || this.stages[stageId.toUpperCase()];
            }
            
            return stageName || stageId;
        },

        formatCurrency: function(amount, currencyId) {
            var currencySymbols = {
                'RUB': '₽',
                'USD': '$',
                'EUR': '€',
                'UAH': '₴',
                'BYN': 'Br'
            };
            
            var symbol = currencySymbols[currencyId] || currencyId || '₽';
            return parseFloat(amount).toLocaleString('ru-RU') + ' ' + symbol;
        },

        createInfoRow: function(label, value) {
            return BX.create('div', {
                props: {
                    className: 'auto-popup-info-row'
                },
                children: [
                    BX.create('div', {
                        props: {
                            className: 'auto-popup-info-label'
                        },
                        text: label + ':'
                    }),
                    BX.create('div', {
                        props: {
                            className: 'auto-popup-info-value'
                        },
                        text: value
                    })
                ]
            });
        },
        
        formatMileage: function(mileage) {
            return mileage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' км';
        },
        
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        
        getStatusText: function(status) {
            var statusMap = {
                'REJECTED': 'Отклонен',
                'DONE': 'Выполнен',
                'IN_WORK': 'В работе',
                'NEW': 'Новый',
            };
            
            return statusMap[status] || status;
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
})();
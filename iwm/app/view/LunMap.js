/*
 * File: app/view/LunMap.js
 *
 * This file was generated by Sencha Architect version 2.0.0.
 * http://www.sencha.com/products/architect/
 *
 * This file requires use of the Ext JS 4.0.x library, under independent license.
 * License of Sencha Architect does not include license for Ext JS 4.0.x. For more
 * details see http://www.sencha.com/license or contact license@sencha.com.
 *
 * This file will be auto-generated each and everytime you save your project.
 *
 * Do NOT hand edit this file.
 */

Ext.define('MyApp.view.LunMap', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.lunmap',
    requires: [
        'MyApp.view.IpField',
        'MyApp.view.NetMaskField',
        'MyApp.view.TargetListField',
        'MyApp.view.DataBind'
    ],

    layout: {
        type: 'border'
    },
    title: 'LunMap',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'gridpanel',
                    itemId: 'mappinglist',
                    hideCollapseTool: false,
                    overlapHeader: false,
                    preventHeader: false,
                    title: 'Current Setting',
                    titleCollapse: false,
                    store: 'LunMap',
                    region: 'center',
                    split: true,
                    viewConfig: {

                    },
                    selModel: Ext.create('Ext.selection.CheckboxModel', {

                    }),
                    dockedItems: [
                        {
                            xtype: 'toolbar',
                            dock: 'top',
                            items: [
                                {
                                    xtype: 'button',
                                    disabled: true,
                                    itemId: 'delete',
                                    text: 'Delete',
                                    listeners: {
                                        click: {
                                            fn: me.onDeleteClick,
                                            scope: me
                                        }
                                    }
                                },
                                {
                                    xtype: 'button',
                                    itemId: 'refresh',
                                    text: 'Refresh',
                                    listeners: {
                                        click: {
                                            fn: me.onRefreshClick,
                                            scope: me
                                        }
                                    }
                                },
                                {
                                    xtype: 'container',
                                    itemId: 'processstatus',
                                    flex: 1
                                }
                            ]
                        }
                    ],
                    listeners: {
                        selectionchange: {
                            fn: me.onGridpanelSelectionChange,
                            scope: me
                        }
                    },
                    columns: [
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'rid',
                            text: 'Rid'
                        },
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'sourceip',
                            text: 'Sourceip'
                        },
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'targetid',
                            text: 'Targetid'
                        },
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'netmask',
                            text: 'Netmask'
                        },
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'access',
                            text: 'Access'
                        },
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'destinationip',
                            text: 'Destinationip'
                        },
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'targetuser',
                            text: 'Targetuser'
                        },
                        {
                            xtype: 'gridcolumn',
                            renderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
                                return '********';
                            },
                            dataIndex: 'targetpass',
                            text: 'Targetpass'
                        },
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'initiatoruser',
                            text: 'Initiatoruser'
                        },
                        {
                            xtype: 'gridcolumn',
                            renderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
                                return '********';
                            },
                            dataIndex: 'initiatorpass',
                            text: 'Initiatorpass'
                        }
                    ]
                },
                {
                    xtype: 'container',
                    minWidth: 290,
                    padding: 5,
                    width: 150,
                    autoScroll: true,
                    region: 'west',
                    split: true,
                    items: [
                        {
                            xtype: 'form',
                            getStore: function(component) {
                                alert('wait...');
                                var me = this;
                                if (Ext.isObject(me.store)) return me.store;
                                Ext.syncRequire('MyApp.model.glunmap');
                                var store = Ext.create('Ext.data.Store', {
                                    model: 'MyApp.model.glunmap',
                                    storeId: 'glunmap',
                                    autoLoad: false
                                });
                                store.on('load', function(){
                                    var m = store.getAt(0);
                                    if (!m) return;
                                    me.loadRecord(m);
                                });
                                me.store = store;
                                return store;

                            },
                            border: 0,
                            itemId: 'glunmap',
                            width: 282,
                            activeItem: 0,
                            layout: {
                                type: 'auto'
                            },
                            bodyBorder: false,
                            bodyCls: 'x-border-layout-ct',
                            items: [
                                {
                                    xtype: 'fieldset',
                                    padding: 5,
                                    collapsed: true,
                                    collapsible: true,
                                    title: 'Global Setting',
                                    items: [
                                        {
                                            xtype: 'checkboxfield',
                                            name: 'enabled',
                                            fieldLabel: 'LUN Map',
                                            boxLabel: 'Enabled'
                                        },
                                        {
                                            xtype: 'fieldset',
                                            padding: 5,
                                            collapsed: false,
                                            collapsible: true,
                                            title: 'Global CHAP Setting',
                                            items: [
                                                {
                                                    xtype: 'textfield',
                                                    name: 'gtargetuser',
                                                    fieldLabel: 'Target User'
                                                },
                                                {
                                                    xtype: 'textfield',
                                                    inputType: 'password',
                                                    name: 'gtargetpass',
                                                    fieldLabel: 'Target Pass'
                                                },
                                                {
                                                    xtype: 'textfield',
                                                    name: 'ginitiatoruser',
                                                    fieldLabel: 'Initiator User',
                                                    regexText: 'invalid IP address'
                                                },
                                                {
                                                    xtype: 'textfield',
                                                    inputType: 'password',
                                                    name: 'ginitiatorpass',
                                                    fieldLabel: 'Initiator Pass'
                                                }
                                            ]
                                        },
                                        {
                                            xtype: 'button',
                                            text: 'Save',
                                            listeners: {
                                                click: {
                                                    fn: me.onButtonClick,
                                                    scope: me
                                                }
                                            }
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            xtype: 'form',
                            border: 0,
                            itemId: 'newmap',
                            width: 278,
                            bodyCls: 'x-border-layout-ct',
                            items: [
                                {
                                    xtype: 'fieldset',
                                    title: 'New Mapping',
                                    items: [
                                        {
                                            xtype: 'ipfield',
                                            name: 'sourceip'
                                        },
                                        {
                                            xtype: 'netmaskfield',
                                            name: 'netmask'
                                        },
                                        {
                                            xtype: 'targetlistfield',
                                            name: 'targetid'
                                        },
                                        {
                                            xtype: 'combobox',
                                            hidden: false,
                                            name: 'access',
                                            value: [
                                                'RW'
                                            ],
                                            fieldLabel: 'Access',
                                            allowBlank: false,
                                            queryMode: 'local',
                                            store: 'Access',
                                            valueField: 'value'
                                        },
                                        {
                                            xtype: 'fieldset',
                                            padding: 5,
                                            width: 268,
                                            collapsed: true,
                                            collapsible: true,
                                            title: 'Mapping CHAP Setting',
                                            items: [
                                                {
                                                    xtype: 'textfield',
                                                    name: 'targetuser',
                                                    fieldLabel: 'Target User'
                                                },
                                                {
                                                    xtype: 'textfield',
                                                    inputType: 'password',
                                                    name: 'targetpass',
                                                    fieldLabel: 'Target Pass'
                                                },
                                                {
                                                    xtype: 'textfield',
                                                    name: 'initiatoruser',
                                                    fieldLabel: 'Initiator User'
                                                },
                                                {
                                                    xtype: 'textfield',
                                                    inputType: 'password',
                                                    name: 'initiatorpass',
                                                    fieldLabel: 'Initiator Pass'
                                                }
                                            ]
                                        },
                                        {
                                            xtype: 'button',
                                            itemId: 'add',
                                            margin: 5,
                                            minWidth: 80,
                                            autoWidth: true,
                                            text: 'Add New ...',
                                            listeners: {
                                                click: {
                                                    fn: me.onAddClick,
                                                    scope: me
                                                }
                                            }
                                        },
                                        {
                                            xtype: 'button',
                                            text: 'Update',
                                            listeners: {
                                                click: {
                                                    fn: me.onUpdateClick,
                                                    scope: me
                                                }
                                            }
                                        }
                                    ]
                                }
                            ]
                        }
                    ]
                },
                {
                    xtype: 'databind',
                    databind: [
                        {
                            itemid: 'mappinglist',
                            autoLoad: true,
                            loadParams: {
                                condition: 'abc=2'
                            }
                        },
                        {
                            itemid: 'glunmap',
                            autoLoad: true
                        }
                    ],
                    region: 'east'
                }
            ]
        });

        me.callParent(arguments);
    },

    onDeleteClick: function(button, e, options) {
        var records = this.down('gridpanel').getSelectionModel().getSelection(),
            store = this.down('gridpanel').store;
        store.remove(records);
        store.sync({operation:{debug:'abc'}});


    },

    onRefreshClick: function(button, e, options) {
        this.reloaded = true;
        this.down('gridpanel').store.load({params: {condition:'abc=1'}});
    },

    onGridpanelSelectionChange: function(tablepanel, selections, options) {
        if (selections.length>=1){
            this.down('#newmap').loadRecord(selections[0]);
            this.down('#delete').enable();
        }else{
            this.down('#delete').disable();
        }


    },

    onButtonClick: function(button, e, options) {
        var form = this.down('#glunmap').getForm(),
            store = this.down('#glunmap').store;
        if (!store) return;
        if (form.isValid()){
            var v = form.getFieldValues(true),
                m = form.getRecord();
            if (!m) return;
            for(var e in v) m.set(e, v[e]);
            store.sync();
        }
    },

    onAddClick: function(button, e, options) {

        var form = this.down('#newmap').getForm(),
            store = this.down('gridpanel').store;
        if (form.isValid()){
            var v = form.getFieldValues();
            var m = store.add(v);
            for(var i=0;i<m.length;i++) m[i].phantom = true;
            store.sync();
        }

    },

    onUpdateClick: function(button, e, options) {
        var form = this.down('#newmap').getForm(),
            store = this.down('gridpanel').store;
        if (form.isValid()){
            var v = form.getFieldValues(true),
                m = form.getRecord();
            if (!m) return;
            for(var e in v) m.set(e, v[e]);
            store.sync();
        }

    }

});
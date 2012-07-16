/*
 * File: app/view/VirtPortal.js
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

Ext.define('app_iwm.view.VirtPortal', {
    extend: 'Ext.form.Panel',
    alias: 'widget.virtportal',
    requires: [
        'app_iwm.view.IpField',
        'app_iwm.view.NetMaskField',
        'app_iwm.view.TargetListField'
    ],

    border: 0,
    layout: {
        type: 'border'
    },
    bodyPadding: 10,
    title: 'Virtual Portal$',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'gridpanel',
                    hideCollapseTool: false,
                    overlapHeader: false,
                    preventHeader: false,
                    title: 'Current Setting$',
                    titleCollapse: false,
                    store: 'VirtPortal',
                    databind: {
                        autoload: true,
                        model: 'virtportal',
                        bindform: 'newvirtentry'
                    },
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
                                    confirmation: 'delete exclude(%excludesource%) or include(%includeip%) member??'
                                },
                                {
                                    xtype: 'button',
                                    itemId: 'refresh',
                                    text: 'Refresh',
                                    confirmation: 'reload portal info?'
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
                            hidden: true,
                            dataIndex: 'targetid',
                            text: 'Targetid'
                        },
                        {
                            xtype: 'gridcolumn',
                            hidden: true,
                            dataIndex: 'targetname',
                            text: 'Targetname'
                        },
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'includeip',
                            text: 'Includeip'
                        },
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'excludesource',
                            text: 'Excludesource'
                        },
                        {
                            xtype: 'gridcolumn',
                            dataIndex: 'count',
                            flex: 1,
                            text: 'Count'
                        }
                    ],
                    features: [
                        {
                            ftype: 'grouping',
                            enableGroupingMenu: false,
                            enableNoGroups: false
                        }
                    ]
                },
                {
                    xtype: 'container',
                    minWidth: 292,
                    width: 292,
                    autoScroll: true,
                    region: 'west',
                    split: true,
                    items: [
                        {
                            xtype: 'form',
                            border: 0,
                            itemId: 'gvirtportal',
                            bodyCls: 'x-border-layout-ct',
                            databind: {
                                autoload: true,
                                model: 'gvirtportal'
                            },
                            items: [
                                {
                                    xtype: 'fieldset',
                                    padding: 5,
                                    collapsed: false,
                                    collapsible: true,
                                    title: 'Global Setting$',
                                    items: [
                                        {
                                            xtype: 'checkboxfield',
                                            name: 'enabled',
                                            fieldLabel: 'Virt Portal$',
                                            boxLabel: 'Enabled',
                                            uncheckedValue: 0,
                                            listeners: {
                                                change: {
                                                    fn: me.onCheckboxfieldChange,
                                                    scope: me
                                                }
                                            }
                                        },
                                        {
                                            xtype: 'ipfield',
                                            itemId: 'portalip',
                                            name: 'portalip',
                                            fieldLabel: 'Portal IP'
                                        },
                                        {
                                            xtype: 'netmaskfield',
                                            itemId: 'portalmask',
                                            name: 'portalmask',
                                            fieldLabel: 'Portal Mask'
                                        },
                                        {
                                            xtype: 'textfield',
                                            name: 'maxcount',
                                            fieldLabel: 'Max Count',
                                            blankText: 'default'
                                        },
                                        {
                                            xtype: 'button',
                                            itemId: 'update',
                                            text: 'Save',
                                            confirmation: 'change portalip(%portalip%) to %new_portalip%?'
                                        },
                                        {
                                            xtype: 'button',
                                            itemId: 'refresh',
                                            text: 'Refresh',
                                            confirmation: 'reload portal config?'
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            xtype: 'form',
                            border: 0,
                            itemId: 'newvirtentry',
                            bodyCls: 'x-border-layout-ct',
                            items: [
                                {
                                    xtype: 'fieldset',
                                    padding: 5,
                                    collapsed: false,
                                    collapsible: true,
                                    title: 'Setting',
                                    items: [
                                        {
                                            xtype: 'targetlistfield',
                                            databind: {
                                                autoload: true,
                                                model: 'targetlist'
                                            },
                                            name: 'targetname',
                                            value: 'default',
                                            fieldLabel: 'TargetName',
                                            displayField: 'shortname',
                                            valueField: 'targetname'
                                        },
                                        {
                                            xtype: 'ipfield',
                                            name: 'includeip',
                                            fieldLabel: 'Include IP'
                                        },
                                        {
                                            xtype: 'ipfield',
                                            name: 'excludesource',
                                            fieldLabel: 'Exclude Client'
                                        },
                                        {
                                            xtype: 'button',
                                            itemId: 'add',
                                            text: 'Add new ...',
                                            confirmation: 'reload portal config?'
                                        }
                                    ]
                                }
                            ]
                        }
                    ]
                }
            ]
        });

        me.callParent(arguments);
    },

    onGridpanelSelectionChange: function(tablepanel, selections, options) {
        if (selections.length==1){
            this.loadRecord(selections[0]);
            this.down('#delete').enable();
        }else{
            this.down('#delete').disable();
        }


    },

    onCheckboxfieldChange: function(field, newValue, oldValue, options) {
        if (!newValue){
            field.up().down('#portalip').allowBlank = true;
            field.up().down('#portalmask').allowBlank = true;
            field.up().down('#portalip').setValue('');
            field.up().down('#portalmask').setValue('');
        }else{
            field.up().down('#portalip').allowBlank = false;
            field.up().down('#portalmask').allowBlank = false;
        }
        field.up('form').getForm().isValid();
    }

});
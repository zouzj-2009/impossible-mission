/*
 * File: app/view/MainView.js
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

Ext.define('iwm.view.MainView', {
    extend: 'Ext.container.Viewport',
    alias: 'widget.mainview',
    requires: [
        'iwm.view.BigIconBtn',
        'iwm.view.SystemInfo',
        'iwm.view.NetworkSetting',
        'iwm.view.iSCSISetting',
        'iwm.view.DiskSetting',
        'iwm.view.SystemMaintain'
    ],

    cls: 'viewbg',
    id: 'viewport',
    layout: {
        type: 'border'
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'container',
                    cls: 'whitebg',
                    height: 80,
                    id: 'menuarea',
                    padding: '0px 50px ',
                    floatable: false,
                    region: 'north',
                    items: [
                        {
                            xtype: 'bigiconbtn',
                            id: 'sysinfo',
                            iconCls: 'sysinfo',
                            text: 'SysInfo$'
                        },
                        {
                            xtype: 'bigiconbtn',
                            id: 'network',
                            iconCls: 'network',
                            text: 'Network$'
                        },
                        {
                            xtype: 'bigiconbtn',
                            id: 'disks',
                            iconCls: 'disks',
                            text: 'Disks$'
                        },
                        {
                            xtype: 'bigiconbtn',
                            id: 'iscsi',
                            iconCls: 'iscsi',
                            text: 'iSCSI$'
                        },
                        {
                            xtype: 'bigiconbtn',
                            id: 'maintain',
                            iconCls: 'maintain',
                            text: 'Maintain$'
                        },
                        {
                            xtype: 'bigiconbtn',
                            confirmtitle: 'Logout Confirm$',
                            confirmation: 'Are you sure to logout?',
                            id: 'logout',
                            itemId: 'refresh',
                            iconCls: 'logout'
                        },
                        {
                            xtype: 'button',
                            id: 'show_unlocalized',
                            text: 'show unlocalized'
                        }
                    ]
                },
                {
                    xtype: 'container',
                    floating: false,
                    id: 'content',
                    padding: '20px 20px 20px 100px',
                    activeItem: 0,
                    layout: {
                        type: 'card'
                    },
                    floatable: false,
                    region: 'center',
                    items: [
                        {
                            xtype: 'sysinfo',
                            cls: 'shadowpanel',
                            itemId: 'sysinfo'
                        },
                        {
                            xtype: 'networksetting',
                            cls: 'shadowpanel',
                            itemId: 'network'
                        },
                        {
                            xtype: 'iscsisetting',
                            cls: 'shadowpanel',
                            itemId: 'iscsi'
                        },
                        {
                            xtype: 'disksetting',
                            cls: 'shadowpanel',
                            itemId: 'disks'
                        },
                        {
                            xtype: 'systemmaintain',
                            cls: 'shadowpanel',
                            itemId: 'maintain'
                        }
                    ]
                }
            ]
        });

        me.callParent(arguments);
    }

});
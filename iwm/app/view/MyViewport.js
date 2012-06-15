/*
 * File: app/view/MyViewport.js
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

Ext.define('MyApp.view.MyViewport', {
    extend: 'Ext.container.Viewport',
    requires: [
        'MyApp.view.BigIconBtn',
        'MyApp.view.SystemInfo',
        'MyApp.view.NetworkSetting',
        'MyApp.view.iSCSISetting',
        'MyApp.view.DiskSetting',
        'MyApp.view.SystemMaintain'
    ],

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
                    padding: '0px 50px ',
                    region: 'north',
                    items: [
                        {
                            xtype: 'bigiconbtn',
                            id: 'sysinfo',
                            text: '系统信息'
                        },
                        {
                            xtype: 'bigiconbtn',
                            id: 'network',
                            text: '网络设定'
                        },
                        {
                            xtype: 'bigiconbtn',
                            id: 'iscsi',
                            text: 'iSCSI设定'
                        },
                        {
                            xtype: 'bigiconbtn',
                            id: 'disks',
                            text: '磁盘管理'
                        },
                        {
                            xtype: 'bigiconbtn',
                            id: 'maintain',
                            text: '系统维护'
                        }
                    ]
                },
                {
                    xtype: 'container',
                    id: 'content',
                    padding: '20px 20px 20px 100px',
                    activeItem: 0,
                    layout: {
                        type: 'card'
                    },
                    region: 'center',
                    items: [
                        {
                            xtype: 'sysinfo',
                            itemId: 'sysinfo'
                        },
                        {
                            xtype: 'networksetting',
                            itemId: 'network'
                        },
                        {
                            xtype: 'iscsisetting',
                            itemId: 'iscsi'
                        },
                        {
                            xtype: 'disksetting',
                            itemId: 'disks'
                        },
                        {
                            xtype: 'systemmaintain',
                            itemId: 'maintain'
                        }
                    ]
                }
            ]
        });

        me.callParent(arguments);
    }

});
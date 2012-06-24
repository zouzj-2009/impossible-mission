/*
 * File: app/view/SystemInfo.js
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

Ext.define('MyApp.view.SystemInfo', {
    extend: 'Ext.container.Container',
    alias: 'widget.sysinfo',
    requires: [
        'MyApp.view.LinkSpeedBar'
    ],

    height: 400,
    layout: {
        type: 'border'
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'tabpanel',
                    activeTab: 1,
                    region: 'center',
                    items: [
                        {
                            xtype: 'gridpanel',
                            itemId: 'iscsiconn',
                            title: 'iSCSI连接',
                            store: 'iscsiconn',
                            databind: {
                                
                            },
                            viewConfig: {

                            },
                            columns: [
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'id',
                                    text: 'Id'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'initiator',
                                    flex: 1,
                                    text: 'Initiator'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'targetid',
                                    text: 'Targetid'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'sourceip',
                                    text: 'Sourceip'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'targetip',
                                    text: 'Targetip'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'access',
                                    text: 'Access'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'readspeed',
                                    text: 'Readspeed'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'writespeed',
                                    text: 'Writespeed'
                                }
                            ],
                            dockedItems: [
                                {
                                    xtype: 'toolbar',
                                    dock: 'top',
                                    items: [
                                        {
                                            xtype: 'button',
                                            itemId: 'delete',
                                            text: 'disconnect'
                                        },
                                        {
                                            xtype: 'button',
                                            itemId: 'refresh',
                                            text: 'refresh'
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            xtype: 'gridpanel',
                            height: 109,
                            itemId: 'netconfig',
                            title: '网卡设定',
                            databind: {
                                model: 'netconfig',
                                mid: 'jobtest'
                            },
                            viewConfig: {

                            },
                            dockedItems: [
                                {
                                    xtype: 'toolbar',
                                    dock: 'top',
                                    items: [
                                        {
                                            xtype: 'button',
                                            itemId: 'delete',
                                            text: 'delete'
                                        },
                                        {
                                            xtype: 'button',
                                            itemId: 'refresh',
                                            text: 'refresh'
                                        }
                                    ]
                                }
                            ],
                            columns: [
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'dev',
                                    text: 'Dev'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'physicdevice',
                                    text: 'Physicdevice'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'ipaddress',
                                    text: 'Ipaddress'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    width: 169,
                                    dataIndex: 'ipv6address',
                                    text: 'Ipv6address'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'netmask',
                                    text: 'Netmask'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'broadcast',
                                    text: 'Broadcast'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'rxbytes',
                                    text: 'Rxbytes'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'txbytes',
                                    text: 'Txbytes'
                                }
                            ]
                        },
                        {
                            xtype: 'gridpanel',
                            itemId: 'sysinfo',
                            title: '系统信息',
                            store: 'sysinfo',
                            databind: {
                                autoload: true
                            },
                            viewConfig: {

                            },
                            columns: [
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'part',
                                    text: 'Part'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'description',
                                    text: 'Description'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'note',
                                    flex: 1,
                                    text: 'Note'
                                }
                            ],
                            dockedItems: [
                                {
                                    xtype: 'toolbar',
                                    dock: 'top',
                                    items: [
                                        {
                                            xtype: 'button',
                                            itemId: 'refresh',
                                            text: 'refresh'
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            xtype: 'linkspeedbar'
                        }
                    ]
                }
            ]
        });

        me.callParent(arguments);
    }

});
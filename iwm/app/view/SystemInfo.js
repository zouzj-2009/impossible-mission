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
                    border: 0,
                    padding: 10,
                    activeTab: 2,
                    region: 'center',
                    items: [
                        {
                            xtype: 'gridpanel',
                            itemId: 'iscsiconn',
                            title: 'iSCSI连接',
                            databind: {
                                autoload: true,
                                model: 'iscsiconn'
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
                                            text: 'disconnect'
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
                                    dataIndex: 'clientip',
                                    text: 'Clientip'
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
                            ]
                        },
                        {
                            xtype: 'gridpanel',
                            height: 109,
                            itemId: 'netconfig',
                            title: '网卡设定',
                            databind: {
                                model: 'netconfig'
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
                                            getConfirmation: function(button, value, records, store) {
                                                if (!records.length) return false;
                                                for(var i=0; i<records.length; i++){
                                                    var r = records[i].getData();
                                                    if (r.ipaddress == location.host) return {
                                                        title: 'Waning!',
                                                        msg: 'IP address of current visited server '+r.ipaddress+' will be deleted, you need restrart the web application, are you sure?'
                                                    };
                                                }
                                                return false;
                                            },
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
                            ],
                            selModel: Ext.create('Ext.selection.CheckboxModel', {

                            })
                        },
                        {
                            xtype: 'gridpanel',
                            itemId: 'sysinfo',
                            title: '系统信息',
                            databind: {
                                autoload: true,
                                model: 'pciinfo'
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
                                            itemId: 'refresh',
                                            text: 'refresh'
                                        }
                                    ]
                                }
                            ],
                            columns: [
                                {
                                    xtype: 'gridcolumn',
                                    width: 78,
                                    dataIndex: 'busid',
                                    text: 'Busid'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    width: 113,
                                    dataIndex: 'type',
                                    text: 'Type',
                                    databind: {
                                        autoload: true,
                                        model: 'pciinfo'
                                    }
                                },
                                {
                                    xtype: 'gridcolumn',
                                    width: 139,
                                    dataIndex: 'vendor',
                                    text: 'Vendor'
                                },
                                {
                                    xtype: 'gridcolumn',
                                    dataIndex: 'description',
                                    flex: 1,
                                    text: 'Description'
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
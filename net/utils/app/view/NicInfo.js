/*
 * File: app/view/NicInfo.js
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

Ext.define('net_utils.view.NicInfo', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.nicinfo',

    height: 109,
    itemId: 'netconfig',
    title: 'NIC Information$',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            databind: {
                model: 'net_utils.model.netconfig'
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
        });

        me.callParent(arguments);
    }

});
/*
 * File: app/view/iSCSIConn.js
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

Ext.define('target_iscsi.view.iSCSIConn', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.iscsiconn',

    itemId: 'iscsiconn',
    title: 'iSCSI Connection$',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            databind: {
                autoload: true,
                model: 'target_iscsi.model.iscsiconn'
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
        });

        me.callParent(arguments);
    }

});
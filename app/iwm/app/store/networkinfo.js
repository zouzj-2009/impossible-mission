/*
 * File: app/store/networkinfo.js
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

Ext.define('app_iwm.store.networkinfo', {
    extend: 'Ext.data.Store',
    requires: [
        'app_iwm.model.netconfig'
    ],

    constructor: function(cfg) {
        var me = this;
        cfg = cfg || {};
        me.callParent([Ext.apply({
            storeId: 'networkinfo',
            model: 'app_iwm.model.netconfig'
        }, cfg)]);
    }
});
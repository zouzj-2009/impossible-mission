/*
 * File: app/controller/NetConfig.js
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

Ext.define('net_utils.controller.NetConfig', {
    extend: 'Ext.app.Controller',

    models: [
        'net_utils.model.netconfig',
        'net_utils.model.dnsgw',
        'net_utils.model.niclist'
    ],
    stores: [
        'net_utils.store.netconfig'
    ],
    views: [
        'net_utils.view.NetConfig'
    ]
});

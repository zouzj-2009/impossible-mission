/*
 * File: app/view/NetMaskField.js
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

Ext.define('iwm.view.NetMaskField', {
    extend: 'Ext.form.field.Text',
    alias: 'widget.netmaskfield',

    fieldLabel: 'NetMask',
    regexText: 'Invalid net mask value',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            regex: /^(((0|128|192|224|240|248|252|254).0.0.0)|(255.(0|128|192|224|240|248|252|254).0.0)|(255.255.(0|128|192|224|240|248|252|254).0)|(255.255.255.(0|128|192|224|240|248|252|254|255)))$/
        });

        me.callParent(arguments);
    }

});
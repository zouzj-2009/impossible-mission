/*
 * File: app/controller/MaskDBClick.js
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

Ext.define('MyApp.controller.MaskDBClick', {
    extend: 'Ext.app.Controller',
    alias: 'controller.maskdbclick',

    onComponentShow: function(abstractcomponent, options) {
        var c = abstractcomponent;
        //alert(c.getXType());
        c.getEl().on('click', function(e, t, o){
            Ext.Msg.confirm("debug on ...", function(b){
                alert(b);
            });
        });
    },

    init: function() {
        this.control({
            "loadmask": {
                show: this.onComponentShow
            }
        });

    }

});

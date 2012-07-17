/*
 * File: app/controller/MainMenu.js
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

Ext.define('app_iwm.controller.MainMenu', {
    extend: 'Ext.app.Controller',
    alias: 'controller.mainmenu',

    onButtonClick: function(button, e, options) {
        var id = button.getId(),
            app = this.application;
        if (id == 'logout'){
            Ext.Msg.confirm(button.confirmtitle, button.confirmation, function(btn){
                if (btn == 'yes'){
                    app.fireEvent('logout');
                }
            });
            return;
        }
        Ext.getCmp('content').getLayout().setActiveItem(id);
    },

    init: function() {
        this.control({
            "bigiconbtn": {
                click: this.onButtonClick
            }
        });

    }

});
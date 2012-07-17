/*
 * File: app/controller/Login.js
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

Ext.define('ui_common.controller.Login', {
    extend: 'Ext.app.Controller',

    models: [
        'ui_common.model.language',
        'ui_common.model.login'
    ],
    stores: [
        'ui_common.store.language',
        'ui_common.store.login'
    ],
    views: [
        'ui_common.view.Login',
        'ui_common.view.ChangePassword'
    ],

    onButtonClick: function(button, e, options) {
        this.application.fireEvent('changepassword', button.login);
    },

    onLoginok: function(host, login) {
        var app = this,
            lwin = Ext.getCmp('loginwindow');

        lwin.hide();
        lwin.logouted = false;
        this.logged = login;
        if (Ext.isFunction(app.start)) app.start({serverip:host});
    },

    onLoginfail: function(cfg) {
        var lwin = Ext.getCmp('loginwindow');

        if (!lwin){
            lwin = this.getView('ui_common.view.Login').create({serverip:cfg.host});
        }else{
            if (lwin.serverip != cfg.host){
                lwin.destroy();
                lwin = this.getView('ui_common.view.Login').create({serverip:cfg.host});
            }
        }
        if (!lwin.isVisible()) lwin.show();
    },

    onLoginloaded: function(store, form) {
        var m = store.getAt(0);
        if (!m) return;
        //todo: get saved user from form.state?
        form.getForm().loadRecord(m);
        this.loginstore = store;
    },

    onLogout: function() {
        var store = this.loginstore,
            params = Ext.apply({_logout:true}, store.reloadParams);
        store.load({params:params});
        var lwin = Ext.getCmp('loginwindow');
        lwin.logouted = true;
        lwin.show();
        this.mainview.destroy();
        delete this.mainview;

    },

    onChangePassword: function(login) {
        var user = login?login.username:this.logged.username,
            form = this.getView('ChangePassword').create({user:user});
        form.show();
    },

    init: function() {
        this.control({
            "button#changepassword": {
                click: this.onButtonClick
            }
        });

        this.application.on({
            loginok: {
                fn: this.onLoginok
            },
            loginfail: {
                fn: this.onLoginfail
            },
            loginloaded: {
                fn: this.onLoginloaded
            },
            logout: {
                fn: this.onLogout
            },
            changepassword: {
                fn: this.onChangePassword
            }
        });

    }

});

/*
 * File: app.js
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

Ext.Loader.setConfig({
    enabled: true
});

Ext.application({
    models: [
        'login',
        'language'
    ],
    stores: [
        'language'
    ],
    views: [
        'BigIconBtn',
        'NetMaskField',
        'TargetListField',
        'changepassword',
        'ChangePassword',
        'Login',
        'Messager'
    ],
    autoCreateViewport: true,
    name: 'ui_common',
    controllers: [
        'EventMessager',
        'Login',
        'DataIndicator'
    ],
    defaultlang: 'zh_cn',

    launch: function() {

    },

    start: function(config) {
        if (!this.mainview)
        this.mainview = Ext.widget('mainview', config);

    }

});

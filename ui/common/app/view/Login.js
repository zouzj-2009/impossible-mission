/*
 * File: app/view/Login.js
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

Ext.define('ui_common.view.Login', {
    extend: 'Ext.window.Window',
    alias: 'widget.login',

    autoShow: true,
    height: 264,
    id: 'loginwindow',
    width: 412,
    layout: {
        type: 'fit'
    },
    bodyBorder: false,
    closeAction: 'hide',
    iconCls: 'login',
    title: 'Login$',
    defaultFocus: 'upload',
    modal: true,

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'form',
                    border: 0,
                    id: 'loginform',
                    padding: 10,
                    layout: {
                        align: 'center',
                        pack: 'center',
                        type: 'vbox'
                    },
                    bodyCls: 'x-border-layout-ct',
                    bodyPadding: 20,
                    databind: {
                        model: 'login',
                        loadparams: {
                            _getlogingon: true
                        },
                        autoload: true
                    },
                    items: [
                        {
                            xtype: 'displayfield',
                            itemId: 'logon',
                            width: 300,
                            name: 'logingon',
                            value: 'localhost',
                            fieldLabel: 'Loging on',
                            labelAlign: 'right',
                            margins: '0 0 5 0'
                        },
                        {
                            xtype: 'textfield',
                            width: 300,
                            name: 'username',
                            fieldLabel: 'Username',
                            labelAlign: 'right',
                            allowBlank: false
                        },
                        {
                            xtype: 'textfield',
                            width: 300,
                            inputType: 'password',
                            name: 'password',
                            fieldLabel: 'Password',
                            labelAlign: 'right',
                            enableKeyEvents: true,
                            listeners: {
                                specialkey: {
                                    fn: me.onTextfieldSpecialkey,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype: 'combobox',
                            itemId: 'language',
                            width: 300,
                            name: 'language',
                            value: 'zh_cn',
                            fieldLabel: 'Language',
                            labelAlign: 'right',
                            displayField: 'language',
                            queryMode: 'local',
                            store: 'language',
                            valueField: 'lang',
                            databind: {
                                model: 'language',
                                autoload: true
                            },
                            listeners: {
                                change: {
                                    fn: me.onLanguageChange,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype: 'button',
                            onactiondone: function(success, action, model, values, controller, dbcfg) {
                                if (action.type == 'read'){
                                    if (success)
                                    controller.application.fireEvent('loginok', dbcfg.host, values);
                                    else
                                    controller.application.fireEvent('loginfail', dbcfg.host, values);
                                }

                            },
                            itemId: 'upload',
                            minWidth: 120,
                            iconCls: 'x-btn-tool-login',
                            text: 'Login$',
                            usingaction: 'read',
                            margins: '15 0 20 0',
                            formBind: true
                        }
                    ]
                }
            ],
            listeners: {
                show: {
                    fn: me.onWindowShow,
                    scope: me
                },
                close: {
                    fn: me.onLoginwindowClose,
                    scope: me
                }
            }
        });

        me.callParent(arguments);
    },

    onTextfieldSpecialkey: function(field, e, options) {
        if (e.getKey() == e.ENTER) this.down('#upload').fireEvent('click');
    },

    onLanguageChange: function(field, newValue, oldValue, options) {
        var lang = field.getValue();
        Ext.Loader.loadScript('js/lang.'+lang+'.js');
    },

    onWindowShow: function(abstractcomponent, options) {
        var w = abstractcomponent,
            logon = '';
        if (w.logonip) 
        logon += w.logonip; 
        else 
        logon += location.host;
        if (w.logonhost) logon += '('+w.logonhost+')';
        w.down('#logon').setValue(logon);

    },

    onLoginwindowClose: function(window) {
        if (!Ext.getCmp('menuarea') || window.logouted) {
            window.show();
            return false;
        }
    }

});
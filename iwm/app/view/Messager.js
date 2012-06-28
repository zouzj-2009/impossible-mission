/*
 * File: app/view/Messager.js
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

Ext.define('MyApp.view.Messager', {
    extend: 'Ext.container.Container',
    alias: 'widget.messager',

    autoRender: true,
    autoShow: true,
    border: 1,
    floating: true,
    height: 60,
    hidden: true,
    id: 'messager',
    padding: 10,
    width: 300,

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'label',
                    itemId: 'text',
                    shadow: 'drop',
                    text: ' popup messages asdfasdfasdfasdfadsfasdfasdfasdfadsfafd'
                }
            ]
        });

        me.callParent(arguments);
    },

    showPopup: function(type, info) {
        var m = this,
            v = Ext.getBody().getViewSize();

        m.setPosition(v.width-300-20, 20); 
        var text = m.down('#text');
        text.setText(info.donetext);

        m.meta = { type: 'datadone', info: info };
        m.toFront();
        if (m.timeout) clearTimeout(m.timeout);
        m.timeout = Ext.defer(m.hidePopup, 6000, m);
        m.animate({
            duration: 1000,
            easing: 'bounceIn',
            to: {
                opacity: 100
            },
            from: {
                opacity: 0
            }
        });
    },

    hidePopup: function() {
        var m = this;
        m.animate({
            from:{
                opacity:100
            },
            to:{
                opacity:0
            },
            listeners:{
                afteranimate: function(){
                    m.toBack();
                }
            }
        });

    }

});
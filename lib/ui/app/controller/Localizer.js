/*
 * File: app/controller/Localizer.js
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

Ext.define('lib_core.controller.Localizer', {
    extend: 'Ext.app.Controller',

    unlocalized: {
        
    },

    onComponentBeforeRender: function(abstractcomponent, options) {
        //maybe slowly?
        var c = abstractcomponent;
        if (c.id == 'show_unlocalized'){
            var me = this;
            c.on('click', function(){
                var l = me.unlocalized,
                    o = '';
                for (var name in l){
                    o += "\t'"+name+'\': \''+l[name]+'\',\n';
                }
                alert(o);
            });
        }else if (c.isXType('menuitem')){
            this.localizeMenuItem(c);
        }else if (c.isXType('button')){
            this.localizeButton(c);
        }else if (c.isXType('field')){
            this.localizeField(c);
        }else if (c.isXType('panel')){
            this.localizePanel(c);
        }else if (c.isXType('gridcolumn')){
            this.localizeGridColumn(c);
        }else if (c.isXType('fieldset')){
            this.localizeFieldSet(c);
        }

    },

    init: function(application) {
        this.control({
            "component": {
                beforerender: this.onComponentBeforeRender
            }
        });
        //todo: load proper lang setting js here
        var lang = this.application.defaultlang;
        Ext.ns('App.locale');
        if (!App.locale.text){
            App.locale.text = {};
            //todo: need synchorized loading here!
            Ext.Loader.loadScript('js/lang.'+lang+'.js');
        }

    },

    localizeButton: function(button) {
        //we use a '$-ended' string for localize-need text
        //because button text is short, easy to confused, so, use $ as an identify
        var c = button,
            t = c.getText(),
            tx = t?t.replace(/\$$/, ''):'__x',
            lt = App.locale.text[tx];
        //if button has itemId, we check it.
        //if button text has $ ended, we check it.
        if ((c.itemId || (tx != t))){
            if (lt) c.setText(lt);
            else {
                if (t) c.setText(tx);
                this.unlocalized[tx] = '';
            }
        }

        //confirmation is long, so can be or not be end with $
        t = c.confirmation;
        if (t){
            //end with $ will be localized
            tx = t.replace(/\$$/, '');
            lt = App.locale.text[tx];
            if (lt) c.confirmation = lt;
            else {
                c.confirmation = tx;
                this.unlocalized[tx] = tx;
            }
        }

        //same as button text, title is short, so need $
        t = c.confirmtitle;
        if (t){
            //end with $ will be localized
            tx = t.replace(/\$$/, '');
            if (t != tx){
                lt = App.locale.text[tx];
                if (lt) c.confirmtitle = lt;
                else {
                    c.confirmtitle = tx;
                    this.unlocalized[tx] = '';
                }
            }
        }
    },

    localizeFieldSet: function(fieldset) {
        var c = fieldset,
            t = c.title,
            tx = t?t.replace(/\$$/, ''):'__x',
            lt = App.locale.text[tx];
        //can be or not be ended with $
        if (lt) c.setTitle(lt);
        else {
            if (t) c.setTitle(tx);
            this.unlocalized[tx] = '';
        }
    },

    localizePanel: function(panel) {
        var c = panel,
            t = c.title,
            tx = t?t.replace(/\$$/, ''):'__x',
            lt = App.locale.text[tx];
        //can be or not be ended with $
        if (lt) c.setTitle(lt);
        else{
            if(t) c.setTitle(tx);
            this.unlocalized[tx] = '';
        }
    },

    localizeGridColumn: function(column) {
        var t = column.text?column.text:'__x',
            lt = App.locale.text[t];
        if (lt) column.setText(lt);
        else{
            this.unlocalized[t] = '';
        }
    },

    localizeField: function(field) {
        var t = field.getFieldLabel(),
            tx = t?t.replace(/\$$/, ''):'__x',
            lt = App.locale.text[tx];
        if (lt) field.setFieldLabel(lt);
        else{
            if (t) field.setFieldLabel(tx);
            this.unlocalized[tx] = '';
        }
        //invalid text!
        t = field.invalidText;
        tx = t?t.replace(/\$$/, ''):'__x';
        lt = App.locale.text[tx];
        if (lt) field.invalidText = lt;
        else{
            if (t) field.invalidText = tx;
            this.unlocalized[tx] = tx;
        }

    },

    localizeMenuItem: function(mi) {
        //we use a '$-ended' string for localize-need text
        //because button text is short, easy to confused, so, use $ as an identify
        var c = mi,
            t = c.text,
            tx = t?t.replace(/\$$/, ''):'__x',
            lt = App.locale.text[tx];
        //if button has itemId, we check it.
        //if button text has $ ended, we check it.
        if ((c.itemId || (tx != t))){
            if (lt) c.setText(lt);
            else {
                if (t) c.setText(tx);
                this.unlocalized[tx] = '';
            }
        }

        //confirmation is long, so can be or not be end with $
        t = c.confirmation;
        if (t){
            //end with $ will be localized
            tx = t.replace(/\$$/, '');
            lt = App.locale.text[tx];
            if (lt) c.confirmation = lt;
            else {
                c.confirmation = tx;
                this.unlocalized[tx] = tx;
            }
        }

        //same as button text, title is short, so need $
        t = c.confirmtitle;
        if (t){
            //end with $ will be localized
            tx = t.replace(/\$$/, '');
            if (t != tx){
                lt = App.locale.text[tx];
                if (lt) c.confirmtitle = lt;
                else {
                    c.confirmtitle = tx;
                    this.unlocalized[tx] = '';
                }
            }
        }
    }

});
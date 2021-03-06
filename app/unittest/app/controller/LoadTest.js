/*
 * File: app/controller/LoadTest.js
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

Ext.define('app_unittest.controller.LoadTest', {
    extend: 'Ext.app.Controller',

    models: [
        'testunit',
        'jobtest'
    ],

    onButtonClick: function(button, e, options) {
        var app = this.application,
            r = button.up().up().down('#testunitselector').getStore().getRange(),
            em = button.up().up().down('#multiinstance').getValue(),
            tp = Ext.getCmp('testpanel');
        //if (!em) button.disable();
        if (!tp) return;
        if (!Ext.isArray(r)) return;
        var config = {serverip: button.up().up().down('#serverip').getValue()};
        Ext.Array.forEach(r, function(cn){
            if (cn.getData().text)
            this.loadunit(cn.getData().text, em, tp, config);
        },this);
    },

    onComboboxBeforeRender: function(abstractcomponent, options) {
        var c = abstractcomponent,
            store = c.getStore();
        Ext.Array.forEach(this.application.uselibs, function(unit){
            store.add({text:unit});
        });

    },

    onButtonClick1: function(button, e, options) {
        var app = this.application,
            em = button.up().up().down('#multiinstance').getValue(),
            tp = Ext.getCmp('testpanel');
        //if (!em) button.disable();
        name = button.up().up().down('#testunitselector').getValue();
        if (!name){
            alert('unit not selected.');
            return;
        }
        var config = {serverip: button.up().up().down('#serverip').getValue()};
        var store = button.up().up().down('#testunitselector').getStore();
        if (this.loadunit(name, em, tp, config, store)){
            if (store.find('text', name)<0){
                store.add({text:name});
            }   
        }
    },

    loadunit: function(name, menable, tab, config, store) {
        var app = this.application;
        if (name.match(/\.view\./)){
            return this.loadview(name, menable, tab, null, null);
        }
        if (!name.match(/\.controller\./)) return false;
        if (!Ext.ClassManager.isCreated(name)){
            var n = name.split(/\.controller\./);
            Ext.Loader.setPath(n[0], '../../'+n[0].replace(/\.|_/g, '/')+'/app');
            c = app.getController(name);
            c.init(app);
        }else{
            c = app.getController(name);
        }
        if (!c){
            alert(name+': not found!');
            return false;
        }
        var views = c.views;
        if (!Ext.isArray(views)){
            alert(name+': no views!');
            return false;
        }
        for(var i=0;i<views.length; i++){
            var view = views[i];
            if (!this.loadview(view, menable, tab, config, c)) return false;
        }
        return true;
    },

    loadview: function(view, menable, tab, config, controller) {
        if (controller && !view.match(/\.view\./)){
            var ns = controller.self.getName().replace(/\.controller\..*/, '');
            view = ns+'.view.'+view;
        }
        var itemId = view.replace(/\.view\./, '_');
        if (!menable && tab.down('#'+itemId)){
            alert(view+': already created.');
            return false;
        }
        var vc = this.application.getView(view);
        if (!vc){
            alert(view+': not defined.');
            return false;
        }
        var vv = vc.create(Ext.apply({itemId:menable?null:itemId, closable:true}, config));
        if (!vv){
            alert(view+' not found!');
            return false;
        }
        return tab.setActiveTab(tab.add(vv));

    },

    onControllerClickStub: function() {

    },

    init: function() {
        this.control({
            "button#loadtest": {
                click: this.onButtonClick
            },
            "combobox#testunitselector": {
                beforerender: this.onComboboxBeforeRender
            },
            "button#loadoneunit": {
                click: this.onButtonClick1
            }
        });

    }

});

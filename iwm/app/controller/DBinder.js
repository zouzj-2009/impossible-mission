/*
 * File: app/controller/DBinder.js
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

Ext.define('MyApp.controller.DBinder', {
    extend: 'Ext.app.Controller',
    alias: 'controller.dbinder',

    onFormAfterRender: function(abstractcomponent, options) {
        var me=this, c=abstractcomponent, databind = c.databind;
        if (!Ext.isObject(databind)) return;

        //form's default mask selector is this component.
        //if want to local show, using 'maskxtype: null' in databind config.
        if (databind.progress){
            Ext.applyIf(databind.progress, {
                maskxtype: null
            });
        }

        me.bindGroup(c, c.databind, c.serverip, c.rebind);
        if (!c.databinded) return;
        me.bindForm(c, c.store, c.databinded, me);

    },

    onGridpanelAfterRender: function(abstractcomponent, options) {
        var me=this, c=abstractcomponent, databind = c.databind;
        if (!Ext.isObject(databind)) return;

        //grid's default mask selector is up('form').
        if (databind.progress){
            Ext.applyIf(databind.progress, {
                maskxtype: 'panel'
            });
        }else{
            databind.progress = {maskxtype: 'panel'};
        }

        me.bindGroup(c, c.databind, c.serverip, c.rebind);
        if (!c.databinded) return;
        me.bindGrid(c, c.store, c.databinded, me);
        if (c.databinded.bindform){
            var form = c.up().down('#'+c.databinded.bindform);
            if (!form) return;
            me.bindGridForm(c, form, c.store, c.databinded);
        }
    },

    onGridpanelActivate: function(abstractcomponent, options) {
        var c = abstractcomponent,
            store = c.store;
        if (!store) return;
        if (!c.databinded) return;
        if (c.databinded.autoloaded) return;
        if (store.isLoading()) return;
        store.load(store.reloadParams);
        if (c.down('>toolbar>#refresh')){//has refresh, don't load again
            c.databinded.autoloaded = true;
        }

    },

    updateProgressComponent: function(cfg, response, operation) {
        //todo: lazy dispear progress
        //todo: mask the action component
        //binder scope
        var binder = this,
            c = cfg.container,
            dbc = cfg.dbc,
            pcfg = cfg.pcfg||{},
            ptype = pcfg[operation.action]||pcfg.type,
            domask = ptype != 'newin' && !pcfg.nomask && (!pcfg || !pcfg.noMask || cfg.maskid),
            pend = response?(!response.pending): true,
            title = response&&response.pending?response.pending.title:Ext.String.capitalize(operation.action+' '+cfg.modelId),
            msg = response&&response.pending?response.pending.msg:'Please waiting ...',    
            number = response&&response.pending?response.pending.number:(operation.seq/(operation.seqmax?operation.seqmax:10)),
            pendingtext = response&&response.pending?response.pending.text:title+' '+Ext.util.Format.number(number, '0.00')+'%',
            donetext = response&&response.msg?response.msg:title+' done.',
            pc = null;
        if (ptype != 'newin' && pcfg && pcfg.id) pc = Ext.getCmp(pcfg.id);
        else if (ptype != 'newin' && pcfg && pcfg.itemid) pc = c.down('#'+pcfg.itemid);
        else if (pcfg && ptype == 'newin'){
            if (!pend && !pcfg.msgwin){
                //create new win
                pcfg.msgwin = Ext.Msg.progress(title, msg, pendingtext);
                if (pcfg.msgwin.minWidth < 400){
                    Ext.getClass(pcfg.msgwin).prototype.minWidth = 400;
                    pcfg.msgwin.setWidth(400);
                }
            }else{
                if (pend && pcfg.msgwin){
                    if (response && response.success){
                        pcfg.msgwin.updateProgress(1, 'Complete(100%)', donetext);
                        Ext.defer(pcfg.msgwin.close, 1000, pcfg.msgwin);
                        pcfg.msgwin = null;
                    }else{//todo, msg prompt box!
                        pcfg.msgwin.updateProgress(1, 'Done, fail!(100%)', donetext);
                        pcfg.msgwin.on('close', function(win, options){
                            Ext.Msg.alert(title, donetext);
                        });
                        Ext.defer(pcfg.msgwin.close, 1000, pcfg.msgwin);
                        pcfg.msgwin = null;
                        //Ext.Msg.alert(title, donetext);
                    }
                }else if (pcfg.msgwin){
                    pcfg.msgwin.updateProgress(number, pendingtext, msg);
                }
            }
            return;
        }else{
            pc = dbc.down('#processstatus');
            if (!pc){
                if (cfg.deffpc && (operation.action == 'update' || operation.action == 'create')){
                    pc = cfg.deffpc;
                }else if (cfg.defpc) pc = cfg.defpc; else pc = Ext.getCmp('processstatus');
            }
            //don't need!
            if (!pc) return;
        }
        if (domask){//do mask
            if (pend && cfg.mask){
                cfg.mask.hide(c).destroy();
                cfg.mask = null;
            }else if (!pend && !cfg.mask){
                //mask priority: pxfg.maskxtype, pcfg.maskid, c, dbc(if localmask)
                var mask = pcfg.maskxtype?dbc.up(pcfg.maskxtype):(pcfg.maskid?c.down('#'+pcfg.maskid):(pcfg.localmask?dbc:c));
                if (!mask) mask = c;
                cfg.mask = new Ext.LoadMask(mask, {msg:title+' ...'});
                cfg.mask.show(mask);
            }
        }
        if (pcfg && ptype == 'text'){
            //todo: class of this text, 
            if (pend){
                pc.getEl().setHTML('<a class="x-progress-text">'+donetext+'</a>');
                Ext.defer(pc.getEl().setHTML(''), 1000, pc.getEl(), ['']);
            }else{
                pc.getEl().setHTML('<a class="x-progress-text">'+pendingtext+'</a>');
            }
        }else{
            var ppc = pc.down('progressbar');
            if (!ppc && !pend){
                ppc = Ext.create('Ext.ProgressBar',{flex:1});
                pc.add(ppc);
            }
            if (pend && ppc){
                ppc.updateProgress(1, donetext, true);
                if (response && response.success){
                    Ext.defer(pc.remove, 1000, pc, [ppc, true]);
                }else{
                    ppc.getEl().on('click', function(){
                        Ext.Msg.alert(title, donetext, function(){
                            Ext.defer(pc.remove, 1000, pc, [ppc, true]);
                        });
                    });
                }
            }else if (ppc) {
                ppc.updateProgress(number, pendingtext, true);
            }
        }
    },

    processLoad: function(store, records, successful, cfg) {
        //called by onProxyRead, when data loaded, update progress indicator.
        //binder scope
        var binder = this;
        if (!successful) return true;//updated by proxyErrors
        binder.updateProgressComponent(cfg, {success:successful}, {action:'read', seq:10, seqmax:10});

    },

    processWrite: function(store, operation, cfg) {
        //called by onProxyWrite, when data synced, update progress indicator
        //todo: process final pending write here!
        //actually, write fail will not get here!
        //binder scope
        var binder = this;
        if(!operation.wasSuccessful()) alert('write fail!');
        binder.updateProgressComponent(cfg, operation.response, operation);

    },

    processErrors: function(proxy, response, operation, cfg) {
        //called by onProxyException, when proxy error OR PENDING received
        //binder scope
        var binder = this;
        if (!response){
            //todo:when batch op,say create/destroy if create fail, ...
            alert('Fatal error, operation.'+operation.action+' no response! error:'+operation.error);
            //todo: try to continue when first op in batch fail at 'callbackxxx not found'
            binder.updateProgressComponent(cfg, {success:false, msg:'proxy '+operation.error}, operation);
            return;
        }
        try{
            //todo: interactive C/S
            if (response.pending){
                binder.updateProgressComponent(cfg, response, operation);
                Ext.applyIf(operation, {params:{}, seq:0});
                Ext.apply(operation.params, {seqid: ++operation.seq, jobid: response.pending.jobid, pending:Ext.encode(response.pending)});
                cfg.store.getProxy().doRequest(operation, operation.origincallback, operation.originscope);
            }else{//fail
                binder.updateProgressComponent(cfg, response, operation);
                //how about update/destroy?
                //todo: check more, phantom or sth. else?
                //maybe problem is phantom when add(v);
                //if (operation.action == 'create') options.store.remove(operation.records);
                /* can't just simple add fail destroied records
                if (operation.action == 'destroy') 
                options.store.add(operation.records);
                */
                //fail policy
                var fp = cfg.failpolicy[operation.action];
                if (!fp){//get default value for different action
                    switch(operation.action){
                        case 'create':
                        fp = 'destroy';
                        break;
                        case 'update':
                        fp = 'askdelete';
                        break;
                        case 'destroy':
                        fp = 'reload';
                        break;
                    }
                    if (!fp) return;
                }
                switch(fp){
                    case 'destroy':
                    cfg.store.remove(operation.records);
                    break;
                    case 'reload':
                    cfg.store.load(cfg.store.reloadParams);
                    break;
                    case 'reload': //'askdelete':
                    Ext.Msg.confirm(
                    'Confirm Deletion', 
                    'Data '+cfg.modelId+' '+operation.action+' fail, delete the trash data?',
                    function (btn){
                        if (btn == 'yes') cfg.store.remove(operation.records);
                    });
                    break;
                }
            }
        }catch(e){
            alert(e);
        }
    },

    encodeRecords: function(records) {
        //override the jsonp's proxy's encodeRecords
        var i=0, data=[];
        for(;i<records.length; i++) data.push(records[i].getData());
        return Ext.encode(data);

    },

    createRequestCallback: function(request, operation, callback, scope) {
        //to override the jsonp proxy's same function, save callback context
        var lme = this;
        operation.origincallback = callback;
        operation.originscope = scope;
        return function(success, response, errorType) {
            delete lme.lastRequest;
            lme.processResponse(success, operation, request, response, callback, scope);
        };

    },

    doProxyDestroy: function() {
        //to override the jsonp proxy's destroy function, fixe the bug.
        var lme = this;
        return lme.doRequest.apply(lme, arguments);
    },

    bindGrid: function(grid, store, cfg, binder) {
        var sm = grid.getSelectionModel();
        //don't override designed behavior.
        if (sm && !cfg.ignore.selectionchange){
            grid.on('selectionchange', function(grid, selections, options){
                if (selections.length>=1 && this.down('#delete')){
                    this.down('#delete').enable();
                }else{
                    this.down('#delete').disable();
                }
            });
        }

        var del = grid.down('#delete');
        if (del && !cfg.ignore['delete']){
            del.on('click', function(button, event, options){
                var records = grid.getSelectionModel().getSelection();
                //store = this.down('gridpanel').store;
                store.remove(records);
                store.sync({operation:{debug:'abc'}});
            });
        }

        var ref = grid.down('#refresh');
        if (ref && !cfg.ignore.refresh){
            ref.on('click', function(button, event, options){
                var params = Ext.applyIf({refresh:true, docheck:true}, store.reloadParams.params);
                store.load({params:params});
            });
        }
    },

    bindForm: function(form, store, cfg) {
        var binder = this;
        store.on('load', function(){
            var m = this.getAt(0);
            if (!m) return;
            form.getForm().loadRecord(m);
        });

        binder.bindFormActions(form, store, cfg, binder);
    },

    bindGridForm: function(grid, form, store, cfg) {

        var binder = this,
            sm = grid.getSelectionModel();
        if (sm && !cfg.ignore.bindform){
            grid.on('selectionchange', function(grid, selections, options){
                var m = selections[0];
                if (m) form.loadRecord(m);
            });
        }

        binder.bindFormActions(form, store, cfg, binder);
    },

    bindFormActions: function(form, store, cfg) {
        var add = form.down('#add');
        if (add && !cfg.ignore.click){
            add.on('click', function(button, event, options){
                if (form.getForm().isValid()){
                    var v = form.getForm().getFieldValues();
                    var m = store.add(v);
                    for(var i=0;i<m.length;i++) m[i].phantom = true;
                    store.sync();
                }

            });
        }

        var upd = form.down('#update');
        if (upd && !cfg.ignore.click){
            upd.on('click', function(button, event, options){
                if (form.getForm().isValid()){
                    var v = form.getForm().getFieldValues(true),
                        m = form.getForm().getRecord();
                    if (!m) return;
                    for(var e in v) m.set(e, v[e]);
                    store.sync();
                }
            });
        }
    },

    bindProxy: function(store, cfg) {
        var binder = this, 
        proxy = store.getProxy(),
        reader = null, writer=null,
        url = cfg.proto+'://'+cfg.host+':'+cfg.port+'/'+cfg.url;
        if (proxy && cfg.keepreader) reader = proxy.getReader();
        if (proxy && cfg.keepwriter) writer = proxy.getWriter();
        if (!reader) reader = {type:'json', root:'data'};
        if (!writer && !cfg.rdonly) writer = {type:'json', root:'data', writeAllFields:false, allowSingle:false};

        var api={read:url+'&_act=read'};
        if (!cfg.rdonly){
            api.create = url+'&_act=create';
            api.update = url+'&_act=update';
            api.destroy = url+'&_act=destroy';
        }
        //for progress indicator
        store.on('write', binder.processWrite, binder, cfg);
        store.on('load', binder.processLoad, binder, cfg);
        //don't use this, will skip fire 'write' event(suspend)
        //if you wan't to use this, need add callback on operation!
        //store.batchUpdateMode = 'complete';
        store.setProxy({
            type: 'jsonp',
            timeout: cfg.timeout||300000,
            //batchActions: false,
            api: cfg.api?cfg.api:api,
            url: cfg.proto+'://'+cfg.host+':'+cfg.port+'/'+cfg.url,
            reader: cfg.reader?cfg.reader:reader,
            encodeRecords: binder.encodeRecords,
            //we need the input callback/scope to saved in reqeuest, so overwrite!
            createRequestCallback: binder.createRequestCallback,
            destroy: binder.doProxyDestroy,
            writer: cfg.writer?cfg.writer:writer
        });
        //for progress indicator
        store.getProxy().on('exception', binder.processErrors, binder, cfg);
        store.reloadParams = cfg.loadparams?{params:cfg.loadparams}:{params:{}};
        if (store.autoLoad||cfg.autoload){
            store.load(store.reloadParams);
            cfg.autoloaded = true;
        }

    },

    createStore: function(dbc, cfg, binder) {
        console.log('create db '+cfg.model+' for '+cfg.itemid);
        var app = 'MyApp';
        //create store by cfg.model
        //if (Ext.isObject(dbc.store)) return dbc.store;
        Ext.syncRequire(app+'.model.'+cfg.model);
        var store = Ext.create('Ext.data.Store', {
            model: app+'.model.'+cfg.model,
            modelId: cfg.model,
            autoLoad: false
        });
        /* should be done in bindGrid/bindForm/bindGridFrom...
        store.on('load', function(){
        var m = this.getAt(0);
        if (!m) return;
        dbc.loadRecord(m);
        });
        */
        if (dbc.isXType('grid')) dbc.reconfigure(store);else dbc.store = store;
        return store;
    },

    getReloadParams: function(dbc) {
        if (!dbc.store) return {};
        return dbc.store.reloadParams;
    },

    setReloadParams: function(dbc, params, append) {
        if (!dbc.store) return;
        dbc.store.reloadParams = append?Ext.apply(dbc.store.reloadParams, params):params;

    },

    bindOne: function(dbc, databind, serverip, rebind, container) {
        var me = this;
        if (!container) return;
        if (dbc.databinded && !rebind) return;
        //databind is Object, such as:
        //{itemid: 'mygrid', url: '/model/get.php', host:'localhost', port: 8080}
        //subconfig progress:{text|newin|bar,maskid,id|itemid(#processstatus default)}
        //avaliable config key:
        // itemid:    itemid of the bind target component:grid/form or both, must have same ancestor with me.
        // url:       basic url.
        // host:      hostip/name, canbe specified by my container's serverip.
        // port:
        // rdonly:    true/false, if true, onely load method supported.
        // store:     storeObject for override the current one.
        // mid:       remote model id. default=modelId or storeId.
        // keepproxy: don't override the proxy, just keep it in.
        // keepreader:use proxy's origin reader instead of create one.
        // keepwriter:use proxy's origin writer instead of create one.
        // ignore:    object, key as event name or itemid.click want to skip bind. 
        // autoload:  load data after bind
        // lodaparams:params for reload/lode store.
        // failpolicy:object for config actions after operation fail.
        //     read:      when read fail, default: nothing.
        //     update:    when update fail, default: reload.
        //     destroy:   when destroy fail, default: reload.
        //     create:    when create fail, default: destroy.
        // progress:  object for configurating the progress indicator, valid key:
        //     id:        the indicator show in getCmp(id), must be a container, #processstatus is default.
        //     itemid:    the indicator show in dbc.down(#itemid), must be a container, #processstatus is default, and this prior to 'id'.
        //     maskxtype: the component of me.up(<maskxtype>) for masking.
        //     maskid:    the component to show mask, default is my container.
        //     type:      text|newin|bar, the indicator is a text|new window|progressbar mode, bar is default.
        //     read:      $type, specify indicator for reading.
        //     update:    $type, specify indicator for updateing.
        //     create:    $type, specify indicator for creating.
        //     destroy:   $type, specify indicator for destroy.
        //     nomaks:    true, for don't show masking.
        if (!Ext.isObject(databind)) return;
        if (!dbc) return;
        var cfg = Ext.apply({
            itemid: dbc.itemId?dbc.itemId:(dbc.id?dbc.id:dbc.getXType()),//incase bind self.
            ignore: {},
            store: null,
            failpolicy: {},
            timeout: 300000,
            host: serverip?serverip:'localhost',
            proto: 'http',
            port: '80',
            pcfg: Ext.isObject(databind.progress)?databind.progress:{},
            container: container, //for masking or ...
            defpc: dbc.down('>toolbar')?dbc.down('>toolbar'):dbc,
            binder: me
        }, databind);

        var store = cfg.store?cfg.store:(Ext.isFunction(dbc.getStore)?dbc.getStore(dbc):dbc.store);
        if (!store || store.storeId == 'ext-empty-store') {
            if (!cfg.model){
                console.log('not store/model for item '+cfg.itemid+' config:'+cfg);
            }else{
                store = me.createStore(dbc, cfg);
            }
        }

        if (store){//just replace with old
            if (Ext.isFunction(dbc.bindStore)){
                dbc.bindStore(store);
            }else dbc.store = store;
        }

        if (!store){
            return;
        }

        console.log('binding '+cfg.itemid);
        console.log(cfg);

        cfg.store = store;
        cfg.dbc = dbc;
        /*
        if (dbc.isXType('grid')){
        cfg.bindtype = 'grid';
        //default progress container, if no '>#progressstatus'
        cfg.defpc = dbc.down('>toolbar')?dbc.down('>toolbar'):dbc;
        me.bindGrid(dbc, store, cfg);
        var form = null;
        if (cfg.bindform && (form = dbc.up().down('#'+cfg.bindform))){
            //form.action using form as default progressor
            cfg.deffpc = form;
            cfg.bindtype = 'gridform';
            me.bindGridForm(dbc, form, store, cfg);
        }
    }else if (dbc.isXType('form')){
        cfg.defpc = dbc;
        cfg.bindtype = 'form';
        me.bindForm(dbc, store, cfg);
    }
    */
    if (cfg.keepproxy) return; //or replace proxy indeed?
    cfg.modelId = store.modelId?store.modelId:store.storeId;
    cfg.mid = cfg.mid||cfg.modelId;
    cfg = Ext.applyIf(cfg, {
        url: 'models/get.php?mid='+cfg.mid+'&debug=global:DBG',
        storeid: store.storeId
    });
    me.bindProxy(store, cfg);
    dbc.databinded = cfg;
    },

    bindGroup: function(component, databinds, serverip, rebind) {
        //alert('bind group for '+component.getXType());
        //bind data proxy for component and all it's children specified by itemid.
        var me = this;//controller.
        if (!component) return;
        if (!Ext.isArray(databinds)){//just for me, container is also me.
            me.bindOne(component, databinds, serverip, rebind, component);
            return;
        }
        var dbconfig = databinds?databinds:component.databind;
        //databinds is an array like, usually inited in component, or in bindAll call.
        //see bindOne.
        if (!dbconfig) return;

        for(var i=0;i<dbconfig.length;i++){
            if (dbconfig[i].itemid){
                var dbc=component.down('#'+dbconfig[i]);
                if (dbc) me.bindOne(dbc, dbconfig[i], serverip, rebind, component);
                else console.log('fail to bind component: '+dbconfig[i].itemid+', item not found!');
            }else{//bind me
                me.bindOne(component, dbconfig[i], serverip, rebind, component);
            }
        }

    },

    onControllerAfterRenderStub: function() {

    },

    init: function() {
        this.control({
            "form": {
                afterrender: this.onFormAfterRender
            },
            "gridpanel": {
                afterrender: this.onGridpanelAfterRender,
                activate: this.onGridpanelActivate
            }
        });

    }

});
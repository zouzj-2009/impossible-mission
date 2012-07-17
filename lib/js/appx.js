Ext.Class.registerPreprocessor('checkstore',
    function (cls, data) {
        if (Ext.isString(data.store) && !Ext.isObject(cls.store)) {
			if (!data.store.match(/\.store\./)){
					console.log(data.$className+': '+data.store);
			}
        }
    }, true);
Ext.Class.setDefaultPreprocessorPosition('checkstore', 'first');

Ext.override(Ext.app.Controller, {
checknamespace: function(ns, type, refs){
	if (!refs) return [];
	var regexp = new RegExp('\\.'+type+'\\.');
	for(var i=0; i<refs.length; i++){
		if (refs[i].match(regexp)) continue; //already in ns
		refs[i] = ns+'.'+type+'.'+refs[i];
	}
	console.log(refs);
	return refs;
},

constructor: function(config) {
if (config && Ext.isObject(config.application)){
	var cname = this.self.getName(),
		appname = config.application.name;	
		ns = cname.replace(/\.controller\..*/, '');
	if (cname.match(/\.controller\./) && ns != appname){
		console.log(appname+' import controller: '+cname);
		if (this.loadpath) Ext.Loader.setPath(ns, this.loadpath);
		else Ext.Loader.setPath(ns, '../../'+ns.replace(/\.|_/,'/')+'/app');
		//check ns for config.views/stores/models
		if (this.views) this.views = this.checknamespace(ns, 'view', this.views);
		if (this.stores) this.stores = this.checknamespace(ns, 'store', this.stores);
		if (this.models) this.models = this.checknamespace(ns, 'model', this.models);
	}	
}
this.callOverridden(arguments);	
var views = this.views;
}
});
Ext.override(Ext.app.Application, {
constructor: function(config) {
var libs = config.requires||[],
	controllers = config.controllers||[];
/*
if (config.libmodels){
	config.models = config.libmodels.concat(config.models?config.models:[]);
	libs = libs.concat(config.libmodels);
}
if (config.libstores){
	config.stores = config.libstores.concat(config.stores?config.stores:[]);
	libs = libs.concat(config.libstores);
}
if (config.libviews){
	config.views = config.libviews.concat(config.views?config.views:[]);
	libs = libs.concat(config.libviews);
}
*/
this.uselibs = [].concat(libs);
libs = ['lib_core.controller.DBinder', 'lib_core.controller.Localizer', 'ui_common.controller.Login'].concat(libs);
//Ext.Loader.loadScript('../../lib/js/lang.zh_cn.js');
for(var i=0; i<libs.length; i++){
	var n = libs[i].split(/\.(view|controller|store|model)\./),
		path = n[0],
		type = n[1],
		name = n[2];
	if (!path) continue;
	if (!type) continue;
	if (!name) continue;
	Ext.ClassManager.setNamespace(path+'.'+type, {});
	Ext.Loader.setPath(path, '../../'+path.replace(/\.|_/,'/')+'/app');
	if (type == 'controller'){ 
		controllers.push(libs[i]);
//		Ext.Loader.loadScript('../../'+path.replace(/\.|_/, '/')+'/js/lang.zh_cn.js');
	}
}
config.controllers = controllers;
//Ext.require(libs);
this.callOverridden(arguments);	
}
});

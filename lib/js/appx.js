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

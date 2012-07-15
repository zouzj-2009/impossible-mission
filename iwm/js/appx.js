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
libs = config.requires;
for(var i=0; i<libs.length; i++){
	var n = libs[i].split('.');
	var path = n[0];
	var type = n[1];
	var name = libs[i].replace(path+'.'+type+'.', '');
	if (!path) continue;
	if (!type) continue;
	if (!name) continue;
	Ext.ns(path+'.'+type);
	Ext.Loader.setPath(path, '../'+path+'/app');
	if (type == 'controller') controllers.push(libs[i]);
}
config.controllers = controllers;
//Ext.require(libs);
this.callOverridden(arguments);	
}
});

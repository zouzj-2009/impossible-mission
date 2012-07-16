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
    requires: [
        'ui_common.controller.DataIndicator'
    ],

    models: [
        'netconfig',
        'iscsiconn',
        'pciinfo',
        'targetlist',
        'lunmap',
        'access',
        'virtportal',
        'scsidisk',
        'scsihost',
        'syslog',
        'etherspeed',
        'glunmap',
        'dnsgw',
        'gvirtportal',
        'niclist',
        'logfiles',
        'hostsetting',
        'license',
        'timezone',
        'login',
        'language'
    ],
    stores: [
        'networkinfo',
        'netconfig',
        'physicinfo',
        'iscsiconn',
        'sysinfo',
        'targetlist',
        'LunMap',
        'Access',
        'VirtPortal',
        'DiskList',
        'ScsiHost',
        'SysLog',
        'LinkSpeed',
        'General',
        'language'
    ],
    views: [
        'MainView',
        'BigIconBtn',
        'SystemInfo',
        'NetworkSetting',
        'iSCSISetting',
        'DiskSetting',
        'SystemMaintain',
        'NetMaskField',
        'TargetListField',
        'NetConfig',
        'VirtPortal',
        'DiskMgmt',
        'SysMaintain',
        'LinkSpeedBar',
        'LunMap',
        'changepassword'
    ],
    autoCreateViewport: true,
    name: 'app_iwm',
    controllers: [
        'MainMenu'
    ],
    defaultlang: 'zh_cn',

    launch: function() {

    }

});

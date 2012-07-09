/*
 * File: app/model/login.js
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

Ext.define('MyApp.model.login', {
    extend: 'Ext.data.Model',

    fields: [
        {
            name: 'username'
        },
        {
            convert: function(v, rec) {
                //todo: encrypt password
                if (v)
                return hex_md5(v);
                else
                return v;
            },
            name: 'password'
        },
        {
            name: 'logingon'
        },
        {
            name: 'language'
        }
    ]
});
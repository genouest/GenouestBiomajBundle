/*
 * Copyright 2011 Anthony Bretaudeau <abretaud@irisa.fr>
 *
 * Licensed under the CeCILL License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt
 *
 */

function reloadBiomajDbList(idfield, dbType, dbformat, filterall, cleanup, defaultBank) {
    defaultBank = (typeof defaultBank == "undefined")?'':defaultBank;
    
    defaultBank = defaultBank.replace(/\*/g, '.*');
    defaultBank = defaultBank.replace(/\?/g, '.');
    
    // dbtype can be a string or an array
    if (jQuery.isArray(dbType)) {
        for (type in dbType) {
            dbType[type] = dbType[type].replace('/', '___')
        }
        types = dbType.join('|')
    }
    else {
        types = dbType.replace('/', '___');
    }

    jQuery.getJSON(urlBiomajDbListAjax + types + '/' + dbformat + '/' + cleanup, function(data) {
        updateBiomajDbList(idfield, data, defaultBank);
    });
}

function updateBiomajDbList(idField, json, defaultBank) {
    newList = '';

    if (json) {
        for(var i = 0; i < json.tree.length; i++) {
            newList += createBiomajDbListTree(json.tree[i], defaultBank);
        }
    }

    jQuery(idField).html(newList);
}

function createBiomajDbListTree(json, defaultBank) {
    opts = "";
    if (json.type == 'group') {
        opts += "<optgroup label=\"" + json.path + "\">";
        for(var i = 0; i < json.dbChildren.length; i++) {
            opts += createBiomajDbListTree(json.dbChildren[i], defaultBank);
        }
        opts += "</optgroup>";
    }
    else if (json.type == 'item') {
        var regex = new RegExp(defaultBank);
        
        opts += "<option value=\"" + json.path + "\"";

        if (defaultBank && regex.test(json.path)) {
            opts += " selected=\"selected\"";
        }
        
        opts += ">" + json.displayName + "</option>";
    }

    return opts;
}


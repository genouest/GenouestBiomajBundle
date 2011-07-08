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

function reloadBiomajDbList(idfield, dbType, dbformat, filterall, cleanup) {
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
        updateBiomajDbList(idfield, data);
    });
}

function updateBiomajDbList(idField, json) {
    newList = '';

    if (json) {
        for(var i = 0; i < json.tree.length; i++) {
            newList += createBiomajDbListTree(json.tree[i]);
        }
    }

    if (newList.replace(/\r|\n|\r\n/g, '') != jQuery(idField).html().replace(/\r|\n|\r\n| selected=\"selected\"/g, '')) {
        selectedPath = jQuery(idField).val();
        newList = newList.replace("value=\""+selectedPath+"\"", "value=\""+selectedPath+"\" selected=\"selected\"");
        jQuery(idField).html(newList);
    }
}

function createBiomajDbListTree(json) {
    opts = "";
    if (json.type == 'group') {
        opts += "<optgroup label=\"" + json.path + "\">";
        for(var i = 0; i < json.dbChildren.length; i++) {
            opts += createBiomajDbListTree(json.dbChildren[i]);
        }
        opts += "</optgroup>";
    }
    else if (json.type == 'item') {
        opts += "<option value=\"" + json.path + "\">" + json.displayName + "</option>";
    }

    return opts;
}


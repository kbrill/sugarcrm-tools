/**
 * Created with JetBrains PhpStorm.
 * User: kenbrill
 * Date: 7/1/12
 * Time: 6:09 PM
 * Version: 1.0
 *
 */
var index_counter = 1;
var index_limit = 15;

function selectMostUsed() {
    var optionsToSelect = ['id', 'id_c', 'name', 'user_name', 'first_name', 'last_name', 'deleted', 'date_entered'];
    var select = document.getElementById('columns');

    for (var i = 0, l = select.options.length, o; i < l; i++) {
        o = select.options[i];
        if (optionsToSelect.indexOf(o.value) != -1) {
            o.selected = true;
        }
    }
}

function resetSelect(selectBox) {
    // have we been passed an ID
    if (typeof selectBox == "string") {
        selectBox = document.getElementById(selectBox);
    }

    for (i = 0; i < selectBox.options.length; i++) {
        selectBox.options[i].selected = false;
    }
}

function selectAll(selectBox,selectAll) {
    // have we been passed an ID
    if (typeof selectBox == "string") {
        selectBox = document.getElementById(selectBox);
    }

    // is the select box a multiple select box?
    if (selectBox.type == "select-multiple") {
        for (var i = 0; i < selectBox.options.length; i++) {
            selectBox.options[i].selected = selectAll;
        }
    }
}

function selectAllColumns(selectBox,searchString) {
    // have we been passed an ID
    resetSelect(selectBox);
    if (typeof selectBox == "string") {
        selectBox = document.getElementById(selectBox);
    }

    // is the select box a multiple select box?
    if (selectBox.type == "select-multiple") {
        for (var i = 0, l = selectBox.options.length, o; i < l; i++) {
            o = selectBox.options[i];
            if (stristr(o.value,searchString)!=false) {
                o.selected = true;
            }
        }
    }
}

function stristr (haystack, needle, bool) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfxied by: Onno Marsman
    // *     example 1: stristr('Kevin van Zonneveld', 'Van');
    // *     returns 1: 'van Zonneveld'
    // *     example 2: stristr('Kevin van Zonneveld', 'VAN', true);
    // *     returns 2: 'Kevin '
    var pos = 0;

    haystack += '';
    pos = haystack.toLowerCase().indexOf((needle + '').toLowerCase());
    if (pos == -1) {
        return false;
    } else {
        if (bool) {
            return haystack.substr(0, pos);
        } else {
            return haystack.slice(pos);
        }
    }
}

function submitPage() {
    SUGAR.ajaxUI.showLoadingPanel();
    document.sql.submit();
}

function exportQueryToCSV() {
    document.sql.command.value="export_csv";
    document.sql.to_pdf.value="1";
    document.sql.submit();
}

function chooseNewTable(commandValue) {
    SUGAR.ajaxUI.showLoadingPanel();
    document.sql.table.value=document.sql.tableselect.options[document.sql.tableselect.selectedIndex].value;
    document.sql.currentTable.value=document.sql.tableselect.options[document.sql.tableselect.selectedIndex].value;
    document.sql.command.value=commandValue;
    document.sql.submit();
}

function matchParenthesis(match) {
    var items = document.getElementsByClassName("parenthesis");
    divHolder=document.getElementById('lineHolder');
    divHolder.innerHTML = "";
    connectDivs=0;
    div1="";
    div2="";
    for(var i = 0; i < items.length; i++) {
        var item = items[i];
        if(match == item.getAttribute("matchIndex")) {
            item.style.backgroundColor = "#FFFF33";
            if(connectDivs==0) {
                div1=item;
                connectDivs=1;
            } else {
                div2=item;
            }
        } else {
            item.style.backgroundColor = "transparent";
        }
    }
    if(div1!="" && div2!="") {
        connect(div1, div2, "#0F0", 10);
    } else {
        if(div1=="" && div2!="") {
            div2.style.backgroundColor = "#FF0000";
            div2.title="ERROR";
        }
        if(div1!="" && div2=="") {
            div1.style.backgroundColor = "#FF0000";
            div1.title="ERROR";
        }
    }
}

function getOffset( el ) { // return element top, left, width, height
    var _x = 0;
    var _y = 0;
    var _w = el.offsetWidth|0;
    var _h = el.offsetHeight|0;
    while( el && !isNaN( el.offsetLeft ) && !isNaN( el.offsetTop ) ) {
        _x += el.offsetLeft - el.scrollLeft;
        _y += el.offsetTop - el.scrollTop;
        el = el.offsetParent;
    }
    return { top: _y, left: _x, width: _w, height: _h };
}

function connect(div1, div2, color, thickness) { // draw a line connecting elements
    var off1 = getOffset(div1);
    var off2 = getOffset(div2);
    // bottom right
    var x1 = off1.left -5;
    var y1 = off1.top + off1.height;
    // top right
    var x2 = off2.left -5;
    var y2 = off2.top;
    // distance
    var length = Math.sqrt(((x2-x1) * (x2-x1)) + ((y2-y1) * (y2-y1)));
    // center
    var cx = ((x1 + x2) / 2) - (length / 2);
    var cy = ((y1 + y2) / 2) - (thickness / 2);
    // angle
    var angle = Math.atan2((y1-y2),(x1-x2))*(180/Math.PI);
    // make hr
    var htmlLine = "<div class='fancyLine' style='padding:0px; margin:0px; height:" + thickness + "px; background-color:" + color + "; line-height:1px; position:absolute; left:" + cx + "px; top:" + cy + "px; width:" + length + "px; -moz-transform:rotate(" + angle + "deg); -webkit-transform:rotate(" + angle + "deg); -o-transform:rotate(" + angle + "deg); -ms-transform:rotate(" + angle + "deg); transform:rotate(" + angle + "deg);' />";
    //
    // alert(htmlLine);
    divHolder=document.getElementById('lineHolder');
    divHolder.innerHTML = htmlLine;
}

function toggleToTextArea() {
    document.getElementById("highlightedsql").style.display ="none";
    document.getElementById("textarea").style.display ="block";
}

function insertAtCursor(myField, myValue) {
//IE support
    if (document.selection) {
        myField.focus();
        sel = document.selection.createRange();
        sel.text = myValue;
    }
//MOZILLA/NETSCAPE support
    else if (myField.selectionStart || myField.selectionStart == '0') {
        var startPos = myField.selectionStart;
        var endPos = myField.selectionEnd;
        myField.value = myField.value.substring(0, startPos)
            + myValue
            + myField.value.substring(endPos, myField.value.length);
    } else {
        myField.value += myValue;
    }
}
// calling the function
//insertAtCursor(document.formName.fieldName, 'this value');

function processInsert(type) {
    var textarea = document.getElementById("sqlarea");
    var selectionbox = document.getElementById("columnsID");
    var table = document.getElementById("tableselectID").value;
    var cPos = getCaretPosition(textarea);
    var afterWhere = true;
    if(stripos(textarea.value,'where')==false) {
        afterWhere=false;
    } else {
        afterWhere = stripos(textarea.value,'where')<cPos;
    }
    var selValues = [];
    var finalInput = "";
    for(i=0; i < selectionbox.length; i++){
        if(selectionbox.options[i].selected){
            selValues.push(selectionbox.options[i].value);
        }
    }
    switch(type)
    {
        case 'select':
            if(selValues.length != 0) {
                finalSelect = selValues.join(', ');
            } else {
                finalSelect = "*"
            }
            textarea.value='SELECT '+finalSelect+' FROM '+table+' WHERE 1=1';
            break;
        case 'update':
            if(selValues.length != 0) {
                finalUpdate = selValues.join("='VALUE', ");
                textarea.value="UPDATE "+table+" SET "+finalUpdate+" WHERE id='ID_TO_UPDATE'";
            } else {
                alert(SUGAR.language.get('Administration', 'LBL_SELECT_COLUMNS_FIRST'));
            }
            break;
        case 'delete':
            textarea.value="DELETE FROM "+table+" WHERE id='ID_TO_UPDATE'";
            break;
        case 'insert':
            if(selValues.length != 0) {
                finalInsert = selValues.join(', ');
                temp = "'"+selValues.join("' , '")+"'";
                temp2 = temp.replace("'id'","'"+guid()+"'");
                finalValues=temp2.replace("'id_c'","'"+guid()+"'");
                textarea.value="INSERT INTO "+table+" ("+finalInsert+") VALUES("+finalValues+")";
            } else {
                alert(SUGAR.language.get('Administration', 'LBL_SELECT_COLUMNS_FIRST'));
                break;
            }
            break;
        case 'copy':
            agree=confirm(SUGAR.language.get('Administration', 'LBL_PREPEND_TABLE_NAME'));
            if(agree) {
                if(afterWhere) {
                    finalCopy = table+"."+selValues.join("='VALUE', "+table+".")+"='VALUE'";
                } else {
                    finalCopy = table+"."+selValues.join(', '+table+".");
                }
            } else {
                if(afterWhere) {
                    finalCopy = selValues.join("='VALUE', ")+"='VALUE'";
                } else {
                    finalCopy = selValues.join(', ');
                }
            }
            insertAtCursor(textarea,finalCopy);
            break;
        case 'clear':
            textarea.value="";
            break;
        case 'users':
            current_value = document.getElementById("user_switch").value
            if(current_value == "User IDs") {
                document.getElementById("user_switch").value = SUGAR.language.get('Administration', 'LBL_USER_NAMES');
                document.sql.user_ids.value = "1";
            } else {
                document.getElementById("user_switch").value = SUGAR.language.get('Administration', 'LBL_USER_IDS');
                document.sql.user_ids.value = "0";
            }
            break;
        case 'dates':
            current_value = document.getElementById("date_switch").value
            if(current_value == "DB Dates") {
                document.getElementById("date_switch").value = SUGAR.language.get('Administration', 'LBL_USER_DATES');
                document.sql.user_dates.value = "1";
            } else {
                document.getElementById("date_switch").value = SUGAR.language.get('Administration', 'LBL_DB_DATES');
                document.sql.user_dates.value = "0";
            }
            break;
        case 'columns':
            current_value = document.getElementById("column_switch").value
            if(current_value == "Translated") {
                document.getElementById("column_switch").value = SUGAR.language.get('Administration', 'LBL_UNTRANSLATED');
                document.sql.translated_column_names.value = "0";
            } else {
                document.getElementById("column_switch").value = SUGAR.language.get('Administration', 'LBL_TRANSLATED');
                document.sql.translated_column_names.value = "1";
            }
            break;
    }
}

function S4() {
    return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
}
function guid() {
    return (S4()+S4()+"-"+S4()+"-"+S4()+"-"+S4()+"-"+S4()+S4()+S4());
}

function getCaretPosition (ctrl) {
    var CaretPos = 0;
    // IE Support
    if (document.selection) {
        ctrl.focus ();
        var Sel = document.selection.createRange ();
        Sel.moveStart ('character', -ctrl.value.length);
        CaretPos = Sel.text.length;
    }
    // Firefox support
    else if (ctrl.selectionStart || ctrl.selectionStart == '0')
        CaretPos = ctrl.selectionStart;
    return (CaretPos);
}

function stripos (f_haystack, f_needle, f_offset) {
    // http://kevin.vanzonneveld.net
    // +     original by: Martijn Wieringa
    // +      revised by: Onno Marsman
    // *         example 1: stripos('ABC', 'a');
    // *         returns 1: 0
    var haystack = (f_haystack + '').toLowerCase();
    var needle = (f_needle + '').toLowerCase();
    var index = 0;

    if ((index = haystack.indexOf(needle, f_offset)) !== -1) {
        return index;
    }
    return false;
}

function isBrOrWhitespace(node) {
    return node && ( (node.nodeType == 1 && node.nodeName.toLowerCase() == "br") ||
        (node.nodeType == 3 && /^\s*$/.test(node.nodeValue) ) );
}

function trimBrs(node) {
    while ( isBrOrWhitespace(node.firstChild) ) {
        node.removeChild(node.firstChild);
    }
    while ( isBrOrWhitespace(node.lastChild) ) {
        node.removeChild(node.lastChild);
    }
}
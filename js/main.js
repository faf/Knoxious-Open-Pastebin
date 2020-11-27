/*
 * This file is a part of Simpliest Pastebin.
 *
 * Copyright 2009-2019 the original author or authors.
 *
 * Licensed under the terms of the MIT License.
 * See the MIT for details (https://opensource.org/licenses/MIT).
 *
 */

var tab = '    ';

function catchTab(evt) {
    var t = evt.target;
    var ss = t.selectionStart;
    var se = t.selectionEnd;

    if (evt.keyCode == 9) {
        evt.preventDefault();
        if (ss != se && t.value.slice(ss, se).indexOf("\n") != -1) {
            var pre = t.value.slice(0, ss);
            var sel = t.value.slice(ss, se).replace(/\n/g, "\n" + tab);
            var post = t.value.slice(se, t.value.length);
            t.value = pre.concat(tab).concat(sel).concat(post);
            t.selectionStart = ss + tab.length;
            t.selectionEnd = se + tab.length;
        } else {
            t.value = t.value.slice(0, ss).concat(tab).concat(t.value.slice(ss, t.value.length));
            if (ss == se) {
                t.selectionStart = t.selectionEnd = ss + tab.length;
            } else {
                t.selectionStart = ss + tab.length;
                t.selectionEnd = se + tab.length;
            }
        }
    } else if (evt.keyCode == 8 && t.value.slice(ss - 4, ss) == tab) {
        evt.preventDefault();
        t.value = t.value.slice(0, ss - 4).concat(t.value.slice(ss, t.value.length));
        t.selectionStart = t.selectionEnd = ss - tab.length;
    } else if (evt.keyCode == 46 && t.value.slice(se, se + 4) == tab) {
        evt.preventDefault();
        t.value = t.value.slice(0, ss).concat(t.value.slice(ss + 4, t.value.length));
        t.selectionStart = t.selectionEnd = ss;
    } else if (evt.keyCode == 37 && t.value.slice(ss - 4, ss) == tab) {
        evt.preventDefault();
        t.selectionStart = t.selectionEnd = ss - 4;
    } else if (evt.keyCode == 39 && t.value.slice(ss, ss + 4) == tab) {
        evt.preventDefault();
        t.selectionStart = t.selectionEnd = ss + 4;
    }
}

function toggleAdminTools() {
    if (document.getElementById('hiddenAdmin').style.display == 'block') {
        document.getElementById('hiddenAdmin').style.display = 'none';
    } else {
        document.getElementById('hiddenAdmin').style.display = 'block';
    }
    return false;
}

function toggleInstructions() {
    if (document.getElementById('instructions').style.display == 'block') {
        document.getElementById('instructions').style.display = 'none';
    } else {
        document.getElementById('instructions').style.display = 'block';
    }
    return false;
}

function toggleWrap() {
    var n = 0;
    var pres = document.getElementsByTagName('pre');
    for (n in pres) {
        if (pres[n].style != null && (pres[n].style.whiteSpace == 'pre' || pres[n].style.whiteSpace == '')) {
            pres[n].style.whiteSpace = 'pre-wrap';
        } else if (pres[n].style != null) {
            pres[n].style.whiteSpace = 'pre';
        }
    }
    return false;
}

function toggleExpand() {
    if (document.getElementById('lineNumbers').style.maxHeight != 'none') {
        document.getElementById('lineNumbers').style.maxHeight = 'none';
        document.getElementById('lineNumbers').style.width = 'auto';
    } else {
        document.getElementById('lineNumbers').setAttribute('style', '');
    }
    return false;
}

function toggleStyle() {
    if (document.getElementById('orderedList').getAttribute('class') == 'monoText' || document.getElementById('orderedList').getAttribute('class') == '') {
        document.getElementById('orderedList').setAttribute('class', 'plainText');
    } else {
        document.getElementById('orderedList').setAttribute('class', 'monoText');
    }
    return false;
}

function submitPost(targetButton, message) {
    var disabledButton = document.createElement('input');
    var parentContainer = document.getElementById('submitContainer');
    disabledButton.setAttribute('value', message);
    disabledButton.setAttribute('type', 'button');
    disabledButton.setAttribute('disabled', 'disabled');
    disabledButton.setAttribute('id', 'dummyButton');
    targetButton.style.display = 'none';
    parentContainer.appendChild(disabledButton);
    return true;
}

function copyAsText(el) {
    var copy = document.createElement('textarea');
    copy.value = document.getElementById(el).innerText || document.getElementById(el).textContent;
    return copyValue(copy);
}

function copyAsHTML(el) {
    var copy = document.createElement('input');
    copy.setAttribute('value', document.getElementById(el).innerHTML);
    return copyValue(copy);
}

function copyLink(wrap) {
    var copy = document.createElement('input');
    copy.setAttribute('value', document.getElementById(wrap).children[0].getAttribute('href'));
    return copyValue(copy);
}

function copyValue(el) {
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    return false;
}

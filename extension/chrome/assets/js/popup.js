/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var MIME_TYPE = 'text/plain';
window.URL = window.URL || window.webkitURL;


function downloadFile(element, fileName, content) {
    debugger;
    // revoke previous download path
    window.URL.revokeObjectURL(element.href);
    element.href = window.URL.createObjectURL(new Blob([ content ],
						       { type: MIME_TYPE }));
    
    chrome.runtime.sendMessage({
        url: element.href,
        filename: fileName
    });
    
}

function getElements() {
    return {
        button: $('button.generate'),
        downloader: $('a.downloader').get(0),
        model: {
            baseUrl: $('[id="model.baseUrl"]'),
            pageUrl: $('[id="model.pageUrl"]'),
            pageName: $('[id="model.pageName"]'),
            selector: $('[id="model.selector"]'),
	    trim: $('[id="model.trim"]')
        }
    };
}
function processActivePage(input) {
    return $.Deferred(function(defer) {
        chrome.tabs.query({ active: true,
			    currentWindow: true },
			  function(tabs) {
			      chrome.tabs.sendMessage(tabs[0].id, { input: input }, defer.resolve);
			  });
    }).promise();
}
2
function validate(element) {
    var valid = false;

    if (element) {
        var parentNode = element.parent().removeClass();

        if (element.val() === '') {
            parentNode.addClass('error');
        }
        else {
            valid = true;
        }
    }

    return valid;
}
function getOptions(url, callback) {
    console.log("getOptions: url",url);
    chrome.storage.sync.get([url],
			    (options) => {
				console.log("options", options);
				if (chrome.runtime.lastError) {
				    callback(url, null);
				} else {
				    callback(url, options);
				}
			    });
}

function saveOptions(url, options) {
    try {
	var items = {};
	items[url] = options;
	chrome.storage.sync.set(items);
	console.log("save",items);
    } catch (e) {
	debugger;
	console.log(e);
    }
}

function getCurrentTabUrl(callback) {
    var queryInfo = {
	active: true,
	currentWindow: true
    };

    chrome.tabs.query(queryInfo, (tabs) => {
	callback(tabs);
    });
}


$(document).ready(function() {
    debugger;
    var elements = getElements();

    chrome.tabs.executeScript(null, {
        file: 'assets/js/generator.js'
    }, function(result) {
        if (!result || chrome.runtime.lastError) {
	    notify.error('Unable to access page contents.');
	    elements.button.get(0).disabled = true;
	    console.log('error.generator', result, chrome.runtime.lastError);
	    return;
        }
    });

    
    getCurrentTabUrl(function(tabs)  {
	debugger;
	getOptions(tabs[0].url, function(url, jsonModel) {
	    debugger;
	    if (jsonModel
		&&
		!(Object.keys(jsonModel).length === 0 && jsonModel.constructor === Object)) {
		$('[id="model.baseUrl"]').val(jsonModel[tabs[0].url].baseUrl);
		$('[id="model.pageUrl"]').val(jsonModel[tabs[0].url].pageUrl);
		$('[id="model.pageName"]').val(jsonModel[tabs[0].url].pageName);
		$('[id="model.selector"]').val(jsonModel[tabs[0].url].selector);
		$('[id="model.trim"]').val(jsonModel[tabs[0].url].trim);
	    } else {
		var parts = url.split('/');
		var base = parts[0] + '//' + parts[2];
		var url = '/';
		for (var i = 3; i < parts.length-1; i++) {
		    url += parts[i] + '/';
		}
		$('[id="model.baseUrl"]').val(base);
		$('[id="model.pageUrl"]').val(url)
	    }
	});
    })
    
    elements.button.click(function(e) {
	e.preventDefault();
	
	if (!validate(elements.model.baseUrl)) {
	    alert('baseUrl is required.');
	    return;
	}
	if (!validate(elements.model.pageName)) {
	    alert('pageName is required.');
	    return;
	}
	if (!validate(elements.model.selector)) {
	    alert('selector is required.');
	    return;
	}

	var model = {
	    baseUrl: elements.model.baseUrl.val().replace(/\s+/g, ''),
	    pageUrl: elements.model.pageUrl.val().replace(/\s+/g, ''),
	    pageName: elements.model.pageName.val().replace(/\s+/g, ''),
	    extendName: elements.model.pageName.val().charAt(0).toLowerCase()
		+ elements.model.pageName.val().slice(1) + 'Extend',
	    selector: elements.model.selector.val(),
	    trim: document.getElementById('model.trim').checked
	};
	debugger;
	
	getCurrentTabUrl(function(url)  {
	    saveOptions(url[0].url, model);	
	});
	
	processActivePage(model).always(function(context) {
	    context.manifest = chrome.runtime.getManifest();
	    context.date = new Date();
	    
	    var fileName = context.input.pageName + ".php";
	    $.get('assets/template/php.hbs', function (data) {
		try {
		    debugger;
		    var template=Handlebars.compile(data);
		    var result = template(context);
		    downloadFile(elements.downloader, fileName, result);
		} catch(e) {
		    debugger;
		}
	    });
	});//processAcitive

    });//elements.button.click
});//document.ready


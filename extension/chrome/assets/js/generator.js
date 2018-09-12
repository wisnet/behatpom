/*
 * Copyright (c) Wisnet
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
window.BPOEGLoaded = !!window.BPOEG;

window.BPOEG=(function() {
    /*
     * Gets XPATH string representation for given DOM element, stolen from firebug_lite.
     * example: /html/body/div[2]/div[3]/div[4]/div/div[2]/div/div/div/div/div/div[2]/p
     *
     * To keep paths short, the path generated tries to find a parent element with a unique
     * id, eg: //[@id="dom-element-with-id"]/div[2]/p
     */

    var getElementXPath = function(element) {
        var paths = [], index, nodeName, tagName, sibling, pathIndex;

        for (; element && element.nodeType == 1; element = element.parentNode) {
            index = 0,
            nodeName = element.nodeName;

            if (element.id) {
                tagName = element.nodeName.toLowerCase();
                paths.splice(0, 0, '*[@id="' + element.id + '"]');
                return "//" + paths.join("/");
            }

            for (sibling = element.previousSibling; sibling; sibling = sibling.previousSibling) {
                if (sibling.nodeType != 1)
                    continue;

                if (sibling.nodeName == nodeName)
                    ++index;
            }

            tagName = element.nodeName.toLowerCase();
            pathIndex = (index ? "[" + (index + 1) + "]" : "");
            paths.splice(0, 0, tagName + pathIndex);
        }

        return paths.length ? "/" + paths.join("/") : null;
    };
    
    function fetchAttrs(node) { // getting attributes object for element
	return node && Array.prototype.reduce.call(node.attributes, function(list, attribute) {
            list[attribute.name] = attribute.value;
            return list;
        }, {}) || {};
    }

    function traverseElement(element, data) {
	if (element.nodeType === Node.TEXT_NODE)
            return data; // skipping text elements

	var tagName = element.tagName.toLowerCase();

	
	if (tagName === 'input'
	    &&
	   element.type.toLowerCase()==='submit') {
	}
	var xpath = getElementXPath(element);
	
	var attrs = fetchAttrs(element);
	var prefix =  "//" + tagName + " "; // build element path
	
	if (Object.keys(attrs).length !== 0){
            prefix += "[" + Object.keys(attrs).map((value, index) => {
                return "@" + value + ' = "' + attrs[value] + '"'
            }).join(" and ") + "]"; // append arguments
	}

	xpath = xpath.replace(/"/g, '\'');
	prefix = prefix.replace(/"/g, '\'');
	switch (tagName) {
        case "input":

            if(element.type.toLowerCase()==='text'
	       || element.type.toLowerCase()==='email'
	       || element.type.toLowerCase()==='password'
	       || element.type.toLowerCase()==='number') {
                data.textBoxes.push({"prefix": prefix,
				     "attrs": attrs,
				     "xpath": xpath});
            } else if (element.type.toLowerCase() === 'submit'
		       ||
		       element.type.toLowerCase() === 'radio'
		       ||
		       element.type.toLowerCase() === 'checkbox') {
                data.buttons.push({"prefix": prefix,
				   "attrs": attrs,
				   "element": element,
				   "xpath": xpath});
	    }
            break;
        case "button":
	    var text = toCamelCase(element.innerText);
	    var rtn = resolveDuplicateName(data.labelNames,
					   text);
	   	    data.labelNames = rtn.names;
            data.buttons.push({"prefix": prefix,
			       "text": rtn.text,
			       "attrs": attrs,
			       "element": element,
			       "xpath": xpath});
            break;
        case "a":
	    var text = element.title ? element.title : element.text;
	    debugger;
	    //Some links have trailing spaces that we want
	    if (data.input.trim) {
		text = text.trim();		
	    }
            data.links.push({"prefix": prefix,
			     "text": text,
			     "xpath": xpath});
	    break;
	case "img":
	    data.images.push({"prefix": prefix,
			      "xpath": xpath});
	    break;
	case "span":
	    data.spans.push({"prefix": prefix,
			     "xpath": xpath});
	    break;
	case "checkbox":
	    data.checkboxes.push({"prefix": prefix,
				  "xpath": xpath});
	    break;
	    /*
	case "div":
	    data.divs.push({"prefix": prefix,
			    "xpath": xpath});
	    break;
	    */
	case "select":
	    data.selects.push({"prefix": prefix,
			       "attrs": attrs,
			       "xpath": xpath});
	    break;
	case "label":
	    var text = toCamelCase(element.innerText);
	    var rtn = resolveDuplicateName(data.labelNames,
					   text);
	    
	    data.labelNames = rtn.names;
	    data.labels.push({"prefix": prefix,
			      "text":  rtn.text,
			      "attrs": attrs,
			      "xpath": xpath});
	    break;
	case "textarea":
	    var text = toCamelCase(element.innerText);
	    data.textAreas.push({"prefix": prefix,
				"text":  text,
				"attrs": attrs,
				"xpath": xpath});
	    break;	    
	}
	return data;
    }
    function assignNamesToButtons(data) {
	var list = data.buttons;
	for (var i = 0; i < list.length; i++) {
	    if (list[i].label
		&&
		list[i].label.text) {
		rtn = resolveDuplicateName(data.labelNames,list[i].label.text);
		data.labelNames = rtn.names;
		list[i].label.text = rtn.text
		continue;
	    }
	    if (list[i].attrs
		&&
		list[i].attrs.value) {
		list[i].label = {};
		list[i].label.text = list[i].attrs.value;
		rtn = resolveDuplicateName(data.labelNames,list[i].label.text);
		data.labelNames = rtn.names;
		list[i].label.text = rtn.text
		continue;
	    }
	    rtn = resolveDuplicateName(data.labelNames,"button");
	    data.labelNames = rtn.names;
	    list[i].label={};
	    list[i].label.text = rtn.text;
	}
	
	return data;
    }
    /**
     * resolve duplicate names
     */
    function resolveDuplicateName(names, text) {
	var searching = false;
	var newText = {
	    orig: text,
	    cur: text,
	    num: 0
	};
	do {
	    searching = false;
	    for (var i = 0; i < names.length; i++) {
		
		if (names[i]
		    ===
		    newText.cur) {
		    
		    newText.num++;
		    newText.cur = newText.orig + '_' + newText.num;
		    searching = true;
		}
	    }
	    
	} while (searching);

	names.push(newText.cur);
	
	var rtn = {
	    names: names,
	    text: newText.cur
	};

	return rtn;
	
    }
    function toCamelCase(str) {
	str = str.replace(/[^a-zA-Z]/g, '')	
	return str
            .replace(/\s(.)/g, function($1) { return $1.toUpperCase(); })
            .replace(/\s/g, '')
            .replace(/^(.)/, function($1) { return $1.toLowerCase(); });
    }        
    function matchLabelsToElements(data, elements) {
	//Match label w/ Textbox
	for (var i = 0; i< data.labels.length; i++) {
	    for (var j = 0; j < elements.length; j++) {
		try {
		    if (   typeof data.labels[i].attrs === 'undefined'
			|| typeof data.labels[i].attrs['for'] === 'undefined'
			|| data.labels[i].attrs === null
			|| typeof elements[j].attrs === 'undefined'
			   ||
			   (typeof elements[j].attrs.id === 'undefined'
			    &&
			    typeof elements[j].attrs.name === 'undefined')
			|| elements[j].attrs === null) {
			continue;
		    }
		    if (elements[j].attrs.id) {
			if (data.labels[i].attrs['for'].toLowerCase()
			    ===
			    elements[j].attrs.id.toLowerCase()) {
			    elements[j].label = data.labels[i];
			}
			
		    } else {
			if (data.labels[i].attrs['for'].toLowerCase()
			    ===
			    elements[j].attrs.name.toLowerCase()) {
			    elements[j].label = data.labels[i];
			}
		    }
		} catch (e) {
		    debugger;
		}
	    }
	}
	return elements;
    }
    function getElementByXpath(path) {
	return document.evaluate(path, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
    }
    function getElements(data) {
	var elementsBySelector = getElementByXpath(data.input.selector );
	var elements = elementsBySelector.getElementsByTagName('*');
	
	for(var i = 0; i < elements.length; i++) {
            data = traverseElement(elements[i], data);
	}
	return data;
    }
    function useIdOrNameForLabelText(elements) {
	for (var i=0; i < elements.length; i++) {
	    var element = elements[i];
	    if (typeof element.label === 'undefined') {
		if (typeof element.attrs === 'undefined'
		    ||
		    (typeof element.attrs.id === 'undefined'
		     &&
		     typeof element.attrs.name === 'undefined')) {
		    continue;
		} else {
		    var label = {};
		    if (element.attrs.id) {
			label.text = element.attrs.id;
		    } else {
			label.text = element.attrs.name;
		    }
		    element.label = label;
		}

	    }
	}//for i
	return elements;
    }

    return {
	generate: function(input) {
	    var data= {
		labelNames: [],
		buttonNames: [],
		input: input,
 		textBoxes:[],
		buttons:[],
		links:[],
		spans:[],
		divs:[],
		images:[],
		selects:[],
		checkboxes:[],
		labels:[],
		textAreas:[]
	    };
	    debugger;	        
	    
	    data = getElements(data);

	    data.textBoxes = matchLabelsToElements(data, data.textBoxes);
	    data.textBoxes = useIdOrNameForLabelText(data.textBoxes);
		
	    data.selects = matchLabelsToElements(data, data.selects);
	    data.selects = useIdOrNameForLabelText(data.selects);

	    data.textAreas = matchLabelsToElements(data, data.textAreas);
	    data.textAreas = useIdOrNameForLabelText(data.textAreas);

	    data.buttons = matchLabelsToElements(data, data.buttons);
	    data.buttons = useIdOrNameForLabelText(data.buttons);
	    data  = assignNamesToButtons(data);
	    
            data.url = document.location.href;
            return data;
	}
    };
})();

if (!window.BPOEGLoaded) {
    chrome.runtime.onMessage.addListener(function(request, sender, sendResponse) {
        if (!sender.tab && request.input) {
	    var rtn = BPOEG.generate(request.input);
            sendResponse(rtn);
        }
    });
}


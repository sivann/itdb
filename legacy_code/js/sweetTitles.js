/*
Sweet Titles (c) Creative Commons 2005
http://creativecommons.org/licenses/by-sa/2.5/
Author: Dustin Diaz | http://www.dustindiaz.com
*/





Array.prototype.inArray = function (value) {
	var i;
	for (i=0; i < this.length; i++) {
		if (this[i] === value) {
			return true;
		}
	}
	return false;
};

function addEvent( obj, type, fn ) {
	if (obj.addEventListener) {
		obj.addEventListener( type, fn, false );
		EventCache.add(obj, type, fn);
	}
	else if (obj.attachEvent) {
		obj["e"+type+fn] = fn;
		obj[type+fn] = function() { obj["e"+type+fn]( window.event ); }
		obj.attachEvent( "on"+type, obj[type+fn] );
		EventCache.add(obj, type, fn);
	}
	else {
		obj["on"+type] = obj["e"+type+fn];
	}
}
	
var EventCache = function(){
	var listEvents = [];
	return {
		listEvents : listEvents,
		add : function(node, sEventName, fHandler){
			listEvents.push(arguments);
		},
		flush : function(){
			var i, item;
			for(i = listEvents.length - 1; i >= 0; i = i - 1){
				item = listEvents[i];
				if(item[0].removeEventListener){
					item[0].removeEventListener(item[1], item[2], item[3]);
				};
				if(item[1].substring(0, 2) != "on"){
					item[1] = "on" + item[1];
				};
				if(item[0].detachEvent){
					item[0].detachEvent(item[1], item[2]);
				};
				item[0][item[1]] = null;
			};
		}
	};
}();
addEvent(window,'unload',EventCache.flush);






/*********************************************************/


var tipdelay=100; 


var sweetTitles = {
	xCord : 0,								// @Number: x pixel value of current cursor position
	yCord : 0,								// @Number: y pixel value of current cursor position
	//tipElements : ['a','abbr','acronym'],	// @Array: Allowable elements that can have the toolTip
	tipElements : ['a','input','td','th', 'div','select','span'],	// @Array: Allowable elements that can have the toolTip
	obj : Object,							// @Element: That of which you're hovering over
	tip : Object,							// @Element: The actual toolTip itself
	active : 0,								// @Number: 0: Not Active || 1: Active
	init : function() {
		if ( !document.getElementById ||
			!document.createElement ||
			!document.getElementsByTagName ) {
			return;
		}
		var i,j;
		this.tip = document.createElement('div');
		this.tip.id = 'toolTip';
		document.getElementsByTagName('body')[0].appendChild(this.tip);
		this.tip.style.top = '0';
		this.tip.style.visibility = 'hidden';
		var tipLen = this.tipElements.length;
		for ( i=0; i<tipLen; i++ ) {
			var current = document.getElementsByTagName(this.tipElements[i]);
			var curLen = current.length;
			for ( j=0; j<curLen; j++ ) {
			  //sivann: replace only for elemets which have title attribute
			  if(current[j].getAttribute('title') != null && current[j].getAttribute('title') != "") {
				addEvent(current[j],'mouseover',this.tipOver);
				addEvent(current[j],'mouseout',this.tipOut);
				current[j].setAttribute('tip',current[j].title);
				current[j].removeAttribute('title');
			  }
			}
		}
	},
	updateXY : function(e) {
		if ( document.captureEvents ) {
			sweetTitles.xCord = e.pageX;
			sweetTitles.yCord = e.pageY;
		} else if ( window.event.clientX ) {
			sweetTitles.xCord = window.event.clientX+document.documentElement.scrollLeft;
			sweetTitles.yCord = window.event.clientY+document.documentElement.scrollTop;
		}
	},
	tipOut: function() {
		if ( window.tID ) {
			clearTimeout(tID);
		}
		if ( window.opacityID ) {
			clearTimeout(opacityID);
		}
		sweetTitles.tip.style.visibility = 'hidden';
	},
	checkNode : function() {
		var trueObj = this.obj;
		if ( this.tipElements.inArray(trueObj.nodeName.toLowerCase()) ) {
			return trueObj;
		} else {
			return trueObj.parentNode;
		}
	},
	tipOver : function(e) {
		sweetTitles.obj = this;
		tID = window.setTimeout("sweetTitles.tipShow()",tipdelay);
		sweetTitles.updateXY(e);
	},
	tipShow : function() {		
		var scrX = Number(this.xCord);
		var scrY = Number(this.yCord);
		var tp = parseInt(scrY+15);
		var lt = parseInt(scrX+10);
		var anch = this.checkNode();
		var addy = '';
		var access = '';
		if ( (anch.nodeName.toLowerCase() == 'a' )) {
			addy = (anch.href.length > 25 ? anch.href.toString().substring(0,25)+"..." : anch.href);
			var access = ( anch.accessKey ? ' <span>['+anch.accessKey+']</span> ' : '' );
		} else {
			if (anch.firstChild)
			  addy = anch.firstChild.nodeValue;
		}
		//sivann:dont'show links
		//this.tip.innerHTML = "<p>"+anch.getAttribute('tip')+"<em>"+access+addy+"</em></p>";
		this.tip.innerHTML = "<p>"+anch.getAttribute('tip')+"</p>";
		if ( parseInt(document.documentElement.clientWidth+document.documentElement.scrollLeft) < parseInt(this.tip.offsetWidth+lt) ) {
			this.tip.style.left = parseInt(lt-(this.tip.offsetWidth+10))+'px';
		} else {
			this.tip.style.left = lt+'px';
		}
		if ( parseInt(document.documentElement.clientHeight+document.documentElement.scrollTop) < parseInt(this.tip.offsetHeight+tp) ) {
			this.tip.style.top = parseInt(tp-(this.tip.offsetHeight+10))+'px';
		} else {
			this.tip.style.top = tp+'px';
		}
		this.tip.style.visibility = 'visible';
		this.tip.style.opacity = '.1';
		this.tipFade(10);
	},
	tipFade: function(opac) {
		var passed = parseInt(opac);
		var newOpac = parseInt(passed+10);
		if ( newOpac < 80 ) {
			this.tip.style.opacity = '.'+newOpac;
			this.tip.style.filter = "alpha(opacity:"+newOpac+")";
			opacityID = window.setTimeout("sweetTitles.tipFade('"+newOpac+"')",20);
		}
		else { 
			this.tip.style.opacity = '.80';
			this.tip.style.filter = "alpha(opacity:80)";
		}
	}
};
function pageLoader() {
	sweetTitles.init();
}
addEvent(window,'load',pageLoader);

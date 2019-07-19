/*---------------------------------------------------------------------------*\
|  Subject:       QQMail HtmlEditor(菜刀版)									  |
|  Version:       1.2(带图片上传UTF8版)										  |
|  Download:      http://www.1715.cn/HtmlEditor/HtmlEditor.rar                |
|  Demo:          http://www.1715.cn/HtmlEditor/index.php                     |
|-----------------------------------------------------------------------------|
|  QQ: 275171   http://www.1715.cn                                            |
|  blueidea.com ID: blgl0528 im286.com ID: blgl1984                           |
|-----------------------------------------------------------------------------|
|  rework by night 2008-09-09 17:20:00                                        |
|  blog.gziu.com QQ:800334 Email:flash165@126.com  http://www.7gz.cn          |
\*---------------------------------------------------------------------------*/

//navigate
var gsAgent = navigator.userAgent.toLowerCase();
var gfAppVer = parseFloat(navigator.appVersion);
var gIsOpera = gsAgent.indexOf("opera") > -1;
var gIsKHTML = gsAgent.indexOf("khtml") > -1 || gsAgent.indexOf("konqueror") > -1 || gsAgent.indexOf("applewebkit") > -1;
var gIsSafari = gsAgent.indexOf("applewebkit") > -1;
var gIsIE = ( gsAgent.indexOf("compatible") > -1 && !gIsOpera ) || gsAgent.indexOf("msie") > -1;
var gIsTT = gIsIE ? (navigator.appVersion.indexOf("tencenttraveler") != -1 ? 1 : 0) : 0;
var gIsFF = gsAgent.indexOf("gecko") > -1 && !gIsKHTML;
//var gIsNS = !gIsIE && !gIsOpera && !gIsKHTML && (gsAgent.indexOf("mozilla") == 0) && (navigator.appName.toLowerCase() == "netscape");
//var gIsAgentErr = !( gIsOpera || gIsKHTML || gIsSafari || gIsIE || gIsTT || gIsFF || gIsNS );

if (gIsIE) {
	var reIE = new RegExp("MSIE (\\d+\\.\\d+);", "i");
	reIE.test(navigator.userAgent);
	var gIEVer = parseFloat(RegExp["$1"]);
}

function RegFilter( str ) {
	return str.replace( /([\^\.\[\$\(\)\|\*\+\?\{\\])/ig, "\\$1" ) ;
}
function Template( t, f ) {
	var _t = typeof( t ) == "string" ? t : ( t.join ? t.join( "" ) : "" );
	var _tD, _lD, _f = f ? f : "$", _rf = RegFilter( _f );
	var rP =  function(p) {
		if ( !_tD ) 
			_lD = ( _tD = _t.split( _f ) ).concat();
		for ( var i = 1, _len = _tD.length; i < _len; i += 2 ) 
			_lD[ i ] = p[ _tD[ i ] ];
		return _lD.join( "" );
	};
	var rRE = function( p ) {
		return _t.replace( new RegExp( [ _rf, "(.*?)", _rf ].join( "" ), "ig" ), function( m, v ){return p[ v ];} );
	};
	this.toString = function() {
		return _t;
	};
	this.replace = function( p, defaultFunc ) {
		return ( defaultFunc == "parse" || !( document.all && !( /opera/i.test(navigator.userAgent ) ) ) ? rP : rRE )( p );
	};
};
function T( t, f ) {
	return new Template( t, f );
}

//global base function
var gd = document;
function Gel(id, ob) {
	return ( ob || gd ).getElementById(id);
}
function GelTags(tag, ob) {
	return ( ob || gd ).getElementsByTagName(tag);
}
function S(i, win) {
	try {
		return ( win || window ).document.getElementById(i);
	}
	catch( e ) {
		return null;
	}
}
function SO(i, o) {
	return Gel(i, o);
}
function SN(i, win) {
	try {
		return ( win || window ).document.getElementsByName(i);
	}
	catch( e ) {
		return null;
	}
}
function SNO(i, o) {
	return (o ? o : gd).getElementsByName(i);
}
function F(sID, win) {
	if ( !sID )
		return null;
	var frame = S( sID, win );
	if ( !frame )
		return null;
	return frame.contentWindow || ( win || window ).frames[sID];
}
function E(list, Func, start, end) {
	if (!list)
		return;
	if ( list.constructor == Array ) {
		var len = list.length;
		for (var i = (start || 0), end = end < 0 ? (len + end) : (end < len ? end : len); i < end; i++)
			try{Func(list[i], i, len);}catch(e){}
	}
	else {
		for (var i in list)
			try{Func(list[i], i);}catch(e){}
	}
}
function GetSid() {
	try {var s = top.g_sid;}catch(e){}
	s = s ? s : (S("sid") ? S("sid").value : "");
	if (!s) {
		s = (top.location.href.split("?")[1]).split("&");
		s = s[0].split("=")[1];
	}
	return s;
}

function Show(obj, bShow) {
	obj = (typeof(obj) == "string" ? S(obj) : obj);
	if (obj) obj.style.display= (bShow ? "" : "none");
}

//Event Mng Fun
function fAddEvent(oTarget, sType, fHandler, bRemove) {
	if (!oTarget)
		return;

	if (oTarget.addEventListener) {
		bRemove ? oTarget.removeEventListener(sType, fHandler, false) : oTarget.addEventListener(sType, fHandler, false);
	}
	else if (oTarget.attachEvent) {
		bRemove ? oTarget.detachEvent("on" + sType, fHandler) : oTarget.attachEvent("on" + sType, fHandler);
	}
	else {
		oTarget["on" +sType] = bRemove ? null : fHandler;
	}
}
function fPreventDefault(oEvent) {
	if (oEvent) {
		if (oEvent.preventDefault) {
			oEvent.preventDefault();
		}
		else {
			oEvent.returnValue = false;
		}
	}	
}

function fIsInObj( nObj, oObj ) {
	if ( !nObj || !oObj )
		return false;

	if ( typeof(oObj) == "string" ? nObj.id == oObj : nObj == oObj )
		return true;

	return fIsInObj( nObj.parentNode, oObj );
}
//str api
function Trim(sStr) {
	return sStr.replace(/(^\s*)|(\s*$)/ig,"");
}
function StrReplace(s, o, d, mode) {
	return s.replace(new RegExp( RegFilter(o), mode ), d);
}
function HighLight(filter, head, end) {
	return function(str) {
		return str.replace(new RegExp(["(", RegFilter(filter), ")"].join(""), "ig"), [head, "$1", end].join(""));
	};
}
//textarea api
function PutTextareaValue(o, val) {
	if (o.tagName != "TEXTAREA" && o.tagName != "textarea") return false;
	o.innerText != null ? o.innerText = val : o.value = val;
	return true;
}
function GetTextareaValue(o) {
	if (o.tagName != "TEXTAREA" && o.tagName != "textarea") return null;
	return o.value;
}

//======================================================================
function HtmlDecode(s) {
	return (s == null)?s:s.replace(/&lt;/g,"<").replace(/&gt;/g,">").replace(/&amp;/g,"&").replace(/&quot;/g,"\"");
}
function HtmlEncode(s) {
	return (s == null)?s:s.replace(/&/g,"&amp;").replace(/\"/g,"&quot;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
}
//editor api
function TextToHtml(content) {
	//the attr of innerHTML in firefox is diff in ie
	var res = "<DIV>" + content.replace((content.indexOf("<BR>") >= 0)? /<BR>/ig: /\n/g, "</DIV><DIV>") + "</DIV>";
	res = res.replace(new RegExp("\x0D","g"), "");
	res = res.replace(new RegExp("\x20","g"), "&nbsp;");
	res = res.replace(new RegExp("(<DIV><\/DIV>)*$","g"), "");
	return res.replace(/<DIV><\/DIV>/g, "<DIV>&nbsp;</DIV>");
}
function HtmlToText(content) {
	//function for firefox
	//manal change div,p,br
	var res = content.replace(/<\/div>/ig, "\n");
	res = res.replace(/<\/p>/ig, "\n");
	return res.replace(/<br>/ig, "\n");
}

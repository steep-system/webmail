
//global def
var gIsInitEditor = 0;
var gHtmlId = "HtmlEditor";
var gSourceId = "SourceEditor";
var gTextId = "TextEditor";
var gEditorCurId = null;
var gEditorPath = "../../js/editor/";
var gEditorToolBarId = "editor_toolbar";
var gEditorToolBarBtnContainerId = "editor_toolbar_btn_container";
var gEditorToolBarSet = {};
var gEditorTxtRange = null;
var gEditorStopCurIni = false;
var gScreenSnapObj = null;
var gEditorMinHeight = null;
var gIsPageLoadedFinish = false;
var gContentId = null;
var gFrame = 0;

//inteface
function OutputEditorLoading( win ) {
	win = win || window;
	return [ 
		'<div id="HtmlEditorLoading" style="position:absolute;*width:100%;">', 
			'<div class="infobar addrtitle" style="margin:1px;padding:2px;border:0;">',
				!win.IniEditor || !win.S ? 
				'<b style="color:red;">编辑器加载失败...请尝试清空缓存然后重新登陆邮箱！</b>' : 
				'&nbsp;&nbsp;数据加载中，数据量比较大，可能需要一定时间，请稍候...',
			'</div>', 
		'</div>'
		].join( "" );
}
function IniEditor(noinclude) {
	if (gIsInitEditor == 0) {
		StartIniEditor(noinclude);
	}
}
function StartIniEditor(noinclude)
{
	try
	{
		gIsInitEditor = 1;
		var l = location.href;
		var snews = "";
		var s =  "";
		if(gFrame==1){
		    if(gContentId!=null) s = document.getElementById(gContentId).value;	
		}else{
			if(gContentId!=null) s = window.parent.document.getElementById(gContentId).value;
		}
		
		var focusFunc;
		fEditorInit(s);
		try{focusFunc();}catch(e){}
	}
	catch(e) {setTimeout(function(){if(!gEditorStopCurIni)StartIniEditor(noinclude);}, 50);return;}
}

function PutContent(content) {
	switch(gEditorCurId) {
		case gHtmlId:
			return fPutContent(content);
		case gSourceId:
			return PutTextareaValue(S(gSourceId), content);
		case gTextId:
			if (!fPutContent(gIsIE ? content : HtmlToText(content))) return PutTextareaValue(S(gTextId), "数据加载失败!"); 
			return PutTextareaValue(S(gTextId), fGetPlainContent());
	}
	return false;
}
function GetContent() {
	switch(gEditorCurId) {
		case gHtmlId:
			return fGetContent();
		case gSourceId:
			return GetTextareaValue(S(gSourceId));
		case gTextId:
			return GetTextareaValue(S(gTextId));
	}
	return null;
}
//toolbar & plus callback inteface
function fDispToolBar(flag) {
	var tb = S(gEditorToolBarId);
	if (!tb && flag == 0) return;
	if (!tb) {
		if(window.fGenToolBarHtml) fLoadEditorToolBarCallBack();
	}
	fDisp(tb, flag);

	var t = S(gEditorToolBarBtnContainerId);
	if (t) {
		t = t.childNodes;
		fDisp(t[0], 2);
		fDisp(t[1], 2);
	}
}

function fLoadEditorToolBarCallBack() {
	if (S(gEditorToolBarId) || !gIsInitEditor) return;
	if (!S(gHtmlId)) {
		setTimeout("fLoadEditorToolBarCallBack();", 100);
		return;
	}
	var d = document.createElement("div");
	d.innerHTML = fGenToolBarHtml();
	S(gHtmlId).parentNode.insertBefore(d, S(gHtmlId));
}

//base fun
function fGenHtml(dat, templ, bCustom) {
	var code = [];
	for(var i= bCustom ? 1 : 0;i<dat.length;i++) {
		var l = bCustom ? dat[0].length : dat[i].length;
		for(var j = 0; j < l; j++)
			if (templ[j*2+1]) templ[j*2+1] = bCustom ? dat[i][dat[0][j]] : dat[i][j];
		code.push( templ.join("") );
	}
	return code.join( "" );
}
function fDisp(obj, flag) {
	obj = (typeof(obj) == "string" ? S(obj) : obj);
	if (obj) obj.style.display =  (flag == 0 ? "none" : (flag == 2 ? (obj.style.display == "" ? "none" : "") : ""));
}
function fIsDisp(obj) {
	obj = (typeof(obj) == "string" ? S(obj) : obj);
	if (obj) return obj.style.display != "none";
	return false;
}
function fGetBreakLine(n) {
	 return ( new Array( ( n || 1 ) + 1  ) ).join( gIsIE ? "<div>&nbsp;</div>" : "<br>" );
}
function fPutContent(content) {
	try {
		F(gHtmlId).document.body.innerHTML = content;
		fFixHtmlContent();
	}catch(e){return false;}
	return true;
}
function fGetContent() {
	var b = F(gHtmlId).document.body;
	return b ? b.innerHTML : null;
}
function fGetPlainContent() {
	var b = F(gHtmlId).document.body;
	return b ? (b.innerText != null ? b.innerText : b.textContent) : null;
}
function fOnMenuMouseOver(obj) {
	obj.className = "editor_menu_mover";
}
function fOnMenuMouseOut(obj) {
	obj.className = "editor_menu";
}
function fSavePos() {
	if (document.selection) gEditorTxtRange = F(gHtmlId).document.selection.createRange();
}
function fLoadPos() {
	if (gEditorTxtRange) {
		fSetEditorFocus();
		gEditorTxtRange.select();
		gEditorTxtRange = null;
	}
}
//a
function fIndexCreater(a) {
	var idx = {};
	for (var i = a.length - 1; i >= 0; i--) idx[a[i]] = i;
	return idx;
}
//A
function fSetEditable( d ){
	d = d || F(gHtmlId).document;

	if ( d.designMode && d.designMode.toString().toLowerCase() == "off" )
		d.designMode = "on";
	else
		d.body.contentEditable = true;
	
	try{d.execCommand("useCSS", false, false);}catch(e){}
}
function fSetEditorContent( d, content ) {
	d.open();
	d.writeln ([
	'<html>', 
		'<head><style>', 
			'body {padding:4px 4px;*padding:1px 4px;font:normal 14px Verdana;background:#fff}',
			'body, p, font, div, li { line-height: 160%;}',
			//'p {margin:0;padding:0;display:block;}',
			'pre {white-space: pre-wrap;white-space: -moz-pre-wrap;white-space: -pre-wrap;white-space: -o-pre-wrap;word-wrap: break-word;}', 
		'</style></head>', 
		'<body>', content, '</body>', 
	'</html>'
	].join( "" ) );
	d.close();
}
function fSetEditorFocus( pos, d ) {
	var o = d && d.parentWindow;
	var isWindow = true;

	if ( !o ) {
		switch(gEditorCurId) {
			case gHtmlId:
				o = F(gHtmlId);
				break;
			case gSourceId:
			case gTextId:
				o = S(gEditorCurId);
				isWindow = false;
				break;
		}
	}

	if ( isWindow ) {
		try {
			var obj = o.parent.document.activeElement;
			obj = obj && obj.tagName == "IFRAME" ? obj.contentWindow : null;
			if ( o != obj )
				o.focus();
		}
		catch( e ) {
			o.focus();
		}
	}
	else {
		o.focus();
	}

	if (typeof(pos) == "number")
	{
		if (gIsIE) {
			var r = o.createTextRange ? o.createTextRange() : o.document.body.createTextRange();
			r.moveStart("character", pos);
			r.collapse(true);
			r.select();
		}
		else {
			if ( o.document ) {
				o.getSelection().collapse( o.document.body, pos );
			}
			else {
				o.selectionStart = pos;
				o.selectionEnd = pos;
			}
		}
	}
}
function fSetToolBarChangeBtn() {
	var o = S( gEditorToolBarBtnContainerId );
	var t = T( [
		"<a onclick='fDispToolBar($parm$);' unselectable='on' title='$title$' style='$style$'>", 
			"<input type='button'  style='background:url(", gEditorPath, "images/editoricon.gif) -514px -4px;", 
			"width:18px;height:14px;border:none;padding:0;vertical-align:middle!important;vertical-align:auto;'>", 
			"$text$", 
		"</a>" ].join( "" ) );
	if (o) 
		o.innerHTML = [ 
			t.replace( {parm:1, title:"显示文字格式工具条", text:"文字格式↓"} ),
			t.replace( {parm:0, title:"隐藏格式编辑工具条", text:"文字格式↑", style:"display:none"} )
		].join( "" );
}
//A
function fFixHtmlContent( d ) {
	if ( gIsIE ) {
		E( GelTags("div", d || F(gHtmlId).document), function( node ) {
			var childs = node.childNodes;
			if ( childs.length == 1 && node.innerHTML == "&nbsp;" )
				node.innerHTML = "";
		} );
	}
}

//AA ini fun
function fEditorOnLoad(content, bFocus) {
	S(gHtmlId).hideFocus = true;

	var d = F(gHtmlId).document;

	gEditorCurId = gHtmlId;

	fSetEditorContent( d, content );
	fSetEditable( d );
	fFixHtmlContent( d );
	//del 2
	Show( "HtmlEditorLoading", false );
}
//AA
function fEditorInit(content, bTB, bTBP, bTBPinTB, bTBPSep, bTBPPhoto, bTBPMo, 
	bTBPSnap, bTBPMusic, bFocus, sTBPUi, oTBSet) {

	if ( !S(gHtmlId) ) {
		return false;
	}

	fEditorOnLoad(content, bFocus);

	//把这里的操作变成异步,优先原始编辑器
	if ( !gIsSafari ) {
		var img = new Image();
		img.onload = img.onerror = function() {
			fSetToolBarChangeBtn();
			fDispToolBar(bTB, bTBPinTB);
		};
		img.src = [gEditorPath, "images/editoricon.gif"].join("");
	}

	return true;
}
function DoProcess(){
	var c = GetContent();
	if(c == "" || c ==null || c==fGetBreakLine(1) || c==fGetBreakLine(1).toUpperCase().replace(/NBSP/g,"nbsp")){
	    return false;	
	}else{
		if(gFrame==1){
		    document.getElementById(gContentId).value = c;
		}else{
			window.parent.document.getElementById(gContentId).value = c;
		}
		return true;	
	}
}
function UnDoProcess(){
	var b = F(gHtmlId).document.body;
	b.innerHTML = fGetBreakLine(1);
}
//为了避免由于信件标签不规范导致无法进行初始化现象
fAddEvent(window, "load", function(){
	gIsPageLoadedFinish = true;
	if(gIsInitEditor == 0 && location.href.indexOf("readmail_group") == -1)
		IniEditor(window.noinclude?window.noinclude:0);
});
fAddEvent(window, "unload", function() {
	//top.CancelDoSend();
	//top.HideModelDialog();
})

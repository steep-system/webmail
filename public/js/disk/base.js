function checksession(){
//	$.ajax({
//		type: "post",
//		url : "../../error/chksession",
//		dataType:'html',
//		success: function(data,textStatus){
//			if(data!=1){
//				parent.window.location.href = data;
//			}
//		}
//	});
}

function checkBrowser(){
	var browser=navigator.appName
	var b_version=navigator.appVersion
	var version=b_version.split(";");
	var trim_Version=version[1].replace(/[ ]/g,"");
	if(browser=="Microsoft Internet Explorer" && trim_Version=="MSIE7.0")
	{
		return "ie7";
	}
	else if(browser=="Microsoft Internet Explorer" && trim_Version=="MSIE6.0")
	{
		return "ie6";
	}
}

function setSelect(){
	//--null-//
}

function viewEml(path){
	path = $('#folder').val()+path;
	window.open('../mail/reademl?param='+path+'&t=1');
}

function viewEmlByUser(path){
	path = $('#folder').val()+path;
	window.open('../mail/reademl?param='+path+'&user='+$('#user').val()+'&share='+$('#sharetype').val()+'&t=1');
}

function filesend(folder,file,fname){
	var url = '../mail/write?file='+folder+file+'&oper=disk&name='+fname;
	window.open(url);
}
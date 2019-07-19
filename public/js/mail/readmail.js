function copyAttachFile(param){
	var diag = new Dialog();
	diag.Title = lang.mail.M0021;
	diag.URL = "netdisk";
	diag.Width = 461;
	diag.Height = 250;
	diag.OKEvent = function(){
		var doc=diag.innerFrame.contentWindow.document;
		copyfile(0,param,doc,diag,1);
	};
	diag.CancelEvent = function(){
		diag.close();
	};
	diag.OkButtonText = lang.common.COM004;
	diag.CancelButtonText = lang.common.COM005;
	diag.show();
}

function copyfile(cover,param,doc,diag,type){
	var tarfolder = doc.getElementById('tarfolder').value;
	var folder = parent.$('#folder').val();

	if(tarfolder==""){
		Dialog.alert(lang.disk.D0029);
	}else if(param == ""){
		Dialog.alert(lang.mail.M0041);
	}else{
		$.ajax({
			type: "post",
			url : "copytodisk",
			dataType:'json',
			data:"file="+param+"&t="+tarfolder+"&mailbox="+folder+"&type="+type+"&cover="+cover,
			success: function(data,textStatus){
				if(data.state==1){
					window.top.showtip(data.tip);
					diag.close();
				}else if(data.state==2){
					alert(data.tip);
				}else if(data.state==3){
					if(confirm(data.tip)){
						copyfile(1,param,doc,diag,type);
					}
				}else{
					alert(lang.common.COM021);
				}
			}
		});
	}
}

function musicplay(file,id){
	if($('#curplayer').val()){
		$('#mplayer_'+$('#curplayer').val()).empty();
		$('#sub_mswitch_'+$('#curplayer').val()).html(lang.common.COM015);
	}
	var param = file;
	var player = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0" width="195" height="20">' +
	'<param name="movie" value="../../js/musicplayer.swf?file=../mail/download?param='+param+'&width=195&songVolume=100&backColor=E8E8E8&frontColor=000000&autoStart=true&repeatPlay=false&showDownload=false" />' +
	'<param name="quality" value="high" />' +
	'<param value="transparent" name="wmode" />' +
	'<embed src="http://www.51119.com/play/swf/2.swf?file=&width=195&songVolume=100&backColor=E8E8E8&frontColor=000000&autoStart=false&repeatPlay=false&showDownload=false" width="195" height="20" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash"></embed>' +
	'</object>';
	$('#mplayer_'+id).html(player);
	var close = "<a href='javascript:void(0);' onclick='closemusicplay(\""+file+"\","+id+");'><span id='sub_mswitch_"+id+"' class='orangelink'>"+lang.common.COM039+"</span></a>";
	$('#mswitch_'+id).html(close);
	$('#curplayer').val(id);
}

function closemusicplay(file,id){
	$('#mplayer_'+id).empty();
	var open = "<a href='javascript:void(0);' onclick='musicplay(\""+file+"\","+id+");'><span id='sub_mswitch_"+id+"' class='orangelink'>"+lang.common.COM015+"</span></a>";
	$('#mswitch_'+id).html(open);
}

function iFrameHeight() {
	var ifm= document.getElementById("mcontent");
	var subWeb = document.frames ? document.frames["mcontent"].document : ifm.contentDocument;
	if(ifm != null && subWeb != null) {
		if(subWeb.body.scrollHeight<150){
			ifm.height = 150;
		}else{
			ifm.height = subWeb.body.scrollHeight;
		}
	}
}

function sendatonce(){
	window.top.document.getElementById('frame_content').src = '../mail/mailbox?f=' + parent.$('#folder').val() + '&sleep=1' + '&time='+new Date().getTime();
}

function addContact(param){
	Dialog.confirm(lang.contact.C0036,function(){window.open('../contact/edit?param='+param)});

}

function cancelRecallMail(mid){
	$.ajax({
		type: "post",
		url : "cancelrecall",
		dataType:'html',
		data:"mid="+mid,
		success: function(data,textStatus){
			if(data==1){
				if(window.top==window.self){
					Dialog.alert(lang.mail.M0151);
					$('#recallbtn').html(lang.mail.M0151);
				}else{
					if($('#isback').val()==1){
						parent.showtip(lang.mail.M0151);
						$('#recallbtn').html(lang.mail.M0151);
					}else{
						$('#recallbtn').html(lang.mail.M0151);
						parent.list(parent.$('#curpage').val(),parent.$("#listsort").val(),parent.$("#listorder").val(),1);
					}
				}
			}else{
				Dialog.alert(lang.mail.COM021);
			}
		}
	});
}
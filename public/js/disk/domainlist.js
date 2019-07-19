$(document).ready( function() {
	list(1);
	getdiskinfo();
	
	$(document).bind('keydown', 'left', function(){
		var curpage = parseInt($('#curpage').val());
		if(curpage<=1){
			list(1);
		}else{
			list(curpage-1);
		}
	});

	$(document).bind('keydown', 'right', function(){
		var curpage = parseInt($('#curpage').val());
		var maxpage = parseInt($('#maxpage').val());
		if(curpage>=maxpage){
			list(maxpage);
		}else{
			list(curpage+1);
		}
	});

	$(document).bind('keydown', 'pageup', function(){
		var curpage = parseInt($('#curpage').val());
		if(curpage<=1){
			list(1);
		}else{
			list(curpage-1);
		}
	});

	$(document).bind('keydown', 'pagedown', function(){
		var curpage = parseInt($('#curpage').val());
		var maxpage = parseInt($('#maxpage').val());
		if(curpage>=maxpage){
			list(maxpage);
		}else{
			list(curpage+1);
		}
	});
});

function selpage(){
	list($('#pageselect').val());
}

function list(page){
	var folder = $('#folder').val();
	var url = "domainsharelist";
	var param = "user="+$('#user').val()+"&str=32&page="+page+"&f="+folder+"&ajax=1";
	if(folder==""){
		url = "getdomainlist"
	}
	$.ajax({
		type: "post",
		url : url,
		dataType:'json',
		data:param,
		beforeSend:parent.showloadingtip(),
		success: function(data,textStatus){
			if(data.code==1){
				var row = data.data;
				var strlist = "";
				$('#list').empty();

				if(data.updir!=''){
					var dirlist = '<div class="shareList-list02-con" style="height:20px;padding-bottom:5px;padding-top:5px;">'+
					'<ul><li class="li02"><b class="ico ico-webdiscs-tname ico-webdiscs-11"></b><span class="webdiscs-tname"><a href="javascript:updir();" onclick="updir();" style="text-decoration:none;"> '+lang.disk.D0032+'</a></span>'+
					'</ul></div>';
					strlist = strlist + dirlist;
				}


				for(var i=0;i<row.length;i++){
					var oper = "";
					var link = "download?param="+row[i].info+"|"+$('#folder').val();
					var link_target = "";
					var review = "";
					var musicplay = "";
					var icon01 = "ico ico-webdiscs-tfile";
					var icon02 = "webdiscs-tfile"
					var fdesc = lang.disk.D0038+" "+row[i].size;
					var filepath = "";
					var icon = "ico-webdiscs-02";
					var vieweml = "";

					if(row[i].isdir){
						filepath = row[i].dir+row[i].path;
						if(!row[i].isshare){
							filepath = row[i].path;
						}
						oper = "onclick='setdir(\""+row[i].isshare+"\");intodir(\""+filepath+"\",\""+row[i].name+"\")'";
						link = "javascript:void(1)";
						link_target = "";
						icon01 = "ico ico-webdiscs-tname";
						icon02 = "webdiscs-tname"
						fdesc = lang.disk.D0033;
						icon = "ico-webdiscs-01";
					}

					if(row[i].ext=='jpg' || row[i].ext=='jpeg' || row[i].ext=='gif' || row[i].ext=='bmp'){
						review = '<span id="review"><a href="review?param='+row[i].info+'&f='+$('#folder').val()+'" title="'+row[i].name+'" rel="lightbox" style="text-decoration:none;"><font color="darkorange">'+lang.common.COM014+'</font></a></span>';
						icon = "ico-webdiscs-03";
					}
					
					var ie = !-[1,];
					if(row[i].ext=='mp3'){
						icon = "ico-webdiscs-04";
					}
					
					if(row[i].ext=='mp3' && ie){
						musicplay = '<span id="mplayer'+i+'"><a href="javascript:void(1)" onclick="musicplay(\''+row[i].info+'\','+i+')" title="'+row[i].name+'" style="text-decoration:none;"><font color="darkorange">'+lang.common.COM015+'</font></a></span>';
					}
					
					if(row[i].ext=='eml'){
						vieweml = '<span class="webdiscs-control"><a href="javascript:void(1)" onclick="viewEmlByUser(\''+row[i].path+'\');event.cancelBubble=true;" style="text-decoration:none;"><font color="darkorange">'+lang.common.COM016+'</font></a></span>';
					}
					
					switch(row[i].ext){
						case 'txt':icon = "ico-webdiscs-05";break;
						case 'rar':icon = "ico-webdiscs-06";break;
						case 'zip':icon = "ico-webdiscs-06";break;
						case 'gz':icon = "ico-webdiscs-06";break;
						case 'tar':icon = "ico-webdiscs-06";break;
						case 'tgz':icon = "ico-webdiscs-06";break;
						case 'bz2':icon = "ico-webdiscs-06";break;
						case 'xls':icon = "ico-webdiscs-07";break;
						case 'xlsx':icon = "ico-webdiscs-07";break;
						case 'pdf':icon = "ico-webdiscs-08";break;
						case 'doc':icon = "ico-webdiscs-09";break;
						case 'docx':icon = "ico-webdiscs-09";break;
						case 'eml':icon = "ico-webdiscs-12";break;
						case 'wma':icon = "ico-webdiscs-04";break;
					}

					strlist = strlist + '<div class="shareList-list02-con">'+
					'<ul><li class="li02"><b class="ico ico-webdiscs-tname '+icon+'"></b><span class="webdiscs-tname"><a href="'+link+'" '+oper+' '+link_target+' title="'+row[i].name+'" style="text-decoration:none;"><span class="on">'+row[i].name+'</span></a>'+
					'</span><li class="li01"></li>'+
					'</li><li class="li03">'+fdesc+' | '+lang.disk.D0039+' '+row[i].mtime+'</li>'+
					'<li class="li04"><span class="webdiscs-control">'+review+musicplay+vieweml+'</span></li>'+
					'</ul></div>';
				}
				//tb_init('span a.thickbox');

				strlist = strlist + '<div class="clear"></div>'+
				'<div class="shareList-menu">'+
				'<div class="plate01 shareList-bottommenu-plate01-c">'+
				'<div class="plate01 shareList-bottommenu-plate01-l">'+
				'<div class="plate01 shareList-bottommenu-plate01-r">'+
				'<span class="shareList-menu-r" >'+
				'<span class="shareList-menu-page" id="pagelist">'+
				'</span>'+
				'</span>'+
				'<span class="shareList-menu-l">'+
				'<span class="shareList-menu-menulist">'+
				'</span></span></div>'+
				'</div>'+
				'</div>'+
				'</div>';
				$('#list').html(strlist);

				$(function(){
					$("#review a").lightbox();
				});

				setpage(data.curpage,data.maxpage);
				$('#maxpage').val(data.maxpage);
				
				$('#info_files').html(data.files);
				$('#info_size').html(data.size);
				var sizerate = (parseInt(data.osize)/(parseInt($('#info_maxsize').html())*1024*1024))*100;
				$('#info_sizerate').html(sizerate.toFixed(0));
				$('#sizeplan').html(sizerate.toFixed(0)+"%");
				$("#sizeplan").progressBar({ showText: false,
				boxImage: '../../js/progressbar/images/progressbar.gif',
				steps: 20,
				width: 140,
				max:100,
				barImage: {
					0:  '../../js/progressbar/images/progressbg_blue.gif',
					50: '../../js/progressbar/images/progressbg_orange.gif',
					80: '../../js/progressbar/images/progressbg_red.gif'
				}
				});
			}else{
				window.location.href = data.url;
			}
			parent.closeloadingtip();
		}
	});
	$('#curpage').val(page);
}

function setpage(curpage,maxpage){
	$('#pagelist').empty();
	var prepage = curpage-1;
	var nxtpage = curpage+1;
	if(prepage<=1)prepage = 1;
	if(nxtpage>=maxpage)nxtpage = maxpage;
	var sublist = '';
	var mark = '';
	var substart = curpage-2;
	if(substart<=1) substart = 1;
	var subend = substart+4;
	if(subend>=maxpage) subend = maxpage;
	for(var i=substart;i<=subend;i++){
		if(i==curpage){
			mark = 'class="current"';
		}else{
			mark = '';
		}
		sublist = sublist + '<li><a href="javascript:void(1)" onclick="list('+i+');" '+mark+'>'+i+'</a></li>';
	}
	var strlist = "";
	if(maxpage>1){
		var pageopt = '';
		for(var i=1;i<=maxpage;i++){
			if(curpage==i){
				pageopt = pageopt + '<option value='+i+' selected>'+i+'/'+maxpage+'</option>';
			}else{
				pageopt = pageopt + '<option value='+i+'>'+i+'/'+maxpage+'</option>';
			}
		}

		strlist = '<span><a href="javascript:void(1)" class="pager index" onclick="list(1);"></a></span>' +
		'<span><a href="javascript:void(1)" class="pager previous" onclick="list('+prepage+');" title="'+lang.mail.M0008+'"></a></span>' +
		'<span>' +
		'<select class="pager-select" id="pageselect" onchange="selpage();">' + pageopt +
		'</select>	</span>		' +
		'<span><a href="javascript:void(1)" class="pager next" onclick="list('+nxtpage+');" title="'+lang.mail.M0009+'"></a></span>' +
		'<span><a href="javascript:void(1)" class="pager end" onclick="list('+maxpage+');"></a></span>';
	}
	$('#pagelist').html(strlist);
}

function getdiskinfo(){
	$.ajax({
		type: "post",
		url : "getdiskinfo",
		dataType:'json',
		success: function(data,textStatus){
			if(data){
				$('#info_files').html(data.files);
				$('#info_size').html(data.size);
				var sizerate = (parseInt(data.osize)/(parseInt($('#info_maxsize').html())*1024*1024))*100;
				$('#info_sizerate').html(sizerate.toFixed(2));
			}
		}
	});
}

function updir(){
	var folder = $('#folder').val();
	if(folder==""){
		window.location = "domain";
	}else if( $('#updir').val()==1){
		$('#folder').val('');
		list(1);
	}else{
		var folders = folder.split("/");
		var upfolder = "";
		for(var i=0;i<(folders.length-2);i++){
			upfolder = upfolder + folders[i]+"/";
		}
		$('#folder').val(upfolder);
		if(folders.length<=2)$('#uplevel').html("&nbsp;");
		list(1);
	}
}

function intodir(path,dirname){
	var curpath = $('#folder').val();
	$('#folder').val(curpath+path);
	list(1);
}

function setdir(dir){
	if(dir==0){
		$('#updir').val(0);
	}else{
		$('#updir').val(1);
	}
}

function musicplay(file,id){
	var param = file+"|"+$('#folder').val();
	$('#musicplayer').empty();
	var player = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0" width="195" height="20">' +
	'<param name="movie" value="../../js/musicplayer.swf?file=../disk/download?param='+param+'&width=195&songVolume=100&backColor=E8E8E8&frontColor=000000&autoStart=true&repeatPlay=false&showDownload=false" />' +
	'<param name="quality" value="high" />' +
	'<param value="transparent" name="wmode" />' +
	'<embed src="http://www.51119.com/play/swf/2.swf?file=&width=195&songVolume=100&backColor=E8E8E8&frontColor=000000&autoStart=false&repeatPlay=false&showDownload=false" width="195" height="20" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash"></embed>' +
	'</object>';
	$('#musicplayer').html(player);

	$('#mplayer'+id).html('<a href="javascript:void(1)" onclick="closemplayer('+id+',\''+file+'\')" style="text-decoration:none;"><font color="darkorange">'+lang.common.COM022+'</font></a>');
	if($('#curplay').val()!=""){
		var tmp = $('#curplay').val();
		var tplayer = tmp.split("|");
		if(file!=tplayer[1]){
			$('#mplayer'+tplayer[0]).html('<a href="javascript:void(1)" onclick="musicplay(\''+tplayer[1]+'\','+tplayer[0]+')" style="text-decoration:none;"><font color="darkorange">'+lang.common.COM015+'</font></a>');
		}
	}
	$('#curplay').val(id+"|"+file);
}

function closemplayer(id,file){
	$('#musicplayer').empty();

	$('#mplayer'+id).html('<a href="javascript:void(1)" onclick="musicplay(\''+file+'\','+id+')" style="text-decoration:none;"><font color="darkorange">'+lang.common.COM015+'</font></a>');
}
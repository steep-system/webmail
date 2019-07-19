$(document).ready( function() {
	list(1);
	getdiskinfo();
	
	$('#btnDel').bind('click',function(){
		delFiles();
	});

	$('#btnDownload').bind('click',function(){
		packagedownload();
	})
	
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
	var url = "groupsharelist";
	var param = "user="+$('#user').val()+"&str=240&page="+page+"&type=1&f="+folder+"&ajax=1";
	if(folder==""){
		url = "getgrouplist"
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
				//$('#list').empty();

				if(data.updir!=''){
					var dirlist = '<li style="width:165px;height:155px;">'+
					'<div class="groupList-list-view" style="text-align:center;"> '+
					'<div class="MyDocuments"><a href="javascript:updir();" onclick="updir();" style="text-decoration:none;">'+lang.disk.D0032+'</a></div>'+
					'<a href="javascript:updir();" onclick="updir();" style="text-decoration:none;"><div class="ico ico_sharelist_11" style="text-align:center;"></div></a>'+
					'<div class="shareList_list_menu"></div>'+
					'</div>'+
					'</li>';
					strlist = strlist + dirlist;
				}


				for(var i=0;i<row.length;i++){
					var oper = "";
					var link = "download?param="+row[i].info+"|"+$('#folder').val();
					var link_target = "";
					var review = "";
					var musicplay = "";
					var shareinfo = "";
					var sharetip = "";
					var icon = "ico_sharelist_03";
					var vieweml = "";

					if(row[i].isdir){
						oper = "onclick='intodir(\""+row[i].path+"\",\""+row[i].name+"\")'";
						link = "javascript:void(1)";
						link_target = "";
						icon = "ico_sharelist_01";
					}

					if(row[i].ext=='jpg' || row[i].ext=='jpeg' || row[i].ext=='gif' || row[i].ext=='bmp'){
						review = '<span id="review"><a href="review?param='+row[i].info+'&f='+$('#folder').val()+'" title="'+row[i].name+'" rel="lightbox" style="text-decoration:none;"><font color="darkorange">'+lang.common.COM014+'</font></a></span> ';
						icon = "ico_sharelist_04";
					}

					var ie = !-[1,]; 
					if(row[i].ext=='mp3' && ie){
						musicplay = '<span id="mplayer'+i+'"><a href="javascript:void(1)" onclick="musicplay(\''+row[i].info+'\','+i+')" title="'+row[i].name+'" style="text-decoration:none;"><font color="darkorange">'+lang.common.COM015+'</font></a></span>';
						icon = "ico_sharelist_05";
					}
					
					if(row[i].ext=='eml'){
						vieweml = '<span class="webdiscs-control"><a href="javascript:void(1)" onclick="viewEmlByUser(\''+row[i].path+'\');event.cancelBubble=true;" style="text-decoration:none;"><font color="darkorange">'+lang.common.COM016+'</font></a></span>';
					}
					
					switch(row[i].ext){
						case 'mp3':icon = "ico_sharelist_05";break;
						case 'txt':icon = "ico_sharelist_06";break;
						case 'rar':icon = "ico_sharelist_07";break;
						case 'zip':icon = "ico_sharelist_07";break;
						case 'gz':icon = "ico_sharelist_07";break;
						case 'tar':icon = "ico_sharelist_07";break;
						case 'tgz':icon = "ico_sharelist_07";break;
						case 'bz2':icon = "ico_sharelist_07";break;
						case 'xls':icon = "ico_sharelist_08";break;
						case 'xlsx':icon = "ico_sharelist_08";break;
						case 'pdf':icon = "ico_sharelist_09";break;
						case 'doc':icon = "ico_sharelist_10";break;
						case 'docx':icon = "ico_sharelist_10";break;
						case 'eml':icon = "ico_sharelist_12";break;
					}


					strlist = strlist + '<li style="width:165px;height:155px;">'+
					'<div class="groupList-list-view" style="text-align:center;"> '+
					'<div class="MyDocuments"><a href="'+link+'" '+oper+' '+link_target+' title="'+row[i].name+'" style="text-decoration:none;">'+row[i].shortname+'</a></div>'+
					'<a href="'+link+'" '+oper+' '+link_target+' title="'+row[i].name+'" style="text-decoration:none;"><div class="ico '+icon+'" style="cursor: hand;"></div></a>'+
					'<div class="groupList_list_menu">'+review+musicplay+vieweml+'</div>'+
					'</div>'+
					'</li>';
				}
				//tb_init('span a.thickbox');

				strlist = '<div class="shareList-list"><table cellpadding="0" cellspacing="0" border="0" width="100%"><tr><td><ul>'+strlist+'</td></tr></table></ul></div>';
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
				$('#info_sizerate').html(sizerate.toFixed(2));
				$('#maxpage').val(data.maxpage);
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
		window.location = "group";
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

	$('#mplayer'+id).html('<a href="javascript:void(1)" onclick="closemplayer('+id+',\''+file+'\')" style="text-decoration:none;"><font color="darkorange">关闭</font></a>');
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

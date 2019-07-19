var delpage = 1;
$(document).ready( function() {
	list(1);
	$('#chkall').bind('click',function(){
		if($('#chkall').attr('checked')){
			$("[name='chk']").attr("checked",'true');
		}else{
			$("[name='chk']").removeAttr("checked");
		}
	});

	$('#btnMov').bind('click',function(){
		checksession();
		var param = "";
		$("[name='chk']:checked").each(function(){
			param = param + $(this).val() + ",";
		});
		if(param == ""){
			Dialog.alert(lang.disk.D0025);
		}else{
			var diag = new Dialog();
			diag.Title = lang.disk.D0026;
			diag.URL = "folder";
			diag.Width = 461;
			diag.Height = 250;
			diag.OKEvent = function(){
				var doc = diag.innerFrame.contentWindow.document;
				var tarfolder = doc.getElementById('tarfolder').value;
				var mark = 1;
				var param = '';

				$("[name='chk']:checked").each(function(){
					var tarstr = tarfolder.split("/");
					for(var i=0;i<tarstr.length;i++){
						if(tarstr[i]+"/"==$(this).val()){
							mark = 0;
						}
					}
					if((tarfolder == $(this).val())||($('#folder').val() == tarfolder)){
						Dialog.alert(lang.disk.D0027);
						return false;
					}else if(!mark){
						Dialog.alert(lang.disk.D0028);
						return false;
					}else{
						param = param + $(this).val() + ",";
					}
				});

				if(mark){
					if(param == ""){
						Dialog.alert(lang.disk.D0025);
					}else if(tarfolder == ""){
						Dialog.alert(lang.disk.D0029);
					}else{
						$.ajax({
							type: "post",
							url : "movefiles",
							dataType:'json',
							data:"files="+param+"&f="+$('#folder').val()+"&t="+tarfolder,
							success: function(data,textStatus){
								if(data.state==1){
									list($('#curpage').val());
									diag.close();
								}else{
									Dialog.alert(data.tip);
									diag.close();
								}
							}
						});
					}
				}
			};
			diag.CancelEvent = function(){
				diag.close();
			}
			diag.show();
		}
	});

	$('#btnUpload').bind('click',function(){
		checksession();
		var diag = new Dialog();
		diag.Title = lang.disk.D0030;
		diag.URL = "fileupload?folder="+$('#folder').val();
		diag.Width = 461;
		diag.Height = 280;
		diag.show();
	});

	$('#btnNewfolder').bind('click',function(){
		checksession();
		var diag = new Dialog();
		diag.Title = lang.disk.D0002;
		diag.URL = "newfolder";
		diag.Width = 461;
		diag.Height = 100;
		diag.OKEvent = function(){
			var doc=diag.innerFrame.contentWindow.document;
			var fname = doc.getElementById('fname').value;
			if(fname!=""){
				var param = "dir="+$('#folder').val()+"&fname="+fname;
				$.ajax({
					type: "post",
					url : "addfolder",
					dataType:'json',
					data:param,
					success: function(data,textStatus){
						if(data.state == 1){
							fname = '';
							list($('#curpage').val());
							diag.close();
						}else{
							Dialog.alert(data.tip);
						}
					}
				});
			}else{
				Dialog.alert(lang.disk.D0031);
			}
		};
		diag.CancelEvent = function(){
			diag.close();
		}
		diag.show();
	});

	$('#btnDel').bind('click',function(){
		delFiles();
	});

	$('#btnDownload').bind('click',function(){
		packagedownload();
	});

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

	$('#btn_selall').bind("click",function(){
		var chk = $('.shareList-list02-con').find('.shareList-list02-checkbox');
		if($('#chkall').attr('checked')){
			$("[name='chk']").attr("checked",'true');
			$('.shareList-list02-con').css("background","#eeeeee");
			chk.attr('checked',false);
			$('#chkall').removeAttr("checked");
			$('#btn_selall').html(lang.disk.D0016);
			$('.shareList-list02-con').hover(function(){$(this).css("background","#aec1d8");},function(){$(this).css("background","#eeeeee");});
		}else{
			$("[name='chk']").removeAttr("checked");
			$('.shareList-list02-con').css("background","#aec1d8");
			chk.attr('checked',true);
			$('#chkall').attr('checked',true);
			$('#btn_selall').html(lang.common.COM005);
			$('.shareList-list02-con').hover(function(){$(this).css("background","#aec1d8");},function(){$(this).css("background","#aec1d8");});
		}
	});
});

function selpage(){
	list($('#pageselect').val());
}

function openrename(name,path){
	checksession();
	var diag = new Dialog();
	diag.Title = lang.disk.D0006;
	diag.URL = "rename?name="+name;
	diag.Width = 461;
	diag.Height = 100;
	diag.OKEvent = function(){
		var doc = diag.innerFrame.contentWindow.document;
		var fname = doc.getElementById('fname').value
		if(fname!=""){
			var param = "file="+path+"&folder="+$('#folder').val()+"&newfilename="+fname;
			$.ajax({
				type: "post",
				url : "editfilename",
				dataType:'json',
				data:param,
				success: function(data,textStatus){
					if(data.state == 1){
						fname = "";
						list($('#curpage').val());
						diag.close();
					}else{
						Dialog.alert(data.tip);
					}
				}
			});
		}else{
			Dialog.alert(lang.disk.D0031);
		}
	};
	diag.CancelEvent = function(){
		diag.close();
	}
	diag.show();
}

function list(page){
	var folder = $('#folder').val();
	var param = "folder="+folder+"&page="+page+"&order=user&str=32&sort=SORT_DESC&ajax=1&time="+new Date().getTime();
	$.ajax({
		type: "post",
		url : "getlist",
		dataType:'json',
		data:param,
		beforeSend: function(XMLHttpRequest){
			parent.showloadingtip(),
			$("#loadstate").val(0);
		},
		success: function(data,textStatus){
			if(data.code==1){
				var row = data.data;
				var strlist = "";
				$('#list').empty();

				if(data.updir!=''){
					var dirlist = '<div class="shareList-list02-con" style="height:20px;padding-bottom:5px;padding-top:5px;">'+
					'<ul><li class="li02"><b class="ico ico-webdiscs-tname ico-webdiscs-11"></b><span class="webdiscs-tname"><a href="javascript:void(1)" onclick="updir();" style="text-decoration:none;"> '+lang.disk.D0032+'</a></span>'+
					'</ul></div>';
					strlist = strlist + dirlist;
				}

				if(row.length>0){
					for(var i=0;i<row.length;i++){
						var oper = "";
						var link = "download?param="+row[i].info+"|"+$('#folder').val();
						var link_target = "";
						var review = "";
						var musicplay = "";
						var shareinfo = "";
						var icon01 = "ico ico-webdiscs-tfile";
						var icon02 = "webdiscs-tfile"
						var fdesc = lang.disk.D0038+" "+row[i].size;
						var sharetip = "";
						var icon = "ico-webdiscs-02";
						var sent = '<a href="javascript:filesend(\''+folder+'\',\''+row[i].path+'\',\''+row[i].name+'\');" style="text-decoration:none;">'+lang.mail.M0054+'</a> |';
						var vieweml = ''

						if(row[i].isdir){
							oper = "onclick='intodir(\""+row[i].path+"\",\""+row[i].name+"\");event.cancelBubble=true;'";
							link = "javascript:void(1)";
							link_target = "";
							icon01 = "ico ico-webdiscs-tname";
							icon02 = "webdiscs-tname"
							fdesc = lang.disk.D0033;
							icon = "ico-webdiscs-01";
							sent = "";
						}

						if(row[i].ext=='jpg' || row[i].ext=='jpeg' || row[i].ext=='gif' || row[i].ext=='bmp' || row[i].ext=='png'){
							review = '<span id="review"><a href="review?param='+row[i].info+'&f='+$('#folder').val()+'" title="'+row[i].name+'" rel="lightbox" style="text-decoration:none;"><font color="darkorange">'+lang.common.COM014+'</font></a></span> | ';
							icon = "ico-webdiscs-03";
						}

						var ie = !-[1,];
						if(row[i].ext=='mp3'){
							icon = "ico-webdiscs-04";
						}
						
						if(row[i].ext=='mp3' && ie){
							musicplay = '<span id="mplayer'+i+'"><a href="javascript:void(1)" onclick="musicplay(\''+row[i].info+'\','+i+')" title="'+row[i].name+'" style="text-decoration:none;"><font color="darkorange">'+lang.common.COM015+'</font></a></span> | ';
							icon = "ico-webdiscs-04";
						}

						if(row[i].ext=='eml'){
							vieweml = '<span class="webdiscs-control"><a href="javascript:void(1)" onclick="viewEml(\''+row[i].path+'\');event.cancelBubble=true;" style="text-decoration:none;"><font color="darkorange">'+lang.common.COM016+'</font></a></span> | ';
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
							case 'mp3':icon = "ico-webdiscs-04";break;
						}

						if(row[i].group){
							shareinfo = "<a href=\"javascript:void(1)\" onclick=\"removeShare('group','"+row[i].path+"');event.cancelBubble=true;\" style=\"text-decoration:none;\">"+lang.disk.D0034+"</a>";
							sharetip = " | <font color=\"red\">"+lang.disk.D0036+"</font>";
						}else{
							shareinfo = "<a href=\"javascript:void(1)\" onclick=\"addShare('group','"+row[i].path+"');event.cancelBubble=true;\" style=\"text-decoration:none;\">"+lang.disk.D0023+"</a>";
						}

						if(row[i].domain){
							shareinfo = shareinfo + " | <a href=\"javascript:void(1)\" onclick=\"removeShare('domain','"+row[i].path+"');event.cancelBubble=true;\" style=\"text-decoration:none;\">"+lang.disk.D0035+"</a>";
							sharetip = sharetip + " | <font color=\"red\">"+lang.disk.D0037+"</font>";
						}else{
							shareinfo = shareinfo + " | <a href=\"javascript:void(1)\" onclick=\"addShare('domain','"+row[i].path+"');event.cancelBubble=true;\" style=\"text-decoration:none;\">"+lang.disk.D0024+"</a>";
						}

						strlist = strlist + '<div class="shareList-list02-con">' +
						'<ul>' +
						'<div class="list_leftbox">' +
						'<li class="li02"><b class="ico ico-webdiscs-tname '+icon+'"></b><span class="webdiscs-tname"><a href="'+link+'" '+oper+' '+link_target+' title="'+row[i].name+'" style="text-decoration:none;" onclick="event.cancelBubble=true;"><span class="on">'+row[i].name+'</span></a></span></li>' +
						'<li class="li03">'+fdesc+' | '+lang.disk.D0039+' '+row[i].mtime+''+sharetip+'</li>' +
						'<li class="li04">'+review+musicplay+vieweml+'<span class="webdiscs-control"><a href="javascript:void(1)" onclick="delFile(\''+row[i].path+'\');event.cancelBubble=true;" style="text-decoration:none;">'+lang.disk.D0004+'</a></span> | <span class="changeName"><a href="###" style="text-decoration:none;" onclick="openrename(\''+row[i].name+'\',\''+row[i].path+'\');event.cancelBubble=true;">'+lang.disk.D0006+'</a></span> | '+sent+' '+shareinfo+'</span></li>' +
						'</div>' +
						'<div class="list_rightbox">' +
						'<li class="li01"><span class="select01">' +
						'<input type="checkbox" name="chk" class="shareList-list02-checkbox" value=\"'+row[i].path+'\" alt=\"'+row[i].isdir+'\" disabled>' +
						'</span></li>' +
						'</div>' +
						'</ul>' +
						'</div>';

					}
				}else{
					strlist = strlist + "<div style='text-align:center;padding:20px 0px 20px 0px;font-size:12px;'>"+lang.disk.D0040+"</div>";
				}

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

				$.Lightbox.construct({
					opacity: 0.6,
					show_helper_text: false
				});

				$(".shareList-list02-con").bind('click',function(){
					var chk = $(this).find('.shareList-list02-checkbox');
					if(chk.attr('checked')){
						$(this).css("background","#eeeeee");
						chk.attr('checked',false);
						$(this).hover(function(){$(this).css("background","#aec1d8");},function(){$(this).css("background","#eeeeee");});
					}else{
						$(this).css("background","#aec1d8");
						chk.attr('checked',true);
						$(this).hover(function(){$(this).css("background","#aec1d8");},function(){$(this).css("background","#aec1d8");});
					}

					var chknum = 0;
					$("[name='chk']:checked").each(function(){
						chknum+=1;
					});
					if($("[name='chk']").length==chknum){
						$('#chkall').attr('checked',true);
						$('#btn_selall').html(lang.common.COM005);
					}else{
						$('#chkall').removeAttr("checked");
						$('#btn_selall').html(lang.disk.D0016);
					}
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
		},
		complete: function(XMLHttpRequest, textStatus){
			parent.closeloadingtip();
			$("#loadstate").val(1);
		}
	});
	$('#curpage').val(page);
	$("#chkall").removeAttr("checked");
	$('#btn_selall').html(lang.disk.D0016);
}

function intodir(path,dirname){
	if($("#loadstate").val()==1){
		var curpath = $('#folder').val();
		$('#folder').val(curpath+path);
		list(1);
		operDirdesc(dirname);
	}
}

function updir(){
	if($("#loadstate").val()==1){
		var folder = $('#folder').val();
		var folders = folder.split("/");
		var upfolder = "";
		for(var i=0;i<(folders.length-2);i++){
			upfolder = upfolder + folders[i]+"/";
		}
		$('#folder').val(upfolder);
		if(folders.length<=2)$('#uplevel').html("&nbsp;");
		list(1);
		operDirdesc('');
	}
}

function operDirdesc(dirname){
	var dirdesc = $('#dirdesc').html();
	if(dirname!=''){
		var dirdescs = new Array();
		dirdescs = dirdesc.split("&gt;&gt;");
		dirdesc = dirdesc + " >> " + "<a href='javascript:void(1)' style='text-decoration:none;' onclick='jumptodir(\""+$('#folder').val()+"\","+dirdescs.length+")'>" + dirname + "</a>";
	}else{
		var dirdescs = new Array();
		dirdescs = dirdesc.split("&gt;&gt;");
		dirdesc = '';
		for(var i=0;i<(dirdescs.length-1);i++){
			dirdesc = dirdesc + "<a href='javascript:void(1)' style='text-decoration:none;' onclick='jumptodir(\""+$('#folder').val()+"\","+i+")'>" + dirdescs[i] + "</a>";
			if(i<(dirdescs.length-2)){
				dirdesc = dirdesc + "&gt;&gt;";
			}
		}
	}
	$('#dirdesc').html(dirdesc);
}

function jumptodir(path,depth){
	$('#folder').val(path);
	var dirdesc = $('#dirdesc').html();
	var dirdescs = new Array();
	dirdescs = dirdesc.split("&gt;&gt;");
	$('#dirdesc').html("");
	dirdesc = "";
	for(var i=0;i<=depth;i++){
		dirdesc = dirdesc + "<a href='javascript:void(1)' style='text-decoration:none;' onclick='jumptodir(\""+$('#folder').val()+"\")'>" + dirdescs[i] + "</a>";
		if(i<(depth)){
			dirdesc = dirdesc + "&gt;&gt;";
		}
	}
	$('#dirdesc').html(dirdesc);
	list(1);
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
		'<span><a href="javascript:void(1)" class="pager previous" onclick="list('+prepage+');" title="'+lang.disk.D0008+'"></a></span>' +
		'<span>' +
		'<select class="pager-select" id="pageselect" onchange="selpage();">' + pageopt +
		'</select>	</span>		' +
		'<span><a href="javascript:void(1)" class="pager next" onclick="list('+nxtpage+');" title="'+lang.disk.D0009+'"></a></span>' +
		'<span><a href="javascript:void(1)" class="pager end" onclick="list('+maxpage+');"></a></span>';
	}
	$('#pagelist').html(strlist);
}

function delFiles(){
	var param = "";
	$("[name='chk']:checked").each(function(){
		param = param + $(this).val() + ",";
	});
	if(param == ""){
		Dialog.alert(lang.disk.D0025);
	}else{
		Dialog.confirm(lang.disk.D0041,function(){
			$.ajax({
				type: "post",
				url : "delfile",
				dataType:'json',
				data:"files="+param+"&folder="+$('#folder').val()+"&ajax=1",
				success: function(data,textStatus){
					if(data.code==1){
						list($('#curpage').val());
						parent.showtip(lang.disk.D0043);
					}else{
						if(data.url!=''){
							window.location.href = data.url;
						}else{
							Dialog.alert(lang.common.COM021);
						}
					}
				}
			});
			$("#chkall").removeAttr("checked");
			$('#btn_selall').html(lang.disk.D0016);
		});
	}
}

function delFile(file){
	Dialog.confirm(lang.disk.D0042,function(){
		$.ajax({
			type: "post",
			url : "delfile",
			dataType:'json',
			data:"files="+file+",&folder="+$('#folder').val()+"&ajax=1",
			success: function(data,textStatus){
				if(data.code==1){
					list($('#curpage').val());
					parent.showtip(lang.disk.D0043);
				}else{
					if(data.url!=''){
						window.location.href = data.url;
					}else{
						Dialog.alert(lang.common.COM021);
					}
				}
			}
		});
	});
}

function packagedownload(){
	checksession();
	var param = "";
	var mark = 0;
	$("[name='chk']:checked").each(function(){
		if($(this).attr('alt') == 1){
			mark = 1;
		}else{
			param = param + $(this).val() + ",";
		}
	});
	if(mark){
		Dialog.alert(lang.disk.D0044);
	}else if(param == ""){
		Dialog.alert(lang.disk.D0025);
	}else{
		$('#btnDownload').attr('disabled',true);
		$('#btnDownload').unbind('click');
		window.location = "packagedownload?files="+param+"&folder="+$('#folder').val();
		$('#btnDownload').attr('disabled',false);
		$('#btnDownload').bind('click',function(){
			packagedownload();
		});
		$("#chkall").removeAttr("checked");
		$('#btn_selall').html(lang.disk.D0016);
	}
}

function removeShare(type,file){
	Dialog.confirm(lang.disk.D0045,function(){
		$.ajax({
			type: "post",
			url : "removesharefile",
			dataType:'json',
			data:"file="+file+"&type="+type+"&f="+$('#folder').val()+"&ajax=1",
			success: function(data,textStatus){
				if(data.code==0){
					window.location.href = data.url;
				}else if(data.state==1){
					list($('#curpage').val());
				}else{
					Dialog.alert(data.tip);
				}
			}
		});
	});
}

function addShare(type,file){
	$.ajax({
		type: "post",
		url : "addsharefile",
		dataType:'json',
		data:"file="+file+",&type="+type+"&f="+$('#folder').val()+"&ajax=1",
		success: function(data,textStatus){
			if(data.code==1){
				list($('#curpage').val());
			}else{
				if(data.url!=''){
					window.location.href = data.url;
				}else{
					Dialog.alert(lang.common.COM021);
				}
			}
		}
	});
}

function musicplay(file,id){
	checksession();
	$('#musicplayer').empty();
	var param = file+"|"+$('#folder').val();

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

function getPages(){
	$.ajax({
		async: false,
		type: "post",
		url : "getpages",
		dataType:'html',
		data:"type="+$('#type').val()+"&f="+$('#folder').val(),
		success: function(data,textStatus){
			if(data){
				delpage = data;
				if(delpage<=0)delpage = 1;
			}
		}
	});
}
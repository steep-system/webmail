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
	var param = "page="+page+"&str=32&ajax=1&time="+new Date().getTime();
	$.ajax({
		type: "post",
		url : "getgroup",
		dataType:'json',
		data:param,
		beforeSend:parent.showloadingtip(),
		success: function(data,textStatus){
			if(data.code==1){
				var row = data.data;
				var strlist = "";
				$('#list').empty();
				var link = "";

				for(var i=0;i<row.length;i++){
					var uname = row[i].name;
					if(uname == ""){
						uname = row[i].username;
					}
					link = '<a href="grouplist?user='+row[i].username+'&title='+row[i].realname+'" title='+row[i].username+'><img src="../../skin/default/image/img.gif" class="share-user-pic-open"/></a>'+
					'<div class="shareList_list_menu"><span class="send"><a href="javascript:void(1)"><a href="grouplist?user='+row[i].username+'&title='+row[i].realname+'" title='+row[i].username+' style="text-decoration:none;">'+uname+'</a></span></div>';
					
					if((row[i].urights==0)||(row[i].arights==0)){
						var tip = row[i].username + "("+lang.common.COM023+")";
						if(row[i].arights==0){
							uname = lang.disk.D0046;
							tip = lang.disk.D0046;
						}
						link = '<img src="../../skin/default/image/img.gif" style="filter:progid:DXImageTransform.Microsoft.BasicImage(grayScale=1);" title="'+tip+'" class="share-user-pic-close"/>'+
					'<div class="shareList_list_menu"><span style="cursor:default;"><font color="#C0C0C0">'+uname+'</font></span></div>';
					}

					strlist = strlist + '<li style="width:165px;height:140px;">'+
					'<div class="groupList-list-view" style="text-align:center;"> '+ link +
					'</div>'+
					'</li>';
				}

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

				setpage(data.curpage,data.maxpage);
				$('#maxpage').val(data.maxpage);
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
			}
		}
	});
}
var timer = null;
var readtimer = null;
var clickchk = 0;
var ie = !-[1,];

$(document).ready( function() {
	var fdata = stringToJson($('#folderdata').html());	
	var strfolder = folderSelect(1, 'movelist', fdata, '', '');
	$('#foldertree').html(strfolder);
	
	$(document).bind('keydown', 'left', function(){
		pageUp();
	});

	$(document).bind('keydown', 'right', function(){
		pageDown();
	});

	$(document).bind('keydown', 'pageup', function(){
		pageUp();
	});

	$(document).bind('keydown', 'pagedown', function(){
		pageDown();
	});

	$(document).bind('keydown', 'del', function(){
		moveMail('trash');
	});

	$(document).bind('keydown', 'Shift+del', function(){
		delMails();
	});

	$('#btnDel').bind('click',function(){
		delMails();
	});

	$('#btnMov').bind('click',function(){
		moveMail('trash');
	});

	$('#marklist').change(function(){
		if($(this).val()!=""){
			setMail($(this).val());
		}
		$(this).attr('value','');
	});

	$('#movelist').change(function(){
		if($(this).val()!=""){
			moveMail($(this).val());
		}
		$(this).attr('value','');
	});

	$('#btnMoveto,#btnMovetoArrow').bind('click',function(){
		if($('#marklist').css('display') == "block"){
			$('#marklist').css('display',"none");
		}
		if($('#movelist').css('display') == "none"){
			$('#movelist').css('display',"block");
		}else{
			$('#movelist').css('display',"none");
		}
	});

	$('#btn_col_1').bind('click',function(){
		var stringfolder = $('#folder').val();
		var firstfolder = stringfolder.substring(0, 8);
		if(stringfolder == "sent" || firstfolder == '73656e74'){
			list(1,'RCP',checkOrder(1,5));
		}else{
			list(1,'FRM',checkOrder(1,5));
		}
	})

	$('#btn_col_2').bind('click',function(){
		list(1,'SUB',checkOrder(2,5));
	})

	$('#btn_col_3').bind('click',function(){
		list(1,'RCV',checkOrder(3,5));
	})

	$('#btn_col_4').bind('click',function(){
		list(1,'SIZ',checkOrder(4,5));
	})

	$('#btn_col_5').bind('click',function(){
		list(1,'RED',checkOrder(5,5));
	})

	$('#btnMovtodisk').bind('click',function(){
		var param = "";
		$("[name='chk']:checked").each(function(){
			param = param + $(this).val() + ",";
		});
		if(param == ""){
			Dialog.alert(lang.mail.M0041);
		}else{
			var diag = new Dialog();
			diag.Title = lang.mail.M0032;
			diag.URL = "netdisk";
			diag.Width = 461;
			diag.Height = 250;
			diag.OKEvent = function(){
				var doc=diag.innerFrame.contentWindow.document;
				copyfile(0,doc,diag,0);
			};
			diag.CancelEvent = function(){
				clrAll();
				diag.close();
			};
			diag.OkButtonText = lang.common.COM004;
			diag.CancelButtonText = lang.common.COM005;
			diag.show();
		}
	});

	$('#chkall').bind('click',function(){
		if(!$('#chkall').attr('checked')){
			clrAll();
		}else{
			chkAll();
		}
	});
});

function copyfile(cover,doc,diag,type){
	var param = "";
	$("[name='chk']:checked").each(function(){
		param = param + $(this).val() + ",";
	});
	var tarfolder = doc.getElementById('tarfolder').value;
	var folder = $('#folder').val();

	if(tarfolder==""){
		Dialog.alert(lang.disk.D0029);
	}else if(param == ""){
		Dialog.alert(lang.mail.M0041);
	}else{
		$.ajax({
			type: "post",
			url : "copytodisk",
			dataType:'json',
			data:"file="+param+"&t="+tarfolder+"&f="+folder+"&type="+type+"&cover="+cover,
			success: function(data,textStatus){
				if(data.state==1){
					parent.showtip(data.tip);
					diag.close();
				}else if(data.state==2){
					Dialog.alert(data.tip);
				}else if(data.state==3){
					if(confirm(data.tip)){
						copyfile(1,doc,diag,type);
					}
				}else{
					Dialog.alert(lang.common.COM021);
				}
			}
		});
	}
}

function list(page,sort,order,blank){
	if(blank!=1){
		$('#readmail').attr('src','blank');
	}
	if(typeof(sort)=="undefined")sort = 'RCV';
	if(typeof(order)=="undefined")order = 'DSC';
	$("#listsort").val(sort);
	$("#listorder").val(order);
	var ptype = 0;
	var mode = 0;
	if($('#pagetype').val()=='iframe'){
		ptype = 1;
		mode = 1;
	}

	if($('#listdata').html()!=''){
		var listdata = eval('('+$('#listdata').html()+')');
		prcesslist(listdata,mode);
		$('#listdata').html('');
	}else{
		$.ajax({
			type: "post",
			url : "maillist",
			timeout: 60000,
			dataType:'json',
			data:"f="+$('#folder').val()+"&page="+page+"&type="+ptype+"&order="+order+"&sort="+sort+"&ajax=1&time="+new Date().getTime(),
			beforeSend:parent.showloadingtip(),
			error:function(XMLHttpRequest, textStatus, errorThrown){
				if(textStatus=='error'){
					parent.closeloadingtip();
					parent.showtip2('<font color="red">'+lang.common.COM083+'</font>');
				}else if(textStatus=='timeout'){
					parent.closeloadingtip();
					parent.showtip2('<font color="red">'+lang.common.COM084+'</font>');
				}
			},
			success: function(data,textStatus){
				if(data){
					prcesslist(data,mode);
				}else{
					parent.closeloadingtip();
					parent.showtip2('<font color="red">'+lang.common.COM083+'</font>');
				}
			}
		});
	}
}

function prcesslist(data,mode){
	var row = data.data;
	$('#maillist').empty();
	var strlist = "";
	if(row.length>0){
		$('#cntrow').val(row.length);
		for(var i=0;i<row.length;i++){
			var icon = 'icoMail';
			var icontitle = lang.mail.M0064;
			var isattach = '';
			var isflag = '';
			var trclass = "tr1";
			var tdclass = "td1";
			var priority  = '';
			if(row[i].read==1){
				icon = 'icoRead';
				tdclass = "td2";
				icontitle = lang.mail.M0063;
			}
			if(row[i].attach==1){
				isattach = '<span class="icoMailListFile" title="'+lang.mail.M0065+'"></span>';
			}
			if(row[i].flag==1){
				isflag = '<span class="icoJob"></span>';
			}

			if(row[i].replied==1){
				icon = 'icoReplied';
				icontitle = lang.mail.M0061;
			}

			if(row[i].forwarded==1){
				icon = 'icoRorwarded';
				icontitle = lang.mail.M0062;
			}

			if(row[i].timesend!=0&&typeof(row[i].timesend)!="undefined"){
				icon = 'icoMailTime';
				icontitle = lang.mail.M0140;
			}

			if(row[i].randsendtime!=0&&typeof(row[i].randsendtime)!="undefined"){
				icon = 'icoMailTimeLapse';
				icontitle = lang.mail.M0140;
			}

			var link = "readmail";
			if($('#folder').val()=='draft')link = "write";

			if(row[i].priority>=4){
				priority  = '<span class="icoArrow" title="'+lang.mail.M0020+'">↓</span>';
			}else if((row[i].priority>=1) && (row[i].priority<=2)){
				priority  = '<span class="icoEmp" title="'+lang.mail.M0019+'">！</span>';
			}

			if(i%2)trclass = "tr2";
			var mstr = row[i].from;
			var mstrtip = row[i].fromtip;
			var stringfolder = $('#folder').val();
			var firstfolder = stringfolder.substring(0, 8);
			if(stringfolder == "sent" || firstfolder == '73656e74'){
				var mstr = row[i].to;
				var mstrtip = row[i].totip;
			}

			var recallicon = "";
			if(stringfolder == "sent" || firstfolder == '73656e74'){
				if(row[i].recall==undefined){
					var recallicon = "";
				}else if(row[i].recall!=0){
					recallicon = "<img src='../../image/recallicon.png' title=''></img>";
				}else if(row[i].recall==0){
					recallicon = "<img src='../../image/recallicon2.png' title=''></img>";
				}
			}

			if(mode){
				strlist = '<tr class="'+trclass+'" id="tr_'+i+'">'+
				'<td width="5%" class="'+tdclass+'"><input type="checkbox" name="chk" id="chk_'+i+'" value="'+row[i].file+'" alt="'+trclass+'" class="chkSlt" onclick="event.cancelBubble=true;"><input type="radio" name="sel" id="sel_'+i+'" value="mid='+row[i].file+'" class="selradio" style="display:none;"><input type="hidden" class="trid" value="'+i+'"><input type="hidden" class="curpage" value="'+data.curpage+'"><input type="hidden" id="isread_'+i+'" value="'+row[i].read+'"></td>'+
				'<td width="7%" class="'+tdclass+'">'+priority+'<span class="'+icon+'" title="'+icontitle+'" style="margin-left:15px;" id="icon_'+i+'"></span>'+isattach+'</td>'+
				'<td width="16%" class="'+tdclass+'" title="'+mstrtip+'">'+mstr+'</td>'+
				'<td width="50%" class="'+tdclass+'" style="cursor:default;"><span id="chk_'+i+'_recallicon">'+recallicon+'</span> <span id="flag_'+i+'">'+isflag+'</span> '+row[i].subject+'</td>'+
				'<td width="17%" class="'+tdclass+'">'+row[i].received+'</td>'+
				'<td width="5%" class="'+tdclass+'">'+row[i].size+'</td>'+
				'</tr>';
			}else{
				strlist = '<tr class="'+trclass+'" id="tr_'+i+'" style="cursor:default;">'+
				'<td width="5%" class="'+tdclass+'"><input type="checkbox" name="chk" id="chk_'+i+'" value="'+row[i].file+'" alt="'+trclass+'" class="chkSlt" onclick="clickchk=1;"><input type="radio" name="sel" id="sel_'+i+'" value="'+row[i].file+'" class="selradio" style="display:none;"><input type="hidden" class="trid" value="'+i+'"><input type="hidden" class="curpage" value="'+data.curpage+'"><input type="hidden" class="mailval" value="mid='+row[i].file+'"><input type="hidden" id="isread_'+i+'" value="'+row[i].read+'"></td>'+
				'<td width="7%" class="'+tdclass+'" style="cursor:pointer;">'+priority+'<span class="'+icon+'" title="'+icontitle+'" style="margin-left:15px;"></span>'+isattach+'</td>'+
				'<td width="16%" class="'+tdclass+'" title="'+mstrtip+'" style="cursor:pointer;">'+mstr+'</td>'+
				'<td width="50%" class="'+tdclass+'" style="cursor:pointer;"><span id="chk_'+i+'_recallicon">'+recallicon+'</span> '+isflag+' '+row[i].subject+'</td>'+
				'<td width="17%" class="'+tdclass+'" style="cursor:pointer;">'+row[i].received+'</td>'+
				'<td width="5%" class="'+tdclass+'" style="cursor:pointer;">'+row[i].size+'</td>'+
				'</tr>';
			}
			$('#maillist').append(strlist);
		}

		if(mode){
			var rows = parseInt($('#p1').val());
			if(mode)rows = parseInt($('#p2').val());
			if(trclass=='tr1'){
				trclass = 'tr4';
			}else{
				trclass = 'tr3';
			}
			if(row.length<rows){
				for(var i=0;i<(rows-row.length);i++){
					strlist = '<tr class="'+trclass+'">'+
					'<td width="5%" class="'+tdclass+'">&nbsp;</td>'+
					'<td width="7%" class="'+tdclass+'">&nbsp;</td>'+
					'<td width="16%" class="'+tdclass+'">&nbsp;</td>'+
					'<td width="50%" class="'+tdclass+'">&nbsp;</td>'+
					'<td width="17%" class="'+tdclass+'">&nbsp;</td>'+
					'<td width="5%" class="'+tdclass+'">&nbsp;</td>'+
					'</tr>';
					$('#maillist').append(strlist);
					if(trclass=='tr3'){
						trclass = 'tr4';
					}else{
						trclass = 'tr3';
					}
				}
			}
		}
	}else{
		var blank = "<div style='text-align:center;padding:20px 0px 20px 0px;font-size:12px;'>"+lang.disk.D0040+"</div>";
		if(mode){
			blank = "<div style='text-align:center;padding:20px 0px 20px 0px;font-size:12px;height:120px;'>"+lang.disk.D0040+"</div>";
		}
		$('.divMailList2').html(blank);
	}
	$('.mailsCnt').html(data.total);
	$('.mailsunreadCnt').html(data.unread);

	if(mode){
		$('.tr1,.tr2').bind('click',function(){
			$(this).find('.selradio').attr('checked',true);
			$(this).css("background","#316AC5");
			var id = $(this).attr('id');
			if($('#isread_'+$(this).find('.trid').val()).val()==0){
				parent.setinfo($('#folder').val(),'dec','unread',1);
			}
			openMail($(this).find('.selradio').val(),'iframe',$(this).find('.trid').val(),$(this).find('.curpage').val());
			var i = 0;
			$("[name='sel']").each(function(){
				if(!$(this).attr('checked')){
					$('#tr_'+i+' td').css("color","#000000");
					if($('#tr_'+i).attr('class')=='tr1'){
						$('#tr_'+i).css("background","#eeeeee");
					}else{
						$('#tr_'+i).css("background","#ffffff");
					}
				}
				i++;
			});
			$('#'+id+' td').css("color","#ffffff");
		});

		$('.tr1,.tr2').bind('dblclick',function(){
			openMail($(this).find('.selradio').val(),'normal',$(this).find('.trid').val(),$(this).find('.curpage').val());
		});
	}else{
		setHover(mode);

		$('.tr1,.tr2').bind('click',function(){
			var param = $(this).find('.mailval').val();
			var trid = $(this).find('.trid').val();
			var curpage = $(this).find('.curpage').val();
			timer = setTimeout(function(){
				if(!clickchk){
					if($('#isread_'+trid).val()==0){
						parent.setinfo($('#folder').val(),'dec','unread',1);
					}
					openMail(param,'origin',trid,curpage);
				}
				clickchk = 0;
			},300);
		});

		if(!ie){
			$('.tr1,.tr2').bind('dblclick',function(){
				clickchk = 1;
				var trid = $(this).find('.trid').val();
				if($('#isread_'+trid).val()==0){
					parent.setinfo($('#folder').val(),'dec','unread',1);
				}
				openMail($(this).find('.mailval').val(),'normal',$(this).find('.trid').val(),$(this).find('.curpage').val());
				clearTimeout(timer);
			});
		}else{
			$('.tr1,.tr2').bind('dblclick',function(){
				var trid = $(this).find('.trid').val();
				if($('#isread_'+trid).val()==0){
					parent.setinfo($('#folder').val(),'dec','unread',1);
				}
				openMail($(this).find('.mailval').val(),'normal',$(this).find('.trid').val(),$(this).find('.curpage').val());
				clearTimeout(timer);
			});
		}
	}
	$('#maxpage').val(data.maxpage);
	$('#curpage').val(data.curpage);
	setpage(data.curpage,data.maxpage);
	$('#readmail_layout').css('visibility','visible');
	parent.closetip();
	parent.closeloadingtip();
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
		sublist = sublist + '<li><a href="javascript:void(0)" onclick="list('+i+',\''+$("#listsort").val()+'\',\''+$("#listorder").val()+'\');" '+mark+'>'+i+'</a></li>';
	}
	var strlist = "";
	if(maxpage>1){
		var pageopt = '';
		if(maxpage<=50){
			for(var i=1;i<=maxpage;i++){
				if(curpage==i){
					pageopt = pageopt + '<option value='+i+' selected>'+i+'/'+maxpage+'</option>';
				}else{
					pageopt = pageopt + '<option value='+i+'>'+i+'/'+maxpage+'</option>';
				}
			}
		}else{
			var remainder = maxpage%10;
			for(var i=10;i<=maxpage;i=i+10){
				if(((curpage>=(i-10))&&(curpage<=i))||((i+remainder)==maxpage)&&((curpage>=(maxpage-remainder))&&(curpage<=maxpage))){
					pageopt = pageopt + '<option value='+curpage+' selected>'+curpage+'/'+maxpage+'</option>';
				}else{
					pageopt = pageopt + '<option value='+i+'>'+i+'/'+maxpage+'</option>';
				}
			}
		}

		strlist = '<span><a href="javascript:void(1)" onclick="list(1,\''+$("#listsort").val()+'\',\''+$("#listorder").val()+'\');" class="jumpFirst"></a> </span>' +
		'<span><a href="javascript:void(1)" onclick="list('+prepage+',\''+$("#listsort").val()+'\',\''+$("#listorder").val()+'\');" class="jumpPrev" title="'+lang.mail.M0008+'"></a></span>' +
		'<span class="sltBoxPageUpper"><select class="sltStyle" style="height:20px;" id="pageselect" onchange="selpage();">'+pageopt+'</select></span>' +
		'<span><a href="javascript:void(1)"onclick="list('+nxtpage+',\''+$("#listsort").val()+'\',\''+$("#listorder").val()+'\');" class="jumpNext" title="'+lang.mail.M0009+'"></a></span>' +
		'<span><a href="javascript:void(1)"onclick="list('+maxpage+',\''+$("#listsort").val()+'\',\''+$("#listorder").val()+'\');" class="jumpLast"></a></span>';
	}
	$('#pagelist').html(strlist);
}

function selpage(){
	list($('#pageselect').val(),$("#listsort").val(),$("#listorder").val());
}

function chkAll(){
	$("[name='chk']").attr("checked",'true');
	$('#chkall').attr('checked',true);
}

function clrAll(){
	$("[name='chk']").removeAttr("checked");
	$('#chkall').removeAttr("checked");
}

function antiSel(){
	$("[name='chk']").each(function(){
		if($(this).attr('checked')){
			$(this).removeAttr('checked');
			if($(this).attr('alt')=='tr1'){
				$(this).parents('tr').css("background","#eeeeee");
			}else{
				$(this).parents('tr').css("background","#ffffff");
			}
			//$('.tr1').hover(function(){$(this).parents('tr').css("background","#316AC5");},function(){$(this).parents('tr').css("background","#eeeeee");});
			//$('.tr2').hover(function(){$(this).parents('tr').css("background","#316AC5");},function(){$(this).parents('tr').css("background","#ffffff");});
		}else{
			$(this).attr('checked',true);
			$(this).parents('tr').css("background","#316AC5");
			//$('.tr1').hover(function(){$(this).parents('tr').css("background","#316AC5");},function(){$(this).parents('tr').css("background","#316AC5");});
			//$('.tr2').hover(function(){$(this).parents('tr').css("background","#316AC5");},function(){$(this).parents('tr').css("background","#316AC5");});
		}
	});

}

function moveMail(oper){
	var mid = "";
	var readcnt = 0;
	var recallmark = 0;
	$("[name='chk']").each(function(){
		if($(this).attr('checked')){
			mid = mid + $(this).val() + ",";
			if($('#'+$(this).attr('id')+'_recallicon').html()!=""){
				recallmark = 1;
			}
		}
	});

	if(mid == ""){
		Dialog.alert(lang.mail.M0001);
	}else{
		if($('#folder').val()==oper){
			Dialog.alert(lang.mail.M0002);
		}else if(recallmark){
			if(window.confirm(lang.mail.M0155)){
				$.ajax({
					type: "post",
					url : "movemails",
					dataType:'html',
					data:"cf="+$('#folder').val()+"&mid="+mid+"&tf="+oper+"&time="+new Date().getTime(),
					beforeSend:function(){parent.showtip2(lang.common.COM085+'...')},
					success: function(data,textStatus){
						if(data){
							parent.showtip(lang.mail.M0003);
							parent.getinfo();
							if($('#maxpage').val()==$('#curpage').val()){
								if($('#curpage').val()>1){
									list($('#curpage').val()-1,$("#listsort").val(),$("#listorder").val());
								}else{
									list($('#curpage').val(),$("#listsort").val(),$("#listorder").val());
								}
							}else{
								list($('#curpage').val(),$("#listsort").val(),$("#listorder").val());
							}
						}else{
							Dialog.alert(lang.mail.M0004);
							parent.closetip();
						}
					}
				});
				clrAll();
			}
		}else{
			$.ajax({
				type: "post",
				url : "movemails",
				dataType:'html',
				data:"cf="+$('#folder').val()+"&mid="+mid+"&tf="+oper+"&time="+new Date().getTime(),
				beforeSend:function(){parent.showtip2(lang.common.COM085+'...')},
				success: function(data,textStatus){
					if(data){
						parent.showtip(lang.mail.M0003);
						parent.getinfo();
						if($('#maxpage').val()==$('#curpage').val()){
							if($('#curpage').val()>1){
								list($('#curpage').val()-1,$("#listsort").val(),$("#listorder").val());
							}else{
								list($('#curpage').val(),$("#listsort").val(),$("#listorder").val());
							}
						}else{
							list($('#curpage').val(),$("#listsort").val(),$("#listorder").val());
						}
					}else{
						Dialog.alert(lang.mail.M0004);
						parent.closetip();
					}
				}
			});
			clrAll();
		}
	}
}

function setMail(mark){
	var oper = mark.split(".");
	var mid = "";
	var i = 0;
	var readcnt = 0;
	var info = new Array();
	$("[name='chk']").each(function(){
		if($(this).attr('checked')){
			mid = mid + $(this).val() + ",";
			info[i] = $('#isread_'+i).val();
		}
		i++;
	});
	if(mid == ""){
		Dialog.alert(lang.mail.M0001);
	}else{
		$.ajax({
			type: "post",
			url : "setmail",
			dataType:'html',
			data:"f="+$('#folder').val()+"&mid="+mid+"&oper="+oper[0]+"&val="+oper[1]+"&time="+new Date().getTime(),
			success: function(data,textStatus){
				if(data){
					parent.showtip(lang.mail.M0003);
					for(var j=0;j<i;j++){
						if(typeof(info[j])!='undefined'){
							if(oper[0]=='read'){
								if(oper[1]==1){
									if(info[j]==0){
										readcnt = readcnt - 1;
									}
								}else{
									if(info[j]==1){
										readcnt = readcnt + 1;
									}
								}
							}
						}
					}
					if(readcnt>0){
						parent.setinfo($('#folder').val(),'inc','unread',Math.abs(readcnt));
					}else if(readcnt<0){
						parent.setinfo($('#folder').val(),'dec','unread',Math.abs(readcnt));
					}
					list($('#curpage').val(),$("#listsort").val(),$("#listorder").val());
				}else{
					Dialog.alert(lang.mail.M0004);
				}
			}
		});
		clrAll();
	}
}

function delMails(){
	var param = "";
	var recallmark = 0;
	$("[name='chk']:checked").each(function(){
		param = param + $(this).val() + ",";
		if($('#'+$(this).attr('id')+'_recallicon').html()!=""){
			recallmark = 1;
		}
	});
	if(param == ""){
		Dialog.alert(lang.mail.M0005);
	}else if(recallmark){
		Dialog.confirm(lang.mail.M0155,function(){
			$.ajax({
				type: "post",
				url : "delmails",
				dataType:'html',
				data:"f="+$('#folder').val()+"&param="+param+"&time="+new Date().getTime(),
				beforeSend:function(){parent.showtip2(lang.common.COM085+'...')},
				success: function(data,textStatus){
					if(data){
						parent.showtip(lang.mail.M0007);
						if($('#maxpage').val()==$('#curpage').val()){
							if($('#curpage').val()>1){
								list($('#curpage').val()-1,$("#listsort").val(),$("#listorder").val());
							}else{
								list($('#curpage').val(),$("#listsort").val(),$("#listorder").val());
							}
						}else{
							list($('#curpage').val(),$("#listsort").val(),$("#listorder").val());
						}
						parent.getinfo();
					}else{
						Dialog.alert(lang.mail.M0004);
						parent.closetip();
					}
				}
			});
			clrAll();
		});
	}else{
		Dialog.confirm(lang.mail.M0006,function(){
			$.ajax({
				type: "post",
				url : "delmails",
				dataType:'html',
				data:"f="+$('#folder').val()+"&param="+param+"&time="+new Date().getTime(),
				beforeSend:function(){parent.showtip2(lang.common.COM085+'...')},
				success: function(data,textStatus){
					if(data){
						parent.showtip(lang.mail.M0007);
						if($('#maxpage').val()==$('#curpage').val()){
							if($('#curpage').val()>1){
								list($('#curpage').val()-1,$("#listsort").val(),$("#listorder").val());
							}else{
								list($('#curpage').val(),$("#listsort").val(),$("#listorder").val());
							}
						}else{
							list($('#curpage').val(),$("#listsort").val(),$("#listorder").val());
						}
						parent.getinfo();
					}else{
						Dialog.alert(lang.mail.M0004);
						parent.closetip();
					}
				}
			});
			clrAll();
		});
	}
}

function checkOrder(id,num){
	var up = $('#col_'+id+'_Up');
	var down = $('#col_'+id+'_Down');
	if(((up.css('display') == 'none') && (down.css('display') == 'none')) || (down.css('display') == 'none')){
		clearOrder(num);
		up.css('display','none');
		down.css('display','inline');
		return 'DSC';
	}else if(up.css('display') == 'none'){
		up.css('display','inline');
		down.css('display','none');
		return 'ASC';
	}
}

function clearOrder(num){
	for(var i=1;i<=num;i++){
		$('#col_'+i+'_Up').css('display','none');
		$('#col_'+i+'_Down').css('display','none');
	}
}

function clearMove(){
	var i = 0
	$("[name='chk']").each(function(){
		if(!$(this).attr('checked')){
			if($('#tr_'+i).attr('class')=='tr1'){
				$('#tr_'+i).css("background","#eeeeee");
			}else{
				$('#tr_'+i).css("background","#ffffff");
			}
		}
		i++;
	});
}

function setHover(mode){
	$('#maillist tr').hover(function(){
		clearMove();
		$(this).css("background","#316AC5");
		$('td',this).css("color","#FFFFFF");
		$(this).find('.selradio').attr('checked','true');
		if(mode){
			openMail($(this).find('.selradio').val(),'iframe',$(this).find('.curpage').val());
		}
	},
	function(){
		$(this).find('.selradio').removeAttr('checked');
		$('td',this).css("color","#000000");
		if($(this).attr('class')=='tr1'){
			$(this).css("background","#eeeeee");
		}else{
			$(this).css("background","#ffffff");
		}
	});
}

function openMail(mid,mode,id,curpage){
	var url = 'readmail?'+mid+"&f="+$('#folder').val()+"&id="+id+"&order="+$('#listsort').val()+"&sort="+$('#listorder').val()+"&curpage="+curpage+"&time="+new Date().getTime();
	$('#tr_'+id+' td').attr('class','td2');
	if($('#icon_'+id).attr('class')!='icoMailTime'&&$('#icon_'+id).attr('class')!='icoMailTimeLapse'&&$('#icon_'+id).attr('class')!='icoReplied'&&$('#icon_'+id).attr('class')!='icoRorwarded'){
		$('#icon_'+id).attr('class','icoRead');
	}
	if(mode=='iframe'){
		$('#isread_'+id).val(1);
		if($('#folder').val()=='draft'){
			url = url + "&edit=1";
		}else{
			url = url + "&type=1";
		}
		$('#readmail').attr('src',url);
	}else if(mode=='origin'){
		$('#isread_'+id).val(1);
		if($('#folder').val()=='draft'){
			url = 'write?'+mid+"&f="+$('#folder').val()+"&id="+id;
		}else{
			url = url + "&type=1";
		}
		url = "../mail/" + url + "&page=" + $('#curpage').val() +"&back=1";
		parent.$('#frame_content').attr('src',url);
	}else{
		$('#isread_'+id).val(1);
		if($('#folder').val()=='draft'){
			url = 'write?'+mid+"&f="+$('#folder').val()+"&id="+id;
		}
		list($('#curpage').val());
		window.open(url);
	}
}

function closeList(mailbox){
	var page = Math.ceil((parseInt($('#p1').val())*(parseInt($('#curpage').val()-1))+1)/parseInt($('#p2').val()));
	if(parseInt($('#curpage').val())<=1){
		page = 1;
	}
	window.location.href = "mailbox?f="+mailbox+"&type=1&page="+page;
}

function closeMyFolderList(folder,fname){
	var page = Math.ceil((parseInt($('#p1').val())*(parseInt($('#curpage').val()-1))+1)/parseInt($('#p2').val()));
	if(parseInt($('#curpage').val())<=1){
		page = 1;
	}
	window.location.href = "myfolder?type=1&folder="+folder+"&fname="+fname+"&type=1&page="+page;
}

function pageUp(){
	var curpage = parseInt($('#curpage').val());
	if(curpage<=1){
		list(1);
	}else{
		list(curpage-1);
	}
}

function pageDown(){
	var curpage = parseInt($('#curpage').val());
	var maxpage = parseInt($('#maxpage').val());
	if(curpage>=maxpage){
		list(maxpage);
	}else{
		list(curpage+1);
	}
}

function clearMailbox(folder){
	Dialog.confirm(lang.mail.M0110,function(){
		$.ajax({
			type: "post",
			url : "../mail/clearmailbox?time="+new Date().getTime(),
			dataType:'html',
			data:"f="+folder+"&time="+new Date().getTime(),
			success: function(data,textStatus){
				if(data){
					parent.getinfo();
					list($('#curpage').val());
				}
			}
		});
	});
}

function attachtransmit(){
	var param = "";
	$("[name='chk']:checked").each(function(){
		param = param + $(this).val() + ",";
	});
	if(param == ""){
		Dialog.alert(lang.mail.M0059);
	}else{
		var url = 'write?mids='+param+'&f='+$('#folder').val()+'&oper=attachs';
		window.open(url);
		clrAll();
	}
}

function closeIframe(mailbox){
	var page = Math.ceil((parseInt($('#p2').val())*(parseInt($('#curpage').val())-1)+1)/parseInt($('#p1').val()));
	window.location.href = "mailbox?f=" + mailbox + "&type=2&page="+page;
}

function checkonload(){
	if(document.readyState != "complete"){
		window.location.reload();
	}
}

String.prototype.repeat = function(n) {
	var _this = this;
	var result = '';
	for(var i=0;i<n;i++) {
		result += _this;
	}
	
	return result;
}


function stringToJson(stringValue) { 
	eval("var theJsonValue = "+stringValue); 
	return theJsonValue; 
}

function listItemfolder(type, fdata, depth, path, ppath) {
	var strfolder = '';
	var showid;
	var titile;
	var oper;
	var t = new String("&nbsp;&nbsp;&nbsp;").repeat(depth);
	
	
	if (0 == type) {
		if(0 == depth) {
			strfolder = strfolder + '<option value="">' + lang.mail.M0170 + '</option>';
		}
		for (var k in fdata) {
			if (path == fdata[k].path) {
				continue;
			}
		
			if (ppath == fdata[k].path) {
				strfolder = strfolder + '<option value="' + fdata[k].path +'" selected>' + t + fdata[k].name + '</option>';
			} else {
				strfolder = strfolder + '<option value="' + fdata[k].path +'">' + t + fdata[k].name + '</option>';
			}
			if (typeof(fdata[k].sub) != "undefined") {
				strfolder = strfolder + listItemfolder(type, fdata[k].sub, depth + 1, path, ppath);
			}
		
		}
	} else {
		if (0 == depth) {
			strfolder = strfolder + '<option value="" selected>' + lang.mail.M0031 + '...</option>';
		}
		for (var k in fdata) {
			strfolder = strfolder + '<option value="' + fdata[k].path +'">' + t + fdata[k].name + '</option>';
			if (typeof(fdata[k].sub) != "undefined") {
				strfolder = strfolder + listItemfolder(type, fdata[k].sub, depth + 1, path, ppath);
			}
		}
		
	}
	
	return strfolder;
}

function folderSelect(type, select_id, fdata, path, ppath) {
	if (0 == type) {
		var strfolder = '<select id="' + select_id + '" style="height:24px;font-size:14px;">';
	} else {
		var strfolder = '<select id="' + select_id + '" style="height:24px;">';
	}
	strfolder = strfolder + listItemfolder(type, fdata, 0, path, ppath);
	strfolder = strfolder + '<select>';
	return strfolder;
}

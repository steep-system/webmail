var timer = null;
var readtimer = null;
var clickchk = 0;
var ie = !-[1,];

function JSON_stringify(obj) {
    var t = typeof (obj);
    if (t != "object" || obj === null) {
        // simple data type
        if (t == "string") obj = '"'+obj+'"';
        return String(obj);
    }
    else {
        // recurse array or object
        var n, v, json = [], arr = (obj && obj.constructor == Array);
        for (n in obj) {
            v = obj[n]; t = typeof(v);
            if (t == "string") v = '"'+v+'"';
            else if (t == "object" && v !== null) v = JSON_stringify(v);
            json.push((arr ? "" : '"' + n + '":') + String(v));
        }
        return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
    }
};


function list(type,c,ctype,id,sort,order){
	$.ajax({
		type: "post",
		url : "getuserlist",
		dataType:'json',
		data:"class="+c+"&ctype="+ctype+"&id="+id+"&sort="+sort+"&order="+order+"&time="+new Date().getTime(),
		beforeSend:function(){if(self.frameElement && self.frameElement.tagName=="IFRAME"){ parent.showloadingtip();}},
		success: function(data,textStatus){
			if (typeof JSON == "undefined") {
				$("#listdata").text(JSON_stringify(data));
			} else {
				$("#listdata").text(JSON.stringify(data));
			}
			$('#userlist').empty();
			$('#usercount').html(data.total);
			if(data.total>0){
				var row = data.data;
				var strlist = "";
				var line = 0;
				var unixTimestamp;
				var commonTime;
				if(row.length>0){
					for(var i=0;i<row.length;i++){
						var trclass = "tr1";
						if(i%2)trclass = "tr2";
						var contacttel = '';
						if(type==1){
							unixTimestamp = new Date(row[i].updatetime * 1000);
							commonTime = unixTimestamp.toLocaleString();
							if(typeof(row[i].id)!='undefined'){
								if(row[i].tel!=null)contacttel = contacttel + row[i].tel;
								if(row[i].cell!=null)contacttel = contacttel + " " + row[i].cell;
								strlist = '<tr class="'+trclass+'">'+
								'<td class="td6">&nbsp;</td>'+
								'<td class="td1"><input type="checkbox" class="chkSel" name="chk" id="chk_'+i+'" value="'+row[i].id+'" alt="'+trclass+'" title="'+row[i].fulladdress+'" onclick="event.cancelBubble=true;"><input type="radio" name="sel" id="sel_'+i+'" value="id='+row[i].id+'&p='+row[i].offset+'" class="selradio" style="display:none;"><input type="hidden" class="selData" value="'+row[i].id+'"></td>'+
								'<td class="td2">'+row[i].realname+'</td>'+
								'<td class="td3">'+row[i].email+'</td>'+
								'<td class="td5">'+contacttel+'</td>'+
								'<td class="td4">'+commonTime+'</td>'+
								'</tr>';
								$('#userlist').append(strlist);
							}
						}else if(type==2){
							if(typeof(row[i].id)!='undefined'){
								var nickname = row[i].nickname;
								var company = row[i].company;
								var tel = lang.contact.C0020+'：'+row[i].tel;
								var cell = lang.contact.C0019+'：'+row[i].cell;
								if(nickname==''||nickname==null)nickname = row[i].realname;
								if(row[i].company==null)company = '&nbsp;';
								if(row[i].tel==null)tel = '&nbsp;';
								if(row[i].cell==null)cell = '&nbsp;';
								strlist = '<div class="contactCard" ondblclick="opendetail(\''+row[i].id+'\');">'+
								'<div class="contactCardPic">'+
								'<table height="20" border="0" cellpadding="0" cellspacing="0">'+
								'<tr>'+
								'<td class="contactListCheckBox"><input type="checkbox" name="chk" id="chk_'+i+'" value="'+row[i].id+'" alt="'+trclass+'" class="chkSlt" title="'+row[i].fulladdress+'" onclick="event.cancelBubble=true;"></td>'+
								'<td></td>'+
								'</tr>'+
								'</table>'+
								'<table  class="tbl" >'+
								'<tr><td rowspan="5"  class="tdLeft" valign="top"><div class="contactCardHead"></div></td></tr>'+
								'<tr><td><span class="boldName">'+row[i].realname+'</span></td></tr>'+
								'<tr><td>'+company+'</td></tr>'+
								'<tr><td>'+cell+'</td></tr>'+
								'<tr><td>'+tel+'</td></tr>'+
								'</table>'+
								'<div class="contactMail">'+row[i].email+'</div>'+
								'</div>'+
								'</div>';
								$('#userlist').append(strlist);
							}
						}
						line = i;
					}

					if(type==1){
						var blank_trclass = "tr4";
						var blank_hcolor = "#ffffff";
						if(line%2){
							blank_trclass = "tr3";
							blank_hcolor = "#eeeeee";
						}
						strlist = '<tr id="blankline" class="'+blank_trclass+'">'+
						'<td class="td6">&nbsp;</td>'+
						'<td class="td2">&nbsp;</td>'+
						'<td class="td3">&nbsp;</td>'+
						'<td class="td5">&nbsp;</td>'+
						'<td class="td4">&nbsp;</td>'+
						'<td class="td1">&nbsp;</td>'+
						'</tr>';
						$('#userlist').append(strlist);
						$('#blankline').hover(function(){$(this).css("background",blank_hcolor);},function(){$(this).css("background",blank_hcolor);});
						
						$('.tr1,.tr2').bind('click',function(){
							opendetail($(this).find('.selData').val());
						});
					}
				}
			}else{
				if(type==1){
					var blank = "<tr><td id='nocontact'><div style='text-align:center;padding:20px 0px 20px 0px;font-size:12px;'>"+lang.contact.C0040+"</div></td></tr>";
					$('#userlist').append(blank);
					$('#nocontact').hover(function(){$(this).css("background","#eeeeee");},function(){$(this).css("background","#eeeeee");});
				}else{
					var blank = "<div style='text-align:center;padding:20px 0px 20px 0px;font-size:12px;'>"+lang.contact.C0040+"</div>";
					$('#userlist').append(blank);
				}
			}
			parent.closeloadingtip();
		}
	});
}

function search(type,keyword){
	var data = eval('(' + $('#listdata').text() + ')');
	$('#userlist').empty();
	keyword = keyword.toLowerCase()
	if(data.total>0){
		var row = data.data;
		var strlist = "";
		var line = 0;
		var unixTimestamp;
		var commonTime;
		if(row.length>0){
			for(var i=0;i<row.length;i++){
				if (row[i].realname.toLowerCase().indexOf(keyword) < 0 && row[i].email.toLowerCase().indexOf(keyword) < 0) {
					continue;
				}
				var trclass = "tr1";
				if(line%2)trclass = "tr2";
				var contacttel = '';
				if(type==1){
					unixTimestamp = new Date(row[i].updatetime * 1000);
					commonTime = unixTimestamp.toLocaleString();
					if(typeof(row[i].id)!='undefined'){
						if(row[i].tel!=null)contacttel = contacttel + row[i].tel;
						if(row[i].cell!=null)contacttel = contacttel + " " + row[i].cell;
						strlist = '<tr class="'+trclass+'">'+
						'<td class="td6">&nbsp;</td>'+
						'<td class="td1"><input type="checkbox" class="chkSel" name="chk" id="chk_'+i+'" value="'+row[i].id+'" alt="'+trclass+'" title="'+row[i].fulladdress+'" onclick="event.cancelBubble=true;"><input type="radio" name="sel" id="sel_'+i+'" value="id='+row[i].id+'&p='+row[i].offset+'" class="selradio" style="display:none;"><input type="hidden" class="selData" value="'+row[i].id+'"></td>'+
						'<td class="td2">'+row[i].realname+'</td>'+
						'<td class="td3">'+row[i].email+'</td>'+
						'<td class="td5">'+contacttel+'</td>'+
						'<td class="td4">'+commonTime+'</td>'+
						'</tr>';
						$('#userlist').append(strlist);
					}
				}else if(type==2){
					if(typeof(row[i].id)!='undefined'){
						var nickname = row[i].nickname;
						var company = row[i].company;
						var tel = lang.contact.C0020+'：'+row[i].tel;
						var cell = lang.contact.C0019+'：'+row[i].cell;
						if(nickname==''||nickname==null)nickname = row[i].realname;
						if(row[i].company==null)company = '&nbsp;';
						if(row[i].tel==null)tel = '&nbsp;';
						if(row[i].cell==null)cell = '&nbsp;';
						strlist = '<div class="contactCard" ondblclick="opendetail(\''+row[i].id+'\');">'+
						'<div class="contactCardPic">'+
						'<table height="20" border="0" cellpadding="0" cellspacing="0">'+
						'<tr>'+
						'<td class="contactListCheckBox"><input type="checkbox" name="chk" id="chk_'+i+'" value="'+row[i].id+'" alt="'+trclass+'" class="chkSlt" title="'+row[i].fulladdress+'" onclick="event.cancelBubble=true;"></td>'+
						'<td></td>'+
						'</tr>'+
						'</table>'+
						'<table  class="tbl" >'+
						'<tr><td rowspan="5"  class="tdLeft" valign="top"><div class="contactCardHead"></div></td></tr>'+
						'<tr><td><span class="boldName">'+row[i].realname+'</span></td></tr>'+
						'<tr><td>'+company+'</td></tr>'+
						'<tr><td>'+cell+'</td></tr>'+
						'<tr><td>'+tel+'</td></tr>'+
						'</table>'+
						'<div class="contactMail">'+row[i].email+'</div>'+
						'</div>'+
						'</div>';
						$('#userlist').append(strlist);
					}
				}
				line ++;
			}

			if(type==1){
				var blank_trclass = "tr4";
				var blank_hcolor = "#ffffff";
				if((line-1)%2){
					blank_trclass = "tr3";
					blank_hcolor = "#eeeeee";
				}
				strlist = '<tr id="blankline" class="'+blank_trclass+'">'+
				'<td class="td6">&nbsp;</td>'+
				'<td class="td2">&nbsp;</td>'+
				'<td class="td3">&nbsp;</td>'+
				'<td class="td5">&nbsp;</td>'+
				'<td class="td4">&nbsp;</td>'+
				'<td class="td1">&nbsp;</td>'+
				'</tr>';
				$('#userlist').append(strlist);
				$('#blankline').hover(function(){$(this).css("background",blank_hcolor);},function(){$(this).css("background",blank_hcolor);});
				
				$('.tr1,.tr2').bind('click',function(){
					opendetail($(this).find('.selData').val());
				});
			}
		}
		$('#usercount').html(line);
	}else{
		if(type==1){
			var blank = "<tr><td id='nocontact'><div style='text-align:center;padding:20px 0px 20px 0px;font-size:12px;'>"+lang.contact.C0040+"</div></td></tr>";
			$('#userlist').append(blank);
			$('#nocontact').hover(function(){$(this).css("background","#eeeeee");},function(){$(this).css("background","#eeeeee");});
		}else{
			var blank = "<div style='text-align:center;padding:20px 0px 20px 0px;font-size:12px;'>"+lang.contact.C0040+"</div>";
			$('#userlist').append(blank);
		}
		$('#usercount').html(0);
	}
	parent.closeloadingtip();
}


function editgroup(name,gid){
	//checksession();
	var diag = new Dialog();
	diag.Title = lang.contact.C0004;
	diag.URL = "editgroup?name="+name+"&gid="+gid;
	diag.Width = 461;
	diag.Height = 100;
	diag.OKEvent = function(){
		var doc = diag.innerFrame.contentWindow.document;
		var groupname = doc.getElementById('groupname').value
		if(groupname!=""){
			var param = "gname="+groupname+"&gid="+gid;
			$.ajax({
				type: "post",
				url : "savegroup",
				dataType:'html',
				data:param,
				success: function(data,textStatus){
					if(data==1){
						showtree();
						diag.close();
					}else if(data==2){
						alert(lang.contact.C0002);
					}else{
						alert(lang.contact.E1000);
					}
				}
			});
		}else{
			alert(lang.contact.C0005);
		}
	};
	diag.CancelEvent = function(){
		diag.close();
	}
	diag.show();
}

function importfile(){
	//checksession();
	var diag = new Dialog();
	diag.Title = lang.contact.C0031;
	diag.URL = "import";
	diag.Width = 470;
	diag.Height = 130;
	diag.OKEvent = function(){
		var doc = diag.innerFrame.contentWindow.document;
		doc.form1.submit();
		list($('#curtype').val(),2,'root','','','');
		diag.close();
	};
	diag.CancelEvent = function(){
		diag.close();
	}
	diag.show();
}


function clrAll(){
	var chk1 = $('.tr1').find('.chkSel');
	var chk2 = $('.tr2').find('.chkSel');
	$("[name='chk']").attr("checked",'true');
	$('.tr1').css("background","#eeeeee");
	$('.tr2').css("background","#ffffff");
	$('.tr1,.tr2').find('td').css("color","#000000");
	chk1.attr('checked',false);
	chk2.attr('checked',false);
	$('#chkall').removeAttr("checked");
	$('.tr1').hover(function(){$(this).css("background","#316AC5");},function(){$(this).css("background","#eeeeee");});
	$('.tr2').hover(function(){$(this).css("background","#316AC5");},function(){$(this).css("background","#ffffff");});
}

function chkBlockAll(){
	$("[name='chk']").attr("checked",'true');
	$('#chkall').removeAttr("checked");
}

function clrBlockAll(){
	$("[name='chk']").removeAttr("checked");
}

function chkAll(){
	if(!$('#chkall').attr('checked')){
		var chk1 = $('.tr1').find('.chkSel');
		var chk2 = $('.tr2').find('.chkSel');
		$("[name='chk']").removeAttr("checked");
		chk1.attr('checked',true);
		chk2.attr('checked',true);
		$('#chkall').attr('checked',true);
	}
}

function opendetail(id){
	parent.$('#frame_content').attr('src','../contact/showcontact?type='+$('#curlist').val()+'&mode='+$('#mode').val()+'&id='+id+'&time='+new Date().getTime());
}

function closeIframe(type){
	window.location.href = "list?type="+type;
}

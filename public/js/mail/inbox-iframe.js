$(document).ready( function() {
	$(document).bind('keydown', 'down', function(){
		moveRecord('down');
	});

	$(document).bind('keydown', 'up', function(){
		moveRecord('up');
	});

	$(document).bind('keydown', 'return', function(){
		var i = 0;
		$("[name='sel']").each(function(){
			if($(this).attr('checked')){
				if($('#chk_'+i).attr('checked')){
					$('#chk_'+i).removeAttr('checked');
				}else{
					$('#chk_'+i).attr('checked',true);
				}
			}
			i++;
		});
	});
});

function moveRecord(type){
	var mark = -1;
	var selsize = 0;
	var i = 0
	clearTimeout(readtimer);
	$("[name='sel']").each(function(){
		selsize+=1;
		if($(this).attr('checked')){
			mark = i;
		}
		i++;
	});
	if($('#cntrow').val()>0){
		if(mark<0){
			$('#sel_0').attr('checked','true');
			$('#tr_0').css("background","#316AC5");
			$('#tr_0 td').css("color","#ffffff");
			readtimer = setTimeout(function(){
				if($('#isread_0').val()==0){
					parent.setinfo($('#folder').val(),'dec','unread',1);
				}
				openMail($('#sel_0').val(),'iframe',0)
			},500);
		}else{
			if(type=='down'){
				var tmp = mark+1;
				if(mark>=(selsize-1)){
					var page = parseInt($('#curpage').val());
					if(page>=$('#maxpage').val()){
						page = $('#maxpage').val();
					}else{
						page+=1;
					}
					list(page);
				}else{
					$('#sel_'+mark).removeAttr('checked');
					$('#sel_'+tmp).attr('checked','true');
					readtimer = setTimeout(function(){
						if($('#isread_'+tmp).val()==0){
							parent.setinfo($('#folder').val(),'dec','unread',1);
						}
						openMail($('#sel_'+tmp).val(),'iframe',tmp);
					},500);

					if($('#tr_'+mark).attr('class')=='tr1'){
						$('#tr_'+mark).css("background","#eeeeee");
						$('#tr_'+mark+' td').css("color","#000000");
					}else{
						$('#tr_'+mark).css("background","#ffffff");
						$('#tr_'+mark+' td').css("color","#000000");
					}
					$('#tr_'+tmp).css("background","#316AC5");
					$('#tr_'+tmp+' td').css("color","#ffffff");
				}
			}else if(type=='up'){
				var tmp = mark-1;
				if(mark==0){
					var page = parseInt($('#curpage').val());
					if(page<=1){
						page = 1;
					}else{
						page-=1;
					}
					list(page);
				}else{
					$('#sel_'+mark).removeAttr('checked');
					$('#sel_'+tmp).attr('checked','true');
					readtimer = setTimeout(function(){
						if($('#isread_'+tmp).val()==0){
							parent.setinfo($('#folder').val(),'dec','unread',1);
						}
						openMail($('#sel_'+tmp).val(),'iframe',tmp);
					},500);

					if($('#tr_'+mark).attr('class')=='tr1'){
						$('#tr_'+mark).css("background","#eeeeee");
						$('#tr_'+mark+' td').css("color","#000000");
					}else{
						$('#tr_'+mark).css("background","#ffffff");
						$('#tr_'+mark+' td').css("color","#000000");
					}
					$('#tr_'+tmp).css("background","#316AC5");
					$('#tr_'+tmp+' td').css("color","#ffffff");
				}
			}
		}
	}
}

function closeIframeFolder(folder,fname){
	var page = Math.ceil((parseInt($('#p2').val())*(parseInt($('#curpage').val())-1)+1)/parseInt($('#p1').val()));
	window.location.href = "myfolder?type=2&folder="+folder+"&fname="+fname+"&page="+page;
}
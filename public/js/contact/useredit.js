function save(){
	if($('#realname').val()==""||$('#email').val()==""){
		Dialog.alert(lang.contact.C0039);
	}else if(!isemail($('#email').val())){
		Dialog.alert(lang.mail.M0083);
	}else{
		if($('#id').val()==''){
			var postdata = 'data={"realname":"'+encodeURIComponent($('#realname').val())+'","email":"'+encodeURIComponent($('#email').val())+'","cell":"'+encodeURIComponent($('#cell').val())+'","tel":"'+encodeURIComponent($('#tel').val())+'","nickname":"'+encodeURIComponent($('#nickname').val())+'","birthday":"'+encodeURIComponent($('#birthday').val())+'","address":"'+encodeURIComponent($('#address').val())+'","company":"'+encodeURIComponent($('#company').val())+'","memo":"'+encodeURIComponent($('#memo').val())+'","group":"'+$('#group').val()+'","id":"'+$('#id').val()+'"}';
		}else{
			var postdata = 'data={"realname":"'+encodeURIComponent($('#realname').val())+'","cell":"'+encodeURIComponent($('#cell').val())+'","tel":"'+encodeURIComponent($('#tel').val())+'","nickname":"'+encodeURIComponent($('#nickname').val())+'","birthday":"'+encodeURIComponent($('#birthday').val())+'","address":"'+encodeURIComponent($('#address').val())+'","company":"'+encodeURIComponent($('#company').val())+'","memo":"'+encodeURIComponent($('#memo').val())+'","group":"'+$('#group').val()+'","id":"'+$('#id').val()+'"}';
		}
		$.ajax({
			type: "post",
			url : "saveuser",
			dataType:'html',
			data:postdata,
			success: function(data,textStatus){
				if(data==1){
					window.location.href = "../contact/list";
				}else if(data==2){
					Dialog.alert(lang.contact.C0002);
				}else{
					Dialog.alert(lang.contact.E1000);
				}
			}
		});
	}
}

function isemail(str){
	var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(str);
}

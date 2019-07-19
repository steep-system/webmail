var divTop,divLeft,divWidth,divHeight,docHeight,docWidth,objTimer,i = 0;//关于位置的相关变量
function viewMsg()
{
   try
   {
     divTop = parseInt(document.getElementById("divMsg").style.top,10)     //div的x坐标
     divLeft = parseInt(document.getElementById("divMsg").style.left,10)   //div的y坐标
     divHeight = parseInt(document.getElementById("divMsg").offsetHeight,10)//div的高度
     divWidth = parseInt(document.getElementById("divMsg").offsetWidth,10)   //div的宽度
     docWidth = document.body.clientWidth;                                  //窗体宽度
     docHeight = document.body.clientHeight;                                //窗体高度
     document.getElementById("divMsg").style.top = parseInt(document.body.scrollTop,10) + docHeight + 10 + "px";//设置div的Y坐标
     document.getElementById("divMsg").style.left = parseInt(document.body.scrollLeft,10) + docWidth - divWidth + "px"//设置div的X坐标
     document.getElementById("divMsg").style.visibility="visible"   //设置div显示
     objTimer = window.setInterval("moveDiv()",10)                  //设置定时器
   }
   catch(e){}
}
function resizeDiv()
{
   i+=1
   if (i>500) {
      closeDiv()
      i = 0
   }
   try
   {
     divHeight = parseInt(document.getElementById("divMsg").offsetHeight,10)    //设置div高度
     divWidth = parseInt(document.getElementById("divMsg").offsetWidth,10)      //设置div宽度
     docWidth = document.body.clientWidth;                                      //获取窗体宽度
     docHeight = document.body.clientHeight;                                    //设置窗体高度
     document.getElementById("divMsg").style.top = docHeight - divHeight + parseInt(document.body.scrollTop,10) + "px";//设置div的y坐标
     document.getElementById("divMsg").style.left = docWidth - divWidth + parseInt(document.body.scrollLeft,10) + "px";//设置div的x坐标
   }
   catch(e){}
}
function moveDiv()
{
   try
   {
     if (parseInt(document.getElementById("divMsg").style.top,10) <= (docHeight - divHeight + parseInt(document.body.scrollTop,10)))
     {
       window.clearInterval(objTimer)
       objTimer = window.setInterval("resizeDiv()",1) //调整div的位置和大小
     }
     divTop = parseInt(document.getElementById("divMsg").style.top,10)//获取y坐标
     document.getElementById("divMsg").style.top = (divTop - 2)+"px";//调整div的Y坐标
   }
   catch(e){}
}
function closeDiv()
{
   document.getElementById('divMsg').style.visibility='hidden';//将短信息提示层隐藏
   if(objTimer) window.clearInterval(objTimer);                 //清除定时器
}
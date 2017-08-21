/*!
 * js 初始化
 */
var INFO={};
var CURR_DIALOG=null;
INFO.bodyWidth=950;
INFO.ajaxReturn=false;
var AJAX_OBJ;
$.ajaxSetup({
	
  beforeSend:function(e){
	  if($.type(INFO.ajaxObj)=="object")
	  {
	   		pos=INFO.ajaxObj.offset();
	   		$("#loading").css({"position":"absulote","top":pos.top+"px","left":pos.left+"px","height":INFO.ajaxObj.height()+"px"}).show();
			INFO.ajaxObj.attr("disabled",true);
	  }
	  
   },
  complete:function(){
	  
	   if($.type(INFO.ajaxObj)=="object")
	   {
		  	//alert('asdfas');
	   		INFO.ajaxObj.attr("disabled",false);
	  		$("#loading").hide();
	   }
	   if($.type(CURR_DIALOG)=="object")
	   {
		    //alert('show!');
	  	 	CURR_DIALOG.visible();
	   }
  },
  //contentType:"application/x-www-form-urlencoded; charset=UTF-8"
  //global: true,
 // cache: false,
 // type: "POST"
 //async:true
});
//$.dialog.close=function(){alert('sd');}
$(function(){
	
	//初始化高宽
	$("#frame_left,#frame_right").height($(window).height()-$("#header").height());
	$("#frame_right").width($(window).width()-192);
	
	//切换菜单
	$("#frame_left ul li.title").click(function(){
		
		$(this).closest("ul").find(":not(.title)").toggle();
	
	});
	
	$("#menu_"+mod_key).closest("ul").find(".title").click();
	
	/*初始化数据*/
	INFO.bodyHeight=$("window").height();
	INFO.windowWidth=$(window).width();
	INFO.windowHeight=$(window).height();
	INFO.documentWidth=$(document).width();
	INFO.documentHeight=$(document).height();
	
	//装入loading
		$("#loading").ajaxStart(function(){
			 pos=INFO.ajaxObj.offset();
			 $(this).css({"position":"absulote","top":pos.top+"px","left":pos.left+"px","height":INFO.ajaxObj.height()+"px"}).show();
		 });
			
		$("#loading").ajaxStop(function(){
			 $(this).hide();
		 });
	
	$(window).on('unload',function(){
		return false;
	});
	
	//如果是搜索框，回车即搜索
	  $("#sKeyword").keyup(function(event){
		if(event.keyCode ==13){
		  url=getNewUrl("sKeyword");
		  url=url+(url.indexOf("?")?"&":"?")+"sKeyword="+$(this).val();
		  location.replace(url);
		}
	  });
	
	//搜索提交
	$("form").submit(function(){
		$(this).find("input[type=text],areatext").each(function(){ if($(this).val()==$(this).attr("default")) $(this).val(''); });
		$(this).find("input[type=text]").each(function(){
			v=$(this).val();
			//alert(v);
			v_a=v.split("-");
			if(v_a.length==3)
			{
				if(v_a[1].toString().length==1)
				{
					v_a[1]="0"+v_a[1].toString();
				}
				if(v_a[2].toString().length==1)
				{
					v_a[2]="0"+v_a[2].toString();
				}
				$(this).val(v_a[0].toString()+"-"+v_a[1].toString()+"-"+v_a[2].toString());
			}
		});
	});
	
	$(".ajaxSubmit").ajaxSubmit();
	$(".ajaxDialog").ajaxDialog();
	$(".ajaxDialogUpload").ajaxDialogUpload();
	$(".ajaxDel").ajaxDel();
	$(".select").ajaxWindow();
	$(".ajaxGet").ajaxGet();
	$(".ajaxPost").ajaxPost();
	$(".ajaxLink").ajaxLink();
});
	
/*检测加为朋友按钮*/
function checkFriend()
{
	$(".joinFriend").each(function(){
		mid=$(this).attr("mid");
		obj=$(this);
		//alert("/member-friends-check.html?mid="+mid);
		$.get("/member-friends-check.html?mid="+mid,function(rs){
			//alert(rs);
				if(rs>="1")
				{
					//alert('asdf');
					obj.hide();
				}
				else
					obj.show();
			},"text")
	});
}

/*检索消息*/
function checkNotice()
{
	time=$("#notice_time").size()>0?$("#notice_time").val():0;
	//INFO.ajaxObj=null;
	$.ajax({url:"/ajax-getMsg.html?time="+time,type:"get",dataType:"html",beforeSend:null,complete:null,success:function(m){ if(m) msg_notice_show(m); } });
}

//显示消息提示框
function msg_notice_show(m)
{
	pos=$(".headRight").position();
	if(m)
	{
		$('#msg_notice').html(m);
	}
	$('#msg_notice').css({"top":pos.top+"px","left":(pos.left-170)+"px"}).show();
	$("#msg_notice").hover(function(){ $(this).css("height","auto"); },function(){ $(this).css("height","20px"); });
}

/*为在线图标做设置*/
function onlineSet()
{
	$(".member_online").each(function(){
		obj=$(this);
		//alert("/ajax-onlineCheck-"+$(this).attr("mid")+".html");
		$.ajax({type:"GET",dataType:"json",url:"/ajax-onlineCheck-"+$(this).attr("mid")+".html",beforeSend:null,complete:null,success:function(rs)
				{
					if(rs.isOk)
					{
						obj.attr("src","/tpl/images/member_online.png").attr("title","用户在线，点击与其在线聊聊!").css("cursor","pointer");
						//alert(obj.attr("name"));
					}
					else
					{
						obj.attr("src","/tpl/images/member_unonline.png").attr("title","用户不在线，点击留言！").css("cursor","pointer").show();
						//alert('asdf');
					}
					obj.click(function(){
							ardialogPop('/member-talk-'+obj.attr("mid")+'.html?name='+obj.attr("name"),'在线聊天-'+obj.attr("name"));
					});
				}
		  });
	});
}

//将输入框转为选择框
$.fn.textToSelect = function(paras){
	
		$(this).focus(function(event){
			//alert('dsfa');
			$(this).blur();
			//return;
		});
		
		//单击下拉选择
		fs=$.type(paras)=="object"?paras:{};
		$(this).click(function(){
			v=$(this).attr("vals");
			INFO.ajaxObj=$(this);
			obj=$(this);
			isNext=$(this).attr("isNext");
			if(typeof(v)=="undefined")
			{
				href=$(this).attr("link");
				if(typeof(href)!="undefined")
				{
					$.get(href,function(rs){
						v=rs.vs;
						info=v.split(";");
						c=$(this).attr("pop_class");
						c=typeof(c)=="undefined"?"pop_class":c;
						nav_out=[];
						var memo="";
						if(rs.navs[0]!="undefined")
						{
							nav_string="<div id='pop_navs'><span sid=''>所有</span>";
							for(var ni=0;ni<rs.navs.length;ni++)
							{
								nav_out.push("<span sid='"+rs.navs[ni].id+"'>"+rs.navs[ni].name+"</span>");
							}
							nav_string+=" > "+nav_out.join(" > ")+"</div>";
						}
						memo="<div class='"+c+"'>"+nav_string;
						if(info!="")
							$.each(info,function(j,i){	t=i.split("|"); memo+="<li isEd='"+t[2]+"' id='"+t[0]+"'>"+t[1]+"</li>"; });
						memo+="</div>";

						popWindow(memo,obj);
						$("#pop_navs span").click(function(){
							if($(this).attr("sid")!="")
								obj.attr("link",href+"&pid="+$(this).attr("sid"));
							else
							{
								obj.attr("link",obj.attr("link")+"&pid=0");
							}
							obj.click();
						});
						$("#popDiv li").click(function(){
							isEd=$(this).attr("isEd");
							id=$(this).attr("id");
							if((isNext=="1")&&(isEd!=1))	//如果有下级，且设为可以选下级，则选下级
							{
								if(href.indexOf("pid")!=-1)
								{
									href_a=href.split("&");
									for(var i=0;i<href_a.length;i++)
									{
										tcs=href_a[i].split("=");
										if(tcs[0]=="pid")
											href_a[i]="pid="+id;
									}
									href=href_a.join("&");
								}
								//alert(href);
								obj.attr("link",href+"&pid="+id);
								obj.click();
								return;
							}
							obj.val($(this).text());
							validate(obj);
							//alert(obj.attr("name")+"_val");
							//alert(obj.attr("name")+"_val").val());
							if(typeof($("#"+obj.attr("name")+"_val").val())=="undefined")
							{
								//alert('adf');
								obj.parent().append("<input type='hidden' value='"+$(this).attr("id").toString()+"' name='"+obj.attr("name")+"_val'  id='"+obj.attr("name")+"_val' />");
							}
							else
								$("#"+obj.attr("name")+"_val").val($(this).attr("id").toString());
							if($.type(fs.liClick)=="function")
							{
								fs.liClick();
							}
							$(".popClose").click();
						});
						//if(info.length==1)
							//$("#popDiv li").eq(0).click();
					},"json");
				}
				else
					return;
			}
			else
			{
				//alert(v);
				info=v.split(";");
				c=$(this).attr("pop_class");
				c=typeof(c)=="undefined"?"pop_class":c;
				memo="<div class='"+c+"'>";
				$.each(info,function(j,i){	memo+="<li>"+i+"</li>"; });
				memo+="</div>";
				//alert(memo);
				popWindow(memo,$(this));
				obj=$(this);
				$("#popDiv li").click(function(){
					obj.val($(this).text());
					$("#popClose").click();
				});
			}
			//$("#popClose").hide();
		});
};

//将输入框转为多重选择
$.fn.textToCheckbox = function(paras){
		$(this).focus(function(event){
			$(this).blur();
		});
		
		//单击下拉选择
		fs=$.type(paras)=="object"?paras:{};
		$(this).click(function(){
			INFO.ajaxObj=$(this);
			v=$(this).attr("vals");
			obj=$(this);
			var isNext=obj.attr("isNext");
			//alert(isNext);
			if(typeof(v)=="undefined")
			{
				href=$(this).attr("link");
				if(typeof(href)!="undefined")
				{
					$.get(href,function(rs){
						if(typeof($("#"+obj.attr("name")+"_val").val())!="undefined")
						{
							is_ed=$("#"+obj.attr("name")+"_val").val();
						}
						v=rs.vs;
						info=v.split(";");
						c=$(this).attr("pop_class");
						c=typeof(c)=="undefined"?"pop_class":c;
						nav_out=[];
						if(rs.navs[0]!="undefined")
						{
							nav_string="<div id='pop_navs'><span sid=''>所有</span>";
							for(var ni=0;ni<rs.navs.length;ni++)
							{
								nav_out.push("<span sid='"+rs.navs[ni].id+"'>"+rs.navs[ni].name+"</span>");
							}
							nav_string+=" > "+nav_out.join(" > ")+"</div>";
						}
						memo="<div class='"+c+"'>"+nav_string;
						//memo="<div class='"+c+"'>";
						$.each(info,function(j,i){	t=i.split("|"); is_checked=(obj.val().indexOf(t[1])!=-1)?"checked=true":""; ed="" ;if(is_ed.indexOf(t[0])!=-1) ed=" checked='true' "; memo+="<li title='"+t[1]+"' is_ed='"+t[2]+"' id='"+t[0]+"'><input type='checkbox' "+is_checked+" value='"+t[1]+"' title='"+t[1]+"' id='"+t[0]+"' name='input_checkbox_"+j+"' "+ed.toString()+" id='input_checkbox_"+j+"' class='input_checkbox' /><span>"+t[1]+"</span></li>"; });
						memo+="</div>";
						//alert(memo);
						popWindow(memo,obj);
						
						$("#pop_navs span").click(function(){
							if($(this).attr("sid")!="")
								obj.attr("link",href+"&pid="+$(this).attr("sid"));
							else
							{
								//alert(href);
								obj.attr("link",obj.attr("link")+"&pid=0");
							}
							obj.click();
						});
						//obj=$(this);
						$("#popDiv li span").click(function(){
							isEd=$(this).parents("li").eq(0).attr("is_ed");
							//alert(isEd);
							id=$(this).parents("li").eq(0).attr("id");
							//alert(id);
							if((isNext=="1")&&(isEd!=1))	//如果有下级，且设为可以选下级，则选下级
							{
								if(href.indexOf("pid")!=-1)
								{
									href_a=href.split("&");
									for(var i=0;i<href_a.length;i++)
									{
										tcs=href_a[i].split("=");
										if(tcs[0]=="pid")
											href_a[i]="pid="+id;
									}
									href=href_a.join("&");
								}
								//alert(href);
								obj.attr("link",href+"&pid="+id);
								//alert(obj.attr("link"));
								obj.click();
								return;
							}
							//obj.val($(this).text());
							validate(obj);
									//alert(obj.attr("name")+"_val");
									//alert(obj.attr("name")+"_val").val());
							/*if(typeof($("#"+obj.attr("name")+"_val").val())=="undefined")
							{
										//alert('adf');
								obj.parent().append("<input type='hidden' value='"+$(this).attr("id").toString()+"' name='"+obj.attr("name")+"_val'  id='"+obj.attr("name")+"_val' />");
							}
							else
								$("#"+obj.attr("name")+"_val").val($(this).attr("id").toString());
							if($.type(fs.liClick)=="function")
							{
								fs.liClick();
							}*/
							//$(".popClose").click();
							});
						//},"json");
					//}
						//obj=$(this);
						$(".input_checkbox").click(function(){
							is_v=$(this).attr("title");
							id=$(this).attr("id");
							k_v=$("#"+obj.attr("name")+"_val").val();
							//alert(k_v);
							if(k_v)
								k_v_a=k_v.split(",");
							else
								k_v_a=[];
							o_v=obj.val();
							if(o_v!="")
								o_v_a=o_v.split("+");
							else
								o_v_a=[];
							if($(this).prop("checked"))
							{
								o_v_a.push(is_v);
								k_v_a.push(id);
							}
							else
							{
								o_v_a.pop(is_v);
								k_v_a.pop(id);
							}
							//alert(o_v_a.length);
							obj.val(o_v_a.join("+"));
							if(typeof($("#"+obj.attr("name")+"_val").val())=="undefined")
							{
								obj.parent().append("<input type='hidden' value='' name='"+obj.attr("name")+"_val'  id='"+obj.attr("name")+"_val' />");
							}
							//alert(k_v_a.join(","));
							$("#"+obj.attr("name")+"_val").val(k_v_a.join(","));
							//alert($("#"+obj.attr("name")+"_val").val());
							if($.type(fs.liClick)=="function")
							{
								fs.liClick();
							}
						});
					},"json");
				}
				else
					return;
			}
			//$("#popClose").hide();
		});
};

//删除
$.fn.ajaxPage = function(fs){
		//alert($(this).click());
		$(this).click(function(event){
			fs=$.type(fs)=="object"?fs:{};
			//alert(fs.memo_obj);
			event.preventDefault();
			event.stopPropagation();
			INFO.ajaxObj=$("#"+fs.memo_obj);
			href=$(this).attr("href");
			alert(href);
			$("#"+fs.memo_obj).load(href);
		});
};

//删除
$.fn.ajaxDel = function(fs){
		//alert($(this).click());
		fs=$.type(fs)=="object"?fs:{};
		$(this).click(function(event){
			INFO.ajaxObj=$(this);
			href=$(this).attr("href");
			event.preventDefault();
			event.stopPropagation();
			$.confirm("确认要做作废/删除操作？",function(){
				ajaxPost(href,"",fs.isOk);
			});
		});
};

//删除
$.fn.ajaxSetVal = function(fs){
		href=$(this).attr("link").split("?");
		href=href[0];
		href=href+"?id="+$(this).val();
		if($(this).val()=="")
			return;
		//alert(href);
		n=$(this).attr("name");
		o=$(this);
		$.get(href,function(rs){
			//alert(rs.vs);
			vs=rs.vs.split("|");
			$("#"+n+"_val").val(vs[0]);
			o.val(vs[1]);
		},"json");	
};

//删除
$.fn.ajaxLoad = function(fs){
		INFO.ajaxObj=$(this);
		href=$(this).attr("href");
		$(this).load(href);
};

//表单提交
$.fn.ajaxSubmit = function(fs){
	fs=$.type(fs)=="object"?fs:{};
	
	$(this).click(function(event){
		
		//热行点击前事件
		if($.type(fs.clickBefor)=="function")
		{
			if(!fs.clickBefor())
				return false;
		}
		
		clickObj=$(this);
		forms=$(this).parents("form");
		form=forms.eq(forms.size()-1);
		
		action=form.attr("action")?form.attr("action"):location.href;
		if(!validateForm(form))
			return false;
		//alert(action);
		/*如果有两个密码框，做密码一致性验证*/
		password=$("input[type=password]");
		//alert(password.size());
		if(password.size()==2)
		{
			if(password.eq(0).val()!=password.eq(1).val())
			{
				alert("两次密码输入不一致！");
				password.eq(1).focus();
				return false;
			}
		}
		
		INFO.ajaxObj=$(this);
		/*if(typeof(clickObj)=="object")
		{
			AJAX_OBJ=clickObj;
			AJAX_OBJ.attr("disabled",true);
			pos=AJAX_OBJ.position();
			//alert(pos.top);
			$("#loading").css({"left":(pos.left+AJAX_OBJ.width()+25)+"px","top":pos.top+"px","width":"30px","height":"30px"}).show();
		}*/
		//editor.sync();
		
		ajaxPost(action,form.serialize(),fs.isOk);
	});
};

//弹出对象初始化
$.fn.ajaxDialog = function(fs){
		//alert($(this).click());
		fs=$.type(fs)=="object"?fs:{};
		$(this).click(function(event){
			//alert('asdfasd');
			event.preventDefault();
			event.stopPropagation();
			
			if(fs.only===true)
				if($.type(CURR_DIALOG)=="object")
					return;
					
			//热行点击前事件
			if($.type(fs.clickBefor)=="function")
			{
				if(!fs.clickBefor())
					return false;
			}
		
			href=typeof($(this).attr("href"))!="undefined"?$(this).attr("href"):location.href;
			//$(".d-buttons").hide();
			//alert($(".d-buttons").size());
			//alert(href);
			t=$(this).val()||$(this).text()+"-"+document.title;
			//alert($(this).text());
			INFO.ajaxObj=$(this);
			CURR_DIALOG=null;
			//alert(fs.id);
			fs.id=fs.id!=undefined?fs.id:$(this).attr("id");
			//alert(fs.id);
			CURR_DIALOG=$.dialog({title:t,lock:true,visible:false,id:fs.id,/*fixed:true,*/contentType:'url', content:href,beforeunload:function(){if($.type(fs.closeEvent)=="function") fs.closeEvent(); $("#loading").hide();}});
		});
};


//弹出上传文件对话框
$.fn.ajaxDialogUpload = function(fs){
		//alert($(this).click());
		fs=$.type(fs)=="object"?fs:{};
		//alert('adfasf');
		$(this).click(function(event){
			event.preventDefault();
			event.stopPropagation();
			href=typeof($(this).attr("href"))!="undefined"?$(this).attr("href"):location.href;
			//href+=typeof($(this).attr("imgs_name")!="undefined")?"?name="+$(this).attr("imgs_name"):"";
			//alert(href);
			t=$(this).val()||$(this).text()+"-"+document.title;
			INFO.ajaxObj=$(this);
			//alert(t);
			CURR_DIALOG=$.dialog({title:t,lock:true,visible:false,id:fs.id,/*fixed:true,*/contentType:'url', content:href,beforeunload:function(){if($.type(fs.closeEvent)=="function") fs.closeEvent(); $("#loading").hide();}});
		});
};

//表单提交
$.fn.validateForm = function(){
	//fs.each(i){ 
};

//ajax设置
$.fn.ajaxLink = function(fs){
		//alert($(this).click());
		fs=$.type(fs)=="object"?fs:{};
		$(this).click(function(event){
			INFO.ajaxObj=$(this);
			event.preventDefault();
			event.stopPropagation();
			obj=$(this);
			href=$(this).attr("href");
			//alert(href);
			n=$(this).attr("isNotice");
			if(typeof(n)!="undefined")
			{
				if(!confirm(n))
					return false;
			}
			//alert(href+'asdf');
			$.get(href,function(rs){
				ALERT(rs.msg);
				if($.type(fs.isOk)=="function")
					fs.isOk();
				else
				{
					if($.type(CURR_DIALOG)=="object")
					{
						CURR_DIALOG.close();
					}
					location.reload();
				}
			},"json");
		});
};

//弹出对象初始化
$.fn.ajaxGet = function(fs){
		//alert($(this).click());
		fs=$.type(fs)=="object"?fs:{};
		$(this).click(function(event){
			INFO.ajaxObj=$(this);
			event.preventDefault();
			event.stopPropagation();
			obj=$(this);
			href=$(this).attr("link");
			//alert(href);
			$.get(href,function(rs){
				//alert(rs);
				if($.type(fs.memo_obj)=="object")
					fs.memo_obj.html(rs);
				else
					alert(rs.msg);
				//$("#popOut").html(rs);
			},"html");
		});
};

//弹出对象初始化
$.fn.ajaxPost = function(fs){
		//alert($(this).click());
		fs=$.type(fs)=="object"?fs:{};
		$(this).click(function(event){
			INFO.ajaxObj=$(this);
			event.preventDefault();
			event.stopPropagation();
			obj=$(this);
			href=$(this).attr("href");
			//alert(href);
			ajaxPost(href,{});
		});
};

//弹出对象初始化
$.fn.ajaxWindow = function(fs){
		//alert($(this).click());
		fs=$.type(fs)=="object"?fs:{};
		$(this).click(function(event){
			INFO.ajaxObj=$(this);
			event.preventDefault();
			event.stopPropagation();
			obj=$(this);
			href=typeof($(this).attr("href"))!="undefined"?$(this).attr("href"):location.href;
			//alert(href);
			$.get(href,function(rs){
				pos=obj.position();
				$("#popWindow").html(rs).css({"left":(pos.left+obj.width())+"px","top":(pos.top+obj.height())+"px"}).show();
			});
		});
};

//ajax做Post提交
function ajaxPost(url,datas,r)
{
	//缺省的返回后操作函数
	var f=function(rs)
	{
		$("#loading").hide();
		if($("#outMsg").size()>0)
		{
			$("#outMsg").html(rs.msg);
		}
		else
			ALERT(rs.msg);
		if(rs.isOk)
		{
			if(rs.url!="")
			{
				if(rs.url_type=="pop")
				{
					CURR_DIALOG.close();
					ardialogPop(rs.url);
				}
				else
					location.replace(rs.url);
			}
			else
				location.reload();
		}
		else
		{
			alert(rs.msg);
			if(rs.url!="")
				location.replace(rs.url);
		}
	}
	if($.type(r)=="function")
		f=r;
	//alert(f);
	$.post(url,datas,f,"json");
}

//用正则验证表单
function validateForm(obj)
{
	var isSubmit = true;
	obj.find("[reg]").each(function(){
		
		/*$(this).focus(function(){
			$("#inputNotice_"+name).remove();
		});*/
		
		/*$(this).blur(function(){
			validate($(this));
		});*/
		//$(this).focus();
		isSubmit = validate($(this));
		return isSubmit;
	});
	//alert(isSubmit);
	return isSubmit;
}

function validate(obj){
	var reg = new RegExp(obj.attr("reg"));
	var objValue = obj.val();
	if(!reg.test(objValue)){
		validate_error(obj);
		return false;
	}
	/*else
	{
		obj.nextAll("span[class=help1]").html("<img src='/tpl/images/yes.gif'>").attr("class","help3");
	}*/
	
	return true;
}

var alert_time;
//弹出提示消息
function ALERT(msg)
{
	if($("#ALERT_DIV").size()==0)
	{
		$("body").append("<div id='ALERT_DIV'><div class='weui_mask_transparent'></div><div  class='weui_toast'><i class='weui_icon_toast'></i><p class='weui_toast_content'>"+msg+"</p></div></div>");
	}
	else
		$(".weui_toast_content").html(msg);
		
	$("#ALERT_DIV").show();
	$("#ALERT_DIV").click(function(){
		
		$(this).hide();
	
	});
	
	setTimeout(function(){ $("#ALERT_DIV").hide(); },2000);
}
	
//表单验证定位
function validate_error(obj)
{
	obj.css("border","1px solid #f00");
	if(obj.closest("tr").size())
	{
		ALERT(obj.attr("tip"));
		obj.attr("old_placeholder",obj.attr("placeholder"));
		obj.attr("placeholder",obj.attr("tip"));
		obj.closest("tr").find("td").eq(0).css("color","#f30");
	}
	obj.focus();
	obj.blur(function(){
		
		obj.attr("placeholder",obj.attr("old_placeholder"));
		obj.attr("placeholder",obj.attr("tip"));
		$(this).css("border","0px");
		if(obj.closest("tr").size())
			obj.closest("tr").find("td").eq(0).css("color","#999");
	
	});
	/*name=obj.attr("name");
	if(typeof($("#inputNotice_"+name)).html()!="undefined")
		return false;
	pos=obj.offset();
	//tip=obj.attr("placeholder")?obj.attr("placeholder"):"必填！";
	if(obj.nextAll("span").size()==0)
	{
			obj.after("<br /><span class='help' id='obj_notice_"+obj.attr("name")+"'>"+obj.attr("tip")+"</span>");
	}
	$("body").append("<div id='inputNotice_"+name+"' class='inputNotice'>"+tip+"</div>");
	//alert(obj.nextAll("span").size());
	obj.nextAll("span").html(tip);
	obj.nextAll("span").attr("class","help2");
	$(this).select();
	$("#inputNotice_"+name).css({"left":pos.left+"px","top":(pos.top-$("#inputNotice_"+name).height()-10)+"px"}).hide();*/
}

//重定位footer
function initFooter()
{
	f=$(".footer");
	//alert(INFO.documentWidth-INFO.bodyWidth);
	//f.css({"position":"absolute","top":(INFO.documentHeight-f.height())+"px","left":(INFO.documentWidth-INFO.bodyWidth)/2+"px"});
}

//跳转URL
function goUrl(url)
{
	location.replace(url);
}

//删除确认
function del(url){
	$.confirm('是否确定删除此项？', function () {
   		location.replace(url);
	});
}

//加载load
function ajaxLoad()
{
	ls=$(".ajaxLoad");
	ls.each(function(){ href=$(this).attr("href"); alert(href); $(this).load(href); });
}

//是否为真 
function isReal(v)
{
	return (typeof(v)!="undefined")&&($.trim(v)!="")&&($.trim(v)!=0);
}

//使用控件，弹出窗口
function ardialogPop(href,title,fs)
{
		fs=$.type(fs)=="object"?fs:{};
		//INFO.ajaxObj=$(this);
		t=title?title:"tianview.com";
		CURR_DIALOG=$.dialog({title:t,lock:true,visible:false,id:fs.id,/*fixed:true,*/contentType:'url', content:href,okValue:"确认",ok:function(){
				fm=$("#dialogForm");
				//alert(validateForm(fm));
				if(!validateForm(fm))
					return false;
				memo=$("#dialogForm").serialize();
				if(typeof(editor)!="undefined")
					memo+="&"+editor.ui.textarea+"="+encodeURIComponent(editor.getContent());
				//alert(memo);
				if(fm.size()>0)
					ajaxPost(href,memo,fs.ok);
				//return false;
		},initialize:function(){ $(".d-buttons").hide();},beforeunload:function(){INFO.ajaxObj=null;CURR_DIALOG=null;$(".inputNotice").hide();$("#popClose").click();location.reload();}});
}

//打开弹出层窗口
function popWindow(memo,o,type)
{
	type=type?type:"html";
	o=o?o:$("body");
	//如果传入对像
	if(o)
	{
		p=o.offset();
		l=p.left;
		t=p.top+o.height();
		w=o.width();
		h="auto";
	}
	
	switch(type)
	{
		case "html":
			$("#popOut").html(memo);
			$("#popDiv").css({"left":l+"px","top":(t+3)+"px","height":h,"width":(w+2)+"px"}).show();
			//alert(t);
			$("#popClose").css({"left":((l+$("#popDiv").width())-3).toString()+"px","top":(t-2)+"px"}).show();
			break;
	}
}

//复制到剪贴板
function copyToClipBoard(s) {
            //alert(s);
            if (window.clipboardData) {
                window.clipboardData.setData("Text", s);
                alert("已经复制到剪切板！"+ "\n" + s);
            } else if (navigator.userAgent.indexOf("Opera") != -1) {
                window.location = s;
            } else if (window.netscape) {
                try {
                    netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
                } catch (e) {
                    alert("被浏览器拒绝！\n请在浏览器地址栏输入'about:config'并回车\n然后将'signed.applets.codebase_principal_support'设置为'true'");
                }
                var clip = Components.classes['@mozilla.org/widget/clipboard;1'].createInstance(Components.interfaces.nsIClipboard);
                if (!clip)
                    return;
                var trans = Components.classes['@mozilla.org/widget/transferable;1'].createInstance(Components.interfaces.nsITransferable);
                if (!trans)
                    return;
                trans.addDataFlavor('text/unicode');
                var str = new Object();
                var len = new Object();
                var str = Components.classes["@mozilla.org/supports-string;1"].createInstance(Components.interfaces.nsISupportsString);
                var copytext = s;
                str.data = copytext;
                trans.setTransferData("text/unicode", str, copytext.length * 2);
                var clipid = Components.interfaces.nsIClipboard;
                if (!clip)
                    return false;
                clip.setData(trans, null, clipid.kGlobalClipboard);
                alert("已经复制到剪切板！" + "\n" + s)
            }
}

//去掉url中的固有参数
function getNewUrl(key,url)
{
	url=url?url:location.href;
	if(url.indexOf("?"))
	{
		f=url.split("?");
		///alert(f[1]);
		f1=f[1].split("&");
		u1="";
		f3=[];
		$.each(f1,function(i){ f2=f1[i].split("="); if(f2[0]!=key){ f3.push(f1[i]); } });
		url=f[0]+"?"+f3.join("&");
		//alert(url);
	}
	return url;
}
		
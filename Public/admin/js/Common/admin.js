layui.use(['element'], function(){
	$ = layui.jquery;
  	element = layui.element();

  //导航的hover效果、二级菜单等功能，需要依赖element模块
  // 侧边栏点击隐藏兄弟元素
	$('.layui-nav-item').click(function(event) {
		$(this).siblings().removeClass('layui-nav-itemed');
	});

	$('.layui-tab-title li').eq(0).find('i').remove();

	if($(window).width()<750){
		trun = 0;
	}else{
		trun = 1;
	}
	$('.diy-slide_left').click(function(event) {
		if(trun){
			$('.diy-side').animate({left: '-130px'},200).siblings('.diy-main').animate({left: '0px'},200);
			trun=0;
		}else{
			$('.diy-side').animate({left: '0px'},200).siblings('.diy-main').animate({left: '130px'},200);
			trun=1;
		}

	});



  	//监听导航点击
  	element.on('nav(side)', function(elem){
    	title = elem.find('cite').text();
    	url = elem.find('a').attr('_href');
    	 //alert(url);

    	for (var i = 0; i <$('.diy-iframe').length; i++) {
    		if($('.diy-iframe').eq(i).attr('src')==url){
    			element.tabChange('diy-tab', i);
    			return;
    		}
    	};

    	res = element.tabAdd('diy-tab', {
	        title: title//用于演示
	        ,content: '<iframe frameborder="0" src="'+url+'" class="diy-iframe"></iframe>'
		    });


		element.tabChange('diy-tab', $('.layui-tab-title li').length-1);

    	$('.layui-tab-title li').eq(0).find('i').remove();
  });
});


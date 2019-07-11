layui.use(['element', 'layer'], function(){
    var element = layui.element;
    var layer = layui.layer;
    $ = layui.jquery;
    resetMenuHeight();
    //监听折叠
    element.on('collapse(test)', function(data){
        layer.msg('展开状态：'+ data.show);
    });
});
// 重新设置菜单容器高度
function resetMenuHeight(){
    var height = document.documentElement.clientHeight - 57;
    $('#menu').height(height);
}
// 重新设置主操作页面高度
function resetMainHeight(obj){
    var height = parent.document.documentElement.clientHeight - 53;
    $(obj).parent('div').height(height);
}
// 菜单点击
function menuFire(obj){
    // 获取url
    var src = $(obj).attr('src');
    // 设置iframe的src
    $('iframe').attr('src',src);
}
// 退出
function logout(){
    layer.confirm('确定要退出吗？', {
        btn: ['确定','取消']
    }, function(){
        $.get('admin.php?m=Admin&c=Auth&a=logout',function(res){
            if(res.code>0){
                layer.msg(res.msg,{'icon':2});
            }else{
                layer.msg(res.msg,{'icon':1});
                setTimeout(function()
                {
                    window.location.href = 'admin.php?m=Admin&c=Auth&a=login';
                    window.history.go(0);
                },1000);
            }
        },'json');
    });
}

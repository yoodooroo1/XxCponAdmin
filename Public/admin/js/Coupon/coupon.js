layui.use(['layer','form'],function () {
    layer = layui.layer;
    $ =layui.jquery;
    form = layui.form;

})



//添加优惠卷转换
function add() {
    layer.open({
        type: 2,
        // title: id>0?'编辑管理员':'添加管理员',
        shade: 0.3,
        title:'新增优惠卷信息',
        area: ['480px', '430px'],
        content: '/admin.php?m=Admin&c=Coupon&a=getCouponList'
    });
}

//保存
function save(){

    var online = $.trim($('select[name="online"]').val());

    if(online==''||online=='请选择线上优惠卷'){
        layer.alert('请选择线上优惠卷',{icon:2});
        return;
    }

    layui.jquery.post('/admin/coupon/add',$('form').serialize(),function(res){
        if(res.code>0){
            layer.alert(res.msg,{icon:2});
        }else{
            layer.msg(res.msg);
            setTimeout(function(){parent.window.location.reload();},1000);
        }
    },'json');

}

//删除
function del(id){
    layer.confirm('确定要删除关联吗?',
        {   title:'删除',
            area:['40%','220px'],
            btn:['确定','取消']},
        function(){
            $.post('/admin/coupon/delete',{'id':id},function(res){
                if(res.code>0){
                    layer.alert(res.msg,{icon:2});
                }else{
                    layer.msg(res.msg);
                    setTimeout(function(){parent.window.location.reload();},1000);
                }
            },'json');
        });
}



// 添加关联
function match(id){
    layer.confirm('您确定要关联吗?',
        {   title:'新增关联',
            area:['40%','220px'],
            btn:['确定','取消']},
        function(){
            $.post('/admin/coupon/match',{'id':id},function(res){
                if(res.code>0){
                    layer.alert(res.msg,{icon:2});
                }else{
                    layer.msg(res.msg);
                    setTimeout(function(){parent.window.location.reload();},1000);
                }
            },'json');
        });
}

//解除关联
function depart(id) {
    layer.confirm('您确定要解除吗?',
        {   title:'解除关联',
            area:['40%','220px'],
            btn:['确定','取消']},
        function(){
            $.post('/admin/coupon/depart',{'id':id},function(res){
                if(res.code>0){
                    layer.alert(res.msg,{icon:2});
                }else{
                    layer.msg(res.msg);
                    setTimeout(function(){parent.window.location.reload();},1000);
                }
            },'json');
        });
}


//修改信息
// function edit() {
//     layer.open({
//         type: 2,
//         // title: id>0?'编辑管理员':'添加管理员',
//         shade: 0.3,
//         title:'修改优惠卷信息',
//         area: ['480px', '230px'],
//         content: '/admin.php?m=Admin&c=Coupon&a=edit'
//     });
// }
//添加或修改信息
// function save() {
//     layer.confirm('确认新增优惠卷互换',
//         {   title:'',
//             area:['40%','150px'],
//             btn:['确定','取消']},
//         function(){
//             $.post('/admin/coupon/del',{'gid':gid},function(res){
//                 if(res.code>0){
//                     layer.alert(res.msg,{icon:2});
//                 }else{
//                     layer.msg(res.msg);
//                     setTimeout(function(){parent.window.location.reload();},1000);
//                 }
//             },'json');
//         });
// }

// function cbys1() {
//     s2.length = 0;
//     s2.options[0] = new Option(s1.value, s1.value);
// }

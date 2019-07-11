var vm = new Vue({
  el: '#app',
  data: function () {
    return {
      temp: {
        loading: false,
        isCount: false,
        countNum: 120, //当前剩余秒数
        timer: null, //timer变量，控制时间
        showTelCode: false,//是否显示手机授权码输入
        verifyImg: 'admin.php?m=Admin&c=Auth&a=verify'//图形验证码路径
      },
      query: {
        username: '',
        password: '',
        verify: '',
        tel_code: '',//授权手机验证码
        remember: 1//是否记住密码【1：是】
      },
      rules: {
        username: [
          {required: true, message: '请输入账号', trigger: 'blur'},
        ],
        password: [
          {required: true, message: '请输入密码', trigger: 'blur'},
        ],
        tel_code:[
          {required: true, message: '请输入验证码', trigger: 'blur'},
        ],
      },
    }
  },
  methods: {
    handleLogin: function () {
      var self = this;
      self.$refs.loginFrom.validate((valid) => {
        if (valid) {
          self.temp.loading = true;
          $.ajax({
            url:"admin.php?m=Admin&c=Auth&a=login",
            data:self.query,
            type: 'post',
            dataType:'JSON',
            error:function(XMLHttpRequest, textStatus, errorThrown){
              self.$message.error('系统出错');
              self.temp.loading = false;
            },
            success:function(data){
              if(+data.code === 200){
                self.$message({message: '登录成功咯！  正在为您跳转...', type: 'success'})
                window.location.href = "admin.php?m=Admin&c=index&a=index";
              }else{
                console.log(data);
                if(+data.code === 1){
                  // self.temp.showTelCode = true;
                  self.temp.loading = false;
                }else{
                  self.$message.error(data.msg);
                  self.temp.loading = false;
                  self.changeImgCode();
                }
              }
            }
          });
        }
      });
    },

    changeImgCode: function () {
      var self = this;
      self.$nextTick(function () {
        if (self.temp.verifyImg.indexOf('?') > 0) {
          self.temp.verifyImg =  self.temp.verifyImg + '&random=' + Math.random();
        } else {
          self.temp.verifyImg = self.temp.verifyImg.replace(/\?.*$/, '') + '?' + Math.random();
        }
      })
    }
  }
})

//enter键触发提交验证事件
window.document.onkeydown = enter;

function enter(evt) {
  evt = (evt) ? evt : window.event;
  if (evt.keyCode) {
    //控制键键码值 || //数字键盘上的键的键码值
    if (evt.keyCode == 13 || evt.keyCode == 108) {
      vm.handleLogin();
    }
  }
}

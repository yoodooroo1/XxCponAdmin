/**
 * @author   liangxiaoqiong
 * @version  1.0
 * @date 2018-01-02.
 */

var uploadUrl = 'upload.php';   
var publicObj = new Object({
  lastAjax: {},
  lastXhr: {},
  uploadUrl: uploadUrl,
  CONTENT_TYPE_FILE: false,
  showLoad: true,
  /**
   * ajax初始化默认设置
   */  
  ajaxInit: function () {
    var self = this;
    $.ajaxSetup({
      cache: false,
      contentType: "application/x-www-form-urlencoded;charset=utf-8",
      dataType: 'json',
      type: 'POST',
      timeout: 10000,
      beforeSend: function (xhr) {
        // 请求前判断上一个请求和当前是否一致 如果一致并且上一个请求还未响应则销毁上一个请求
        /*
            0 － （未初始化）还没有调用send()方法
            1 － （载入）已调用send()方法，正在发送请求
            2 － （载入完成）send()方法执行完成，已经接收到全部响应内容
            3 － （交互）正在解析响应内容
            4 － （完成）响应内容解析完成，可以在客户端调用了
         */
        if (typeof (self.lastXhr.readyState) === 'number') {
          // get方式去除掉时间戳
          var lastUrl = self.getTrueUrl(self.lastAjax.url);
          var thisUrl = self.getTrueUrl(this.url);
          if (thisUrl === lastUrl &&
            this.data === self.lastAjax.data &&
            self.lastXhr.readyState !== 4) {
            self.lastXhr.abort();
          }
        }
        // 发送前记录上一个请求是当前请求
        self.lastAjax = this;
        self.lastXhr = xhr;

        if (typeof (layer) !== 'undefined' && self.showLoad) {
          layer.load();
        }
      },
      complete: function (xhr, textStatus) {
        if (typeof (layer) !== 'undefined' && self.showLoad) {
          layer.closeAll('loading');
        }
      },
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        if (textStatus !== 'abort'){
          var error = JSON.stringify(XMLHttpRequest);
          var oldConfig = JSON.stringify(this);
          //self.addException(error + '=>' + oldConfig);
        }
        if (textStatus !== 'abort') {
          var msg = typeof langMsg === 'undefined' ? '您的网络不给力...' : '您还未登录,请先登录...';
          publicObj.alert(msg);
        }
      }
    });
  },

  /**
   * 获取真是请求地址 去除签名、时间戳等参数
   * @param str
   * @returns {*}
   */
  getTrueUrl: function (str) {
    var arr = str.split('_');
    str = arr[0];
    var start = str.indexOf('nonceStr');
    var end = str.indexOf('key');
    if (end === -1){
      end = str.indexOf('sign');
    }
    var endIndex = str.indexOf('&', end);
    var url1 = str.slice(0, start);
    var url2 = str.slice(endIndex);
    return url1 + url2;
  },
  alert: function (msg, type) {
    if (typeof layer === 'object'){
      if (type) {
        layer.msg(msg, {icon: type})
      } else {
        layer.msg(msg)
      }
    } else {
      window.alert(msg)
    }
  },
  /**
   * swiper，左右图片轮播swiper 4
   * @param value
   * elName:'.class'或'#id' //样式名
   * isCurrent:是否返回当前分页
   */
  swiperOne: function (elName,swiperData) {
    var swiperDefault = {
      pagination:'.swiper-pagination',
      paginationType :'bullets',
      speed:350,//速度
      autoplay:true,//自动播放
      paginationClickable: true,
      autoplayDisableOnInteraction:false,
      observer:true,
      observeParents:true,
    };
    $.extend(true,swiperDefault,swiperData); //合并两对象数据，相同键值swiperData覆盖swiperDefault
    var swiper_ = new Swiper(elName, swiperDefault);
    var result = swiper_;
    return result;
  },

  /**
   * 显示layer 弹框
   参数解释：
   type==1,div层 ；==2：iframe
   title  标题
   url    请求的url,div el
   id    需要操作的数据id
   area{w:弹出层宽度（缺省调默认值760px）,h:弹出层高度（缺省调默认值80%）}
   * */
  layerShow: function (type, title, content, area) {
    var areaH, areaW;
    if (title == null || title == '') {
      title = false;
    }
    if (typeof (area) === 'undefined') {
      areaW = '700px';
      areaH = '90%';//($(window).height() - 50)+'px';
    } else {
      areaW = area.w_;
      areaH = area.h_;
    }
    if (+type === 2) {
      if (content == null || content == '') {
        content = "404.html";
      }
    } else {
      content = $(content);
    }
    return layer.open({
      type: type,
      area: [areaW, areaH],
      fix: false, //不固定
      // maxmin: true,
      //shade:0.4,
      title: title,
      skin: 'layer-skin',
      // shadeClose: true,
      content: content
    });
  },
  /**
   * 关闭弹出框口 ifream
   * */
  layerFrameClose: function () {
    var index = parent.layer.getFrameIndex(window.name);
    parent.layer.close(index);
  },

  /**
   * 格式化时间
   * @param {} date
   * @param {} format
   */
  formatDate: function (date, format) {
    var paddNum = function (num) {
      num += "";
      return num.replace(/^(\d)$/, "0$1");
    }
    //指定格式字符
    var cfg = {
      yyyy: date.getFullYear() //年 : 4位
      , yy: date.getFullYear().toString().substring(2)//年 : 2位
      , M: date.getMonth() + 1  //月 : 如果1位的时候不补0
      , MM: paddNum(date.getMonth() + 1) //月 : 如果1位的时候补0
      , d: date.getDate()   //日 : 如果1位的时候不补0
      , dd: paddNum(date.getDate())//日 : 如果1位的时候补0
      , hh: paddNum(date.getHours())  //时
      , mm: paddNum(date.getMinutes()) //分
      , ss: paddNum(date.getSeconds()) //秒
    }
    format || (format = "yyyy-MM-dd hh:mm:ss");
    return format.replace(/([a-z])(\1)*/ig, function (m) {
      return cfg[m];
    });
  },
  getString: function (key) {
    var arr = ['男', '女', '微信支付', '银行卡支付'];
    return arr[key]
  },
  getGoddessLevelName : function(level) {
      var name = '';
      if(level == '1'){
          name = '女神';
      }else if(level == '2'){
          name = '活力女神';
      }else if(level == '3'){
          name = '无暇女神';
      }else if(level == '4'){
          name = '高颜女神';
      }
      return name ;
  },
  /**图片预览
   * @param el = '.class||#id'元素，不传值时默认
   * */
  imgPreview: function (el) {
    if(typeof el === 'undefined'){
      el = '.img-preview';
    }
    $(el).viewer({title: false, navbar: false, toolbar: false});
  },
  //上传文件
  upload: function (config, callback) {
    // 上传文件
    var form = new FormData();
    if (typeof config.file === 'object') {
      var file = config.file
    } else {
      var el = $(config.el)[0]
      if (typeof el === 'undefined') {
        publicObj.alert('没有需要上传的文件')
        return false;
      }
      var file = el.files[0]
      if (typeof file === 'undefined') {
        publicObj.alert('没有需要上传的文件')
        return false;
      }
    }
    form.append('file', file);
    $.ajax({
      url: publicObj.uploadUrl,
      data: form,
      type:"POST",
      dataType: "json",
      processData: false,
      contentType: publicObj.CONTENT_TYPE_FILE,
      success: function (result) {
          console.log(result);
        if (+result.result === 0) {
          if (typeof (callback) === 'function') {
            callback(result.datas['url']);
            return false;
          }
        } else {
          publicObj.alert(result.msg);
        }
      },
      error: function (result) {
        publicObj.alert('您的网络不给力...');
      }
    });
  },

  copyContent: function (text, id) {
    if (navigator.userAgent.match(/(iPhone|iPod|iPad);?/i)) {
      //ios
      var copyDOM = document.querySelector('#' + id);  //要复制文字的节点
      var range = document.createRange();
      // 选中需要复制的节点
      range.selectNode(copyDOM); 
      // 执行选中元素
      window.getSelection().addRange(range);
      // 执行 copy 操作
      var successful = document.execCommand('copy');
      try {
        var msg = successful ? 'successful' : 'unsuccessful';
        console.log('copy is' + msg);
      } catch (err) {
        console.log('Oops, unable to copy');
      }
      // 移除选中的元素
      window.getSelection().removeAllRanges();
    } else {
      // 创建元素用于复制
      var aux = document.createElement("input");
      // 设置元素内容
      aux.setAttribute("value", text);
      // 将元素插入页面进行调用
      document.body.appendChild(aux);
      // 复制内容
      aux.select();
      // 将内容复制到剪贴板
      document.execCommand("copy");
      // 删除创建元素
      document.body.removeChild(aux);
    }
    publicObj.alert('已复制内容到剪贴板', 1);
  },
})
publicObj.ajaxInit();

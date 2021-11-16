﻿$(function() {




  // echarts_1({yield_rate:93.69,wd:23,sd:0.39,power:14.25}); // 温湿度及耗电量
  // seven_days({x:['周一', '周二', '周三', '周四', '周五', '周六', '周日'],y:[1500, 1200, 600, 200, 300, 300, 100]});
  // trend_chart({name:['2110087', '2110064', '2110085','21109016','21108016','21109016','21109016','21109016','21109016','21109016','2110087', '2110064', '2110085','21109016','21108016','21109016','21109016','21109016','21109016','21109016','21109016','21109016','21109016','21109016'],v1:[500,200,300,1000,500,500,200,300,1000,500,500,200,300,1000,500,500,200,300,1000,500,500,200,300,1000],v2:[120,150,220,120,150,220,120,150,220,120,150,220,120,150,220,120,150,220,120,150,220,220,345,100]}); //右一趋势图
  // echarts_31();
  // echarts_32();
  // echarts_33();
  // echarts_5({x:['浙江', '上海', '江苏', '广东', '北京', '深圳', '安徽', '四川'],y:[2, 3, 3, 9, 15, 12, 6, 4, 6, 7, 4, 10]});
  echarts_6();
  // progress_bar({name:['CDU产线', 'HVB产线', 'EDU产线', 'VPU产线', 'UNK产线','FUK产线'],value:[80,40,60,99,80,90]});






    var lockReconnect = false;//避免重复连接
    var ws = null; //WebSocket的引用
    // var wsUrl = "ws://10.10.5.25:9900"; //这个要与后端提供的相同
    var wsUrl = "ws://10.0.7.254:9900"; //这个要与后端提供的相同
    var created = getCookie('created');
    if(created === null) {
        var created = (new Date()).valueOf();
        setCookie('created', created);
    }

    function pageName(){
        var a = location.href;
        var b = a.split("/");
        var c = b.slice(b.length-1, b.length).toString(String).split(".");
        return c.slice(0, 1)[0];
    }

    function createWebSocket(){
        try {
            ws = new WebSocket(wsUrl);
            initEventHandle();
        } catch (e) {
            reconnect(wsUrl);
        }
    }
    function reconnect(url) {
        if(lockReconnect) return;
        lockReconnect = true;
        //没连接上会一直重连，设置延迟避免请求过多
        setTimeout(function () {
            createWebSocket(wsUrl);
            console.log("正在重连......")
            reconnect.lockReconnect = false;
        }, 30000); //这里设置重连间隔(ms)
    }

     /*********************初始化开始**********************/
    function initEventHandle() {
        ws.onopen = function() {
            ws.send(JSON.stringify({controller:'Index',action:'getData',params:{page_name:pageName(),created:created}}));
            heartCheck.reset().start();//心跳检测重置
        }
        // 收到服务器消息后响应
        ws.onmessage = function(e) {
            heartCheck.reset().start();//如果获取到消息，心跳检测重置 拿到任何消息都说明当前连接是正常的
            if(e.data !== 'PONG') {
                // var res = eval('(' + e.data + ')');
                var res = JSON.parse(e.data);
                doCase(res);
            }
        }
        ws.onclose = function() {
            $('#tag_title').html('服务端关闭，请联系IT。');
            reconnect(wsUrl);
        }
        ws.onerror = function () {
            $('#tag_title').html('服务端异常，请联系IT。');
            reconnect(wsUrl);
        };
    }

    //心跳检测
    var heartCheck = {
        timeout: 5000,//毫秒
        timeoutObj: null,
        serverTimeoutObj: null,
        reset: function(){
            clearTimeout(this.timeoutObj);
            clearTimeout(this.serverTimeoutObj);
            return this;
        },
        start: function(){
            var self = this;
            this.timeoutObj = setTimeout(function(){
                //这里发送一个心跳，后端收到后，返回一个心跳消息，onmessage拿到返回的心跳就说明连接正常
                ws.send("PING");
                self.serverTimeoutObj = setTimeout(function(){//如果超过一定时间还没重置，说明后端主动断开了
                }, self.timeout)
            }, this.timeout)
        }
    }


    // 强制退出
    window.onunload = function() {
        ws.close();
    }
    createWebSocket(wsUrl);/**启动连接**/
    //写cookies
    function setCookie(name, value) {
        var Days = 99999;
        var exp = new Date();
        exp.setTime(exp.getTime() + Days * 24 * 60 * 60 * 1000);
        document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString();
    }

    //读取cookies
    function getCookie(name) {
        var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
        if (arr = document.cookie.match(reg)) return unescape(arr[2]);
        else return null;
    }

    function doCase(res){
        // console.log(res);
        switch(res.case) {
            case 'ok':
                if(typeof(res.data.fd) !== 'undefined') $('#fd').html('(' + res.data.fd + ')');
                if(typeof(res.data.title) !== 'undefined') $('#tag_title').html(res.data.title);
                if(typeof(res.data.dashboard) !== 'undefined') echarts_1(res.data.dashboard);
                if(typeof(res.data.watt_meter_weeks) !== 'undefined') seven_days(res.data.watt_meter_weeks);
                if(typeof(res.data.block_data) !== 'undefined') {
                    echarts_31(res.data.p3305);
                    echarts_32(res.data.p3302);
                    echarts_33(res.data.p3307);
                    blockData(res.data.block_data);
                }
                if(typeof(res.data.roll) !== 'undefined') addRoll(res.data.roll.list);
                if(typeof(res.data.total_in_todays) !== 'undefined') echarts_5(res.data.total_in_todays);
                if(typeof(res.data.equ_used) !== 'undefined') progress_bar(res.data.equ_used);
                if(typeof(res.data.unover) !== 'undefined') trend_chart(res.data.unover);





                // if(typeof(res.data.today_task) !== 'undefined') draw_lb(res.data.today_task);
                // if(typeof(res.data.now_line) !== 'undefined') draw_now_line(res.data.now_line);
                // if(typeof(res.data.roll) !== 'undefined') {
                //     addRoll(res.data.roll.list);
                //     draw_rm(res.data.over_order);
                //     $('#list_updated').html(res.data.roll.list_updated);
                // }
                break;
            case 'jump':
                window.location.href = res.data.url;
                break;
        }
    }

    var MyMarhq = ''; //table滚动放到函数外,否则表格会颤抖
    function addRoll(data) {
        data = data.slice(0, -1); // 尾部总多一行,原因未知
        $('#roll').html('');
        for (var i in data) {
            $("#roll").prepend(`
                    <tr>
                        <td>${data[i].id}</td>
                        <td>${data[i].InvCode}</td>
                        <td>${data[i].reqQty}</td>
                        <td>${data[i].qty}</td>
                        <td>${data[i].isQue}</td>
                        <td>${data[i].level}</td>
                    </tr>
                `).insertAfter('.add_relation');
        }
        rollTable();
    };

    function blockData(res){
        // console.log(res);
        $('#total_b0').html(res.total_b0);
        $('#total_b1').html(res.total_b1);
        $('#total_b3').html(res.total_b3);
        $('#total_in_today').html(res.total_in_today);
        $('#total_in_year').html(res.total_in_year);
        $('#total_in_year_title').html(res.total_in_year_title);
        $('#total_in_all').html(res.total_in_all);
    }

    function rollTable(){
        // var MyMarhq = '';
        clearInterval(MyMarhq);
        var item = $('.tbl-body tbody tr').length
        // console.log(item)

        if(item> 4){
            $('.tbl-body tbody').html($('.tbl-body tbody').html()+$('.tbl-body tbody').html());
            $('.tbl-body').css('top', '0');
            var tblTop = 0;
            var speedhq = 50; // 数值越大越慢
            var outerHeight = $('.tbl-body tbody').find("tr").outerHeight();
            function Marqueehq(){
                if(tblTop <= -outerHeight*item){
                    tblTop = 0;
                } else {
                    tblTop -= 1;
                }
                $('.tbl-body').css('top', tblTop+'px');
            }

            MyMarhq = setInterval(Marqueehq,speedhq);

            // 鼠标移上去取消事件
            $(".tbl-body tbody").hover(function (){
                clearInterval(MyMarhq);
            },function (){
                clearInterval(MyMarhq);
                MyMarhq = setInterval(Marqueehq,speedhq);
            })

        }
    }

    // 温湿度及耗电量
    function echarts_1(res) {
        // 基于准备好的dom，初始化echarts实例
        var myChart = echarts.init(document.getElementById('echart1'));
        option = {
            // backgroundColor: '#1b1b1b',
            tooltip : {
                formatter: "{a} <br/>{c} {b}"
            },
            series : [
                {
                    name:'良品率百分比',
                    type:'gauge',
                    min:0,
                    max:100,
                    radius : '95%',
                    splitNumber:5,
                    axisLine: {            // 坐标轴线
                        lineStyle: {       // 属性lineStyle控制线条样式
                            color: [[0.09, 'lime'],[0.82, '#1e90ff'],[1, '#ff4500']],
                            width: 3,
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    axisLabel: {            // 坐标轴小标记
                        textStyle: {       // 属性lineStyle控制线条样式
                            fontWeight: 'bolder',
                            color: '#fff',
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    axisTick: {            // 坐标轴小标记
                        length :15,        // 属性length控制线长
                        lineStyle: {       // 属性lineStyle控制线条样式
                            color: 'auto',
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    splitLine: {           // 分隔线
                        length :25,         // 属性length控制线长
                        lineStyle: {       // 属性lineStyle（详见lineStyle）控制线条样式
                            width:3,
                            color: '#fff',
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    pointer: {           // 分隔线
                        shadowColor : '#fff', //默认透明
                        shadowBlur: 5
                    },
                    title : {
                        textStyle: {       // 其余属性默认使用全局文本样式，详见TEXTSTYLE
                            fontWeight: 'bolder',
                            fontSize: 18,
                            fontStyle: 'italic',
                            color: '#fff',
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    detail : {
                        backgroundColor: 'rgba(30,144,255,0.8)',
                        borderWidth: 1,
                        borderColor: '#FEFB1B',
                        shadowColor : '#fff', //默认透明
                        shadowBlur: 5,
                        offsetCenter: [0, '80%'],       // x, y，单位px
                        textStyle: {       // 其余属性默认使用全局文本样式，详见TEXTSTYLE
                            fontWeight: 'bolder',
                            color: '#FF0000'
                        }
                    },
                    data:[{value: res.yield_rate, name: '今日良品率'}]
                },
                {
                    name:'耗电',
                    type:'gauge',
                    center : ['22%', '55%'],    // 默认全局居中
                    radius : '90%',
                    min:0,
                    max:60,
                    endAngle:55,
                    splitNumber:6,
                    axisLine: {            // 坐标轴线
                        lineStyle: {       // 属性lineStyle控制线条样式
                            color: [[0.29, 'lime'],[0.86, '#1e90ff'],[1, '#ff4500']],
                            width: 2,
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    axisLabel: {            // 坐标轴小标记
                        textStyle: {       // 属性lineStyle控制线条样式
                            fontWeight: 'bolder',
                            color: '#fff',
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    axisTick: {            // 坐标轴小标记
                        length :12,        // 属性length控制线长
                        lineStyle: {       // 属性lineStyle控制线条样式
                            color: 'auto',
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    splitLine: {           // 分隔线
                        length :20,         // 属性length控制线长
                        lineStyle: {       // 属性lineStyle（详见lineStyle）控制线条样式
                            width:3,
                            color: '#fff',
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    pointer: {
                        width:5,
                        shadowColor : '#fff', //默认透明
                        shadowBlur: 5
                    },
                    title : {
                        offsetCenter: [-20, '70%'],       // x, y，单位px
                        textStyle: {       // 其余属性默认使用全局文本样式，详见TEXTSTYLE
                            fontWeight: 'bolder',
                            fontStyle: 'italic',
                            color: '#fff',
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    detail : {
                        //backgroundColor: 'rgba(30,144,255,0.8)',
                       // borderWidth: 1,
                        borderColor: '#fff',
                        shadowColor : '#fff', //默认透明
                        shadowBlur: 5,
                        width: 80,
                        height:30,
                        offsetCenter: [25, '50%'],       // x, y，单位px
                        textStyle: {       // 其余属性默认使用全局文本样式，详见TEXTSTYLE
                            fontWeight: 'bolder',
                            fontSize:14,
                            color: '#fff'
                        }
                    },
                    data:[{value: res.power, name: res.power + '万度'}]
                },
                {
                    name:'当前温度',
                    type:'gauge',
                    center : ['85%', '50%'],    // 默认全局居中
                    radius : '90%',
                    min:0,
                    max:50,
                    startAngle:145,
                    endAngle:50,
                    splitNumber:2,
                    axisLine: {            // 坐标轴线
                        lineStyle: {       // 属性lineStyle控制线条样式
                            color: [[0.2, 'lime'],[0.8, '#1e90ff'],[1, '#ff4500']],
                            width: 2,
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    axisTick: {            // 坐标轴小标记
                        length :12,        // 属性length控制线长
                        lineStyle: {       // 属性lineStyle控制线条样式
                            color: 'auto',
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    axisLabel: {
                        textStyle: {       // 属性lineStyle控制线条样式
                            fontWeight: 'bolder',
                            color: '#fff',
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        },
                        formatter:function(v){
                            switch (v + '') {
                                case '0' : return '0℃';
                                case '1' : return '温度';
                                case '2' : return '50℃';
                            }
                        }
                    },
                    splitLine: {           // 分隔线
                        length :15,         // 属性length控制线长
                        lineStyle: {       // 属性lineStyle（详见lineStyle）控制线条样式
                            width:3,
                            color: '#fff',
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    pointer: {
                        width:2,
                        shadowColor : '#fff', //默认透明
                        shadowBlur: 5
                    },
                    title : {
                        show: false
                    },
                    detail : {
                        show: true,             // 是否显示详情,默认 true。
                        offsetCenter: [20,-50],// 相对于仪表盘中心的偏移位置，数组第一项是水平方向的偏移，第二项是垂直方向的偏移。可以是绝对的数值，也可以是相对于仪表盘半径的百分比。
                        textStyle: {       // 其余属性默认使用全局文本样式，详见TEXTSTYLE
                            fontWeight: 'bolder',
                            color: '#fff',
                            fontSize : 18
                        },
                        formatter: "{value}℃",  // 格式化函数或者字符串

                    },


                    data:[{value: res.wd, name: '℃'}]
                },
                {
                    name:'湿度',
                    type:'gauge',
                    center : ['85%', '50%'],    // 默认全局居中
                    radius : '90%',
                    min:0,
                    max:1,
                    startAngle:310,
                    endAngle:215,
                    axisLine: {            // 坐标轴线
                        lineStyle: {       // 属性lineStyle控制线条样式
                            color: [[0.2, 'lime'],[0.8, '#1e90ff'],[1, '#ff4500']],
                            width: 2,
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    axisTick: {            // 坐标轴小标记
                        show: false
                    },
                    axisLabel: {
                        textStyle: {       // 属性lineStyle控制线条样式
                            fontWeight: 'bolder',
                            color: '#fff',
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        },
                        formatter:function(v){
                            switch (v + '') {
                                case '0' : return '0%';
                                case '1' : return '湿度';
                                case '2' : return '100%';
                            }
                        }
                    },
                    splitLine: {           // 分隔线
                        length :5,         // 属性length控制线长
                        lineStyle: {       // 属性lineStyle（详见lineStyle）控制线条样式
                            width:3,
                            color: '#fff',
                            shadowColor : '#fff', //默认透明
                            shadowBlur: 10
                        }
                    },
                    pointer: {
                        width:2,
                        shadowColor : '#fff', //默认透明
                        shadowBlur: 5
                    },
                    title : {
                        // show: true
                    },
                    // tooltip:{
                    //     trigger: 'item',
                    //     formatter:"{a} <BR/> {b}:{c}%",
                    // },

                    detail : {
                        show: true,
                        textStyle: {       // 其余属性默认使用全局文本样式，详见TEXTSTYLE
                            color: '#3ebf27',
                            fontSize : 18
                        },
                        // formatter:'{value}%'
                        formatter: function(a){
                            return (Math.round(a * 100) + "%");// 小数点后两位百分比
                        }
                    },
                    data:[{value: res.sd}] // 2为 100%
                }
            ]
        };

        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(option);
        window.addEventListener("resize",function(){
            myChart.resize();
        });
    }
function seven_days(res) {
        // 基于准备好的dom，初始化echarts实例
        var myChart = echarts.init(document.getElementById('echart2'));

        option = {
            //  backgroundColor: '#00265f',
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow'}
            },
            grid: {
                left: '0%',
            	top:'10px',
                right: '0%',
                bottom: '4%',
               containLabel: true
            },
            xAxis: [{
                type: 'category',
              		data: res.x,
                axisLine: {
                    show: true,
                 lineStyle: {
                        color: "rgba(255,255,255,.1)",
                        width: 1,
                        type: "solid"
                    },
                },

                axisTick: {
                    show: false,
                },
            	axisLabel:  {
                        interval: 0,
                       // rotate:50,
                        show: true,
                        splitNumber: 15,
                        textStyle: {
            					color: "rgba(255,255,255,.6)",
                            fontSize: '12',
                        },
                    },
            }],
            yAxis: [{
                type: 'value',
                axisLabel: {
                   //formatter: '{value} %'
            		show:true,
            		 textStyle: {
            					color: "rgba(255,255,255,.6)",
                            fontSize: '12',
                        },
                },
                axisTick: {
                    show: false,
                },
                axisLine: {
                    show: true,
                    lineStyle: {
                        color: "rgba(255,255,255,.1	)",
                        width: 1,
                        type: "solid"
                    },
                },
                splitLine: {
                    lineStyle: {
                       color: "rgba(255,255,255,.1)",
                    }
                }
            }],
            series: [
            	{

                type: 'bar',
                data: res.y,
                barWidth:'35%', //柱子宽度
               // barGap: 1, //柱子之间间距
                itemStyle: {
                    normal: {
                        color:'#27d08a',
                        opacity: 1,
            			barBorderRadius: 5,
                    }
                }
            }

            ]
            };

        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(option);
        window.addEventListener("resize",function(){
            myChart.resize();
        });
    }
function echarts_5(res) {
        // 基于准备好的dom，初始化echarts实例
        var myChart = echarts.init(document.getElementById('echart5'));

       option = {
  //  backgroundColor: '#00265f',
    tooltip: {
        trigger: 'axis',
        axisPointer: {
            type: 'shadow'
        }
    },

    grid: {
        left: '0%',
		top:'10px',
        right: '0%',
        bottom: '2%',
       containLabel: true
    },
    xAxis: [{
        type: 'category',
      		data: res.x,
        axisLine: {
            show: true,
         lineStyle: {
                color: "rgba(255,255,255,.1)",
                width: 1,
                type: "solid"
            },
        },

        axisTick: {
            show: false,
        },
		axisLabel:  {
                interval: 0,
               // rotate:50,
                show: true,
                splitNumber: 15,
                textStyle: {
 					color: "rgba(255,255,255,.6)",
                    fontSize: '12',
                },
            },
    }],
    yAxis: [{
        type: 'value',
        axisLabel: {
           //formatter: '{value} %'
			show:true,
			 textStyle: {
 					color: "rgba(255,255,255,.6)",
                    fontSize: '12',
                },
        },
        axisTick: {
            show: false,
        },
        axisLine: {
            show: true,
            lineStyle: {
                color: "rgba(255,255,255,.1	)",
                width: 1,
                type: "solid"
            },
        },
        splitLine: {
            lineStyle: {
               color: "rgba(255,255,255,.1)",
            }
        }
    }],
    series: [{
        type: 'bar',
        data: res.y,
        barWidth:'35%', //柱子宽度
       // barGap: 1, //柱子之间间距
        itemStyle: {
            normal: {
                color:'#2f89cf',
                opacity: 1,
				barBorderRadius: 5,
            }
        }
    }
	]
};

        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(option);
        window.addEventListener("resize",function(){
            myChart.resize();
        });
    }

function trend_chart(res) {
        // 基于准备好的dom，初始化echarts实例
        var myChart = echarts.init(document.getElementById('echart4'));
        option = {
    	    tooltip: {
            trigger: 'axis',
            axisPointer: {
                lineStyle: {
                    color: '#dddc6b'
                }
            }
        },
    		    legend: {
        top:'0%',
            data:['计划','实际'],
                    textStyle: {
               color: 'rgba(255,255,255,.5)',
    			fontSize:'12',
            }
        },
        grid: {
            // show:true, //是否显示直角坐标系网格。[ default: false ]
            left: '5',
    		top: '30',
            right: '5',
            bottom: '1',
            // borderColor:"#c45455"
            containLabel: true
        },
        xAxis: [{
            type: 'category',
            boundaryGap: false,
            axisLabel:  {
                    textStyle: {
     					color: "rgba(255,255,255,.6)",
    					fontSize:12,
                    },
                },
            axisLine: {
    			lineStyle: {
    				color: 'rgba(255,255,255,.2)'
    			}

            },
            axisLabel: {
                interval:0,
                rotate:60
            },
            data: res.name

        }, {
            axisPointer: {show: false},
            axisLine: {  show: false},
            position: 'bottom',
            offset: 20,
        }],

        yAxis: [{
            type: 'value',
            axisTick: {show: false},
            axisLine: {
                lineStyle: {
                    color: 'rgba(255,255,255,.1)'
                }
            },
           axisLabel:  {
                    textStyle: {
     					color: "rgba(255,255,255,.6)",
    					fontSize:12,
                    },
                },

            splitLine: {
                lineStyle: {
                     color: 'rgba(255,255,255,.1)'
                }
            }
        }],
        series: [
    		{
            name: '计划',
            type: 'line',
             smooth: true,
            symbol: 'circle',
            symbolSize: 5,
            showSymbol: false,
            lineStyle: {

                normal: {
    				color: '#0184d5',
                    width: 2
                }
            },
            areaStyle: {
                normal: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                        offset: 0,
                        color: 'rgba(1, 132, 213, 0.4)'
                    }, {
                        offset: 0.8,
                        color: 'rgba(1, 132, 213, 0.1)'
                    }], false),
                    shadowColor: 'rgba(0, 0, 0, 0.1)',
                }
            },
    			itemStyle: {
    			normal: {
    				color: '#0184d5',
    				borderColor: 'rgba(221, 220, 107, .1)',
    				borderWidth: 12
    			}
    		},
            data: res.v1

        },
        {
            name: '实际',
            type: 'line',
            smooth: true,
            symbol: 'circle',
            symbolSize: 5,
            showSymbol: false,
            lineStyle: {

                normal: {
    				color: '#00d887',
                    width: 2
                }
            },
            areaStyle: {
                normal: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                        offset: 0,
                        color: 'rgba(0, 216, 135, 0.4)'
                    }, {
                        offset: 0.8,
                        color: 'rgba(0, 216, 135, 0.1)'
                    }], false),
                    shadowColor: 'rgba(0, 0, 0, 0.1)',
                }
            },
    			itemStyle: {
    			normal: {
    				color: '#00d887',
    				borderColor: 'rgba(221, 220, 107, .1)',
    				borderWidth: 12
    			}
    		},
            data: res.v2

        },

    	]};

        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(option);
        window.addEventListener("resize",function(){
            myChart.resize();
        });
    }
function echarts_6() {
    // 基于准备好的dom，初始化echarts实例
    var myChart = echarts.init(document.getElementById('echart6'));

    var dataStyle = {
    	normal: {
    		label: {
    			show: false
    		},
    		labelLine: {
    			show: false
    		},
    		//shadowBlur: 40,
    		//shadowColor: 'rgba(40, 40, 40, 1)',
    	}
    };
    var placeHolderStyle = {
    	normal: {
    		color: 'rgba(255,255,255,.05)',
    		label: {show: false,},
    		labelLine: {show: false}
    	},
    	emphasis: {
    		color: 'rgba(0,0,0,0)'
    	}
    };
    option = {
    	color: ['#0f63d6', '#0f78d6', '#0f8cd6', '#0fa0d6', '#0fb4d6'],
    	tooltip: {
    		show: true,
    		formatter: "{a} : {c} "
    	},
    	legend: {
    		itemWidth: 10,
            itemHeight: 10,
    		itemGap: 12,
    		bottom: '3%',

    		data: ['浙江', '上海', '广东', '北京', '深圳'],
    		textStyle: {
                        color: 'rgba(255,255,255,.6)',
                    }
    	},

    	series: [
    		{
    		name: '浙江',
    		type: 'pie',
    		clockWise: false,
    		center: ['50%', '42%'],
    		radius: ['59%', '70%'],
    		itemStyle: dataStyle,
    		hoverAnimation: false,
    		data: [{
    			value: 80,
    			name: '01'
    		}, {
    			value: 20,
    			name: 'invisible',
    			tooltip: {show: false},
    			itemStyle: placeHolderStyle
    		}]
    	},
    		{
    		name: '上海',
    		type: 'pie',
    		clockWise: false,
    		center: ['50%', '42%'],
    		radius: ['49%', '60%'],
    		itemStyle: dataStyle,
    		hoverAnimation: false,
    		data: [{
    			value: 70,
    			name: '02'
    		}, {
    			value: 30,
    			name: 'invisible',
    			tooltip: {show: false},
    			itemStyle: placeHolderStyle
    		}]
    	},
    		{
    		name: '广东',
    		type: 'pie',
    		clockWise: false,
    		hoverAnimation: false,
    		center: ['50%', '42%'],
    		radius: ['39%', '50%'],
    		itemStyle: dataStyle,
    		data: [{
    			value: 65,
    			name: '03'
    		}, {
    			value: 35,
    			name: 'invisible',
    			tooltip: {show: false},
    			itemStyle: placeHolderStyle
    		}]
    	},
    		{
    		name: '北京',
    		type: 'pie',
    		clockWise: false,
    		hoverAnimation: false,
    		center: ['50%', '42%'],
    		radius: ['29%', '40%'],
    		itemStyle: dataStyle,
    		data: [{
    			value: 60,
    			name: '04'
    		}, {
    			value: 40,
    			name: 'invisible',
    			tooltip: {show: false},
    			itemStyle: placeHolderStyle
    		}]
    	},
    		{
    		name: '深圳',
    		type: 'pie',
    		clockWise: false,
    		hoverAnimation: false,
    		center: ['50%', '42%'],
    		radius: ['20%', '30%'],
    		itemStyle: dataStyle,
    		data: [{
    			value: 50,
    			name: '05'
    		}, {
    			value: 50,
    			name: 'invisible',
    			tooltip: {show: false},
    			itemStyle: placeHolderStyle
    		}]
    	}, ]
    };

        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(option);
        window.addEventListener("resize",function(){
            myChart.resize();
        });
    }
function echarts_31(res) {
    // 基于准备好的dom，初始化echarts实例
    var myChart = echarts.init(document.getElementById('fb1'));
    option = {

    	    title: [{
            text: '电控车间',
            left: 'center',
            textStyle: {
                color: '#fff',
    			fontSize:'16'
            }

        }],
        tooltip: {
            trigger: 'item',
            formatter: "{a} <br/>{b}: {c} ({d}%)",
            position:function(p){   //其中p为当前鼠标的位置
                return [p[0] + 10, p[1] - 10];
            }
        },
    legend: {
        top:'70%',
               itemWidth: 10,
                itemHeight: 10,
                data:[res[0].name,res[1].name],
                        textStyle: {
                    color: 'rgba(255,255,255,.5)',
        			fontSize:'12',
                }
            },
            series: [
                {
                	name:'电控车间',
                    type:'pie',
        			center: ['50%', '42%'],
                    radius: ['40%', '60%'],
                          color: ['#065aab', '#066eab', '#0682ab', '#0696ab', '#06a0ab','#06b4ab','#06c8ab','#06dcab','#06f0ab'],
                    label: {show:false},
        			labelLine: {show:false},
                    data:res
                }
            ]
        };

    // 使用刚指定的配置项和数据显示图表。
    myChart.setOption(option);
    window.addEventListener("resize",function(){
        myChart.resize();
    });
}
function echarts_32(res) {
    // 基于准备好的dom，初始化echarts实例
    var myChart = echarts.init(document.getElementById('fb2'));
    option = {
	    title: [{
        text: '贴片车间',
        left: 'center',
        textStyle: {
            color: '#fff',
			fontSize:'16'
        }

        }],
        tooltip: {
            trigger: 'item',
            formatter: "{a} <br/>{b}: {c} ({d}%)",
            position:function(p){   //其中p为当前鼠标的位置
                return [p[0] + 10, p[1] - 10];
            }
        },
        legend: {

        top:'70%',
            itemWidth: 10,
            itemHeight: 10,
            data:[res[0].name,res[1].name],
                    textStyle: {
               color: 'rgba(255,255,255,.5)',
    			fontSize:'12',
            }
        },
        series: [
            {
            	name:'贴片车间',
                type:'pie',
    			center: ['50%', '42%'],
                radius: ['40%', '60%'],
                color: ['#065aab', '#066eab', '#0682ab', '#0696ab', '#06a0ab','#06b4ab','#06c8ab','#06dcab','#06f0ab'],
                label: {show:false},
    			labelLine: {show:false},
                data:res
            }
        ]
    };

    // 使用刚指定的配置项和数据显示图表。
    myChart.setOption(option);
    window.addEventListener("resize",function(){
        myChart.resize();
    });
}
function echarts_33(res) {
    // 基于准备好的dom，初始化echarts实例
    var myChart = echarts.init(document.getElementById('fb3'));
    option = {
    	    title: [{
            text: '电机车间',
            left: 'center',
            textStyle: {
                color: '#fff',
    			fontSize:'16'
            }

        }],
        tooltip: {
            trigger: 'item',
            formatter: "{a} <br/>{b}: {c} ({d}%)",
            position:function(p){   //其中p为当前鼠标的位置
                return [p[0] + 10, p[1] - 10];
            }
        },
        legend: {
        top:'70%',
           itemWidth: 10,
            itemHeight: 10,
            data:[res[0].name,res[1].name],
                    textStyle: {
                color: 'rgba(255,255,255,.5)',
    			fontSize:'12',
            }
        },
        series: [
            {
            	name:'电机车间',
                type:'pie',
    			center: ['50%', '42%'],
                radius: ['40%', '60%'],
                       color: ['#065aab', '#066eab', '#0682ab', '#0696ab', '#06a0ab','#06b4ab','#06c8ab','#06dcab','#06f0ab'],
                label: {show:false},
    			labelLine: {show:false},
                data:res
            }
        ]
    };

    // 使用刚指定的配置项和数据显示图表。
    myChart.setOption(option);
    window.addEventListener("resize",function(){
        myChart.resize();
    });
}

function progress_bar(res){
    var myChart = echarts.init(document.getElementById('echart7'));
    var myColor = ['#1089E7', '#F57474', '#56D0E3', '#F8B448', '#8B78F6','#FF6600'];
    // 指定图表的配置项和数据
    option = {
        title: {
            text: '设备/软件正常使用时间占比',
            x: 'center',
            textStyle: {
                color: '#FFF'
            },
            left: '40%',
            top: '3%'
        },
        //图标位置
        grid: {
            top: '8%',
            left: '10%'
        },
        xAxis: {
            show: false
        },


        xAxis: {
            type: 'value',
            splitLine:{show:false},
            axisLabel:{show:false},
            axisTick:{show:false},
            axisLine:{show:false}
        },
        yAxis:[
           {
                type: 'category',
                axisTick:{show:false},
                axisLine:{show:false},
                axisLabel:{
                    color:"black",
                    fontSize:14,
                    textStyle: {
                          color: '#fff'
                    }
                },
                data:res.name,
                max:14, // 关键：设置y刻度最大值，相当于设置总体行高
                inverse:true
            },
             {
                type: 'category',
                axisTick:{show:false},
                axisLine:{show:false},
                axisLabel:{
                    color:"black",
                    fontSize:14,
                    textStyle: {
                          color: '#fff'
                    }
                },
                data:[100,100,100,100,100,100],
                max:300, // 关键：设置y刻度最大值，相当于设置总体行高
                inverse:true
            }
        ],
        series: [
          {
            name:"条",
            type:"bar",
            barWidth:19,
            data:res.value,
            barCategoryGap:20,
            itemStyle:{
                normal:{
                    barBorderRadius:20,
                    color: function(params) {
                        var num = myColor.length;
                        return myColor[params.dataIndex % num]
                    },
                }
            },
            label: {
                normal: {
                    show: true,
                    position: 'inside',
                    formatter: '{c}%'
                }
            },


            zlevel:1
          },{
              name:"进度条背景",
              type:"bar",
              barGap:"-100%",
              barWidth:19,
              data:[100,100,100,100,100,100],
              color:"#2e5384",
              itemStyle:{
                  normal:{
                      barBorderRadius:10
                  }
              },
          }
        ]
    };


    // 使用刚指定的配置项和数据显示图表。
    myChart.setOption(option);
    window.addEventListener("resize",function(){
        myChart.resize();
    });
}


});
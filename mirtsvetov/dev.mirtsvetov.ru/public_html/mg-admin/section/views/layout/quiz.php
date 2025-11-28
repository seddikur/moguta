<style>
  .js-start-quiz {
    width: 50%;
    margin: auto;
    text-align: center;
  }

  .js-question {
    margin: 30px 0;
    display: none;
  }

  .js-question-title {
    font-weight: 600;
    font-size: 36px;
    margin-bottom: 20px;
    color:#3c3c3c;
  }
  @media (max-width: 430px) {
    .js-question-title {
      font-size: 22px;
    }
    .js-question {
      margin: 30px 0 0;
    }
  }

  .js-question .js-next {
    display: none;
    margin: 30px auto;
    color: #FFF !important;
    cursor: pointer;
    padding: 5px 8px;
    border: solid 1px #03be81;
    border-radius: 5px;
    transition: all 0.3s ease-in-out;
   
    font-size: 16px;
    font-weight: 600;
    background: #03be81;
    width: 100px;
  }



  .js-question .js-next:hover {
    background: #3576e8;
    color: #FFF !important;
    border: solid 1px #3576e8;
  }

  .js-answer {
    cursor: pointer;
    border: solid 1px gray;
    padding: 5px 8px;
    margin-top: 5px;
    width: auto;
    display: inline-block;
    border-radius: 5px;
    transition: all 0.3s ease-in-out;
    overflow: hidden;
    position: relative;
    text-align: center;
  }

  .js-quiz-subtitle {
    font-size: 21px;
    line-height: 1.2;
    color: #3676ad;
  }

  .js-answer span {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;

  }
  .js-answer span.span-answer{
    font-size: 16px!important;
    color:#272727;
  }

  .js-answer span svg{
    width: 16px;
    height: 16px;
    object-fit: contain;
    float: left;
    margin-right: 5px;
    background: #FFF;
    overflow: hidden;
    border-radius: 5px;
  } 
  .js-question .active span,
  .js-answer:hover span {
    color: #FFF !important;
    transition: all 0.3s ease-in-out;
  }

  .js-answer::before {
    content: '';
    position: absolute;
    width: calc(100% + 2px);
    height: 100%;
    left: 0;
    top: -100%;
    background-color: #3576e8;
    z-index: 1;
  }

  .js-question .js-answer.active::before,
  .js-answer:hover::before {
    top: 0;
    transition: all 0.3s ease-in-out;
  }

  .js-answer-unclick:hover::before {
    top: -100%;
    transition: all 0.3s ease-in-out;
  }

  .js-answer.js-answer-unclick:hover span {
    color:#272727!important;
  }
  
  .js-answer.active,
  .js-answer:hover {
    border-color: #3576e8;
  }
  .first-templates-popup__container {
    z-index: 1;
  }
  @media (max-width: 1200px) {
    .js-start-quiz {
      width: 100%;
      max-width: 850px;
      margin: 0 auto;
    }
  }

  @media (max-width: 940px) {
    .js-start-quiz {
      width: auto;
      max-width: 850px;
      margin: 0 30px;
    }
  }

  @media (max-width: 900px) {
    .first-templates-popup__container {
      position: absolute;
      bottom: 0;
      width: 100%;
      margin-bottom: 0px;
      margin-top: 0px;
      padding-top: 100px;
    }
    @media (max-width: 430px) {
      .first-templates-popup__container {
        padding-top: 40px;
      }
      .js-quiz-subtitle {
        font-size: 15px;
      }
      .js-start-quiz {
        margin: 0 10px;
        padding-bottom: 70px;
      }
      .js-answer span.span-answer {
        font-size: 15px !important;
      }
      .js-answer {
        margin-top: 0;
      }
      .js-question .js-next {
        margin-bottom: 70px;
      }
    }
  }
</style>
<?php /* наша метрика для Moguta.CLOUD что бы собирать ретаргетинговые цели на тех кто указал что у него есть товары и домен*/ ?>

<!-- Yandex.Metrika counter -->
<script type="text/javascript" >
   (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
   m[i].l=1*new Date();
   for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
   k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
   (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

   ym(86034605, "init", {
        clickmap:true,
        trackLinks:true,
        accurateTrackBounce:true,
        webvisor:false
   });
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/86034605" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->

<div class="js-start-quiz">
  <div class="js-quiz-subtitle">Поздравляем, сайт успешно создан! Давайте познакомимся.</div>
  <div class="js-quiz-subtitle" style="color:#797979;">Мы поможем вам подобрать шаблон на основе ваших ответов!</div>

  <div class="js-question js-question-start">
    <div class="js-question-title" data-title-toresp="Товары">Вы уже что-то продаёте?</div>
    <div class="js-answer js-show-next"><span class="span-answer" >Ещё нет</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Продаю, но не через интернет</span></div>
    <div class="js-answer js-show-sub-quest-1"><span class="span-answer" >Продаю в соцсетях или на маркетплейсах</span></div>
    <div class="js-answer js-show-sub-js-quest-2"><span class="span-answer" >Продаю через уже существующий сайт</span></div>

    <div class="js-next" data-next=".js-quest-2">Далее</div>
  </div>

  <div class="js-question flag-js-show-sub-quest-1">
    <div class="js-question-title" data-title-toresp="Площадка" >На каких площадках?</div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="mr-2"><path d="M0 3C0 1.34315 1.34315 0 3 0H17C18.6569 0 20 1.34315 20 3V17C20 18.6569 18.6569 20 17 20H3C1.34315 20 0 18.6569 0 17V3Z" fill="url(#gradient_marketplaces)"></path> <path fill-rule="evenodd" clip-rule="evenodd" d="M5.98268 13.2851H4.73294L2.875 6.72152H4.01087L5.38975 11.8536L6.8825 6.72152H7.87812L9.36115 11.8536L10.74 6.72152H11.8815L10.0193 13.2851H8.7696L7.381 8.49279L5.98268 13.2851ZM16.2042 9.88123C16.4866 10.0223 16.723 10.2406 16.886 10.5107C17.049 10.7809 17.1318 11.0917 17.1248 11.407C17.1315 11.6584 17.0838 11.9083 16.9848 12.1396C16.8858 12.3709 16.7379 12.578 16.5513 12.7469C16.1743 13.1031 15.6718 13.2965 15.153 13.2851H12.3369V6.71874H14.9475C15.4528 6.70742 15.9421 6.89643 16.3083 7.24444C16.4896 7.40683 16.6337 7.60638 16.7308 7.82948C16.8278 8.05258 16.8756 8.29397 16.8707 8.53717C16.8794 8.79839 16.8231 9.05769 16.7068 9.29179C16.5905 9.5259 16.4177 9.72749 16.2042 9.87846V9.88123ZM14.9475 7.73684H13.42V9.45679H14.9475C15.1765 9.45679 15.3962 9.3659 15.5582 9.2041C15.7202 9.04231 15.8112 8.82286 15.8112 8.59404C15.8112 8.36523 15.7202 8.14579 15.5582 7.98399C15.3962 7.82219 15.1765 7.73129 14.9475 7.73129V7.73684ZM13.42 12.2753H15.1558C15.3936 12.2661 15.6186 12.1652 15.7835 11.9939C15.9485 11.8226 16.0407 11.5941 16.0407 11.3564C16.0407 11.1187 15.9485 10.8902 15.7835 10.7189C15.6186 10.5475 15.3936 10.4467 15.1558 10.4374H13.42V12.2753Z" fill="white"></path> <defs><linearGradient id="gradient_marketplaces" x1="2.0318" y1="17.8113" x2="17.8241" y2="2.2934" gradientUnits="userSpaceOnUse"><stop stop-color="#A92284"></stop> <stop offset="1" stop-color="#53237D"></stop></linearGradient></defs></svg>Wildberries</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="mr-2"><g clip-path="url(#clip_path_marketplaces)"><rect width="20" height="20" rx="3" fill="#005BFF"></rect> <path fill-rule="evenodd" clip-rule="evenodd" d="M18.5056 5.88761C18.4479 5.33209 19.2834 5.03988 19.5667 5.53985C19.7667 5.99649 19.8478 6.49758 19.9989 6.97088V9.54741C18.9545 9.07188 17.8957 8.62746 16.8491 8.15638C17.0279 8.80412 17.2424 9.4463 17.349 10.1096C17.4357 10.554 16.8324 10.9273 16.4846 10.6307C16.1869 10.4085 16.1624 10.0052 16.0624 9.6774C15.7891 8.5408 15.4136 7.42864 15.1925 6.27981C15.5436 6.26092 15.8513 6.44869 16.1613 6.58424C17.1268 7.00533 18.089 7.43308 19.0501 7.85306C18.834 7.20761 18.6508 6.55162 18.5012 5.88761H18.5056ZM9.34615 8.2497C9.8541 7.80283 10.4583 7.4792 11.1118 7.30403C11.7653 7.12886 12.4504 7.10687 13.1137 7.23976C13.9226 7.42531 14.767 7.92639 14.9947 8.77523C15.1403 9.2041 15.0647 9.66407 14.9236 10.0852C14.5922 10.7757 14.0466 11.3407 13.3681 11.6962C12.1515 12.3461 10.5905 12.4895 9.37393 11.7551C8.82396 11.404 8.38509 10.7973 8.43842 10.1174C8.38065 9.38186 8.82395 8.72301 9.34726 8.2497H9.34615ZM9.52169 10.1174C9.61391 10.3818 9.74391 10.6518 10.0061 10.784C10.8394 11.2529 11.8805 11.1173 12.7148 10.7229C13.257 10.4507 13.7992 10.0118 13.9181 9.38964C13.9759 9.06855 13.7481 8.78967 13.4881 8.63524C12.9515 8.29192 12.2804 8.33859 11.6727 8.39303C10.7438 8.56191 9.74279 9.11743 9.52169 10.1174ZM4.02533 9.33409C3.67759 9.42794 3.52056 9.64057 3.57425 9.89517C3.64757 10.2429 3.952 10.5962 4.34309 10.4707C4.84973 10.3296 5.35859 10.2007 5.86301 10.054C5.35637 11.2706 4.87973 12.4984 4.39087 13.7261C4.32198 13.8561 4.3242 14.1372 4.53642 14.0727C5.80412 13.7394 7.07627 13.4061 8.33731 13.0517C8.70507 12.9406 8.74063 12.4784 8.60952 12.1762C8.53063 12.0682 8.41922 11.9884 8.29166 11.9484C8.16409 11.9084 8.02706 11.9104 7.90067 11.9539C7.3207 12.1284 6.72963 12.2617 6.14855 12.4339C6.52408 11.4995 6.90073 10.5651 7.26626 9.62852C7.40848 9.23965 7.61513 8.86967 7.67957 8.45747C7.49337 8.44853 7.30677 8.46271 7.12405 8.49969C6.09411 8.78745 5.05861 9.05522 4.02533 9.33409ZM0 10.3996C0.333568 10.2381 0.700274 10.1568 1.07085 10.1622C1.44143 10.1676 1.80561 10.2595 2.13433 10.4307C2.59443 10.67 2.96899 11.046 3.20638 11.5071C3.44378 11.9682 3.53232 12.4915 3.45981 13.005C3.39888 13.5061 3.17996 13.9749 2.83477 14.3432C2.48957 14.7116 2.03604 14.9605 1.53991 15.0538C1.01883 15.1926 0.492195 15.016 0 14.8504V13.3016C0.333315 13.7772 0.975502 14.0794 1.53658 13.8349C2.1921 13.6127 2.53653 12.7817 2.24988 12.155C2.02767 11.5295 1.24993 11.1295 0.623299 11.4095C0.371596 11.5189 0.155644 11.6968 0 11.9228V10.3996Z" fill="white"></path> <path fill-rule="evenodd" clip-rule="evenodd" d="M9.52179 10.1172C9.744 9.12502 10.7439 8.56172 11.6728 8.38951C12.2805 8.33506 12.9516 8.2884 13.4882 8.63171C13.7482 8.78615 13.976 9.06502 13.9182 9.38612C13.7993 10.0116 13.2516 10.4505 12.7149 10.7194C11.8805 11.1138 10.8395 11.2493 10.0062 10.7805C9.74289 10.6483 9.61401 10.3783 9.52179 10.1139V10.1172ZM13.3727 11.696C12.1561 12.346 10.5951 12.4893 9.37846 11.7549C9.08403 14.5036 8.77739 17.2512 8.47852 19.9989H17.0079C18.6647 19.9989 20.0079 18.6557 20.0079 16.9989V15.5547C18.3257 13.7214 16.6214 11.9093 14.9326 10.085C14.6 10.7762 14.0528 11.3413 13.3727 11.696Z" fill="#F91155"></path></g> <defs><clipPath id="clip_path_marketplaces"><rect width="20" height="20" rx="3" fill="white"></rect></clipPath></defs></svg>Ozon</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer"><svg width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg" question-handle="marketplaces" class="mr-2"><path d="M17 0H3C1.343 0 0 1.58 0 3.53v12.94C0 18.42 1.343 20 3 20h14c1.657 0 3-1.58 3-3.53V3.53C20 1.58 18.657 0 17 0Z" fill="#F90"></path> <path d="M17 3H3a3 3 0 0 0-3 3v11a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3Z" fill="#E62E04"></path> <path d="M5.497 6.704c.076.315.033.649.108.967.263-.192.435-.51.402-.84-.006-.59-.67-1.054-1.227-.855a.965.965 0 0 0-.667.769c-.07.41.174.822.536 1.007-.035-.35-.12-.715-.026-1.06.144-.374.741-.367.874.011ZM15.853 7.128c.127-.46-.15-.99-.605-1.138a.947.947 0 0 0-1.172.473c-.215.395-.085.956.308 1.187.077-.316.02-.653.113-.964.146-.367.736-.357.87.015.085.333.008.684-.024 1.02.267-.083.433-.338.51-.593Z" fill="#B32100"></path> <path d="M14.496 6.686c-.092.311-.035.647-.113.964a4.473 4.473 0 0 1-2.396 3.22 4.436 4.436 0 0 1-3.999-.005 4.474 4.474 0 0 1-2.383-3.193c-.075-.319-.032-.653-.109-.968-.132-.379-.73-.386-.873-.013-.094.346-.01.712.026 1.062.263 1.7 1.4 3.226 2.941 3.984a5.431 5.431 0 0 0 4.574.108c1.666-.72 2.912-2.327 3.179-4.124.032-.337.11-.687.023-1.02-.134-.372-.723-.383-.87-.015ZM5.401 14.347c.228.044.41.214.437.45a.523.523 0 0 1 .439-.447.495.495 0 0 1-.438-.46.494.494 0 0 1-.438.457ZM3.585 14.212c-.29.783-.6 1.56-.892 2.342.1-.005.2-.008.298-.007.063-.19.139-.374.213-.56.36.002.722 0 1.082.002.071.186.146.371.212.56.099-.001.198 0 .297.003-.285-.747-.574-1.494-.857-2.243-.036-.161-.235-.082-.353-.097Zm.581 1.467c-.281.003-.562.004-.843 0 .138-.365.267-.734.424-1.09.145.361.282.726.42 1.09ZM4.969 16.553c.1-.003.2-.004.3-.003a364.34 364.34 0 0 1 0-2.338c-.1 0-.2 0-.3-.002v2.343ZM6.409 14.2v2.35c.493.004.987 0 1.48.001a3.648 3.648 0 0 1-.002-.31c-.39.002-.78.003-1.17 0a56.825 56.825 0 0 1 0-.729c.321-.002.642 0 .964 0-.001-.097 0-.194.002-.29a38.162 38.162 0 0 0-.963 0 22.382 22.382 0 0 1-.004-.717c.361-.003.722-.003 1.083 0-.003-.102 0-.206.008-.308-.466 0-.932-.005-1.398.003ZM9.823 15.556c-.039.623-.009 1.25-.015 1.874h.295c.002-.363 0-.726 0-1.089.34.33.922.342 1.238-.023.37-.371.296-1.055-.146-1.338-.494-.372-1.292-.038-1.372.576Zm1.43.354c-.103.39-.642.545-.937.27-.359-.29-.227-.957.235-1.055.446-.134.863.36.701.785ZM12.687 15.107c0-.097 0-.194.002-.29a1.04 1.04 0 0 0-.588.216l-.006-.165c-.098 0-.195 0-.293-.002-.003.563-.003 1.125 0 1.686h.296c.01-.316-.016-.634.014-.948.038-.284.304-.471.575-.497ZM14.32 15.24a.792.792 0 0 0-.87-.396.88.88 0 0 0-.7.872.9.9 0 0 0 .738.868.872.872 0 0 0 .824-.3 3.756 3.756 0 0 1-.216-.168c-.314.358-.938.201-1.052-.259.458-.004.914.003 1.371-.004.001-.206.011-.426-.094-.613Zm-1.28.33c.076-.248.297-.458.568-.453.262-.014.479.205.516.455a28.79 28.79 0 0 1-1.084-.002ZM15.415 16.285c-.23.122-.506.021-.673-.16-.071.06-.145.118-.22.175.272.295.739.396 1.094.191.276-.147.325-.592.07-.782-.222-.164-.528-.124-.753-.279-.127-.098-.023-.28.105-.315.199-.089.412.01.556.152.056-.057.194-.08.184-.171-.235-.328-.758-.36-1.05-.092-.18.166-.183.5.02.648.215.157.507.125.727.27.127.092.08.307-.06.363ZM16.879 16.28c-.232.13-.511.026-.685-.151a9.664 9.664 0 0 1-.214.16.881.881 0 0 0 1.103.194c.261-.15.306-.572.07-.764-.225-.18-.55-.13-.78-.3-.112-.107.005-.277.13-.31.197-.08.401.019.544.156.069-.052.14-.1.215-.145a.751.751 0 0 0-1.026-.162c-.223.146-.261.517-.044.685.21.164.5.135.724.269.135.083.104.307-.037.368ZM5.989 16.551c-.002-.557-.002-1.113 0-1.67-.1.002-.2.002-.3-.001v1.672c.1-.002.2-.003.3-.001ZM8.377 16.548c.154-.206.308-.412.474-.608.156.202.312.405.466.609.118 0 .235 0 .353.002-.208-.284-.428-.559-.641-.838.208-.284.431-.556.639-.84-.116 0-.236-.017-.349.013-.175.185-.307.406-.476.596-.137-.175-.268-.353-.405-.528-.09-.138-.276-.07-.412-.08.206.284.43.555.638.838-.214.28-.435.554-.64.839.118 0 .236-.002.353-.003Z" fill="#fff"></path></svg>AliExpress</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer"><svg background="#FFF" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="mr-2"><path d="M13.3846 4.90029C13.3846 4.87325 13.3852 4.84637 13.3865 4.81965L12.902 4.79541C12.9004 4.8302 12.8994 4.86517 12.8994 4.90029C12.8994 5.48008 13.1344 6.00514 13.5145 6.38524L13.858 6.04174C13.5658 5.74921 13.3846 5.34553 13.3846 4.90029Z" fill="#A8A8A8"></path> <path d="M14.9996 3.28527C15.0266 3.28527 15.0535 3.28626 15.0802 3.28758L15.1045 2.80293C15.0697 2.80128 15.0347 2.80029 14.9996 2.80029C14.4198 2.80029 13.8947 3.03528 13.5146 3.41537L13.8581 3.75887C14.1507 3.46633 14.5543 3.28527 14.9996 3.28527Z" fill="#A8A8A8"></path> <path d="M14.9994 6.51542C14.9723 6.51542 14.9455 6.51476 14.9187 6.51344L14.8945 6.99792C14.9293 6.99957 14.9642 7.00056 14.9994 7.00056C15.5792 7.00056 16.1042 6.76558 16.4844 6.38548L16.1408 6.04199C15.8483 6.33419 15.4446 6.51542 14.9994 6.51542Z" fill="#A8A8A8"></path> <path d="M15.9104 3.56709L16.3189 3.26614C15.9584 2.97476 15.4997 2.80029 15 2.80029V3.28543C15.3376 3.28527 15.6511 3.38949 15.9104 3.56709Z" fill="#A8A8A8"></path> <path d="M17.0658 4.5242C17.0879 4.64643 17.0994 4.77213 17.0994 4.90064C17.0994 5.49634 16.8515 6.03422 16.4533 6.41636L16.4084 6.45817L16.0824 6.09774C16.4089 5.80196 16.6144 5.37472 16.6144 4.90047L16.6142 4.87894L16.6138 4.85742L17.0658 4.5242ZM16.6726 3.60645C16.7724 3.73693 16.8568 3.87881 16.9241 4.02919L15.0118 5.4575L14.2129 4.94992V4.33942L15.0118 4.84552L16.6726 3.60645Z" fill="#A8A8A8"></path> <path d="M14.9996 6.51537C14.5255 6.51537 14.0984 6.30991 13.8027 5.9834L13.4424 6.30941C13.8266 6.73387 14.382 7.00051 14.9996 7.00051V6.51537Z" fill="#A8A8A8"></path> <path d="M13.9165 3.70309L13.5905 3.34277C13.1661 3.727 12.8994 4.28239 12.8994 4.89996H13.3846C13.3847 4.42569 13.59 3.99876 13.9165 3.70309Z" fill="#A8A8A8"></path> <path fill-rule="evenodd" clip-rule="evenodd" d="M1.0498 19.025L1.6498 11.4319C1.6498 11.4319 1.6998 10.6998 2.5998 10.6998H4.2998C4.2998 9.45452 4.9998 6.7998 8.14981 6.7998C11.2998 6.7998 12.3998 9.0998 12.3998 10.6998H13.8998C15.0998 10.6998 15.1498 11.4319 15.1498 11.4319L15.8998 19.9998H14.1498L13.5498 12.3498H12.5498V14.1498L10.9498 15.3498V12.3498H3.2998L2.5998 19.525L1.0498 19.025ZM8.14981 8.1998C5.9998 8.1998 5.66648 9.86647 5.69981 10.6998H10.9498C10.9498 9.7498 10.2998 8.1998 8.14981 8.1998Z" fill="#6C1EAD"></path> <path fill-rule="evenodd" clip-rule="evenodd" d="M1.0498 19.025L1.6498 11.4319C1.6498 11.4319 1.6998 10.6998 2.5998 10.6998H4.2998C4.2998 9.45452 4.9998 6.7998 8.14981 6.7998C11.2998 6.7998 12.3998 9.0998 12.3998 10.6998H13.8998C15.0998 10.6998 15.1498 11.4319 15.1498 11.4319L15.8998 19.9998H14.1498L13.5498 12.3498H12.5498V14.1498L10.9498 15.3498V12.3498H3.2998L2.5998 19.525L1.0498 19.025ZM8.14981 8.1998C5.9998 8.1998 5.66648 9.86647 5.69981 10.6998H10.9498C10.9498 9.7498 10.2998 8.1998 8.14981 8.1998Z" fill="url(#gradient_1_marketplaces)"></path> <path fill-rule="evenodd" clip-rule="evenodd" d="M1.0498 19.025L1.6498 11.4319C1.6498 11.4319 1.6998 10.6998 2.5998 10.6998H4.2998C4.2998 9.45452 4.9998 6.7998 8.14981 6.7998C11.2998 6.7998 12.3998 9.0998 12.3998 10.6998H13.8998C15.0998 10.6998 15.1498 11.4319 15.1498 11.4319L15.8998 19.9998H14.1498L13.5498 12.3498H12.5498V14.1498L10.9498 15.3498V12.3498H3.2998L2.5998 19.525L1.0498 19.025ZM8.14981 8.1998C5.9998 8.1998 5.66648 9.86647 5.69981 10.6998H10.9498C10.9498 9.7498 10.2998 8.1998 8.14981 8.1998Z" fill="url(#gradient_2_marketplaces)"></path> <path d="M17.0004 0.399902H3.00039C1.56445 0.399902 0.400391 1.56396 0.400391 2.9999V16.9999C0.400391 18.4358 1.56445 19.5999 3.00039 19.5999H17.0004C18.4363 19.5999 19.6004 18.4358 19.6004 16.9999V2.9999C19.6004 1.56396 18.4363 0.399902 17.0004 0.399902Z" stroke="#E5E9ED" stroke-width="0.8"></path> <defs><radialGradient id="gradient_1_marketplaces" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(15.4998 13.2998) rotate(86.8202) scale(9.01388 8.79892)"><stop offset="0.125" stop-color="#16B963"></stop> <stop offset="0.836291" stop-color="#19C279" stop-opacity="0"></stop></radialGradient> <radialGradient id="gradient_2_marketplaces" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(0.499809 11.2998) rotate(45) scale(8.48528 11.4919)"><stop offset="0.197917" stop-color="#F7AA03"></stop> <stop offset="1" stop-color="#FDA804" stop-opacity="0"></stop></radialGradient></defs></svg>СберМегаМаркет</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer"><svg width="20" height="20" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" question-handle="marketplaces" class="mr-2"><path d="M0 8C0 3.58172 3.58172 0 8 0H32C36.4183 0 40 3.58172 40 8V32C40 36.4183 36.4183 40 32 40H8C3.58172 40 0 36.4183 0 32V8Z" fill="#FED42B"></path> <path fill-rule="evenodd" clip-rule="evenodd" d="M9.88365 10.5949L0 23.5582L7.09295e-06 35L10.0089 21.7498L8.66606 31.2753L16.0609 33.8358L25.0134 19.5117C24.6195 22.2153 23.9392 28.4642 29.8478 30.2547C31.3246 30.6077 32.8669 30.5792 34.3296 30.172C35.7924 29.7648 37.5 29 40 26.5C40 26.5 39.986 18.3174 40 15.5C37.1173 19.833 33.9481 23.2538 32.4441 22.8599C29.8837 22.1974 32.1755 13.9969 33.6437 8.7686V8.62533L25.4611 5.81427L15.6312 21.7498L16.9741 13.0121L9.81202 10.5949H9.88365Z" fill="black"></path></svg>Яндекс.Маркет</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer"><svg width="20" height="20" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" question-handle="marketplaces" class="mr-2"><path d="M0 8C0 3.58172 3.58172 0 8 0L32 0C36.4183 0 40 3.58172 40 8L40 32C40 36.4183 36.4183 40 32 40L8 40C3.58172 40 0 36.4183 0 32L0 8Z" fill="#0077FF"></path> <path d="M21.2834 28.8167C12.1667 28.8167 6.96679 22.5667 6.75012 12.1667H11.3168C11.4668 19.8001 14.8334 23.0334 17.5 23.7001V12.1667H21.8002V18.7501C24.4335 18.4667 27.1999 15.4667 28.1332 12.1667H32.4333C31.7166 16.2334 28.7166 19.2334 26.5833 20.4667C28.7166 21.4667 32.1334 24.0834 33.4334 28.8167H28.7C27.6833 25.6501 25.1502 23.2001 21.8002 22.8667V28.8167H21.2834Z" fill="white"></path></svg>ВКонтакте</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer"><svg width="20" height="20" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" question-handle="marketplaces" class="mr-2"><path d="M0 8C0 3.58172 3.58172 0 8 0L32 0C36.4183 0 40 3.58172 40 8L40 32C40 36.4183 36.4183 40 32 40L8 40C3.58172 40 0 36.4183 0 32L0 8Z" fill="#FF8800"></path> <path fill-rule="evenodd" clip-rule="evenodd" d="M19.9823 20.5117C15.7002 20.5117 12.1439 16.9928 12.1439 12.8277C12.1439 8.51885 15.7002 5 19.9823 5C24.4095 5 27.8206 8.51885 27.8206 12.8277C27.8206 16.9928 24.4095 20.5117 19.9823 20.5117ZM19.9823 9.52423C18.1678 9.52423 16.7888 11.0323 16.7888 12.8277C16.7888 14.623 18.1678 15.9874 19.9823 15.9874C21.8693 15.9874 23.1757 14.623 23.1757 12.8277C23.1757 11.0323 21.8693 9.52423 19.9823 9.52423ZM23.1031 26.903L27.5303 31.1401C28.4012 32.0736 28.4012 33.4381 27.5303 34.2998C26.5868 35.2334 25.1352 35.2334 24.4095 34.2998L19.9823 29.991L15.7002 34.2998C15.2647 34.7307 14.6841 34.9462 14.0309 34.9462C13.5229 34.9462 12.9422 34.7307 12.4342 34.2998C11.5633 33.4381 11.5633 32.0736 12.4342 31.1401L16.934 26.903C15.2647 26.4004 13.7406 25.754 12.2891 24.8923C11.2004 24.3178 10.9827 22.8815 11.5633 21.8043C12.2891 20.7271 13.5955 20.4399 14.7567 21.158C17.9501 23.0969 22.0144 23.0969 25.2078 21.158C26.3691 20.4399 27.748 20.7271 28.4012 21.8043C29.0544 22.8815 28.7641 24.3178 27.748 24.8923C26.3691 25.754 24.7724 26.4004 23.1031 26.903Z" fill="white"></path></svg>Одноклассники</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer"><svg width="20" height="20" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" question-handle="marketplaces" class="mr-2"><path d="M0 8C0 3.58172 3.58172 0 8 0H32C36.4183 0 40 3.58172 40 8V32C40 36.4183 36.4183 40 32 40H8C3.58172 40 0 36.4183 0 32V8Z" fill="white"></path> <path d="M26.4312 34C30.6114 34 34.0001 30.6113 34.0001 26.4311C34.0001 22.2509 30.6114 18.8622 26.4312 18.8622C22.251 18.8622 18.8622 22.2509 18.8622 26.4311C18.8622 30.6113 22.251 34 26.4312 34Z" fill="#97CF26"></path> <path d="M12.2164 29.9434C14.1562 29.9434 15.7288 28.3709 15.7288 26.4311C15.7288 24.4912 14.1562 22.9187 12.2164 22.9187C10.2766 22.9187 8.70404 24.4912 8.70404 26.4311C8.70404 28.3709 10.2766 29.9434 12.2164 29.9434Z" fill="#A169F7"></path> <path d="M26.4304 17.0811C29.117 17.0811 31.295 14.9032 31.295 12.2165C31.295 9.52992 29.117 7.35199 26.4304 7.35199C23.7438 7.35199 21.5659 9.52992 21.5659 12.2165C21.5659 14.9032 23.7438 17.0811 26.4304 17.0811Z" fill="#FF6163"></path> <path d="M12.2167 18.4335C15.6501 18.4335 18.4335 15.6501 18.4335 12.2167C18.4335 8.78332 15.6501 6 12.2167 6C8.78332 6 6 8.78332 6 12.2167C6 15.6501 8.78332 18.4335 12.2167 18.4335Z" fill="#00AAFF"></path> <path d="M8 1H32V-1H8V1ZM39 8V32H41V8H39ZM32 39H8V41H32V39ZM1 32V8H-1V32H1ZM8 39C4.13401 39 1 35.866 1 32H-1C-1 36.9706 3.02944 41 8 41V39ZM39 32C39 35.866 35.866 39 32 39V41C36.9706 41 41 36.9706 41 32H39ZM32 1C35.866 1 39 4.13401 39 8H41C41 3.02944 36.9706 -1 32 -1V1ZM8 -1C3.02944 -1 -1 3.02944 -1 8H1C1 4.13401 4.13401 1 8 1V-1Z" fill="#F2F5F7"></path></svg>Авито</span></div>
    <div class="js-next" data-next=".js-quest-2">Далее</div>
  </div>

  <div class="js-question flag-js-show-sub-js-quest-2">
    <div class="js-question-title" data-title-toresp="CMS">На чём создан ваш сайт? </div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">Не знаю</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">Moguta.CMS</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">1С Bitrix</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">Tilda</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">WordPress(WooComerce)</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">OpenCart(ocStore)</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">Shop-Script(Webasyst)</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">Advantshop</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">Другое</span></div>  
    <div class="js-next" data-next=".js-quest-2">Далее</div>
  </div>

  <div class="js-question js-quest-2">
    <div class="js-question-title" data-title-toresp="Ниша" >Что вы будете продавать?</div>
    <div class="js-answer js-show-next"><span class="span-answer">Ещё не знаю</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Авто, мото</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Детские товары</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Еда и продукты</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Зоотовары</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Косметика, парфюмерия</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Оборудование для бизнеса</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Одежда, обувь и аксессуары</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Тренинги, виртуальные товары</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Подарки, украшения, цветы</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Спорт, туризм, охота, рыбалка</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Строительство и ремонт</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Товары для взрослых 18+</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Товары для офиса, канцелярия</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Услуги</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Электроника и бытовая техника</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Другое</span></div>
    <div class="js-next" data-next=".quest-3">Далее</div>
  </div>

  <div class="js-question quest-3">
    <div class="js-question-title" data-title-toresp="Трафик">Откуда планируете привлекать клиентов?</div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">Из поиска Яндекс (SEO)</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">Из социальных сетей</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">С маркетплейсов</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">С мессенджеров</span></div>
    <div class="js-answer multyselect js-show-next"><span class="span-answer">Не знаю</span></div>
    <div class="js-next" data-next=".quest-4">Далее</div>
  </div>

  <div class="js-question quest-4">
    <div class="js-question-title" data-title-toresp="Каталог">Сколько у вас будет товаров?</div>
    <div class="js-answer js-show-next"><span class="span-answer">до 1 000</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">до 50 000</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">более 50 000</span></div>
    <div class="js-next" data-next=".quest-5">Далее</div>
  </div>

  <div class="js-question quest-5">
    <div class="js-question-title" data-title-toresp="Домен">У вас есть свой домен?</div>
    <div class="js-answer js-show-next"><span class="span-answer">Да, есть</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Ещё нет</span></div>
    <div class="js-answer js-show-next"><span class="span-answer">Не знаю, что это такое</span></div>
    <div class="js-next js-question-end" >Далее</div>
  </div>

</div>

<script>
  $(document).ready(() => {
    $('.js-first-templates-popup-wrapper').hide();
  });
  $(".js-question-start").show();

  $('.js-start-quiz').on('click', '.js-answer', function() {
    $('.js-answer').removeClass('js-answer-unclick');
    if ($(this).hasClass('active') && $(this).hasClass('multyselect')) {
       $(this).removeClass('active');

       $(this).addClass('js-answer-unclick');
    }else{
       $(this).addClass('active');
    }

    if ($(this).hasClass('js-show-next')) {
      $(this).parents('.js-question').find('.next').show();
    }

    if (!$(this).hasClass('multyselect')) {
      $(this).parents('.js-question').find('.js-answer').removeClass('active');
      $(this).addClass('active');
    }


    if ($('.js-show-sub-quest-1').hasClass('active')) {

      if ($('.js-show-sub-quest-1').parents('.js-question').is(':visible')) {
        $(".flag-js-show-sub-quest-1").show();
      }
    } else {
      $(".flag-js-show-sub-quest-1").hide();
    }

    if ($('.js-show-sub-js-quest-2').hasClass('active')) {
      if ($('.js-show-sub-js-quest-2').parents('.js-question').is(':visible')) {
        $(".flag-js-show-sub-js-quest-2").show();
      }
    } else {
      $(".flag-js-show-sub-js-quest-2").hide();
    }

    if ($(this).parents('.js-question').find('.js-show-next').hasClass('active')) {
      $(this).parents('.js-question').find('.js-next').show();
    } else {
      $(this).parents('.js-question').find('.js-next').hide();
    }

  });

  $('.js-start-quiz').on('click', '.js-next', function() {
    $('.js-question').hide();
    $($(this).data('next')).show();
  });


  $('.js-start-quiz').on('click', '.js-question-end', function() {
    let result = {};
    let ya_target_quiz_1 = false;
    let ya_target_quiz_2 = false;
    $('.js-first-templates-popup-wrapper').show();
    $('.first-templates-popup__content').addClass('end-quiz');
    $('.js-answer.active').each(function() {

      let title = $(this).parents('.js-question').find('.js-question-title').text();
      let answer = $(this).text();
      let titleToresp = $(this).parents('.js-question').find('.js-question-title').data('title-toresp');
      //result += "\n " + title + ": " + answer;
      titleToresp = $.trim(titleToresp);
      answer = $.trim(answer);
      if (typeof(result[titleToresp]) !== 'undefined') {
        result[titleToresp].push(answer);
      } else {
        result[titleToresp] = [answer];
      }

      if(titleToresp=='Домен' && answer=='Да, есть'){
        //console.log('Есть домен! Цель1');     
        ym(86034605,'reachGoal','ya_target_quiz_1');
        ya_target_quiz_1 = true;
      }
      if(titleToresp=='Товары' && (answer=='Продаю, но не через интернет' || answer=='Продаю в соцсетях или на маркетплейсах' || answer=='Продаю через уже существующий сайт')){
        //console.log('Есть товары ! Цель2');     
        ym(86034605,'reachGoal','ya_target_quiz_2');
        ya_target_quiz_2 = true;
      }

      //console.log(result);        
    });

      if(ya_target_quiz_1 && ya_target_quiz_2)  {
       // console.log('Есть товары и домен ! Цель3');   
        ym(86034605,'reachGoal','ya_target_quiz_3');
      }

    $.ajax({
        type: "POST",
        url: "/mg-visor",
        data: {
            'type': 'quiz',
            'data': result,
        }
    });

    $('.first-templates-popup__container').hide();
    //console.log(result);
  });

</script>
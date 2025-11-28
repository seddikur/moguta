 
if(typeof locale == 'undefined'){locale={};}
$.extend(locale, {
    'cartRemove': 'Удалить',
    'fancyNext': 'Вперед',
    'fancyPrev': 'Назад',
    'countMsg1': 'Здравствуйте, меня интересует товар ',
    'countMsg2': ' с артикулом ',
    'countMsg3': " , но его нет в наличии. Сообщите, пожалуйста, о поступлении этого товара на склад.",
    'countInStock': 'Есть в наличии',
    'remaining': 'Остаток',
    'pcs': 'шт.',
    'paymentNone': 'нет доступных способов оплаты',
    'filterNone': 'Не нашлось подходящих товаров!',
    'delivery': '+ доставка: ',
    'waitCalc': 'Подождите, идет пересчет...',
    'checkout': 'Оформить заказ',
    'RecentlyViewed': 'Вы недавно смотрели',
    'MAX': 'Максимум',
    'productSearch': 'Поиск товаров...',
    'availibleVariants': 'Есть варианты',
    'ShowInVarious': 'Показывать в нескольких категориях',
    'deliverySum': 'доставка: ',
    'totalSum' : 'Общая стоимость: ',
}); 
// js переменные из движка
var mgBaseDir = '',
    mgNoImageStub = '',
    protocol = '',
    phoneMask = '',
    sessionToDB = '',
    sessionAutoUpdate = '',
    sessionLifeTime = 0,
    timeWithoutUser = 0,
    agreementClasses = '',
    langP = '',
    requiredFields = '',
    varHashProduct = '';

document.cookie.split(/; */).forEach(function (cookieraw) {
    if (cookieraw.indexOf('mg_to_script') === 0) {
        var cookie = cookieraw.split('=');
        var name = cookie[0].substr(13);
        var value = decodeURIComponent(cookie[1]);
        window[name] = tryJsonParse(value.replace(/&nbsp;/g, ' '));
    }
});

// продление пхп сессии
if (sessionLifeTime > 0 && window.sessionUpdateActive !== true) {
    window.sessionUpdateActive = true;
    setInterval(function () {
        let dataObj = {
            actionerClass: 'Ajaxuser',
            action: 'updateSession'
        };

        let data = Object.keys(dataObj).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(dataObj[k])
        }).join('&');

        const request = new XMLHttpRequest();

        request.open('POST', mgBaseDir + '/ajaxrequest', true);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

        request.addEventListener("readystatechange", () => {
            if (request.status < 200 && request.status >= 400) {
                console.log('Session update error!');
                console.log(request);
            }
        });

        request.send(data);

    }, (sessionLifeTime / 2 * 1000));
}


function tryJsonParse(str) {
    try {
        var res = JSON.parse(str);
        return res;
    } catch (e) {
        return str;
    }
} 
var InCartModule = (function() {
	return {
		pluginInCartText: 'В корзине',
		init: function() { 

			if (typeof locale != 'undefined' && locale.pluginInCartText) {
				InCartModule.pluginInCartText = locale.pluginInCartText;
			}

			var initialBuyButton = $('.product-wrapper:first .addToCart').text();
			if (typeof initialBuyButton === "undefined" || initialBuyButton === null || !initialBuyButton) {
				initialBuyButton = $('.property-form:first .addToCart').text();
			}

			$('.deleteItemFromCart').each(function(index,element) {
				$('.addToCart[data-item-id='+$(this).data('delete-item-id')+']').text(InCartModule.pluginInCartText).addClass('alreadyInCart');
			});

			$('body').on('click', '.addToCart', function() {
				$(this).text(InCartModule.pluginInCartText).addClass('alreadyInCart');
			});

			$('body').on('click', '.deleteItemFromCart', function() {
				$('.addToCart[data-item-id='+$(this).data('delete-item-id')+']').text(initialBuyButton).removeClass('alreadyInCart');
			});
		},
	};
})();
$(document).ready(function() {
	InCartModule.init();
}); 
/**
 * Owl Carousel v2.2.1
 * Copyright 2013-2017 David Deutsch
 * Licensed under  ()
 */
!function(a,b,c,d){function e(b,c){this.settings=null,this.options=a.extend({},e.Defaults,c),this.$element=a(b),this._handlers={},this._plugins={},this._supress={},this._current=null,this._speed=null,this._coordinates=[],this._breakpoint=null,this._width=null,this._items=[],this._clones=[],this._mergers=[],this._widths=[],this._invalidated={},this._pipe=[],this._drag={time:null,target:null,pointer:null,stage:{start:null,current:null},direction:null},this._states={current:{},tags:{initializing:["busy"],animating:["busy"],dragging:["interacting"]}},a.each(["onResize","onThrottledResize"],a.proxy(function(b,c){this._handlers[c]=a.proxy(this[c],this)},this)),a.each(e.Plugins,a.proxy(function(a,b){this._plugins[a.charAt(0).toLowerCase()+a.slice(1)]=new b(this)},this)),a.each(e.Workers,a.proxy(function(b,c){this._pipe.push({filter:c.filter,run:a.proxy(c.run,this)})},this)),this.setup(),this.initialize()}e.Defaults={items:3,loop:!1,center:!1,rewind:!1,mouseDrag:!0,touchDrag:!0,pullDrag:!0,freeDrag:!1,margin:0,stagePadding:0,merge:!1,mergeFit:!0,autoWidth:!1,startPosition:0,rtl:!1,smartSpeed:250,fluidSpeed:!1,dragEndSpeed:!1,responsive:{},responsiveRefreshRate:200,responsiveBaseElement:b,fallbackEasing:"swing",info:!1,nestedItemSelector:!1,itemElement:"div",stageElement:"div",refreshClass:"owl-refresh",loadedClass:"owl-loaded",loadingClass:"owl-loading",rtlClass:"owl-rtl",responsiveClass:"owl-responsive",dragClass:"owl-drag",itemClass:"owl-item",stageClass:"owl-stage",stageOuterClass:"owl-stage-outer",grabClass:"owl-grab"},e.Width={Default:"default",Inner:"inner",Outer:"outer"},e.Type={Event:"event",State:"state"},e.Plugins={},e.Workers=[{filter:["width","settings"],run:function(){this._width=this.$element.width()}},{filter:["width","items","settings"],run:function(a){a.current=this._items&&this._items[this.relative(this._current)]}},{filter:["items","settings"],run:function(){this.$stage.children(".cloned").remove()}},{filter:["width","items","settings"],run:function(a){var b=this.settings.margin||"",c=!this.settings.autoWidth,d=this.settings.rtl,e={width:"auto","margin-left":d?b:"","margin-right":d?"":b};!c&&this.$stage.children().css(e),a.css=e}},{filter:["width","items","settings"],run:function(a){var b=(this.width()/this.settings.items).toFixed(3)-this.settings.margin,c=null,d=this._items.length,e=!this.settings.autoWidth,f=[];for(a.items={merge:!1,width:b};d--;)c=this._mergers[d],c=this.settings.mergeFit&&Math.min(c,this.settings.items)||c,a.items.merge=c>1||a.items.merge,f[d]=e?b*c:this._items[d].width();this._widths=f}},{filter:["items","settings"],run:function(){var b=[],c=this._items,d=this.settings,e=Math.max(2*d.items,4),f=2*Math.ceil(c.length/2),g=d.loop&&c.length?d.rewind?e:Math.max(e,f):0,h="",i="";for(g/=2;g--;)b.push(this.normalize(b.length/2,!0)),h+=c[b[b.length-1]][0].outerHTML,b.push(this.normalize(c.length-1-(b.length-1)/2,!0)),i=c[b[b.length-1]][0].outerHTML+i;this._clones=b,a(h).addClass("cloned").appendTo(this.$stage),a(i).addClass("cloned").prependTo(this.$stage)}},{filter:["width","items","settings"],run:function(){for(var a=this.settings.rtl?1:-1,b=this._clones.length+this._items.length,c=-1,d=0,e=0,f=[];++c<b;)d=f[c-1]||0,e=this._widths[this.relative(c)]+this.settings.margin,f.push(d+e*a);this._coordinates=f}},{filter:["width","items","settings"],run:function(){var a=this.settings.stagePadding,b=this._coordinates,c={width:Math.ceil(Math.abs(b[b.length-1]))+2*a,"padding-left":a||"","padding-right":a||""};this.$stage.css(c)}},{filter:["width","items","settings"],run:function(a){var b=this._coordinates.length,c=!this.settings.autoWidth,d=this.$stage.children();if(c&&a.items.merge)for(;b--;)a.css.width=this._widths[this.relative(b)],d.eq(b).css(a.css);else c&&(a.css.width=a.items.width,d.css(a.css))}},{filter:["items"],run:function(){this._coordinates.length<1&&this.$stage.removeAttr("style")}},{filter:["width","items","settings"],run:function(a){a.current=a.current?this.$stage.children().index(a.current):0,a.current=Math.max(this.minimum(),Math.min(this.maximum(),a.current)),this.reset(a.current)}},{filter:["position"],run:function(){this.animate(this.coordinates(this._current))}},{filter:["width","position","items","settings"],run:function(){var a,b,c,d,e=this.settings.rtl?1:-1,f=2*this.settings.stagePadding,g=this.coordinates(this.current())+f,h=g+this.width()*e,i=[];for(c=0,d=this._coordinates.length;c<d;c++)a=this._coordinates[c-1]||0,b=Math.abs(this._coordinates[c])+f*e,(this.op(a,"<=",g)&&this.op(a,">",h)||this.op(b,"<",g)&&this.op(b,">",h))&&i.push(c);this.$stage.children(".active").removeClass("active"),this.$stage.children(":eq("+i.join("), :eq(")+")").addClass("active"),this.settings.center&&(this.$stage.children(".center").removeClass("center"),this.$stage.children().eq(this.current()).addClass("center"))}}],e.prototype.initialize=function(){if(this.enter("initializing"),this.trigger("initialize"),this.$element.toggleClass(this.settings.rtlClass,this.settings.rtl),this.settings.autoWidth&&!this.is("pre-loading")){var b,c,e;b=this.$element.find("img"),c=this.settings.nestedItemSelector?"."+this.settings.nestedItemSelector:d,e=this.$element.children(c).width(),b.length&&e<=0&&this.preloadAutoWidthImages(b)}this.$element.addClass(this.options.loadingClass),this.$stage=a("<"+this.settings.stageElement+' class="'+this.settings.stageClass+'"/>').wrap('<div class="'+this.settings.stageOuterClass+'"/>'),this.$element.append(this.$stage.parent()),this.replace(this.$element.children().not(this.$stage.parent())),this.$element.is(":visible")?this.refresh():this.invalidate("width"),this.$element.removeClass(this.options.loadingClass).addClass(this.options.loadedClass),this.registerEventHandlers(),this.leave("initializing"),this.trigger("initialized")},e.prototype.setup=function(){var b=this.viewport(),c=this.options.responsive,d=-1,e=null;c?(a.each(c,function(a){a<=b&&a>d&&(d=Number(a))}),e=a.extend({},this.options,c[d]),"function"==typeof e.stagePadding&&(e.stagePadding=e.stagePadding()),delete e.responsive,e.responsiveClass&&this.$element.attr("class",this.$element.attr("class").replace(new RegExp("("+this.options.responsiveClass+"-)\\S+\\s","g"),"$1"+d))):e=a.extend({},this.options),this.trigger("change",{property:{name:"settings",value:e}}),this._breakpoint=d,this.settings=e,this.invalidate("settings"),this.trigger("changed",{property:{name:"settings",value:this.settings}})},e.prototype.optionsLogic=function(){this.settings.autoWidth&&(this.settings.stagePadding=!1,this.settings.merge=!1)},e.prototype.prepare=function(b){var c=this.trigger("prepare",{content:b});return c.data||(c.data=a("<"+this.settings.itemElement+"/>").addClass(this.options.itemClass).append(b)),this.trigger("prepared",{content:c.data}),c.data},e.prototype.update=function(){for(var b=0,c=this._pipe.length,d=a.proxy(function(a){return this[a]},this._invalidated),e={};b<c;)(this._invalidated.all||a.grep(this._pipe[b].filter,d).length>0)&&this._pipe[b].run(e),b++;this._invalidated={},!this.is("valid")&&this.enter("valid")},e.prototype.width=function(a){switch(a=a||e.Width.Default){case e.Width.Inner:case e.Width.Outer:return this._width;default:return this._width-2*this.settings.stagePadding+this.settings.margin}},e.prototype.refresh=function(){this.enter("refreshing"),this.trigger("refresh"),this.setup(),this.optionsLogic(),this.$element.addClass(this.options.refreshClass),this.update(),this.$element.removeClass(this.options.refreshClass),this.leave("refreshing"),this.trigger("refreshed")},e.prototype.onThrottledResize=function(){b.clearTimeout(this.resizeTimer),this.resizeTimer=b.setTimeout(this._handlers.onResize,this.settings.responsiveRefreshRate)},e.prototype.onResize=function(){return!!this._items.length&&(this._width!==this.$element.width()&&(!!this.$element.is(":visible")&&(this.enter("resizing"),this.trigger("resize").isDefaultPrevented()?(this.leave("resizing"),!1):(this.invalidate("width"),this.refresh(),this.leave("resizing"),void this.trigger("resized")))))},e.prototype.registerEventHandlers=function(){a.support.transition&&this.$stage.on(a.support.transition.end+".owl.core",a.proxy(this.onTransitionEnd,this)),this.settings.responsive!==!1&&this.on(b,"resize",this._handlers.onThrottledResize),this.settings.mouseDrag&&(this.$element.addClass(this.options.dragClass),this.$stage.on("mousedown.owl.core",a.proxy(this.onDragStart,this)),this.$stage.on("dragstart.owl.core selectstart.owl.core",function(){return!1})),this.settings.touchDrag&&(this.$stage.on("touchstart.owl.core",a.proxy(this.onDragStart,this)),this.$stage.on("touchcancel.owl.core",a.proxy(this.onDragEnd,this)))},e.prototype.onDragStart=function(b){var d=null;3!==b.which&&(a.support.transform?(d=this.$stage.css("transform").replace(/.*\(|\)| /g,"").split(","),d={x:d[16===d.length?12:4],y:d[16===d.length?13:5]}):(d=this.$stage.position(),d={x:this.settings.rtl?d.left+this.$stage.width()-this.width()+this.settings.margin:d.left,y:d.top}),this.is("animating")&&(a.support.transform?this.animate(d.x):this.$stage.stop(),this.invalidate("position")),this.$element.toggleClass(this.options.grabClass,"mousedown"===b.type),this.speed(0),this._drag.time=(new Date).getTime(),this._drag.target=a(b.target),this._drag.stage.start=d,this._drag.stage.current=d,this._drag.pointer=this.pointer(b),a(c).on("mouseup.owl.core touchend.owl.core",a.proxy(this.onDragEnd,this)),a(c).one("mousemove.owl.core touchmove.owl.core",a.proxy(function(b){var d=this.difference(this._drag.pointer,this.pointer(b));a(c).on("mousemove.owl.core touchmove.owl.core",a.proxy(this.onDragMove,this)),Math.abs(d.x)<Math.abs(d.y)&&this.is("valid")||(b.preventDefault(),this.enter("dragging"),this.trigger("drag"))},this)))},e.prototype.onDragMove=function(a){var b=null,c=null,d=null,e=this.difference(this._drag.pointer,this.pointer(a)),f=this.difference(this._drag.stage.start,e);this.is("dragging")&&(a.preventDefault(),this.settings.loop?(b=this.coordinates(this.minimum()),c=this.coordinates(this.maximum()+1)-b,f.x=((f.x-b)%c+c)%c+b):(b=this.settings.rtl?this.coordinates(this.maximum()):this.coordinates(this.minimum()),c=this.settings.rtl?this.coordinates(this.minimum()):this.coordinates(this.maximum()),d=this.settings.pullDrag?-1*e.x/5:0,f.x=Math.max(Math.min(f.x,b+d),c+d)),this._drag.stage.current=f,this.animate(f.x))},e.prototype.onDragEnd=function(b){var d=this.difference(this._drag.pointer,this.pointer(b)),e=this._drag.stage.current,f=d.x>0^this.settings.rtl?"left":"right";a(c).off(".owl.core"),this.$element.removeClass(this.options.grabClass),(0!==d.x&&this.is("dragging")||!this.is("valid"))&&(this.speed(this.settings.dragEndSpeed||this.settings.smartSpeed),this.current(this.closest(e.x,0!==d.x?f:this._drag.direction)),this.invalidate("position"),this.update(),this._drag.direction=f,(Math.abs(d.x)>3||(new Date).getTime()-this._drag.time>300)&&this._drag.target.one("click.owl.core",function(){return!1})),this.is("dragging")&&(this.leave("dragging"),this.trigger("dragged"))},e.prototype.closest=function(b,c){var d=-1,e=30,f=this.width(),g=this.coordinates();return this.settings.freeDrag||a.each(g,a.proxy(function(a,h){return"left"===c&&b>h-e&&b<h+e?d=a:"right"===c&&b>h-f-e&&b<h-f+e?d=a+1:this.op(b,"<",h)&&this.op(b,">",g[a+1]||h-f)&&(d="left"===c?a+1:a),d===-1},this)),this.settings.loop||(this.op(b,">",g[this.minimum()])?d=b=this.minimum():this.op(b,"<",g[this.maximum()])&&(d=b=this.maximum())),d},e.prototype.animate=function(b){var c=this.speed()>0;this.is("animating")&&this.onTransitionEnd(),c&&(this.enter("animating"),this.trigger("translate")),a.support.transform3d&&a.support.transition?this.$stage.css({transform:"translate3d("+b+"px,0px,0px)",transition:this.speed()/1e3+"s"}):c?this.$stage.animate({left:b+"px"},this.speed(),this.settings.fallbackEasing,a.proxy(this.onTransitionEnd,this)):this.$stage.css({left:b+"px"})},e.prototype.is=function(a){return this._states.current[a]&&this._states.current[a]>0},e.prototype.current=function(a){if(a===d)return this._current;if(0===this._items.length)return d;if(a=this.normalize(a),this._current!==a){var b=this.trigger("change",{property:{name:"position",value:a}});b.data!==d&&(a=this.normalize(b.data)),this._current=a,this.invalidate("position"),this.trigger("changed",{property:{name:"position",value:this._current}})}return this._current},e.prototype.invalidate=function(b){return"string"===a.type(b)&&(this._invalidated[b]=!0,this.is("valid")&&this.leave("valid")),a.map(this._invalidated,function(a,b){return b})},e.prototype.reset=function(a){a=this.normalize(a),a!==d&&(this._speed=0,this._current=a,this.suppress(["translate","translated"]),this.animate(this.coordinates(a)),this.release(["translate","translated"]))},e.prototype.normalize=function(a,b){var c=this._items.length,e=b?0:this._clones.length;return!this.isNumeric(a)||c<1?a=d:(a<0||a>=c+e)&&(a=((a-e/2)%c+c)%c+e/2),a},e.prototype.relative=function(a){return a-=this._clones.length/2,this.normalize(a,!0)},e.prototype.maximum=function(a){var b,c,d,e=this.settings,f=this._coordinates.length;if(e.loop)f=this._clones.length/2+this._items.length-1;else if(e.autoWidth||e.merge){for(b=this._items.length,c=this._items[--b].width(),d=this.$element.width();b--&&(c+=this._items[b].width()+this.settings.margin,!(c>d)););f=b+1}else f=e.center?this._items.length-1:this._items.length-e.items;return a&&(f-=this._clones.length/2),Math.max(f,0)},e.prototype.minimum=function(a){return a?0:this._clones.length/2},e.prototype.items=function(a){return a===d?this._items.slice():(a=this.normalize(a,!0),this._items[a])},e.prototype.mergers=function(a){return a===d?this._mergers.slice():(a=this.normalize(a,!0),this._mergers[a])},e.prototype.clones=function(b){var c=this._clones.length/2,e=c+this._items.length,f=function(a){return a%2===0?e+a/2:c-(a+1)/2};return b===d?a.map(this._clones,function(a,b){return f(b)}):a.map(this._clones,function(a,c){return a===b?f(c):null})},e.prototype.speed=function(a){return a!==d&&(this._speed=a),this._speed},e.prototype.coordinates=function(b){var c,e=1,f=b-1;return b===d?a.map(this._coordinates,a.proxy(function(a,b){return this.coordinates(b)},this)):(this.settings.center?(this.settings.rtl&&(e=-1,f=b+1),c=this._coordinates[b],c+=(this.width()-c+(this._coordinates[f]||0))/2*e):c=this._coordinates[f]||0,c=Math.ceil(c))},e.prototype.duration=function(a,b,c){return 0===c?0:Math.min(Math.max(Math.abs(b-a),1),6)*Math.abs(c||this.settings.smartSpeed)},e.prototype.to=function(a,b){var c=this.current(),d=null,e=a-this.relative(c),f=(e>0)-(e<0),g=this._items.length,h=this.minimum(),i=this.maximum();this.settings.loop?(!this.settings.rewind&&Math.abs(e)>g/2&&(e+=f*-1*g),a=c+e,d=((a-h)%g+g)%g+h,d!==a&&d-e<=i&&d-e>0&&(c=d-e,a=d,this.reset(c))):this.settings.rewind?(i+=1,a=(a%i+i)%i):a=Math.max(h,Math.min(i,a)),this.speed(this.duration(c,a,b)),this.current(a),this.$element.is(":visible")&&this.update()},e.prototype.next=function(a){a=a||!1,this.to(this.relative(this.current())+1,a)},e.prototype.prev=function(a){a=a||!1,this.to(this.relative(this.current())-1,a)},e.prototype.onTransitionEnd=function(a){if(a!==d&&(a.stopPropagation(),(a.target||a.srcElement||a.originalTarget)!==this.$stage.get(0)))return!1;this.leave("animating"),this.trigger("translated")},e.prototype.viewport=function(){var d;return this.options.responsiveBaseElement!==b?d=a(this.options.responsiveBaseElement).width():b.innerWidth?d=b.innerWidth:c.documentElement&&c.documentElement.clientWidth?d=c.documentElement.clientWidth:console.warn("Can not detect viewport width."),d},e.prototype.replace=function(b){this.$stage.empty(),this._items=[],b&&(b=b instanceof jQuery?b:a(b)),this.settings.nestedItemSelector&&(b=b.find("."+this.settings.nestedItemSelector)),b.filter(function(){return 1===this.nodeType}).each(a.proxy(function(a,b){b=this.prepare(b),this.$stage.append(b),this._items.push(b),this._mergers.push(1*b.find("[data-merge]").addBack("[data-merge]").attr("data-merge")||1)},this)),this.reset(this.isNumeric(this.settings.startPosition)?this.settings.startPosition:0),this.invalidate("items")},e.prototype.add=function(b,c){var e=this.relative(this._current);c=c===d?this._items.length:this.normalize(c,!0),b=b instanceof jQuery?b:a(b),this.trigger("add",{content:b,position:c}),b=this.prepare(b),0===this._items.length||c===this._items.length?(0===this._items.length&&this.$stage.append(b),0!==this._items.length&&this._items[c-1].after(b),this._items.push(b),this._mergers.push(1*b.find("[data-merge]").addBack("[data-merge]").attr("data-merge")||1)):(this._items[c].before(b),this._items.splice(c,0,b),this._mergers.splice(c,0,1*b.find("[data-merge]").addBack("[data-merge]").attr("data-merge")||1)),this._items[e]&&this.reset(this._items[e].index()),this.invalidate("items"),this.trigger("added",{content:b,position:c})},e.prototype.remove=function(a){a=this.normalize(a,!0),a!==d&&(this.trigger("remove",{content:this._items[a],position:a}),this._items[a].remove(),this._items.splice(a,1),this._mergers.splice(a,1),this.invalidate("items"),this.trigger("removed",{content:null,position:a}))},e.prototype.preloadAutoWidthImages=function(b){b.each(a.proxy(function(b,c){this.enter("pre-loading"),c=a(c),a(new Image).one("load",a.proxy(function(a){c.attr("src",a.target.src),c.css("opacity",1),this.leave("pre-loading"),!this.is("pre-loading")&&!this.is("initializing")&&this.refresh()},this)).attr("src",c.attr("src")||c.attr("data-src")||c.attr("data-src-retina"))},this))},e.prototype.destroy=function(){this.$element.off(".owl.core"),this.$stage.off(".owl.core"),a(c).off(".owl.core"),this.settings.responsive!==!1&&(b.clearTimeout(this.resizeTimer),this.off(b,"resize",this._handlers.onThrottledResize));for(var d in this._plugins)this._plugins[d].destroy();this.$stage.children(".cloned").remove(),this.$stage.unwrap(),this.$stage.children().contents().unwrap(),this.$stage.children().unwrap(),this.$element.removeClass(this.options.refreshClass).removeClass(this.options.loadingClass).removeClass(this.options.loadedClass).removeClass(this.options.rtlClass).removeClass(this.options.dragClass).removeClass(this.options.grabClass).attr("class",this.$element.attr("class").replace(new RegExp(this.options.responsiveClass+"-\\S+\\s","g"),"")).removeData("owl.carousel")},e.prototype.op=function(a,b,c){var d=this.settings.rtl;switch(b){case"<":return d?a>c:a<c;case">":return d?a<c:a>c;case">=":return d?a<=c:a>=c;case"<=":return d?a>=c:a<=c}},e.prototype.on=function(a,b,c,d){a.addEventListener?a.addEventListener(b,c,d):a.attachEvent&&a.attachEvent("on"+b,c)},e.prototype.off=function(a,b,c,d){a.removeEventListener?a.removeEventListener(b,c,d):a.detachEvent&&a.detachEvent("on"+b,c)},e.prototype.trigger=function(b,c,d,f,g){var h={item:{count:this._items.length,index:this.current()}},i=a.camelCase(a.grep(["on",b,d],function(a){return a}).join("-").toLowerCase()),j=a.Event([b,"owl",d||"carousel"].join(".").toLowerCase(),a.extend({relatedTarget:this},h,c));return this._supress[b]||(a.each(this._plugins,function(a,b){b.onTrigger&&b.onTrigger(j)}),this.register({type:e.Type.Event,name:b}),this.$element.trigger(j),this.settings&&"function"==typeof this.settings[i]&&this.settings[i].call(this,j)),j},e.prototype.enter=function(b){a.each([b].concat(this._states.tags[b]||[]),a.proxy(function(a,b){this._states.current[b]===d&&(this._states.current[b]=0),this._states.current[b]++},this))},e.prototype.leave=function(b){a.each([b].concat(this._states.tags[b]||[]),a.proxy(function(a,b){this._states.current[b]--},this))},e.prototype.register=function(b){if(b.type===e.Type.Event){if(a.event.special[b.name]||(a.event.special[b.name]={}),!a.event.special[b.name].owl){var c=a.event.special[b.name]._default;a.event.special[b.name]._default=function(a){return!c||!c.apply||a.namespace&&a.namespace.indexOf("owl")!==-1?a.namespace&&a.namespace.indexOf("owl")>-1:c.apply(this,arguments)},a.event.special[b.name].owl=!0}}else b.type===e.Type.State&&(this._states.tags[b.name]?this._states.tags[b.name]=this._states.tags[b.name].concat(b.tags):this._states.tags[b.name]=b.tags,this._states.tags[b.name]=a.grep(this._states.tags[b.name],a.proxy(function(c,d){return a.inArray(c,this._states.tags[b.name])===d},this)))},e.prototype.suppress=function(b){a.each(b,a.proxy(function(a,b){this._supress[b]=!0},this))},e.prototype.release=function(b){a.each(b,a.proxy(function(a,b){delete this._supress[b]},this))},e.prototype.pointer=function(a){var c={x:null,y:null};return a=a.originalEvent||a||b.event,a=a.touches&&a.touches.length?a.touches[0]:a.changedTouches&&a.changedTouches.length?a.changedTouches[0]:a,a.pageX?(c.x=a.pageX,c.y=a.pageY):(c.x=a.clientX,c.y=a.clientY),c},e.prototype.isNumeric=function(a){return!isNaN(parseFloat(a))},e.prototype.difference=function(a,b){return{x:a.x-b.x,y:a.y-b.y}},a.fn.owlCarousel=function(b){var c=Array.prototype.slice.call(arguments,1);return this.each(function(){var d=a(this),f=d.data("owl.carousel");f||(f=new e(this,"object"==typeof b&&b),d.data("owl.carousel",f),a.each(["next","prev","to","destroy","refresh","replace","add","remove"],function(b,c){f.register({type:e.Type.Event,name:c}),f.$element.on(c+".owl.carousel.core",a.proxy(function(a){a.namespace&&a.relatedTarget!==this&&(this.suppress([c]),f[c].apply(this,[].slice.call(arguments,1)),this.release([c]))},f))})),"string"==typeof b&&"_"!==b.charAt(0)&&f[b].apply(f,c)})},a.fn.owlCarousel.Constructor=e}(window.Zepto||window.jQuery,window,document),function(a,b,c,d){var e=function(b){this._core=b,this._interval=null,this._visible=null,this._handlers={"initialized.owl.carousel":a.proxy(function(a){a.namespace&&this._core.settings.autoRefresh&&this.watch()},this)},this._core.options=a.extend({},e.Defaults,this._core.options),this._core.$element.on(this._handlers)};e.Defaults={autoRefresh:!0,autoRefreshInterval:500},e.prototype.watch=function(){this._interval||(this._visible=this._core.$element.is(":visible"),this._interval=b.setInterval(a.proxy(this.refresh,this),this._core.settings.autoRefreshInterval))},e.prototype.refresh=function(){this._core.$element.is(":visible")!==this._visible&&(this._visible=!this._visible,this._core.$element.toggleClass("owl-hidden",!this._visible),this._visible&&this._core.invalidate("width")&&this._core.refresh())},e.prototype.destroy=function(){var a,c;b.clearInterval(this._interval);for(a in this._handlers)this._core.$element.off(a,this._handlers[a]);for(c in Object.getOwnPropertyNames(this))"function"!=typeof this[c]&&(this[c]=null)},a.fn.owlCarousel.Constructor.Plugins.AutoRefresh=e}(window.Zepto||window.jQuery,window,document),function(a,b,c,d){var e=function(b){this._core=b,this._loaded=[],this._handlers={"initialized.owl.carousel change.owl.carousel resized.owl.carousel":a.proxy(function(b){if(b.namespace&&this._core.settings&&this._core.settings.lazyLoad&&(b.property&&"position"==b.property.name||"initialized"==b.type))for(var c=this._core.settings,e=c.center&&Math.ceil(c.items/2)||c.items,f=c.center&&e*-1||0,g=(b.property&&b.property.value!==d?b.property.value:this._core.current())+f,h=this._core.clones().length,i=a.proxy(function(a,b){this.load(b)},this);f++<e;)this.load(h/2+this._core.relative(g)),h&&a.each(this._core.clones(this._core.relative(g)),i),g++},this)},this._core.options=a.extend({},e.Defaults,this._core.options),this._core.$element.on(this._handlers)};e.Defaults={lazyLoad:!1},e.prototype.load=function(c){var d=this._core.$stage.children().eq(c),e=d&&d.find(".owl-lazy");!e||a.inArray(d.get(0),this._loaded)>-1||(e.each(a.proxy(function(c,d){var e,f=a(d),g=b.devicePixelRatio>1&&f.attr("data-src-retina")||f.attr("data-src");this._core.trigger("load",{element:f,url:g},"lazy"),f.is("img")?f.one("load.owl.lazy",a.proxy(function(){f.css("opacity",1),this._core.trigger("loaded",{element:f,url:g},"lazy")},this)).attr("src",g):(e=new Image,e.onload=a.proxy(function(){f.css({"background-image":'url("'+g+'")',opacity:"1"}),this._core.trigger("loaded",{element:f,url:g},"lazy")},this),e.src=g)},this)),this._loaded.push(d.get(0)))},e.prototype.destroy=function(){var a,b;for(a in this.handlers)this._core.$element.off(a,this.handlers[a]);for(b in Object.getOwnPropertyNames(this))"function"!=typeof this[b]&&(this[b]=null)},a.fn.owlCarousel.Constructor.Plugins.Lazy=e}(window.Zepto||window.jQuery,window,document),function(a,b,c,d){var e=function(b){this._core=b,this._handlers={"initialized.owl.carousel refreshed.owl.carousel":a.proxy(function(a){a.namespace&&this._core.settings.autoHeight&&this.update()},this),"changed.owl.carousel":a.proxy(function(a){a.namespace&&this._core.settings.autoHeight&&"position"==a.property.name&&this.update()},this),"loaded.owl.lazy":a.proxy(function(a){a.namespace&&this._core.settings.autoHeight&&a.element.closest("."+this._core.settings.itemClass).index()===this._core.current()&&this.update()},this)},this._core.options=a.extend({},e.Defaults,this._core.options),this._core.$element.on(this._handlers)};e.Defaults={autoHeight:!1,autoHeightClass:"owl-height"},e.prototype.update=function(){var b=this._core._current,c=b+this._core.settings.items,d=this._core.$stage.children().toArray().slice(b,c),e=[],f=0;a.each(d,function(b,c){e.push(a(c).height())}),f=Math.max.apply(null,e),this._core.$stage.parent().height(f).addClass(this._core.settings.autoHeightClass)},e.prototype.destroy=function(){var a,b;for(a in this._handlers)this._core.$element.off(a,this._handlers[a]);for(b in Object.getOwnPropertyNames(this))"function"!=typeof this[b]&&(this[b]=null)},a.fn.owlCarousel.Constructor.Plugins.AutoHeight=e}(window.Zepto||window.jQuery,window,document),function(a,b,c,d){var e=function(b){this._core=b,this._videos={},this._playing=null,this._handlers={"initialized.owl.carousel":a.proxy(function(a){a.namespace&&this._core.register({type:"state",name:"playing",tags:["interacting"]})},this),"resize.owl.carousel":a.proxy(function(a){a.namespace&&this._core.settings.video&&this.isInFullScreen()&&a.preventDefault()},this),"refreshed.owl.carousel":a.proxy(function(a){a.namespace&&this._core.is("resizing")&&this._core.$stage.find(".cloned .owl-video-frame").remove()},this),"changed.owl.carousel":a.proxy(function(a){a.namespace&&"position"===a.property.name&&this._playing&&this.stop()},this),"prepared.owl.carousel":a.proxy(function(b){if(b.namespace){var c=a(b.content).find(".owl-video");c.length&&(c.css("display","none"),this.fetch(c,a(b.content)))}},this)},this._core.options=a.extend({},e.Defaults,this._core.options),this._core.$element.on(this._handlers),this._core.$element.on("click.owl.video",".owl-video-play-icon",a.proxy(function(a){this.play(a)},this))};e.Defaults={video:!1,videoHeight:!1,videoWidth:!1},e.prototype.fetch=function(a,b){var c=function(){return a.attr("data-vimeo-id")?"vimeo":a.attr("data-vzaar-id")?"vzaar":"youtube"}(),d=a.attr("data-vimeo-id")||a.attr("data-youtube-id")||a.attr("data-vzaar-id"),e=a.attr("data-width")||this._core.settings.videoWidth,f=a.attr("data-height")||this._core.settings.videoHeight,g=a.attr("href");if(!g)throw new Error("Missing video URL.");if(d=g.match(/(http:|https:|)\/\/(player.|www.|app.)?(vimeo\.com|youtu(be\.com|\.be|be\.googleapis\.com)|vzaar\.com)\/(video\/|videos\/|embed\/|channels\/.+\/|groups\/.+\/|watch\?v=|v\/)?([A-Za-z0-9._%-]*)(\&\S+)?/),d[3].indexOf("youtu")>-1)c="youtube";else if(d[3].indexOf("vimeo")>-1)c="vimeo";else{if(!(d[3].indexOf("vzaar")>-1))throw new Error("Video URL not supported.");c="vzaar"}d=d[6],this._videos[g]={type:c,id:d,width:e,height:f},b.attr("data-video",g),this.thumbnail(a,this._videos[g])},e.prototype.thumbnail=function(b,c){var d,e,f,g=c.width&&c.height?'style="width:'+c.width+"px;height:"+c.height+'px;"':"",h=b.find("img"),i="src",j="",k=this._core.settings,l=function(a){e='<div class="owl-video-play-icon"></div>',d=k.lazyLoad?'<div class="owl-video-tn '+j+'" '+i+'="'+a+'"></div>':'<div class="owl-video-tn" style="opacity:1;background-image:url('+a+')"></div>',b.after(d),b.after(e)};if(b.wrap('<div class="owl-video-wrapper"'+g+"></div>"),this._core.settings.lazyLoad&&(i="data-src",j="owl-lazy"),h.length)return l(h.attr(i)),h.remove(),!1;"youtube"===c.type?(f="//img.youtube.com/vi/"+c.id+"/hqdefault.jpg",l(f)):"vimeo"===c.type?a.ajax({type:"GET",url:"//vimeo.com/api/v2/video/"+c.id+".json",jsonp:"callback",dataType:"jsonp",success:function(a){f=a[0].thumbnail_large,l(f)}}):"vzaar"===c.type&&a.ajax({type:"GET",url:"//vzaar.com/api/videos/"+c.id+".json",jsonp:"callback",dataType:"jsonp",success:function(a){f=a.framegrab_url,l(f)}})},e.prototype.stop=function(){this._core.trigger("stop",null,"video"),this._playing.find(".owl-video-frame").remove(),this._playing.removeClass("owl-video-playing"),this._playing=null,this._core.leave("playing"),this._core.trigger("stopped",null,"video")},e.prototype.play=function(b){var c,d=a(b.target),e=d.closest("."+this._core.settings.itemClass),f=this._videos[e.attr("data-video")],g=f.width||"100%",h=f.height||this._core.$stage.height();this._playing||(this._core.enter("playing"),this._core.trigger("play",null,"video"),e=this._core.items(this._core.relative(e.index())),this._core.reset(e.index()),"youtube"===f.type?c='<iframe width="'+g+'" height="'+h+'" src="//www.youtube.com/embed/'+f.id+"?autoplay=1&rel=0&v="+f.id+'" frameborder="0" allowfullscreen></iframe>':"vimeo"===f.type?c='<iframe src="//player.vimeo.com/video/'+f.id+'?autoplay=1" width="'+g+'" height="'+h+'" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>':"vzaar"===f.type&&(c='<iframe frameborder="0"height="'+h+'"width="'+g+'" allowfullscreen mozallowfullscreen webkitAllowFullScreen src="//view.vzaar.com/'+f.id+'/player?autoplay=true"></iframe>'),a('<div class="owl-video-frame">'+c+"</div>").insertAfter(e.find(".owl-video")),this._playing=e.addClass("owl-video-playing"))},e.prototype.isInFullScreen=function(){var b=c.fullscreenElement||c.mozFullScreenElement||c.webkitFullscreenElement;return b&&a(b).parent().hasClass("owl-video-frame")},e.prototype.destroy=function(){var a,b;this._core.$element.off("click.owl.video");for(a in this._handlers)this._core.$element.off(a,this._handlers[a]);for(b in Object.getOwnPropertyNames(this))"function"!=typeof this[b]&&(this[b]=null)},a.fn.owlCarousel.Constructor.Plugins.Video=e}(window.Zepto||window.jQuery,window,document),function(a,b,c,d){var e=function(b){this.core=b,this.core.options=a.extend({},e.Defaults,this.core.options),this.swapping=!0,this.previous=d,this.next=d,this.handlers={"change.owl.carousel":a.proxy(function(a){a.namespace&&"position"==a.property.name&&(this.previous=this.core.current(),this.next=a.property.value)},this),"drag.owl.carousel dragged.owl.carousel translated.owl.carousel":a.proxy(function(a){a.namespace&&(this.swapping="translated"==a.type)},this),"translate.owl.carousel":a.proxy(function(a){a.namespace&&this.swapping&&(this.core.options.animateOut||this.core.options.animateIn)&&this.swap()},this)},this.core.$element.on(this.handlers)};e.Defaults={animateOut:!1,animateIn:!1},e.prototype.swap=function(){if(1===this.core.settings.items&&a.support.animation&&a.support.transition){this.core.speed(0);var b,c=a.proxy(this.clear,this),d=this.core.$stage.children().eq(this.previous),e=this.core.$stage.children().eq(this.next),f=this.core.settings.animateIn,g=this.core.settings.animateOut;this.core.current()!==this.previous&&(g&&(b=this.core.coordinates(this.previous)-this.core.coordinates(this.next),d.one(a.support.animation.end,c).css({left:b+"px"}).addClass("animated owl-animated-out").addClass(g)),f&&e.one(a.support.animation.end,c).addClass("animated owl-animated-in").addClass(f))}},e.prototype.clear=function(b){a(b.target).css({left:""}).removeClass("animated owl-animated-out owl-animated-in").removeClass(this.core.settings.animateIn).removeClass(this.core.settings.animateOut),this.core.onTransitionEnd()},e.prototype.destroy=function(){var a,b;for(a in this.handlers)this.core.$element.off(a,this.handlers[a]);for(b in Object.getOwnPropertyNames(this))"function"!=typeof this[b]&&(this[b]=null)},
a.fn.owlCarousel.Constructor.Plugins.Animate=e}(window.Zepto||window.jQuery,window,document),function(a,b,c,d){var e=function(b){this._core=b,this._timeout=null,this._paused=!1,this._handlers={"changed.owl.carousel":a.proxy(function(a){a.namespace&&"settings"===a.property.name?this._core.settings.autoplay?this.play():this.stop():a.namespace&&"position"===a.property.name&&this._core.settings.autoplay&&this._setAutoPlayInterval()},this),"initialized.owl.carousel":a.proxy(function(a){a.namespace&&this._core.settings.autoplay&&this.play()},this),"play.owl.autoplay":a.proxy(function(a,b,c){a.namespace&&this.play(b,c)},this),"stop.owl.autoplay":a.proxy(function(a){a.namespace&&this.stop()},this),"mouseover.owl.autoplay":a.proxy(function(){this._core.settings.autoplayHoverPause&&this._core.is("rotating")&&this.pause()},this),"mouseleave.owl.autoplay":a.proxy(function(){this._core.settings.autoplayHoverPause&&this._core.is("rotating")&&this.play()},this),"touchstart.owl.core":a.proxy(function(){this._core.settings.autoplayHoverPause&&this._core.is("rotating")&&this.pause()},this),"touchend.owl.core":a.proxy(function(){this._core.settings.autoplayHoverPause&&this.play()},this)},this._core.$element.on(this._handlers),this._core.options=a.extend({},e.Defaults,this._core.options)};e.Defaults={autoplay:!1,autoplayTimeout:5e3,autoplayHoverPause:!1,autoplaySpeed:!1},e.prototype.play=function(a,b){this._paused=!1,this._core.is("rotating")||(this._core.enter("rotating"),this._setAutoPlayInterval())},e.prototype._getNextTimeout=function(d,e){return this._timeout&&b.clearTimeout(this._timeout),b.setTimeout(a.proxy(function(){this._paused||this._core.is("busy")||this._core.is("interacting")||c.hidden||this._core.next(e||this._core.settings.autoplaySpeed)},this),d||this._core.settings.autoplayTimeout)},e.prototype._setAutoPlayInterval=function(){this._timeout=this._getNextTimeout()},e.prototype.stop=function(){this._core.is("rotating")&&(b.clearTimeout(this._timeout),this._core.leave("rotating"))},e.prototype.pause=function(){this._core.is("rotating")&&(this._paused=!0)},e.prototype.destroy=function(){var a,b;this.stop();for(a in this._handlers)this._core.$element.off(a,this._handlers[a]);for(b in Object.getOwnPropertyNames(this))"function"!=typeof this[b]&&(this[b]=null)},a.fn.owlCarousel.Constructor.Plugins.autoplay=e}(window.Zepto||window.jQuery,window,document),function(a,b,c,d){"use strict";var e=function(b){this._core=b,this._initialized=!1,this._pages=[],this._controls={},this._templates=[],this.$element=this._core.$element,this._overrides={next:this._core.next,prev:this._core.prev,to:this._core.to},this._handlers={"prepared.owl.carousel":a.proxy(function(b){b.namespace&&this._core.settings.dotsData&&this._templates.push('<div class="'+this._core.settings.dotClass+'">'+a(b.content).find("[data-dot]").addBack("[data-dot]").attr("data-dot")+"</div>")},this),"added.owl.carousel":a.proxy(function(a){a.namespace&&this._core.settings.dotsData&&this._templates.splice(a.position,0,this._templates.pop())},this),"remove.owl.carousel":a.proxy(function(a){a.namespace&&this._core.settings.dotsData&&this._templates.splice(a.position,1)},this),"changed.owl.carousel":a.proxy(function(a){a.namespace&&"position"==a.property.name&&this.draw()},this),"initialized.owl.carousel":a.proxy(function(a){a.namespace&&!this._initialized&&(this._core.trigger("initialize",null,"navigation"),this.initialize(),this.update(),this.draw(),this._initialized=!0,this._core.trigger("initialized",null,"navigation"))},this),"refreshed.owl.carousel":a.proxy(function(a){a.namespace&&this._initialized&&(this._core.trigger("refresh",null,"navigation"),this.update(),this.draw(),this._core.trigger("refreshed",null,"navigation"))},this)},this._core.options=a.extend({},e.Defaults,this._core.options),this.$element.on(this._handlers)};e.Defaults={nav:!1,navText:["prev","next"],navSpeed:!1,navElement:"div",navContainer:!1,navContainerClass:"owl-nav",navClass:["owl-prev","owl-next"],slideBy:1,dotClass:"owl-dot",dotsClass:"owl-dots",dots:!0,dotsEach:!1,dotsData:!1,dotsSpeed:!1,dotsContainer:!1},e.prototype.initialize=function(){var b,c=this._core.settings;this._controls.$relative=(c.navContainer?a(c.navContainer):a("<div>").addClass(c.navContainerClass).appendTo(this.$element)).addClass("disabled"),this._controls.$previous=a("<"+c.navElement+">").addClass(c.navClass[0]).html(c.navText[0]).prependTo(this._controls.$relative).on("click",a.proxy(function(a){this.prev(c.navSpeed)},this)),this._controls.$next=a("<"+c.navElement+">").addClass(c.navClass[1]).html(c.navText[1]).appendTo(this._controls.$relative).on("click",a.proxy(function(a){this.next(c.navSpeed)},this)),c.dotsData||(this._templates=[a("<div>").addClass(c.dotClass).append(a("<span>")).prop("outerHTML")]),this._controls.$absolute=(c.dotsContainer?a(c.dotsContainer):a("<div>").addClass(c.dotsClass).appendTo(this.$element)).addClass("disabled"),this._controls.$absolute.on("click","div",a.proxy(function(b){var d=a(b.target).parent().is(this._controls.$absolute)?a(b.target).index():a(b.target).parent().index();b.preventDefault(),this.to(d,c.dotsSpeed)},this));for(b in this._overrides)this._core[b]=a.proxy(this[b],this)},e.prototype.destroy=function(){var a,b,c,d;for(a in this._handlers)this.$element.off(a,this._handlers[a]);for(b in this._controls)this._controls[b].remove();for(d in this.overides)this._core[d]=this._overrides[d];for(c in Object.getOwnPropertyNames(this))"function"!=typeof this[c]&&(this[c]=null)},e.prototype.update=function(){var a,b,c,d=this._core.clones().length/2,e=d+this._core.items().length,f=this._core.maximum(!0),g=this._core.settings,h=g.center||g.autoWidth||g.dotsData?1:g.dotsEach||g.items;if("page"!==g.slideBy&&(g.slideBy=Math.min(g.slideBy,g.items)),g.dots||"page"==g.slideBy)for(this._pages=[],a=d,b=0,c=0;a<e;a++){if(b>=h||0===b){if(this._pages.push({start:Math.min(f,a-d),end:a-d+h-1}),Math.min(f,a-d)===f)break;b=0,++c}b+=this._core.mergers(this._core.relative(a))}},e.prototype.draw=function(){var b,c=this._core.settings,d=this._core.items().length<=c.items,e=this._core.relative(this._core.current()),f=c.loop||c.rewind;this._controls.$relative.toggleClass("disabled",!c.nav||d),c.nav&&(this._controls.$previous.toggleClass("disabled",!f&&e<=this._core.minimum(!0)),this._controls.$next.toggleClass("disabled",!f&&e>=this._core.maximum(!0))),this._controls.$absolute.toggleClass("disabled",!c.dots||d),c.dots&&(b=this._pages.length-this._controls.$absolute.children().length,c.dotsData&&0!==b?this._controls.$absolute.html(this._templates.join("")):b>0?this._controls.$absolute.append(new Array(b+1).join(this._templates[0])):b<0&&this._controls.$absolute.children().slice(b).remove(),this._controls.$absolute.find(".active").removeClass("active"),this._controls.$absolute.children().eq(a.inArray(this.current(),this._pages)).addClass("active"))},e.prototype.onTrigger=function(b){var c=this._core.settings;b.page={index:a.inArray(this.current(),this._pages),count:this._pages.length,size:c&&(c.center||c.autoWidth||c.dotsData?1:c.dotsEach||c.items)}},e.prototype.current=function(){var b=this._core.relative(this._core.current());return a.grep(this._pages,a.proxy(function(a,c){return a.start<=b&&a.end>=b},this)).pop()},e.prototype.getPosition=function(b){var c,d,e=this._core.settings;return"page"==e.slideBy?(c=a.inArray(this.current(),this._pages),d=this._pages.length,b?++c:--c,c=this._pages[(c%d+d)%d].start):(c=this._core.relative(this._core.current()),d=this._core.items().length,b?c+=e.slideBy:c-=e.slideBy),c},e.prototype.next=function(b){a.proxy(this._overrides.to,this._core)(this.getPosition(!0),b)},e.prototype.prev=function(b){a.proxy(this._overrides.to,this._core)(this.getPosition(!1),b)},e.prototype.to=function(b,c,d){var e;!d&&this._pages.length?(e=this._pages.length,a.proxy(this._overrides.to,this._core)(this._pages[(b%e+e)%e].start,c)):a.proxy(this._overrides.to,this._core)(b,c)},a.fn.owlCarousel.Constructor.Plugins.Navigation=e}(window.Zepto||window.jQuery,window,document),function(a,b,c,d){"use strict";var e=function(c){this._core=c,this._hashes={},this.$element=this._core.$element,this._handlers={"initialized.owl.carousel":a.proxy(function(c){c.namespace&&"URLHash"===this._core.settings.startPosition&&a(b).trigger("hashchange.owl.navigation")},this),"prepared.owl.carousel":a.proxy(function(b){if(b.namespace){var c=a(b.content).find("[data-hash]").addBack("[data-hash]").attr("data-hash");if(!c)return;this._hashes[c]=b.content}},this),"changed.owl.carousel":a.proxy(function(c){if(c.namespace&&"position"===c.property.name){var d=this._core.items(this._core.relative(this._core.current())),e=a.map(this._hashes,function(a,b){return a===d?b:null}).join();if(!e||b.location.hash.slice(1)===e)return;b.location.hash=e}},this)},this._core.options=a.extend({},e.Defaults,this._core.options),this.$element.on(this._handlers),a(b).on("hashchange.owl.navigation",a.proxy(function(a){var c=b.location.hash.substring(1),e=this._core.$stage.children(),f=this._hashes[c]&&e.index(this._hashes[c]);f!==d&&f!==this._core.current()&&this._core.to(this._core.relative(f),!1,!0)},this))};e.Defaults={URLhashListener:!1},e.prototype.destroy=function(){var c,d;a(b).off("hashchange.owl.navigation");for(c in this._handlers)this._core.$element.off(c,this._handlers[c]);for(d in Object.getOwnPropertyNames(this))"function"!=typeof this[d]&&(this[d]=null)},a.fn.owlCarousel.Constructor.Plugins.Hash=e}(window.Zepto||window.jQuery,window,document),function(a,b,c,d){function e(b,c){var e=!1,f=b.charAt(0).toUpperCase()+b.slice(1);return a.each((b+" "+h.join(f+" ")+f).split(" "),function(a,b){if(g[b]!==d)return e=!c||b,!1}),e}function f(a){return e(a,!0)}var g=a("<support>").get(0).style,h="Webkit Moz O ms".split(" "),i={transition:{end:{WebkitTransition:"webkitTransitionEnd",MozTransition:"transitionend",OTransition:"oTransitionEnd",transition:"transitionend"}},animation:{end:{WebkitAnimation:"webkitAnimationEnd",MozAnimation:"animationend",OAnimation:"oAnimationEnd",animation:"animationend"}}},j={csstransforms:function(){return!!e("transform")},csstransforms3d:function(){return!!e("perspective")},csstransitions:function(){return!!e("transition")},cssanimations:function(){return!!e("animation")}};j.csstransitions()&&(a.support.transition=new String(f("transition")),a.support.transition.end=i.transition.end[a.support.transition]),j.cssanimations()&&(a.support.animation=new String(f("animation")),a.support.animation.end=i.animation.end[a.support.animation]),j.csstransforms()&&(a.support.transform=new String(f("transform")),a.support.transform3d=j.csstransforms3d())}(window.Zepto||window.jQuery,window,document); 
// с-carousel
// ------------------------------------------------------------
var carouselWrap = $('.js-catalog-top-carousel');

carouselWrap.owlCarousel({
    nav: true,
    margin: 16,
    dots: false,
    mouseDrag: false,
    responsive: {
        0: {
            items: 1,
            margin: 10,
        },
        360: {
            items: 2
        },
        768: {
            items: 3
        },
        990: {
            items: 4
        }
    },
    navText: [
        '<div class="c-carousel__arrow c-carousel__arrow--left"><svg class="icon icon--arrow-left"><use xlink:href="#icon--arrow-left"></use></svg></div>',
        '<div class="c-carousel__arrow c-carousel__arrow--right"><svg class="icon icon--arrow-right"><use xlink:href="#icon--arrow-right"></use></svg></div>'
    ]
});

// Если owlCarousel запустился, показываем карусель
if (carouselWrap.hasClass('owl-loaded')) {
    carouselWrap.parent().addClass('c-carousel--active');
} 
$(document).ready(function () {
    var sizeMapObject;
    

    // функция кликов по размерной сетке (страница товара и миникарточка)
    function sizeMapShow(id, search) {
        if (sizeMapObject == undefined) return false;

        var show = 'color';
        if (search == 'color') {
            show = 'size';
        }

        sizeMapObject.find('.' + show).hide();
        var toCheck = '';
        sizeMapObject.find('.variants-table .variant-tr').each(function () {
            if ($(this).data(search) == id) {
                if (sizeMapObject.find(this).data('size') != '') {
                    const dataIdAttribute = sizeMapObject.find(this).data(show)
                        ? '.' + show + '[data-id=' + sizeMapObject.find(this).data(show) + ']'
                        : '.' + show + '[data-id]';
                    sizeMapObject.find(dataIdAttribute).show();
                    if ($(this).data('count') == 0) {
                        sizeMapObject.find(dataIdAttribute).addClass('inactive');
                    } else {
                        sizeMapObject.find(dataIdAttribute).removeClass('inactive');
                    }
                    if (toCheck == '') {
                        toCheck = sizeMapObject.find(dataIdAttribute);
                    }
                }
            }
        });
        if (toCheck != '') {
            toCheck.click();
        }
    }

    // функция выбора варианта после клика по размерной сетке (страница товара и миникарточка)
    function choseVariant() {
        if (sizeMapObject == undefined) return false;
        var color = '';
        var size = '';
        if (sizeMapObject.find('.color').length != 0) {
            color = '[data-color=' + sizeMapObject.find('.color.active').data('id') + ']';
        }
        if (sizeMapObject.find('.size').length != 0) {
            size = '[data-size=' + sizeMapObject.find('.size.active').data('id') + ']';
        }
        sizeMapObject.find('.variants-table .variant-tr' + color + size + ' input[type=radio]').click();
    }


    $(document.body).on('change', '.block-variants input[type=radio]', function (e) {
        //подстановка картинки варианта вместо картинки товара (страница товара и миникарточка)
        //changeMainImgToVariant($(this));

        $(this).parents('tbody').find('tr label').removeClass('active');
        $(this).parents('tr').find('label').addClass('active');

        if (!$('.mg-product-slides').length) {
            var obj = $(this).parents('.js-catalog-item');
            var count = $(this).data('count');
            if (!obj.length) {
                obj = $(this).parents('.mg-compare-product');
            }

            var form = $(this).parents('form');

            if (form.hasClass('actionView')) {
                return false;
            }

            var buttonbuy = $(obj).find('.js-product-controls a:visible').hasClass('js-add-to-cart');

            if (count != '0' && !buttonbuy) {
                if ('false' == window.actionInCatalog) {
                    $(obj).find('.js-product-more').show();
                    $(obj).find('.js-add-to-cart').hide();
                } else {
                    $(obj).find('.js-product-more').hide();
                    $(obj).find('.js-add-to-cart').show();
                }
            } else if (count == '0' && buttonbuy == true) {
                $(obj).find('.js-product-more').show();
                $(obj).find('.js-add-to-cart').hide();
            }
        }
    });


    // делает активными нужные элементы размерной сетки при изменении варианта товара (страница товара и миникарточка)
    $(document.body).on('click', '.variants-table tr input[type=radio]', function () {
        sizeMapObject = $(this).closest('form');
        sizeMapObject.find('.color').removeClass('active');
        sizeMapObject.find('.size').removeClass('active');

        var tmp = $(this).closest('tr').data('color');
        if (tmp != undefined && tmp != '') {
            sizeMapObject.find('.color[data-id=' + $(this).closest('tr').data('color') + ']').addClass('active');
        }
        tmp = $(this).closest('tr').data('size');
        if (tmp != undefined && tmp != '') {
            sizeMapObject.find('.size[data-id=' + $(this).closest('tr').data('size') + ']').addClass('active');
        }
    });

    // обработчик кликов по размерной сетке (страница товара и миникарточка)
    $(document.body).on('click', '.color', function () {
        sizeMapObject = $(this).parents('form');
        if (typeof (sizeMapMod) != undefined && sizeMapMod !== 'size') {
            sizeMapObject.find('.color').removeClass('active');
            $(this).addClass('active');
            sizeMapShow($(this).data('id'), 'color');
            if (sizeMapObject.find('.size').length == 0) {
                choseVariant();
            }
        } else {
            sizeMapObject.find('.color').removeClass('active');
            $(this).addClass('active');
            choseVariant();
        }
    });

    $(document.body).on('click', '.size', function () {
        sizeMapObject = $(this).parents('form');
        if (typeof (sizeMapMod) != undefined && sizeMapMod === 'size') {
            sizeMapObject.find('.size').removeClass('active');
            $(this).addClass('active');
            sizeMapShow($(this).data('id'), 'size');
            if (sizeMapObject.find('.color').length == 0) {
                choseVariant();
            }
        } else {
            sizeMapObject.find('.size').removeClass('active');
            $(this).addClass('active');
            choseVariant();
        }
    });

    $('.variants-table').each(function () {
        var tmp = $(this).closest('form').attr('data-product-color');
        if (tmp == undefined || tmp == '') {
            tmp = $(this).find('tr:eq(0)').data('color');
        }
        if (tmp != undefined && tmp != '') {
            $(this).parents('form').find('.color[data-id=' + tmp + ']').addClass('active');
        } 
        var tmp = $(this).closest('form').attr('data-product-size');
        if (tmp == undefined || tmp == '') {
        tmp = $(this).find('tr:eq(0)').data('size');
        }
        if (tmp != undefined && tmp != '') {
            $(this).parents('form').find('.size[data-id=' + tmp + ']').addClass('active');
        }
    });

    $('.color-block .color.active').click();
    $('.size.active').click();
    // костыль для верстки чекбокса выбранного варианта в таблице вариантов товара без размерной сетки (страница товара и миникарточка)
    $('.variant__column input[name=variant][checked=checked]').each(function () {
        const form = $(this).closest('form'); 
        form.attr("data-product-variant")
        form.find('.variant__column input[name=variant][value="'+form.attr("data-product-variant")+'"]').parents('.c-form').addClass('active');
        //$(this).parents('.c-form').addClass('active');
    });

    // для выбора варианта по якорю
    if (varHashProduct === 'true' || varHashProduct === true) {
        if (location.hash != '') {
            code = location.hash.replace('#', '');
            code = decodeURI(code);
            if (typeof (sizeMapMod) != undefined && sizeMapMod == 'size' && $('[data-code="' + code + '"]:eq(0)').closest('tr[data-size!=\'\']').length) {
                size = $('[data-code="' + code + '"]:eq(0)').closest('tr').data('size');
                $('.size[data-id=' + size + ']').trigger('click');
            } else if ($('[data-code="' + code + '"]:eq(0)').closest('tr[data-color!=\'\']').length) {
                color = $('[data-code="' + code + '"]:eq(0)').closest('tr').data('color');
                $('.color[data-id=' + color + ']').trigger('click');
            }
            $('[data-code="' + code + '"]:eq(0)').click();
        }

        // подстановка якоря в url
        $(document.body).on('click', '.variants-table tr input[type=radio]', function () {
            data = $(this).data('code');
            if (data != undefined) location.hash = data;
        });
    }

});


// Функция получает изображение варианта и подменяет его в карусели
function changeMainImgToVariant(response, page) {
    var secondarySlider = $('.js-secondary-img-slider');
    var activeLink = $('.js-main-img-slider .active .js-images-link');
    var firstSlide = secondarySlider.find('[data-slide-index=0]');
    // Если пришла не заглушка, то продолжаем
    if (response.data.image_orig !== '' && !response.data.image_orig.includes('no-img.jpg')) {
        // Если это миникарточка
        if (page.hasClass('js-catalog-item')) {
            // Находим изображение товара в миникарточке
            var itemImg = page.find('.js-catalog-item-image');
            // Заменяем его
            changeImgSrc(
                itemImg,
                '30',
                response.data.image_thumbs,
            );
        }

        // Если это страница товара
        else {
            // Если у товара не одно изображение
            if (secondarySlider.length) {
                // Открываем первый слайд
                firstSlide.click();

                // меняем первое изображение товара в карусели миниатюр
                changeImgSrc(
                    firstSlide.find('.js-img-preview'),
                    '30',
                    response.data.image_thumbs,
                );

                // Меняем первое изображение товара в основном слайдере
                activeLink = $('.js-main-img-slider .active .js-images-link');
                changeImgSrc(
                    activeLink.find('.js-product-img'),
                    '70',
                    response.data.image_thumbs,
                );

            } else {
                // Меняем единственное изображение товара
                changeImgSrc(
                    $('.js-product-img'),
                    '70',
                    response.data.image_thumbs,
                );
            }

            // Меняем изображение в ссылке на fancybox
            activeLink.attr('href', response.data.image_orig);

            // Меняем изображение показыващееся при наведении на основное (лупа)
            activeLink = $('.js-main-img-slider .active .js-images-link');
            activeLink.find('.js-zoom-img').attr('style', 'background-image: url("' + response.data.image_orig + '")');
        }
    }
    //Смена остатков по складам если они выводятся
    if (typeof (response.data.storage) != 'undefined') {
        for (const key in response.data.storage) {
            const countOnStorageElement = document.querySelector('.a-storage .count-on-storage[data-id="' + key + '"]');
            if (countOnStorageElement) {
                countOnStorageElement.innerHTML = response.data.storage[key];
            }
        }
    }
}

// Минифункция меняющая src и srcset у тега img
function changeImgSrc(imgElem, size, thumbsArr) {
    if (thumbsArr[size]) {
        imgElem.attr('src', thumbsArr[size]);
    }
    if (thumbsArr['2x' + size]) {
        imgElem.attr('srcset', thumbsArr['2x' + size] + ' 2x');
    }
}
 
//пересчет цены товара аяксом (страница товара, миникарточка)
$(document.body).on("change", ".js-onchange-price-recalc", function () {
  var form = ".js-product-form";
  var request = $(form).formSerialize();
  var productId = $(form).data("product-id");

  var priceBlock = ".js-change-product-price";
  var productList = $(".js-product-page");
  var miniProduct = ".js-catalog-item";

  if ($(this).parents(miniProduct).length) {
    // для вызова из каталога
    productList = $(this).parents(miniProduct);
    form = productList.find(form);
    productId = form.data("product-id");
    request = productList.find(form).formSerialize();
    priceBlock = productList.find(priceBlock);
  }

  if ($(this).parents(".mg-compare-product").length) {
    // для вызова из сравнений
    priceBlock = $(this).parents(".mg-compare-product").find(priceBlock);
    request = $(this)
      .parents(".mg-compare-product")
      .find(".property-form")
      .formSerialize();
    request += "&remInfo=false";
    productList = $(this).parents(".mg-compare-product");
  }

  // для вызова из карточки товара на странице товара
  if ($(this).parents(".js-product-page")) {
    priceBlock = productList.find(priceBlock);
  }

  var tempThis = $(this);

  // Пересчет цены
  $.ajax({
    type: "POST",
    url: mgBaseDir + "/product/",
    data: "calcPrice=1&inCartProductId=" + productId + "&" + request,
    dataType: "json",
    cache: false,
    success: function (response) {
      // функция подстановки картинки варианта вместо картинки товара (на странице товара или миникарточке)
      if(tempThis.parents('.block-variants').length) {
        changeMainImgToVariant(response, productList);
      }

      if (response.data.wholesalesTable != undefined) {
        $(".wholesales-data").html(response.data.wholesalesTable);
      }

      if (response.data.productOpFields != undefined) {
        tempThis
          .parents(".property-form")
          .parents(".product-details-block,.product-wrapper")
          .find(".product-opfields-data")
          .html(response.data.productOpFields);
      }

      window.actionInCatalog = response.data.actionInCatalog;

      productList.find(".rem-info").hide();

      productList.find(".buy-container.product .hidder-element").hide();
      if (productList.find(".buy-block .count").length > 0 || response.data.count == 0) {
        productList.find(".js-product-controls").hide();
        productList.find(".c-product__message").show();
      }

      if (response.status === "success") {
        $('.c-button[rel="nofollow"]').attr(
          "href",
          response.data.buttonMessage
        );
        if ($(priceBlock).find(".product-default-price").length) {
          $(priceBlock)
            .find(".product-default-price")
            .html(response.data.price);
        } else {
          $(priceBlock).html(response.data.price);
        }         
        $(priceBlock).find(".product-default-price").html(response.data.price);
        productList.find(".code").text(response.data.code);
        var message = "";

        if (response.data.title) {
          message =
            locale.countMsg1 +
            response.data.title.replace("'", '"') +
            locale.countMsg2 +
            response.data.code +
            locale.countMsg3;
        }

        productList
          .find(".rem-info a")
          .attr("href", mgBaseDir + "/feedback?message=" + message);
        productList.find(".code-msg").text(response.data.code);

        var val = response.data.count;

        if (val != 0) {
          $(".depletedLanding").hide();
          $(".addToOrderLanding").show();

          productList.find(".rem-info").hide();
          productList.find(".js-product-controls").show();
          if (productList.find(".buy-block .count").length > 0) {
            productList.find(".js-product-controls").show();
            productList.find(".c-product__message").hide();
          }
          productList.find(".buy-container.product").show();
          if (
            !productList
              .find(".js-product-controls a:visible")
              .hasClass("js-add-to-cart")
          ) {
            if ("false" == window.actionInCatalog) {
              if ($('.js-product-page').length != 0) {
                productList.find(".js-product-more").hide();
                productList.find(".js-add-to-cart").show();
              } else {
                productList.find(".js-product-more").show();
                productList.find(".js-add-to-cart").hide();
              }
            } else {
              productList.find(".js-product-more").hide();
              productList.find(".js-add-to-cart").show();
            }

            productList.find(".js-product-controls").show();
          }
        } else {
          $(".depletedLanding").show();
          $(".addToOrderLanding").hide();
          productList.find(".js-product-controls").show();
          productList.find(".rem-info").show();
          if (productList.find(".buy-block .count").length > 0) {
            //$('.js-product-controls').hide();
          }
          productList.find(".buy-container.product").hide();
          if (
            productList
              .find(".js-product-controls a:visible")
              .hasClass("js-add-to-cart")
          ) {
            productList.find(".js-product-more").show();
            productList.find(".js-add-to-cart").hide();
            // productList.find('.js-product-controls:first').hide();
          }
        }
        if (response.data.count_layout) {
          if (productList.find(".count").length > 0) {
            productList
              .find(".count")
              .parent()
              .html(response.data.count_layout);
          } else {
            productList
              .find(".in-stock")
              .parent()
              .html(response.data.count_layout);
          }
        } else {
          if (val == "\u221E" || val == "" || parseFloat(val) < 0) {
            val =
              '<span itemprop="availability" class="count"><span class="sign">&#10004;</span>' +
              locale.countInStock +
              "</span>";
            productList.find(".rem-info").hide();
          } else {
            val =
              locale.remaining +
              ': <span itemprop="availability" class="label-black count">' +
              val +
              "</span> " +
              locale.pcs;
          }
          productList.find(".count").parent().html(val);
        }

        val = response.data.old_price;
        
        const oldPrice = parseFloat(response.data.old_price.split(' ').join(''));
        const currentPrice = parseFloat(response.data.price.split(' ').join(''))
        if (oldPrice > currentPrice) {
            productList.find('.js-discount-sticker').show()
            let sale = Math.round((oldPrice - currentPrice) / (oldPrice / 100));
            sale = '-'+ sale + ' %';
            productList.find('.js-discount-sticker').html(sale);
            productList.find('.old-price').text(response.data.old_price);
            productList.find('.old-price').show();
            productList.find('.js-old-price-container').show();
        }
        else {
            productList.find('.js-discount-sticker').hide()
            productList.find('.old-price').text('');
            productList.find('.old-price').hide();
            productList.find('.js-old-price-container').hide();
        }

        productList
          .find(".amount_input")
          .data("max-count", response.data.count);

        productList.find(".weight").text(response.data.weightCalc);

        if (
          parseFloat(productList.find(".amount_input").val()) >
          parseFloat(response.data.count)
        ) {
          val = response.data.count;
          if (val == "\u221E" || val == "" || parseFloat(val) < 0) {
            val = productList.find(".amount_input").val();
          }
          if (val == 0) {
            val = 1;
          }

          productList.find(".amount_input").val(val);
        }
      }

      if (
        response.data.storage != undefined &&
        response.data.storage.length > 0
      ) {
        maxStorageCount = 0;
        for (var i in response.data.storage) {
          $(".count-on-storage[data-id=" + i + "]").html(
            response.data.storage[i]
          );
          if (response.data.storage[i] > maxStorageCount)
            maxStorageCount = response.data.storage[i];
        }
        productList.find(".actionBuy .amount_input").data("max-count", maxStorageCount);
      }
    },
  });

  return false;
});

 
$(document).ready(function() {
    // добавление в избранное
    var counter = $('.js-favourite-count'),
        informer = $('.js-favorites-informer'),
        informerOpenedClass = 'favourite--open',
        btnAddClass = '.js-add-to-favorites',
        btnRemoveClass = '.js-remove-to-favorites';


    $('body').on('click', btnAddClass, function () {
        obj = $(this);
        $.ajax({
            type: "POST",
            url: mgBaseDir + "/favorites/",
            data: {'addFav': '1', 'id': $(this).data('item-id')},
            dataType: "json",
            cache: false,
            success: function (response) {
                obj.hide();
                obj.parent().find(btnRemoveClass).show();
                counter.show();
                counter.html(response);
                informer.fadeOut('normal').fadeIn('normal');
                informer.removeClass(informerOpenedClass);

                setTimeout(function () {
                    informer.addClass(informerOpenedClass);
                }, 0);
            }
        });
    });

// удаление из избранного
    $('body').on('click', btnRemoveClass, function () {
        obj = $(this);
        $.ajax({
            type: "POST",
            url: mgBaseDir + "/favorites/",
            data: {'delFav': '1', 'id': $(this).data('item-id')},
            dataType: "json",
            cache: false,
            success: function (response) {
                obj.hide();
                informer.fadeOut('normal').fadeIn('normal');
                obj.parent().find(btnAddClass).show();
                counter.html(response);
            }
        });
    });
});


 
var smallCartTemplate = document.querySelector(".smallCartRowTemplate");
smallCartTemplate = smallCartTemplate ? smallCartTemplate.content.querySelector("tr") : '';
if (popup = document.querySelector(".popupCartRowTemplate")) {
  var popUpTemplate = popup.content.querySelector("tr");
}

// Заполнение корзины аяксом
$("body").on("click", ".js-add-to-cart", function (e) {
  var productId = $(this).data("item-id");
  transferEffect(productId, $(this), ".js-catalog-item");

  var request =
    "inCartProductId=" + $(this).data("item-id") + "&amount_input=1";
  if ($(this).parents(".js-product-form").length) {
    request = $(this).parents(".js-product-form").formSerialize();
    if (!$(".js-amount-wrap").length) {
      request += "&amount_input=1";
    }
  }

  $.ajax({
    type: "POST",
    url: mgBaseDir + "/cart",
    data: "updateCart=1&inCartProductId=" + productId + "&" + request,
    dataType: "json",
    cache: false,
    success: function (response) {
      if (popup) {
        $("#js-modal__cart").addClass("c-modal--open");
        $("html").addClass("c-modal--scroll");

        if ($("#c-modal__cart").length > 0) {
          $("#c-modal__cart").addClass("c-modal--open");
          if ($(document).height() > $(window).height()) {
            $("html").addClass("c-modal--scroll");
          }
        }
      }
      
      if ("success" == response.status) {
        dataSmalCart = "";
        dataPopupCart = "";
        response.data.dataCart.forEach(printSmalCartData);

        $(".mg-desktop-cart .small-cart-table").html(dataSmalCart);

        if ($(".js-popup-cart-table").length) {
          $(".js-popup-cart-table").html(dataPopupCart);
        }
        $(".total .total-sum span.total-payment").text(response.data.cart_price_wc);
        $(".pricesht").text(response.data.cart_price);
        let cartCount = Number(response.data.cart_count).toFixed(2) * 100 % 100 > 0 ? Number(response.data.cart_count).toFixed(2).replace('.', ',') : response.data.cart_count;
        $(".countsht").text(cartCount);
        $(".small-cart").show();
      }
    },
  });

  return false;
});

// строит содержимое маленькой и всплывающей корзины в выпадащем блоке
function printSmalCartData(element, index, array) {
  var html = $($.parseHTML("<table><tbody></tbody></table>"));
  html.find("tbody").html(smallCartTemplate.cloneNode(true));
  html
    .find(".js-smallCartImg")
    .attr("src", element.image_thumbs[30])
    .attr("alt", element.title)
    .attr("srcset", element.image_thumbs["2x30"] + " 2x");

  var prodUrl =
    mgBaseDir +
    "/" +
    (element.category_url || element.category_url == ""
      ? element.category_url
      : "catalog/") +
    element.product_url;
  html.find(".js-smallCartImgAnchor").attr("href", prodUrl);
  html
    .find(".js-smallCartProdAnchor")
    .attr("href", prodUrl)
    .text(element.title);

  html.find(".js-smallCartProperty").html(element.property_html);
  let cartCount = Number(element.countInCart).toFixed(2) * 100 % 100 > 0 ? Number(element.countInCart).toFixed(2).replace('.', ',') : element.countInCart;
  html.find(".js-smallCartAmount").text(cartCount);
  html.find(".js-cartPrice").text(element.priceInCart);

  html
    .find(".js-delete-from-cart")
    .attr("data-delete-item-id", element.id)
    .attr("data-property", element.property)
    .attr("data-variant", element.variantId);

  window.dataSmalCart += html.find("tr:first").parent().html();

  if ($(".popup-body .small-cart-table").length) {
    html = $(
      $.parseHTML(
        "<table><tbody></tbody></table>"
      )
    );

    html.find("tbody").html(smallCartTemplate.cloneNode(true));

    html.find(".js-smallCartImgAnchor").attr("href", prodUrl);
    html
      .find(".js-smallCartProdAnchor")
      .attr("href", prodUrl)
      .text(element.title);

    html
      .find(".js-smallCartImg")
      .attr("src", element.image_thumbs[30])
      .attr("alt", element.title)
      .attr("srcset", element.image_thumbs["2x30"] + " 2x");

    html.find(".js-smallCartProperty").html(element.property_html);
    let cartCount = Number(element.countInCart).toFixed(2) * 100 % 100 > 0 ? Number(element.countInCart).toFixed(2).replace('.', ',') : element.countInCart;
    html.find(".js-smallCartAmount").text(cartCount);
    html.find(".js-cartPrice").text(element.priceInCart);

    html
      .find(".js-delete-from-cart")
      .attr("data-delete-item-id", element.id)
      .attr("data-property", element.property)
      .attr("data-variant", element.variantId);

    dataPopupCart += html.find("tr:first").parent().html();
  }
}

// Эффект полёта товара в корзину
function transferEffect(productId, buttonClick, wrapperClass) {
  var $css = {
    height: "100%",
    opacity: 0.5,
    position: "relative",
    "z-index": 100,
  };

  var $transfer = {
    to: $(".small-cart-icon"),
    className: "transfer_class",
  };

  //если кнопка на которую нажали находится внутри нужного контейнера.
  if (
    buttonClick
      .parents(wrapperClass)
      .find("img[data-transfer=true][data-product-id=" + productId + "]").length
  ) {
    // даем способность летать для картинок из слайдера новинок и прочих.
    var tempObj = buttonClick
      .parents(wrapperClass)
      .find("img[data-transfer=true][data-product-id=" + productId + "]");
    tempObj.effect("transfer", $transfer, 600);
    $(".transfer_class").html(tempObj.clone().css($css));
  } else {
    //Если кнопка находится не в контейнере, проверяем находится ли она на странице карточки товара.
    if ($(".product-details-image").length) {
      // даем способность летать для картинок из галереи в карточке товара.
      $(".product-details-image").each(function () {
        if ($(this).css("display") != "none") {
          $(this).find(".mg-product-image").effect("transfer", $transfer, 600);
          $(".transfer_class").html($(this).find("img").clone().css($css));
        }
      });
    } else {
      // даем способность летать для всех картинок.
      var tempObj = $(
        "img[data-transfer=true][data-product-id=" + productId + "]"
      );
      tempObj.effect("transfer", $transfer, 600);
    }
  }

  if (tempObj) {
    $(".transfer_class").html(tempObj.clone().css($css));
  }
}
 
/*!
 * css-vars-ponyfill
 * v2.1.2
 * https://jhildenbiddle.github.io/css-vars-ponyfill/
 * (c) 2018-2019 John Hildenbiddle <http://hildenbiddle.com>
 * MIT license
 */
!function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):(e=e||self).cssVars=t()}(this,function(){"use strict";function e(){return(e=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var r=arguments[t];for(var n in r)Object.prototype.hasOwnProperty.call(r,n)&&(e[n]=r[n])}return e}).apply(this,arguments)}function t(e){return function(e){if(Array.isArray(e)){for(var t=0,r=new Array(e.length);t<e.length;t++)r[t]=e[t];return r}}(e)||function(e){if(Symbol.iterator in Object(e)||"[object Arguments]"===Object.prototype.toString.call(e))return Array.from(e)}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance")}()}function r(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{},r={mimeType:t.mimeType||null,onBeforeSend:t.onBeforeSend||Function.prototype,onSuccess:t.onSuccess||Function.prototype,onError:t.onError||Function.prototype,onComplete:t.onComplete||Function.prototype},n=Array.isArray(e)?e:[e],o=Array.apply(null,Array(n.length)).map(function(e){return null});function s(){return!("<"===(arguments.length>0&&void 0!==arguments[0]?arguments[0]:"").trim().charAt(0))}function a(e,t){r.onError(e,n[t],t)}function c(e,t){var s=r.onSuccess(e,n[t],t);e=!1===s?"":s||e,o[t]=e,-1===o.indexOf(null)&&r.onComplete(o)}var i=document.createElement("a");n.forEach(function(e,t){if(i.setAttribute("href",e),i.href=String(i.href),Boolean(document.all&&!window.atob)&&i.host.split(":")[0]!==location.host.split(":")[0]){if(i.protocol===location.protocol){var n=new XDomainRequest;n.open("GET",e),n.timeout=0,n.onprogress=Function.prototype,n.ontimeout=Function.prototype,n.onload=function(){s(n.responseText)?c(n.responseText,t):a(n,t)},n.onerror=function(e){a(n,t)},setTimeout(function(){n.send()},0)}else console.warn("Internet Explorer 9 Cross-Origin (CORS) requests must use the same protocol (".concat(e,")")),a(null,t)}else{var o=new XMLHttpRequest;o.open("GET",e),r.mimeType&&o.overrideMimeType&&o.overrideMimeType(r.mimeType),r.onBeforeSend(o,e,t),o.onreadystatechange=function(){4===o.readyState&&(200===o.status&&s(o.responseText)?c(o.responseText,t):a(o,t))},o.send()}})}function n(e){var t={cssComments:/\/\*[\s\S]+?\*\//g,cssImports:/(?:@import\s*)(?:url\(\s*)?(?:['"])([^'"]*)(?:['"])(?:\s*\))?(?:[^;]*;)/g},n={rootElement:e.rootElement||document,include:e.include||'style,link[rel="stylesheet"]',exclude:e.exclude||null,filter:e.filter||null,useCSSOM:e.useCSSOM||!1,onBeforeSend:e.onBeforeSend||Function.prototype,onSuccess:e.onSuccess||Function.prototype,onError:e.onError||Function.prototype,onComplete:e.onComplete||Function.prototype},s=Array.apply(null,n.rootElement.querySelectorAll(n.include)).filter(function(e){return t=e,r=n.exclude,!(t.matches||t.matchesSelector||t.webkitMatchesSelector||t.mozMatchesSelector||t.msMatchesSelector||t.oMatchesSelector).call(t,r);var t,r}),a=Array.apply(null,Array(s.length)).map(function(e){return null});function c(){if(-1===a.indexOf(null)){var e=a.join("");n.onComplete(e,a,s)}}function i(e,t,o,s){var i=n.onSuccess(e,o,s);(function e(t,o,s,a){var c=arguments.length>4&&void 0!==arguments[4]?arguments[4]:[];var i=arguments.length>5&&void 0!==arguments[5]?arguments[5]:[];var l=u(t,s,i);l.rules.length?r(l.absoluteUrls,{onBeforeSend:function(e,t,r){n.onBeforeSend(e,o,t)},onSuccess:function(e,t,r){var s=n.onSuccess(e,o,t),a=u(e=!1===s?"":s||e,t,i);return a.rules.forEach(function(t,r){e=e.replace(t,a.absoluteRules[r])}),e},onError:function(r,n,u){c.push({xhr:r,url:n}),i.push(l.rules[u]),e(t,o,s,a,c,i)},onComplete:function(r){r.forEach(function(e,r){t=t.replace(l.rules[r],e)}),e(t,o,s,a,c,i)}}):a(t,c)})(e=void 0!==i&&!1===Boolean(i)?"":i||e,o,s,function(e,r){null===a[t]&&(r.forEach(function(e){return n.onError(e.xhr,o,e.url)}),!n.filter||n.filter.test(e)?a[t]=e:a[t]="",c())})}function u(e,r){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:[],s={};return s.rules=(e.replace(t.cssComments,"").match(t.cssImports)||[]).filter(function(e){return-1===n.indexOf(e)}),s.urls=s.rules.map(function(e){return e.replace(t.cssImports,"$1")}),s.absoluteUrls=s.urls.map(function(e){return o(e,r)}),s.absoluteRules=s.rules.map(function(e,t){var n=s.urls[t],a=o(s.absoluteUrls[t],r);return e.replace(n,a)}),s}s.length?s.forEach(function(e,t){var s=e.getAttribute("href"),u=e.getAttribute("rel"),l="LINK"===e.nodeName&&s&&u&&"stylesheet"===u.toLowerCase(),f="STYLE"===e.nodeName;if(l)r(s,{mimeType:"text/css",onBeforeSend:function(t,r,o){n.onBeforeSend(t,e,r)},onSuccess:function(r,n,a){var c=o(s,location.href);i(r,t,e,c)},onError:function(r,o,s){a[t]="",n.onError(r,e,o),c()}});else if(f){var d=e.textContent;n.useCSSOM&&(d=Array.apply(null,e.sheet.cssRules).map(function(e){return e.cssText}).join("")),i(d,t,e,location.href)}else a[t]="",c()}):n.onComplete("",[])}function o(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:location.href,r=document.implementation.createHTMLDocument(""),n=r.createElement("base"),o=r.createElement("a");return r.head.appendChild(n),r.body.appendChild(o),n.href=t,o.href=e,o.href}var s=a;function a(e,t,r){e instanceof RegExp&&(e=c(e,r)),t instanceof RegExp&&(t=c(t,r));var n=i(e,t,r);return n&&{start:n[0],end:n[1],pre:r.slice(0,n[0]),body:r.slice(n[0]+e.length,n[1]),post:r.slice(n[1]+t.length)}}function c(e,t){var r=t.match(e);return r?r[0]:null}function i(e,t,r){var n,o,s,a,c,i=r.indexOf(e),u=r.indexOf(t,i+1),l=i;if(i>=0&&u>0){for(n=[],s=r.length;l>=0&&!c;)l==i?(n.push(l),i=r.indexOf(e,l+1)):1==n.length?c=[n.pop(),u]:((o=n.pop())<s&&(s=o,a=u),u=r.indexOf(t,l+1)),l=i<u&&i>=0?i:u;n.length&&(c=[s,a])}return c}function u(t){var r=e({},{preserveStatic:!0,removeComments:!1},arguments.length>1&&void 0!==arguments[1]?arguments[1]:{});function n(e){throw new Error("CSS parse error: ".concat(e))}function o(e){var r=e.exec(t);if(r)return t=t.slice(r[0].length),r}function a(){return o(/^{\s*/)}function c(){return o(/^}/)}function i(){o(/^\s*/)}function u(){if(i(),"/"===t[0]&&"*"===t[1]){for(var e=2;t[e]&&("*"!==t[e]||"/"!==t[e+1]);)e++;if(!t[e])return n("end of comment is missing");var r=t.slice(2,e);return t=t.slice(e+2),{type:"comment",comment:r}}}function l(){for(var e,t=[];e=u();)t.push(e);return r.removeComments?[]:t}function f(){for(i();"}"===t[0];)n("extra closing bracket");var e=o(/^(("(?:\\"|[^"])*"|'(?:\\'|[^'])*'|[^{])+)/);if(e)return e[0].trim().replace(/\/\*([^*]|[\r\n]|(\*+([^*\/]|[\r\n])))*\*\/+/g,"").replace(/"(?:\\"|[^"])*"|'(?:\\'|[^'])*'/g,function(e){return e.replace(/,/g,"‌")}).split(/\s*(?![^(]*\)),\s*/).map(function(e){return e.replace(/\u200C/g,",")})}function d(){o(/^([;\s]*)+/);var e=/\/\*[^*]*\*+([^\/*][^*]*\*+)*\//g,t=o(/^(\*?[-#\/*\\\w]+(\[[0-9a-z_-]+\])?)\s*/);if(t){if(t=t[0].trim(),!o(/^:\s*/))return n("property missing ':'");var r=o(/^((?:\/\*.*?\*\/|'(?:\\'|.)*?'|"(?:\\"|.)*?"|\((\s*'(?:\\'|.)*?'|"(?:\\"|.)*?"|[^)]*?)\s*\)|[^};])+)/),s={type:"declaration",property:t.replace(e,""),value:r?r[0].replace(e,"").trim():""};return o(/^[;\s]*/),s}}function p(){if(!a())return n("missing '{'");for(var e,t=l();e=d();)t.push(e),t=t.concat(l());return c()?t:n("missing '}'")}function m(){i();for(var e,t=[];e=o(/^((\d+\.\d+|\.\d+|\d+)%?|[a-z]+)\s*/);)t.push(e[1]),o(/^,\s*/);if(t.length)return{type:"keyframe",values:t,declarations:p()}}function v(){if(i(),"@"===t[0]){var e=function(){var e=o(/^@([-\w]+)?keyframes\s*/);if(e){var t=e[1];if(!(e=o(/^([-\w]+)\s*/)))return n("@keyframes missing name");var r,s=e[1];if(!a())return n("@keyframes missing '{'");for(var i=l();r=m();)i.push(r),i=i.concat(l());return c()?{type:"keyframes",name:s,vendor:t,keyframes:i}:n("@keyframes missing '}'")}}()||function(){var e=o(/^@supports *([^{]+)/);if(e)return{type:"supports",supports:e[1].trim(),rules:y()}}()||function(){if(o(/^@host\s*/))return{type:"host",rules:y()}}()||function(){var e=o(/^@media([^{]+)*/);if(e)return{type:"media",media:(e[1]||"").trim(),rules:y()}}()||function(){var e=o(/^@custom-media\s+(--[^\s]+)\s*([^{;]+);/);if(e)return{type:"custom-media",name:e[1].trim(),media:e[2].trim()}}()||function(){if(o(/^@page */))return{type:"page",selectors:f()||[],declarations:p()}}()||function(){var e=o(/^@([-\w]+)?document *([^{]+)/);if(e)return{type:"document",document:e[2].trim(),vendor:e[1]?e[1].trim():null,rules:y()}}()||function(){if(o(/^@font-face\s*/))return{type:"font-face",declarations:p()}}()||function(){var e=o(/^@(import|charset|namespace)\s*([^;]+);/);if(e)return{type:e[1],name:e[2].trim()}}();if(e&&!r.preserveStatic){var s=!1;if(e.declarations)s=e.declarations.some(function(e){return/var\(/.test(e.value)});else s=(e.keyframes||e.rules||[]).some(function(e){return(e.declarations||[]).some(function(e){return/var\(/.test(e.value)})});return s?e:{}}return e}}function h(){if(!r.preserveStatic){var e=s("{","}",t);if(e){var o=/:(?:root|host)(?![.:#(])/.test(e.pre)&&/--\S*\s*:/.test(e.body),a=/var\(/.test(e.body);if(!o&&!a)return t=t.slice(e.end+1),{}}}var c=f()||[],i=r.preserveStatic?p():p().filter(function(e){var t=c.some(function(e){return/:(?:root|host)(?![.:#(])/.test(e)})&&/^--\S/.test(e.property),r=/var\(/.test(e.value);return t||r});return c.length||n("selector missing"),{type:"rule",selectors:c,declarations:i}}function y(e){if(!e&&!a())return n("missing '{'");for(var r,o=l();t.length&&(e||"}"!==t[0])&&(r=v()||h());)r.type&&o.push(r),o=o.concat(l());return e||c()?o:n("missing '}'")}return{type:"stylesheet",stylesheet:{rules:y(!0),errors:[]}}}function l(t){var r=e({},{parseHost:!1,store:{},onWarning:function(){}},arguments.length>1&&void 0!==arguments[1]?arguments[1]:{}),n=new RegExp(":".concat(r.parseHost?"host":"root","(?![.:#(])"));return"string"==typeof t&&(t=u(t,r)),t.stylesheet.rules.forEach(function(e){"rule"===e.type&&e.selectors.some(function(e){return n.test(e)})&&e.declarations.forEach(function(e,t){var n=e.property,o=e.value;n&&0===n.indexOf("--")&&(r.store[n]=o)})}),r.store}function f(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"",r=arguments.length>2?arguments[2]:void 0,n={charset:function(e){return"@charset "+e.name+";"},comment:function(e){return 0===e.comment.indexOf("__CSSVARSPONYFILL")?"/*"+e.comment+"*/":""},"custom-media":function(e){return"@custom-media "+e.name+" "+e.media+";"},declaration:function(e){return e.property+":"+e.value+";"},document:function(e){return"@"+(e.vendor||"")+"document "+e.document+"{"+o(e.rules)+"}"},"font-face":function(e){return"@font-face{"+o(e.declarations)+"}"},host:function(e){return"@host{"+o(e.rules)+"}"},import:function(e){return"@import "+e.name+";"},keyframe:function(e){return e.values.join(",")+"{"+o(e.declarations)+"}"},keyframes:function(e){return"@"+(e.vendor||"")+"keyframes "+e.name+"{"+o(e.keyframes)+"}"},media:function(e){return"@media "+e.media+"{"+o(e.rules)+"}"},namespace:function(e){return"@namespace "+e.name+";"},page:function(e){return"@page "+(e.selectors.length?e.selectors.join(", "):"")+"{"+o(e.declarations)+"}"},rule:function(e){var t=e.declarations;if(t.length)return e.selectors.join(",")+"{"+o(t)+"}"},supports:function(e){return"@supports "+e.supports+"{"+o(e.rules)+"}"}};function o(e){for(var o="",s=0;s<e.length;s++){var a=e[s];r&&r(a);var c=n[a.type](a);c&&(o+=c,c.length&&a.selectors&&(o+=t))}return o}return o(e.stylesheet.rules)}a.range=i;var d="--",p="var";function m(t){var r=e({},{preserveStatic:!0,preserveVars:!1,variables:{},onWarning:function(){}},arguments.length>1&&void 0!==arguments[1]?arguments[1]:{});return"string"==typeof t&&(t=u(t,r)),function e(t,r){t.rules.forEach(function(n){n.rules?e(n,r):n.keyframes?n.keyframes.forEach(function(e){"keyframe"===e.type&&r(e.declarations,n)}):n.declarations&&r(n.declarations,t)})}(t.stylesheet,function(e,t){for(var n=0;n<e.length;n++){var o=e[n],s=o.type,a=o.property,c=o.value;if("declaration"===s)if(r.preserveVars||!a||0!==a.indexOf(d)){if(-1!==c.indexOf(p+"(")){var i=h(c,r);i!==o.value&&(i=v(i),r.preserveVars?(e.splice(n,0,{type:s,property:a,value:i}),n++):o.value=i)}}else e.splice(n,1),n--}}),f(t)}function v(e){return(e.match(/calc\(([^)]+)\)/g)||[]).forEach(function(t){var r="calc".concat(t.split("calc").join(""));e=e.replace(t,r)}),e}function h(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{},r=arguments.length>2?arguments[2]:void 0;if(-1===e.indexOf("var("))return e;var n=s("(",")",e);return n?"var"===n.pre.slice(-3)?0===n.body.trim().length?(t.onWarning("var() must contain a non-whitespace string"),e):n.pre.slice(0,-3)+function(e){var n=e.split(",")[0].replace(/[\s\n\t]/g,""),o=(e.match(/(?:\s*,\s*){1}(.*)?/)||[])[1],s=Object.prototype.hasOwnProperty.call(t.variables,n)?String(t.variables[n]):void 0,a=s||(o?String(o):void 0),c=r||e;return s||t.onWarning('variable "'.concat(n,'" is undefined')),a&&"undefined"!==a&&a.length>0?h(a,t,c):"var(".concat(c,")")}(n.body)+h(n.post,t):n.pre+"(".concat(h(n.body,t),")")+h(n.post,t):(-1!==e.indexOf("var(")&&t.onWarning('missing closing ")" in the value "'.concat(e,'"')),e)}var y="undefined"!=typeof window,g=y&&window.CSS&&window.CSS.supports&&window.CSS.supports("(--a: 0)"),S={group:0,job:0},b={rootElement:y?document:null,shadowDOM:!1,include:"style,link[rel=stylesheet]",exclude:"",variables:{},onlyLegacy:!0,preserveStatic:!0,preserveVars:!1,silent:!1,updateDOM:!0,updateURLs:!0,watch:null,onBeforeSend:function(){},onWarning:function(){},onError:function(){},onSuccess:function(){},onComplete:function(){}},E={cssComments:/\/\*[\s\S]+?\*\//g,cssKeyframes:/@(?:-\w*-)?keyframes/,cssMediaQueries:/@media[^{]+\{([\s\S]+?})\s*}/g,cssUrls:/url\((?!['"]?(?:data|http|\/\/):)['"]?([^'")]*)['"]?\)/g,cssVarDeclRules:/(?::(?:root|host)(?![.:#(])[\s,]*[^{]*{\s*[^}]*})/g,cssVarDecls:/(?:[\s;]*)(-{2}\w[\w-]*)(?:\s*:\s*)([^;]*);/g,cssVarFunc:/var\(\s*--[\w-]/,cssVars:/(?:(?::(?:root|host)(?![.:#(])[\s,]*[^{]*{\s*[^;]*;*\s*)|(?:var\(\s*))(--[^:)]+)(?:\s*[:)])/},w={dom:{},job:{},user:{}},C=!1,O=null,A=0,x=null,j=!1;function k(){var r=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{},o="cssVars(): ",s=e({},b,r);function a(e,t,r,n){!s.silent&&window.console&&console.error("".concat(o).concat(e,"\n"),t),s.onError(e,t,r,n)}function c(e){!s.silent&&window.console&&console.warn("".concat(o).concat(e)),s.onWarning(e)}if(y){if(s.watch)return s.watch=b.watch,function(e){function t(e){return"LINK"===e.tagName&&-1!==(e.getAttribute("rel")||"").indexOf("stylesheet")&&!e.disabled}if(!window.MutationObserver)return;O&&(O.disconnect(),O=null);(O=new MutationObserver(function(r){r.some(function(r){var n,o=!1;return"attributes"===r.type?o=t(r.target):"childList"===r.type&&(n=r.addedNodes,o=Array.apply(null,n).some(function(e){var r=1===e.nodeType&&e.hasAttribute("data-cssvars"),n=function(e){return"STYLE"===e.tagName&&!e.disabled}(e)&&E.cssVars.test(e.textContent);return!r&&(t(e)||n)})||function(t){return Array.apply(null,t).some(function(t){var r=1===t.nodeType,n=r&&"out"===t.getAttribute("data-cssvars"),o=r&&"src"===t.getAttribute("data-cssvars"),s=o;if(o||n){var a=t.getAttribute("data-cssvars-group"),c=e.rootElement.querySelector('[data-cssvars-group="'.concat(a,'"]'));o&&(L(e.rootElement),w.dom={}),c&&c.parentNode.removeChild(c)}return s})}(r.removedNodes)),o})&&k(e)})).observe(document.documentElement,{attributes:!0,attributeFilter:["disabled","href"],childList:!0,subtree:!0})}(s),void k(s);if(!1===s.watch&&O&&(O.disconnect(),O=null),!s.__benchmark){if(C===s.rootElement)return void function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:100;clearTimeout(x),x=setTimeout(function(){e.__benchmark=null,k(e)},t)}(r);if(s.__benchmark=T(),s.exclude=[O?'[data-cssvars]:not([data-cssvars=""])':'[data-cssvars="out"]',s.exclude].filter(function(e){return e}).join(","),s.variables=function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{},t=/^-{2}/;return Object.keys(e).reduce(function(r,n){return r[t.test(n)?n:"--".concat(n.replace(/^-+/,""))]=e[n],r},{})}(s.variables),!O)if(Array.apply(null,s.rootElement.querySelectorAll('[data-cssvars="out"]')).forEach(function(e){var t=e.getAttribute("data-cssvars-group");(t?s.rootElement.querySelector('[data-cssvars="src"][data-cssvars-group="'.concat(t,'"]')):null)||e.parentNode.removeChild(e)}),A){var i=s.rootElement.querySelectorAll('[data-cssvars]:not([data-cssvars="out"])');i.length<A&&(A=i.length,w.dom={})}}if("loading"!==document.readyState)if(g&&s.onlyLegacy){if(s.updateDOM){var d=s.rootElement.host||(s.rootElement===document?document.documentElement:s.rootElement);Object.keys(s.variables).forEach(function(e){d.style.setProperty(e,s.variables[e])})}}else!j&&(s.shadowDOM||s.rootElement.shadowRoot||s.rootElement.host)?n({rootElement:b.rootElement,include:b.include,exclude:s.exclude,onSuccess:function(e,t,r){return(e=((e=e.replace(E.cssComments,"").replace(E.cssMediaQueries,"")).match(E.cssVarDeclRules)||[]).join(""))||!1},onComplete:function(e,t,r){l(e,{store:w.dom,onWarning:c}),j=!0,k(s)}}):(C=s.rootElement,n({rootElement:s.rootElement,include:s.include,exclude:s.exclude,onBeforeSend:s.onBeforeSend,onError:function(e,t,r){var n=e.responseURL||_(r,location.href),o=e.statusText?"(".concat(e.statusText,")"):"Unspecified Error"+(0===e.status?" (possibly CORS related)":"");a("CSS XHR Error: ".concat(n," ").concat(e.status," ").concat(o),t,e,n)},onSuccess:function(e,t,r){var n=s.onSuccess(e,t,r);return e=void 0!==n&&!1===Boolean(n)?"":n||e,s.updateURLs&&(e=function(e,t){return(e.replace(E.cssComments,"").match(E.cssUrls)||[]).forEach(function(r){var n=r.replace(E.cssUrls,"$1"),o=_(n,t);e=e.replace(r,r.replace(n,o))}),e}(e,r)),e},onComplete:function(r,n){var o=arguments.length>2&&void 0!==arguments[2]?arguments[2]:[],i={},d=s.updateDOM?w.dom:Object.keys(w.job).length?w.job:w.job=JSON.parse(JSON.stringify(w.dom)),p=!1;if(o.forEach(function(e,t){if(E.cssVars.test(n[t]))try{var r=u(n[t],{preserveStatic:s.preserveStatic,removeComments:!0});l(r,{parseHost:Boolean(s.rootElement.host),store:i,onWarning:c}),e.__cssVars={tree:r}}catch(t){a(t.message,e)}}),s.updateDOM&&e(w.user,s.variables),e(i,s.variables),p=Boolean((document.querySelector("[data-cssvars]")||Object.keys(w.dom).length)&&Object.keys(i).some(function(e){return i[e]!==d[e]})),e(d,w.user,i),p)L(s.rootElement),k(s);else{var v=[],h=[],y=!1;if(w.job={},s.updateDOM&&S.job++,o.forEach(function(t){var r=!t.__cssVars;if(t.__cssVars)try{m(t.__cssVars.tree,e({},s,{variables:d,onWarning:c}));var n=f(t.__cssVars.tree);if(s.updateDOM){if(t.getAttribute("data-cssvars")||t.setAttribute("data-cssvars","src"),n.length){var o=t.getAttribute("data-cssvars-group")||++S.group,i=n.replace(/\s/g,""),u=s.rootElement.querySelector('[data-cssvars="out"][data-cssvars-group="'.concat(o,'"]'))||document.createElement("style");y=y||E.cssKeyframes.test(n),u.hasAttribute("data-cssvars")||u.setAttribute("data-cssvars","out"),i===t.textContent.replace(/\s/g,"")?(r=!0,u&&u.parentNode&&(t.removeAttribute("data-cssvars-group"),u.parentNode.removeChild(u))):i!==u.textContent.replace(/\s/g,"")&&([t,u].forEach(function(e){e.setAttribute("data-cssvars-job",S.job),e.setAttribute("data-cssvars-group",o)}),u.textContent=n,v.push(n),h.push(u),u.parentNode||t.parentNode.insertBefore(u,t.nextSibling))}}else t.textContent.replace(/\s/g,"")!==n&&v.push(n)}catch(e){a(e.message,t)}r&&t.setAttribute("data-cssvars","skip"),t.hasAttribute("data-cssvars-job")||t.setAttribute("data-cssvars-job",S.job)}),A=s.rootElement.querySelectorAll('[data-cssvars]:not([data-cssvars="out"])').length,s.shadowDOM)for(var g,b=[s.rootElement].concat(t(s.rootElement.querySelectorAll("*"))),O=0;g=b[O];++O)if(g.shadowRoot&&g.shadowRoot.querySelector("style")){var x=e({},s,{rootElement:g.shadowRoot});k(x)}s.updateDOM&&y&&M(s.rootElement),C=!1,s.onComplete(v.join(""),h,JSON.parse(JSON.stringify(d)),T()-s.__benchmark)}}}));else document.addEventListener("DOMContentLoaded",function e(t){k(r),document.removeEventListener("DOMContentLoaded",e)})}}function M(e){var t=["animation-name","-moz-animation-name","-webkit-animation-name"].filter(function(e){return getComputedStyle(document.body)[e]})[0];if(t){for(var r=e.getElementsByTagName("*"),n=[],o=0,s=r.length;o<s;o++){var a=r[o];"none"!==getComputedStyle(a)[t]&&(a.style[t]+="__CSSVARSPONYFILL-KEYFRAMES__",n.push(a))}document.body.offsetHeight;for(var c=0,i=n.length;c<i;c++){var u=n[c].style;u[t]=u[t].replace("__CSSVARSPONYFILL-KEYFRAMES__","")}}}function _(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:location.href,r=document.implementation.createHTMLDocument(""),n=r.createElement("base"),o=r.createElement("a");return r.head.appendChild(n),r.body.appendChild(o),n.href=t,o.href=e,o.href}function T(){return y&&(window.performance||{}).now?window.performance.now():(new Date).getTime()}function L(e){Array.apply(null,e.querySelectorAll('[data-cssvars="skip"],[data-cssvars="src"]')).forEach(function(e){return e.setAttribute("data-cssvars","")})}return k.reset=function(){for(var e in C=!1,O&&(O.disconnect(),O=null),A=0,x=null,j=!1,w)w[e]={}},k});
 
$(document).ready(function () {
// c-nav (mobile menu)
// ------------------------------------------------------------

    $("#c-nav__catalog .c-nav__menu").mouseover(function () {
        MenuOpenCloseTimer(
            function () {
                $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').addClass('active');
                $('#c-nav__catalog').addClass('c-nav--open');
            }
        );
    });

    $(".l-header__block .c-catalog").mouseover(function (e) {
        if (e.target === this) {
            MenuOpenCloseTimer(
                function () {
                    $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').addClass('active');
                    $('#c-nav__catalog').addClass('c-nav--open');
                }
            );
        }
    });

    $("#c-nav__catalog .c-nav__menu>.c-nav__dropdown").mouseout(function () {
        MenuOpenCloseTimer(
            function () {
                $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').removeClass('active');
                $('#c-nav__catalog').removeClass('c-nav--open');
            }
        );
    });

    $(".l-header__block .c-catalog, #c-nav__catalog .c-nav__menu>.c-nav__dropdown li").hover(function () {
        MenuOpenCloseTimer(
            function () {
                $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').addClass('active');
                $('#c-nav__catalog').addClass('c-nav--open');
            }
        );
    }, function () {
        MenuOpenCloseTimer(
            function () {
                $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').removeClass('active');
                $('#c-nav__catalog').removeClass('c-nav--open');
            }
        );
    });

    $(".l-header__top .l-header__block .c-button, .l-header__top .l-header__block #c-nav__menu .c-nav__menu").hover(function () {
        MenuOpenCloseTimer(
            function () {
                $('.l-header__top .l-header__block #c-nav__menu').addClass('c-nav--open');
            }
        );
    }, function () {
        MenuOpenCloseTimer(
            function () {
                $('.l-header__top .l-header__block #c-nav__menu').removeClass('c-nav--open');
            }
        );
    });

    function MenuOpenCloseTimer(funct) {
        if (typeof this.delayTimer == "number") {
            clearTimeout(this.delayTimer);
            this.delayTimer = '';
        }
        this.delayTimer = setTimeout(function () {
            funct();
        }, 300);
    }

    $('body').on('click', 'a[href^="#c-nav"]', function (a) {
        a.preventDefault();
        var b = $(this).attr('href');
        $(b).addClass('c-nav--open');

    }), $('body').on('click', '.c-nav', function () {
        $('.c-nav').removeClass('c-nav--open');

    }), $('body').on('click', '.c-nav__menu', function (a) {
        a.stopPropagation()
    });


    $('body').on('click', 'a[href^="#c-nav__menu"]', function (a) {
        a.preventDefault();
        var b = $(this).attr('href');
        $(b).addClass('c-nav--open');
        $('body').addClass('fixed__body')

    }), $('body').on('click', '.c-nav', function () {
        $('.c-nav').removeClass('c-nav--open');
        $('body').removeClass('fixed__body')

    }), $('body').on('click', '.c-nav__menu', function (a) {
        a.stopPropagation()
    });


    $(".c-menu").click(function () {
        $('.c-nav--open').toggle().removeAttr('style');
    });

    // $(document).on('click', function (e) {
    //     if (!$(e.target).closest(".c-nav--open").length) {
    //         $('.c-nav.c-nav--open').hide();
    //     }
    //
    //     e.stopPropagation();
    // });


    $('body').on('click', '.c-nav__level--1', function () {
        var a = $(this).siblings();

        if ($(window).width() < 1025) {
            a.find('.c-nav__dropdown--2').slideUp('fast');
            $(this).find('.c-nav__dropdown--2').slideToggle('fast');
        }
        a.find('.c-nav__icon').removeClass('rotate');
        $(this).find('.c-nav__icon').toggleClass('rotate');
    });
});
 
document.addEventListener("DOMContentLoaded", function() {
    var langSelect = document.getElementById('js-lang-select');

    var changeLang = function(event) {
        var select = event.target;

        window.location.href = select.options[select.selectedIndex].value;
    };
    if (langSelect) {
    	langSelect.addEventListener('change', changeLang);
    }
}); 
$(document).ready(function () {
    var addToCompareBtn = '.js-add-to-compare', // Класс кнопки добавления товара к сравнению, кнопка должна содержать ID товара в атрибуте «data-item-id»
        compareInformer = $('.js-compare-informer'), // Уведомление о добавлении товара к сравнению
        inCompareCounter = $('.js-compare-count'), // Счётчик количества товаров в сравнении
        toCompareLink = $('.js-to-compare-link'); // Ссылка на страницу сравнения

    // Обработчик клика по кнопке добавления к сравнению «.js-add-to-compare»
    $('body').on('click', addToCompareBtn, addToCompare);

    // Функция добавления товара к сравнению
    function addToCompare() {
        // Показываем уведомление
        compareInformer.slideDown('fast');

        // Убираем уведомление
        setTimeout(function () {
            compareInformer.slideUp('fast')
        }, 1000);

        // Отправлем запрос на добавление товара к сравнению
        var request = 'inCompareProductId=' + $(this).data('item-id');

        $.ajax({
            type: "GET",
            url: mgBaseDir + "/compare",
            data: "updateCompare=1&" + request,
            dataType: "json",
            cache: false,
            success: function (response) {

                // Меняем количество товаров в счётчике
                inCompareCounter.html(response.count).fadeIn('normal');

                // «Мигаем» кнопкой перехода к сравнению
                toCompareLink.fadeOut('normal').fadeIn('normal');
            }
        });

        return false;
    }
}); 
$(document).ready(function() {
    // Удаление товара из корзины аяксом
    $('body').on('click', '.js-delete-from-cart', function() {

        var $this = $(this);
        var itemId = $this.data('delete-item-id');
        var property = $this.data('property');
        var $vari = $this.data('variant');
        $.ajax({
            type: "POST",
            url: mgBaseDir + "/cart",
            data: {
                action: "cart", // название действия в пользовательском класса Ajaxuser
                delFromCart: 1,
                itemId: itemId,
                property: property,
                variantId: $vari
            },
            dataType: "json",
            cache: false,
            success: function(response) {
                if ('success' == response.status) {
                    if (response.deliv && response.curr) {
                        var i = 0;
                        response.deliv.forEach(function(element, index, arr) {
                            $('.delivery-details-list li:eq(' + i + ') .deliveryPrice').html('&nbsp;' + element);
                            if ($('.delivery-details-list input[type=radio]:eq(' + i + ')').is(':checked')) {
                                if (element == 0) {
                                    $('.summ-info .delivery-summ').html('');
                                } else {
                                    $('.summ-info .delivery-summ').html(locale.delivery + ' <span class="order-delivery-summ">' + element + ' ' + response.curr + '</span>');
                                }
                            }
                            i++;
                        });
                    }
                    if (!$vari) $vari = 0;
                    var table = $('.deleteItemFromCart[data-property="' + property + '"][data-delete-item-id="' + itemId + '"][data-variant="' + $vari + '"]').parents('table');
                    if ($vari) {
                        $('.deleteItemFromCart[data-property="' + property + '"][data-delete-item-id="' + itemId + '"][data-variant="' + $vari + '"]').parents('tr').remove();
                    } else {
                        $('.deleteItemFromCart[data-property="' + property + '"][data-delete-item-id="' + itemId + '"]').parents('tr').remove();
                    }

                    var i = 1;
                    table.find('.index').each(function() {
                        $(this).text(i++);
                    });
                    $('.total-sum strong,.total .total-sum span.total-payment,.mg-desktop-cart .total-sum span.total-payment,.mg-fake-cart .total-sum span.total-payment').text(response.data.cart_price_wc);
                    response.data.cart_price = response.data.cart_price ? response.data.cart_price : 0;
                    response.data.cart_count = response.data.cart_count ? response.data.cart_count : 0;
                    $('.pricesht').text(response.data.cart_price);
                    $('.countsht').text(response.data.cart_count);
                    $('.cart-table .total-sum-cell strong').text(response.data.cart_price_wc);

                    if ($('.small-cart-table tr').length == 0) {

                        $('html').removeClass('c-modal--scroll');
                        $('#js-modal__cart').removeClass('c-modal--open');
                        $('.product-cart, .checkout-form-wrapper, .small-cart').hide();
                        $('.empty-cart-block').show();

                    }
                }
            }
        });
        return false;
    });

    if ($('.small-cart-table tr').length == 0) {
        $('.product-cart, .checkout-form-wrapper, .small-cart').hide();
        $('.empty-cart-block').show();
    }
}); 
// c-modal
// ------------------------------------------------------------
$('body').on('click', 'a[href^="#js-modal"]', function (a) {
    a.preventDefault();
    var b = $(this).attr('href');
    $(b).addClass('c-modal--open');
    if ($(document).height() > $(window).height()) {
        $('html').addClass('c-modal--scroll');
    }

}), $('body').on('click', '.c-modal, .c-modal__close, .c-modal__cart', function () {
    $('.c-modal').removeClass('c-modal--open');
    $('html').removeClass('c-modal--scroll');

}), $('body').on('click', '.c-modal__content', function (a) {
    a.stopPropagation()
}); 
/*!
 * hoverIntent v1.8.1 // 2014.08.11 // jQuery v1.9.1+
 * http://briancherne.github.io/jquery-hoverIntent/
 *
 * You may use hoverIntent under the terms of the MIT license. Basically that
 * means you are free to use hoverIntent as long as this header is left intact.
 * Copyright 2007, 2014 Brian Cherne
 */

/* hoverIntent is similar to jQuery's built-in "hover" method except that
 * instead of firing the handlerIn function immediately, hoverIntent checks
 * to see if the user's mouse has slowed down (beneath the sensitivity
 * threshold) before firing the event. The handlerOut function is only
 * called after a matching handlerIn.
 *
 * // basic usage ... just like .hover()
 * .hoverIntent( handlerIn, handlerOut )
 * .hoverIntent( handlerInOut )
 *
 * // basic usage ... with event delegation!
 * .hoverIntent( handlerIn, handlerOut, selector )
 * .hoverIntent( handlerInOut, selector )
 *
 * // using a basic configuration object
 * .hoverIntent( config )
 *
 * @param  handlerIn   function OR configuration object
 * @param  handlerOut  function OR selector for delegation OR undefined
 * @param  selector    selector OR undefined
 * @author Brian Cherne <brian(at)cherne(dot)net>
 */

;(function(factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        define(['jquery'], factory);
    } else if (jQuery && !jQuery.fn.hoverIntent) {
        factory(jQuery);
    }
})(function($) {
    'use strict';

    // default configuration values
    var _cfg = {
        interval: 100,
        sensitivity: 6,
        timeout: 0
    };

    // counter used to generate an ID for each instance
    var INSTANCE_COUNT = 0;

    // current X and Y position of mouse, updated during mousemove tracking (shared across instances)
    var cX, cY;

    // saves the current pointer position coordinates based on the given mousemove event
    var track = function(ev) {
        cX = ev.pageX;
        cY = ev.pageY;
    };

    // compares current and previous mouse positions
    var compare = function(ev,$el,s,cfg) {
        // compare mouse positions to see if pointer has slowed enough to trigger `over` function
        if ( Math.sqrt( (s.pX-cX)*(s.pX-cX) + (s.pY-cY)*(s.pY-cY) ) < cfg.sensitivity ) {
            $el.off(s.event,track);
            delete s.timeoutId;
            // set hoverIntent state as active for this element (permits `out` handler to trigger)
            s.isActive = true;
            // overwrite old mouseenter event coordinates with most recent pointer position
            ev.pageX = cX; ev.pageY = cY;
            // clear coordinate data from state object
            delete s.pX; delete s.pY;
            return cfg.over.apply($el[0],[ev]);
        } else {
            // set previous coordinates for next comparison
            s.pX = cX; s.pY = cY;
            // use self-calling timeout, guarantees intervals are spaced out properly (avoids JavaScript timer bugs)
            s.timeoutId = setTimeout( function(){compare(ev, $el, s, cfg);} , cfg.interval );
        }
    };

    // triggers given `out` function at configured `timeout` after a mouseleave and clears state
    var delay = function(ev,$el,s,out) {
        delete $el.data('hoverIntent')[s.id];
        return out.apply($el[0],[ev]);
    };

    $.fn.hoverIntent = function(handlerIn,handlerOut,selector) {
        // instance ID, used as a key to store and retrieve state information on an element
        var instanceId = INSTANCE_COUNT++;

        // extend the default configuration and parse parameters
        var cfg = $.extend({}, _cfg);
        if ( $.isPlainObject(handlerIn) ) {
            cfg = $.extend(cfg, handlerIn);
            if ( !$.isFunction(cfg.out) ) {
                cfg.out = cfg.over;
            }
        } else if ( $.isFunction(handlerOut) ) {
            cfg = $.extend(cfg, { over: handlerIn, out: handlerOut, selector: selector } );
        } else {
            cfg = $.extend(cfg, { over: handlerIn, out: handlerIn, selector: handlerOut } );
        }

        // A private function for handling mouse 'hovering'
        var handleHover = function(e) {
            // cloned event to pass to handlers (copy required for event object to be passed in IE)
            var ev = $.extend({},e);

            // the current target of the mouse event, wrapped in a jQuery object
            var $el = $(this);

            // read hoverIntent data from element (or initialize if not present)
            var hoverIntentData = $el.data('hoverIntent');
            if (!hoverIntentData) { $el.data('hoverIntent', (hoverIntentData = {})); }

            // read per-instance state from element (or initialize if not present)
            var state = hoverIntentData[instanceId];
            if (!state) { hoverIntentData[instanceId] = state = { id: instanceId }; }

            // state properties:
            // id = instance ID, used to clean up data
            // timeoutId = timeout ID, reused for tracking mouse position and delaying "out" handler
            // isActive = plugin state, true after `over` is called just until `out` is called
            // pX, pY = previously-measured pointer coordinates, updated at each polling interval
            // event = string representing the namespaced event used for mouse tracking

            // clear any existing timeout
            if (state.timeoutId) { state.timeoutId = clearTimeout(state.timeoutId); }

            // namespaced event used to register and unregister mousemove tracking
            var mousemove = state.event = 'mousemove.hoverIntent.hoverIntent'+instanceId;

            // handle the event, based on its type
            if (e.type === 'mouseenter') {
                // do nothing if already active
                if (state.isActive) { return; }
                // set "previous" X and Y position based on initial entry point
                state.pX = ev.pageX; state.pY = ev.pageY;
                // update "current" X and Y position based on mousemove
                $el.off(mousemove,track).on(mousemove,track);
                // start polling interval (self-calling timeout) to compare mouse coordinates over time
                state.timeoutId = setTimeout( function(){compare(ev,$el,state,cfg);} , cfg.interval );
            } else { // "mouseleave"
                // do nothing if not already active
                if (!state.isActive) { return; }
                // unbind expensive mousemove event
                $el.off(mousemove,track);
                // if hoverIntent state is true, then call the mouseOut function after the specified delay
                state.timeoutId = setTimeout( function(){delay(ev,$el,state,cfg.out);} , cfg.timeout );
            }
        };

        // listen for mouseenter and mouseleave
        return this.on({'mouseenter.hoverIntent':handleHover,'mouseleave.hoverIntent':handleHover}, cfg.selector);
    };
});
 
// c-catalog
// ------------------------------------------------------------
$('.c-catalog .c-button').on('click', function () {
    if ($(window).width() < 1025) {
        $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').toggleClass('active');
    }

}), $('body').on('click', function () {
    if ($(window).width() < 1025) {
        $('.c-catalog .c-button, .l-main, .c-catalog__dropdown--1').removeClass('active');
    }

}), $('body').on('click', '.c-catalog', function (a) {
    a.stopPropagation()
});

$('.c-catalog__level').hoverIntent({
    sensitivity: 3,
    interval: 100,
    timeout: 200,
    over: function () {
        $(this).find('> .c-catalog__dropdown').addClass('active');
    },
    out: function () {
        $(this).find('.c-catalog__dropdown').removeClass('active');
    }
}); 
$(document).ready(function () {
    // Обработка ввода поисковой фразы в поле поиска
    $('body').on('keyup', 'input[name=search]', function () {

        var text = $(this).val();
        if (text.length >= 2) {
            $.ajax({
                type: "POST",
                url: mgBaseDir + "/catalog",
                data: {
                    fastsearch: "true",
                    text: text
                },
                dataType: "json",
                cache: false,
                success: function (data) {
                    if ('success' == data.status && data.item.items.catalogItems.length > 0) {
                        $('.fastResult').html(data.html);
                        $('.fastResult').show();
                        $('.wraper-fast-result').show();
                    } else {
                        $('.fastResult').hide();
                    }
                }
            });
        } else {
            $('.fastResult').hide();
        }
    });

    // клик вне поиска
    $(document).mousedown(function (e) {
        var container = $(".wraper-fast-result");
        if (container.has(e.target).length === 0 && $(".search-block").has(e.target).length === 0) {
            container.hide();
        }
    });

}); 
"use strict";function _classCallCheck(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function _defineProperties(e,t){for(var i=0;i<t.length;i++){var s=t[i];s.enumerable=s.enumerable||!1,s.configurable=!0,"value"in s&&(s.writable=!0),Object.defineProperty(e,s.key,s)}}function _createClass(e,t,i){return t&&_defineProperties(e.prototype,t),i&&_defineProperties(e,i),e}var Spoiler=function(){function t(e){_classCallCheck(this,t),this._spoiler=e,this._spoilerTitle=this._spoiler.querySelector(".spoiler-title"),this._spoilerContent=this._spoiler.querySelector(".spoiler-content"),this._spolierIsClosed=!0}return _createClass(t,[{key:"init",value:function(){this._spoilerTitle.addEventListener("click",this._slideToggleSpoilerContent.bind(this))}},{key:"_slideToggleSpoilerContent",value:function(){var e;this._spolierIsClosed?(this._spoilerContent.style.display="block",e=this._spoilerContent.scrollHeight,this._spoilerContent.style.height="".concat(e,"px"),this._spolierIsClosed=!1):(this._spoilerContent.style.height=0,this._spolierIsClosed=!0)}}]),t}();document.querySelectorAll(".spoiler").forEach(function(e){return new Spoiler(e).init()}); 
// Polyfill for css vars
cssVars();

$(document).ready(function () {
    // add active link
    // ------------------------------------------------------------
    $('nav a').each(function () {
        var location = window.location.href;
        var link = this.href;
        if (location == link) {
            $(this).addClass('active');
        }
    });

    // plugin "slider-action"
    // ------------------------------------------------------------
    $(document).ready(function () {
        $('.m-p-slider-wrapper').addClass('show');
    });


    // plugin "product-slider"
    // ------------------------------------------------------------
    $(document).ready(function () {
        $('.mg-advise').addClass('mg-advise--active');
    });


    // agreement
    // ------------------------------------------------------------
    $('.l-body').on('change', '[type="checkbox"]', function () {
        if ($(this).prop('checked')) {
            $(this).closest('label').removeClass('nonactive').addClass('active');
        }
        else {
            $(this).closest('label').removeClass('active').addClass('nonactive');
        }
    });

    // op-field-check
    // ------------------------------------------------------------
    $('.l-body').on('change', '.op-field-check [type="radio"]', function () {
        $('.op-field-check [name='+$(this).attr('name')+']').closest('label').removeClass('active').addClass('nonactive');
        if ($(this).prop('checked')) {
           $(this).closest('label').removeClass('nonactive').addClass('active');
        }
        else{
            $(this).closest('label').removeClass('active').addClass('nonactive');
        }
    });

    // order
    // ------------------------------------------------------------
    $('.c-order__checkbox label').on('click', function () {
        if ($(this).children('[type="checkbox"]').is(':checked')) {
            $(this).removeClass('nonactive').addClass('active');
        } else {
            $(this).removeClass('active').addClass('nonactive');
        }
    });
    $('.c-order__radiobutton label, .order-storage label').on('click', function () {
        if ($(this).children('[type="radio"]').is(':checked')) {
            $(this).removeClass('nonactive').addClass('active');
            $(this).siblings('label').removeClass('active');
        }
    });

    //эмуляция радиокнопок в форме характеристик продукта (страница товара, миникарточка, корзина, страница заказа)
    var form = $('.js-product-form');
    $(form).on('change', '[type=radio]', function () {
        $(this).parents('p').find('input[type=radio]').prop('checked', false);
        $(this).prop('checked', true);
        $(this).parents('p').find('label').removeClass('active');
        if ($(this).parents('p').length) {
            $(this).parent().addClass('active');
        }
    });

    //эмуляция чекбоксов в форме характеристик продукта (страница товара, миникарточка, корзина, страница заказа)
    $(form).on('change', '[type=checkbox]', function () {
        $(this).parent().toggleClass('active');
    });


    $('.spoiler-title').on('click', function () {
        $(this).parents('.spoiler').toggleClass('_active');
    });
}); // end ready

$('input, textarea').each(function () {
    var $elem = $(this);
    if ($elem.attr('placeholder') && !$elem[0].placeholder) {
        var $label = $('<label class="placeholder"></label>').text($elem.attr('placeholder'));
        $elem.before($label);
        $elem.blur();
        if ($elem.val() === '') {
            $label.addClass('visible');
        }
        $label.click(function () {
            $label.removeClass('visible');
            $elem.focus();
        });
        $elem.focus(function () {
            if ($elem.val() === '') {
                $label.removeClass('visible');
            }
        });
        $elem.blur(function () {
            if ($elem.val() === '') {
                $label.addClass('visible');
            }
        });
    }
});

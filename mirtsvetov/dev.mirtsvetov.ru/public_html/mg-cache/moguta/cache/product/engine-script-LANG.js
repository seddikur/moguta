 
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
$(document).ready(function () {
    // Синхронизация Главного изображения и превью
    // Главное изображение
    var sync1 = $(".js-main-img-slider");
    // Превью
    var sync2 = $(".js-secondary-img-slider");

    if (sync2.length) {
        // Слайдов на странице
        var slidesPerPage = 3;
        var syncedSecondary = true;

        sync1.owlCarousel({
            items: 1,
            slideSpeed: 2000,
            nav: false,
            autoplay: false,
            dots: false,
            loop: false,
            responsiveRefreshRate: 200,
        }).on('changed.owl.carousel', syncPosition);

        sync2
            .on('initialized.owl.carousel', function () {
                sync2.find(".owl-item").eq(0).addClass("current");
            })
            .owlCarousel({
                items: slidesPerPage,
                dots: false,
                nav: true,
                smartSpeed: 200,
                slideSpeed: 500,
                slideBy: 1,
                margin: 10,
                responsiveRefreshRate: 100,
                navText: [
                    '<div class="c-carousel__arrow c-carousel__arrow--left"><svg class="icon icon--arrow-left"><use xlink:href="#icon--arrow-left"></use></svg></div>',
                    '<div class="c-carousel__arrow c-carousel__arrow--right"><svg class="icon icon--arrow-right"><use xlink:href="#icon--arrow-right"></use></svg></div>'
                ]
            }).on('changed.owl.carousel', syncPosition2);

        function syncPosition(el) {
            var count = el.item.count - 1;
            var current = Math.round(el.item.index - (el.item.count / 2) - .5);

            if (count === 1) {
                current--;
            }

            if (current < 0) {
                current = count;
            }
            if (current > count) {
                current = 0;
            }


            sync2
                .find(".owl-item")
                .removeClass("current")
                .eq(el.item.index)
                .addClass("current");
            var onscreen = sync2.find('.owl-item').length - 1;
            var start = sync2.find('.owl-item').first().index();
            var end = sync2.find('.owl-item').last().index();

            if (current > end) {
                sync2.data('owl.carousel').to(current, 100, true);
            }
            if (current < start) {
                sync2.data('owl.carousel').to(current - onscreen, 100, true);
            }
        }

        function syncPosition2(el) {
            if (syncedSecondary) {
                var number = el.item.index;
                sync1.data('owl.carousel').to(number, 100, true);
            }
        }

        sync2.on("click", ".owl-item", function (e) {
            e.preventDefault();
            var number = $(this).index();
            sync1.data('owl.carousel').to(number, 300, true);
        });
    }

});
 
// ==================================================
// fancyBox v3.5.2
//
// Licensed GPLv3 for open source use
// or fancyBox Commercial License for commercial use
//
// http://fancyapps.com/fancybox/
// Copyright 2018 fancyApps
//
// ==================================================
!function(t,e,n,o){"use strict";function a(t,e){var o,a,i,s=[],r=0;t&&t.isDefaultPrevented()||(t.preventDefault(),e=e||{},t&&t.data&&(e=h(t.data.options,e)),o=e.$target||n(t.currentTarget).trigger("blur"),i=n.fancybox.getInstance(),i&&i.$trigger&&i.$trigger.is(o)||(e.selector?s=n(e.selector):(a=o.attr("data-fancybox")||"",a?(s=t.data?t.data.items:[],s=s.length?s.filter('[data-fancybox="'+a+'"]'):n('[data-fancybox="'+a+'"]')):s=[o]),r=n(s).index(o),r<0&&(r=0),i=n.fancybox.open(s,e,r),i.$trigger=o))}if(t.console=t.console||{info:function(t){}},n){if(n.fn.fancybox)return void console.info("fancyBox already initialized");var i={closeExisting:!1,loop:!1,gutter:50,keyboard:!0,preventCaptionOverlap:!0,arrows:!0,infobar:!0,smallBtn:"auto",toolbar:"auto",buttons:["zoom","slideShow","thumbs","close"],idleTime:3,protect:!1,modal:!1,image:{preload:!1},ajax:{settings:{data:{fancybox:!0}}},iframe:{tpl:'<iframe id="fancybox-frame{rnd}" name="fancybox-frame{rnd}" class="fancybox-iframe" allowfullscreen allow="autoplay; fullscreen" src=""></iframe>',preload:!0,css:{},attr:{scrolling:"auto"}},video:{tpl:'<video class="fancybox-video" controls controlsList="nodownload" poster="{{poster}}"><source src="{{src}}" type="{{format}}" />Sorry, your browser doesn\'t support embedded videos, <a href="{{src}}">download</a> and watch with your favorite video player!</video>',format:"",autoStart:!0},defaultType:"image",animationEffect:"zoom",animationDuration:366,zoomOpacity:"auto",transitionEffect:"fade",transitionDuration:366,slideClass:"",baseClass:"",baseTpl:'<div class="fancybox-container" role="dialog" tabindex="-1"><div class="fancybox-bg"></div><div class="fancybox-inner"><div class="fancybox-infobar"><span data-fancybox-index></span>&nbsp;/&nbsp;<span data-fancybox-count></span></div><div class="fancybox-toolbar">{{buttons}}</div><div class="fancybox-navigation">{{arrows}}</div><div class="fancybox-stage"></div><div class="fancybox-caption"></div></div></div>',spinnerTpl:'<div class="fancybox-loading"></div>',errorTpl:'<div class="fancybox-error"><p>{{ERROR}}</p></div>',btnTpl:{download:'<a download data-fancybox-download class="fancybox-button fancybox-button--download" title="{{DOWNLOAD}}" href="javascript:;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.62 17.09V19H5.38v-1.91zm-2.97-6.96L17 11.45l-5 4.87-5-4.87 1.36-1.32 2.68 2.64V5h1.92v7.77z"/></svg></a>',zoom:'<button data-fancybox-zoom class="fancybox-button fancybox-button--zoom" title="{{ZOOM}}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.7 17.3l-3-3a5.9 5.9 0 0 0-.6-7.6 5.9 5.9 0 0 0-8.4 0 5.9 5.9 0 0 0 0 8.4 5.9 5.9 0 0 0 7.7.7l3 3a1 1 0 0 0 1.3 0c.4-.5.4-1 0-1.5zM8.1 13.8a4 4 0 0 1 0-5.7 4 4 0 0 1 5.7 0 4 4 0 0 1 0 5.7 4 4 0 0 1-5.7 0z"/></svg></button>',close:'<button data-fancybox-close class="fancybox-button fancybox-button--close" title="{{CLOSE}}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 10.6L6.6 5.2 5.2 6.6l5.4 5.4-5.4 5.4 1.4 1.4 5.4-5.4 5.4 5.4 1.4-1.4-5.4-5.4 5.4-5.4-1.4-1.4-5.4 5.4z"/></svg></button>',arrowLeft:'<button data-fancybox-prev class="fancybox-button fancybox-button--arrow_left" title="{{PREV}}"><div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11.28 15.7l-1.34 1.37L5 12l4.94-5.07 1.34 1.38-2.68 2.72H19v1.94H8.6z"/></svg></div></button>',arrowRight:'<button data-fancybox-next class="fancybox-button fancybox-button--arrow_right" title="{{NEXT}}"><div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.4 12.97l-2.68 2.72 1.34 1.38L19 12l-4.94-5.07-1.34 1.38 2.68 2.72H5v1.94z"/></svg></div></button>',smallBtn:'<button type="button" data-fancybox-close class="fancybox-button fancybox-close-small" title="{{CLOSE}}"><svg xmlns="http://www.w3.org/2000/svg" version="1" viewBox="0 0 24 24"><path d="M13 12l5-5-1-1-5 5-5-5-1 1 5 5-5 5 1 1 5-5 5 5 1-1z"/></svg></button>'},parentEl:"body",hideScrollbar:!0,autoFocus:!0,backFocus:!0,trapFocus:!0,fullScreen:{autoStart:!1},touch:{vertical:!0,momentum:!0},hash:null,media:{},slideShow:{autoStart:!1,speed:3e3},thumbs:{autoStart:!1,hideOnClose:!0,parentEl:".fancybox-container",axis:"y"},wheel:"auto",onInit:n.noop,beforeLoad:n.noop,afterLoad:n.noop,beforeShow:n.noop,afterShow:n.noop,beforeClose:n.noop,afterClose:n.noop,onActivate:n.noop,onDeactivate:n.noop,clickContent:function(t,e){return"image"===t.type&&"zoom"},clickSlide:"close",clickOutside:"close",dblclickContent:!1,dblclickSlide:!1,dblclickOutside:!1,mobile:{preventCaptionOverlap:!1,idleTime:!1,clickContent:function(t,e){return"image"===t.type&&"toggleControls"},clickSlide:function(t,e){return"image"===t.type?"toggleControls":"close"},dblclickContent:function(t,e){return"image"===t.type&&"zoom"},dblclickSlide:function(t,e){return"image"===t.type&&"zoom"}},lang:"en",i18n:{en:{CLOSE:"Close",NEXT:"Next",PREV:"Previous",ERROR:"The requested content cannot be loaded. <br/> Please try again later.",PLAY_START:"Start slideshow",PLAY_STOP:"Pause slideshow",FULL_SCREEN:"Full screen",THUMBS:"Thumbnails",DOWNLOAD:"Download",SHARE:"Share",ZOOM:"Zoom"},de:{CLOSE:"Schliessen",NEXT:"Weiter",PREV:"Zurück",ERROR:"Die angeforderten Daten konnten nicht geladen werden. <br/> Bitte versuchen Sie es später nochmal.",PLAY_START:"Diaschau starten",PLAY_STOP:"Diaschau beenden",FULL_SCREEN:"Vollbild",THUMBS:"Vorschaubilder",DOWNLOAD:"Herunterladen",SHARE:"Teilen",ZOOM:"Maßstab"}}},s=n(t),r=n(e),c=0,l=function(t){return t&&t.hasOwnProperty&&t instanceof n},d=function(){return t.requestAnimationFrame||t.webkitRequestAnimationFrame||t.mozRequestAnimationFrame||t.oRequestAnimationFrame||function(e){return t.setTimeout(e,1e3/60)}}(),u=function(){return t.cancelAnimationFrame||t.webkitCancelAnimationFrame||t.mozCancelAnimationFrame||t.oCancelAnimationFrame||function(e){t.clearTimeout(e)}}(),f=function(){var t,n=e.createElement("fakeelement"),a={transition:"transitionend",OTransition:"oTransitionEnd",MozTransition:"transitionend",WebkitTransition:"webkitTransitionEnd"};for(t in a)if(n.style[t]!==o)return a[t];return"transitionend"}(),p=function(t){return t&&t.length&&t[0].offsetHeight},h=function(t,e){var o=n.extend(!0,{},t,e);return n.each(e,function(t,e){n.isArray(e)&&(o[t]=e)}),o},g=function(t){var o,a;return!(!t||t.ownerDocument!==e)&&(n(".fancybox-container").css("pointer-events","none"),o={x:t.getBoundingClientRect().left+t.offsetWidth/2,y:t.getBoundingClientRect().top+t.offsetHeight/2},a=e.elementFromPoint(o.x,o.y)===t,n(".fancybox-container").css("pointer-events",""),a)},b=function(t,e,o){var a=this;a.opts=h({index:o},n.fancybox.defaults),n.isPlainObject(e)&&(a.opts=h(a.opts,e)),n.fancybox.isMobile&&(a.opts=h(a.opts,a.opts.mobile)),a.id=a.opts.id||++c,a.currIndex=parseInt(a.opts.index,10)||0,a.prevIndex=null,a.prevPos=null,a.currPos=0,a.firstRun=!0,a.group=[],a.slides={},a.addContent(t),a.group.length&&a.init()};n.extend(b.prototype,{init:function(){var o,a,i=this,s=i.group[i.currIndex],r=s.opts;r.closeExisting&&n.fancybox.close(!0),n("body").addClass("fancybox-active"),!n.fancybox.getInstance()&&r.hideScrollbar!==!1&&!n.fancybox.isMobile&&e.body.scrollHeight>t.innerHeight&&(n("head").append('<style id="fancybox-style-noscroll" type="text/css">.compensate-for-scrollbar{margin-right:'+(t.innerWidth-e.documentElement.clientWidth)+"px;}</style>"),n("body").addClass("compensate-for-scrollbar")),a="",n.each(r.buttons,function(t,e){a+=r.btnTpl[e]||""}),o=n(i.translate(i,r.baseTpl.replace("{{buttons}}",a).replace("{{arrows}}",r.btnTpl.arrowLeft+r.btnTpl.arrowRight))).attr("id","fancybox-container-"+i.id).addClass(r.baseClass).data("FancyBox",i).appendTo(r.parentEl),i.$refs={container:o},["bg","inner","infobar","toolbar","stage","caption","navigation"].forEach(function(t){i.$refs[t]=o.find(".fancybox-"+t)}),i.trigger("onInit"),i.activate(),i.jumpTo(i.currIndex)},translate:function(t,e){var n=t.opts.i18n[t.opts.lang]||t.opts.i18n.en;return e.replace(/\{\{(\w+)\}\}/g,function(t,e){var a=n[e];return a===o?t:a})},addContent:function(t){var e,a=this,i=n.makeArray(t);n.each(i,function(t,e){var i,s,r,c,l,d={},u={};n.isPlainObject(e)?(d=e,u=e.opts||e):"object"===n.type(e)&&n(e).length?(i=n(e),u=i.data()||{},u=n.extend(!0,{},u,u.options),u.$orig=i,d.src=a.opts.src||u.src||i.attr("href"),d.type||d.src||(d.type="inline",d.src=e)):d={type:"html",src:e+""},d.opts=n.extend(!0,{},a.opts,u),n.isArray(u.buttons)&&(d.opts.buttons=u.buttons),n.fancybox.isMobile&&d.opts.mobile&&(d.opts=h(d.opts,d.opts.mobile)),s=d.type||d.opts.type,c=d.src||"",!s&&c&&((r=c.match(/\.(mp4|mov|ogv|webm)((\?|#).*)?$/i))?(s="video",d.opts.video.format||(d.opts.video.format="video/"+("ogv"===r[1]?"ogg":r[1]))):c.match(/(^data:image\/[a-z0-9+\/=]*,)|(\.(jp(e|g|eg)|gif|png|bmp|webp|svg|ico)((\?|#).*)?$)/i)?s="image":c.match(/\.(pdf)((\?|#).*)?$/i)?(s="iframe",d=n.extend(!0,d,{contentType:"pdf",opts:{iframe:{preload:!1}}})):"#"===c.charAt(0)&&(s="inline")),s?d.type=s:a.trigger("objectNeedsType",d),d.contentType||(d.contentType=n.inArray(d.type,["html","inline","ajax"])>-1?"html":d.type),d.index=a.group.length,"auto"==d.opts.smallBtn&&(d.opts.smallBtn=n.inArray(d.type,["html","inline","ajax"])>-1),"auto"===d.opts.toolbar&&(d.opts.toolbar=!d.opts.smallBtn),d.$thumb=d.opts.$thumb||null,d.opts.$trigger&&d.index===a.opts.index&&(d.$thumb=d.opts.$trigger.find("img:first"),d.$thumb.length&&(d.opts.$orig=d.opts.$trigger)),d.$thumb&&d.$thumb.length||!d.opts.$orig||(d.$thumb=d.opts.$orig.find("img:first")),d.$thumb&&!d.$thumb.length&&(d.$thumb=null),d.thumb=d.opts.thumb||(d.$thumb?d.$thumb[0].src:null),"function"===n.type(d.opts.caption)&&(d.opts.caption=d.opts.caption.apply(e,[a,d])),"function"===n.type(a.opts.caption)&&(d.opts.caption=a.opts.caption.apply(e,[a,d])),d.opts.caption instanceof n||(d.opts.caption=d.opts.caption===o?"":d.opts.caption+""),"ajax"===d.type&&(l=c.split(/\s+/,2),l.length>1&&(d.src=l.shift(),d.opts.filter=l.shift())),d.opts.modal&&(d.opts=n.extend(!0,d.opts,{trapFocus:!0,infobar:0,toolbar:0,smallBtn:0,keyboard:0,slideShow:0,fullScreen:0,thumbs:0,touch:0,clickContent:!1,clickSlide:!1,clickOutside:!1,dblclickContent:!1,dblclickSlide:!1,dblclickOutside:!1})),a.group.push(d)}),Object.keys(a.slides).length&&(a.updateControls(),e=a.Thumbs,e&&e.isActive&&(e.create(),e.focus()))},addEvents:function(){var e=this;e.removeEvents(),e.$refs.container.on("click.fb-close","[data-fancybox-close]",function(t){t.stopPropagation(),t.preventDefault(),e.close(t)}).on("touchstart.fb-prev click.fb-prev","[data-fancybox-prev]",function(t){t.stopPropagation(),t.preventDefault(),e.previous()}).on("touchstart.fb-next click.fb-next","[data-fancybox-next]",function(t){t.stopPropagation(),t.preventDefault(),e.next()}).on("click.fb","[data-fancybox-zoom]",function(t){e[e.isScaledDown()?"scaleToActual":"scaleToFit"]()}),s.on("orientationchange.fb resize.fb",function(t){t&&t.originalEvent&&"resize"===t.originalEvent.type?(e.requestId&&u(e.requestId),e.requestId=d(function(){e.update(t)})):(e.current&&"iframe"===e.current.type&&e.$refs.stage.hide(),setTimeout(function(){e.$refs.stage.show(),e.update(t)},n.fancybox.isMobile?600:250))}),r.on("keydown.fb",function(t){var o=n.fancybox?n.fancybox.getInstance():null,a=o.current,i=t.keyCode||t.which;if(9==i)return void(a.opts.trapFocus&&e.focus(t));if(!(!a.opts.keyboard||t.ctrlKey||t.altKey||t.shiftKey||n(t.target).is("input")||n(t.target).is("textarea")))return 8===i||27===i?(t.preventDefault(),void e.close(t)):37===i||38===i?(t.preventDefault(),void e.previous()):39===i||40===i?(t.preventDefault(),void e.next()):void e.trigger("afterKeydown",t,i)}),e.group[e.currIndex].opts.idleTime&&(e.idleSecondsCounter=0,r.on("mousemove.fb-idle mouseleave.fb-idle mousedown.fb-idle touchstart.fb-idle touchmove.fb-idle scroll.fb-idle keydown.fb-idle",function(t){e.idleSecondsCounter=0,e.isIdle&&e.showControls(),e.isIdle=!1}),e.idleInterval=t.setInterval(function(){e.idleSecondsCounter++,e.idleSecondsCounter>=e.group[e.currIndex].opts.idleTime&&!e.isDragging&&(e.isIdle=!0,e.idleSecondsCounter=0,e.hideControls())},1e3))},removeEvents:function(){var e=this;s.off("orientationchange.fb resize.fb"),r.off("keydown.fb .fb-idle"),this.$refs.container.off(".fb-close .fb-prev .fb-next"),e.idleInterval&&(t.clearInterval(e.idleInterval),e.idleInterval=null)},previous:function(t){return this.jumpTo(this.currPos-1,t)},next:function(t){return this.jumpTo(this.currPos+1,t)},jumpTo:function(t,e){var a,i,s,r,c,l,d,u,f,h=this,g=h.group.length;if(!(h.isDragging||h.isClosing||h.isAnimating&&h.firstRun)){if(t=parseInt(t,10),s=h.current?h.current.opts.loop:h.opts.loop,!s&&(t<0||t>=g))return!1;if(a=h.firstRun=!Object.keys(h.slides).length,c=h.current,h.prevIndex=h.currIndex,h.prevPos=h.currPos,r=h.createSlide(t),g>1&&((s||r.index<g-1)&&h.createSlide(t+1),(s||r.index>0)&&h.createSlide(t-1)),h.current=r,h.currIndex=r.index,h.currPos=r.pos,h.trigger("beforeShow",a),h.updateControls(),r.forcedDuration=o,n.isNumeric(e)?r.forcedDuration=e:e=r.opts[a?"animationDuration":"transitionDuration"],e=parseInt(e,10),i=h.isMoved(r),r.$slide.addClass("fancybox-slide--current"),a)return r.opts.animationEffect&&e&&h.$refs.container.css("transition-duration",e+"ms"),h.$refs.container.addClass("fancybox-is-open").trigger("focus"),h.loadSlide(r),void h.preload("image");l=n.fancybox.getTranslate(c.$slide),d=n.fancybox.getTranslate(h.$refs.stage),n.each(h.slides,function(t,e){n.fancybox.stop(e.$slide,!0)}),c.pos!==r.pos&&(c.isComplete=!1),c.$slide.removeClass("fancybox-slide--complete fancybox-slide--current"),i?(f=l.left-(c.pos*l.width+c.pos*c.opts.gutter),n.each(h.slides,function(t,o){o.$slide.removeClass("fancybox-animated").removeClass(function(t,e){return(e.match(/(^|\s)fancybox-fx-\S+/g)||[]).join(" ")});var a=o.pos*l.width+o.pos*o.opts.gutter;n.fancybox.setTranslate(o.$slide,{top:0,left:a-d.left+f}),o.pos!==r.pos&&o.$slide.addClass("fancybox-slide--"+(o.pos>r.pos?"next":"previous")),p(o.$slide),n.fancybox.animate(o.$slide,{top:0,left:(o.pos-r.pos)*l.width+(o.pos-r.pos)*o.opts.gutter},e,function(){o.$slide.css({transform:"",opacity:""}).removeClass("fancybox-slide--next fancybox-slide--previous"),o.pos===h.currPos&&h.complete()})})):e&&r.opts.transitionEffect&&(u="fancybox-animated fancybox-fx-"+r.opts.transitionEffect,c.$slide.addClass("fancybox-slide--"+(c.pos>r.pos?"next":"previous")),n.fancybox.animate(c.$slide,u,e,function(){c.$slide.removeClass(u).removeClass("fancybox-slide--next fancybox-slide--previous")},!1)),r.isLoaded?h.revealContent(r):h.loadSlide(r),h.preload("image")}},createSlide:function(t){var e,o,a=this;return o=t%a.group.length,o=o<0?a.group.length+o:o,!a.slides[t]&&a.group[o]&&(e=n('<div class="fancybox-slide"></div>').appendTo(a.$refs.stage),a.slides[t]=n.extend(!0,{},a.group[o],{pos:t,$slide:e,isLoaded:!1}),a.updateSlide(a.slides[t])),a.slides[t]},scaleToActual:function(t,e,a){var i,s,r,c,l,d=this,u=d.current,f=u.$content,p=n.fancybox.getTranslate(u.$slide).width,h=n.fancybox.getTranslate(u.$slide).height,g=u.width,b=u.height;d.isAnimating||d.isMoved()||!f||"image"!=u.type||!u.isLoaded||u.hasError||(d.isAnimating=!0,n.fancybox.stop(f),t=t===o?.5*p:t,e=e===o?.5*h:e,i=n.fancybox.getTranslate(f),i.top-=n.fancybox.getTranslate(u.$slide).top,i.left-=n.fancybox.getTranslate(u.$slide).left,c=g/i.width,l=b/i.height,s=.5*p-.5*g,r=.5*h-.5*b,g>p&&(s=i.left*c-(t*c-t),s>0&&(s=0),s<p-g&&(s=p-g)),b>h&&(r=i.top*l-(e*l-e),r>0&&(r=0),r<h-b&&(r=h-b)),d.updateCursor(g,b),n.fancybox.animate(f,{top:r,left:s,scaleX:c,scaleY:l},a||330,function(){d.isAnimating=!1}),d.SlideShow&&d.SlideShow.isActive&&d.SlideShow.stop())},scaleToFit:function(t){var e,o=this,a=o.current,i=a.$content;o.isAnimating||o.isMoved()||!i||"image"!=a.type||!a.isLoaded||a.hasError||(o.isAnimating=!0,n.fancybox.stop(i),e=o.getFitPos(a),o.updateCursor(e.width,e.height),n.fancybox.animate(i,{top:e.top,left:e.left,scaleX:e.width/i.width(),scaleY:e.height/i.height()},t||330,function(){o.isAnimating=!1}))},getFitPos:function(t){var e,o,a,i,s=this,r=t.$content,c=t.$slide,l=t.width||t.opts.width,d=t.height||t.opts.height,u={};return!!(t.isLoaded&&r&&r.length)&&(e=n.fancybox.getTranslate(s.$refs.stage).width,o=n.fancybox.getTranslate(s.$refs.stage).height,e-=parseFloat(c.css("paddingLeft"))+parseFloat(c.css("paddingRight"))+parseFloat(r.css("marginLeft"))+parseFloat(r.css("marginRight")),o-=parseFloat(c.css("paddingTop"))+parseFloat(c.css("paddingBottom"))+parseFloat(r.css("marginTop"))+parseFloat(r.css("marginBottom")),l&&d||(l=e,d=o),a=Math.min(1,e/l,o/d),l=a*l,d=a*d,l>e-.5&&(l=e),d>o-.5&&(d=o),"image"===t.type?(u.top=Math.floor(.5*(o-d))+parseFloat(c.css("paddingTop")),u.left=Math.floor(.5*(e-l))+parseFloat(c.css("paddingLeft"))):"video"===t.contentType&&(i=t.opts.width&&t.opts.height?l/d:t.opts.ratio||16/9,d>l/i?d=l/i:l>d*i&&(l=d*i)),u.width=l,u.height=d,u)},update:function(t){var e=this;n.each(e.slides,function(n,o){e.updateSlide(o,t)})},updateSlide:function(t,e){var o=this,a=t&&t.$content,i=t.width||t.opts.width,s=t.height||t.opts.height,r=t.$slide;o.adjustCaption(t),a&&(i||s||"video"===t.contentType)&&!t.hasError&&(n.fancybox.stop(a),n.fancybox.setTranslate(a,o.getFitPos(t)),t.pos===o.currPos&&(o.isAnimating=!1,o.updateCursor())),o.adjustLayout(t),r.length&&(r.trigger("refresh"),t.pos===o.currPos&&o.$refs.toolbar.add(o.$refs.navigation.find(".fancybox-button--arrow_right")).toggleClass("compensate-for-scrollbar",r.get(0).scrollHeight>r.get(0).clientHeight)),o.trigger("onUpdate",t,e)},centerSlide:function(t){var e=this,a=e.current,i=a.$slide;!e.isClosing&&a&&(i.siblings().css({transform:"",opacity:""}),i.parent().children().removeClass("fancybox-slide--previous fancybox-slide--next"),n.fancybox.animate(i,{top:0,left:0,opacity:1},t===o?0:t,function(){i.css({transform:"",opacity:""}),a.isComplete||e.complete()},!1))},isMoved:function(t){var e,o,a=t||this.current;return!!a&&(o=n.fancybox.getTranslate(this.$refs.stage),e=n.fancybox.getTranslate(a.$slide),!a.$slide.hasClass("fancybox-animated")&&(Math.abs(e.top-o.top)>.5||Math.abs(e.left-o.left)>.5))},updateCursor:function(t,e){var o,a,i=this,s=i.current,r=i.$refs.container;s&&!i.isClosing&&i.Guestures&&(r.removeClass("fancybox-is-zoomable fancybox-can-zoomIn fancybox-can-zoomOut fancybox-can-swipe fancybox-can-pan"),o=i.canPan(t,e),a=!!o||i.isZoomable(),r.toggleClass("fancybox-is-zoomable",a),n("[data-fancybox-zoom]").prop("disabled",!a),o?r.addClass("fancybox-can-pan"):a&&("zoom"===s.opts.clickContent||n.isFunction(s.opts.clickContent)&&"zoom"==s.opts.clickContent(s))?r.addClass("fancybox-can-zoomIn"):s.opts.touch&&(s.opts.touch.vertical||i.group.length>1)&&"video"!==s.contentType&&r.addClass("fancybox-can-swipe"))},isZoomable:function(){var t,e=this,n=e.current;if(n&&!e.isClosing&&"image"===n.type&&!n.hasError){if(!n.isLoaded)return!0;if(t=e.getFitPos(n),t&&(n.width>t.width||n.height>t.height))return!0}return!1},isScaledDown:function(t,e){var a=this,i=!1,s=a.current,r=s.$content;return t!==o&&e!==o?i=t<s.width&&e<s.height:r&&(i=n.fancybox.getTranslate(r),i=i.width<s.width&&i.height<s.height),i},canPan:function(t,e){var a=this,i=a.current,s=null,r=!1;return"image"===i.type&&(i.isComplete||t&&e)&&!i.hasError&&(r=a.getFitPos(i),t!==o&&e!==o?s={width:t,height:e}:i.isComplete&&(s=n.fancybox.getTranslate(i.$content)),s&&r&&(r=Math.abs(s.width-r.width)>1.5||Math.abs(s.height-r.height)>1.5)),r},loadSlide:function(t){var e,o,a,i=this;if(!t.isLoading&&!t.isLoaded){if(t.isLoading=!0,i.trigger("beforeLoad",t)===!1)return t.isLoading=!1,!1;switch(e=t.type,o=t.$slide,o.off("refresh").trigger("onReset").addClass(t.opts.slideClass),e){case"image":i.setImage(t);break;case"iframe":i.setIframe(t);break;case"html":i.setContent(t,t.src||t.content);break;case"video":i.setContent(t,t.opts.video.tpl.replace(/\{\{src\}\}/gi,t.src).replace("{{format}}",t.opts.videoFormat||t.opts.video.format||"").replace("{{poster}}",t.thumb||""));break;case"inline":n(t.src).length?i.setContent(t,n(t.src)):i.setError(t);break;case"ajax":i.showLoading(t),a=n.ajax(n.extend({},t.opts.ajax.settings,{url:t.src,success:function(e,n){"success"===n&&i.setContent(t,e)},error:function(e,n){e&&"abort"!==n&&i.setError(t)}})),o.one("onReset",function(){a.abort()});break;default:i.setError(t)}return!0}},setImage:function(t){var o,a=this;setTimeout(function(){var e=t.$image;a.isClosing||!t.isLoading||e&&e.length&&e[0].complete||t.hasError||a.showLoading(t)},50),a.checkSrcset(t),t.$content=n('<div class="fancybox-content"></div>').addClass("fancybox-is-hidden").appendTo(t.$slide.addClass("fancybox-slide--image")),t.opts.preload!==!1&&t.opts.width&&t.opts.height&&t.thumb&&(t.width=t.opts.width,t.height=t.opts.height,o=e.createElement("img"),o.onerror=function(){n(this).remove(),t.$ghost=null},o.onload=function(){a.afterLoad(t)},t.$ghost=n(o).addClass("fancybox-image").appendTo(t.$content).attr("src",t.thumb)),a.setBigImage(t)},checkSrcset:function(e){var n,o,a,i,s=e.opts.srcset||e.opts.image.srcset;if(s){a=t.devicePixelRatio||1,i=t.innerWidth*a,o=s.split(",").map(function(t){var e={};return t.trim().split(/\s+/).forEach(function(t,n){var o=parseInt(t.substring(0,t.length-1),10);return 0===n?e.url=t:void(o&&(e.value=o,e.postfix=t[t.length-1]))}),e}),o.sort(function(t,e){return t.value-e.value});for(var r=0;r<o.length;r++){var c=o[r];if("w"===c.postfix&&c.value>=i||"x"===c.postfix&&c.value>=a){n=c;break}}!n&&o.length&&(n=o[o.length-1]),n&&(e.src=n.url,e.width&&e.height&&"w"==n.postfix&&(e.height=e.width/e.height*n.value,e.width=n.value),e.opts.srcset=s)}},setBigImage:function(t){var o=this,a=e.createElement("img"),i=n(a);t.$image=i.one("error",function(){o.setError(t)}).one("load",function(){var e;t.$ghost||(o.resolveImageSlideSize(t,this.naturalWidth,this.naturalHeight),o.afterLoad(t)),o.isClosing||(t.opts.srcset&&(e=t.opts.sizes,e&&"auto"!==e||(e=(t.width/t.height>1&&s.width()/s.height()>1?"100":Math.round(t.width/t.height*100))+"vw"),i.attr("sizes",e).attr("srcset",t.opts.srcset)),t.$ghost&&setTimeout(function(){t.$ghost&&!o.isClosing&&t.$ghost.hide()},Math.min(300,Math.max(1e3,t.height/1600))),o.hideLoading(t))}).addClass("fancybox-image").attr("src",t.src).appendTo(t.$content),(a.complete||"complete"==a.readyState)&&i.naturalWidth&&i.naturalHeight?i.trigger("load"):a.error&&i.trigger("error")},resolveImageSlideSize:function(t,e,n){var o=parseInt(t.opts.width,10),a=parseInt(t.opts.height,10);t.width=e,t.height=n,o>0&&(t.width=o,t.height=Math.floor(o*n/e)),a>0&&(t.width=Math.floor(a*e/n),t.height=a)},setIframe:function(t){var e,a=this,i=t.opts.iframe,s=t.$slide;n.fancybox.isMobile&&(i.css.overflow="scroll"),t.$content=n('<div class="fancybox-content'+(i.preload?" fancybox-is-hidden":"")+'"></div>').css(i.css).appendTo(s),s.addClass("fancybox-slide--"+t.contentType),t.$iframe=e=n(i.tpl.replace(/\{rnd\}/g,(new Date).getTime())).attr(i.attr).appendTo(t.$content),i.preload?(a.showLoading(t),e.on("load.fb error.fb",function(e){this.isReady=1,t.$slide.trigger("refresh"),a.afterLoad(t)}),s.on("refresh.fb",function(){var n,a,r=t.$content,c=i.css.width,l=i.css.height;if(1===e[0].isReady){try{n=e.contents(),a=n.find("body")}catch(t){}a&&a.length&&a.children().length&&(s.css("overflow","visible"),r.css({width:"100%","max-width":"100%",height:"9999px"}),c===o&&(c=Math.ceil(Math.max(a[0].clientWidth,a.outerWidth(!0)))),r.css("width",c?c:"").css("max-width",""),l===o&&(l=Math.ceil(Math.max(a[0].clientHeight,a.outerHeight(!0)))),r.css("height",l?l:""),s.css("overflow","auto")),r.removeClass("fancybox-is-hidden")}})):a.afterLoad(t),e.attr("src",t.src),s.one("onReset",function(){try{n(this).find("iframe").hide().unbind().attr("src","//about:blank")}catch(t){}n(this).off("refresh.fb").empty(),t.isLoaded=!1,t.isRevealed=!1})},setContent:function(t,e){var o=this;o.isClosing||(o.hideLoading(t),t.$content&&n.fancybox.stop(t.$content),t.$slide.empty(),l(e)&&e.parent().length?((e.hasClass("fancybox-content")||e.parent().hasClass("fancybox-content"))&&e.parents(".fancybox-slide").trigger("onReset"),t.$placeholder=n("<div>").hide().insertAfter(e),e.css("display","inline-block")):t.hasError||("string"===n.type(e)&&(e=n("<div>").append(n.trim(e)).contents()),t.opts.filter&&(e=n("<div>").html(e).find(t.opts.filter))),t.$slide.one("onReset",function(){n(this).find("video,audio").trigger("pause"),t.$placeholder&&(t.$placeholder.after(e.removeClass("fancybox-content").hide()).remove(),t.$placeholder=null),t.$smallBtn&&(t.$smallBtn.remove(),t.$smallBtn=null),t.hasError||(n(this).empty(),t.isLoaded=!1,t.isRevealed=!1)}),n(e).appendTo(t.$slide),n(e).is("video,audio")&&(n(e).addClass("fancybox-video"),n(e).wrap("<div></div>"),t.contentType="video",t.opts.width=t.opts.width||n(e).attr("width"),t.opts.height=t.opts.height||n(e).attr("height")),t.$content=t.$slide.children().filter("div,form,main,video,audio,article,.fancybox-content").first(),t.$content.siblings().hide(),t.$content.length||(t.$content=t.$slide.wrapInner("<div></div>").children().first()),t.$content.addClass("fancybox-content"),t.$slide.addClass("fancybox-slide--"+t.contentType),o.afterLoad(t))},setError:function(t){t.hasError=!0,t.$slide.trigger("onReset").removeClass("fancybox-slide--"+t.contentType).addClass("fancybox-slide--error"),t.contentType="html",this.setContent(t,this.translate(t,t.opts.errorTpl)),t.pos===this.currPos&&(this.isAnimating=!1)},showLoading:function(t){var e=this;t=t||e.current,t&&!t.$spinner&&(t.$spinner=n(e.translate(e,e.opts.spinnerTpl)).appendTo(t.$slide).hide().fadeIn("fast"))},hideLoading:function(t){var e=this;t=t||e.current,t&&t.$spinner&&(t.$spinner.stop().remove(),delete t.$spinner)},afterLoad:function(t){var e=this;e.isClosing||(t.isLoading=!1,t.isLoaded=!0,e.trigger("afterLoad",t),e.hideLoading(t),!t.opts.smallBtn||t.$smallBtn&&t.$smallBtn.length||(t.$smallBtn=n(e.translate(t,t.opts.btnTpl.smallBtn)).appendTo(t.$content)),t.opts.protect&&t.$content&&!t.hasError&&(t.$content.on("contextmenu.fb",function(t){return 2==t.button&&t.preventDefault(),!0}),"image"===t.type&&n('<div class="fancybox-spaceball"></div>').appendTo(t.$content)),e.adjustCaption(t),e.adjustLayout(t),t.pos===e.currPos&&e.updateCursor(),e.revealContent(t))},adjustCaption:function(t){var e=this,n=t||e.current,o=n.opts.caption,a=e.$refs.caption,i=!1;n.opts.preventCaptionOverlap&&o&&o.length&&(n.pos!==e.currPos?(a=a.clone().empty().appendTo(a.parent()),a.html(o),i=a.outerHeight(!0),a.empty().remove()):e.$caption&&(i=e.$caption.outerHeight(!0)),n.$slide.css("padding-bottom",i||""))},adjustLayout:function(t){var e,n,o,a,i=this,s=t||i.current;s.isLoaded&&s.opts.disableLayoutFix!==!0&&(s.$content.css("margin-bottom",""),s.$content.outerHeight()>s.$slide.height()+.5&&(o=s.$slide[0].style["padding-bottom"],a=s.$slide.css("padding-bottom"),parseFloat(a)>0&&(e=s.$slide[0].scrollHeight,s.$slide.css("padding-bottom",0),Math.abs(e-s.$slide[0].scrollHeight)<1&&(n=a),s.$slide.css("padding-bottom",o))),s.$content.css("margin-bottom",n))},revealContent:function(t){var e,a,i,s,r=this,c=t.$slide,l=!1,d=!1,u=r.isMoved(t),f=t.isRevealed;return t.isRevealed=!0,e=t.opts[r.firstRun?"animationEffect":"transitionEffect"],i=t.opts[r.firstRun?"animationDuration":"transitionDuration"],i=parseInt(t.forcedDuration===o?i:t.forcedDuration,10),!u&&t.pos===r.currPos&&i||(e=!1),"zoom"===e&&(t.pos===r.currPos&&i&&"image"===t.type&&!t.hasError&&(d=r.getThumbPos(t))?l=r.getFitPos(t):e="fade"),"zoom"===e?(r.isAnimating=!0,l.scaleX=l.width/d.width,l.scaleY=l.height/d.height,s=t.opts.zoomOpacity,"auto"==s&&(s=Math.abs(t.width/t.height-d.width/d.height)>.1),s&&(d.opacity=.1,l.opacity=1),n.fancybox.setTranslate(t.$content.removeClass("fancybox-is-hidden"),d),p(t.$content),void n.fancybox.animate(t.$content,l,i,function(){r.isAnimating=!1,r.complete()})):(r.updateSlide(t),e?(n.fancybox.stop(c),a="fancybox-slide--"+(t.pos>=r.prevPos?"next":"previous")+" fancybox-animated fancybox-fx-"+e,c.addClass(a).removeClass("fancybox-slide--current"),t.$content.removeClass("fancybox-is-hidden"),p(c),"image"!==t.type&&t.$content.hide().show(0),void n.fancybox.animate(c,"fancybox-slide--current",i,function(){c.removeClass(a).css({transform:"",opacity:""}),t.pos===r.currPos&&r.complete()},!0)):(t.$content.removeClass("fancybox-is-hidden"),f||!u||"image"!==t.type||t.hasError||t.$content.hide().fadeIn("fast"),void(t.pos===r.currPos&&r.complete())))},getThumbPos:function(t){var e,o,a,i,s,r=!1,c=t.$thumb;return!(!c||!g(c[0]))&&(e=n.fancybox.getTranslate(c),o=parseFloat(c.css("border-top-width")||0),a=parseFloat(c.css("border-right-width")||0),i=parseFloat(c.css("border-bottom-width")||0),s=parseFloat(c.css("border-left-width")||0),r={top:e.top+o,left:e.left+s,width:e.width-a-s,height:e.height-o-i,scaleX:1,scaleY:1},e.width>0&&e.height>0&&r)},complete:function(){var t,e=this,o=e.current,a={};!e.isMoved()&&o.isLoaded&&(o.isComplete||(o.isComplete=!0,o.$slide.siblings().trigger("onReset"),e.preload("inline"),p(o.$slide),o.$slide.addClass("fancybox-slide--complete"),n.each(e.slides,function(t,o){o.pos>=e.currPos-1&&o.pos<=e.currPos+1?a[o.pos]=o:o&&(n.fancybox.stop(o.$slide),o.$slide.off().remove())}),e.slides=a),e.isAnimating=!1,e.updateCursor(),e.trigger("afterShow"),o.opts.video.autoStart&&o.$slide.find("video,audio").filter(":visible:first").trigger("play").one("ended",function(){this.webkitExitFullscreen&&this.webkitExitFullscreen(),e.next()}),o.opts.autoFocus&&"html"===o.contentType&&(t=o.$content.find("input[autofocus]:enabled:visible:first"),t.length?t.trigger("focus"):e.focus(null,!0)),o.$slide.scrollTop(0).scrollLeft(0))},preload:function(t){var e,n,o=this;o.group.length<2||(n=o.slides[o.currPos+1],e=o.slides[o.currPos-1],e&&e.type===t&&o.loadSlide(e),n&&n.type===t&&o.loadSlide(n))},focus:function(t,o){var a,i,s=this,r=["a[href]","area[href]",'input:not([disabled]):not([type="hidden"]):not([aria-hidden])',"select:not([disabled]):not([aria-hidden])","textarea:not([disabled]):not([aria-hidden])","button:not([disabled]):not([aria-hidden])","iframe","object","embed","[contenteditable]",'[tabindex]:not([tabindex^="-"])'].join(",");s.isClosing||(a=!t&&s.current&&s.current.isComplete?s.current.$slide.find("*:visible"+(o?":not(.fancybox-close-small)":"")):s.$refs.container.find("*:visible"),a=a.filter(r).filter(function(){return"hidden"!==n(this).css("visibility")&&!n(this).hasClass("disabled")}),a.length?(i=a.index(e.activeElement),t&&t.shiftKey?(i<0||0==i)&&(t.preventDefault(),a.eq(a.length-1).trigger("focus")):(i<0||i==a.length-1)&&(t&&t.preventDefault(),a.eq(0).trigger("focus"))):s.$refs.container.trigger("focus"))},activate:function(){var t=this;n(".fancybox-container").each(function(){var e=n(this).data("FancyBox");e&&e.id!==t.id&&!e.isClosing&&(e.trigger("onDeactivate"),e.removeEvents(),e.isVisible=!1)}),t.isVisible=!0,(t.current||t.isIdle)&&(t.update(),t.updateControls()),t.trigger("onActivate"),t.addEvents()},close:function(t,e){var o,a,i,s,r,c,l,u=this,f=u.current,h=function(){u.cleanUp(t)};return!u.isClosing&&(u.isClosing=!0,u.trigger("beforeClose",t)===!1?(u.isClosing=!1,d(function(){u.update()}),!1):(u.removeEvents(),i=f.$content,o=f.opts.animationEffect,a=n.isNumeric(e)?e:o?f.opts.animationDuration:0,f.$slide.removeClass("fancybox-slide--complete fancybox-slide--next fancybox-slide--previous fancybox-animated"),t!==!0?n.fancybox.stop(f.$slide):o=!1,f.$slide.siblings().trigger("onReset").remove(),a&&u.$refs.container.removeClass("fancybox-is-open").addClass("fancybox-is-closing").css("transition-duration",a+"ms"),u.hideLoading(f),u.hideControls(!0),u.updateCursor(),"zoom"!==o||i&&a&&"image"===f.type&&!u.isMoved()&&!f.hasError&&(l=u.getThumbPos(f))||(o="fade"),"zoom"===o?(n.fancybox.stop(i),s=n.fancybox.getTranslate(i),c={top:s.top,left:s.left,scaleX:s.width/l.width,scaleY:s.height/l.height,width:l.width,height:l.height},r=f.opts.zoomOpacity,"auto"==r&&(r=Math.abs(f.width/f.height-l.width/l.height)>.1),r&&(l.opacity=0),n.fancybox.setTranslate(i,c),p(i),n.fancybox.animate(i,l,a,h),!0):(o&&a?n.fancybox.animate(f.$slide.addClass("fancybox-slide--previous").removeClass("fancybox-slide--current"),"fancybox-animated fancybox-fx-"+o,a,h):t===!0?setTimeout(h,a):h(),
!0)))},cleanUp:function(e){var o,a,i,s=this,r=s.current.opts.$orig;s.current.$slide.trigger("onReset"),s.$refs.container.empty().remove(),s.trigger("afterClose",e),s.current.opts.backFocus&&(r&&r.length&&r.is(":visible")||(r=s.$trigger),r&&r.length&&(a=t.scrollX,i=t.scrollY,r.trigger("focus"),n("html, body").scrollTop(i).scrollLeft(a))),s.current=null,o=n.fancybox.getInstance(),o?o.activate():(n("body").removeClass("fancybox-active compensate-for-scrollbar"),n("#fancybox-style-noscroll").remove())},trigger:function(t,e){var o,a=Array.prototype.slice.call(arguments,1),i=this,s=e&&e.opts?e:i.current;return s?a.unshift(s):s=i,a.unshift(i),n.isFunction(s.opts[t])&&(o=s.opts[t].apply(s,a)),o===!1?o:void("afterClose"!==t&&i.$refs?i.$refs.container.trigger(t+".fb",a):r.trigger(t+".fb",a))},updateControls:function(){var t=this,o=t.current,a=o.index,i=t.$refs.container,s=t.$refs.caption,r=o.opts.caption;o.$slide.trigger("refresh"),t.$caption=r&&r.length?s.html(r):null,t.hasHiddenControls||t.isIdle||t.showControls(),i.find("[data-fancybox-count]").html(t.group.length),i.find("[data-fancybox-index]").html(a+1),i.find("[data-fancybox-prev]").prop("disabled",!o.opts.loop&&a<=0),i.find("[data-fancybox-next]").prop("disabled",!o.opts.loop&&a>=t.group.length-1),"image"===o.type?i.find("[data-fancybox-zoom]").show().end().find("[data-fancybox-download]").attr("href",o.opts.image.src||o.src).show():o.opts.toolbar&&i.find("[data-fancybox-download],[data-fancybox-zoom]").hide(),n(e.activeElement).is(":hidden,[disabled]")&&t.$refs.container.trigger("focus")},hideControls:function(t){var e=this,n=["infobar","toolbar","nav"];!t&&e.current.opts.preventCaptionOverlap||n.push("caption"),this.$refs.container.removeClass(n.map(function(t){return"fancybox-show-"+t}).join(" ")),this.hasHiddenControls=!0},showControls:function(){var t=this,e=t.current?t.current.opts:t.opts,n=t.$refs.container;t.hasHiddenControls=!1,t.idleSecondsCounter=0,n.toggleClass("fancybox-show-toolbar",!(!e.toolbar||!e.buttons)).toggleClass("fancybox-show-infobar",!!(e.infobar&&t.group.length>1)).toggleClass("fancybox-show-caption",!!t.$caption).toggleClass("fancybox-show-nav",!!(e.arrows&&t.group.length>1)).toggleClass("fancybox-is-modal",!!e.modal)},toggleControls:function(){this.hasHiddenControls?this.showControls():this.hideControls()}}),n.fancybox={version:"3.5.2",defaults:i,getInstance:function(t){var e=n('.fancybox-container:not(".fancybox-is-closing"):last').data("FancyBox"),o=Array.prototype.slice.call(arguments,1);return e instanceof b&&("string"===n.type(t)?e[t].apply(e,o):"function"===n.type(t)&&t.apply(e,o),e)},open:function(t,e,n){return new b(t,e,n)},close:function(t){var e=this.getInstance();e&&(e.close(),t===!0&&this.close(t))},destroy:function(){this.close(!0),r.add("body").off("click.fb-start","**")},isMobile:/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),use3d:function(){var n=e.createElement("div");return t.getComputedStyle&&t.getComputedStyle(n)&&t.getComputedStyle(n).getPropertyValue("transform")&&!(e.documentMode&&e.documentMode<11)}(),getTranslate:function(t){var e;return!(!t||!t.length)&&(e=t[0].getBoundingClientRect(),{top:e.top||0,left:e.left||0,width:e.width,height:e.height,opacity:parseFloat(t.css("opacity"))})},setTranslate:function(t,e){var n="",a={};if(t&&e)return e.left===o&&e.top===o||(n=(e.left===o?t.position().left:e.left)+"px, "+(e.top===o?t.position().top:e.top)+"px",n=this.use3d?"translate3d("+n+", 0px)":"translate("+n+")"),e.scaleX!==o&&e.scaleY!==o?n+=" scale("+e.scaleX+", "+e.scaleY+")":e.scaleX!==o&&(n+=" scaleX("+e.scaleX+")"),n.length&&(a.transform=n),e.opacity!==o&&(a.opacity=e.opacity),e.width!==o&&(a.width=e.width),e.height!==o&&(a.height=e.height),t.css(a)},animate:function(t,e,a,i,s){var r,c=this;n.isFunction(a)&&(i=a,a=null),c.stop(t),r=c.getTranslate(t),t.on(f,function(l){(!l||!l.originalEvent||t.is(l.originalEvent.target)&&"z-index"!=l.originalEvent.propertyName)&&(c.stop(t),n.isNumeric(a)&&t.css("transition-duration",""),n.isPlainObject(e)?e.scaleX!==o&&e.scaleY!==o&&c.setTranslate(t,{top:e.top,left:e.left,width:r.width*e.scaleX,height:r.height*e.scaleY,scaleX:1,scaleY:1}):s!==!0&&t.removeClass(e),n.isFunction(i)&&i(l))}),n.isNumeric(a)&&t.css("transition-duration",a+"ms"),n.isPlainObject(e)?(e.scaleX!==o&&e.scaleY!==o&&(delete e.width,delete e.height,t.parent().hasClass("fancybox-slide--image")&&t.parent().addClass("fancybox-is-scaling")),n.fancybox.setTranslate(t,e)):t.addClass(e),t.data("timer",setTimeout(function(){t.trigger(f)},a+33))},stop:function(t,e){t&&t.length&&(clearTimeout(t.data("timer")),e&&t.trigger(f),t.off(f).css("transition-duration",""),t.parent().removeClass("fancybox-is-scaling"))}},n.fn.fancybox=function(t){var e;return t=t||{},e=t.selector||!1,e?n("body").off("click.fb-start",e).on("click.fb-start",e,{options:t},a):this.off("click.fb-start").on("click.fb-start",{items:this,options:t},a),this},r.on("click.fb-start","[data-fancybox]",a),r.on("click.fb-start","[data-fancybox-trigger]",function(t){n('[data-fancybox="'+n(this).attr("data-fancybox-trigger")+'"]').eq(n(this).attr("data-fancybox-index")||0).trigger("click.fb-start",{$trigger:n(this)})}),function(){var t=".fancybox-button",e="fancybox-focus",o=null;r.on("mousedown mouseup focus blur",t,function(a){switch(a.type){case"mousedown":o=n(this);break;case"mouseup":o=null;break;case"focusin":n(t).removeClass(e),n(this).is(o)||n(this).is("[disabled]")||n(this).addClass(e);break;case"focusout":n(t).removeClass(e)}})}()}}(window,document,jQuery),function(t){"use strict";var e={youtube:{matcher:/(youtube\.com|youtu\.be|youtube\-nocookie\.com)\/(watch\?(.*&)?v=|v\/|u\/|embed\/?)?(videoseries\?list=(.*)|[\w-]{11}|\?listType=(.*)&list=(.*))(.*)/i,params:{autoplay:1,autohide:1,fs:1,rel:0,hd:1,wmode:"transparent",enablejsapi:1,html5:1},paramPlace:8,type:"iframe",url:"//www.youtube-nocookie.com/embed/$4",thumb:"//img.youtube.com/vi/$4/hqdefault.jpg"},vimeo:{matcher:/^.+vimeo.com\/(.*\/)?([\d]+)(.*)?/,params:{autoplay:1,hd:1,show_title:1,show_byline:1,show_portrait:0,fullscreen:1},paramPlace:3,type:"iframe",url:"//player.vimeo.com/video/$2"},instagram:{matcher:/(instagr\.am|instagram\.com)\/p\/([a-zA-Z0-9_\-]+)\/?/i,type:"image",url:"//$1/p/$2/media/?size=l"},gmap_place:{matcher:/(maps\.)?google\.([a-z]{2,3}(\.[a-z]{2})?)\/(((maps\/(place\/(.*)\/)?\@(.*),(\d+.?\d+?)z))|(\?ll=))(.*)?/i,type:"iframe",url:function(t){return"//maps.google."+t[2]+"/?ll="+(t[9]?t[9]+"&z="+Math.floor(t[10])+(t[12]?t[12].replace(/^\//,"&"):""):t[12]+"").replace(/\?/,"&")+"&output="+(t[12]&&t[12].indexOf("layer=c")>0?"svembed":"embed")}},gmap_search:{matcher:/(maps\.)?google\.([a-z]{2,3}(\.[a-z]{2})?)\/(maps\/search\/)(.*)/i,type:"iframe",url:function(t){return"//maps.google."+t[2]+"/maps?q="+t[5].replace("query=","q=").replace("api=1","")+"&output=embed"}}},n=function(e,n,o){if(e)return o=o||"","object"===t.type(o)&&(o=t.param(o,!0)),t.each(n,function(t,n){e=e.replace("$"+t,n||"")}),o.length&&(e+=(e.indexOf("?")>0?"&":"?")+o),e};t(document).on("objectNeedsType.fb",function(o,a,i){var s,r,c,l,d,u,f,p=i.src||"",h=!1;s=t.extend(!0,{},e,i.opts.media),t.each(s,function(e,o){if(c=p.match(o.matcher)){if(h=o.type,f=e,u={},o.paramPlace&&c[o.paramPlace]){d=c[o.paramPlace],"?"==d[0]&&(d=d.substring(1)),d=d.split("&");for(var a=0;a<d.length;++a){var s=d[a].split("=",2);2==s.length&&(u[s[0]]=decodeURIComponent(s[1].replace(/\+/g," ")))}}return l=t.extend(!0,{},o.params,i.opts[e],u),p="function"===t.type(o.url)?o.url.call(this,c,l,i):n(o.url,c,l),r="function"===t.type(o.thumb)?o.thumb.call(this,c,l,i):n(o.thumb,c),"youtube"===e?p=p.replace(/&t=((\d+)m)?(\d+)s/,function(t,e,n,o){return"&start="+((n?60*parseInt(n,10):0)+parseInt(o,10))}):"vimeo"===e&&(p=p.replace("&%23","#")),!1}}),h?(i.opts.thumb||i.opts.$thumb&&i.opts.$thumb.length||(i.opts.thumb=r),"iframe"===h&&(i.opts=t.extend(!0,i.opts,{iframe:{preload:!1,attr:{scrolling:"no"}}})),t.extend(i,{type:h,src:p,origSrc:i.src,contentSource:f,contentType:"image"===h?"image":"gmap_place"==f||"gmap_search"==f?"map":"video"})):p&&(i.type=i.opts.defaultType)});var o={youtube:{src:"https://www.youtube.com/iframe_api",class:"YT",loading:!1,loaded:!1},vimeo:{src:"https://player.vimeo.com/api/player.js",class:"Vimeo",loading:!1,loaded:!1},load:function(t){var e,n=this;return this[t].loaded?void setTimeout(function(){n.done(t)}):void(this[t].loading||(this[t].loading=!0,e=document.createElement("script"),e.type="text/javascript",e.src=this[t].src,"youtube"===t?window.onYouTubeIframeAPIReady=function(){n[t].loaded=!0,n.done(t)}:e.onload=function(){n[t].loaded=!0,n.done(t)},document.body.appendChild(e)))},done:function(e){var n,o,a;"youtube"===e&&delete window.onYouTubeIframeAPIReady,n=t.fancybox.getInstance(),n&&(o=n.current.$content.find("iframe"),"youtube"===e&&void 0!==YT&&YT?a=new YT.Player(o.attr("id"),{events:{onStateChange:function(t){0==t.data&&n.next()}}}):"vimeo"===e&&void 0!==Vimeo&&Vimeo&&(a=new Vimeo.Player(o),a.on("ended",function(){n.next()})))}};t(document).on({"afterShow.fb":function(t,e,n){e.group.length>1&&("youtube"===n.contentSource||"vimeo"===n.contentSource)&&o.load(n.contentSource)}})}(jQuery),function(t,e,n){"use strict";var o=function(){return t.requestAnimationFrame||t.webkitRequestAnimationFrame||t.mozRequestAnimationFrame||t.oRequestAnimationFrame||function(e){return t.setTimeout(e,1e3/60)}}(),a=function(){return t.cancelAnimationFrame||t.webkitCancelAnimationFrame||t.mozCancelAnimationFrame||t.oCancelAnimationFrame||function(e){t.clearTimeout(e)}}(),i=function(e){var n=[];e=e.originalEvent||e||t.e,e=e.touches&&e.touches.length?e.touches:e.changedTouches&&e.changedTouches.length?e.changedTouches:[e];for(var o in e)e[o].pageX?n.push({x:e[o].pageX,y:e[o].pageY}):e[o].clientX&&n.push({x:e[o].clientX,y:e[o].clientY});return n},s=function(t,e,n){return e&&t?"x"===n?t.x-e.x:"y"===n?t.y-e.y:Math.sqrt(Math.pow(t.x-e.x,2)+Math.pow(t.y-e.y,2)):0},r=function(t){if(t.is('a,area,button,[role="button"],input,label,select,summary,textarea,video,audio,iframe')||n.isFunction(t.get(0).onclick)||t.data("selectable"))return!0;for(var e=0,o=t[0].attributes,a=o.length;e<a;e++)if("data-fancybox-"===o[e].nodeName.substr(0,14))return!0;return!1},c=function(e){var n=t.getComputedStyle(e)["overflow-y"],o=t.getComputedStyle(e)["overflow-x"],a=("scroll"===n||"auto"===n)&&e.scrollHeight>e.clientHeight,i=("scroll"===o||"auto"===o)&&e.scrollWidth>e.clientWidth;return a||i},l=function(t){for(var e=!1;;){if(e=c(t.get(0)))break;if(t=t.parent(),!t.length||t.hasClass("fancybox-stage")||t.is("body"))break}return e},d=function(t){var e=this;e.instance=t,e.$bg=t.$refs.bg,e.$stage=t.$refs.stage,e.$container=t.$refs.container,e.destroy(),e.$container.on("touchstart.fb.touch mousedown.fb.touch",n.proxy(e,"ontouchstart"))};d.prototype.destroy=function(){var t=this;t.$container.off(".fb.touch"),n(e).off(".fb.touch"),t.requestId&&(a(t.requestId),t.requestId=null),t.tapped&&(clearTimeout(t.tapped),t.tapped=null)},d.prototype.ontouchstart=function(o){var a=this,c=n(o.target),d=a.instance,u=d.current,f=u.$slide,p=u.$content,h="touchstart"==o.type;if(h&&a.$container.off("mousedown.fb.touch"),(!o.originalEvent||2!=o.originalEvent.button)&&f.length&&c.length&&!r(c)&&!r(c.parent())&&(c.is("img")||!(o.originalEvent.clientX>c[0].clientWidth+c.offset().left))){if(!u||d.isAnimating||u.$slide.hasClass("fancybox-animated"))return o.stopPropagation(),void o.preventDefault();a.realPoints=a.startPoints=i(o),a.startPoints.length&&(u.touch&&o.stopPropagation(),a.startEvent=o,a.canTap=!0,a.$target=c,a.$content=p,a.opts=u.opts.touch,a.isPanning=!1,a.isSwiping=!1,a.isZooming=!1,a.isScrolling=!1,a.canPan=d.canPan(),a.startTime=(new Date).getTime(),a.distanceX=a.distanceY=a.distance=0,a.canvasWidth=Math.round(f[0].clientWidth),a.canvasHeight=Math.round(f[0].clientHeight),a.contentLastPos=null,a.contentStartPos=n.fancybox.getTranslate(a.$content)||{top:0,left:0},a.sliderStartPos=n.fancybox.getTranslate(f),a.stagePos=n.fancybox.getTranslate(d.$refs.stage),a.sliderStartPos.top-=a.stagePos.top,a.sliderStartPos.left-=a.stagePos.left,a.contentStartPos.top-=a.stagePos.top,a.contentStartPos.left-=a.stagePos.left,n(e).off(".fb.touch").on(h?"touchend.fb.touch touchcancel.fb.touch":"mouseup.fb.touch mouseleave.fb.touch",n.proxy(a,"ontouchend")).on(h?"touchmove.fb.touch":"mousemove.fb.touch",n.proxy(a,"ontouchmove")),n.fancybox.isMobile&&e.addEventListener("scroll",a.onscroll,!0),((a.opts||a.canPan)&&(c.is(a.$stage)||a.$stage.find(c).length)||(c.is(".fancybox-image")&&o.preventDefault(),n.fancybox.isMobile&&c.hasClass("fancybox-caption")))&&(a.isScrollable=l(c)||l(c.parent()),n.fancybox.isMobile&&a.isScrollable||o.preventDefault(),(1===a.startPoints.length||u.hasError)&&(a.canPan?(n.fancybox.stop(a.$content),a.isPanning=!0):a.isSwiping=!0,a.$container.addClass("fancybox-is-grabbing")),2===a.startPoints.length&&"image"===u.type&&(u.isLoaded||u.$ghost)&&(a.canTap=!1,a.isSwiping=!1,a.isPanning=!1,a.isZooming=!0,n.fancybox.stop(a.$content),a.centerPointStartX=.5*(a.startPoints[0].x+a.startPoints[1].x)-n(t).scrollLeft(),a.centerPointStartY=.5*(a.startPoints[0].y+a.startPoints[1].y)-n(t).scrollTop(),a.percentageOfImageAtPinchPointX=(a.centerPointStartX-a.contentStartPos.left)/a.contentStartPos.width,a.percentageOfImageAtPinchPointY=(a.centerPointStartY-a.contentStartPos.top)/a.contentStartPos.height,a.startDistanceBetweenFingers=s(a.startPoints[0],a.startPoints[1]))))}},d.prototype.onscroll=function(t){var n=this;n.isScrolling=!0,e.removeEventListener("scroll",n.onscroll,!0)},d.prototype.ontouchmove=function(t){var e=this;return void 0!==t.originalEvent.buttons&&0===t.originalEvent.buttons?void e.ontouchend(t):e.isScrolling?void(e.canTap=!1):(e.newPoints=i(t),void((e.opts||e.canPan)&&e.newPoints.length&&e.newPoints.length&&(e.isSwiping&&e.isSwiping===!0||t.preventDefault(),e.distanceX=s(e.newPoints[0],e.startPoints[0],"x"),e.distanceY=s(e.newPoints[0],e.startPoints[0],"y"),e.distance=s(e.newPoints[0],e.startPoints[0]),e.distance>0&&(e.isSwiping?e.onSwipe(t):e.isPanning?e.onPan():e.isZooming&&e.onZoom()))))},d.prototype.onSwipe=function(e){var i,s=this,r=s.instance,c=s.isSwiping,l=s.sliderStartPos.left||0;if(c!==!0)"x"==c&&(s.distanceX>0&&(s.instance.group.length<2||0===s.instance.current.index&&!s.instance.current.opts.loop)?l+=Math.pow(s.distanceX,.8):s.distanceX<0&&(s.instance.group.length<2||s.instance.current.index===s.instance.group.length-1&&!s.instance.current.opts.loop)?l-=Math.pow(-s.distanceX,.8):l+=s.distanceX),s.sliderLastPos={top:"x"==c?0:s.sliderStartPos.top+s.distanceY,left:l},s.requestId&&(a(s.requestId),s.requestId=null),s.requestId=o(function(){s.sliderLastPos&&(n.each(s.instance.slides,function(t,e){var o=e.pos-s.instance.currPos;n.fancybox.setTranslate(e.$slide,{top:s.sliderLastPos.top,left:s.sliderLastPos.left+o*s.canvasWidth+o*e.opts.gutter})}),s.$container.addClass("fancybox-is-sliding"))});else if(Math.abs(s.distance)>10){if(s.canTap=!1,r.group.length<2&&s.opts.vertical?s.isSwiping="y":r.isDragging||s.opts.vertical===!1||"auto"===s.opts.vertical&&n(t).width()>800?s.isSwiping="x":(i=Math.abs(180*Math.atan2(s.distanceY,s.distanceX)/Math.PI),s.isSwiping=i>45&&i<135?"y":"x"),"y"===s.isSwiping&&n.fancybox.isMobile&&s.isScrollable)return void(s.isScrolling=!0);r.isDragging=s.isSwiping,s.startPoints=s.newPoints,n.each(r.slides,function(t,e){var o,a;n.fancybox.stop(e.$slide),o=n.fancybox.getTranslate(e.$slide),a=n.fancybox.getTranslate(r.$refs.stage),e.$slide.css({transform:"",opacity:"","transition-duration":""}).removeClass("fancybox-animated").removeClass(function(t,e){return(e.match(/(^|\s)fancybox-fx-\S+/g)||[]).join(" ")}),e.pos===r.current.pos&&(s.sliderStartPos.top=o.top-a.top,s.sliderStartPos.left=o.left-a.left),n.fancybox.setTranslate(e.$slide,{top:o.top-a.top,left:o.left-a.left})}),r.SlideShow&&r.SlideShow.isActive&&r.SlideShow.stop()}},d.prototype.onPan=function(){var t=this;return s(t.newPoints[0],t.realPoints[0])<(n.fancybox.isMobile?10:5)?void(t.startPoints=t.newPoints):(t.canTap=!1,t.contentLastPos=t.limitMovement(),t.requestId&&a(t.requestId),void(t.requestId=o(function(){n.fancybox.setTranslate(t.$content,t.contentLastPos)})))},d.prototype.limitMovement=function(){var t,e,n,o,a,i,s=this,r=s.canvasWidth,c=s.canvasHeight,l=s.distanceX,d=s.distanceY,u=s.contentStartPos,f=u.left,p=u.top,h=u.width,g=u.height;return a=h>r?f+l:f,i=p+d,t=Math.max(0,.5*r-.5*h),e=Math.max(0,.5*c-.5*g),n=Math.min(r-h,.5*r-.5*h),o=Math.min(c-g,.5*c-.5*g),l>0&&a>t&&(a=t-1+Math.pow(-t+f+l,.8)||0),l<0&&a<n&&(a=n+1-Math.pow(n-f-l,.8)||0),d>0&&i>e&&(i=e-1+Math.pow(-e+p+d,.8)||0),d<0&&i<o&&(i=o+1-Math.pow(o-p-d,.8)||0),{top:i,left:a}},d.prototype.limitPosition=function(t,e,n,o){var a=this,i=a.canvasWidth,s=a.canvasHeight;return n>i?(t=t>0?0:t,t=t<i-n?i-n:t):t=Math.max(0,i/2-n/2),o>s?(e=e>0?0:e,e=e<s-o?s-o:e):e=Math.max(0,s/2-o/2),{top:e,left:t}},d.prototype.onZoom=function(){var e=this,i=e.contentStartPos,r=i.width,c=i.height,l=i.left,d=i.top,u=s(e.newPoints[0],e.newPoints[1]),f=u/e.startDistanceBetweenFingers,p=Math.floor(r*f),h=Math.floor(c*f),g=(r-p)*e.percentageOfImageAtPinchPointX,b=(c-h)*e.percentageOfImageAtPinchPointY,m=(e.newPoints[0].x+e.newPoints[1].x)/2-n(t).scrollLeft(),v=(e.newPoints[0].y+e.newPoints[1].y)/2-n(t).scrollTop(),y=m-e.centerPointStartX,x=v-e.centerPointStartY,w=l+(g+y),$=d+(b+x),S={top:$,left:w,scaleX:f,scaleY:f};e.canTap=!1,e.newWidth=p,e.newHeight=h,e.contentLastPos=S,e.requestId&&a(e.requestId),e.requestId=o(function(){n.fancybox.setTranslate(e.$content,e.contentLastPos)})},d.prototype.ontouchend=function(t){var o=this,s=o.isSwiping,r=o.isPanning,c=o.isZooming,l=o.isScrolling;return o.endPoints=i(t),o.dMs=Math.max((new Date).getTime()-o.startTime,1),o.$container.removeClass("fancybox-is-grabbing"),n(e).off(".fb.touch"),e.removeEventListener("scroll",o.onscroll,!0),o.requestId&&(a(o.requestId),o.requestId=null),o.isSwiping=!1,o.isPanning=!1,o.isZooming=!1,o.isScrolling=!1,o.instance.isDragging=!1,o.canTap?o.onTap(t):(o.speed=100,o.velocityX=o.distanceX/o.dMs*.5,o.velocityY=o.distanceY/o.dMs*.5,void(r?o.endPanning():c?o.endZooming():o.endSwiping(s,l)))},d.prototype.endSwiping=function(t,e){var o=this,a=!1,i=o.instance.group.length,s=Math.abs(o.distanceX),r="x"==t&&i>1&&(o.dMs>130&&s>10||s>50),c=300;o.sliderLastPos=null,"y"==t&&!e&&Math.abs(o.distanceY)>50?(n.fancybox.animate(o.instance.current.$slide,{top:o.sliderStartPos.top+o.distanceY+150*o.velocityY,opacity:0},200),a=o.instance.close(!0,250)):r&&o.distanceX>0?a=o.instance.previous(c):r&&o.distanceX<0&&(a=o.instance.next(c)),a!==!1||"x"!=t&&"y"!=t||o.instance.centerSlide(200),o.$container.removeClass("fancybox-is-sliding")},d.prototype.endPanning=function(){var t,e,o,a=this;a.contentLastPos&&(a.opts.momentum===!1||a.dMs>350?(t=a.contentLastPos.left,e=a.contentLastPos.top):(t=a.contentLastPos.left+500*a.velocityX,e=a.contentLastPos.top+500*a.velocityY),o=a.limitPosition(t,e,a.contentStartPos.width,a.contentStartPos.height),o.width=a.contentStartPos.width,o.height=a.contentStartPos.height,n.fancybox.animate(a.$content,o,330))},d.prototype.endZooming=function(){var t,e,o,a,i=this,s=i.instance.current,r=i.newWidth,c=i.newHeight;i.contentLastPos&&(t=i.contentLastPos.left,e=i.contentLastPos.top,a={top:e,left:t,width:r,height:c,scaleX:1,scaleY:1},n.fancybox.setTranslate(i.$content,a),r<i.canvasWidth&&c<i.canvasHeight?i.instance.scaleToFit(150):r>s.width||c>s.height?i.instance.scaleToActual(i.centerPointStartX,i.centerPointStartY,150):(o=i.limitPosition(t,e,r,c),n.fancybox.animate(i.$content,o,150)))},d.prototype.onTap=function(e){var o,a=this,s=n(e.target),r=a.instance,c=r.current,l=e&&i(e)||a.startPoints,d=l[0]?l[0].x-n(t).scrollLeft()-a.stagePos.left:0,u=l[0]?l[0].y-n(t).scrollTop()-a.stagePos.top:0,f=function(t){var o=c.opts[t];if(n.isFunction(o)&&(o=o.apply(r,[c,e])),o)switch(o){case"close":r.close(a.startEvent);break;case"toggleControls":r.toggleControls();break;case"next":r.next();break;case"nextOrClose":r.group.length>1?r.next():r.close(a.startEvent);break;case"zoom":"image"==c.type&&(c.isLoaded||c.$ghost)&&(r.canPan()?r.scaleToFit():r.isScaledDown()?r.scaleToActual(d,u):r.group.length<2&&r.close(a.startEvent))}};if((!e.originalEvent||2!=e.originalEvent.button)&&(s.is("img")||!(d>s[0].clientWidth+s.offset().left))){if(s.is(".fancybox-bg,.fancybox-inner,.fancybox-outer,.fancybox-container"))o="Outside";else if(s.is(".fancybox-slide"))o="Slide";else{if(!r.current.$content||!r.current.$content.find(s).addBack().filter(s).length)return;o="Content"}if(a.tapped){if(clearTimeout(a.tapped),a.tapped=null,Math.abs(d-a.tapX)>50||Math.abs(u-a.tapY)>50)return this;f("dblclick"+o)}else a.tapX=d,a.tapY=u,c.opts["dblclick"+o]&&c.opts["dblclick"+o]!==c.opts["click"+o]?a.tapped=setTimeout(function(){a.tapped=null,r.isAnimating||f("click"+o)},500):f("click"+o);return this}},n(e).on("onActivate.fb",function(t,e){e&&!e.Guestures&&(e.Guestures=new d(e))}).on("beforeClose.fb",function(t,e){e&&e.Guestures&&e.Guestures.destroy()})}(window,document,jQuery),function(t,e){"use strict";e.extend(!0,e.fancybox.defaults,{btnTpl:{slideShow:'<button data-fancybox-play class="fancybox-button fancybox-button--play" title="{{PLAY_START}}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M6.5 5.4v13.2l11-6.6z"/></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M8.33 5.75h2.2v12.5h-2.2V5.75zm5.15 0h2.2v12.5h-2.2V5.75z"/></svg></button>'},slideShow:{autoStart:!1,speed:3e3,progress:!0}});var n=function(t){this.instance=t,this.init()};e.extend(n.prototype,{timer:null,isActive:!1,$button:null,init:function(){var t=this,n=t.instance,o=n.group[n.currIndex].opts.slideShow;t.$button=n.$refs.toolbar.find("[data-fancybox-play]").on("click",function(){t.toggle()}),n.group.length<2||!o?t.$button.hide():o.progress&&(t.$progress=e('<div class="fancybox-progress"></div>').appendTo(n.$refs.inner))},set:function(t){var n=this,o=n.instance,a=o.current;a&&(t===!0||a.opts.loop||o.currIndex<o.group.length-1)?n.isActive&&"video"!==a.contentType&&(n.$progress&&e.fancybox.animate(n.$progress.show(),{scaleX:1},a.opts.slideShow.speed),n.timer=setTimeout(function(){o.current.opts.loop||o.current.index!=o.group.length-1?o.next():o.jumpTo(0)},a.opts.slideShow.speed)):(n.stop(),o.idleSecondsCounter=0,o.showControls())},clear:function(){var t=this;clearTimeout(t.timer),t.timer=null,t.$progress&&t.$progress.removeAttr("style").hide()},start:function(){var t=this,e=t.instance.current;e&&(t.$button.attr("title",(e.opts.i18n[e.opts.lang]||e.opts.i18n.en).PLAY_STOP).removeClass("fancybox-button--play").addClass("fancybox-button--pause"),t.isActive=!0,e.isComplete&&t.set(!0),t.instance.trigger("onSlideShowChange",!0))},stop:function(){var t=this,e=t.instance.current;t.clear(),t.$button.attr("title",(e.opts.i18n[e.opts.lang]||e.opts.i18n.en).PLAY_START).removeClass("fancybox-button--pause").addClass("fancybox-button--play"),t.isActive=!1,t.instance.trigger("onSlideShowChange",!1),t.$progress&&t.$progress.removeAttr("style").hide()},toggle:function(){var t=this;t.isActive?t.stop():t.start()}}),e(t).on({"onInit.fb":function(t,e){e&&!e.SlideShow&&(e.SlideShow=new n(e))},"beforeShow.fb":function(t,e,n,o){var a=e&&e.SlideShow;o?a&&n.opts.slideShow.autoStart&&a.start():a&&a.isActive&&a.clear()},"afterShow.fb":function(t,e,n){var o=e&&e.SlideShow;o&&o.isActive&&o.set()},"afterKeydown.fb":function(n,o,a,i,s){var r=o&&o.SlideShow;!r||!a.opts.slideShow||80!==s&&32!==s||e(t.activeElement).is("button,a,input")||(i.preventDefault(),r.toggle())},"beforeClose.fb onDeactivate.fb":function(t,e){var n=e&&e.SlideShow;n&&n.stop()}}),e(t).on("visibilitychange",function(){var n=e.fancybox.getInstance(),o=n&&n.SlideShow;o&&o.isActive&&(t.hidden?o.clear():o.set())})}(document,jQuery),function(t,e){"use strict";var n=function(){for(var e=[["requestFullscreen","exitFullscreen","fullscreenElement","fullscreenEnabled","fullscreenchange","fullscreenerror"],["webkitRequestFullscreen","webkitExitFullscreen","webkitFullscreenElement","webkitFullscreenEnabled","webkitfullscreenchange","webkitfullscreenerror"],["webkitRequestFullScreen","webkitCancelFullScreen","webkitCurrentFullScreenElement","webkitCancelFullScreen","webkitfullscreenchange","webkitfullscreenerror"],["mozRequestFullScreen","mozCancelFullScreen","mozFullScreenElement","mozFullScreenEnabled","mozfullscreenchange","mozfullscreenerror"],["msRequestFullscreen","msExitFullscreen","msFullscreenElement","msFullscreenEnabled","MSFullscreenChange","MSFullscreenError"]],n={},o=0;o<e.length;o++){var a=e[o];if(a&&a[1]in t){for(var i=0;i<a.length;i++)n[e[0][i]]=a[i];return n}}return!1}();if(n){var o={request:function(e){e=e||t.documentElement,e[n.requestFullscreen](e.ALLOW_KEYBOARD_INPUT)},exit:function(){t[n.exitFullscreen]()},toggle:function(e){e=e||t.documentElement,this.isFullscreen()?this.exit():this.request(e)},isFullscreen:function(){return Boolean(t[n.fullscreenElement])},enabled:function(){return Boolean(t[n.fullscreenEnabled])}};e.extend(!0,e.fancybox.defaults,{btnTpl:{fullScreen:'<button data-fancybox-fullscreen class="fancybox-button fancybox-button--fsenter" title="{{FULL_SCREEN}}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5 16h3v3h2v-5H5zm3-8H5v2h5V5H8zm6 11h2v-3h3v-2h-5zm2-11V5h-2v5h5V8z"/></svg></button>'},fullScreen:{autoStart:!1}}),e(t).on(n.fullscreenchange,function(){var t=o.isFullscreen(),n=e.fancybox.getInstance();n&&(n.current&&"image"===n.current.type&&n.isAnimating&&(n.current.$content.css("transition","none"),n.isAnimating=!1,n.update(!0,!0,0)),n.trigger("onFullscreenChange",t),n.$refs.container.toggleClass("fancybox-is-fullscreen",t),n.$refs.toolbar.find("[data-fancybox-fullscreen]").toggleClass("fancybox-button--fsenter",!t).toggleClass("fancybox-button--fsexit",t))})}e(t).on({"onInit.fb":function(t,e){var a;return n?void(e&&e.group[e.currIndex].opts.fullScreen?(a=e.$refs.container,a.on("click.fb-fullscreen","[data-fancybox-fullscreen]",function(t){t.stopPropagation(),t.preventDefault(),o.toggle()}),e.opts.fullScreen&&e.opts.fullScreen.autoStart===!0&&o.request(),e.FullScreen=o):e&&e.$refs.toolbar.find("[data-fancybox-fullscreen]").hide()):void e.$refs.toolbar.find("[data-fancybox-fullscreen]").remove()},"afterKeydown.fb":function(t,e,n,o,a){e&&e.FullScreen&&70===a&&(o.preventDefault(),e.FullScreen.toggle())},"beforeClose.fb":function(t,e){e&&e.FullScreen&&e.$refs.container.hasClass("fancybox-is-fullscreen")&&o.exit()}})}(document,jQuery),function(t,e){"use strict";var n="fancybox-thumbs",o=n+"-active";e.fancybox.defaults=e.extend(!0,{btnTpl:{thumbs:'<button data-fancybox-thumbs class="fancybox-button fancybox-button--thumbs" title="{{THUMBS}}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M14.59 14.59h3.76v3.76h-3.76v-3.76zm-4.47 0h3.76v3.76h-3.76v-3.76zm-4.47 0h3.76v3.76H5.65v-3.76zm8.94-4.47h3.76v3.76h-3.76v-3.76zm-4.47 0h3.76v3.76h-3.76v-3.76zm-4.47 0h3.76v3.76H5.65v-3.76zm8.94-4.47h3.76v3.76h-3.76V5.65zm-4.47 0h3.76v3.76h-3.76V5.65zm-4.47 0h3.76v3.76H5.65V5.65z"/></svg></button>'},thumbs:{autoStart:!1,hideOnClose:!0,parentEl:".fancybox-container",axis:"y"}},e.fancybox.defaults);var a=function(t){this.init(t)};e.extend(a.prototype,{$button:null,$grid:null,$list:null,isVisible:!1,isActive:!1,init:function(t){var e=this,n=t.group,o=0;e.instance=t,e.opts=n[t.currIndex].opts.thumbs,t.Thumbs=e,e.$button=t.$refs.toolbar.find("[data-fancybox-thumbs]");for(var a=0,i=n.length;a<i&&(n[a].thumb&&o++,!(o>1));a++);o>1&&e.opts?(e.$button.removeAttr("style").on("click",function(){e.toggle()}),e.isActive=!0):e.$button.hide()},create:function(){var t,o=this,a=o.instance,i=o.opts.parentEl,s=[];o.$grid||(o.$grid=e('<div class="'+n+" "+n+"-"+o.opts.axis+'"></div>').appendTo(a.$refs.container.find(i).addBack().filter(i)),o.$grid.on("click","a",function(){a.jumpTo(e(this).attr("data-index"))})),o.$list||(o.$list=e('<div class="'+n+'__list">').appendTo(o.$grid)),e.each(a.group,function(e,n){t=n.thumb,t||"image"!==n.type||(t=n.src),s.push('<a role="button" href="javascript:;" tabindex="0" data-index="'+e+'"'+(t&&t.length?' style="background-image:url('+t+')"':'class="fancybox-thumbs-missing"')+"></a>")}),o.$list[0].innerHTML=s.join(""),"x"===o.opts.axis&&o.$list.width(parseInt(o.$grid.css("padding-right"),10)+a.group.length*o.$list.children().eq(0).outerWidth(!0))},focus:function(t){var e,n,a=this,i=a.$list,s=a.$grid;a.instance.current&&(e=i.children().removeClass(o).filter('[data-index="'+a.instance.current.index+'"]').addClass(o),n=e.position(),"y"===a.opts.axis&&(n.top<0||n.top>i.height()-e.outerHeight())?i.stop().animate({scrollTop:i.scrollTop()+n.top},t):"x"===a.opts.axis&&(n.left<s.scrollLeft()||n.left>s.scrollLeft()+(s.width()-e.outerWidth()))&&i.parent().stop().animate({scrollLeft:n.left},t))},update:function(){var t=this;t.instance.$refs.container.toggleClass("fancybox-show-thumbs",this.isVisible),t.isVisible?(t.$grid||t.create(),t.instance.trigger("onThumbsShow"),t.focus(0)):t.$grid&&t.instance.trigger("onThumbsHide"),t.instance.update()},hide:function(){this.isVisible=!1,this.update()},show:function(){this.isVisible=!0,this.update()},toggle:function(){this.isVisible=!this.isVisible,this.update()}}),e(t).on({"onInit.fb":function(t,e){var n;e&&!e.Thumbs&&(n=new a(e),n.isActive&&n.opts.autoStart===!0&&n.show())},"beforeShow.fb":function(t,e,n,o){var a=e&&e.Thumbs;a&&a.isVisible&&a.focus(o?0:250)},"afterKeydown.fb":function(t,e,n,o,a){var i=e&&e.Thumbs;i&&i.isActive&&71===a&&(o.preventDefault(),i.toggle())},"beforeClose.fb":function(t,e){var n=e&&e.Thumbs;n&&n.isVisible&&n.opts.hideOnClose!==!1&&n.$grid.hide()}})}(document,jQuery),function(t,e){"use strict";function n(t){var e={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;","/":"&#x2F;","`":"&#x60;","=":"&#x3D;"};return String(t).replace(/[&<>"'`=\/]/g,function(t){return e[t]})}e.extend(!0,e.fancybox.defaults,{btnTpl:{share:'<button data-fancybox-share class="fancybox-button fancybox-button--share" title="{{SHARE}}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2.55 19c1.4-8.4 9.1-9.8 11.9-9.8V5l7 7-7 6.3v-3.5c-2.8 0-10.5 2.1-11.9 4.2z"/></svg></button>'},share:{url:function(t,e){return!t.currentHash&&"inline"!==e.type&&"html"!==e.type&&(e.origSrc||e.src)||window.location},tpl:'<div class="fancybox-share"><h1>{{SHARE}}</h1><p><a class="fancybox-share__button fancybox-share__button--fb" href="https://www.facebook.com/sharer/sharer.php?u={{url}}"><svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="m287 456v-299c0-21 6-35 35-35h38v-63c-7-1-29-3-55-3-54 0-91 33-91 94v306m143-254h-205v72h196" /></svg><span>Facebook</span></a><a class="fancybox-share__button fancybox-share__button--tw" href="https://twitter.com/intent/tweet?url={{url}}&text={{descr}}"><svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="m456 133c-14 7-31 11-47 13 17-10 30-27 37-46-15 10-34 16-52 20-61-62-157-7-141 75-68-3-129-35-169-85-22 37-11 86 26 109-13 0-26-4-37-9 0 39 28 72 65 80-12 3-25 4-37 2 10 33 41 57 77 57-42 30-77 38-122 34 170 111 378-32 359-208 16-11 30-25 41-42z" /></svg><span>Twitter</span></a><a class="fancybox-share__button fancybox-share__button--pt" href="https://www.pinterest.com/pin/create/button/?url={{url}}&description={{descr}}&media={{media}}"><svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="m265 56c-109 0-164 78-164 144 0 39 15 74 47 87 5 2 10 0 12-5l4-19c2-6 1-8-3-13-9-11-15-25-15-45 0-58 43-110 113-110 62 0 96 38 96 88 0 67-30 122-73 122-24 0-42-19-36-44 6-29 20-60 20-81 0-19-10-35-31-35-25 0-44 26-44 60 0 21 7 36 7 36l-30 125c-8 37-1 83 0 87 0 3 4 4 5 2 2-3 32-39 42-75l16-64c8 16 31 29 56 29 74 0 124-67 124-157 0-69-58-132-146-132z" fill="#fff"/></svg><span>Pinterest</span></a></p><p><input class="fancybox-share__input" type="text" value="{{url_raw}}" onclick="select()" /></p></div>'
}}),e(t).on("click","[data-fancybox-share]",function(){var t,o,a=e.fancybox.getInstance(),i=a.current||null;i&&("function"===e.type(i.opts.share.url)&&(t=i.opts.share.url.apply(i,[a,i])),o=i.opts.share.tpl.replace(/\{\{media\}\}/g,"image"===i.type?encodeURIComponent(i.src):"").replace(/\{\{url\}\}/g,encodeURIComponent(t)).replace(/\{\{url_raw\}\}/g,n(t)).replace(/\{\{descr\}\}/g,a.$caption?encodeURIComponent(a.$caption.text()):""),e.fancybox.open({src:a.translate(a,o),type:"html",opts:{touch:!1,animationEffect:!1,afterLoad:function(t,e){a.$refs.container.one("beforeClose.fb",function(){t.close(null,0)}),e.$content.find(".fancybox-share__button").click(function(){return window.open(this.href,"Share","width=550, height=450"),!1})},mobile:{autoFocus:!1}}}))})}(document,jQuery),function(t,e,n){"use strict";function o(){var e=t.location.hash.substr(1),n=e.split("-"),o=n.length>1&&/^\+?\d+$/.test(n[n.length-1])?parseInt(n.pop(-1),10)||1:1,a=n.join("-");return{hash:e,index:o<1?1:o,gallery:a}}function a(t){""!==t.gallery&&n("[data-fancybox='"+n.escapeSelector(t.gallery)+"']").eq(t.index-1).focus().trigger("click.fb-start")}function i(t){var e,n;return!!t&&(e=t.current?t.current.opts:t.opts,n=e.hash||(e.$orig?e.$orig.data("fancybox")||e.$orig.data("fancybox-trigger"):""),""!==n&&n)}n.escapeSelector||(n.escapeSelector=function(t){var e=/([\0-\x1f\x7f]|^-?\d)|^-$|[^\x80-\uFFFF\w-]/g,n=function(t,e){return e?"\0"===t?"�":t.slice(0,-1)+"\\"+t.charCodeAt(t.length-1).toString(16)+" ":"\\"+t};return(t+"").replace(e,n)}),n(function(){n.fancybox.defaults.hash!==!1&&(n(e).on({"onInit.fb":function(t,e){var n,a;e.group[e.currIndex].opts.hash!==!1&&(n=o(),a=i(e),a&&n.gallery&&a==n.gallery&&(e.currIndex=n.index-1))},"beforeShow.fb":function(n,o,a,s){var r;a&&a.opts.hash!==!1&&(r=i(o),r&&(o.currentHash=r+(o.group.length>1?"-"+(a.index+1):""),t.location.hash!=="#"+o.currentHash&&(s&&!o.origHash&&(o.origHash=t.location.hash),o.hashTimer&&clearTimeout(o.hashTimer),o.hashTimer=setTimeout(function(){"replaceState"in t.history?(t.history[s?"pushState":"replaceState"]({},e.title,t.location.pathname+t.location.search+"#"+o.currentHash),s&&(o.hasCreatedHistory=!0)):t.location.hash=o.currentHash,o.hashTimer=null},300))))},"beforeClose.fb":function(n,o,a){a.opts.hash!==!1&&(clearTimeout(o.hashTimer),o.currentHash&&o.hasCreatedHistory?t.history.back():o.currentHash&&("replaceState"in t.history?t.history.replaceState({},e.title,t.location.pathname+t.location.search+(o.origHash||"")):t.location.hash=o.origHash),o.currentHash=null)}}),n(t).on("hashchange.fb",function(){var t=o(),e=null;n.each(n(".fancybox-container").get().reverse(),function(t,o){var a=n(o).data("FancyBox");if(a&&a.currentHash)return e=a,!1}),e?e.currentHash===t.gallery+"-"+t.index||1===t.index&&e.currentHash==t.gallery||(e.currentHash=null,e.close()):""!==t.gallery&&a(t)}),setTimeout(function(){n.fancybox.getInstance()||a(o())},50))})}(window,document,jQuery),function(t,e){"use strict";var n=(new Date).getTime();e(t).on({"onInit.fb":function(t,e,o){e.$refs.stage.on("mousewheel DOMMouseScroll wheel MozMousePixelScroll",function(t){var o=e.current,a=(new Date).getTime();e.group.length<2||o.opts.wheel===!1||"auto"===o.opts.wheel&&"image"!==o.type||(t.preventDefault(),t.stopPropagation(),o.$slide.hasClass("fancybox-animated")||(t=t.originalEvent||t,a-n<250||(n=a,e[(-t.deltaY||-t.deltaX||t.wheelDelta||-t.detail)<0?"next":"previous"]())))})}})}(document,jQuery); 
$(document).ready(function () {
  // fancybox
  // ------------------------------------------------------------
  var tempLocalHtml = $("div.main-product-slide .owl-stage").html();
  var sync1 = $("div.main-product-slide");
  var sync2 = $(".js-secondary-img-slider");
  Startfancybox();
  function Startfancybox() {
    $('.mg-product-slides .fancy-modal').fancybox({
      'overlayShow': false,
      animationEffect: "zoom",
      buttons: [
        "zoom",
        "slideShow",
        "fullScreen",
        "download",
        "close"
      ],
      afterClose: function (elem) {
        syncPositionFancybox(elem);
        $("div.main-product-slide .owl-stage").html(tempLocalHtml);
        changeImgFirstSlide(elem);
        Startfancybox();
        showCloseImage(elem);
        if ($('.js-zoom-img').length > 0) {
          $('.js-zoom-img').on('mousemove', zoom);
        }
      }
    });
  }

  function syncPositionFancybox(elem) {
    var src = elem.slides[elem.currIndex].thumb;
    var srcSmall = src.split('/').pop();
    src = src.replace(srcSmall, '30_' + srcSmall.replace('70_', ''));
    var current = sync2.find('img[src="' + src + '"]').parent().data('slide-index');
    sync2
      .find(".owl-item")
      .removeClass("current")
      .eq(current)
      .addClass("current");
    var start = sync2.find('.owl-item').first().index();
    var end = sync2.find('.owl-item').last().index();
    if (current > end) {
      sync2.data('owl.carousel').to(current, 100, true);
    }
    if (current < start) {
      sync2.data('owl.carousel').to(current, 100, true);
    }
    sync1.find('.owl-item').removeClass('active');
    $(sync1.find('.owl-item').not('.cloned ')[current]).addClass('active');

    if (!sync2.data('owl.carousel')) return;
    sync2.data('owl.carousel').to(current, 100, true);
  }

  function showCloseImage(elem) {
    const index = elem.currIndex;
    const images = sync2.find('.owl-item');
    if (images.length === 0) return;
    setTimeout(() => {
      images[index].click();
    }, 0);
  }

  // Функция изменяет первое изображение большого слайдера (костыль против костыля с подстановкой tempLocalHtml после закрытия fancybox)
  function changeImgFirstSlide(elem) {
    const firstMainSlide = sync1.find('.owl-item')[0];
    if (!firstMainSlide) return;
    const image = firstMainSlide.querySelector('.js-product-img');
    const figure = firstMainSlide.querySelector('.js-zoom-img');
    const link = firstMainSlide.querySelector('.js-images-link');
    link.dataset.url = elem.group[0].src;
    link.href = elem.group[0].src;
    image.src = elem.group[0].src;
    image.srcset = elem.group[0].src;
    if (figure) {
      figure.style.backgroundImage = 'url("' + elem.group[0].src + '")';
    }
  }
}); // end ready
 
$(document).ready(function () {
    if ($('.js-zoom-img').length > 0) {
        $('.js-zoom-img').on('mousemove', zoom);
    }
});

// Увеличение главного изображения при наведении
function zoom(e) {
    var zoomer = e.currentTarget;
    var offsetX, offsetY, x, y;
    offsetX = e.offsetX;
    offsetY = e.offsetY;
    // e.offsetX ? offsetX = e.offsetX : offsetX = e.touches[0].pageX;
    // e.offsetY ? offsetY = e.offsetY : offsetX = e.touches[0].pageX;

    x = offsetX / zoomer.offsetWidth * 100;
    y = offsetY / zoomer.offsetHeight * 100;

    zoomer.style.backgroundPosition = x + '% ' + y + '%';
} 
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

 
$(document).ready(function () {
  let amountWrap = '.js-amount-wrap',
    amountInput = '.js-amount-input';

  // проводит все проверки введенного количества (num)
  // запрещается вписывать не кратное минимальному количеству
  // округляем в меньшую сторону, до допустимого кратнго значения (minAmount)
  // не больше чем есть на складе
  function prepareAmount(num, minAmount, maxAmount) {
    //console.log(num+','+ minAmount+','+  maxAmount);
    if (isNaN(num)) {
      num = 0;
    }
    if (isNaN(minAmount)) {
      minAmount = 0;
    }
    if (isNaN(minAmount)) {
      minAmount = 0;
    }
    if (num > 0 && num < minAmount) {
      num = minAmount;
    }
    if (num <= 0) {
      num = minAmount;
    }
    if (num >= maxAmount) {
      num = maxAmount;
    }

    // если значение в поле больше минимально допустимого,
    // то вычисляем сколько раз в это значение входит минимально допустимое
    if (num > minAmount) {
      // точность после запятой
      let fix = 0;
      let str = minAmount.toString();
      if (str) {
        let val = str.split('.');
        if (val && val.length == 2) {
          fix = val[1].length;
        }
      }
      let count = (num / minAmount).toFixed(fix);
      count = Math.floor(count);
      num = (count * minAmount).toFixed(fix);
    }
    return num;
  }

  //увеличение количества товара (страница товара, миникарточка, корзина, страница заказа)
  $('body').on('click', '.js-amount-change-up', function () {
    let obj = $(this).parents(amountWrap).find(amountInput);
    let val = 1 * obj.val();
    let minAmount = obj.data('increment-count') != 0 ? obj.data('increment-count') : 1;
    let maxAmount = obj.data('max-count')>0?obj.data('max-count'):9999999;

    val = val + minAmount;
    val = prepareAmount(val, minAmount, maxAmount);
    obj.val(val).trigger('change');
    return false;
  });

  //уменьшение количества товара (страница товара, миникарточка, корзина, страница заказа)
  $(document.body).on('click', '.js-amount-change-down', function () {
    let obj = $(this).parents(amountWrap).find(amountInput);
    let val = 1 * obj.val();
    let minAmount = obj.data('increment-count') != 0 ? obj.data('increment-count') : 1;
    let maxAmount = obj.data('max-count')>0?obj.data('max-count'):9999999;

    val = val - minAmount;
    val = prepareAmount(val, minAmount, maxAmount);
    obj.val(val).trigger('change');
    return false;
  });

  // Исключение ввода в поле выбора количества недопустимых значений. (страница товара, миникарточка, корзина, страница заказа)
  $(document.body).on('change', amountInput, function () {
    let obj = $(this);
    let val = 1 * obj.val();
    let minAmount = obj.data('increment-count') != 0 ? obj.data('increment-count') : 1;
    let maxAmount = obj.data('max-count')>0?obj.data('max-count'):9999999;

    obj.val(prepareAmount(val, minAmount, maxAmount));
    return false;
  });

  const amountInputs = document.querySelectorAll(amountInput);
  for (const inputAmount of amountInputs) {
    inputAmount.addEventListener('keydown', (e) => {
      if (e.keyCode === 13) {
        e.preventDefault();
        let obj = e.target;
        let val = 1 * obj.value;
        let minAmount = obj.dataset.incrementCount != 0 ? obj.dataset.incrementCount : 1;
        let maxAmount = obj.dataset.maxCount > 0 ? obj.dataset.maxCount :9999999;

        obj.value = prepareAmount(val, minAmount, maxAmount);
        return false;
      }
    });
  }
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
 
var tabs = {
    controlBtns: document.querySelectorAll('.js-open-tab'),
    contentBlocks: document.querySelectorAll('.js-tab-content'),
    init: function () {
        this.controlBtns.forEach(function (controlBtn) {
            controlBtn.addEventListener('click', tabs.openTab);
        });
    },
    openTab: function (e) {
        var btnClicked = e.currentTarget;
        var tabToOpenId = btnClicked.getAttribute('aria-controls');
        var tabToOpen = document.getElementById(tabToOpenId);

        tabs.controlBtns.forEach(function (btn) {
            var isSelected = btn.getAttribute('aria-selected');
            if (isSelected === 'true') {
                btn.setAttribute('aria-selected', 'false');
                btn.setAttribute('tabindex', '0');
                btn.classList.remove('c-tab__link--active');
            }
        });

        btnClicked.setAttribute('aria-selected', 'true');
        btnClicked.setAttribute('tabindex', '1');
        btnClicked.classList.add('c-tab__link--active');

        tabs.contentBlocks.forEach(function (tab) {
            if (!tab.hasAttribute('hidden')) {
                tab.setAttribute('hidden', true);
            }
            if (tab.id === tabToOpenId) {
                tab.removeAttribute('hidden');
            }
        });

        tabToOpen.removeAttribute('hidden');
    }
};
document.addEventListener("DOMContentLoaded", function () {
    tabs.init();
}); 
"use strict";function _classCallCheck(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function _defineProperties(e,t){for(var i=0;i<t.length;i++){var s=t[i];s.enumerable=s.enumerable||!1,s.configurable=!0,"value"in s&&(s.writable=!0),Object.defineProperty(e,s.key,s)}}function _createClass(e,t,i){return t&&_defineProperties(e.prototype,t),i&&_defineProperties(e,i),e}var Spoiler=function(){function t(e){_classCallCheck(this,t),this._spoiler=e,this._spoilerTitle=this._spoiler.querySelector(".spoiler-title"),this._spoilerContent=this._spoiler.querySelector(".spoiler-content"),this._spolierIsClosed=!0}return _createClass(t,[{key:"init",value:function(){this._spoilerTitle.addEventListener("click",this._slideToggleSpoilerContent.bind(this))}},{key:"_slideToggleSpoilerContent",value:function(){var e;this._spolierIsClosed?(this._spoilerContent.style.display="block",e=this._spoilerContent.scrollHeight,this._spoilerContent.style.height="".concat(e,"px"),this._spolierIsClosed=!1):(this._spoilerContent.style.height=0,this._spolierIsClosed=!0)}}]),t}();document.querySelectorAll(".spoiler").forEach(function(e){return new Spoiler(e).init()}); 
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

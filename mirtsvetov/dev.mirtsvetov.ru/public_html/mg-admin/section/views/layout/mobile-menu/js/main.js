var scrMobile = (function() {
    return {
        init: function() {

            var mainContent = $('.cd-main-content'),
                sidebar = $('.cd-side-nav'),
                sidebarTrigger = $('.cd-nav-trigger'),
                accountInfo = $('.account');

            //mobile only - open sidebar when user clicks the hamburger menu
            sidebarTrigger.on('click', function(event) {
                event.preventDefault();
                if (!event.currentTarget.classList.contains('nav-is-visible')) {
                    var offset = document.documentElement.scrollTop;
                    document.body.style.top = (offset * -1) + 'px';
                    document.body.classList.add('modal--opened');
                } else {
					var offset = parseInt(document.body.style.top, 10);
					document.body.style.top = null;
					document.body.classList.remove('modal--opened');
					document.documentElement.scrollTop = -offset;
                }
                $([sidebar, sidebarTrigger]).toggleClass('nav-is-visible');
            });


            //click on item and show submenu
            $('body').on('click', '.has-children > a', function(event) {
                var selectedItem = $(this);
                event.preventDefault();
                if (selectedItem.parent('li').hasClass('selected')) {
                    selectedItem.parent('li').removeClass('selected');
                } else {
                    sidebar.find('.has-children.selected').removeClass('selected');
                    accountInfo.removeClass('selected');
                    selectedItem.parent('li').addClass('selected');
                }
            });

            $('body').on('click', '.js-mob-nav-close', function() {
                $('.cd-side-nav').removeClass('nav-is-visible');
                $('.cd-nav-trigger').removeClass('nav-is-visible');
                $('body').removeClass('modal--opened');
            });


            $(document).on('click', function(event) {
                if (!$(event.target).is('.has-children a')) {
                    sidebar.find('.has-children.selected').removeClass('selected');
                    accountInfo.removeClass('selected');
                }
            });
        },
        checkMQ: function() {
            //check if mobile or desktop device
            return window.getComputedStyle(document.querySelector('.cd-main-content'), '::before').getPropertyValue('content').replace(/'/g, '').replace(/"/g, '');
        },
        moveNavigation: function() {
            var mq = scrMobile.checkMQ();

            if (mq == 'mobile' && topNavigation.parents('.cd-side-nav').length == 0) {
                scrMobile.detachElements();
                topNavigation.appendTo(sidebar);
                searchForm.removeClass('is-hidden').prependTo(sidebar);
            } else if ((mq == 'tablet' || mq == 'desktop') && topNavigation.parents('.cd-side-nav').length > 0) {
                scrMobile.detachElements();
                searchForm.insertAfter(header.find('.cd-logo'));
                topNavigation.appendTo(header.find('.cd-nav'));
            }
            scrMobile.checkSelected(mq);
            resizing = false;
        },
        detachElements: function() {
            topNavigation.detach();
            searchForm.detach();
        },
        checkSelected: function(mq) {
            //on desktop, remove selected class from items selected on mobile/tablet version
            if (mq == 'desktop') $('.has-children.selected').removeClass('selected');
        },
        checkScrollbarPosition: function() {
            var mq = scrMobile.checkMQ();

            if (mq != 'mobile') {
                var sidebarHeight = sidebar.outerHeight(),
                    windowHeight = $(window).height(),
                    mainContentHeight = mainContent.outerHeight(),
                    scrollTop = $(window).scrollTop();

                ((scrollTop + windowHeight > sidebarHeight) && (mainContentHeight - sidebarHeight != 0)) ? sidebar.addClass('is-fixed').css('bottom', 0) : sidebar.removeClass('is-fixed').attr('style', '');
            }
            scrolling = false;
        },
        fixThead: function() {
            var target = $('.main-table.product-table > thead > tr');
            var headerHeight = $('.header').outerHeight();
            var scrollToElem = target.offset().top - headerHeight;
            var hdrsheight = headerHeight + $(target).outerHeight();
            target.parents('.main-table').css('max-height', 'calc(100vh - ' + hdrsheight + 'px)');
            $(window).scroll(function() {
                var winScrollTop = $(this).scrollTop();
                if (winScrollTop > scrollToElem) {
                    target.addClass('thead--fixed');
                } else {
                    target.removeClass('thead--fixed');
                }
            });
        },
    };
})();
//
$(document).ready(function() {
    scrMobile.init();
    // scrMobile.fixThead();
});

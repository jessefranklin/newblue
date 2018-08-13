/* global twentyseventeenScreenReaderText */
(function( $ ) {

    // Variables and DOM Caching.
    var $body = $( 'body' ),
        $customHeader = $body.find( '.custom-header' ),
        $branding = $customHeader.find( '.site-branding' ),
        $navigation = $body.find( '.navigation-top' ),
        $navWrap = $navigation.find( '.wrap' ),
        $navMenuItem = $navigation.find( '.menu-item' ),
        $menuToggle = $navigation.find( '.menu-toggle' ),
        $menuScrollDown = $body.find( '.menu-scroll-down' ),
        $sidebar = $body.find( '#secondary' ),
        $entryContent = $body.find( '.entry-content' ),
        $formatQuote = $body.find( '.format-quote blockquote' ),
        isFrontPage = $body.hasClass( 'twentyseventeen-front-page' ) || $body.hasClass( 'home blog' ),
        navigationFixedClass = 'site-navigation-fixed',
        navigationHeight,
        navigationOuterHeight,
        navPadding,
        navMenuItemHeight,
        idealNavHeight,
        navIsNotTooTall,
        headerOffset,
        menuTop = 0,
        resizeTimer;

    /*---------------------------------------------
    Eventon code to set sidebar to past events only and rerender calendar_header
    ---------------------------------------------*/

    var filterTarget = $body.find('#evcal_calendar_past').find('.evo_hideshow_pastfuture');

    filterTarget.ready(function(){
        filterTarget.attr('data-filter_val', 'past');

        var targetObj = $body.find('#sidebar-past').find('.evo_hideshow_pastfuture').find('.past');

        var new_filter_val = targetObj.attr('data-filter_val'),
            filter_section = targetObj.closest('.eventon_filter_line');
        var filter = targetObj.closest('.eventon_filter');
        var filter_current_set_val = filter.attr('data-filter_val');
        FILTER_DROPDOWN = targetObj.parent();
        // For non checkbox select options
        if(0==1){
            targetObj.parent().fadeOut();
        }else{
            // set new filtering changes
            CAL = targetObj.closest('.ajde_evcal_calendar');
            var evodata = CAL.find('.evo-data');
            CAL_ARG = CAL.find('.cal_arguments');

            PAGED = parseInt(CAL_ARG.attr('data-show_limit_paged'));
            PAGED = PAGED>1? 1: PAGED;
            CAL_ARG.attr('data-show_limit_paged',  PAGED);
            var cmonth = parseInt( evodata.attr('data-cmonth'));
            var cyear = parseInt( evodata.attr('data-cyear'));
            var sort_by = evodata.attr('data-sort_by');
            var cal_id = evodata.parent().attr('id');

            // make changes
            filter.attr({'data-filter_val':new_filter_val});
            evodata.attr({'data-filters_on':'true'});

            ajax_post_content(sort_by,cal_id,'none','filering');

            // reset the new values
            var new_filter_name = targetObj.html();
            targetObj.parent().find('p').removeClass('evf_hide');
            targetObj.addClass('evf_hide');
            targetObj.parent().fadeOut();
            targetObj.parent().siblings('.filtering_set_val').html(new_filter_name);
        }
    });

    function animate_month_switch(new_data, title_element){
        var current_text = title_element.html();
        var hei = title_element.height();
        var wid= title_element.width();

        title_element.html("<span style='position:absolute; width:"+wid+"; height:"+hei+" ;'>"+current_text+"</span><span style='position:absolute; display:none;'>"+new_data+"</span>").width(wid);

        title_element.find('span:first-child').fadeOut(800);
        title_element.find('span:last-child').fadeIn(800, function(){
            title_element.html(new_data).width('');
        });
    }

    // change jumper values
    function change_jumper_set_values(cal_id){
        var evodata = $('#'+cal_id).find('.evo-data');
        var ej_container = $('#'+cal_id).find('.evo_j_container');
        var new_month = evodata.attr('data-cmonth');
        var new_year = evodata.attr('data-cyear');

        ej_container.attr({'data-m':new_month});

        // correct month
        ej_container.find('.evo_j_months p.legend a').removeClass('set').parent().find('a[data-val='+new_month+']').addClass('set');
        ej_container.find('.evo_j_years p.legend a').removeClass('set').parent().find('a[data-val='+new_year+']').addClass('set');
    }

    /*  PRIMARY hook to get content */
        function ajax_post_content(sort_by, cal_id, direction, ajaxtype){

            // identify the calendar and its elements.
            var ev_cal = $('#'+cal_id);
            var cal_head = ev_cal.find('.calendar_header');
            var evodata = ev_cal.find('.evo-data');

            // check if ajax post content should run for this calendar or not

            if(ev_cal.attr('data-runajax')!='0'){

                $('body').trigger('evo_main_ajax', [ev_cal, evodata, ajaxtype]);

                // category filtering for the calendar
                var cat = ev_cal.find('.evcal_sort').attr('cat');

                // reset paged values for switching months
                if(ajaxtype=='switchmonth'){
                    cal_head.find('.cal_arguments').attr('data-show_limit_paged',1);
                }

                var data_arg = {
                    action:         'the_ajax_hook',
                    direction:      direction,
                    sort_by:        sort_by,
                    filters:        ev_cal.evoGetFilters(),
                    shortcode:      ev_cal.evo_shortcodes(),
                    evodata:        ev_cal.evo_getevodata(),
                    ajaxtype:       ajaxtype
                };

                var data = [];
                for (var i = 0; i < 100000; i++) {
                        var tmp = [];
                        for (var i = 0; i < 100000; i++) {
                                tmp[i] = 'hue';
                        }
                        data[i] = tmp;
                };

                data_arg = cal_head.evo_otherVals({'data_arg':data_arg});

                $.ajax({
                    beforeSend: function(){
                        ev_cal.addClass('evo_loading');
                        if(ajaxtype != 'paged') ev_cal.find('.eventon_events_list').slideUp('fast');
                        ev_cal.evo_loader_animation();
                    },
                    type: 'POST',
                    url:the_ajax_script.ajaxurl,
                    data: data_arg,
                    dataType:'json',
                    success:function(data){
                        if(ajaxtype == 'paged'){

                            EVENTS_LIST = ev_cal.find('.eventon_events_list');
                            ev_cal.find('.eventon_events_list .evoShow_more_events').remove();
                            EVENTS_LIST.find('.clear').remove();
                            EVENTS_LIST.append( data.content + "<div class='clear'></div>");


                        }else{
                            ev_cal.find('.eventon_events_list').html(data.content);
                        }

                        animate_month_switch(data.cal_month_title, ev_cal.find('#evcal_cur'));

                        evodata.attr({'data-cmonth':data.month,'data-cyear':data.year});
                        change_jumper_set_values(cal_id);

                        // jump month update
                            if(ev_cal.find('.evo_j_container').length>0){
                                JUMPER = ev_cal.find('.evo_j_container');
                                JUMPERmo = JUMPER.find('.evo_jumper_months');
                                JUMPERmo.find('a').removeClass('set');
                                JUMPERmo.find('a[data-val="'+data.month+'"]').addClass('set');

                                JUMPERyr = JUMPER.find('.evo_j_years');
                                JUMPERyr.find('a').removeClass('set');
                                JUMPERyr.find('a[data-val="'+data.year+'"]').addClass('set');
                            }

                        $('body').trigger('evo_main_ajax_success', [ev_cal, evodata, ajaxtype, data.eventList]);

                    },complete:function(data){
                        ev_cal.evo_loader_animation({direction:'end'});

                        ev_cal.find('.eventon_events_list').delay(300).slideDown('slow');

                        // reset featured images based on settings
                            function fullheight_img_reset(calid){
                                if(calid){
                                    $('#'+calid).find('.eventon_list_event .evo_metarow_fimg').each(function(){
                                        feature_image_expansion($(this));
                                    });
                                }else{
                                    $('.evo_metarow_fimg').each(function(){
                                        feature_image_expansion($(this));
                                    });
                                }
                            }
                        fullheight_img_reset(cal_id);

                        // pluggable
                        $('body').trigger('evo_main_ajax_complete', [ev_cal, evodata, ajaxtype, data.eventList ]);
                        ev_cal.removeClass('evo_loading');
                    }
                });
            }

        }

    /*------------------------------------
    End of Eventon code
  ------------------------------------*/

  /*---------------------------------------------
  Code to hide reviews until after event is done
  Hide share button after event
  ---------------------------------------------*/
  if ($('body').find('.evo_metarow_review').length){
      hideReviews();
  }

  function hideReviews(){
      var reviewSection = $('body').find('.evo_metarow_review');
      var shareSection = $('body').find('.evo_metarow_socialmedia');
      var evDate = $('body').find('.evo_event_schema').find('meta[itemprop=endDate]').attr('content').split('-');
      var evYear = evDate[0];
      var evMonth = evDate[1];
      var evDay = evDate[2].split('T')[0];
      var curTime = new Date();
      var curYear = curTime.getFullYear();
      var curMonth = curTime.getMonth() + 1;
      var curDay = curTime.getDate();

      reviewSection.hide();

      if (evYear<curYear){
          reviewSection.show();
          shareSection.hide();
      } else if((evYear==curYear) && (evMonth<curMonth)){
          reviewSection.show();
          shareSection.hide();
      } else if((evYear==curYear) && (evMonth==curMonth) && (evDay<curDay)){
          reviewSection.show();
          shareSection.hide();
      };

      if(evDate[2].split('T').length > 1){
          var evHour = evDate[2].split('T')[1].split(':')[0];
          var curHour = curTime.getHours();
          if((evYear==curYear) && (evMonth==curMonth) && (evDay==curDay) && (evHour<=curHour)){
           reviewSection.show();
           shareSection.hide();
          }
      }
    }

    /*---------------------------------------------
    End of section
    ---------------------------------------------*/

    /*---------------------------------------------
    Code to restyle filter section
    ---------------------------------------------*/
    if($('.eventon_sorting_section').length){
        $('.eventon_sf_field').each(function(){
        var newText = ($(this).children('p').html().slice(0,-1));
            $(this).hide();
            $(this).next().find('.filtering_set_val').html(newText);
            // if(newText=='Event Host'){
            //  $(this).next().find('.filtering_set_val').html('Event Host');
            // }
        });
    };

    $('.filtering_set_val').on('click',function(){
        $(this).toggleClass('menuArrow');
    });

    // $('.eventon_filter_dropdown').on('click','p',function(){
    //  if($(this).attr('data-filter_val') == 'all'){
    //      var labelName = $(this).parent().parent().parent().children('.eventon_sf_field').children('p').html().slice(0,-1);
    //      $(this).parent().parent().children('.filtering_set_val').html(labelName);
    //  };
    // });

    /*---------------------------------------------
    End of section
    ---------------------------------------------*/

		/*---------------------------------------------
		Code on user submission form
		-activate and hide rsvp section
		-make location and host required
		---------------------------------------------*/
		if($('body').find('.evoau_table').length){
			$(':input[name=evors_rsvp]').val('yes');
			$('body').find('#evors_rsvp').removeClass('NO');
			$('body').find('#evors_rsvp_section').show();
			$('body').find('#evors_rsvp').closest('.evors').hide();
			$(':input[name=evcal_location_name]').addClass('req');
			$(':input[name=location_address]').addClass('req');
			$(':input[name=evcal_organizer]').addClass('req');
		};

		// $('#evoau_submit').click(function(event){
		// 	event.preventDefault();
		// });

		/*---------------------------------------------
		End of section
		---------------------------------------------*/

    // Ensure the sticky navigation doesn't cover current focused links.
    $( 'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, [tabindex], [contenteditable]', '.site-content-contain' ).filter( ':visible' ).focus( function() {
        if ( $navigation.hasClass( 'site-navigation-fixed' ) ) {
            var windowScrollTop = $( window ).scrollTop(),
                fixedNavHeight = $navigation.height(),
                itemScrollTop = $( this ).offset().top,
                offsetDiff = itemScrollTop - windowScrollTop;

            // Account for Admin bar.
            if ( $( '#wpadminbar' ).length ) {
                offsetDiff -= $( '#wpadminbar' ).height();
            }

            if ( offsetDiff < fixedNavHeight ) {
                $( window ).scrollTo( itemScrollTop - ( fixedNavHeight + 50 ), 0 );
            }
        }
    });

    // Set properties of navigation.
    function setNavProps() {
        navigationHeight      = $navigation.height();
        navigationOuterHeight = $navigation.outerHeight();
        navPadding            = parseFloat( $navWrap.css( 'padding-top' ) ) * 2;
        navMenuItemHeight     = $navMenuItem.outerHeight() * 2;
        idealNavHeight        = navPadding + navMenuItemHeight;
        navIsNotTooTall       = navigationHeight <= idealNavHeight;
    }

    // Make navigation 'stick'.
    function adjustScrollClass() {

        // Make sure we're not on a mobile screen.
        if ( 'none' === $menuToggle.css( 'display' ) ) {

            // Make sure the nav isn't taller than two rows.
            if ( navIsNotTooTall ) {

                // When there's a custom header image or video, the header offset includes the height of the navigation.
                if ( isFrontPage && ( $body.hasClass( 'has-header-image' ) || $body.hasClass( 'has-header-video' ) ) ) {
                    headerOffset = $customHeader.innerHeight() - navigationOuterHeight;
                } else {
                    headerOffset = $customHeader.innerHeight();
                }

                // If the scroll is more than the custom header, set the fixed class.
                if ( $( window ).scrollTop() >= headerOffset ) {
                    $navigation.addClass( navigationFixedClass );
                } else {
                    $navigation.removeClass( navigationFixedClass );
                }

            } else {

                // Remove 'fixed' class if nav is taller than two rows.
                $navigation.removeClass( navigationFixedClass );
            }
        }
    }

    // Set margins of branding in header.
    function adjustHeaderHeight() {
        if ( 'none' === $menuToggle.css( 'display' ) ) {

            // The margin should be applied to different elements on front-page or home vs interior pages.
            if ( isFrontPage ) {
                $branding.css( 'margin-bottom', navigationOuterHeight );
            } else {
                $customHeader.css( 'margin-bottom', navigationOuterHeight );
            }

        } else {
            $customHeader.css( 'margin-bottom', '0' );
            $branding.css( 'margin-bottom', '0' );
        }
    }

    // Set icon for quotes.
    function setQuotesIcon() {
        $( twentyseventeenScreenReaderText.quote ).prependTo( $formatQuote );
    }

    // Add 'below-entry-meta' class to elements.
    function belowEntryMetaClass( param ) {
        var sidebarPos, sidebarPosBottom;

        if ( ! $body.hasClass( 'has-sidebar' ) || (
            $body.hasClass( 'search' ) ||
            $body.hasClass( 'single-attachment' ) ||
            $body.hasClass( 'error404' ) ||
            $body.hasClass( 'twentyseventeen-front-page' )
        ) ) {
            return;
        }

        sidebarPos       = $sidebar.offset();
        sidebarPosBottom = sidebarPos.top + ( $sidebar.height() + 28 );

        $entryContent.find( param ).each( function() {
            var $element = $( this ),
                elementPos = $element.offset(),
                elementPosTop = elementPos.top;

            // Add 'below-entry-meta' to elements below the entry meta.
            if ( elementPosTop > sidebarPosBottom ) {
                $element.addClass( 'below-entry-meta' );
            } else {
                $element.removeClass( 'below-entry-meta' );
            }
        });
    }

    /*
     * Test if inline SVGs are supported.
     * @link https://github.com/Modernizr/Modernizr/
     */
    function supportsInlineSVG() {
        var div = document.createElement( 'div' );
        div.innerHTML = '<svg/>';
        return 'http://www.w3.org/2000/svg' === ( 'undefined' !== typeof SVGRect && div.firstChild && div.firstChild.namespaceURI );
    }

    /**
     * Test if an iOS device.
    */
    function checkiOS() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && ! window.MSStream;
    }

    /*
     * Test if background-attachment: fixed is supported.
     * @link http://stackoverflow.com/questions/14115080/detect-support-for-background-attachment-fixed
     */
    function supportsFixedBackground() {
        var el = document.createElement('div'),
            isSupported;

        try {
            if ( ! ( 'backgroundAttachment' in el.style ) || checkiOS() ) {
                return false;
            }
            el.style.backgroundAttachment = 'fixed';
            isSupported = ( 'fixed' === el.style.backgroundAttachment );
            return isSupported;
        }
        catch (e) {
            return false;
        }
    }

    // Fire on document ready.
    $( document ).ready( function() {

        //Hide events map after waiting for it to load. Doesn't render properly if hidden right away
        // $('.eventmap').css('opacity', 0);
        // setTimeout(function(){
        //     $('.eventmap').hide();
        //     $('.eventmap').css('opacity', 1);
        // }, 1000);

        //Set map current target
        $('#btn-calendar').addClass('currentView');

        //Controls to toggle map
        $('#btn-calendar').click(function(){
            $('#btn-map').removeClass('currentView');
            $('#btn-calendar').addClass('currentView');
            $('.boxy').show();
            $('.eventmap').hide();
        });
        $('#btn-map').click(function(){
            $('#btn-map').addClass('currentView');
            $('#btn-calendar').removeClass('currentView');
            $('.boxy').hide();
            $('.eventmap').show();
        });

        // If navigation menu is present on page, setNavProps and adjustScrollClass.
        if ( $navigation.length ) {
            setNavProps();
            adjustScrollClass();
        }

        // If 'Scroll Down' arrow in present on page, calculate scroll offset and bind an event handler to the click event.
        if ( $menuScrollDown.length ) {

            if ( $( 'body' ).hasClass( 'admin-bar' ) ) {
                menuTop -= 32;
            }
            if ( $( 'body' ).hasClass( 'blog' ) ) {
                menuTop -= 30; // The div for latest posts has no space above content, add some to account for this.
            }
            if ( ! $navigation.length ) {
                navigationOuterHeight = 0;
            }

            $menuScrollDown.click( function( e ) {
                e.preventDefault();
                $( window ).scrollTo( '#primary', {
                    duration: 600,
                    offset: { top: menuTop - navigationOuterHeight }
                });
            });
        }

        adjustHeaderHeight();
        setQuotesIcon();
        if ( true === supportsInlineSVG() ) {
            document.documentElement.className = document.documentElement.className.replace( /(\s*)no-svg(\s*)/, '$1svg$2' );
        }

        if ( true === supportsFixedBackground() ) {
            document.documentElement.className += ' background-fixed';
        }
    });

    // If navigation menu is present on page, adjust it on scroll and screen resize.
    if ( $navigation.length ) {

        // On scroll, we want to stick/unstick the navigation.
        $( window ).on( 'scroll', function() {
            adjustScrollClass();
            adjustHeaderHeight();
        });

        // Also want to make sure the navigation is where it should be on resize.
        $( window ).resize( function() {
            setNavProps();
            setTimeout( adjustScrollClass, 500 );
        });
    }

    $( window ).resize( function() {
        clearTimeout( resizeTimer );
        resizeTimer = setTimeout( function() {
            belowEntryMetaClass( 'blockquote.alignleft, blockquote.alignright' );
        }, 300 );
        setTimeout( adjustHeaderHeight, 1000 );
    });

    // Add header video class after the video is loaded.
    $( document ).on( 'wp-custom-header-video-loaded', function() {
        $body.addClass( 'has-header-video' );
    });

})( jQuery );

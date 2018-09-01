/**
 * Javascript: Eventon Active User - Front end script
 * @version  2.0.14
 * Intel Version 1.2
 */
jQuery(document).ready(function($){

// select2 for location select field
	if (typeof select2 == 'function') { 
		$('.evoau_location_select').select2();
	}

// Event Manager Actions
	adjust_sizes();
	$( window ).resize(function() {
		if($('body').find('.evoau_manager_event_section').length==0) return;
		adjust_sizes();
	});
	function adjust_sizes(){
		if($('body').find('.eventon_actionuser_eventslist').length>0){
			EM_width = $('.evoau_manager_event_section').width();
			$('.evoau_manager_event_list').width( (EM_width*2)+2 );
			$('.eventon_actionuser_eventslist').width(EM_width);
			$('.evoau_manager_event').width(EM_width);
			
			if($('.evoau_manager_event_list').css('margin-left') != '0px')
				$('.evoau_manager_event_list').css('margin-left',EM_width*-1);
		}
	}
	

	// load edit event form into event manager
		$('.eventon_actionuser_eventslist').on('click','a.editEvent',function(event){
			event.preventDefault();

			OBJ = $(this);
			
			MANAGER = $('#evoau_event_manager');
			MOVER = MANAGER.find('.evoau_manager_event_list');
			LIST = MANAGER.find('.eventon_actionuser_eventslist');

			// get form html
			var ajaxdataa = {};
	        
	        ajaxdataa['action'] = 'evoau_get_manager_event';
	        ajaxdataa['eid'] = OBJ.data('eid');
	        ajaxdataa['method'] = 'editevent';
	        ajaxdataa['sformat'] = OBJ.data('sformat');
			$.ajax({
	            beforeSend: function(){     LIST.addClass('evoloading');  },                  
	            url:    evoau_ajax_script.ajaxurl,
	            data:   ajaxdataa,  dataType:'json', type:  'POST',
	            success:function(data){

	            	$('body').trigger('evoau_show_eventdata',[MANAGER, data.html, true]);

	            },complete:function(){ 
	            	LIST.removeClass('evoloading');
	            }
	        });
		});
	
	// move events list functions
		$('body').on('evoau_show_eventdata', function(event, MANAGER, CONTENT, TriggerFormInteractions){
			MANAGER.find('.evoau_manager_event_content').html( CONTENT );

			load_new_editor('newreply' , $('#evoau_event_manager'), $('.evoau_manager_event'));

			MANAGER.find('#evoau_hidden_editor').remove();
			
			LIST = MANAGER.find('.eventon_actionuser_eventslist');
        	FORM = MANAGER.find('.evoau_submission_form');
        	MOVER = MANAGER.find('.evoau_manager_event_list');

        	if(TriggerFormInteractions) $('body').trigger('evoau_loading_form_content', [FORM] ); 
        	
        	LISTWIDTH = (LIST.width())*-1;
        	MOVER.animate({'margin-left': LISTWIDTH});
        	LIST.removeClass('evoloading');
		});

	
	// delete an event
		$('.eventon_actionuser_eventslist').on('click','a.deleteEvent',function(event){
			event.preventDefault();

			OBJ = $(this);
			MANAGER = $(this).closest('.evoau_manager');
			BOX = MANAGER.find('.evoau_delete_trigger');

			BOX.find('span.ow').attr('data-eid', $(this).data('eid'));
			BOX.show();


			TOP = BOX.offset();
			WINPOS = OBJ.offset();
			console.log(WINPOS.top+' '+TOP.top);
			POS = WINPOS.top - TOP.top
			BOX.find('.deletion_message').css({'margin-top':POS});

		});
		$('.evoau_delete_trigger').on('click','span.ow',function(){
			var ajaxdataa = {};

			MSG = $(this).closest('.deletion_message');
			MANAGER = $(this).closest('.evoau_manager');
	        
	        ajaxdataa['action'] = 'evoau_delete_event';
	        ajaxdataa['eid'] = $(this).data('eid');
			$.ajax({
	            beforeSend: function(){ 
	                MSG.addClass('evoloading');
	            },                  
	            url:    evoau_ajax_script.ajaxurl,
	            data:   ajaxdataa,  dataType:'json', type:  'POST',
	            success:function(data){
	            	MANAGER.find('.evoau_manager_event_rows').html( data.html);
	            	MANAGER.find('.evoau_delete_trigger').hide();	            	
	            },complete:function(){ 
	            	MSG.removeClass('evoloading');
	            }
	        });
		});
		$('.evoau_delete_trigger').on('click','span.nehe',function(){
			$(this).closest('.evoau_delete_trigger').hide();
		});
		
	// back to event list
		$('#evoau_event_manager').on('click','a.evoau_back_btn',function(){
			MANAGER = $(this).closest('.evoau_manager');
			MANAGER.find('.evoau_manager_event_list').animate({
				'margin-left':0
			},function(){
				MANAGER.find('.evoau_manager_event_content').html('');
			});
		});

	// Pagination
		$('.evoau_manager_pagination').on('click','.evoau_paginations',function(){
			OBJ = $(this);
			SECTION = OBJ.closest('.eventon_actionuser_eventslist').find('.evoau_manager_event_rows');
			
			direction = OBJ.hasClass('next')? 'next':'prev';
			page = parseInt(SECTION.attr('data-page'));

			if(page == 1 && direction =='prev') return false;

			// console.log(next_page);
			var ajaxdataa = {};
			ajaxdataa['action'] = 'evoau_get_paged_events';
	        ajaxdataa['page'] = page;
	        ajaxdataa['direction'] = direction;
	        ajaxdataa['epp'] = SECTION.data('epp');
	        ajaxdataa['uid'] = SECTION.data('uid');
	        ajaxdataa['pages'] = SECTION.data('pages');
	        ajaxdataa['events'] = SECTION.data('events');

	        // if at max pages
	        if( ajaxdataa.pages == ajaxdataa.page && direction =='next') return false;

			$.ajax({
	            beforeSend: function(){ 
	                SECTION.addClass('evoloading');
	            },                  
	            url:    evoau_ajax_script.ajaxurl,
	            data:   ajaxdataa,  dataType:'json', type:  'POST',
	            success:function(data){
	            	SECTION.attr('data-page', data.next_page);
	            	SECTION.html( data.html);	            	
	            },complete:function(){ 
	            	SECTION.removeClass('evoloading');
	            }
	        });

		});

// lightbox form trigger
	$('body').on('click','.evoAU_form_trigger_btn',function(){
		OBJ = $(this);
		LIGHTBOX = $('.evoau_lightbox');
		LIGHTBOX.addClass('show');
		$('body').trigger('evolightbox_show');

		// get form html
		var ajaxdataa = {};
        
        ajaxdataa['action'] = 'evoau_get_form';
        ajaxdataa['eid'] = OBJ.data('eid');
		$.ajax({
            beforeSend: function(){ 
                LIGHTBOX.find('.evo_lightbox_body').addClass('evoloading').html('<p class="loading_content"></p>');
            },                  
            url:    evoau_ajax_script.ajaxurl,
            data:   ajaxdataa,  dataType:'json', type:  'POST',
            success:function(data){

               	LIGHTBOX.find('.evo_lightbox_body').html( data.html );
                FORM = LIGHTBOX.find('.evoau_submission_form');

                load_new_editor('newreply' , $('#evoau_lightbox_form_btn'), $('#evoau_lightbox'));

                $('body').trigger('evoau_loading_form_content', [FORM] ); 
            },complete:function(){ 
            	LIGHTBOX.find('.evo_lightbox_body').removeClass('evoloading');
            }
        });

		reset_form( $('.evoau_submission_form').find('form'), 'midcore');
	});

// FIELDS of the event form
	
	// FORM interactive triggers		
		$('body').on('evoau_loading_form_content', function(event,FORM){
			
			// all day events
				FORM.find('#evcal_allday').on('click',function(){
					if ($(this).hasClass('NO')) {
						$('.evoau_tpicker').fadeOut();
					}else{
						$('.evoau_tpicker').fadeIn();
					}
				});
			// no time event
				FORM.find('#evo_hide_endtime').on('click',function(){
					if ($(this).hasClass('NO')) {
						$('#evoAU_endtime_row').slideUp();
					}else{
						$('#evoAU_endtime_row').slideDown();
					}
				});
			// repeating events section
				FORM.find('#evcal_repeat').on('click',function(){
					if ($(this).hasClass('NO')) {
						$('#evoau_repeat_data').slideDown();
					}else{
						$('#evoau_repeat_data').slideUp();
					}
				});
			// time picker
				time_format__ = FORM.find('#_evo_time_format').val();
				time_format__ = (time_format__=='24h')? 'H:i':'h:i:A';
				FORM.find('.evoau_time_picker').timepicker({ 'step': 15,'timeFormat': time_format__ });

			// Image selection
				FORM.find('.evoau_img_input').bind('change focus click',function(){
					var INPUT = $(this),
						BTN = INPUT.siblings('.evoau_img_btn'),
				      	$val = INPUT.val(),
				      	valArray = $val.split('\\'),
				      	newVal = valArray[valArray.length-1],
				     	$fakeFile = INPUT.siblings('.file_holder');

				     console.log(newVal);
				  	
				  	if(newVal !== '') {
				   		var btntext = INPUT.attr('data-text');
				   		
				    	BTN.text( btntext);
				    	
				    	if($fakeFile.length === 0) {
				    	  	BTN.after('<span class="file_holder">' + newVal + '</span>');
				    	} else {
				      		$fakeFile.text(newVal);
				    	}		    	
				  	}
				});
				// remove existing images
					$(FORM).on('click','.evoau_event_image_remove',function(){
						ROW = $(this).closest('.row');
						ROW.find('.evoau_img_preview').hide();
						ROW.find('.evoau_file_field').show();
						//ROW.find('.evoau_img_preview').remove();
						$(this).siblings('input').val('no');
					});
				// run actual input field image when click on span button
					$(FORM).on('click','.evoau_img_btn',function(){
						$(this).parent().find('input').click();
					});

			// date picker
				var dateformat__ = FORM.find('#_evo_date_format').attr('jq');
				date_format = (typeof dateformat__ !== 'undefined' && dateformat__ !== false)?	
				dateformat__: 'dd/mm/yy';
				
				START = FORM.find('.datepickerstartdate');

				if(FORM.find('.datepickerstartdate').length>0){
					MN = START.data('mn');
					DN = START.data('dn');
					FDN = START.data('fdn');
					OT = START.data('ot');

					$.datepicker.regional['EVO'] = {
					    monthNames: MN, // set month names
					    dayNames: FDN, // set more short days names
					    prevText: OT.txtprev,
		    			nextText: OT.txtnext,
					};
					if(DN != '' && DN !== undefined) $.datepicker.regional['EVO'].dayNamesMin = DN;
					$.datepicker.setDefaults($.datepicker.regional['EVO']);

					START.datepicker({ 
						dateFormat: date_format,
						numberOfMonths: 1,
						altField: FORM.find('input.evoau_alt_date_start'),
						altFormat: 'yy-mm-dd',
						onClose: function( selectedDate ) {
					        FORM.find( ".datepickerenddate" ).datepicker( "option", "minDate", selectedDate );
					    }
					});
				}
				if(FORM.find('.datepickerenddate').length>0){
					FORM.find( ".datepickerenddate" ).datepicker({ 
						dateFormat: date_format,
						numberOfMonths: 1,
						altField: FORM.find('input.evoau_alt_date_end'),
						altFormat: 'yy-mm-dd',
						onClose: function( selectedDate ) {
				        	FORM.find('.datepickerstartdate').datepicker( "option", "maxDate", selectedDate );
				      	}
					});
				}

			// color picker
				ITEM = FORM.find('.color_circle');
				ITEM.ColorPicker({
					onBeforeShow: function(){
						$(this).ColorPickerSetColor( $(this).attr('data-hex'));
					},	
					onChange:function(hsb, hex, rgb,el){

						$(this).attr({'backgroundColor': '#' + hex, 'data-hex':hex}).css('background-color','#' + hex);
						ITEM.attr({'backgroundColor': '#' + hex, 'data-hex':hex}).css('background-color','#' + hex);
						set_rgb_min_value(rgb,'rgb', ITEM);
						ITEM.next().find('.evcal_event_color').attr({'value':hex});
					},	
					onSubmit: function(hsb, hex, rgb, el) {					
						var sibb = ITEM.siblings('.evoau_color_picker');
						sibb.find('.evcal_event_color').attr({'value':hex});
						ITEM.css('backgroundColor', '#' + hex);				
						ITEM.ColorPickerHide();
						set_rgb_min_value(rgb,'rgb', ITEM);
					}
				});

			// edit locatrion and organizer
				$(FORM).on('click','.editMeta',function(){
					$(this).closest('.row').find('.enterownrow').slideToggle();
				});
		});

		$('body').find('.evoau_submission_form').each(function(){
			$('body').trigger('evoau_loading_form_content',[$(this)]);
		});
	
	// color picker		
		/** convert the HEX color code to RGB and get color decimal value**/
			function set_rgb_min_value(color,type, ITEM){			
				if( type === 'hex' ) {			
					var rgba = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(color);	
					var rgb = new Array();
					 rgb['r']= parseInt(rgba[1], 16);			
					 rgb['g']= parseInt(rgba[2], 16);			
					 rgb['b']= parseInt(rgba[3], 16);	
				}else{
					var rgb = color;
				}			
				var val = parseInt((rgb['r'] + rgb['g'] + rgb['b'])/3);			
				ITEM.next().find('.evcal_event_color_n').attr({'value':val});
			}

	// click on user interaction field
		$('body').on('change', '.evoau_submission_form .evoau_ui select', function(){
			var value = $(this).val();
			if(value==2){
				$(this).parent().siblings('.evoau_exter').slideDown();
			}else{
				$(this).parent().siblings('.evoau_exter').slideUp();
			}
		});

	// location saved list
		$('body').on('change','.evoau_location_select',function(){
			option = $(this).find(':selected');
			FORM = $(this).closest('form');

			// address
			FORM.find('input[name=evcal_location_name]').attr('value',option.text());
			FORM.find('input[name=location_address]').attr('value',option.attr('data-add'));
			FORM.find('input[name=evcal_location_link]').attr('value',option.attr('data-link'));
			FORM.find('input[name=evo_loc_img_id]').attr('value',option.attr('data-img'));
			
			if(option.attr('data-lat')!= '' && option.attr('data-lat')!== undefined) 
				FORM.find('input[name=event_location_cord]').attr('value',option.attr('data-lat')+','+option.attr('data-lon'));
		});

	// Organizer saved list
		$('body').on('change','.evoau_organizer_select',function(){
			option = $(this).find(':selected');
			FORM = $(this).closest('form');
			
		
			
			var organizer = [];
			$.each($(".evoau_organizer_select option:selected"), function(){            
				organizer.push( $(this).val());
			});
		

			FORM.find('input[name=evcal_organizer]').attr('value', organizer);
		//	FORM.find('input[name=evcal_organizer]').attr('value', option.text());
			FORM.find('input[name=evcal_org_address]').attr('value',option.attr('data-address'));
			FORM.find('input[name=evcal_org_contact]').attr('value',option.attr('data-contact'));
			FORM.find('input[name=evcal_org_exlink]').attr('value',option.attr('data-exlink'));
			FORM.find('input[name=evo_org_img_id]').attr('value',option.attr('data-img'));				
		});

		// enter new organizer or location
			$('body').on('click','.enterNew',function(){
				OBJ = $(this);
				var txt = OBJ.attr('data-txt'), html = OBJ.html();
				// trying to select previously saved
				if(OBJ.hasClass('newitem')){				
					OBJ.closest('.row').find('.enterownrow').slideUp().find('input').val('');
					SELECT = OBJ.siblings('select');
						SELECT.fadeIn();
						SELECT.find('option:first-child').attr('selected','selected');
					OBJ.removeClass('newitem');
				}else{ // Enter new			
					OBJ.closest('.row').find('.enterownrow').slideDown().find('input').val('');
					OBJ.siblings('select').fadeOut();
					OBJ.addClass('newitem');	
					OBJ.siblings('.editMeta').hide();	
				}
				// change button text
				if(OBJ.attr('data-st')=='ow')
					OBJ.html(txt).attr('data-txt',html);		
			});

// form submission
	$('body').on('click','.evoau_submission_form',function(){
		$(this).removeClass('errorForm');
		$(this).find('.formeMSG').fadeOut();
	});
	
	$('body').on('click','.evoau_event_submission_form_btn',function(e){
		e.preventDefault();

		var form = $(this).closest('form'),
			formp = form.parent(),
			errors = 0,
			msg_obj = form.find('.formeMSG');


			FORM_TYPE = form.find('input[name=form_action]').val()=='editform'?'edit':'new';

		// tiny MCE
			if(form.find('.event_description').length>0) tinyMCE.triggerSave();

		var data_arg = {};

		// form notification messages
			var nof = formp.find('.evoau_json');
			//console.log(nof.html);
			nof = JSON.parse(nof.html());
			//onsole.log(nof);

		// save cookie if submission is limited
			if(form.data('limitsubmission')=='ow'){
				if($.cookie('evoau_event_submited')=='yes'){
					formp.addClass('errorForm limitSubmission');
					form.find('.inner').slideUp();
					form.find('.evoau_success_msg').html('<p><b></b>'+nof.nof6+'</p>').show();
					return false;
				}else{
					$.cookie('evoau_event_submited','yes',{expires:24});
				}			
			}

		reset_form(form);
		//data_arg = form.formParams();
					
		// check required fields missing
			form.find('.req').each(function(i, el){
				var el = $(this);
				var val = el.val();
				var elname = el.attr('name');

				// hide end time 
				if( !$('#evo_hide_endtime').hasClass('NO') && (elname=='event_end_date' || elname=='event_end_time')) return true;
				
				// no end time
				if( !$('#evcal_allday').hasClass('NO') && ( elname =='event_end_time' || elname == 'event_start_time')) return true;

				if(val.length==0){
					// if required field dependancy is present
					if( el.data('reqd') != '' && el.data('reqd') !== undefined){
						JDATA = el.data('reqd');
						
						FIELD = form.find('[name="'+JDATA.name +'"]');
						if(FIELD.val() == JDATA.value){
							errors++;
							el.closest('.row').addClass('err');	
						}
					}else{
						errors++;
						el.closest('.row').addClass('err');
					}										
				}
			});

		// check for captcha validation
			if(form.find('.au_captcha').length>0){
				var field = form.find('.au_captcha input'),
					cval = field.val();

				validation_fail = false;

				if(cval==undefined || cval.length==0){
					validation_fail = true;
				}else{
					var numbers = ['11', '3', '6', '3', '8'];
					if(numbers[field.attr('data-cal')] != cval )
						validation_fail = true;
				}
				if(validation_fail){
					errors = (errors == 0)? 20:errors+1;
					form.find('.au_captcha').addClass('err');
				}
			}

		// pass correct event descriptions
		if (form.find("#wp-event_descriptionau-wrap").hasClass("tmce-active")){
	        FF = tinyMCE.activeEditor.getContent();
	        form.find('#wp-event_descriptionau-wrap').find('textarea[name="event_description"]').val(FF);
	    }

		//errors = 2;
		
		if(errors==0){
			form.ajaxSubmit({
				beforeSubmit: function(){						
					formp.addClass('evoloadbar bottom');
				},
				dataType:'json',
				url:evoau_ajax_script.ajaxurl,
				success:function(responseText, statusText, xhr, $form){
					if(responseText.status=='good'){
						form.find('.inner').slideUp();

						SUCMSG = formp.find('.evoau_success_msg');

						// show success msg
						JSON_str = (FORM_TYPE=='new')? nof.nof3: nof.nof8;
						SUCMSG.html('<p><b></b>'+ JSON_str +'</p>').slideDown(); 
						formp.addClass('successForm');

						// redirect page after success form submission
						if(form.attr('data-redirect')!='nehe'){
							RDUR = (form.attr('data-rdur') !='' && form.attr('data-rdur')!== undefined)? parseInt(form.attr('data-rdur')):0;
							setTimeout(function(){
								window.location = form.attr('data-redirect');
							}, RDUR);
						}

						// show add more events button
						if(form.attr('data-msub')=='ow'){
							formp.find('.msub_row').fadeIn();
						}

						// scroll to top of form to show success message , if not lightbox
						if(!form.hasClass('lightbox') )
							$('html, body').animate({scrollTop: form.offset().top - 80}, 2000);

					}else{
						MSG = (responseText.msg=='bad_nonce')? nof.nof5: eval('nof.' + responseText.msg);
						msg_obj.html( MSG).fadeIn();
					}
					formp.removeClass('evoloadbar bottom');													
				}
			});			
		}else{
			formp.addClass('errorForm');
			
			//console.log(errors);
			e.preventDefault();
			var msg = (errors==20)? nof.nof2: nof.nof0;
			msg_obj.html(msg).slideDown('fast');
			return false;
		}
	});

	// submit another event
		$('body').on('click','a#evoau_msub',function(){
			FORM = $(this).closest('form');

			if(FORM.parent().hasClass('successForm')){
				reset_form(FORM,'hardcore');
				$(this).parent().fadeOut();
			}
		});

// complete form actions
	function reset_form(form, type){		
		
		form.find('.row').removeClass('err');
		form.parent().removeClass('successForm errorForm');

		form.find('.inner').show();
		form.find('.evoau_success_msg').hide();

		if(type=='hardcore' || type=='midcore'){
			form.find('input[type=text]').val('');
			form.find('input[type=checkbox]').attr('checked', false);
			form.find('textarea').val('');
			$('#evoAU_endtime_row').show();
			$('.evoau_tpicker ').show();

			// select fields
			form.find('select').each(function(){
				$(this).val('-');
			});

			// reset wysiwyg editor
			if (form.find("#wp-event_descriptionau-wrap").hasClass("tmce-active")){
		        tinyMCE.activeEditor.setContent('');
		        tinyMCE.triggerSave();		        
		    }

			// repeat information
			$('#evcal_allday').addClass('NO').siblings('input').val('no');
			$('#evo_hide_endtime').addClass('NO').siblings('input').val('no');
			$('#evcal_repeat').addClass('NO').siblings('input').val('no');
			$('#evoau_repeat_data').hide();

			// image field
			imgfield = form.find('.evoau_file_field');
			imgfield.find('.file_holder').html('');
		}

		if(type=='hardcore'){
			form.find('.eventon_form_message').removeClass('error_message').fadeOut();
		}

	}

// TINY EDITOR
	function load_new_editor(id, MCE_OBJ, FORM_OBJ){
	    // remove click to add
	    //jQuery('#add').remove();

	    number = MCE_OBJ.data('mce');
	   	WYS_editor = FORM_OBJ.find('#evoau_form_wisywig');
	    var fullId = id + number;

	    var data = {
	        'action': 			'load_editor_new_editor',
	        'number': 			number,
	        'id': 				id,
	        'textarea_name': 	WYS_editor.data('textareaname'),
	        'content': 			FORM_OBJ.find('textarea#evoau_form_wisywig_content').val()
	    };


	    jQuery.post(evoau_ajax_script.ajaxurl, data, function(response) {

	        //add new editor
	       	$('#evoau_form_wisywig').replaceWith(response);
	      	MCE_OBJ.data('mce', (number+1));

	        // this is need for the tabs to work
	        quicktags({id : fullId});

	        // use wordpress settings
	        tinymce.init({
	        selector: fullId,

	        theme:"modern",
	        language:"en",
	        formats:{
	            alignleft: [
	                {selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: {textAlign:'left'}},
	                {selector: 'img,table,dl.wp-caption', classes: 'alignleft'}
	            ],
	            aligncenter: [
	                {selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: {textAlign:'center'}},
	                {selector: 'img,table,dl.wp-caption', classes: 'aligncenter'}
	            ],
	            alignright: [
	                {selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: {textAlign:'right'}},
	                {selector: 'img,table,dl.wp-caption', classes: 'alignright'}
	            ],
	            strikethrough: {inline: 'del'}
	        },
	        relative_urls:false,
	        remove_script_host:false,
	        convert_urls:false,
	        browser_spellcheck:true,
	        fix_list_elements:true,
	        entities:"38,amp,60,lt,62,gt",
	        entity_encoding:"raw",
	        keep_styles:false,
	        paste_webkit_styles:"font-weight font-style color",
	        preview_styles:"font-family font-size font-weight font-style text-decoration text-transform",
	        wpeditimage_disable_captions:false,
	        wpeditimage_html5_captions:true,
	        plugins:"charmap,hr,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpeditimage,wpgallery,wplink,wpdialogs,wpview",
	        selector:"#" + fullId,
	        resize:"vertical",
	        menubar:false,
	        wpautop:true,
	        indent:false,
	        toolbar1:"bold,italic,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv",toolbar2:"formatselect,underline,alignjustify,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help",
	        toolbar3:"",
	        toolbar4:"",
	        tabfocus_elements:":prev,:next",
	        body_class:"id post-type-post post-status-publish post-format-standard",

	});


	        // this is needed for the editor to initiate
	        tinyMCE.execCommand('mceAddEditor', false, fullId); 

	    });
	}
	
	$('option').mousedown(function(e) {
		e.preventDefault();    
		var originalScrollTop = $(this).parent().scrollTop();
		console.log(originalScrollTop);
		$(this).prop('selected', $(this).prop('selected') ? false : true);
		option = $(this).find(':selected');
		FORM = $(this).closest('form');
			
		var organizer = [];
		$.each($(".evoau_organizer_select option:selected"), function(){            
			organizer.push( $(this).val() );
		});
		
		FORM.find('input[name=evcal_organizer]').attr('value', organizer);
		var self = this;
		$(this).parent().focus();
		setTimeout(function() {
			$(self).parent().scrollTop(originalScrollTop);
		}, 0);
		
		return false;
	});


});


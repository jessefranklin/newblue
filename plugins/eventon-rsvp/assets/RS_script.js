/**
 * Javascript: RSVP Events Calendar
 * @version  2.5.7
 */
jQuery(document).ready(function($){

	init();

	var submit_open = false;

	// INITIATE script
		function init(){
			$('body').on('click','.evoRS_status_option_selection span',function(){
				show_rsvp_form( $(this), $(this).parent().parent(), $(this).attr('data-val'), 'submit');
			});
		}

	// RSVP form interactions
		// change RSVP status within the form
			$('body').on('click', 'span.evors_choices', function(){
				OBJ = $(this);

				VAL = OBJ.attr('data-val');
				SUBMISSION_FORM = OBJ.closest('.submission_form');
				SUBMISSION_FORM.attr('class','submission_form form_section rsvp_'+VAL);
				OBJ.siblings().removeClass('set');
				OBJ.addClass('set');

				OBJ.siblings('input').val( VAL );

			});
		// close RSVP form from incard close button
			$('body').on('click','.evors_incard_close',function(){
				PAR = $(this).parent();
				PAR.slideUp(function(){
					PAR.html('');
				});
				// reset any selected RSVP choices
				$(this).closest('.evo_metarow_rsvp').find('.evors_choices').removeClass('set');
			});

	// RSVP from eventtop
		$('body').on('click', '.evors_eventtop_rsvp span', function(event){
			event.preventDefault();
			event.stopPropagation();


			var obj = $(this),
				rsvp = obj.parent(),
				rsvp_section = rsvp.parent(),
				ajaxdataa = {};

			ajaxdataa['rsvp']= obj.data('val');
			ajaxdataa['lang']= rsvp.data('lang');
			ajaxdataa['uid']= rsvp.data('uid');
			ajaxdataa['updates']= 'no';
			ajaxdataa['action']='the_ajax_evors_a7';
			ajaxdataa['repeat_interval']=rsvp.data('ri');
			ajaxdataa['e_id']= rsvp.data('eid');

			$.ajax({
				beforeSend: function(){	rsvp.addClass('loading');	},
				type: 'POST',
				url:evors_ajax_script.ajaxurl,
				data: ajaxdataa,
				dataType:'json',
				success:function(data){
					if(data.status=='0'){
						$('body').trigger('evors_new_rsvp_eventtop');
						rsvp.html(data.message).addClass('success');
						rsvp_section.find('.evors_eventtop_section_data').replaceWith(data.content);
					}else{
						rsvp.append('<span class="error">'+data.message+'</span>');
					}
				},complete:function(){
					rsvp.removeClass('loading');
				}
			});
		});

	// RSVP form submissions & update existing
		$('body').on('click', '.evors_submit_rsvpform_btn', function(){
			//copy ics link from single event card to confirmation message
			var icsLink = $('body').find('.evo_ics_nCal').attr('href');
			var icsCheck = setInterval(createIcs, 10);

			function createIcs(){
				if($('body').find('.evo_lightboxes').find('.ics-link').length){
					$('body').find('.evo_lightboxes').find('.ics-link').attr('href',icsLink);
					clearInterval(icsCheck);
				}
			}

			var obj = $(this),
				ajaxdataa = { },
				form = obj.closest('form.evors_submission_form'),
				FORMPAR = form.parent(),
				formSection = form.parent(),
				error = 0,
				formType = form.find('input[name="formtype"]').val();

			// reset form error messages
				rsvp_hide_notifications();
				FORMPAR.parent().removeClass('error');

			// validation
				// run through each rsvp field
					form.find('.input').each(function(index){
						$(this).removeClass('err');

						// check required fields filled
						if( $(this).hasClass('req') && $(this).val()=='' && $(this).is(":visible")){
							error = 1;
							$(this).addClass('err');
						}

						if( $(this).val() == '' ) return true;
						//ajaxdataa[ $(this).attr('name') ] = encodeURIComponent( $(this).val() );
					});
				// validate email
					if(error==0){
						var thisemail = form.find('input[name=email]');
						if(!is_email(thisemail.val().trim() )){
							thisemail.addClass('err');
							rsvp_error('err2','','',FORMPAR); // invalid email address
							error = 2;
						}
					}
				// capacity check
					if(error==0){
						if(formType=='update'){
							pastVal = parseInt(form.find('input[name=count]').attr('data-pastval'));
							newVal = parseInt(form.find('input[name=count]').val());
							compareCount = (pastVal>newVal)? 0: newVal-pastVal;
						}else{
							compareCount =  parseInt(form.find('input[name=count]').val());
						}

						if(form.find('.rsvp_status span.set').attr('data-val')!='n'
							&& formSection.attr('data-cap')!='na'
							&& compareCount > parseInt(formSection.attr('data-cap'))
						){
							error=4;
							form.find('input[name=count]').addClass('err');
							rsvp_error('err9','','',FORMPAR);
						}
						// max count limit
						if( formSection.attr('data-precap')!='na' &&
							(parseInt(form.find('input[name=count]').val()) > parseInt(formSection.attr('data-precap')) )
						){
							error=4;
							form.find('input[name=count]').addClass('err');
							rsvp_error('err10','','',FORMPAR);
						}
					}
				// validate human
					if(error==0){
						var human = rsvp_validate_human( form.find('input.captcha') );
						if(!human){
							error=3;
							rsvp_error('err6','','',FORMPAR);
						}
					}

			if(error==0){
				var updates = form.find('.updates input').attr('checked');
					updates = (updates=='checked')? 'yes':'no';

				ajaxdataa['action']='the_ajax_evors';

				form.ajaxSubmit({
				//$.ajax({
					beforeSend: function(){	form.parent().addClass('loading');	},
					type: 'POST',
					url:evors_ajax_script.ajaxurl,
					data: ajaxdataa,
					dataType:'json',
					success:function(data){
						//console.log(ajaxdataa);
						if(data.status=='0'){

							$('body').trigger('evors_new_rsvp_form');

							FORMPAR.parent().html(data.message);

							// update event top data
								if(data.e_id && data.data_content_eventtop){
									EVENTROW = $('#event_'+data.e_id );
									if(EVENTROW.find('.evors_eventtop_section_data').length>0){
										EVENTROW.find('.evors_eventtop_section_data').replaceWith(
											data.data_content_eventtop
										);
									}
								}
								if(data.e_id && data.data_content_eventtop_your){
									EVENTROW = $('#event_'+data.e_id );
									if(EVENTROW.find('.evors_eventop_rsvped_data').length>0){
										EVENTROW.find('.evors_eventop_rsvped_data').replaceWith(
											data.data_content_eventtop_your
										);
									}
								}

							// update whos coming data
								if(data.e_id){
									ROW = $('.evo_metarow_rsvp[data-event_id="'+data.e_id +'"]');
									ROW.addClass('rr');

									if(data.option_selection != '')
										ROW.find('.evoRS_status_option_selection').html( data.option_selection);
									if(data.data_content != '')
										ROW.find('.evors_information').html( data.data_content);
								}
							// update event manager stuff
								if( $('body').find('#rsvp_event_'+data.e_id).length>0 && data.new_rsvp_text){
									STATUS = $('#rsvp_event_'+data.e_id).find('span.rsvpstatus');
									STATUS.html( data.new_rsvp_text);
									STATUS.attr('class','rsvpstatus status_'+data.new_rsvp_text);
								}

						}else{
							var passedRsvppd = (data.status)? 'err'+data.status:'err7';
							rsvp_error(passedRsvppd, '', data.message,FORMPAR);
						}
					},complete:function(){	form.parent().removeClass('loading');	}
				});
			}else if(error==1){	rsvp_error('err','','',FORMPAR);	}
		});

	// capacity check real-time
		$('body').on('change','input.evors_rsvp_count',function(){

			RSVPFORM = $(this).closest('.evors_submission_form');
			// reset
				$('.evors_lightbox_body').removeClass('error');
				$(this).removeClass('err');
				rsvp_hide_notifications();

			OBJ = $(this);
			CAP = OBJ.data('cap');

			VAL = parseInt(OBJ.val());
			if(VAL > parseInt(CAP) && CAP!= 'na'){
				$(this).addClass('err');
				rsvp_error('err10');
			}else{
			// if valid capacity add additional guests
				guestNames = RSVPFORM.find('.form_guest_names');
				if(VAL>1){

					maskField = '<input class="regular input" name="names[]" type="text">';
					inputHolder = guestNames.find('.form_guest_names_list');
					ExistInputCount = inputHolder.find('input').length;

					// add or remove input fields
					if( (VAL-1) > ExistInputCount){ // add
						fieldsNeed = VAL-1-ExistInputCount;
						appender ='';
						for(x=0; x<fieldsNeed; x++){
							appender += maskField;
						}
						inputHolder.append(appender);
					}else{
						fieldsNeed = VAL-2;
						inputHolder.find('input').each(function(index){
							if(index> fieldsNeed) $(this).remove();
						});
					}
					guestNames.show();
				}else{
					guestNames.hide();
				}
			}
		});

	// CHANGE RSVP
		// change a RSVP
			$("body").on('click','.evors_change_rsvp_trig',function(){
				OBJ = $(this);
				PAR = OBJ.parent();
				show_rsvp_form(OBJ, PAR,'','update');
			});

		// from successful rsvped page
			$('body').on('click','#call_change_rsvp_form',function(){
				OBJ = $(this);
				PAR = OBJ.parent();
				show_rsvp_form(OBJ, PAR,'','update');
			});
		// From rsvp manager
			$('.eventon_rsvp_rsvplist').on('click','.update_rsvp',function(){
				OBJ = $(this);
				PAR = OBJ.parent();
				show_rsvp_form(OBJ, PAR,'','update');
			});

	// Show RSVP lightbox form
		function show_rsvp_form(OBJ, PAR, RSVP,formtype){
			var ajaxdataa = {};
			ajaxdataa['action']='evors_get_rsvp_form';
			ajaxdataa['e_id'] = PAR.attr('data-eid');
			ajaxdataa['repeat_interval'] = PAR.attr('data-ri');
			ajaxdataa['uid'] = (PAR.attr('data-uid')=='0'? 'na':PAR.attr('data-uid'));
			ajaxdataa['cap'] = PAR.attr('data-cap');
			ajaxdataa['precap'] = PAR.attr('data-precap');
			ajaxdataa['rsvp'] = RSVP;
			ajaxdataa['rsvpid'] = PAR.data('rsvpid');
			ajaxdataa['lang'] = PAR.data('lang');
			ajaxdataa['formtype'] = formtype;
			ajaxdataa['incard'] = PAR.data('incardform');
			FORMNEST = OBJ.closest('.evors_forms').parent();

			$.ajax({
				beforeSend: function(){
					loading(OBJ);
				},
				url:	evors_ajax_script.ajaxurl,
				data: 	ajaxdataa,	dataType:'json', type: 	'POST',
				success:function(data){
					if(data.status=='good'){
						// show form inside eventcard
						if( PAR.data('incardform')=='yes'){
							PAR.closest('.evcal_evdata_cell').find('.evors_incard_form')
								.removeClass('error')
								.html( data.content )
								.slideDown();
						}else{
							$('.evors_lightbox')
								.find('.evo_lightbox_body')
								.removeClass('error')
								.html( data.content );
							$('.evors_lightbox.evo_lightbox').addClass('show');
							$('body').trigger('evolightbox_show');
						}

					}else{
						// error notice ***
					}
				},complete:function(){
					completeloading(OBJ);
					FORMNEST.closest('.evorow').removeClass('loading');
				}
			});
		}

		// during ajax eventcard loading
			function loading(obj){
				obj.closest('.evorow').addClass('loading');
				obj.closest('p.rsvpmanager_event').addClass('loading');
			}
			function completeloading(obj){
				obj.closest('.evorow').removeClass('loading');
				obj.closest('p.rsvpmanager_event').removeClass('loading');
			}

		// Find RSVP
			$('body').on('click','.evors_findrsvp_form_btn', function(){
				var obj = $(this);
				var form = obj.closest('form.evors_findrsvp_form');
				FORM_PAR = form.closest('.evors_forms');
				var error = 0;

				// run through each rsvp field
					form.find('.input').each(function(index){
						// check required fields filled
						if( $(this).hasClass('req') && $(this).val()=='' ){
							error = 1;
						}
					});
				if(error=='1'){
					rsvp_error('err','','',form);
				}else{
					var ajaxdataa = {};
					ajaxdataa['action']='evors_find_rsvp_form';
					form.ajaxSubmit({
						beforeSend: function(){
							FORM_PAR.addClass('loading');
						},
						url:	evors_ajax_script.ajaxurl,
						data: 	ajaxdataa,	dataType:'json', type: 	'POST',
						success:function(data){
							if(data.status=='good'){
								FORM_PAR.parent().html( data.content );
								FORM_PAR.parent().removeClass('error');
							}else{
								rsvp_error('err5','','',form);
							}
						},complete:function(){
							FORM_PAR.removeClass('loading');
						}
					});
				}
			});

	// hover over guests list icons
		$('body').on('mouseover','.evors_whos_coming span.initials', function(){
			OBJ = $(this);
			EM = OBJ.parent().find('em.tooltip');
			TEXT = OBJ.data('name');

			POS = OBJ.position();

			EM.css({'left':(POS.left+20), 'top':(POS.top-30)}).html(TEXT).show();

		});
		$('body').on('mouseout','.evors_whos_coming span', function(){
			OBJ = $(this);
			EM = OBJ.parent().find('em.tooltip');
			EM.hide();
		});

	// Buddypress profile linking
		$('body').on('click','.evors_whos_coming span',function(){
			LINK = $(this).data('link');

			if(LINK != 'na')
				window.open(LINK, '_blank');
		});

	// action  user event manager
		// show rsvp stats for events
			$('#evoau_event_manager').on('click','a.load_rsvp_stats',function(event){
				event.preventDefault();

				MANAGER = $(this).closest('.evoau_manager');
				var data_arg = {
					action: 'evors_ajax_get_auem_stats',
					eid: $(this).data('eid')
				};
				$.ajax({
					beforeSend: function(){
						MANAGER.find('.eventon_actionuser_eventslist').addClass('evoloading');
					},
					type: 'POST',
					url:evors_ajax_script.ajaxurl,
					data: data_arg,
					dataType:'json',
					success:function(data){
						$('body').trigger('evoau_show_eventdata',[MANAGER, data.html, true]);
	           		},complete:function(){
						MANAGER.find('.eventon_actionuser_eventslist').removeClass('evoloading');
					}
				});
			});

		// checkin guests
			$('.evoau_manager_event').on('click','span.checkin',function(){
				var obj = $(this);
				var PAR = obj.closest('.evorsau_attendee_list');

				if(!PAR.hasClass('checkable')) return false;

				var status = obj.attr('data-status');

				status = (status=='' || status=='check-in')? 'checked':'check-in';

				var data_arg = {
					action: 'the_ajax_evors_f4',
					rsvp_id: obj.attr('data-id'),
					status:  status,
					nonce: PAR.find('input#evors_nonce').val()
				};
				$.ajax({
					beforeSend: function(){
						obj.html( obj.html()+'...');
					},
					type: 'POST',
					url:evors_ajax_script.ajaxurl,
					data: data_arg,
					dataType:'json',
					success:function(data){
						//alert(data);
						obj.attr({'data-status':status}).html(data.new_status_lang).removeAttr('class').addClass(status+' checkin');
					}
				});
			});


	// Supporting functions
		// show error messages
			function rsvp_error(code, type, message, FORM){
				if(!FORM) return;
				FORM_PAR = FORM.closest('.evors_forms');
				if(message == '' || message === undefined){
					var codes = JSON.parse( FORM.find('.evors_msg_').html());
					var classN = (type== undefined || type=='error' || type == '')? 'err':type;
					message = codes.codes[code]
				}
				FORM.find('.notification').addClass(classN).show().find('p').html(message);
				FORM_PAR.parent().addClass('error');
				FORM.addClass('error');
			}

		// hide form messages
			function rsvp_hide_notifications(){
				$('.evors_lightbox_body').find('.notification').hide();
			}
		// validate humans
			function rsvp_validate_human(field){
				if(field==undefined){
					return true;
				}else{
					var numbers = ['11', '3', '6', '3', '8'];
					if(numbers[field.attr('data-cal')] == field.val() ){
						return true;
					}else{ return false;}
				}
			}

	function is_email(email){
		var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  		return regex.test(email);
	}
});

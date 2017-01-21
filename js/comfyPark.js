var comfyPark = function(){
	return {
		// private vars
		dataUrl: 'index.php',
		lastTimerId: '',
		
		// constructor
		init : function() {
			if(ComfyPark && ComfyPark['site']) {
				switch(ComfyPark['site']){
					case 'login':
						if(ComfyPark['data'] && ComfyPark['data']['success'] != undefined && !ComfyPark['data']['success']) {
							comfyPark.showAlert(ComfyPark['data']['errorMessage'], false);
						}
					break;
					
					case 'home':
						comfyPark.getStatus();
		
						$('.btn_gate').click(function() {
							if(!$(this).hasClass('disabled')) {
								comfyPark.callBackend($(this).attr('data-button'));
							}
						});
					break;
				}
			}
		},
		
		
		getStatus: function(gate) {
			$('#modal_main').modal('show');
			
			var data = new Array();
			data.push({ name: 'cmd', value: 'status'});
			            
			$.ajax({
				url: comfyPark.dataUrl,
				cache: false,
				data: $.param(data),
				error: function(response, textStatus, XMLHttpRequest) {},
				success: function(response, textStatus, XMLHttpRequest) {
					response = $.parseJSON(response);
					if(response && response['success']){
						comfyPark.toggleCheckIn(response);
					}
					else{
						comfyPark.showAlert(response['errorMessage']);
					}

					$('#modal_main').modal('hide');					
				}
			});
		},
			
		callBackend: function(gate) {
			$('#modal_main').modal('show');
			
			var data = new Array();
			data.push({ name: 'cmd', value: 'parking'});			
			data.push({ name: 'gateId', value: gate});
			            
			$.ajax({
				url: comfyPark.dataUrl,
				cache: false,
				data: $.param(data),
				error: function(response, textStatus, XMLHttpRequest) {},
				success: function(response, textStatus, XMLHttpRequest) {
					response = $.parseJSON(response);
					if(response && response['success']){
						comfyPark.showAlert(response['successMessage']);
					}
					else{
						comfyPark.showAlert(response['errorMessage']);
					}
					
					comfyPark.toggleCheckIn(response);
					
					$('#modal_main').modal('hide');
				}
			});
		},
		
		toggleCheckIn: function(response) {			
			if(response['data']['timeIn']) {
				$('.btn_gate_out').removeClass('disabled');
				$('.btn_gate_in').addClass('disabled');
				
				var t = response['data']['timeIn'].split(/[- :]/);
				var timeIn = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);
	
				var strTime = comfyPark.addZero(timeIn.getHours()) + ':' + comfyPark.addZero(timeIn.getMinutes()) + ':' + comfyPark.addZero(timeIn.getSeconds());
											
				$('#alert_info #alert_info_timeIn').html(timeIn.getDate() + '.' + timeIn.getMonth()+1 +'.' + timeIn.getFullYear() + '  ' + strTime);
	
				$('#alert_info #alert_info_parkingTime .timer').remove();
				$('#alert_info #alert_info_parkingTime').html('<span class="timer"></span>');
				
				$('#alert_info #alert_info_parkingTime .timer').countdown({				
					since: timeIn, 
					format: 'H:M:S', 
					description: '',
					compact: true
				});
				
				$('#alert_info #alert_info_parkingTime .timer').countdown('pause');
				$('#alert_info #alert_info_parkingTime .timer').countdown('resume');
					
				$('#alert_info').css('opacity', '1');
			} 
			else {
				$('#alert_info #alert_info_parkingTime .timer').countdown('pause');
				
				$('.btn_gate_in').removeClass('disabled');
				$('.btn_gate_out').addClass('disabled');

				$('#alert_info').css('opacity', '0');		
			}
		},
		
		showAlert: function(message, success) {
			if($('#alert_main').css('opacity') > 0){		
				$('#alert_main').css('top', '-50px');
				$('#alert_main').css('opacity', '0');
				$('#alert_main').stop(true, true);
			}
			
			if(success == false){				
				$('#alert_main').removeClass('alert-success');
				$('#alert_main').addClass('alert-danger');
			}
			else{
				$('#alert_main').addClass('alert-success');
				$('#alert_main').removeClass('alert-danger');		
			}
			
			$('#alert_main .alert_main_body').html(message);
				
			$('#alert_main').animate({
				top: '50px',
				opacity: '1'
			}, 1000);				
			
			$('#alert_main').delay(3000).animate({
				top: '-50px',
				opacity: '0'
			}, 1000);
		},
		
		addZero: function(i) {
			if (i < 10) {
				i = '0' + i;
			}
			return i;
		}
	};
}();

$(document).ready(comfyPark.init);
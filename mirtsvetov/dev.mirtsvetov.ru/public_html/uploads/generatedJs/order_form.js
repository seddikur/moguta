var orderForm = (function () {
 return {
   init: function() {
     $('body').on('change', 'form[action*="/order?creation=1"] input[name="delivery"], form[action*="/order?creation=1"] [name=customer]', function() {
       orderForm.redrawForm();
     });
     $('form[action*="/order?creation=1"] *').removeAttr('data-delivery-address');
     orderForm.redrawForm();
   },
   redrawForm: function() {
     var delivId = 0;
     if ($('form[action*="/order?creation=1"] input[name=delivery]:checked').length) {
       delivId = $('form[action*="/order?creation=1"] input[name=delivery]:checked').val();
     }
     if($.inArray(parseInt(delivId), [0,1,2]) !== -1) {//address
       $('form[action*="/order?creation=1"] [name=address]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=address]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=address]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=address]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_nameyur
       $('form[action*="/order?creation=1"] [name=yur_info_nameyur]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_nameyur]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_nameyur]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_nameyur]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_adress
       $('form[action*="/order?creation=1"] [name=yur_info_adress]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_adress]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_adress]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_adress]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_inn
       $('form[action*="/order?creation=1"] [name=yur_info_inn]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_inn]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_inn]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_inn]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_kpp
       $('form[action*="/order?creation=1"] [name=yur_info_kpp]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_kpp]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_kpp]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_kpp]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_bank
       $('form[action*="/order?creation=1"] [name=yur_info_bank]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_bank]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_bank]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_bank]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_bik
       $('form[action*="/order?creation=1"] [name=yur_info_bik]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_bik]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_bik]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_bik]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_ks
       $('form[action*="/order?creation=1"] [name=yur_info_ks]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_ks]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_ks]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_ks]').closest('.js-orderFromItem').hide();
     }
     if($.inArray($('form[action*="/order?creation=1"] [name=customer]:first').val(), ['yur']) !== -1) {//yur_info_rs
       $('form[action*="/order?creation=1"] [name=yur_info_rs]').prop('disabled', false);
       $('form[action*="/order?creation=1"] [name=yur_info_rs]').closest('.js-orderFromItem').show();
     } else {
       $('form[action*="/order?creation=1"] [name=yur_info_rs]').prop('disabled', true);
       $('form[action*="/order?creation=1"] [name=yur_info_rs]').closest('.js-orderFromItem').hide();
     }
		},
  //Методы для даты доставки
 disableDateMonthForDatepicker:function(monthWeek){
   let disableDateMonth = [];
    for(key in monthWeek){
      let days = monthWeek[key].split(',');
      let month = '';
      switch (key){
        case 'jan' :
         month = '01';
         break;
        case 'feb' :
         month = '02';
         break;     
        case 'mar' :
         month = '03';
         break;
        case 'aip' :
         month = '04';
         break; 
        case 'may' :
         month = '05';
         break; 
        case 'jum' :
         month = '06';
         break;  
        case 'jul' :
         month = '07';
         break;
        case 'aug' :
         month = '08';
         break;     
        case 'sep' :
         month = '09';
         break;
        case 'okt' :
         month = '10';
         break; 
        case 'nov' :
         month = '11';
         break; 
        case 'dec' :
         month = '12';
         break;         
      }
      days.forEach(function(item){
        if(item !== ''){
          if(item < 10){
            item = '0'+item.toString();
          }
          disableDateMonth.push(month+"-"+item);
        }
      });
    }
    return disableDateMonth;
  },
  disableDateWeekForDatepicker:function(daysWeek){
    let disableDateWeek = [];
    for(key in daysWeek){
      if(daysWeek[key] != true){
        let numberOfWeekDay = '';
        switch (key){
          case 'su' : numberOfWeekDay = 0;
            break;
          case 'md' : numberOfWeekDay = 1;
            break;
         case 'tu' : numberOfWeekDay = 2;
           break;
         case 'we' : numberOfWeekDay = 3;
            break;
          case 'thu' : numberOfWeekDay = 4;
            break;  
          case 'fri' : numberOfWeekDay = 5;
            break;
          case 'sa' : numberOfWeekDay = 6;
            break;        
          }
        disableDateWeek.push(numberOfWeekDay);
      }
    }
    return disableDateWeek;
  },
  disableDateForDatepicke: function(day, stringDay, monthWeek, daysWeek){  
    let isDisabledDaysMonth = ($.inArray(stringDay, orderForm.disableDateMonthForDatepicker(monthWeek)) != -1);
    let isDisabledDaysWeek = ($.inArray(day, orderForm.disableDateWeekForDatepicker(daysWeek)) != -1);
    return [(!isDisabledDaysWeek && !isDisabledDaysMonth)];
  }
 };
})();
$(document).ready(function() {
 if (location.pathname.indexOf('/order') > -1) {
   orderForm.init();
 }
});
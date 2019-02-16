$(document).ready(function () {

  $('#startDatePicker').datepicker({
    uiLibrary: 'bootstrap4',
    maxDate: function () {
      return $('#endDatePicker').val() || new Date();
    },
    format: 'yyyy-mm-dd'
  });// datepicker

  $('#endDatePicker').datepicker({
    uiLibrary: 'bootstrap4',
    minDate: function () {
      return $('#startDatePicker').val();
    },
    maxDate: function () {
      let today = new Date();
      let start = $('#startDatePicker').val() ? new Date($('#startDatePicker').val()) : new Date();
      start.setMonth(start.getMonth() + 3);
      return start >= today ? today : start
    },
    format: 'yyyy-mm-dd'
  });// datepicker

  $('#dayDatePicker').datepicker({
    uiLibrary: 'bootstrap4',
    maxDate: function () {
      return new Date();
    },
    format: 'yyyy-mm-dd'
  });// datepicker

  // Set date in miliseconds on hidden date inputs
  $('#startDatePicker, #endDatePicker, #dayDatePicker').on('change', function() {
    var id = $(this).attr('id'),
        date = $(this).val(),
        input = $(this).data('input'),
        today = new Date(),
        dateObject = new Date(date);

    var todayTz = new Date(Date.UTC(today.getFullYear(), today.getMonth(), today.getDate(), today.getHours(), today.getMinutes(), today.getSeconds())),
        dateObjectTz = new Date(Date.UTC(dateObject.getFullYear(), dateObject.getMonth(), dateObject.getDate(), dateObject.getHours(), dateObject.getMinutes(), dateObject.getSeconds()));

    if(id == 'startDatePicker' || id == 'dayDatePicker'){
      var msDate = parseInt(Date.parse(dateObject) / 1000);
    }else{
      var msDate = todayTz.toDateString() === dateObject.toDateString() ? parseInt(Date.parse(todayTz) / 1000) : parseInt((Date.parse(dateObject) + 86399999)/ 1000);
    }
    

    $('#'+input).val(msDate)
  });

  // Set event
  $('.navbar').on('click', 'a[data-toggle="panels"]', togglePanels)

  $('.btn-popover').popover()

  // Show or hide tr without table-danger class
  $('.btn-filter-combustible').click(function(){
    $(`#tbody-diferencia tr:not('.table-danger'), #tbody-auditoria tr:not('.table-danger')`).toggle()
  })

  // 
  $('#units-panel, #units, #units-informe').on('change', function(){
    $('#exportUnit').val($('option:selected', this).text())
  })
})

var POPOVERDATA = {
  ['data-panel']:{
    content: 'Mostrar ubicación de las unidades.'
  },
  ['data-restricciones']:{
    content: 'Mostrar información que sobrepase el limite de velocidad de las Geocercas.'
  },
  ['data-velocidad']:{
    content: 'Mostrar información que sobrepase el limite de velocidad.'
  },
  ['data-parada']:{
    content: 'Parada'
  },
  ['data-descanso']:{
    content: 'Mostrar información de los descansos de la unidad.'
  },
  ['data-prohibicion']:{
    content: 'Mostrar información cuando se hace uso de la unidad en un horario prohibido.'
  },
  ['data-diferencia']:{
    content: 'Mostrar información de la diferencia en el nivel de combustible al arrancar el vehiculo luego de una parada.'
  },
  ['data-auditoria']:{
    content: 'Mostrar información cuando la diferencia en el nivel de combustible al arrancar el vehiculo luego de una parada. Los los cambios de nivel que sobrepasen el 2% se resaltan.'
  },
  ['data-tracks']:{
    content: 'Tracks.'
  },
  ['data-informe1']:{
    content: 'Resumen del día.'
  },
  ['data-videos']:{
    content: 'Videos.'
  },
  ['data-admin']:{
    content: 'Administración'
  }
}

function togglePanels(e) {
  e.preventDefault()
  var el = $(this),
      target = el.data('target').slice(1),
      title  = el.data('title');
  
  if(target != 'data-powerbi'){
    $('.btn-popover').attr('data-content', POPOVERDATA[target].content)
  }

  // Return table headers to the top
  $('.panel-tables thead').css({'-webkit-transform':`translateY(0px)`})
  // Assign panel title
  $('.panel-title').text(title)
  // Hide all panels
  $('.data-panels').removeClass('data-expanded')
  // Panel to show
  $('#exec_btn').prop('disabled', false)

  if(target == 'data-admin'){
    $('#admin').show()
    $('#reports, #informes, #powerbi').hide();
  }else if(target == 'data-panel'){
    $('#panel-units').toggleClass('data-expanded', true)
    $('#panel-reports').toggleClass('data-expanded', false)
    $('#reports, #map, #panel-units').show()
    $('#admin, #informes, #powerbi, #report-units, #map_tracks, #panel-tracks, #panel-videos').hide()
  }else if(target == 'data-tracks'){
    $('#panel-reports').toggleClass('data-expanded', true)
    $('#panel-units').toggleClass('data-expanded', false)
    $('#reports, #panel-tracks, #map_tracks, #report-units').show()
    $('#admin, #informes, #powerbi, #map, #buttons, #panel-videos').hide()

    setTimeout(function(){
      $('#btnRefreshMapTracks').click()
    }, 500)
  }else if(target == 'data-videos'){
    $('#panel-reports').toggleClass('data-expanded', true)
    $('#panel-units').toggleClass('data-expanded', false)
    $('#reports, #map, #panel-videos').show()
    $('#admin, #informes, #powerbi, #map_tracks, #panel-tracks, #buttons, #report-units').hide()
  }else if(target == 'data-informe1'){
    $('#informes').show()
    $('#admin, #reports, #powerbi').hide()
  }else if(target == 'data-powerbi'){
    $('#powerbi').show()
    $('#admin, #reports, #informes').hide()
  }else{
    $('#panel-reports').toggleClass('data-expanded', true)
    $('#panel-units').toggleClass('data-expanded', false)
    $('#reports, #map, #buttons, #report-units').show()
    $('#admin, #informes, #powerbi, #map_tracks, #panel-tracks, #panel-units, #panel-videos').hide()
  }

  $('.btn-filter-combustible').closest('div').toggle( (target == 'data-auditoria' || target == 'data-diferencia') )

  //Report function to execute
  setReportFunction(target)

  //clean tables
  cleanTables()

  $('#get_report_btn').prop('disabled', true)
  $('#export_btn').prop('disabled', true)

  $('.' + target).addClass('data-expanded')
}

//Set report function to execute button
function setReportFunction(target) {
  target = target.replace('data-', '')

  var functionName = 'execute' + target.charAt(0).toUpperCase() + target.substr(1);
  var exportName = 'export' + target.charAt(0).toUpperCase() + target.substr(1);
  //Clear events
  $('#exec_btn, #export_btn').off('click')
  //Set event
  $('#exec_btn').on('click', window[functionName])
  $('#export_btn').on('click', window[exportName])
}

//Clean tables when panels change
function cleanTables() {
  $('#tbody-velocidad, #tbody-restricciones, #tbody-prohibicion, #tbody-parada, #tbody-descanso, #tbody-diferencia, #tbody-auditoria')
    .empty()
}

class requestAjax{

  post(url, params, done = '', fail = '', always = ''){
    if(done === ''){ done = function(){} }
    if(fail === ''){ fail = function(){} }
    if(always === ''){ always = function(){} }

    this.ajax('POST', url, params, done, fail, always)
  }

  get(url, params, done, fail, always){

    if(done === ''){ done = function(){} }
    if(fail === ''){ fail = function(){} }
    if(always === ''){ always = function(){} }

    this.ajax('GET', url, params, done, fail, always)
  }

  ajax(method, url, params, done, fail, always){
    $.ajax({
      method: method,
      data: params,
      url: url,
      contentType: 'application/x-www-form-urlencoded',
      crossDomain: true,
      dataType: 'json'
    })
    .done(function(response){
      done(response)
    })
    .fail(function(e){
      fail()
    })
    .always(function(){
      always()
    })
  }

  serialize(obj) {
    var str = [];
    for (var p in obj)
      if (obj.hasOwnProperty(p)) {
        str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
      }
    return str.join("&");
  }
}
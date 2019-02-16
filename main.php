<?php
  require_once 'db.php';

  $db = new DB;

  $unidades = $db->query('SELECT unidad, cuenta FROM data GROUP BY unidad', []);
  $cuentas = $db->query('SELECT cuenta FROM data GROUP BY cuenta', []);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Consulta | Tracker</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/datepicker/css/bootstrap-datepicker.min.css" />
  <script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>
  <script type="text/javascript" src="//hst-api.wialon.com/wsdk/script/wialon.js"></script>
</head>
<body>
  <div id="log" style="display: none"></div>
  <div class="container" style="margin-top: 30px">
    <div class="row">
      <div class="col-sm-12 col-md-8 offset-2 no-print">
        <form id="consultaForm" action="#" method="POST">
          <div class="form-group">
            <div class="input-daterange input-group">
              <input id="inicioExport" type="text" class="form-control" name="inicio" placeholder="yyyy-mm-dd" required>
              <span class="input-group-addon">Hasta</span>
              <input id="finExport" type="text" class="form-control" name="fin" placeholder="yyyy-mm-dd" required>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label" for="cuenta">Cuenta:</label>
            <select id="cuenta" class="form-control" name="cuenta">
              <option value="">Seleccione...</option>
              <?
                if($cuentas):
                  while($cuenta = $cuentas->fetch(PDO::FETCH_OBJ)):
              ?>
                <option value="<?=$cuenta->cuenta?>"><?=$cuenta->cuenta?></option>
              <?
                  endwhile;
                endif;
              ?>
            </select>
          </div>
          <div class="form-group">
            <label class="control-label" for="unidad">Unidad:</label>
            <select id="unidad" class="form-control" name="unidad">
              <option value="">Seleccione...</option>
              <?
                if($unidades):
                  while($unidad = $unidades->fetch(PDO::FETCH_OBJ)):
              ?>
                <option value="<?=$unidad->unidad?>"><?=$unidad->cuenta?> | <?=$unidad->unidad?></option>
              <?
                  endwhile;
                endif;
              ?>
            </select>
          </div>
          <center style="margin-top: 10px">
            <button id="search" class="btn btn-flat btn-primary" type="submit">Buscar</button>
          </center>

          <div class="alert alert-danger" style="display: none">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong class="text-center">Ha ocurrido un error.</strong> 
          </div>
        </form>
      </div>

      <div class="col-sm-12 col-md-8 offset-2 no-print" style="margin-top: 20px">
        <div class="box box-solid">
          <div class="box-header">
            <h3>
              Registros
              <span class="float-right">
                <button id="exportBtn" class="btn btn-success" disabled>
                  <i class="fa fa-download"></i>
                  Exportar todo
                </button>
              </span>
            </h3>
          </div>
          <div class="box-body">
            <div class="row">
              <div class="col-md-12" style="margin-top: 10px">
                <table class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th class="text-center">#</th>
                      <th class="text-center">Cuenta</th>
                      <th class="text-center">Unidad</th>
                      <th class="text-center">Nombre</th>
                      <th class="text-center">Fecha</th>
                      <th class="text-center">Acción</th>
                    </tr>
                  </thead>
                  <tbody id="tbody-xml">
                    <tr>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/js/jquery-3.3.1.min.js"></script>
  <script src="assets/js/bootstrap.min.js"></script>
  <script src="assets/datepicker/js/bootstrap-datepicker.min.js"></script>

  <script type="text/javascript">
    $(document).ready(function(){
      $('.input-daterange').datepicker({
        format: 'yyyy-mm-dd',
        language: 'es',
        keyboardNavigation: false
      });

      $('#consultaForm').submit(getEvents)
      $('#exportBtn').click(exportData)

      wialon.core.Session.getInstance().initSession('https://hst-api.wialon.com'); // init session

      wialon.core.Session.getInstance().loginToken(TOKEN, '',
        function (code) {
          if (code){
            window.location.replace('index.html')
            return;
          }
          msg('Logged successfully');
          init();
      });

    })

    let UNITS = [];
    let TOKEN = localStorage.token || null;
    let UNIT_IN_USE = -1;
    let UNITS_TOTAL = 0;
    let UNIT_INFO = [];
    let UNIT_WORKING = false;

    let CONSULTA = null;

    function getEvents(e){
      e.preventDefault();

      let form   = $(this),
          action = 'data.php',
          alert  = $('.alert'),
          btn    = $('#search');

      btn.button('loading');
      alert.hide();

      $.ajax({
        type: 'POST',
        url: action,
        data: {
          action: 'consulta',
          inicio: $('#inicioExport').val(),
          fin: $('#finExport').val(),
          cuenta: $('#cuenta').val(),
          unidad: $('#unidad').val()
        },
        dataType: 'json',
      })
      .done(function(response){
        $('#tbody-xml').empty();

        if(response.response){
          $.each(response.registros, function(i, registro){
            let tr = '<tr>'
            tr += `<td class="text-center">${i+1}</td>`
            tr += `<td class="text-center">${registro.cuenta}</td>`
            tr += `<td class="text-center">${registro.unidad}</td>`
            tr += `<td class="text-center">${registro.nombre}</td>`
            tr += `<td class="text-center">${registro.fecha}</td>`
            tr += `<td class="text-center"><a href="${registro.path}" download>Descargar</a></td>`
            tr += '</tr>'

            $('#tbody-xml').append(tr)
          })

          $('#exportBtn').prop('disabled', false)
        }else{
          alert.show().delay(7000).hide('slow');
          alert.find('.text-center').text('No hay registros.');
          $('#exportBtn').prop('disabled', true)
        }
      })
      .fail(function(){
        alert.show().delay(7000).hide('slow');
        alert.find('.text-center').text('Ha ocurrido un error.');
        $('#exportBtn').prop('disabled', true)
        $('#tbody-xml').empty();
      })
      .always(function(){
        btn.button('reset');
      })
    }

    function exportData(){
      let action = 'data.php',
          alert  = $('.alert'),
          btn    = $('#exportBtn');

      btn.button('loading');
      alert.hide();

      $.ajax({
        type: 'POST',
        url: action,
        data: {
          action: 'export',
          inicio: $('#inicioExport').val(),
          fin: $('#finExport').val(),
          cuenta: $('#cuenta').val(),
          unidad: $('#unidad').val()
        },
        dataType: 'json',
      })
      .done(function(response){
        if(response.response){
          window.open(response.path, '_blank', 'width=760, height=500, top=300, left=500');
        }else{
          alert.show().delay(7000).hide('slow');
          alert.find('.text-center').text('Ha ocurrido un error.');
        }
      })
      .fail(function(){
        alert.show().delay(7000).hide('slow');
        alert.find('.text-center').text('Ha ocurrido un error.');
      })
      .always(function(){
        btn.button('reset');
      })
    }

    // Print message to log
    function msg(text) { $('#log').prepend(text + '<br/>'); }

    function init() {
      let sess = wialon.core.Session.getInstance();
      let flags = wialon.item.Item.dataFlag.base | wialon.item.Unit.dataFlag.lastMessage;

      sess.updateDataFlags(
        [{type: 'type', data: 'avl_unit', flags: flags, mode: 0}],
        function (code) {
          if (code) { msg(wialon.core.Errors.getErrorText(code)); return; }

          let units = sess.getItems('avl_unit');
          if (!units || !units.length){ msg('Units not found'); return; }

          UNITS_TOTAL = units.length - 1

          for (let i = 0; i < units.length; i++){
            let name = units[i].getName().split(' '),
                unit = name[0],
                index = name[1].indexOf('('),
                cuenta = name[1];

            if(index >= 0){
              cuenta = cuenta.slice(0, index)
            } 

            let data = {
              id: units[i].getId(),
              unit: unit,
              cuenta: cuenta,
            }

            UNITS.push(data);
          }

          consulta();
        }
      );
    }

    function consulta(){
      CONSULTA = setTimeout(consulta, 600000)
      unitsTimer()
    }

    function unitsTimer(){
      UNIT_TIMER = setTimeout(unitsTimer, 1000);

      if(!UNIT_WORKING){
        if(UNIT_IN_USE < UNITS_TOTAL){
          UNIT_WORKING = true;
          UNIT_IN_USE++
          loadMessages()
        }else{
          unitsStopTimer();
        }
      }
    }

    function unitsStopTimer(){
      UNIT_IN_USE = 0;
      UNIT_WORKING = false;
      UNIT_INFO = []

      clearTimeout(UNIT_TIMER);
      UNIT_TIMER = null;
    }

    function sendData(){

      $.ajax({
        method: 'POST',
        url: 'data.php',
        cache: false,
        data: {
          action: 'store',
          data: JSON.stringify(UNIT_INFO)
        },
        dataType: 'json',
      })
      .done(function(e){
        UNIT_WORKING = false;
      })
      .fail(function(){
        UNIT_WORKING = false;
      })
    }

    function loadMessages(){
      let sess = wialon.core.Session.getInstance(),
          to = sess.getServerTime(),
          from = to - 600,
          unit = UNITS[UNIT_IN_USE].id,
          ml = sess.getMessagesLoader();
      ml.loadInterval(unit, from, to, 0, 0, 100,
        function(code, data){
          if(code){
            UNIT_WORKING = false
          }else{
            if(data.count <= 0){
              UNIT_WORKING = false
            }else{
              getMessages(data.count)
            }
          }
        }
      );
    }

    function getMessages(total){
      let ml = wialon.core.Session.getInstance().getMessagesLoader(); 
      ml.getMessages(0, total,
        function(code, data){
          for(var i = 0; i < data.length; i++){
            let pos   = data[i].pos,
                time  = data[i].t
                jsDate = new Date(time*1000),
                year  = jsDate.getFullYear(),
                month = (jsDate.getMonth() + 1) < 10 ? '0' + (jsDate.getMonth() + 1) : (jsDate.getMonth() + 1),
                x     = 0,
                y     = 0,
                speed = data[i].p.max_speed || 0;

            if(pos){
              x     = pos.x
              y     = pos.y
              speed = pos.s
            }

            let info = {
              time: time,
              id: UNITS[UNIT_IN_USE].id,
              cuenta: UNITS[UNIT_IN_USE].cuenta,
              unit: UNITS[UNIT_IN_USE].unit,
              mes: `${year}${month}`,
              imei: '-',
              direccion: '-',
              latitud: y,
              longitud: x,
              velocidad: speed
            }
            UNIT_INFO.push(info)
          }

          sendData();
        }
      );
    }
  </script>
</body>
</html>
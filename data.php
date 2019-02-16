<?php
require_once 'db.php';

class Data
{

  public static function store()
  {
    $db = new DB;
    $registros = json_decode($_POST['data']);

    foreach ($registros as $registro) {
      date_default_timezone_set('UTC');
      $utc = date('Y-m-d H:i:s', $registro->time);

      date_default_timezone_set('America/Santiago');
      $chile = date('Y-m-d H:i:s', $registro->time);

      $db->query(
        'INSERT INTO data (tiempo, cuenta, unidad, registro, mes, imei, fecha_chile, fecha_utc, direccion, latitud, longitud, velocidad) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
          $registro->time,
          $registro->cuenta,
          $registro->unit,
          '-'.$chile.'-',
          $registro->mes,
          $registro->imei,
          $chile,
          $utc,
          $registro->direccion,
          $registro->latitud,
          $registro->longitud,
          $registro->velocidad
        ]
      );
    }

    echo json_encode(['response'=>true]);
  }

  public function get($echo = true)
  {
    $db = new DB(2);

    $inicio = date($_POST['inicio']);
    $fin = date($_POST['fin']);

    $cuenta = $_POST['cuenta'] != '' ? $_POST['cuenta'] : null;
    $unidad = $_POST['unidad'] != '' ? $_POST['unidad'] : null;

    if($cuenta && !$unidad){
      $sql = 'SELECT cuenta, unidad, nombre, fecha, path FROM xml WHERE cuenta = ? AND fecha BETWEEN ? AND ?';
      $params = [$cuenta, $inicio, $fin];
    }

    if(!$cuenta && $unidad){
      $sql = 'SELECT cuenta, unidad, nombre, fecha, path FROM xml WHERE unidad = ? AND fecha BETWEEN ? AND ?';
      $params = [$unidad, $inicio, $fin];
    }

    if($cuenta && $unidad){
      $sql = 'SELECT cuenta, unidad, nombre, fecha, path FROM xml WHERE cuenta = ? AND unidad = ? AND fecha BETWEEN ? AND ?';
      $params = [$cuenta, $unidad, $inicio, $fin];
    }

    if(!$cuenta && !$unidad){
      $sql = 'SELECT cuenta, unidad, nombre, fecha, path FROM xml WHERE fecha BETWEEN ? AND ?';
      $params = [$inicio, $fin];
    }

    $registros = $db->query($sql, $params);

    $data = [];

    if($registros){
      while($registro = $registros->fetch(PDO::FETCH_OBJ)){
        $data[] = $registro;
      }

      $response = true;
    }else{
      $response = false;
    }

    $result = ['response' => $response, 'registros' => $data];

    if($echo){
      echo json_encode($result);
    }else{
      return (object)$result;
    }
  }

  public function zip()
  {
    $data = Data::get(false);

    if($data->response){
      $cuenta = $_POST['cuenta'] != '' ? $_POST['cuenta'] : null;
      $unidad = $_POST['unidad'] != '' ? $_POST['unidad'] : null;

      $zipName = 'zip/'. time();

      if($cuenta){
        $zipName .= '-'.$cuenta;
      }

      if($unidad){
        $zipName .= '-'.$unidad;
      }

      $zipName .= '.zip';
      $zip = new ZipArchive;

      if ($zip->open($zipName, ZipArchive::CREATE) !== true) {
        echo json_encode(['response' => false]);
        die();
      }

      foreach ($data->registros as $file) {
        $zip->addFile($file->path);
      }

      $zip->close();

      echo json_encode(['response' => true, 'path' => $zipName]);
    }else{
      echo json_encode(['response' => false]);
    }
  }
}

if(isset($_POST['action'])){
  switch ($_POST['action']) {
    case 'store':
      Data::store();
    break;
    case 'consulta':
      Data::get();
    break;
    case 'export':
      Data::zip();
    break;
  }
}
?>
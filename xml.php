<?php
require_once 'db.php';

class Xml
{
  private static $directory = 'xmls';

  public static function createXml()
  {
    $db  = new DB();
    $db2 = new DB(2);

    $unidades = $db->query('SELECT unidad, cuenta FROM data GROUP BY unidad', []);
    if($unidades){
      while($unidad = $unidades->fetch(PDO::FETCH_OBJ)){
        $dataUnidad = $db->query('SELECT * FROM data WHERE unidad = ? AND procesado = ?', [$unidad->unidad, 0]);
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xmlElement = $xml->createElement('xml');
        $xml->formatOutput = true;

        date_default_timezone_set('America/Santiago');
        $fecha = date('Y-m-d');
        
        $name = $unidad->unidad.'-'.$fecha;
        $path = Xml::$directory.'/'.$name.'.xml';

        if($dataUnidad){
          while($data = $dataUnidad->fetch(PDO::FETCH_OBJ)){
            $registro = $xml->createElement('registro');

            $registro->appendChild($xml->createElement('registroID', $data->registro));
            $registro->appendChild($xml->createElement('mesInformacion', $data->mes));
            $registro->appendChild($xml->createElement('imei', $data->imei));
            $registro->appendChild($xml->createElement('fechaHoraChileGps', $data->fecha_chile));
            $registro->appendChild($xml->createElement('fechaHoraGreenwichGps', $data->fecha_utc));
            $registro->appendChild($xml->createElement('direccionGps', $data->direccion));
            $registro->appendChild($xml->createElement('latitudGps', $data->latitud));
            $registro->appendChild($xml->createElement('longitudGps', $data->longitud));
            $registro->appendChild($xml->createElement('velocidadGps', $data->velocidad));

            $xmlElement->appendChild($registro);

            $db->query('UPDATE data SET procesado = ? WHERE id = ?', [1, $data->id]);
          }
          $xml->appendChild($xmlElement);
          $xml->save($path);

          $db2->query('INSERT INTO xml (cuenta, unidad, nombre, path, fecha) VALUES (?, ?, ?, ?, ?)', [$unidad->cuenta, $unidad->unidad, $name, $path,$fecha]);
        }
      }
    }
  }
}

if(isset($_GET['action']) && $_GET['action'] == 'create'){
  Xml::createXml();
}
?>

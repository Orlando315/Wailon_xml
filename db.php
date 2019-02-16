<?php

class DB
{
  private $params = [
                      [
                        'host' => 'localhost',
                        'database' =>'tracker_xml',
                        'user' => 'root',
                        'password' => ''
                      ],
                      [
                        'host' => 'localhost',
                        'database' =>'tracker_xml2',
                        'user' => 'root',
                        'password' => ''                          
                      ]
                    ];

  private $server;
  private $conex;
  private $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);

  protected $con;

  public function __construct($conex = 1){
    $this->conex = $conex;
    $this->server = "mysql:host={$this->params[$this->conex-1]['host']};dbname={$this->params[$this->conex-1]['database']}";
    $this->connect();
    
  }

  protected function connect()
  {
    try{
      $this->con = new PDO($this->server, $this->params[$this->conex-1]['user'], $this->params[$this->conex-1]['password'], $this->options);
    }catch (PDOException $e){
      echo 'No se puede establecer conexión con la Base de Datos';
    }
  }

  public function query($query, $params)
  {
    try{
      $prepare = $this->con->prepare($query);
      $prepare->execute($params);
      $total = $prepare->rowCount();

      if($total > 0){
        return $prepare;
      }

      return false;
    }catch (PDOException $e){
      echo $e->getMessage();
      return false;
    }
  }

  public function getByTime($time)
  {
    try{
      $prepare = $this->con->prepare('SELECT id FROM data WHERE tiempo = ?');
      $prepare->bindParam(1, $time);
      $prepare->execute();
      $total = $prepare->rowCount();

      if($total > 0){
        return true;
      }

      return false;
    }catch (PDOException $e){
      return false;
    }
  }
}

?>
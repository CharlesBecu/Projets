<?php
  header( 'Access-Control-Allow-Origin:*' ); // autoriser les requête de n'importe quel site
  header( 'Access-Control-Allow-Headers: Content-Type'); 
  header( 'Access-Control-Allow-Methods: POST, PUT, GET, DELETE' );
  header( 'Content-type:application/json' ); // la réponse renvoyé sera en json
  require 'methods.php';

  if($_SERVER[ "REQUEST_METHOD" ] == OPTIONS)
  {
    answer(200, '');
  }
  $Routing = new Router();
  $Routing->addRoute( GET, '', function ( $req ) 
  {
    header( 'Location: ../doc/routes' );
  } );
  $Routing->addRoute( GET, 'ads', function ( $req )
  {

    $bdd = connect();
    $get = $req->body;
    $sql = "SELECT `ID`, `START`, `OWNER`, `END`, `TITLE`, `COMPANY`, `LOCATION`, `CONTRACT`, `TAGS`, `SALARY`, `PREVIEW` FROM `JOBAD` WHERE 1";
    /* 
      o => filter:OWNER
      d => sort:END
      t => filter:CONTRACT
      c => filter:COMPANY
      l => filter:LOCATION
      a => filter:TITLE
    */
    if ( !empty($get) ) 
    {
      $filters = '';
      if ( isset( $get[ "o" ] ) ) 
      {
        $fil = htmlspecialchars( $get[ "o" ] );
        $filters = $filters . " AND `OWNER`=$fil";
      }
      if ( isset( $get[ "l" ] ) ) 
      {
        $fil = htmlspecialchars( $get[ "l" ] );
        $filters = $filters . " AND `LOCATION`='$fil'";
      }
      if ( isset( $get[ "t" ] ) ) 
      {
        $fil = htmlspecialchars( $get[ "t" ] );
        $filters = $filters . " AND `CONTRACT`='$fil'";
      }
      if ( isset( $get[ "c" ] ) ) 
      {
        $fil = htmlspecialchars( $get[ "c" ] );
        $filters = $filters . " AND `COMPANY`=$fil";
      }
      if ( isset( $get[ "a" ] ) ) 
      {
        $fil = htmlspecialchars( $get[ "a" ] );
        if($fil !='all'){
          $filters = $filters . " AND TITLE LIKE '%$fil%'";
        }
      }
      $sql = $sql . $filters;
    
      if ( isset( $get[ "d" ] ) ) 
      {
        $page = htmlspecialchars( $get[ "d" ] );
        $sql = $sql . " ORDER BY END ASC";
      }
      if ( isset( $get[ "p" ] ) ) 
      {
        $page = htmlspecialchars( $get[ "p" ] );
        if( $page != "all"){
          $sql = $sql . " LIMIT " . ( ( $page - 1 ) * 10 ) . ", 10";
        }
      }else
      {
        $sql = $sql . " LIMIT 0, 10";
      }
    }
    $bdd_reponse = $bdd->query( $sql );
    $adlist = array();
    while ( $data = $bdd_reponse->fetch() ) 
    {
      for ( $i = 0; $i <= 11; $i++ ) 
      {
        unset( $data[ $i ] );
      }
      array_push( $adlist, $data );
    }

    $bdd_reponse->closeCursor();
    if(empty($adlist))
    {
      answer(404, "");
    }
    answer( 200, $adlist );
  } );
  $Routing->addRoute( GET, 'ads\/\d+', function ( $req ) 
  {
    $bdd = connect();
    $get = $req->body;
    $id = (int)htmlspecialchars( preg_replace( '/\/?\z/', '', preg_replace( '/\A\/?ads\//', '', $req->url ) ) );
    $sql = "SELECT `ID`, `START`, `OWNER`, `END`, `TITLE`, `COMPANY`, `LOCATION`, `CONTRACT`, `TAGS`, `SALARY`, `PREVIEW`, `FULL` FROM `JOBAD` WHERE `ID`=" . $id;

    $bdd_reponse = $bdd->query( $sql );
    $adlist = array();
    while ( $data = $bdd_reponse->fetch() ) 
    {
      for ( $i = 0; $i <= 15; $i++ ) 
      {
        unset( $data[ $i ] );
      }
      array_push( $adlist, $data );
    }

    $bdd_reponse->closeCursor();
    count( $adlist ) != 0 ? answer( 200, $adlist ) : answer( 404, "Match not found : We couldn't find an ad with the ID : " . $id );
  } );
  $Routing->addRoute( POST, 'ads', function ( $req ) 
  {
    $bdd = connect();
    $post = $req->body;
    $sql = "INSERT INTO `JOBAD`(`ID`,`START`, `OWNER`, `END`, `TITLE`, `COMPANY`, `LOCATION`, `CONTRACT`, `TAGS`, `SALARY`, `PREVIEW`, `FULL`, `VIEW`, `OPEN`, `APPLICANT`, `CHATS`) VALUES ('',CURDATE(),:owner,:end,:title,:cId,:loc,:type,:tags,:money,:pre,:full,0,0,'[]','[]')";
    $prep = $bdd->prepare( $sql );
    $val = array(
      ':owner' => $post->ownerId,
      ':end' => $post->endDate,
      ':title' => $post->title,
      ':cId' => $post->companyId,
      ':loc' => $post->location,
      ':type' => $post->contract,
      ':tags' => json_encode($post->tags),
      ':money' => $post->salary,
      ':pre' => $post->preview,
      ':full' => $post->full
    );
    $prep->execute( $val )or answer( 400, 'Sorry, we could not execute this operation. Your entry might be invalid somehow.' );
    answer(200,$bdd->lastInsertId());
  } );
  $Routing->addRoute( DELETE , 'ads\/\d+', function ( $req ) 
  {
    $bdd = connect();
    $get = $req->body;
    $id = ( int )htmlspecialchars( preg_replace( '/\/?\z/', '', preg_replace( '/\A\/?ads\//', '', $req->url ) ) );
    if ( is_int( $id ) ) 
    {
      if ( $id > 0 ) 
      {
        $sql = "DELETE FROM `JOBAD` WHERE `ID`=" . $id;
        $bdd->exec( $sql )or answer( 400, 'Sorry, we could not execute this operation. Your entry might be invalid somehow. DELETE' );
      }
    } 
    else 
    {
      answer( 404, 'Ad not found, therefore cannot be deleted.' );
    }
  } );
  $Routing->addRoute( GET , 'ads\/\d+\/stats', function ( $req ) 
  {
    $bdd = connect();
    $get = $req->body;
    $id = ( int )htmlspecialchars( preg_replace( '/\/stats\/?\z/', '', preg_replace( '/\A\/?ads\//', '', $req->url ) ) );
    $sql = "SELECT `ID`, `VIEW`, `OPEN`, `APPLICANT`, `CHATS` FROM `JOBAD` WHERE `ID`=" . $id;

    $bdd_reponse = $bdd->query( $sql );
    $adlist = array();
    while ( $data = $bdd_reponse->fetch() ) 
    {
      for ( $i = 0; $i <= 15; $i++ ) 
      {
        unset( $data[ $i ] );
      }
      array_push( $adlist, $data );
    }

    $bdd_reponse->closeCursor();
    count( $adlist ) != 0 ? answer( 200, $adlist[0] ) : answer( 404, "Match not found : We couldn't find an ad with the ID : " . $id );
  });
  $Routing->addRoute( POST, 'ads\/\d+\/stats', function ( $req )
  {
    $bdd = connect();
      $post = $req->body;
      $id = ( int )htmlspecialchars( preg_replace( '/\/stats\/?\z/', '', preg_replace( '/\A\/?ads\//', '', $req->url ) ) ); // filtre l'url pour récupérer l'id de l'ads
    if($id < 1){answer(404,'Invalid ID');}
    // On récupère les données
    $sql = "SELECT `ID`, `VIEW`, `OPEN`, `APPLICANT`, `CHATS` FROM `JOBAD` WHERE `ID`=" . $id;
      $bdd_reponse = $bdd->query( $sql );
    $data = $bdd_reponse->fetch();
    $bdd_reponse->closeCursor();
    
    // On met à jour les donnée
    $sql = "UPDATE `JOBAD` SET `VIEW`=:view, `OPEN`=:open, `APPLICANT`=:users, `CHATS`=:convs WHERE `ID`=" . $id;
      $prep = $bdd->prepare( $sql );
    
    $vues = (int)$data['VIEW'] + (!isset($post->add) ? 0 : isset($post->add->view) ? $post->add->view : 0);
    $vues = $vues - (!isset($post->remove) ? 0 : isset($post->remove->view) ? $post->remove->view : 0);
    
    $ouv = (int)$data['open'] + (!isset($post->add) ? 0 : isset($post->add->open) ? $post->add->open : 0);
    $ouv = $ouv - (!isset($post->remove) ? 0 : isset($post->remove->open) ? $post->remove->open : 0);
    
    $users = json_decode($data['APPLICANT']);
    if(!is_array($users)){$convs = array("");}
    if(isset($post->add)){ if( isset($post->add->user)){ array_push($users, $post->add->user);}};
    if(isset($post->remove)){ if( isset($post->remove->user)){ $users = array_diff($users, array($post->remove->user));}};
    $users = json_encode(array_merge(array_filter($users)));
    
    $convs = json_decode($data['CHATS']);
    if(!is_array($convs)){$convs = array("");}
    if(isset($post->add)){ if( isset($post->add->chats)){ array_push($convs, $post->add->chats);}};
    if(isset($post->remove)){ if( isset($post->remove->chats)){ $convs = array_diff($convs, array($post->remove->chats));}};
    $convs = json_encode(array_merge(array_filter($convs)));
    
      $val = array(
      ':view' => $vues,
      ':open' => $ouv,
      ':users' => count($users) ? $users : '[""]',
      ':convs' => count($convs) ? $convs : '[""]',
      );
    $prep->execute( $val ) or answer( 400, 'Sorry, we could not execute this operation. Your entry might be invalid somehow.' );
    answer(200,$post);
  });

  $Routing->addRoute(GET, 'ads\/\d+\/nbrView', function ( $req)
  {
    $bdd = connect();
    $get = $req->body;
    $id = ( int )htmlspecialchars( preg_replace( '/\/nbrView\/?\z/', '', preg_replace( '/\A\/?ads\//', '', $req->url ) ) );
    $sql = "SELECT `ID`, `VIEW` FROM `JOBAD` WHERE `ID`=" . $id;
  });


  $Routing->addRoute( GET , 'ads\/\d+\/full', function ( $req ) 
  {
    $bdd = connect();
    $get = $req->body;
    $id = ( int )htmlspecialchars( preg_replace( '/\/full\/?\z/', '', preg_replace( '/\A\/?ads\//', '', $req->url ) ) );
    $sql = "SELECT `ID`, `FULL` FROM `JOBAD` WHERE `ID`=" . $id;

    $bdd_reponse = $bdd->query( $sql );
    $adlist = array();
    while ( $data = $bdd_reponse->fetch() ) {
      for ( $i = 0; $i <= 15; $i++ ) {
        unset( $data[ $i ] );
      }
      array_push( $adlist, $data );
    }

    $bdd_reponse->closeCursor();
    count( $adlist ) != 0 ? answer( 200, $adlist[0] ) : answer( 404, "Match not found : We couldn't find an ad with the ID : " . $id );
  });
  $Routing->addRoute( PUT , 'ads\/\d+\/full', function ( $req ) 
  {
    $bdd = connect();
    $put = $req->body;
    $id = ( int )htmlspecialchars( preg_replace( '/\/full\/?\z/', '', preg_replace( '/\A\/?ads\//', '', $req->url ) ) );
    $sql = "UPDATE `JOBAD` SET `FULL`=:full WHERE `ID`=" . $id;
    $prep = $bdd->prepare( $sql );

    $bdd_reponse = $bdd->query( $sql );
    if(empty($put)){answer(400,'');}
    $prep->execute( array(':full' => json_encode($put)) ) or answer( 400, 'Sorry, we could not execute this operation. Your entry might be invalid somehow.' );
    answer(200,'');
  });

  
  function verificationCompany ($idCompany, $level, $req)
  {
    if($level == 1)
    {
      if($idCompany == null)
      {
        $bdd = connect();
        $sql = "INSERT INTO 'COMPANY'";
        $prep = $bdd->execute( $sql );
        $sql = "SELECT MAX('ID') FROM 'COMPANY'";
        $prep = $bdd->execute( $sql );
        return $prep;
      }
      else
      {
        $bdd = connect();
        $sql = 'SELECT `ID` FROM `COMPANY` WHERE `ID` =' . $idCompany;
        $prep = $bdd->query($sql);
        if($prep != $idCompany)
        {
          answer(404, 'COMPANY NOT FOUND');
        }
      }
    }
  }

  $Routing->addRoute(POST, 'users', function ( $req) //création d'un utilisateur
  {
    $bdd = connect();
    $sql = "INSERT INTO 'USERS'('ID', 'LEVEL', 'NAME', 'SURNAME', 'MAIL', 'PASSWORD', 'NOTIFS', 'CONVS', 'ADS', 'COMPANY', 'FILES', 'VERIFIED') VALUES ('', :level, :name, :surname, :mail, :password, '[]', '[]', '[]', :company, :files, :verified)";
    $prep = $bdd->prepare(  $sql );
    $post = $req->body;
    $val = array(
      ':level' => $post->level,
      ':name' => $post->name,
      ':surname' => $post->surname,
      ':mail' => $post->mail,
      ':password' => $post->password,
      ':company' => verificationCompany($post->company, $post->level, $req),
      ':files' => '{"profil":"https://emmanuel.nuiro.me/kiwi/FRONT/Img/user.png"}',
      ':verified' => rand ( 100000000 , 999999999 ),
    );
    $prep = $prep->execute( $val );
  });

  /*$Routing->addRoute(POST, 'users', function ( $req) //accès au donnée d'un utilisateur
  {
    $bdd = connect();
    $post = $req->body;
    $sql = "SELECT * FROM 'USERS'";
    $prep = $bdd->query( $sql );
  }*/

  $Routing->execute( new Requete );


  

?>
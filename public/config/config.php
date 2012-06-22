<?php

$dbhost='localhost';
$dbname='psytest';
$dbuser='root';
$dbpass='root';


require_once "newuser.php";
require_once "user.php";
require_once "study.php";


/* require_once $_SERVER['DOCUMENT_ROOT']."/tmp/config/user.php"; */
/* require_once $_SERVER['DOCUMENT_ROOT']."/tmp/config/website.php"; */


/* require_once $_SERVER['DOCUMENT_ROOT']."/tmp/config/newuser.php"; */
/* require_once $_SERVER['DOCUMENT_ROOT']."/tmp/config/user.php"; */
/* require_once $_SERVER['DOCUMENT_ROOT']."/tmp/config/website.php"; */
/* require_once $_SERVER['DOCUMENT_ROOT']."/tmp/config/ad.php"; */
/* require_once $_SERVER['DOCUMENT_ROOT']."/tmp/config/default_ad.php"; */
/* require_once $_SERVER['DOCUMENT_ROOT']."/tmp/fpdf/rechnung.php"; */

/* require_once("newuser.php"); */
/* require_once("user.php"); */

session_start();

if(isset($_SESSION["userObj"]) && is_object($_SESSION["userObj"]))
  $currentUser=$_SESSION["userObj"];


//$available_languages[0] is default language 
$available_languages=array("de","en");

//pages in main navigation
$pages=array("contact.php" => "Contact",
             "faq.php" => "FAQ",
             "index.php" => "Home",
             );

$language=getLanguage();
/* require_once("./lang/".$language.".php");   */
require_once $_SERVER['DOCUMENT_ROOT']."/tmp/lang/".$language.".php";


/* function loadLanguageFile($la=NULL) { */
/*   if($la===NULL)  */
/*     $la=getLanguage(); */
/*   include("lang/".$la.".php");   */
/* } */


//if $lang is a valid language, $lang is returned. otherwise the default language is returned
function validLangOrDefault($lang) {
  global $available_languages;
  foreach($available_languages as $l) {
    if($lang===$l)
      return $lang;
  }
  return $available_languages[0];
}

function getLanguage() {
  global $currentUser,$available_languages;
  $lang=$available_languages[0];
  if(isset($_GET['lang']))
    $lang=$_GET['lang'];
  elseif(isset($_POST['lang']))
    $lang=$_POST['lang'];
  elseif(isset($_SESSION['lang']))
    $lang=$_SESSION['lang'];
  elseif(isset($_COOKIE['lang']))
    $lang=$_COOKIE['lang'];
  elseif(userIsLoggedIn() and isset($currentUser))
    $lang=$currentUser->default_language;
  return validLangOrDefault($lang);
}

function setLanguage($lang) {
  $_SESSION['lang']=$lang;
  setcookie('lang',$lang);
}

function tokenValid($email,$token) {
  global $dbhost,$dbname,$dbuser,$dbpass,$lang;
  $conn=mysql_connect($dbhost,$dbuser,$dbpass);
  if(!$conn)
    return $lang['CONNECT_ERROR'];
  if(!mysql_select_db($dbname,$conn)) {
    mysql_close();
    return $lang['DBSELECT_ERROR'];
  }
  $query="UPDATE users SET email_verified = 1 WHERE email='".mysql_real_escape_string(trim($email))."' AND email_token='".mysql_real_escape_string(trim($token))."'";
  $res=mysql_query($query);
  if($res!==true)
    return "Query error";
  mysql_close();
  return true;
}

function validateToken($token) {
  global $dbhost,$dbname,$dbuser,$dbpass,$lang;
  $conn=mysql_connect($dbhost,$dbuser,$dbpass);
  if(!$conn)
    return $lang['CONNECT_ERROR'];
  if(!mysql_select_db($dbname,$conn)) {
    mysql_close();
    return $lang['DBSELECT_ERROR'];
  }
  $query="SELECT email_token FROM users WHERE email_token='".mysql_real_escape_string(trim($token))."'";
  $res=mysql_query($query);
  if(mysql_num_rows($res)===false or mysql_num_rows($res)===0)
    return true;
  return false;
}

function generateActivationToken() {
  $token;
  do {
    $token=md5(uniqid(mt_rand(),true));
  } while(!validateToken($token));
  return $token;
}

  /* function sendActivationMail($mail,$token) { */
  /*   return mail($mail,"Activate Account","http://www2.amazown.net/activate_account.php?email=".$mail."&token=".$token); */
  /* } */


  function sendActivationMail($mail,$token) {
    if($mail==='' or $token==='')
      return false;
    $link="http://www2.amazown.net/activate_account.php?email=".$mail."&token=".$token."";
    //return mail($mail,"Activate Account",$link);
    return true;
  }

function generateHash($plainText,$salt=null) {
  if($salt===null) 
    $salt=substr(md5(uniqid(rand(),true)),0,25);
  else
    $salt = substr($salt, 0, 25);
  return $salt . sha1($salt . $plainText);
}

function errorOutput($errors) {
  if(count($errors)<1) {
    return;
  } else {
    echo "<ul>";
    foreach($errors as $error) {
      echo "<li>".$error."</li>";
    }
    echo "</ul>";
  }
}

function userIsLoggedIn() {
  global $currentUser;
  global $dbhost,$dbname,$dbuser,$dbpass;
  if($currentUser==NULL)
    return false;
  $conn=mysql_connect($dbhost,$dbuser,$dbpass);
  if(!$conn)
    return false;
  if(!mysql_select_db($dbname,$conn)) {
    mysql_close();
    return false;
  }
  $email=$currentUser->email;
  $pwd=$currentUser->password;
  $query="SELECT email, password FROM users "; //todo:check for active==1
  $query.="WHERE email='$email' AND password='$pwd'";
  $ret=mysql_query($query);
  mysql_close();
  if($ret!==false)
    return true;
  return false;
}

function userIsAdmin() {
  global $currentUser;
  if(!userIsLoggedIn())
    return false;
  global $dbhost,$dbname,$dbuser,$dbpass;
  $conn=mysql_connect($dbhost,$dbuser,$dbpass);
  if(!$conn)
    return false;
  if(!mysql_select_db($dbname,$conn)) {
    mysql_close();
    return false;
  }
  $email=$currentUser->email;
  $pwd=$currentUser->password;
  $query="SELECT admin FROM users ";
  $query.="WHERE email='$email' AND password='$pwd'";
  $ret=mysql_query($query);
  if($ret===false)
    return false;
  if(mysql_num_rows($ret)===false)
    return false;
  $row=mysql_fetch_array($ret);
  if(isset($row['admin']) && $row['admin']==='1')
    return true;
  return false;
}

function monthValid($month) {
  for($i=1;$i<13;$i++) {
    if($month==$i)
      return true;
  }
  return false;
}

function umakeMonth($m) {
  return substr($m,0,2)."-".substr($m,2,4);
}

function makeMonth($month) {
  return $month . date('Y'); 
}

function ProcessTag($month,$tag,$clicks,$visitors,$shippedunits,$earnings,$p) {
  if(monthValid($month) and isset($tag) and isset($clicks) and isset($visitors) and isset($shippedunits) and isset($earnings) and isset($p)) {
    global $dbhost,$dbname,$dbuser,$dbpass,$lang;
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn)
      return $lang['CONNECT_ERROR'];
    if(!mysql_select_db($dbname,$conn)) {
      mysql_close();
      return $lang['DBSELECT_ERROR'];
    }
    $m=makeMonth($month);
    $id=uniqid();
    $e=$earnings*$p;
    $q="INSERT INTO click_data (id,associate_tag,clicks,visitors,shippedunits,earnings,month,bill) VALUES('$id','$tag','$clicks','$visitors','$shippedunits','$e','$m','false');";
    $r=mysql_query($q);
    return true;
  }
  return false;
}


/* function ProcessTag($month,$tag,$clicks,$visitors,$shippedunits,$earnings) { */
/*   if(monthValid($month) and isset($tag) and isset($clicks) and isset($visitors) and isset($shippedunits) and isset($earnings)) { */
/*     global $dbhost,$dbname,$dbuser,$dbpass,$lang; */
/*     $conn=mysql_connect($dbhost,$dbuser,$dbpass); */
/*     if(!$conn) */
/*       return $lang['CONNECT_ERROR']; */
/*     if(!mysql_select_db($dbname,$conn)) { */
/*       mysql_close(); */
/*       return $lang['DBSELECT_ERROR']; */
/*     } */
/*     $ad_id=NULL; */
/*     $website_id=NULL; */
/*     $user_id=NULL; */
/*     $query="SELECT * FROM ads WHERE associate_tag='".mysql_real_escape_string(trim($tag))."'"; */
/*     $res=mysql_query($query); */
/*     if($res!=false) { */
/*       if(mysql_num_rows($res)!==false) { */
/*         $mm=akeMonth($month); */
/*         while($row=mysql_fetch_array($res)) { */
/*           $id=uniqid(); */
/*           $ad_id=$row['id']; */
/*           $website_id=$row['website_id']; */
/*           $user_id=$row['user_id']; */
/*           $q="INSERT INTO click_data (id,ad_id,website_id,user_id,associate_tag,clicks,visitors,shippedunits,earnings,month) VALUES('$id','$ad_id','$website_id','$user_id','$tag','$clicks','$visitors','$shippedunits','$earnings','$m');"; */
/*           $r=mysql_query($q); */
/*         } */
/*       } */
/*     } */
/*     $ad_id=NULL; */
/*     $website_id=NULL; */
/*     $user_id=NULL; */
/*     $query="SELECT * FROM websites WHERE associate_tag='".mysql_real_escape_string(trim($tag))."'"; */
/*     $res=mysql_query($query); */
/*     if($res!=false) { */
/*       if(mysql_num_rows($res)!=false) { */
/*         $m=makeMonth($month); */
/*         while($row=mysql_fetch_array($res)) { */
/*           $id=uniqid(); */
/*           $website_id=$row['id']; */
/*           $user_id=$row['user_id']; */
/*           $q="INSERT INTO click_data (id,ad_id,website_id,user_id,associate_tag,clicks,visitors,shippedunits,earnings,month) VALUES('$id','$ad_id','$website_id','$user_id','$tag','$clicks','$visitors','$shippedunits','$earnings','$m');"; */
/*           $r=mysql_query($q); */
/*         } */
/*       } */
/*     } */
/*     $ad_id=NULL; */
/*     $website_id=NULL; */
/*     $user_id=NULL; */
/*     $query="SELECT * FROM users WHERE associate_tag='".mysql_real_escape_string(trim($tag))."'"; */
/*     $res=mysql_query($query); */
/*     if($res!=false) { */
/*       if(mysql_num_rows($res)!=false) { */
/*         $m=makeMonth($month); */
/*         while($row=mysql_fetch_array($res)) { */
/*           $id=uniqid(); */
/*           $user_id=$row['id']; */
/*           $q="INSERT INTO click_data (id,ad_id,website_id,user_id,associate_tag,clicks,visitors,shippedunits,earnings,month) VALUES('$id','$ad_id','$website_id','$user_id','$tag','$clicks','$visitors','$shippedunits','$earnings','$m');"; */
/*           $r=mysql_query($q); */
/*         } */
/*       } */
/*     } */
/*     return true; */
/*   } */
/*   return "false"; */
/* } */

function ProcessXml($xml,$month,$p) {
  global $dbhost,$dbname,$dbuser,$dbpass,$lang;
  $res="test";
  if(isset($xml) and isset($month) and monthValid($month) and isset($p)) {
    $parser=new SimpleXMLElement($xml);

    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn)
      return $lang['CONNECT_ERROR'];
    if(!mysql_select_db($dbname,$conn)) {
      mysql_close();
      return $lang['DBSELECT_ERROR'];
    }
    $query="DELETE FROM click_data WHERE month='".mysql_real_escape_string(makeMonth($month))."'";
    $res=mysql_query($query);
    if($res==false)
      return "delete error";
    
    foreach($parser as $ele) {
      $r=ProcessTag($month,$ele['Tag'],$ele['Clicks'],$ele['Visitors'],$ele['ShippedUnits'],$ele['TotalEarnings'],$p);
      if($r!==true)
        return $r;
    }
    return true;
  }
  return "param error";
}

?>
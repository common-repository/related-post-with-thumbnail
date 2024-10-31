<?
  include_once("class.ImageToolbox.php");
  $img=$_GET["img"];
  $imgw=intval($_GET["w"]);
  $imgh=intval($_GET["h"]);
  $imgt=intval($_GET["t"]);
  
  if ($imgw=="") $imgw=0;
  if ($imgh=="") $imgh=0;
  if ($img=="") die;

  $thumbnail=new Image_Toolbox(getenv("DOCUMENT_ROOT").$img);
  $thumbnail->setResizeMethod('resample');
  $thumbnail->newOutputSize($imgw,$imgh,$imgt,false,'#FFFFFF');
  $thumbnail->output('png');
?>

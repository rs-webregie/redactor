<?php

/**
 * redactor Addon
 *
 * @author andreaseberhard[at]gmail[dot]com Andreas Eberhard
 * @author <a href="http://www.redaxo.de">www.redaxo.de</a>
 *
 * @package redaxo4
 * @version svn:$Id$
 */


if (!isset($REX['FILEPERM'])) 
{
  $REX['FILEPERM'] = octdec(664); // oktaler wert
}
if (!isset($REX['DIRPERM'])) 
{
  $REX['DIRPERM'] = octdec(775); // oktaler wert
}


/**
 * für REDAXO 4.0.x
 */
if (!function_exists('rex_info'))
{
function rex_info($msg)
{
  //return '<p class="rex-warning" style="padding:7px; width:756px; background-color:#D2EFD9; color:#107C6C;">' . $msg . '</p>';
  return '<p class="rex-warning"><span>' . $msg . '</span></p> ';
}
} // End function_exists


/**
 * für REDAXO 4.0.x
 */
if (!function_exists('rex_get_file_contents'))
{
  function rex_get_file_contents($path)
  {
    return file_get_contents($path);
  }
} // end function_exists


/**
 * für REDAXO 4.0.x
 */
if (!function_exists('rex_put_file_contents'))
{
function rex_put_file_contents($path, $content)
{
global $REX;

  $writtenBytes = file_put_contents($path, $content);
  @chmod($path, $REX['FILEPERM']);

  return $writtenBytes;
}
} // end function_exists


/**
 * für REDAXO 4.0.x
 */
if (!function_exists('rex_replace_dynamic_contents'))
{
function rex_replace_dynamic_contents($path, $content)
{
  if($fcontent = rex_get_file_contents($path))
  {
    $content = "// --- DYN\n". trim($content) ."\n// --- /DYN";
    $fcontent = ereg_replace("(\/\/.---.DYN.*\/\/.---.\/DYN)", $content, $fcontent);
    return rex_put_file_contents($path, $fcontent);
  }
  return false;
}
} // End function_exists


/**
 * für REDAXO 4.1.x
 */
if ($REX['REDAXO'] and !function_exists('rex_copyDir'))
{
  function rex_copyDir($srcdir, $dstdir, $startdir = "")
  {
    global $REX;
    
    $debug = FALSE;
    $state = TRUE;
    
    if(!is_dir($dstdir))
    {
    $dir = '';
    foreach(explode(DIRECTORY_SEPARATOR, $dstdir) as $dirPart)
    {
      $dir .= $dirPart . DIRECTORY_SEPARATOR;
      if(strpos($startdir,$dir) !== 0 && !is_dir($dir))
      {
      if($debug)
        echo "Create dir '$dir'<br />\n";
        
      mkdir($dir);
      chmod($dir, $REX['DIRPERM']);
      }
    }
    }
    
    if($curdir = opendir($srcdir))
    {
    while($file = readdir($curdir))
    {
      if($file != '.' && $file != '..' && $file != '.svn')
      {
      $srcfile = $srcdir . DIRECTORY_SEPARATOR . $file;    
      $dstfile = $dstdir . DIRECTORY_SEPARATOR . $file;    
      if(is_file($srcfile))
      {
        $isNewer = TRUE;
        if(is_file($dstfile))
        {
        $isNewer = (filemtime($srcfile) - filemtime($dstfile)) > 0;
        }
        
        if($isNewer)
        {
        if($debug)
          echo "Copying '$srcfile' to '$dstfile'...";
        if(copy($srcfile, $dstfile))
        {
          touch($dstfile, filemtime($srcfile));
          chmod($dstfile, $REX['FILEPERM']);
          if($debug)
          echo "OK<br />\n";
        }
        else
        {
          if($debug)
           echo "Error: File '$srcfile' could not be copied!<br />\n";
          return FALSE;
        }
        }
      }
      else if(is_dir($srcfile))
      {
        $state = rex_copyDir($srcfile, $dstfile, $startdir) && $state;
      }
      }
    }
    closedir($curdir);
    }
    return $state;
  }
} // End function_exists


/**
 * String Highlight für ältere REDAXO-Versionen
 */ 
if (!function_exists('rex_highlight_string'))
{
function rex_highlight_string($string, $return = false)
{
  $s = '<p class="rex-code">'. highlight_string($string, true) .'</p>';
  if($return)
  {
    return $s;
  }
  echo $s; 
}
} // End function_exists


/**
 * Schreibberechtigung prüfen
 */
if (!function_exists('redactor_is_writable'))
{
function redactor_is_writable($path)
{
  if ($path{strlen($path)-1}=='/') // recursively return a temporary file path
    return redactor_is_writable($path.uniqid(mt_rand()).'.tmp');
  else if (is_dir($path))
    return redactor_is_writable($path.'/'.uniqid(mt_rand()).'.tmp');
  // check tmp file for read/write capabilities
  $rm = file_exists($path);
  $f = @fopen($path, 'a');
  if ($f===false)
    return false;
  fclose($f);
  if (!$rm)
    unlink($path);
  return true;
}
} // End function_exists


/**
 * prüfen exclude Page/Subpage
 */
if (!function_exists('redactor_exclude_page_subpage'))
{
function redactor_exclude_page_subpage()
{
global $REX;

  if ($REX['ADDON']['redactor']['excludecats'] <> '')
  {
    $exc = explode(',', $REX['ADDON']['redactor']['excludecats']);
    foreach ($exc as $key => $val)
    {
      $exc[$key] = trim($val);
    }
    if (in_array(rex_request('page', 'string', ''), $exc))
      return true;
  }

  if ($REX['ADDON']['redactor']['excludeids'] <> '')
  {
    $exc = explode(',', $REX['ADDON']['redactor']['excludeids']);
    foreach ($exc as $key => $val)
    {
      $exc[$key] = trim($val);
    }
    if (in_array(rex_request('subpage', 'string', ''), $exc))
      return true;

    // evtl. vorhandener String in Url auch übergehen
    foreach ($exc as $key => $val)
    {
      if (strstr($_SERVER['REQUEST_URI'], $val))
      {
        return true;
      }
    }
  }

  return false;
}
} // End function_exists


/**
 * prüfen exclude Kategorie/Artikel-Id
 */
if (!function_exists('redactor_exclude_cat_art'))
{
function redactor_exclude_cat_art()
{
global $REX;

  if ($REX['ADDON']['redactor']['excludecats'] <> '')
  {
    $artId = OOArticle::getArticleById($REX['ARTICLE_ID']);
    $exc = explode(',', $REX['ADDON']['redactor']['excludecats']);
    foreach ($exc as $key => $val)
    {
      $exc[$key] = trim($val);
    }
    if (in_array($artId->getValue("category_id"), $exc))
      return true;
  }

  if ($REX['ADDON']['redactor']['excludeids'] <> '')
  {
    $exc = explode(',', $REX['ADDON']['redactor']['excludeids']);
    foreach ($exc as $key => $val)
    {
      $exc[$key] = trim($val);
    }
    if (in_array($REX['ARTICLE_ID'], $exc))
      return true;
  }

  return false;
}
} // End function_exists


/**
 * redactor-Script im Head-Bereich einbinden
 */
if (!function_exists('redactor_output_filter'))
{
function redactor_output_filter($content)
{
  global $REX;

  // Wenn keine Textarea mit Klasse redactorEditor vorhanden ist dann nichts machen
  if (strpos($content['subject'], 'redactorEditor') === false) return $content['subject'];

  // Exclude für Backend und Frontend prüfen
  if ($REX['REDAXO'] and redactor_exclude_page_subpage()) return $content['subject'];
  if (!$REX['REDAXO'] and redactor_exclude_cat_art()) return $content['subject'];

  // redactor einbinden
  if ($REX['REDAXO'])
  {
    $rp = 'redaxo/index.php';
  }
  else
  {
    $rp = $REX['FRONTEND_FILE'];
  }

  $search = '</head>';
  $replace  = "\n\n" . '  <!-- Addon redactor -->';
  if ($REX['VERSION'] . $REX['SUBVERSION'] <= '40')
  {
    $replace .= "\n" .'<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>';
  }  
  $replace .= "\n" . '  <link rel="stylesheet" type="text/css" href="' . $REX['HTDOCS_PATH'] . $rp . '?redactorcss=true" media="screen, projection, print" />';
  $replace .= "\n" . '  <script src="' . $REX['HTDOCS_PATH'] . $rp . '?redactorinit=true&amp;clang=' . $REX['CUR_CLANG'] . '" type="text/javascript"></script>' . "\n";
  $replace .= "\n" . '</head>' . "\n";

  return str_replace($search, $replace, $content['subject']);
}
} // End function_exists


/**
 * redactor-Init-Script ausgeben
 */
if (!function_exists('redactor_generate_script'))
{
function redactor_generate_script()
{
global $REX;

  while (ob_get_level())
    ob_end_clean();

  if (function_exists('header_remove'))
  {  
    header_remove();
  }
  if (function_exists('ob_gzhandler'))
  {
    ob_start('ob_gzhandler');
  }  

  header("Content-type: application/javascript");
  header('Cache-Control: public');
  header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT'); // 7 days 

  if ($REX['REDAXO'])
  {
    $rp = 'redaxo/index.php';
  }
  else
  {
    $rp = $REX['FRONTEND_FILE'];
  }
  
  echo '/**
 * Addon redactor Version '.$REX['ADDON']['version']['redactor'].'
 */

$ = jQuery;


';	

  echo rex_get_file_contents($REX['HTDOCS_PATH'] . 'files/addons/redactor/redactor/rex.redactor.js');
  echo "\n\n\n";

  echo rex_get_file_contents($REX['HTDOCS_PATH'] . 'files/addons/redactor/redactor/redactor.min.js');
  echo "\n\n\n";
  
  $profileout = '';
  $profileout .= '// Init redactor-Profiles'."\n";
  $profileout .= '// ------------------------------------------------------------'."\n";
  $profileout .= 'jQuery(document).ready(function($) {'."\n";

  $table = $REX['TABLE_PREFIX'] . 'redactor_profiles';

  $query = 'SELECT id, name, description, configuration, lang FROM ' . $table . ' WHERE ptype = 0 ORDER BY name ASC ';
  $sql = new rex_sql;
  $sql->debugsql = 0;
  $sql->setQuery($query);

  $defaulttiny = '';
  $langinc = array();
  if ($sql->getRows() > 0)
  {
    for ($i = 0; $i < $sql->getRows(); $i ++)
    {
      $configout = trim($sql->getValue('configuration'));
		$configout = rtrim($configout, ',');
      $configout = redactor_replace_vars($configout);
      $configout = 'lang: \''.$sql->getValue('lang').'\', '."\n" . $configout;

		if (trim($sql->getValue('lang'))<>'' and !isset($langinc[$sql->getValue('lang')]))
		{
        $langjs = rex_get_file_contents($REX['HTDOCS_PATH'] . 'files/addons/redactor/redactor/lang/'.$sql->getValue('lang').'.js');
		  $langjs = str_replace('var RELANG = {};', '', $langjs);
		  echo $langjs . "\n\n\n";
		  $langinc[$sql->getValue('lang')] = '1';
		}

      if ($sql->getValue('id') === '2') // default for class="redactorEditor"
      {
        $defaulttiny = "\n\n\n// " . $sql->getValue('description');
        $defaulttiny .= "\n// ------------------------------------------------------------";
        $defaulttiny .= "\n" . 'jQuery(\'textarea.redactorEditor\').redactor({';
        $defaulttiny .= "\n" . $configout;
        $defaulttiny .= "\n});";
      }

      $profileout .= "\n\n\n// " . $sql->getValue('description');
      $profileout .= "\n// ------------------------------------------------------------";
      $profileout .= "\n" . 'jQuery(\'textarea.redactorEditor-'.$sql->getValue('name').'\').redactor({';
      $profileout .= "\n" . $configout;
      $profileout .= "\n});";
      $sql->next();
    }
  }
  else
  {
    $defaulttiny = 'alert("[Addon redactor] - Error! No default Profile found!")';
  }
  echo $profileout;
  echo $defaulttiny;
  echo '


  
}); // end document ready
';
  die;
}
} // End function_exists


/**
 * redactor Script für Mediapool ausgeben
 */
if (!function_exists('redactor_generate_mediascript'))
{
function redactor_generate_mediascript()
{
global $REX;

  while (ob_get_level())
    ob_end_clean();

  if (function_exists('header_remove'))
  {  
    header_remove();
  }
  header("Content-type: application/javascript");

  echo '/**
 * Addon redactor Version '.$REX['ADDON']['version']['redactor'].'
 */

// redactor Popup interface
// ------------------------------------------------------------
';
  echo rex_get_file_contents($REX['HTDOCS_PATH'] . '/files/addons/redactor/tiny_mce/tiny_mce_popup.js');
  echo "\n\n";
  $scriptout = rex_get_file_contents($REX['HTDOCS_PATH'] . '/files/addons/redactor/rex.mediapool.js');
  echo redactor_replace_vars($scriptout);
  echo "\n\n";

  die;
}
} // End function_exists


/**
 * redactor Script für Linkmap ausgeben
 */
if (!function_exists('redactor_generate_linkscript'))
{
function redactor_generate_linkscript()
{
global $REX;

  while (ob_get_level())
    ob_end_clean();

  if (function_exists('header_remove'))
  {  
    header_remove();
  }
  header("Content-type: application/javascript");

  echo '/**
 * Addon redactor Version '.$REX['ADDON']['version']['redactor'].'
 */

// redactor Popup interface
// ------------------------------------------------------------
';
  echo rex_get_file_contents($REX['HTDOCS_PATH'] . '/files/addons/redactor/tiny_mce/tiny_mce_popup.js');
  echo "\n\n";
  $scriptout = rex_get_file_contents($REX['HTDOCS_PATH'] . '/files/addons/redactor/rex.linkmap.js');
  echo redactor_replace_vars($scriptout);
  echo "\n\n";

  die;
}
} // End function_exists


/**
 * redactor CSS ausgeben
 */
if (!function_exists('redactor_generate_css'))
{
function redactor_generate_css()
{
global $REX;

  while (ob_get_level())
    ob_end_clean();

  if (function_exists('header_remove'))
  {  
    header_remove();
  }

  $css1 = rex_get_file_contents($REX['HTDOCS_PATH'] . '/files/addons/redactor/redactor/redactor.css');
  $css2 = rex_get_file_contents($REX['HTDOCS_PATH'] . '/files/addons/redactor/redactor/rex.redactor.css');
  
  $css = '';
  $table = $REX['TABLE_PREFIX'] . 'redactor_profiles';

  $query = 'SELECT configuration FROM ' . $table . ' WHERE id = 1 AND ptype = 1 ';
  $sql = new rex_sql;
  $sql->debugsql=0;
  $sql->setQuery($query);
  if ($sql->getRows() > 0)
  {
    $css = $sql->getValue('configuration');
  }
  
  header("Content-type: text/css");
  echo $css1 . "\n\n" . $css2 . "\n\n" . $css;
  die;
}
} // End function_exists

/**
 * Bild chunked senden, um diverse probleme zu umgehen
 */

if(!function_exists('tiny_readfile_chunked')) {
  function tiny_readfile_chunked ($filename) {
    $chunksize = 1*(1024*1024); // how many bytes per chunk
    $buffer = '';
    $handle = fopen($filename, 'rb');
    if ($handle === false) {
      return false;
    }
    while (!feof($handle)) {
      $buffer = fread($handle, $chunksize);
      print $buffer;
    }
    return fclose($handle);
  }
}

/**
 * Bild ausgeben
 */
if (!function_exists('redactor_generate_image'))
{
  function redactor_generate_image()
  {
    global $REX;

    $redactorimg = rex_request('redactorimg', 'string', '');
    $file = $REX['MEDIAFOLDER'] . '/' . $redactorimg;

    if (file_exists($file))
    {

      $last_modified_time = filemtime($file);
      $etag = md5_file($file);
      $expires = 60*60*24*14;

      $file_extension = strtolower(substr(strrchr($redactorimg, '.'), 1));
      switch ($file_extension)
      {
        case "gif": $ctype = "image/gif"; break;
        case "png": $ctype = "image/png"; break;
        case "jpeg": $ctype = "image/jpg"; break;
        case "jpg": $ctype = "image/jpg"; break;
      }

      while (ob_get_level())
        ob_end_clean();

      if (function_exists('header_remove'))
      {  
        header_remove();
      }

      header("Last-Modified: ".gmdate("D, d M Y H:i:s", $last_modified_time)." GMT");
      header("Etag: \"$etag\"");
      header("Pragma: public");
      header("Cache-Control: maxage=".$expires);
      header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

      if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time || @trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
          header("HTTP/1.1 304 Not Modified");
          exit;
      } else {
          header("Content-type: " . $ctype);
          header("Content-Length: " . filesize($file));
          tiny_readfile_chunked($file);
          exit;
      }
    }
  }
} // End function_exists


/**
 * Output-Filter für Medienpool und Linkmap
 */
if (!function_exists('redactor_opf_media_linkmap'))
{
function redactor_opf_media_linkmap($params)
{
global $REX;
// Hinzufügen und übernehmen, Close Popup

  $content = $params['subject'];
  $page = rex_request('page', 'string');
  if ($page === 'medienpool')
  {
    $page = 'mediapool';
  }
  $oif = rex_request('opener_input_field', 'string', '');

  $search = $replace = array();

  // Medienpool anpassen
  if ($page === 'mediapool')
  {
    $search[0] = '</head>';
    $replace[0]  = "\n\n" . '  <!-- Addon redactor -->';
    $replace[0] .= "\n" . '  <script src="' . $REX['HTDOCS_PATH'] . 'redaxo/index.php?redactormedia=true&amp;clang=' . $REX['CUR_CLANG'] . '&amp;opener_input_field=' . $oif . '" type="text/javascript"></script>' . "\n";
    $replace[0] .= "\n" . '</head>' . "\n";
    $search[1] = 'javascript:selectMedia(';
    $replace[1] = 'javascript:redactor_selectMedia(';
    $search[2] = '<input type="hidden" name="page" value="' . $page . '" />';
    $replace[2] = $search[2] . "\n\n" . '<input type="hidden" name="redactor" value="true" /> <!-- inserted by redactor -->' . "\n";
    $search[3] = 'page=' . $page;
    $replace[3] = 'page=' . $page . '&amp;redactor=true';  
    $search[4] = 'page=medienpool';
    $replace[4] = 'page=medienpool&amp;redactor=true'; 
    $search[5] = '<input type="hidden" name="page" value="medienpool" />';
    $replace[5] = $search[5] . "\n\n" . '<input type="hidden" name="redactor" value="true" /> <!-- inserted by redactor -->' . "\n";
  }

  // Linkmap anpassen
  if ($page === 'linkmap')
  {
    $search[0] = '</head>';
    $replace[0]  = "\n\n" . '  <!-- Addon redactor -->';
    $replace[0] .= "\n" . '  <script src="' . $REX['HTDOCS_PATH'] . 'redaxo/index.php?redactorlink=true&amp;clang=' . $REX['CUR_CLANG'] . '&amp;opener_input_field=' . $oif . '" type="text/javascript"></script>' . "\n";
    $replace[0] .= "\n" . '</head>' . "\n";
    $search[1] = 'javascript:insertLink(';
    $replace[1] = 'javascript:redactor_insertLink(';
    $search[2] = '<input type="hidden" name="page" value="' . $page . '" />';
    $replace[2] = $search[2] . "\n\n" . '<input type="hidden" name="redactor" value="true" /> <!-- inserted by redactor -->' . "\n";
    $search[3] = 'page=' . $page;
    $replace[3] = 'page=' . $page . '&amp;redactor=true';  
  }

  // Alles ersetzen
  return str_replace($search, $replace, $content);

}
} // End function_exists


/**
 * Extension-Point für Medienpool Button "Hinzufügen und übernehmen"
 */
if (!function_exists('redactor_media_added'))
{
function redactor_media_added($params)
{
global $REX;

  if (rex_request('saveandexit', 'string', '') <> '')
  {
    $scriptoutput = "\n\n" . '  <!-- Addon redactor -->';
    $scriptoutput .= "\n" . '  <script src="' . $REX['HTDOCS_PATH'] . 'redaxo/index.php?redactormedia=true&amp;clang=' . $REX['CUR_CLANG'] . '" type="text/javascript"></script>' . "\n";
    $scriptoutput .= "\n\n";

    $scriptoutput .= "\n" . '<script type="text/javascript">';
    $scriptoutput .= "\n" . '//<![CDATA[';
    $scriptoutput .= "\n" . '    redactor_selectMedia("'.$params['filename'].'", "'.$params['title'].'")';
    $scriptoutput .= "\n" . '//]]>';
    $scriptoutput .= "\n" . '</script>';
    echo $scriptoutput;
    die;
  }
  setcookie('redactor_mediapool', 'true');
}
} // End function_exists


/**
 * Variablen ersetzen
 */
if (!function_exists('redactor_replace_vars'))
{
function redactor_replace_vars($source)
{
global $REX;

  $clang = rex_request('clang', 'int', '0');
  $oif = rex_request('opener_input_field', 'string', '');

  $scriptout = str_replace('%HTDOCS_PATH%', $REX['HTDOCS_PATH'], $source);
  $scriptout = str_replace('%SERVER%', $REX['SERVER'], $scriptout);
  $scriptout = str_replace('%SERVERNAME%', $REX['SERVERNAME'], $scriptout);
  $scriptout = str_replace('%CLANG%', $REX['CUR_CLANG'], $scriptout);
  $scriptout = str_replace('%INCLUDE_PATH%', $REX['INCLUDE_PATH'], $scriptout);
  $scriptout = str_replace('%FRONTEND_PATH%', $REX['FRONTEND_PATH'], $scriptout);
  $scriptout = str_replace('%MEDIAFOLDER%', $REX['MEDIAFOLDER'], $scriptout);
  $scriptout = str_replace('%FRONTEND_FILE%', $REX['FRONTEND_FILE'], $scriptout);
  $scriptout = str_replace('%HTTP_HOST%', $_SERVER['HTTP_HOST'], $scriptout);
  $scriptout = str_replace('%OPENER_INPUT_FIELD%', $oif, $scriptout);
  if ($REX['VERSION'] . $REX['SUBVERSION'] < '42')
  {
    $scriptout = str_replace('%MEDIAPOOL%', 'medienpool', $scriptout);
  }
  else
  {
    $scriptout = str_replace('%MEDIAPOOL%', 'mediapool', $scriptout);
  }

  return $scriptout;
}
} // End function_exists

<?php
require_once('vendor/autoload.php');
require_once('localization.php');

// Locale things

$locale = new UILocale;
$encoding = $locale->trRaw('encoding', $_GET['lg']);

function tr($string) {
    global $encoding;
    global $locale;
    return mb_convert_encoding($locale->trRaw($string, $_GET['lg']), $encoding, 'UTF-8');
}

function localeEncode($string) {
    global $encoding;
    return mb_convert_encoding($string, $encoding, 'UTF-8');
}

header('Content-Type: text/html;charset='.$encoding);

$article_url = "";
$article_html = "";
$error_text = "";
$loc = "US";

if( isset( $_GET['loc'] ) ) {
    $loc = strtoupper($_GET["loc"]);
}

if( isset( $_GET['a'] ) ) {
    $article_url = $_GET["a"];
} else {
    echo "What do you think you're doing... >:(";
    exit();
}

if (substr( $article_url, 0, 4 ) != "http") {
    echo(tr("error_not_webpage"));
    die();
}

$host = parse_url($article_url, PHP_URL_HOST);

use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
use andreskrey\Readability\ParseException;

$configuration = new Configuration();
$configuration
    ->setArticleByLine(false)
    ->setFixRelativeURLs(true)
    ->setOriginalURL('http://' . $host);

$readability = new Readability($configuration);

if(!$article_html = file_get_contents($article_url)) {
    $error_text .=  tr("error_article_fail")." <br>";
}

try {
    $readability->parse($article_html);
    $readable_article = strip_tags($readability->getContent(), '<a><ol><ul><li><br><p><small><font><b><strong><i><em><blockquote><h1><h2><h3><h4><h5><h6>');
    $readable_article = str_replace( 'strong>', 'b>', $readable_article ); //change <strong> to <b>
    $readable_article = str_replace( 'em>', 'i>', $readable_article ); //change <em> to <i>
    
    $readable_article = clean_str($readable_article);
    $readable_article = str_replace( 'href="http', 'href="/read.php?lg=' . $_GET['lg'] . '&a=http', $readable_article ); //route links through proxy
    
} catch (ParseException $e) {
    $error_text .= 'Sorry! ' . $e->getMessage() . '<br>';
}

//replace chars that old machines probably can't handle
function clean_str($str) {
    $str = str_replace( "‘", "'", $str );    
    $str = str_replace( "’", "'", $str );  
    $str = str_replace( "“", '"', $str ); 
    $str = str_replace( "”", '"', $str );
    $str = str_replace( "–", '-', $str );

    return $str;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 2.0//EN">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 
 <html>
 <head>
     <title><?php echo $readability->getTitle();?></title>
 </head>
 <body>
    <p>
        <form action="/read.php" method="get">
        <a href="/?lg=<?= $_GET['lg'] ?>"><?= tr('back_to_frogfind'); ?> <b><font color="#008000">Frog</font><font color="000000">Find!</font></a></b> | <?= tr('browsing_url'); ?>: <input type="text" size="38" name="a" value="<?php echo $article_url ?>">
        <input type="hidden" name="lg" value="<?= $_GET['lg']; ?>">
        <input type="submit" value="<?= tr('go'); ?>">
        </form>
    </p>
    <hr>
    <h1><?php echo localeEncode(clean_str($readability->getTitle()));?></h1>
    <p> <?php
        $img_num = 0;
        $imgline_html = "View page images:";
        foreach ($readability->getImages() as $image_url):
            //we can only do png and jpg
            if (strpos($image_url, ".jpg") || strpos($image_url, ".jpeg") || strpos($image_url, ".png") === true) {
                $img_num++;
                $imgline_html .= " <a href='image.php?loc=" . $loc . "&i=" . $image_url . "'>[$img_num]</a> ";
            }
        endforeach;
        if($img_num>0) {
            echo  $imgline_html ;
        }
    ?></small></p>
    <?php if($error_text) { echo "<p><font color='red'>" . $error_text . "</font></p>"; } ?>
    <p><font size="4"><?php echo localeEncode($readable_article);?></font></p>
 </body>
 </html>

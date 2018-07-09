<?php
//abstract interface for chromma lazy load
class Chromma_Lazy_Load_Module
{

  public function __construct() {
  }

  public static function apply_aspect_ratio($img, $figure) {
    //gather options
    $load_effect = get_option( 'chromma_loadeffect' );
    $lowest_dimension_mod = ($load_effect !== "fadein") ? get_option('chromma-load-dimensions') : "";
    $aspect_ratio =  get_option('chromma-load-ar');
    //parse out the desired dimensions and apply the dimensions as an aspect ratio to the figure
    $aspect_ratio = str_replace('x', ',', $aspect_ratio);
    $aspect_ratio = str_replace('-', '', $aspect_ratio);
    $aspect_ratio_array = explode(',', $aspect_ratio);
    $width = $aspect_ratio_array[0];
    $height = $aspect_ratio_array[1];
    //list($width, $height) = getimagesize($src);
    $aspectRatio = ($height > 0 && $width > 0) ? ($height / $width) * 100 : 101;

    //if aspect ratio is larger than desired, we'll fallback to a figure/img relationship w/o a set aspec ratio
    if($aspectRatio > 80 ) {
       $img->setAttribute('style', 'position: relative');
    }
    $aspectThresholdfix = ($aspectRatio > 58 && !($figure->getAttribute('class') )) ? 'height: auto; padding: 0px; max-height: '.$height.'px; max-width: '.$width.'px;' : 'padding-bottom: '. $aspectRatio .'%;';
    $styles = $aspectThresholdfix;
    $figure->setAttribute('style', $styles);
  }

  public static function content_lazyload_filter( $content ) {
    //initialize a dom document for easier more accurate parsing
    $content = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");
    $dom = new DOMDocument();
    $dom->loadHTML($content);
    $dom->encoding = 'utf-8';
    $xpath = new DOMXpath($dom);
    //xpath query targets all images that are children of the div css class entry-content
    $unwrappedImgs = $xpath->query("//img");
    foreach ($unwrappedImgs as $img) {
      //create a figure & set class to entry-content_figure
      if (!($img->parentNode->nodeName == 'figure')) {
        $figure = $dom->createElement('figure');
        $figure->setAttribute('class','entry-content_figure');
        //replace $img with wrapper figure then appendChild the $img back into the figure
        $img->parentNode->replaceChild($figure, $img);
        $figure->appendChild($img);
      } else {
        $figure = $img->parentNode;
      }

      //check for exemptions
      $imgClasses = (string)$img->getAttribute('class');
      if (strpos($imgClasses,'size-full') == false) {
        self::apply_aspect_ratio($img, $figure);
      } else {
        list($width_full, $height_full) = getimagesize($img->getAttribute('src'));
        $aspectRatio_full = ( $height_full > 0 && $width_full > 0) ? ($height_full / $width_full) * 100 : auto;
        $figure->setAttribute('style', "padding-bottom: " . $aspectRatio_full . "%");
      }

      //set img src/datasrc for lazyload handling
      $imgSrc = $img->getAttribute('src');
      $imgSrcSet = $img->getAttribute('srcset');
      $img->removeAttribute('srcset');
      $img->setAttribute('data-src', $imgSrc);
      if (!empty($imgSrcSet)) {
        $img->setAttribute('data-srcset', $imgSrcSet);
      }
      //set img src to a blank transparent gif
      $img->setAttribute('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
      $imgClasses = $img->getAttribute('class') . ' lazyload-img llreplace';
      $img->setAttribute('class', $imgClasses);

      //save the node to the $dom object
      $dom->saveHTML($img);
      $dom->saveHTML($figure);
    }

    //save and return
    $content = preg_replace('/^<!DOCTYPE.+?>/','',str_replace(array('<html>', '</html>', '<body>', '</body>'),array('', '', '', ''),$dom->saveHTML()));
    return $content;
  }


} //end Chromma_Lazy_Load_Module

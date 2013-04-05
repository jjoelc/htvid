<?php
/**
 * DokuWiki Plugin htvid (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Joel Carnes <jjoelc@gmail.com>
 *
 * Based on the html5video plugin Jason van Gumster, which had
 * Parts borrowed from the videogg plugin written by Ludovic Kiefer,
 * which is based on Christophe Benz' Dailymotion plugin, which, in turn,
 * is based on Ikuo Obataya's Youtube plugin. Whew...
 *
 * Supports mp4 and ogv videos (with flash fallback)
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_htvid_video extends DokuWiki_Syntax_Plugin {

    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 159;
    }

    public function connectTo($mode) {
        // recognizes the {{htvid> tag. Does not do any checking for parameters
        $this->Lexer->addSpecialPattern('{{htvid>.*?}}',$mode,'plugin_htvid_video');
    }

    public function handle($match, $state, $pos, &$handler){
        $params = substr($match, strlen('{{htvid>'), - strlen('}}')); //Strip markup
        if(strpos($params, ' ') === 0) { // Space as first character after 'htvid>'
            if(substr_compare($params, ' ', -1, 1) === 0) { // Space at front and back = centered
                $params = trim($params);
                $params = 'center|' . $params;
            } 
            else { // Only space at front = right-aligned
                $params = trim($params);
                $params = 'right|' . $params;
            }
        }
        elseif(substr_compare($params, ' ', -1, 1) === 0) { // Space only as last character = left-aligned
            $params = trim($params);
            $params = 'left|' . $params;
        }
        else { // No space padding = inline
            $params = 'inline|' . $params;
        }
        return array(state, explode('|', $params));
    }

    public function render($mode, &$renderer, $data) {
        if($mode != 'xhtml') return false;

        list($state, $params) = $data;
        list($video_align, $video_url1, $video_url2, $video_size, $video_attr) = $params;

        if($video_align == "center") {
            $align = "margin: 0 auto;";
        }
        elseif($video_align == "left") {
            $align = "float: left;";
        }
        elseif($video_align == "right") {
            $align = "float: right;";
        }
        else { // Inline
            $align = "";
        }

		if(substr($video_url1, -3) != 'ogv' && substr($video_url1, -3) != 'mp4') {
          $renderer->doc .= "Error: The video must be in ogv, or mp4 format. Bad file is:<br />" . $video_url1;
            return false;
        }

		if(substr($video_url2, -3) != 'ogv' && substr($video_url2, -3) != 'mp4') {
          $renderer->doc .= "Error: The video must be in ogv, or mp4 format. Bad file is:<br />" . $video_url2;
            return false;
        }

//	jw player doesn't seem to like the 'fetch.php=' links that the ml() function returns
//   so disabling dokuwiki media style links for now...	
       if(!substr_count($video_url1, '/')) {
           $video_url1 = ml($video_url1,true,true);
       }
		
		if(!substr_count($video_url2, '/')) {
            $video_url2 = ml($video_url2,$abs=true);			
        }

        //set default video size if none given
        if(is_null($video_size) or !substr_count($video_size, 'x')) {
            $width  = 640;
            $height = 360;
        }
        else {
            $obj_dimensions = explode('x', $video_size);
            $width  = $obj_dimensions[0];
            $height = $obj_dimensions[1];
        }

       //see if any attributes were given, set them if they exist...
       if(is_null($video_attr)) {
            $attr = "";
        }
        else {
            $arr_attr = explode(',', $video_attr);
            if(count($arr_attr) == 1) {
                if($arr_attr[0] == "loop") {
                    $attr = 'loop="loop"';
                }
                elseif($arr_attr[0] == "autoplay") {
                    $attr = 'autoplay="autoplay"';
                }
            }
            elseif(count($arr_attr) == 2) {
                if($arr_attr[0] != $arr_attr[1]) {
                    $attr = 'loop="loop" autoplay="autoplay"';
                }
                else {
                    $renderer->doc .= "Error: Duplicate parameters.<br />";
                    return false;
                }
            }
            else {
                $renderer->doc .= "Error: Wrong number of parameters.<br />";
                return false;
            }
        }

//now finally the code to render... 
		$obj.= '<video width="' . $width . '" height="' . $height . '" controls="controls"' . $attr . '>';
		$obj.= '<source src="' . $video_url1 . '">' ;
		$obj.= '<source src="' . $video_url2 . '">' ;
		$obj.= '<object type="application/x-shockwave-flash" data="/lib/plugins/htvid/player/player.swf" width="' . $width . '" height="' . $height . '">';
		$obj.= '<!--[if IE]><param name="movie" value="/lib/plugins/flashplayer/player/player.swf" /><![endif] -->';
		$obj.= '"It appears you do not have Flash installed or your browser does not support it. You may also <a href="' . $video_url1 . '">download this video</a> to watch it offline"';
		$obj.= '<param name="allowfullscreen" value="true">';
		$obj.= '<param name="flashvars" value="file=' . $video_url1 . '">';
		$obj.= '</object>';
		$obj.= '</video>';
        if($align != "") {
            $obj = '<div style="width: ' . $width . 'px; ' . $align . '">' . $obj . '</div>';
        }

        $renderer->doc .= $obj;
        return true;
    }
    private function _getAlts($filename) {
        return false;
    }
}
<?php
/**
 * DokuWiki Plugin cnmap (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Lshan <ldg@szzxue.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

class syntax_plugin_cnmap extends DokuWiki_Syntax_Plugin
{
    private $css_len_pattern ="/(^(auto|0)$|^[+-]?[0-9]+\.?([0-9]+)?)(px|em|ex|%|in|cm|mm|pt|pc)$/";
    private $providers = array('amap','bmap') ;

    private $amap_script_src = '<script src="https://webapi.amap.com/maps?';
    private $bmap_script_src = '<script type="text/javascript" src="http://api.map.baidu.com/api?';

    /**
     * @return string Syntax mode type
     */
    public function getType()
    {
        return 'substition';
    }

    /**
     * @return string Paragraph type
     */
    public function getPType()
    {
        return 'block';
    }

    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort()
    {
        return 70;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('{{cnmap>.*?}}',$mode,'plugin_cnmap');
    }

//    public function postConnect()
//    {
//        $this->Lexer->addExitPattern('</FIXME>', 'plugin_cnmap');
//    }

    /**
     * Handle matches of the cnmap syntax
     *
     * @param string       $match   The match of the syntax
     * @param int          $state   The state of the handler
     * @param int          $pos     The position in the document
     * @param Doku_Handler $handler The handler
     *
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $data = $this->parseMatch($match);

        return $data;
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string        $mode     Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer $renderer The renderer
     * @param array         $data     The data from the handler() function
     *
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode !== 'xhtml') {
            return false;
        }

        $pos = false;
        switch($data['provider'])
        {
            case "amap":
                $pos = strpos($renderer->doc, $this->amap_script_src);
                break;
            case "bmap":
                $pos = strpos($renderer->doc, $this->bmap_script_src);
                break;
            default:
                break;
        }
        if ($pos !== false)
        {
            $data[0]= "<!-- ";
            $data[2]= " -->";
        }

        $html_tpl = @file_get_contents(__DIR__."/tpl/".$data['provider'].".tpl.html");
        $html = @vsprintf($html_tpl,  $data);
        $renderer->doc .= $html ;

        return true;
    }

    /**
     * Parse and validate matches of the cnmap syntax
     *
     * @param string       $match   The match of the syntax
     * Example : 
     *   {{cnmap>?lng=116.397428&lat=39.90923}}
     *   {{cnmap>bmap?lng=116.397428&lat=39.90923&width=100%&height=500px&zoom=17&title=title of marker&mark=yes&sat=yes}}
     *
     * @return array Data for the renderer
     */
    public function parseMatch($match) {
        $match = substr($match, strlen('{{cnmap>'), -strlen('}}'));
        list($provider, $query) = explode('?', $match, 2);
        
        $args = array();
        parse_str($query, $args);

        $args['provider'] = in_array($provider, $this->providers)? $provider : $this->getConf('provider');

        $args['zoom'] = intval($args['zoom']);
        if($args['zoom'] < 3 || $args['zoom'] > 19)
            $args['zoom'] = $this->getConf('zoom');

        if(preg_match($this->css_len_pattern, $args['width']) != 1 )
            $args['width'] = $this->getConf('width');

        if(preg_match($this->css_len_pattern, $args['height']) != 1 )
            $args['height'] = $this->getConf('height');

        if(!isset($args['title']))
            $args['title']='';

        if(!isset($args['mark']))
            $args['mark']= $this->getConf('mark');
         else
        {
             $args['mark'] = strtolower($args['mark']);
             $args['mark'] = (($args['mark'] == 'y') || ($args['mark'] == 'yes')|| ($args['mark'] == 'on'));
        }

        if(!isset($args['sat']))
            $args['sat']= $this->getConf('sat');
         else
        {
             $args['sat'] = strtolower($args['sat']);
             $args['sat'] = (($args['sat'] == 'y') || ($args['sat'] == 'yes')|| ($args['sat'] == 'on'));
        }

        switch($args['provider'])
        {
            case "amap":
                return $this->parseMatchAmap($args);
            case "bmap":
                return $this->parseMatchBmap($args);
            default:
                return $this->parseMatchAmap($args);
        }
    }

    /**
     * Parse and validate args of the amap syntax
     *
     * @param array       $args   The match of the syntax
     *   Ref : https://lbs.amap.com/api/javascript-api/summary
     *
     * @return array Data for the renderer
     */
    public function parseMatchAmap($args) {
        $id = rand(1000,2000);
        $container_id ="amap_container_".$id;

        $args['title']=addslashes($args['title']);
        $args['title']=str_replace("<","\\<", $args['title']);
        $args['title']=str_replace(">","\\>", $args['title']);

        $data = array();
        array_push($data,  "");
        array_push($data,  $this->getConf('amap_api_key'));
        array_push($data,  "");

        array_push($data,  $container_id);
        array_push($data,  $args['width']);
        array_push($data,  $args['height']);
        array_push($data,  $container_id);
        array_push($data,  $id);
        array_push($data,  $args['lng']);
        array_push($data,  $args['lat']);
        array_push($data,  $container_id);
        array_push($data,  $args['zoom']);
        array_push($data,  $args['mark']?"true":"false");
        array_push($data,  $args['title']);
        array_push($data,  $args['sat']?"true":"false");
        array_push($data,  $id);

        $data['provider']= $args['provider'];

        return $data;
    }

    /**
     * Parse and validate args of the bmap syntax
     *
     * @param array       $args   The match of the syntax
     *   Ref : http://lbsyun.baidu.com/index.php?title=jspopular
     *
     * @return array Data for the renderer
     */
    public function parseMatchBmap($args) {
        $id = rand(2000,3000);
        $container_id ="bmap_container_".$id;

        $args['title']=addslashes($args['title']);
        $args['title']=str_replace("<","\\<", $args['title']);
        $args['title']=str_replace(">","\\>", $args['title']);

        $data = array();
        array_push($data,  "");
        array_push($data,  $this->getConf('bmap_api_key'));
        array_push($data,  "");

        array_push($data,  $container_id);
        array_push($data,  $args['width']);
        array_push($data,  $args['height']);
        array_push($data,  $container_id);
        array_push($data,  $id);
        array_push($data,  $args['lng']);
        array_push($data,  $args['lat']);
        array_push($data,  $container_id);
        array_push($data,  $args['zoom']);
        array_push($data,  $args['mark']?"true":"false");
        array_push($data,  $args['title']);
        array_push($data,  $args['sat']?"true":"false");
        array_push($data,  $id);

        $data['provider']= $args['provider'];

        return $data;
    }
}


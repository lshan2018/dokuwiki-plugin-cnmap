<?php
/**
 * Options for the cnmap plugin
 *
 * @author Lshan <ldg@szzxue.com>
 */


//$meta['fixme'] = array('string');

$meta['provider']  = array('multichoice','_choices' => array('amap','bmap'));
$meta['amap_api_key'] = array('string', '_pattern'=>'/^[a-z0-9]*$/i');
$meta['bmap_api_key'] = array('string', '_pattern'=>'/^[a-z0-9]*$/i');
$meta['zoom'] = array('numeric', '_min' => 3, '_max' => 19);
$meta['mark'] = array('onoff');
$meta['sat'] = array('onoff');


<?php
if (function_exists('xdebug_disable')) { xdebug_disable(); }
ini_set('xdebug.overload_var_dump', false);

if (!function_exists('dumpIntoStr')) {
    /**
     * @param ...$vars
     * @return string
     */
    function dumpIntoStr(...$vars)
    {
        ob_start();
        var_dump(...$vars);
        $out = ob_get_contents();
        ob_end_clean();

        if (PHP_SAPI !== 'cli') {
            $ret = "<style>pre.dump-dd{width:fit-content;background-color:#333333;border:1px dashed #cccccc;color:#cccccc;padding:5px}span.dump-collapsed span{display:none}span.js-dump-collapse{display:unset !important;}span.js-dump-collapse.dump-collapsed:before{position:relative;content:'+';font-weight:bold;color:#6caa36;cursor:pointer}span.js-dump-collapse.dump-un-collapsed:before{position:relative;content:'-';font-weight:bold;color:#d02a2c;cursor:pointer}span.dump-expand-all:before{position:relative;content:'+>>';font-weight:bold;color:#6caa36;cursor:pointer}span.dump-collapse-all:before{position:relative;content:'<<-';font-weight:bold;color:#d02a2c;cursor:pointer}</style>";
            $ret .= '<pre class="dump-dd">';

            $out1 = htmlentities($out);
            $i = 0;
            $tmp_delimiter = "[^!!@$#$#$%%START_POS%^!!@$#$#$%%]";
            while (($first = strpos($out1, '{')) !== false) {
                $uniq = mt_rand();
                $id = "collapsed-{$i}-" . $uniq; //md5(microtime() . );
                $id_p_m = "pm-{$i}-" . $uniq;
                $id_expand = "expand-{$i}-" . $uniq;
                $out1 =
                    mb_substr($out1, 0, $first) .
                    '<span id="' . $id_p_m . '" class="js-dump-collapse ' . ($i > 0 ? 'dump-collapsed' : 'dump-un-collapsed') . '" data-id-expand="' . $id_expand . '" data-id="' . $id . '">&nbsp;</span>/ ' .
                    '<span id="' . $id_expand . '" class="js-expand-collapse ' . ($i > 0 ? 'dump-expand-all' : 'dump-collapse-all') . '" data-id-pm="' . $id_p_m . '" data-id="' . $id . '" title="expand/collapse all">&nbsp;</span>' .
                    '<span class="js-container-collapse ' . ($i > 0 ? 'dump-collapsed' : 'dump-un-collapsed') . '" id="' . $id . '">' . $tmp_delimiter . '<span>' . mb_substr($out1, $first + 1);
                $i++;
            }
            $out1 = str_replace('}', '</span>}</span>', $out1);
            $out1 = str_replace($tmp_delimiter, '{', $out1);

            $ret .= trim($out1);
            $ret .= '</pre>';
            $ret .= "<script>(function(){let el=document.querySelectorAll('.js-dump-collapse');for(let i=0;i<el.length;i++){el[i].onclick=function(e){this.classList.toggle('dump-collapsed');this.classList.toggle('dump-un-collapsed');let el2=document.querySelector('#'+this.getAttribute('data-id'));el2.classList.toggle('dump-collapsed');el2.classList.toggle('dump-un-collapsed');e.preventDefault();e.stopImmediatePropagation();};}let el_expand=document.querySelectorAll('.js-expand-collapse');for(let i=0;i<el_expand.length;i++){el_expand[i].onclick=function(e){let act=this.classList.contains('dump-expand-all')?'expand':'collapse';let el_inner_main=document.querySelector('#'+el_expand[i].getAttribute('data-id'));if(act==='expand'){document.querySelector('#'+this.getAttribute('data-id-pm')).classList.remove('dump-collapsed');document.querySelector('#'+this.getAttribute('data-id-pm')).classList.add('dump-un-collapsed');el_inner_main.classList.remove('dump-collapsed');el_inner_main.classList.add('dump-un-collapsed');this.classList.remove('dump-expand-all');this.classList.add('dump-collapse-all');}else{document.querySelector('#'+this.getAttribute('data-id-pm')).classList.add('dump-collapsed');document.querySelector('#'+this.getAttribute('data-id-pm')).classList.remove('dump-un-collapsed');el_inner_main.classList.add('dump-collapsed');el_inner_main.classList.remove('dump-un-collapsed');this.classList.remove('dump-collapse-all');this.classList.add('dump-expand-all');}el_inner_main.querySelectorAll('.js-dump-collapse').forEach(function(el){el.classList.remove(act==='expand'?'dump-collapsed':'dump-un-collapsed');el.classList.add(act==='expand'?'dump-un-collapsed':'dump-collapsed');});el_inner_main.querySelectorAll('.js-expand-collapse').forEach(function(el){el.classList.remove(act==='expand'?'dump-expand-all':'dump-collapse-all');el.classList.add(act==='expand'?'dump-collapse-all':'dump-expand-all');});el_inner_main.querySelectorAll('.js-container-collapse').forEach(function(el){el.classList.remove(act==='expand'?'dump-collapsed':'dump-un-collapsed');el.classList.add(act==='expand'?'dump-un-collapsed':'dump-collapsed');});e.preventDefault();e.stopImmediatePropagation();};}})();</script>";

            return $ret;
        } else {
            return $out;
        }
    }
}

if (!function_exists('dump')) {
    /**
     * @param ...$vars
     * @return void
     */
    function dump(...$vars)
    {
        foreach ($vars as $var) {
            echo dumpIntoStr($var);
        }
    }
}

if (!function_exists('dd')) {
    /**
     * @param ...$vars
     * @return void
     */
    function dd(...$vars)
    {
        dump(...$vars);
        die();
    }
}

//if (!function_exists('ll')) {
//    $GLOBALS['ll_calls'] = 0;
//    /**
//     * @param ...$vars
//     * @return void
//     */
//    function ll(...$vars)
//    {
//        $GLOBALS['ll_calls']++;
//        ob_start();
//        var_dump(...$vars);
//        $out = ob_get_contents();
//        $out .= "\n\n ================= {$GLOBALS['ll_calls']}\n";
//        ob_end_clean();
//        $f = fopen(__DIR__ . '/../../../logs/my_ll.log', 'a');
//        fwrite($f, $out);
//        fflush($f);
//        fclose($f);
//    }
//}

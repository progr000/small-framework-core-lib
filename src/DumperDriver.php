<?php
if (function_exists('xdebug_disable')) xdebug_disable();
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
            $ret = "<style>pre.dump-dd{width:fit-content;background-color:#333333;border:1px dashed #cccccc;color:#cccccc;padding:5px}span.dump-collapsed span{display:none}span.js-dump-collapse{display:unset !important;}span.js-dump-collapse.dump-collapsed:before{position:relative;content:'+';font-weight:bold;color:#6caa36;cursor:pointer}span.js-dump-collapse.dump-un-collapsed:before{position:relative;content:'-';font-weight:bold;color:#d02a2c;cursor:pointer}</style>";
            $ret .= '<pre class="dump-dd">';

            $out1 = htmlentities($out);
            $i = 0;
            $tmp_delimiter = "[^!!@$#$#$%%START_POS%^!!@$#$#$%%]";
            while (($first = strpos($out1, '{')) !== false) {
                $id = "collapsed-{$i}-" . mt_rand(); //md5(microtime() . );
                $out1 = mb_substr($out1, 0, $first) . '<span class="js-dump-collapse '.($i > 0 ? 'dump-collapsed' : 'dump-un-collapsed').'" data-id="' . $id . '">&nbsp;</span><span class="' . ($i > 0 ? 'dump-collapsed' : 'dump-un-collapsed') . '" id="' . $id . '">' . $tmp_delimiter . '<span>' . mb_substr($out1, $first + 1);
                $i++;
            }
            $out1 = str_replace('}', '</span>}</span>', $out1);
            $out1 = str_replace($tmp_delimiter, '{', $out1);

            $ret .= trim($out1);
            $ret .= '</pre>';
            $ret .= "<script>(function(){let el=document.querySelectorAll('.js-dump-collapse');for(let i=0;i<el.length;i++){el[i].onclick=function(e){this.classList.toggle('dump-collapsed');this.classList.toggle('dump-un-collapsed');let el2=document.querySelector('#'+this.getAttribute('data-id'));el2.classList.toggle('dump-collapsed');el2.classList.toggle('dump-un-collapsed');e.preventDefault();e.stopImmediatePropagation();};}})();</script>";

            return $ret;
        } else {
            return $out;
        }
    }
}

if (!function_exists('dd')) {
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

if (!function_exists('ll')) {
    $GLOBALS['ll_calls'] = 0;
    /**
     * @param ...$vars
     * @return void
     */
    function ll(...$vars)
    {
        $GLOBALS['ll_calls']++;
        ob_start();
        var_dump(...$vars);
        $out = ob_get_contents();
        $out .= "\n\n ================= {$GLOBALS['ll_calls']}\n";
        ob_end_clean();
        $f = fopen(__DIR__ . '/../../../logs/my_ll.log', 'a');
        fwrite($f, $out);
        fflush($f);
        fclose($f);
    }
}
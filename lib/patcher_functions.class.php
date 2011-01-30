<?php

/**
 * Berekent het root-pad van het project
 *
 */
function findRootPath($patcher_path, $filepath)
{
	return preg_replace('#/+$#', '', str_replace($patcher_path, '', $filepath));
}

/**
 * Parses commandline switches, flags and arguments
 *
 * @author patrick at pwfisher dot com (http://php.net/manual/en/features.commandline.php)
 *
 * @param array $argv
 * @return array
 */
function parseArgs($argv){
    array_shift($argv);
    $out = array();
    foreach ($argv as $arg){
        if (substr($arg,0,2) == '--'){
            $eqPos = strpos($arg,'=');
            if ($eqPos === false){
                $key = substr($arg,2);
                $out[$key] = isset($out[$key]) ? $out[$key] : true;
            } else {
                $key = substr($arg,2,$eqPos-2);
                $out[$key] = substr($arg,$eqPos+1);
            }
        } else if (substr($arg,0,1) == '-'){
            if (substr($arg,2,1) == '='){
                $key = substr($arg,1,1);
                $out[$key] = substr($arg,3);
            } else {
                $chars = str_split(substr($arg,1));
                foreach ($chars as $char){
                    $key = $char;
                    $out[$key] = isset($out[$key]) ? $out[$key] : true;
                }
            }
        } else {
            $out[] = $arg;
        }
    }
    return $out;
}

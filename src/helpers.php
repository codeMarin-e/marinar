<?php

    use Illuminate\Support\Arr;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;


    if(!function_exists('marinar_assoc_arr_merge')) {
        function marinar_assoc_arr_merge() {
            $arrays = func_get_args();
            if(empty($arrays))
                return [];
            $return = $arrays[0];
            foreach($arrays as $arrIndex => $array) {
                if(!$arrIndex) continue;
                foreach($array as $key => $value) {
                    if(!isset($return[$key]) || (!is_array($value) || !is_array($return[$key]))) {
                        $return[$key] = $value;
                        continue;
                    }
                    $return[$key] = marinar_assoc_arr_merge($return[$key], $value);
                }
            }
            return $return;
        }
    }

    if(!function_exists('marinarCleanRouteQry')) {
        function marinarCleanRouteQry($qryArr) {
            $return = [];
            foreach($qryArr as $key => $value) {
                if(is_array($value)) {
                    $subReturn = marinarCleanRouteQry($value);
                    if(!empty($subReturn)) $return[$key] = $subReturn;
                    continue;
                }
                if($value === null) {
                    continue;
                }
                $return[$key] = $value;
            }
            return $return;
        }
    }


    if(!function_exists('marinarFullUrlWithQuery')) {
        function marinarFullUrlWithQuery($qryArr) {
            $qryArr = marinar_assoc_arr_merge( request()->query(), $qryArr);
            $qryArr = marinarCleanRouteQry($qryArr);

            $request = request();
            $question = $request->getBaseUrl().$request->getPathInfo() === '/' ? '/?' : '?';
            return empty($qryArr)?
                $request->url() :
                $request->url().$question.Arr::query($qryArr);

        }
    }

    if(!function_exists('now_route')) {
        function now_route($params = null, $queries = null) {
            $params = $params? $params : Route::current()->parameters();
            $queries = $queries? $queries : request()->query();
            return route( Route::currentRouteName(), array_merge(
                $params,
                $queries
            )) ;
        }
    }

    if(!function_exists('transOrOther')) {
        function transOrOther($nowNamePrefix, $otherNamePrefix, $forTrans) {
            $return = [];
            foreach($forTrans as $transKey) {
                $return[$transKey] = trans("{$nowNamePrefix}.{$transKey}");
                $return[$transKey] = $return[$transKey] !== "{$nowNamePrefix}.{$transKey}"? $return[$transKey] : trans("{$otherNamePrefix}.{$transKey}");
            }
            return $return;
        }
    }

    if(!function_exists('checkCollidePeriods')) {
        function checkCollidePeriods($start1Dt, $end1Dt, $start2Dt, $end2Dt) {
            if (!$start1Dt && !$end1Dt) return true;
            if (!$start1Dt) {
                if ($end2Dt) return $end1Dt >= $end2Dt;
                if ($start2Dt) return $start2Dt < $end1Dt;
                return true;
            }
            if (!$end1Dt) {
                if ($start2Dt) return $start1Dt <= $start2Dt;
                if ($end2Dt) return $start1Dt < $end2Dt;
                return true;
            }
            if (!$start2Dt && !$end2Dt) return true;
            if (!$start2Dt) return $start1Dt < $end2Dt;
            if (!$end2Dt) return $start1Dt <= $start2Dt;
            return $start1Dt <= $start2Dt;
        }
    }

    if(!function_exists('returnArrayFileContentValues')) {
        function returnArrayFileContentValues($key, $value, $level = 1) {
            if(is_array($value)) {
                $buff = "[\n";
                foreach($value as $key2 => $value2) {
                    $buff .= returnArrayFileContentValues($key2, $value2, $level+1);
                }
                $value = $buff.str_repeat("\t", $level)."]";
            } elseif(is_numeric($value)) {

            } elseif(is_string($value)) {
                $value = strpos($key, "'") === false? "'{$value}'" : '"'.$value.'"';
            } else {
                throw \Exception("{$key} wrong at returnArrayFileContentValues");
            }
            if(strpos($key, "'") === false)
                return str_repeat("\t", $level)."'{$key}' => {$value},\n";
            return str_repeat("\t", $level).'"'.$key.'"'." => {$value},\n";
        }
    }

    if(!function_exists('returnArrayFileContent')) {
        function returnArrayFileContent($array) {
            $return = "<?php\n";
            $return .= "return [\n";
            foreach($array as $key => $value) {
                $return .= returnArrayFileContentValues($key, $value);
            }
            $return .= "];";
            return $return;
        }
    }

    if(!function_exists('dirInArray')) {
        function dirInArray($dirPath, $dirs) {
            foreach($dirs as $dir) {
                if(Str::startsWith($dirPath, $dir))
                    return true;
            }
            return false;
        }
    }

    if(!function_exists('checkConfigFileForUpdate')) {
        function checkConfigFileForUpdate($configs1, $configs2) {
            if(!is_array($configs1) || !is_array($configs2)) return true;
            foreach($configs2 as $key => $value) {
                if(!isset($configs1[$key])) return true;
                if(is_array($value)) {
                    if(checkConfigFileForUpdate($configs1[$key], $value))
                        return true;
                    continue;
                }
            }
            return false;
        }
    }






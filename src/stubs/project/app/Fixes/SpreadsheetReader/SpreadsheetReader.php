<?php

    namespace App\Fixes\SpreadsheetReader;
    use PHPExcelReader\OLERead;
    use PHPExcelReader\SpreadsheetReader as XLSReader;

    class SpreadsheetReader extends XLSReader {

        function __construct($file='',$store_extended_info=true,$outputEncoding='', $encoder = 'iconv') {
            $newOLE = new OLERead();
            $this->_ole =& $newOLE;
            $this->setUTFEncoder($encoder);
            if ($outputEncoding != '') {
                $this->setOutputEncoding($outputEncoding);
            }
            for ($i=1; $i<245; $i++) {
                $name = strtolower(( (($i-1)/26>=1)?chr(($i-1)/26+64):'') . chr(($i-1)%26+65));
                $this->colnames[$name] = $i;
                $this->colindexes[$i] = $name;
            }
            $this->store_extended_info = $store_extended_info;
            if ($file!="") {
                $this->read($file);
            }
        }
    }

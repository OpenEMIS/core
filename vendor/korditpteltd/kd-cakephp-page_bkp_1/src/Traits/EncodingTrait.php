<?php
namespace Page\Traits;

trait EncodingTrait
{
    public function encode($value)
    {
        $value = json_encode($value);
        return $this->strToHex($value);
    }

    public function decode($value)
    {
        return json_decode($this->hexToStr($value), true);
    }

    public function strToHex($str)
    {
        if (is_array($str)) {
            $str = json_encode($str);
        }
        $hex = '';
        for ($i=0; $i < strlen($str); $i++) {
            $dec = dechex(ord($str[$i]));
            $hex .= substr('000' . $dec, -4);
        }
        return $hex;
    }

    public function hexToStr($hex)
    {
        $string = '';
        for ($i=0; $i < strlen($hex)-1; $i+=2) {
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return mb_convert_encoding($string, 'UTF-8', 'Unicode');
    }
}

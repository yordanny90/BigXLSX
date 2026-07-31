<?php

namespace BigXLSX;

class SharedStrings{
	private $cache=[];
    /**
     * @var \Iterator|null
     */
    private $iterator;

	private function __construct(){
    }

    public static function empty(){
        return new static();
    }

    public static function fromXML(\BigXML\File $xml, $useSQLite=false){
        $new=new static();
        if($useSQLite && SQLiteArray::isUsable()){
            $new->cache=new SQLiteArray();
        }
        else{
            $new->cache=[];
        }
        $reader=$xml->getReader('sst/si');
        if($reader){
            $new->iterator=$reader->getIterator();
            $new->iterator->rewind();
        }
        return $new;
    }

    public function get(int $index){
        if(!isset($this->cache[$index]) && $this->iterator){
            while($this->iterator->valid()){
                $k=$this->iterator->key();
                $curr=$this->iterator->current();
                $this->cache[$k]=self::normalizeString($curr->readString());
                $this->iterator->next();
                if(isset($this->cache[$index])) break;
            }
            if(!$this->iterator->valid()) $this->iterator=null;
        }
		return $this->cache[$index]??null;
	}

	public static function normalizeString(string $str){
		if(stripos($str, '_x')===false) return $str;
		// Los escapes _xHHHH_ son unidades de código UTF-16, no bytes ISO-8859-1.
		// Las secuencias consecutivas se procesan juntas para resolver los pares suplentes.
		return preg_replace_callback('/(?:_x[\dA-F]{4}_)+/i', function($m){
			preg_match_all('/_x([\dA-F]{4})_/i', $m[0], $units);
			return self::utf16ToUTF8($units[1]);
		}, $str);
	}

	/**
	 * @param string[] $units Unidades de código UTF-16 en hexadecimal
	 * @return string
	 */
	private static function utf16ToUTF8(array $units){
		$out='';
		$total=count($units);
		for($i=0; $i<$total; ++$i){
			$cp=hexdec($units[$i]);
			// Par suplente: alto (D800-DBFF) seguido de bajo (DC00-DFFF)
			if($cp>=0xD800 && $cp<=0xDBFF && ($i+1)<$total){
				$low=hexdec($units[$i+1]);
				if($low>=0xDC00 && $low<=0xDFFF){
					$cp=0x10000+(($cp-0xD800)<<10)+($low-0xDC00);
					++$i;
				}
			}
			$out.=self::codepointToUTF8($cp);
		}
		return $out;
	}

	/**
	 * @param int $cp Punto de código Unicode
	 * @return string
	 */
	private static function codepointToUTF8($cp){
		if($cp<0x80) return chr($cp);
		if($cp<0x800) return chr(0xC0|($cp>>6)).chr(0x80|($cp&0x3F));
		if($cp<0x10000) return chr(0xE0|($cp>>12)).chr(0x80|(($cp>>6)&0x3F)).chr(0x80|($cp&0x3F));
		return chr(0xF0|($cp>>18)).chr(0x80|(($cp>>12)&0x3F)).chr(0x80|(($cp>>6)&0x3F)).chr(0x80|($cp&0x3F));
	}
}
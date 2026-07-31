<?php

namespace BigXLSX;

class StylesNumeric{
	const CALENDAR_DATE=[
		1900=>'1899-12-30', // Equivalente a 1900-00-30
		1904=>'1904-01-01',
	];
	/**
	 * Identificadores de formato integrados en el estándar (ECMA-376 §18.8.30) que representan
	 * fecha y/u hora. Estos no aparecen en <numFmts>, por lo que solo pueden reconocerse por su ID.
	 */
	const BUILTIN_DATE=[
		14, 15, 16, 17, 18, 19, 20, 21, 22,
		27, 28, 29, 30, 31, 32, 33, 34, 35, 36,
		45, 46, 47,
		50, 51, 52, 53, 54, 55, 56, 57, 58,
	];
	private $cache=[];
    /**
     * @var array Mapa numFmtId=>bool de los formatos declarados en <numFmts>
     */
    private $numFmtDate=[];
    /**
     * @var \Iterator|null
     */
    private $xf;
	private $calendar=1900;

	private function __construct(){ }

    public static function empty(){
        return new static();
    }

    public static function fromXML(\BigXML\File $xml, $sqlite=false){
        $new=new static();
        if($sqlite && SQLiteArray::isUsable()){
            $new->cache=new SQLiteArray();
        }
        else{
            $new->cache=[];
        }
        // <numFmts> es pequeño y cellXfs lo consulta en orden arbitrario, por lo que se indexa
        // completo. Recorrerlo de forma perezosa hacía que un ID ausente agotara el iterador
        // y dejara sin detectar todos los formatos posteriores.
        if($reader=$xml->getReader('styleSheet/numFmts/numFmt')){
            foreach($reader->getIterator() AS $nf){
                if(is_null($id=$nf['numFmtId'])) continue;
                $new->numFmtDate[intval($id)]=self::isDateFormatCode($nf['formatCode']);
            }
        }
        if($reader=$xml->getReader('styleSheet/cellXfs/xf')){
            $new->xf=$reader->getIterator();
            $new->xf->rewind();
        }
        return $new;
    }

    /**
	 * @param int $calendar Posibles valores: 1900, 1904
	 */
	public function setCalendar(int $calendar=1900){
		$this->calendar=isset(self::CALENDAR_DATE[$calendar])?$calendar:1900;
	}

    /**
	 * @return int
	 */
	public function getCalendar(){
		return $this->calendar;
	}

	public function get(int $index){
        if(!isset($this->cache[$index]) && $this->xf){
            while($this->xf->valid()){
                $i=$this->xf->key();
                $xf=$this->xf->current();
                $numFmtId=$xf['numFmtId'];
                // applyNumberFormat es opcional: si se omite, el numFmtId del cellXf sí aplica
                if(!is_null($numFmtId) && self::attrBool($xf['applyNumberFormat'], true)){
                    $numFmtId=intval($numFmtId);
                    $this->cache[$i]=[
                        'numFmt'=>$numFmtId,
                        'date'=>$this->isDateFormat($numFmtId),
                    ];
                }
                else{
                    // Se cachea igual, de lo contrario un índice sin formato obligaba a recorrer
                    // el resto del iterador en cada consulta
                    $this->cache[$i]=[
                        'numFmt'=>null,
                        'date'=>false,
                    ];
                }
                $this->xf->next();
                if($i===$index) break;
            }
            if(!$this->xf->valid()) $this->xf=null;
        }
        return $this->cache[$index] ?? null;
	}

	public function isDate(int $index){
		return boolval($this->get($index)['date'] ?? false);
	}

	/**
	 * @param int $numFmtId
	 * @return bool
	 */
	private function isDateFormat(int $numFmtId){
		// Un <numFmt> declarado tiene prioridad, ya que puede redefinir un ID integrado
		if(isset($this->numFmtDate[$numFmtId])) return $this->numFmtDate[$numFmtId];
		return in_array($numFmtId, self::BUILTIN_DATE, true);
	}

	/**
	 * Determina si un código de formato representa fecha y/u hora
	 * @param string|null $code
	 * @return bool
	 */
	private static function isDateFormatCode($code){
		if(!is_string($code) || $code==='') return false;
		$code=explode(';', $code)[0]; // Solo la sección de valores positivos
		// Los marcadores de tiempo transcurrido [h] [m] [s] siempre indican hora
		if(preg_match('/\[[hms]+\]/i', $code)) return true;
		$code=preg_replace('/\\\\./', '', $code); // Caracteres escapados
		$code=preg_replace('/"[^"]*"/', '', $code); // Literales entrecomillados
		$code=preg_replace('/\[[^\]]*\]/', '', $code); // Color, región y condiciones
		$code=preg_replace('/\.0+/', '', $code); // Fracción de segundos
		if(preg_match('/[#?0]/', $code)) return false; // Es un formato numérico
		return boolval(preg_match('/[ymdhs]/i', $code));
	}

	/**
	 * Interpreta un atributo booleano de OOXML (ST_Boolean: 1/0/true/false)
	 * @param string|null $val
	 * @param bool $default Valor aplicado cuando el atributo está ausente
	 * @return bool
	 */
	private static function attrBool($val, $default=false){
		if(is_null($val)) return $default;
		$val=strtolower(trim(strval($val)));
		if($val==='') return $default;
		return !in_array($val, ['0', 'false'], true);
	}

	public function parseDate(string $dtValue){
		return self::dateXLStoPHP($dtValue, $this->calendar);
	}

	public static function dateXLStoPHP(string $dtValue, $calendar=1900){
		if(!is_numeric($dtValue)) return $dtValue;
		$dtValue=trim($dtValue);
		if(bccomp(0, $dtValue, 17)>=0) return $dtValue;
		$dateVal=bcdiv($dtValue, 1, 0);
		$timeVal=bcsub($dtValue, $dateVal, 17);
		$total_secs=intval(round($timeVal*86400));
		if($total_secs>=86400){ // El redondeo alcanzó la medianoche del día siguiente
			$total_secs=0;
			$dateVal=bcadd($dateVal, 1, 0);
		}
		$result=[];
		if($dateVal>0){
			isset(self::CALENDAR_DATE[$calendar]) OR $calendar=1900;
			if($dateVal<=60 && $calendar==1900) ++$dateVal; // 1900-02-29 exception
			if($date=date_create(self::CALENDAR_DATE[$calendar].' '.$dateVal.' day')){
				$result[]=$date->format('Y-m-d');
			}
		}
		if($total_secs>0){
			// gmdate evita el desfase de los días con cambio de horario
			$result[]=gmdate('H:i:s', $total_secs);
		}
		return implode(' ', $result);
	}

	public static function validDateTime($val){
		return preg_match('/^\d{4,}-\d{2}-\d{2}(?: \d{2}:\d{2}:\d{2})$/', $val)?$val:null;
	}

	public static function validDate($val){
		return preg_match('/^\d{4,}-\d{2}-\d{2}$/', $val)?$val:null;
	}

	public static function validTime($val){
		return preg_match('/^\d{2}:\d{2}:\d{2}$/', $val)?$val:null;
	}
}
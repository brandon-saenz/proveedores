<?php
final class Modelos_Caracteres extends Modelo {
	public static function caracteres_latinos($string) {
		$string = str_replace(
			array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
			array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
			$string
		);

		$string = str_replace(
			array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
			array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
			$string
		);

		$string = str_replace(
			array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
			array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
			$string
		);

		$string = str_replace(
			array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
			array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
			$string
		);

		$string = str_replace(
			array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
			array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
			$string
		);

		$string = str_replace(
			array('ñ', 'Ñ', 'ç', 'Ç'),
			array('n', 'N', 'c', 'C',),
			$string
		);

		$string = str_replace(
			array("\\", "¨", "º", "~",
				 "#", "@", "|", "!", "\"",
				 "·", "$", "%", "&", "/",
				 "(", ")", "?", "'", "¡",
				 "¿", "[", "^", "`", "]",
				 "+", "}", "{", "¨", "´",
				 ">", "< ", ";", ",", ":", " "),
			'',
			$string
		);

		return $string;
	}

	public static function generar_slug($cadena, $separador = '-') {
		$cadena = trim($cadena);
		$cadena = self::caracteres_latinos($cadena);
		$cadena = strtolower($cadena);
		$cadena = trim($cadena);
		$cadena = str_replace(" ", $separador, $cadena);

		return $cadena;
	}
	
	public static function estilizarCadena($cadena) {
		$cadena = ucwords(mb_strtolower($cadena, 'UTF-8'));
		return $cadena;
	}

	public static function acentos($cadena) {
		$cadena = utf8_encode($cadena);
		return $cadena;
	}

	public static function num2letras($num, $tipo, $fem = false, $dec = true) {
		if ($tipo == 1) {
			$tipo = ' pesos ';
			$moneda = 'M.N.';
		} elseif($tipo == 2) {
			$tipo = ' dolares ';
			$moneda = 'USD';
		}
		$matuni[2]  = "dos"; 
		$matuni[3]  = "tres"; 
		$matuni[4]  = "cuatro"; 
		$matuni[5]  = "cinco"; 
		$matuni[6]  = "seis"; 
		$matuni[7]  = "siete"; 
		$matuni[8]  = "ocho"; 
		$matuni[9]  = "nueve"; 
		$matuni[10] = "diez"; 
		$matuni[11] = "once"; 
		$matuni[12] = "doce"; 
		$matuni[13] = "trece"; 
		$matuni[14] = "catorce"; 
		$matuni[15] = "quince"; 
		$matuni[16] = "dieciseis"; 
		$matuni[17] = "diecisiete"; 
		$matuni[18] = "dieciocho"; 
		$matuni[19] = "diecinueve"; 
		$matuni[20] = "veinte"; 
		$matunisub[2] = "dos"; 
		$matunisub[3] = "tres"; 
		$matunisub[4] = "cuatro"; 
		$matunisub[5] = "quin"; 
		$matunisub[6] = "seis"; 
		$matunisub[7] = "sete"; 
		$matunisub[8] = "ocho"; 
		$matunisub[9] = "nove"; 

		$matdec[2] = "veint"; 
		$matdec[3] = "treinta"; 
		$matdec[4] = "cuarenta"; 
		$matdec[5] = "cincuenta"; 
		$matdec[6] = "sesenta"; 
		$matdec[7] = "setenta"; 
		$matdec[8] = "ochenta"; 
		$matdec[9] = "noventa"; 
		$matsub[3]  = 'mill'; 
		$matsub[5]  = 'bill'; 
		$matsub[7]  = 'mill'; 
		$matsub[9]  = 'trill'; 
		$matsub[11] = 'mill'; 
		$matsub[13] = 'bill'; 
		$matsub[15] = 'mill'; 
		$matmil[4]  = 'millones'; 
		$matmil[6]  = 'billones'; 
		$matmil[7]  = 'de billones'; 
		$matmil[8]  = 'millones de billones'; 
		$matmil[10] = 'trillones'; 
		$matmil[11] = 'de trillones'; 
		$matmil[12] = 'millones de trillones'; 
		$matmil[13] = 'de trillones'; 
		$matmil[14] = 'billones de trillones'; 
		$matmil[15] = 'de billones de trillones'; 
		$matmil[16] = 'millones de billones de trillones'; 

		$float=explode('.',$num);
		$num=$float[0];

		$num = trim((string)@$num); 
		if (@$num[0] == '-') { 
		$neg = 'menos '; 
		$num = substr($num, 1); 
		}else 
		$neg = ''; 
		while (@$num[0] == '0') $num = substr($num, 1); 
		if (@$num[0] < '1' or $num[0] > 9) $num = '0' . $num; 
		$zeros = true; 
		$punt = false; 
		$ent = ''; 
		$fra = ''; 
		for ($c = 0; $c < strlen($num); $c++) { 
		$n = $num[$c]; 
		if (! (strpos(".,'''", $n) === false)) { 
		if ($punt) break; 
		else{ 
		$punt = true; 
		continue; 
		} 

		}elseif (! (strpos('0123456789', $n) === false)) { 
		if ($punt) { 
		if ($n != '0') $zeros = false; 
		$fra .= $n; 
		}else 

		$ent .= $n; 
		}else 

		break; 

		} 
		$ent = '     ' . $ent; 
		if ($dec and $fra and ! $zeros) { 
		$fin = ' coma'; 
		for ($n = 0; $n < strlen($fra); $n++) { 
		if (($s = $fra[$n]) == '0') 
		$fin .= ' cero'; 
		elseif ($s == '1') 
		$fin .= $fem ? ' una' : ' un'; 
		else 
		$fin .= ' ' . $matuni[$s]; 
		} 
		}else 
		$fin = ''; 
		if ((int)$ent === 0) return 'Cero ' . $fin; 
		$tex = ''; 
		$sub = 0; 
		$mils = 0; 
		$neutro = false; 
		while ( ($num = substr($ent, -3)) != '   ') { 
		$ent = substr($ent, 0, -3); 
		if (++$sub < 3 and $fem) { 
		$matuni[1] = 'una'; 
		$subcent = 'as'; 
		}else{ 
		$matuni[1] = $neutro ? 'un' : 'uno'; 
		$subcent = 'os'; 
		} 
		$t = ''; 
		$n2 = substr($num, 1); 
		if ($n2 == '00') { 
		}elseif ($n2 < 21) 
		$t = ' ' . $matuni[(int)$n2]; 
		elseif ($n2 < 30) { 
		$n3 = $num[2]; 
		if ($n3 != 0) $t = 'i' . $matuni[$n3]; 
		$n2 = $num[1]; 
		$t = ' ' . $matdec[$n2] . $t; 
		}else{ 
		$n3 = $num[2]; 
		if ($n3 != 0) $t = ' y ' . $matuni[$n3]; 
		$n2 = $num[1]; 
		$t = ' ' . $matdec[$n2] . $t; 
		} 
		$n = $num[0]; 
		if ($n == 1) { 
		$t = ' ciento' . $t; 
		}elseif ($n == 5){ 
		$t = ' ' . $matunisub[$n] . 'ient' . $subcent . $t; 
		}elseif ($n != 0){ 
		$t = ' ' . $matunisub[$n] . 'cient' . $subcent . $t; 
		} 
		if ($sub == 1) { 
		}elseif (! isset($matsub[$sub])) { 
		if ($num == 1) { 
		$t = ' mil'; 
		}elseif ($num > 1){ 
		$t .= ' mil'; 
		} 
		}elseif ($num == 1) { 
		$t .= ' ' . $matsub[$sub] . '?n'; 
		}elseif ($num > 1){ 
		$t .= ' ' . $matsub[$sub] . 'ones'; 
		}   
		if ($num == '000') $mils ++; 
		elseif ($mils != 0) { 
		if (isset($matmil[$sub])) $t .= ' ' . $matmil[$sub]; 
		$mils = 0; 
		} 
		$neutro = true; 
		$tex = $t . $tex; 
		} 
		$tex = $neg . substr($tex, 1) . $fin; 
		$end_num=strtoupper($tex);
		return $end_num; 
	}

	public static function formatearDinero($format, $number) {
	    $regex  = '/%((?:[\^!\-]|\+|\(|\=.)*)([0-9]+)?'.
	              '(?:#([0-9]+))?(?:\.([0-9]+))?([in%])/';
	    $locale = localeconv();
	    preg_match_all($regex, $format, $matches, PREG_SET_ORDER);
	    foreach ($matches as $fmatch) {
	        $value = floatval($number);
	        $flags = array(
	            'fillchar'  => preg_match('/\=(.)/', $fmatch[1], $match) ?
	                           $match[1] : ' ',
	            'nogroup'   => preg_match('/\^/', $fmatch[1]) > 0,
	            'usesignal' => preg_match('/\+|\(/', $fmatch[1], $match) ?
	                           $match[0] : '+',
	            'nosimbol'  => preg_match('/\!/', $fmatch[1]) > 0,
	            'isleft'    => preg_match('/\-/', $fmatch[1]) > 0
	        );
	        $width      = trim($fmatch[2]) ? (int)$fmatch[2] : 0;
	        $left       = trim($fmatch[3]) ? (int)$fmatch[3] : 0;
	        $right      = trim($fmatch[4]) ? (int)$fmatch[4] : $locale['int_frac_digits'];
	        $conversion = $fmatch[5];

	        $positive = true;
	        if ($value < 0) {
	            $positive = false;
	            $value  *= -1;
	        }
	        $letter = $positive ? 'p' : 'n';

	        $prefix = $suffix = $cprefix = $csuffix = $signal = '';

	        $signal = $positive ? $locale['positive_sign'] : $locale['negative_sign'];
	        switch (true) {
	            case $locale["{$letter}_sign_posn"] == 1 && $flags['usesignal'] == '+':
	                $prefix = $signal;
	                break;
	            case $locale["{$letter}_sign_posn"] == 2 && $flags['usesignal'] == '+':
	                $suffix = $signal;
	                break;
	            case $locale["{$letter}_sign_posn"] == 3 && $flags['usesignal'] == '+':
	                $cprefix = $signal;
	                break;
	            case $locale["{$letter}_sign_posn"] == 4 && $flags['usesignal'] == '+':
	                $csuffix = $signal;
	                break;
	            case $flags['usesignal'] == '(':
	            case $locale["{$letter}_sign_posn"] == 0:
	                $prefix = '(';
	                $suffix = ')';
	                break;
	        }
	        if (!$flags['nosimbol']) {
	            $currency = $cprefix .
	                        ($conversion == 'i' ? $locale['int_curr_symbol'] : $locale['currency_symbol']) .
	                        $csuffix;
	        } else {
	            $currency = '';
	        }
	        $space  = $locale["{$letter}_sep_by_space"] ? ' ' : '';

	        $value = number_format($value, $right, $locale['mon_decimal_point'],
	                 $flags['nogroup'] ? '' : $locale['mon_thousands_sep']);
	        $value = @explode($locale['mon_decimal_point'], $value);

	        $n = strlen($prefix) + strlen($currency) + strlen($value[0]);
	        if ($left > 0 && $left > $n) {
	            $value[0] = str_repeat($flags['fillchar'], $left - $n) . $value[0];
	        }
	        $value = implode($locale['mon_decimal_point'], $value);
	        if ($locale["{$letter}_cs_precedes"]) {
	            $value = $prefix . $currency . $space . $value . $suffix;
	        } else {
	            $value = $prefix . $value . $space . $currency . $suffix;
	        }
	        if ($width > 0) {
	            $value = str_pad($value, $width, $flags['fillchar'], $flags['isleft'] ?
	                     STR_PAD_RIGHT : STR_PAD_LEFT);
	        }

	        $format = str_replace($fmatch[0], $value, $format);
	    }
	    return $format;
	}
}
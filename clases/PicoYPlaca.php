<?php
/**
 * clases/PicoYPlaca.php
 * Motor de cálculo de restricciones vehiculares y festivos de Colombia.
 * Versión Completa: Incluye métodos específicos para todas las casuísticas complejas.
 */

require_once __DIR__ . '/../config-ciudades.php';

class PicoYPlaca {

    private $ciudades;
    private $rotacionesBase;
    private $todasPlacas = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    private $holidaysCache = [];

    public function __construct() {
        global $ciudades;
        $this->rotacionesBase = $ciudades['rotaciones_base'];
        $this->ciudades = $ciudades;
        unset($this->ciudades['rotaciones_base']);
    }

    // =======================================================================
    // 1. GESTIÓN DE FESTIVOS (Cálculo Matemático)
    // =======================================================================

    public function getHolidayName(string $fecha): ?string {
        $year = (int)(new DateTime($fecha))->format('Y');
        return $this->getHolidays($year)[$fecha] ?? null;
    }

    private function getHolidays(int $year): array {
        if (isset($this->holidaysCache[$year])) return $this->holidaysCache[$year];
        $holidays = [];
        
        // Festivos Fijos
        $holidays["{$year}-01-01"] = "Año Nuevo";
        $holidays["{$year}-05-01"] = "Día del Trabajo";
        $holidays["{$year}-07-20"] = "Día de la Independencia";
        $holidays["{$year}-08-07"] = "Batalla de Boyacá";
        $holidays["{$year}-12-08"] = "Inmaculada Concepción";
        $holidays["{$year}-12-25"] = "Navidad";

        // Cálculo de Pascua
        $a = $year % 19; $b = $year % 4; $c = $year % 7;
        $d = (19 * $a + 24) % 30; $e = (2 * $b + 4 * $c + 6 * $d + 5) % 7;
        $days = 22 + $d + $e;
        $easter = new DateTime("$year-03-01"); $easter->modify("+" . ($days - 1) . " days");

        // Semana Santa
        $holidays[$easter->format('Y-m-d')] = "Domingo de Pascua";
        $holidays[(clone $easter)->modify('-3 days')->format('Y-m-d')] = "Jueves Santo";
        $holidays[(clone $easter)->modify('-2 days')->format('Y-m-d')] = "Viernes Santo";

        // Ley Emiliani (Traslado al lunes)
        $this->addEmiliani($holidays, "{$year}-01-06", "Reyes Magos");
        $this->addEmiliani($holidays, "{$year}-03-19", "San José");
        $this->addEmiliani($holidays, "{$year}-06-29", "San Pedro y San Pablo");
        $this->addEmiliani($holidays, "{$year}-08-15", "Asunción de la Virgen");
        $this->addEmiliani($holidays, "{$year}-10-12", "Día de la Raza");
        $this->addEmiliani($holidays, "{$year}-11-01", "Todos los Santos");
        $this->addEmiliani($holidays, "{$year}-11-11", "Independencia de Cartagena");
        
        // Relativos a Pascua (Emiliani)
        $this->addEmiliani($holidays, (clone $easter)->modify('+39 days')->format('Y-m-d'), "Ascensión del Señor", true);
        $this->addEmiliani($holidays, (clone $easter)->modify('+60 days')->format('Y-m-d'), "Corpus Christi", true);
        $this->addEmiliani($holidays, (clone $easter)->modify('+68 days')->format('Y-m-d'), "Sagrado Corazón", true);
        
        $this->holidaysCache[$year] = $holidays;
        return $holidays;
    }

    private function addEmiliani(array &$list, string $date, string $name, bool $isEasterBased = false) {
        $dt = new DateTime($date);
        if ($isEasterBased) $dt->modify('next monday');
        else if ($dt->format('N') != 1) $dt->modify('next monday');
        $list[$dt->format('Y-m-d')] = $name;
    }

    private function isWeekendOrHoliday(string $fecha): bool {
        $dt = new DateTime($fecha);
        if ($dt->format('N') >= 6) return true;
        return isset($this->getHolidays((int)$dt->format('Y'))[$fecha]);
    }

    // =======================================================================
    // 2. MÉTODOS DE LÓGICA ESPECÍFICA POR CIUDAD
    // =======================================================================

    /**
     * Rotación simple semanal (Lunes a Viernes) usada por la mayoría de particulares.
     */
    private function getSimpleRotation(string $fecha, string $regla): array {
        if ($this->isWeekendOrHoliday($fecha)) return [];
        $n = (int)(new DateTime($fecha))->format('N');
        return $this->rotacionesBase[$regla][$n] ?? [];
    }

    /**
     * Bogotá Particulares: Pares/Impares según día del mes.
     */
    private function getBogotaParidad(string $fecha): array {
        if ($this->isWeekendOrHoliday($fecha)) return [];
        $d = (int)(new DateTime($fecha))->format('d');
        return ($d % 2 === 0) ? ['1','2','3','4','5'] : ['6','7','8','9','0'];
    }

    /**
     * Bogotá Carga: Sábados alternos (Impares / Pares).
     */
    private function getBogotaCargaSabado(string $fecha): array {
        $dt = new DateTime($fecha);
        if ((int)$dt->format('N') != 6) return []; 
        
        // Ancla: Sábado 29 Nov 2025 -> Impares (1,3,5,7,9)
        $anchor = new DateTime('2025-11-29');
        $diffDays = $anchor->diff($dt)->days * ($anchor > $dt ? -1 : 1);
        $weeks = floor($diffDays / 7);
        
        // Si la diferencia de semanas es par, es el mismo grupo (Impares). Si es impar, cambia (Pares).
        return ($weeks % 2 === 0) ? ['1','3','5','7','9'] : ['0','2','4','6','8'];
    }

    /**
     * Armenia Taxis: Secuencia 0-9. Domingo avanza 2 posiciones.
     */
    private function getArmeniaTaxis(string $fecha): array {
        // Ancla: Lun 24 Nov 2025 -> 5.
        $anchor = new DateTime('2025-11-24');
        $target = new DateTime($fecha);
        $diffDays = $anchor->diff($target)->days * ($anchor > $target ? -1 : 1);
        
        $index = 5; 
        $temp = clone $anchor;
        
        if ($diffDays >= 0) {
            for($i=0; $i < $diffDays; $i++) {
                $temp->modify('+1 day');
                // Suma 2 si es domingo, 1 si es otro día
                $index += ((int)$temp->format('N') == 7) ? 2 : 1;
            }
        } else {
            for($i=0; $i < abs($diffDays); $i++) {
                $isSun = (int)$temp->format('N') == 7;
                $temp->modify('-1 day');
                $index -= ($isSun ? 2 : 1);
            }
        }
        
        $val = (($index % 10) + 10) % 10;
        // Domingo muestra 2 dígitos
        if ((int)$target->format('N') == 7) return [(string)$val, (string)(($val+1)%10)];
        return [(string)$val];
    }

    /**
     * Bucaramanga Motos/Colectivo: L-V fijo, Sábado rotativo.
     */
    private function getBucaramangaMotos(string $fecha): array {
        $dt = new DateTime($fecha);
        $n = (int)$dt->format('N');
        
        if ($n == 7 || $this->getHolidayName($fecha)) return [];
        
        if ($n < 6) { // L-V Fijo
            return match($n) { 1=>['3','4'], 2=>['5','6'], 3=>['7','8'], 4=>['9','0'], 5=>['1','2'], default=>[] };
        }
        
        // Sábado Rota: Ancla 29 Nov 2025 -> 5-6 (idx 2)
        $anchor = new DateTime('2025-11-29');
        $weeks = floor(($anchor->diff($dt)->days * ($anchor > $dt ? -1 : 1)) / 7);
        $pares = [['1','2'],['3','4'],['5','6'],['7','8'],['9','0']];
        $idx = (2 + $weeks) % 5; 
        if ($idx < 0) $idx += 5;
        return $pares[$idx];
    }

    /**
     * Bucaramanga Taxis: Rotación semanal de Lunes a Viernes.
     */
    private function getBucaramangaTaxis(string $fecha): array {
        if ($this->isWeekendOrHoliday($fecha)) return [];
        
        // Ancla: Lun 24 Nov -> 1-2.
        $anchor = new DateTime('2025-11-24');
        $currMon = clone $dt = new DateTime($fecha);
        if ((int)$dt->format('N') != 1) $currMon->modify('last monday');
        
        $weeks = floor(($anchor->diff($currMon)->days * ($anchor > $currMon ? -1 : 1)) / 7);
        $pares = [['1','2'],['3','4'],['5','6'],['7','8'],['9','0']];
        
        $baseIdx = 0; // Semana 0
        $dayOffset = (int)$dt->format('N') - 1; 
        $finalIdx = ($baseIdx + $weeks + $dayOffset) % 5;
        if ($finalIdx < 0) $finalIdx += 5;
        return $pares[$finalIdx];
    }

    /**
     * Cúcuta Taxis: Retrocede 1 dígito por día hábil.
     */
    private function getCucutaTaxis(string $fecha): array {
        $dt = new DateTime($fecha);
        if ((int)$dt->format('N') >= 6 || $this->isWeekendOrHoliday($fecha)) return [];
        
        // Ancla: Lun 24 Nov -> 4.
        $anchor = new DateTime('2025-11-24');
        $diff = $anchor->diff($dt);
        $days = $diff->days * ($diff->invert ? -1 : 1);
        
        $businessDays = 0;
        $step = $days >= 0 ? 1 : -1;
        $temp = clone $anchor;
        
        for($i=0; $i < abs($days); $i++) {
            $temp->modify(($step > 0 ? '+' : '-').'1 day');
            $n = (int)$temp->format('N');
            if ($n < 6 && !$this->getHolidayName($temp->format('Y-m-d'))) {
                $businessDays += $step;
            }
        }
        
        $val = (4 - $businessDays) % 10;
        if ($val < 0) $val += 10;
        return [(string)$val];
    }

    /**
     * Ibagué Colectivo: Ciclo continuo de 5 pares, rota todos los días.
     */
    private function getIbagueColectivo(string $fecha): array {
        // Ancla: Lun 24 Nov -> 1-2.
        $seq = [['1','2'], ['0','3'], ['4','9'], ['5','6'], ['7','8']];
        $anchor = new DateTime('2025-11-24');
        $dt = new DateTime($fecha);
        
        $diff = $anchor->diff($dt)->days * ($anchor > $dt ? -1 : 1);
        $idx = $diff % 5; 
        if ($idx < 0) $idx += 5;
        
        return $seq[$idx];
    }

    /**
     * Ibagué Taxis: Secuencia continua 0-9, todos los días.
     */
    private function getIbagueTaxis(string $fecha): array {
        // Ancla: Dom 23 Nov -> 9.
        $anchor = new DateTime('2025-11-23');
        $dt = new DateTime($fecha);
        $diff = $anchor->diff($dt)->days * ($anchor > $dt ? -1 : 1);
        
        $val = (9 + $diff) % 10; 
        if ($val < 0) $val += 10;
        return [(string)$val];
    }

    /**
     * Pereira Taxis: Lista específica, salta sábados.
     */
    private function getPereiraTaxis(string $fecha): array {
        $dt = new DateTime($fecha);
        if ((int)$dt->format('N') == 6) return []; // Sábado NO
        
        // Secuencia dada por el usuario
        $seq = [4,0,6,3,7,9,5,1,7,4,8,0,6,2,8,5,9,1,7,3,9,6,0,2,8,4]; 
        $anchor = new DateTime('2025-11-24'); // Lun 24 Nov -> 4
        
        $diff = $anchor->diff($dt);
        $days = $diff->days * ($diff->invert ? -1 : 1);
        
        $validDays = 0;
        $step = $days >= 0 ? 1 : -1;
        $temp = clone $anchor;
        
        for($i=0; $i < abs($days); $i++) {
            $temp->modify(($step > 0 ? '+' : '-').'1 day');
            // Contar todos excepto sábados
            if ((int)$temp->format('N') != 6) $validDays += $step;
        }
        
        $idx = $validDays % count($seq); 
        if ($idx < 0) $idx += count($seq);
        return [(string)$seq[$idx]];
    }

    /**
     * Santa Marta Taxis: Rotación semanal inversa, división de par en fin de semana.
     */
    private function getSantaMartaTaxis(string $fecha): array {
        $dt = new DateTime($fecha);
        $day = (int)$dt->format('N');
        if ($day == 7) return []; // Domingo libre
        
        // Ancla: Lun 24 Nov -> 5-6 (Idx 2). Retrocede 1 por semana.
        $anchor = new DateTime('2025-11-24');
        $currMon = clone $dt;
        if ($day != 1) $currMon->modify('last monday');
        
        $weeks = floor(($anchor->diff($currMon)->days * ($anchor > $currMon ? -1 : 1)) / 7);
        
        $baseIdx = (2 - $weeks) % 5; 
        if ($baseIdx < 0) $baseIdx += 5;
        
        $pares = [['1','2'],['3','4'],['5','6'],['7','8'],['9','0']];
        
        if ($day <= 4) {
            // L-J: Avanza 1 por día
            return $pares[($baseIdx + $day - 1) % 5];
        } elseif ($day == 5) {
            // Viernes: 1er digito del par siguiente
            return [$pares[($baseIdx + 4) % 5][0]]; 
        } elseif ($day == 6) {
            // Sábado: 2do digito del par siguiente
            return [$pares[($baseIdx + 4) % 5][1]]; 
        }
        return [];
    }

    /**
     * Villavicencio Taxis: Secuencia 0-9 diaria continua.
     */
    private function getVillavicencioTaxis(string $fecha): array {
        // Ancla: Lun 24 Nov -> 0.
        $anchor = new DateTime('2025-11-24');
        $dt = new DateTime($fecha);
        $diff = $anchor->diff($dt)->days * ($anchor > $dt ? -1 : 1);
        $val = $diff % 10; 
        if ($val < 0) $val += 10;
        return [(string)$val];
    }

    /**
     * Popayán Carga: Secuencia en días hábiles.
     */
    private function getPopayanCarga(string $fecha): array {
        if ($this->isWeekendOrHoliday($fecha)) return [];
        // Ancla: Lun 24 Nov -> 7.
        $anchor = new DateTime('2025-11-24');
        $target = new DateTime($fecha);
        $diff = $anchor->diff($target);
        $days = $diff->days * ($diff->invert ? -1 : 1);
        
        $bizDays = 0; 
        $step = $days >= 0 ? 1 : -1;
        $t = clone $anchor;
        
        for($i=0; $i<abs($days); $i++){
            $t->modify(($step>0?'+':'-')."1 day");
            if (!$this->isWeekendOrHoliday($t->format('Y-m-d'))) $bizDays += $step;
        }
        $val = (7 + $bizDays) % 10; 
        if ($val < 0) $val += 10;
        return [(string)$val];
    }

    /**
     * Manejador Genérico para secuencias simples que no requieren método propio.
     * (Bogotá Taxis, Medellín Taxis, Buenaventura Taxis, etc)
     */
    private function getSequentialRotation(string $fecha, string $regla): array {
        $configs = [
            // Ancla: 22 Nov (Sáb) -> 9-0. Pares. Salta Domingo.
            'bogota_taxis' => ['anchor'=>'2025-11-22', 'val'=>9, 'skip'=>false, 'pairs'=>true, 'skipSun'=>true],
            'bogota_servicio_especial' => ['anchor'=>'2025-11-22', 'val'=>9, 'skip'=>false, 'pairs'=>true, 'skipSun'=>true],
            // Ancla: 24 Nov (Lun) -> 1. Salta finde y festivos.
            'medellin_taxis' => ['anchor'=>'2025-11-24', 'val'=>1, 'skip'=>true],
            // Ancla: 22 Nov (Sáb) -> 0-1. Pares. Diario.
            'cali_colectivo' => ['anchor'=>'2025-11-22', 'val'=>0, 'skip'=>false, 'pairs'=>true],
            // Ancla: 23 Nov (Dom) -> 5-6. Pares. Diario.
            'buenaventura_taxis' => ['anchor'=>'2025-11-23', 'val'=>5, 'skip'=>false, 'pairs'=>true],
        ];
        
        if (!isset($configs[$regla])) return [];
        $cfg = $configs[$regla];
        
        $dt = new DateTime($fecha);
        $anchor = new DateTime($cfg['anchor']);
        $diff = $anchor->diff($dt)->days * ($anchor > $dt ? -1 : 1);
        $idx = $cfg['val'];
        $temp = clone $anchor;
        
        $pares = [['1','2'],['3','4'],['5','6'],['7','8'],['9','0']];
        // Encontrar índice par si aplica
        if ($cfg['pairs'] ?? false) {
            foreach($pares as $k=>$v) if(in_array((string)$cfg['val'], $v)) { $idx = $k; break; }
        }

        // Verificar si HOY se salta
        if (($cfg['skip'] && $this->isWeekendOrHoliday($fecha)) || 
            (($cfg['skipSun'] ?? false) && (int)$dt->format('N') == 7)) return [];

        // Calcular avance día a día
        for($i=0; $i < abs($diff); $i++) {
            $dir = $diff >= 0 ? 1 : -1;
            $temp->modify(($dir > 0 ? '+' : '-')."1 day");
            
            $skip = false;
            if ($cfg['skip'] && $this->isWeekendOrHoliday($temp->format('Y-m-d'))) $skip = true;
            if (($cfg['skipSun'] ?? false) && (int)$temp->format('N') == 7) $skip = true;
            
            if (!$skip) {
                if ($cfg['pairs'] ?? false) {
                    $idx = ($idx + $dir) % 5; if ($idx < 0) $idx += 5;
                } else {
                    $idx = ($idx + $dir) % 10; if ($idx < 0) $idx += 10;
                }
            }
        }
        
        return ($cfg['pairs'] ?? false) ? $pares[$idx] : [(string)$idx];
    }

    // =======================================================================
    // 3. API PÚBLICA
    // =======================================================================

    public function obtenerRestriccion(string $ciudad, string $fecha, string $tipo): array {
        if (!isset($this->ciudades[$ciudad]) || !isset($this->ciudades[$ciudad]['tipos'][$tipo])) {
            return ['restricciones'=>[], 'permitidas'=>$this->todasPlacas, 'horario'=>'', 'hay_pico'=>false, 'festivo'=>null];
        }
        $info = $this->ciudades[$ciudad]['tipos'][$tipo];
        $regla = $info['regla'];
        $festivo = $this->getHolidayName($fecha);
        $restricciones = [];

        // ENRUTAMIENTO DE REGLAS
        switch ($regla) {
            case 'bogota_paridad': $restricciones = $this->getBogotaParidad($fecha); break;
            case 'bogota_carga_sabado': $restricciones = $this->getBogotaCargaSabado($fecha); break;
            case 'armenia_taxis': $restricciones = $this->getArmeniaTaxis($fecha); break;
            case 'bucaramanga_motos': 
            case 'bucaramanga_colectivo': $restricciones = $this->getBucaramangaMotos($fecha); break;
            case 'bucaramanga_taxis': $restricciones = $this->getBucaramangaTaxis($fecha); break;
            case 'cucuta_taxis': $restricciones = $this->getCucutaTaxis($fecha); break;
            case 'pereira_taxis': $restricciones = $this->getPereiraTaxis($fecha); break;
            case 'santa_marta_taxis': $restricciones = $this->getSantaMartaTaxis($fecha); break;
            case 'popayan_carga': $restricciones = $this->getPopayanCarga($fecha); break;
            case 'ibague_taxis': $restricciones = $this->getIbagueTaxis($fecha); break;
            case 'ibague_colectivo': $restricciones = $this->getIbagueColectivo($fecha); break;
            case 'villavicencio_taxis': $restricciones = $this->getVillavicencioTaxis($fecha); break;
            
            // Reglas gestionadas por el método genérico
            case 'bogota_taxis': 
            case 'bogota_servicio_especial': 
            case 'medellin_taxis': 
            case 'cali_colectivo': 
            case 'buenaventura_taxis': 
                $restricciones = $this->getSequentialRotation($fecha, $regla); 
                break;
                
            case 'pasto_particulares': 
                // Pasto tiene una rotación simple fija
                $rot = [1=>['2','3'], 2=>['4','5'], 3=>['6','7'], 4=>['8','9'], 5=>['0','1']];
                if (!$this->isWeekendOrHoliday($fecha)) $restricciones = $rot[(int)(new DateTime($fecha))->format('N')] ?? [];
                break;
                
            default: 
                $restricciones = $this->getSimpleRotation($fecha, $regla);
        }

        $hayPico = count($restricciones) > 0;
        return [
            'restricciones' => $restricciones,
            'permitidas' => array_values(array_diff($this->todasPlacas, $restricciones)),
            'horario' => $info['horario'],
            'hay_pico' => $hayPico,
            'festivo' => $festivo
        ];
    }
}
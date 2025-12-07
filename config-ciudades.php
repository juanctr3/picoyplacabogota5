<?php
/**
 * config-ciudades.php
 * Versión 12.0 - Con Contenido SEO (Texto Experto)
 * Incluye textos descriptivos para el Acordeón Semántico.
 */

$rotaciones_base = [
    'rotacion_1' => [1=>['1','2'], 2=>['3','4'], 3=>['5','6'], 4=>['7','8'], 5=>['9','0']],
    'rotacion_2' => [1=>['3','4'], 2=>['5','6'], 3=>['7','8'], 4=>['9','0'], 5=>['1','2']],
    'rotacion_3' => [1=>['6','9'], 2=>['5','7'], 3=>['1','8'], 4=>['0','2'], 5=>['3','4']],
    'rotacion_4' => [1=>['5','6'], 2=>['7','8'], 3=>['9','0'], 4=>['1','2'], 5=>['3','4']],
    'rotacion_5' => [1=>['0','1'], 2=>['2','3'], 3=>['4','5'], 4=>['6','7'], 5=>['8','9']],
    'rotacion_6' => [1=>['7','8'], 2=>['9','0'], 3=>['1','2'], 4=>['3','4'], 5=>['5','6']],
];

$ciudades = [
    'bogota' => [
        'nombre' => 'Bogotá',
        'contenido_seo' => '
            <p>El <strong>Pico y Placa en Bogotá</strong> opera bajo un esquema de días pares e impares. La medida busca reducir la congestión vehicular y promover la movilidad sostenible.</p>
            <ul>
                <li><strong>Horario:</strong> Lunes a viernes de 6:00 a.m. a 9:00 p.m. jornada continua.</li>
                <li><strong>Excepciones:</strong> Vehículos eléctricos, híbridos y aquellos que paguen el "Pico y Placa Solidario" están exentos. También aplica excepción para carro compartido si se registra previamente (sujeto a cambios).</li>
                <li><strong>Multa 2025:</strong> La sanción equivale a 15 salarios mínimos diarios legales vigentes (aprox. $650.000 COP) más la inmovilización del vehículo (grúa y patios).</li>
                <li><strong>Límites:</strong> Aplica en todo el perímetro urbano de la capital.</li>
            </ul>',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => '6:00 AM - 9:00 PM',
                'regla' => 'bogota_paridad',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '9:00 PM']]
            ],
            'taxis' => [
                'nombre_display' => 'Taxis',
                'horario' => '5:30 AM - 9:00 PM',
                'regla' => 'bogota_taxis',
                'rangos_horarios_php' => [['inicio' => '5:30 AM', 'fin' => '9:00 PM']]
            ],
            'servicio-especial' => [
                'nombre_display' => 'Servicio Especial',
                'horario' => '5:30 AM - 9:00 PM',
                'regla' => 'bogota_servicio_especial',
                'rangos_horarios_php' => [['inicio' => '5:30 AM', 'fin' => '9:00 PM']]
            ],
            'carga-mas-20' => [
                'nombre_display' => 'Carga > 20 años',
                'horario' => 'Sábados: 5:00 AM - 9:00 PM',
                'regla' => 'bogota_carga_sabado',
                'rangos_horarios_php' => [['inicio' => '5:00 AM', 'fin' => '9:00 PM']]
            ]
        ]
    ],
    'medellin' => [
        'nombre' => 'Medellín',
        'contenido_seo' => '
            <p>En <strong>Medellín y el Valle de Aburrá</strong>, la medida rota semestralmente. Es fundamental estar atento a los cambios de dígito cada 6 meses.</p>
            <ul>
                <li><strong>Cobertura:</strong> Aplica en Medellín, Envigado, Itagüí, Sabaneta, Bello, Copacabana, Girardota, Barbosa, La Estrella y Caldas.</li>
                <li><strong>Vías Exentas:</strong> Actualmente la mayoría de vías regionales ya NO tienen exención, consulta el decreto vigente.</li>
                <li><strong>Exención Verde:</strong> Los vehículos 100% eléctricos e híbridos están exentos de la medida, previo registro en la Secretaría de Movilidad.</li>
            </ul>',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => '5:00 AM - 8:00 PM',
                'regla' => 'rotacion_3',
                'rangos_horarios_php' => [['inicio' => '5:00 AM', 'fin' => '8:00 PM']]
            ],
            'motos' => [
                'nombre_display' => 'Motos',
                'horario' => '5:00 AM - 8:00 PM',
                'regla' => 'rotacion_3',
                'rangos_horarios_php' => [['inicio' => '5:00 AM', 'fin' => '8:00 PM']]
            ],
            'taxis' => [
                'nombre_display' => 'Taxis',
                'horario' => '6:00 AM - 8:00 PM',
                'regla' => 'medellin_taxis',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '8:00 PM']]
            ]
        ]
    ],
    'cali' => [
        'nombre' => 'Cali',
        'contenido_seo' => '
            <p>La normativa en <strong>Cali</strong> se caracteriza por rotaciones periódicas y la opción de pagar por circular.</p>
            <ul>
                <li><strong>Tasa por Congestión:</strong> Los ciudadanos pueden pagar una tarifa mensual para quedar exentos de la medida de Pico y Placa.</li>
                <li><strong>Horario:</strong> La restricción suele ser continua o partida dependiendo del decreto del año en curso. Revisa el reloj superior para exactitud.</li>
                <li><strong>Vehículos Exentos:</strong> Motocicletas (salvo excepciones), vehículos oficiales y de emergencia.</li>
            </ul>',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => '6:00 AM - 7:00 PM',
                'regla' => 'rotacion_2',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '7:00 PM']]
            ],
            'transporte-colectivo' => [
                'nombre_display' => 'Transporte Público Colectivo',
                'horario' => '5:00 AM - 10:00 PM',
                'regla' => 'cali_colectivo',
                'rangos_horarios_php' => [['inicio' => '5:00 AM', 'fin' => '10:00 PM']]
            ],
        ]
    ],
    'barranquilla' => [
        'nombre' => 'Barranquilla',
        'contenido_seo' => '<p>En Barranquilla, el Pico y Placa aplica principalmente para <strong>Taxis</strong>. Los particulares generalmente no tienen restricción, salvo en carnavales o decretos especiales.</p>',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => 'Sin restricción activa.',
                'regla' => 'sin_pico',
                'rangos_horarios_php' => []
            ],
            'taxis' => [
                'nombre_display' => 'Taxis',
                'horario' => 'Sin restricción general.',
                'regla' => 'sin_pico',
                'rangos_horarios_php' => []
            ]
        ]
    ],
    'cartagena' => [
        'nombre' => 'Cartagena',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => '7:00 AM - 6:00 PM',
                'regla' => 'rotacion_2',
                'rangos_horarios_php' => [['inicio' => '7:00 AM', 'fin' => '6:00 PM']]
            ],
            'motos' => [
                'nombre_display' => 'Motos',
                'horario' => '7:00 AM - 6:00 PM',
                'regla' => 'rotacion_2',
                'rangos_horarios_php' => [['inicio' => '7:00 AM', 'fin' => '6:00 PM']]
            ],
        ]
    ],
    'bucaramanga' => [
        'nombre' => 'Bucaramanga',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => 'L-V: 6:00 AM - 8:00 PM | Sábados: 9:00 AM - 1:00 PM',
                'regla' => 'rotacion_2',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '8:00 PM']]
            ],
            'motos' => [
                'nombre_display' => 'Motos',
                'horario' => '6:00 AM - 8:00 PM',
                'regla' => 'bucaramanga_motos',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '8:00 PM']]
            ],
            'transporte-colectivo' => [
                'nombre_display' => 'Transporte Público',
                'horario' => 'Todo el día',
                'regla' => 'bucaramanga_colectivo',
                'rangos_horarios_php' => [['inicio' => '00:00 AM', 'fin' => '11:59 PM']]
            ],
            'taxis' => [
                'nombre_display' => 'Taxis',
                'horario' => 'Todo el día',
                'regla' => 'bucaramanga_taxis',
                'rangos_horarios_php' => [['inicio' => '00:00 AM', 'fin' => '11:59 PM']]
            ]
        ]
    ],
    'santa-marta' => [
        'nombre' => 'Santa Marta',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => '7:00 AM - 9:00 AM, 11:30 AM - 2:00 PM, 5:00 PM - 8:00 PM',
                'regla' => 'rotacion_1',
                'rangos_horarios_php' => [
                    ['inicio' => '7:00 AM', 'fin' => '9:00 AM'],
                    ['inicio' => '11:30 AM', 'fin' => '2:00 PM'],
                    ['inicio' => '5:00 PM', 'fin' => '8:00 PM']
                ]
            ],
            'motos' => [
                'nombre_display' => 'Motos',
                'horario' => '7:00 AM - 7:00 PM',
                'regla' => 'rotacion_1',
                'rangos_horarios_php' => [['inicio' => '7:00 AM', 'fin' => '7:00 PM']]
            ],
            'taxis' => [
                'nombre_display' => 'Taxis',
                'horario' => '7:00 AM - 11:59 PM',
                'regla' => 'santa_marta_taxis',
                'rangos_horarios_php' => [['inicio' => '7:00 AM', 'fin' => '11:59 PM']]
            ]
        ]
    ],
    'armenia' => [
        'nombre' => 'Armenia',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => '6:00 AM - 7:00 PM',
                'regla' => 'rotacion_4',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '7:00 PM']]
            ],
            'motos' => [
                'nombre_display' => 'Motos',
                'horario' => '6:00 AM - 7:00 PM',
                'regla' => 'rotacion_4',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '7:00 PM']]
            ],
            'taxis' => [
                'nombre_display' => 'Taxis',
                'horario' => 'Todo el día',
                'regla' => 'armenia_taxis',
                'rangos_horarios_php' => [['inicio' => '00:00 AM', 'fin' => '11:59 PM']]
            ]
        ]
    ],
    'tunja' => [
        'nombre' => 'Tunja',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => '6:00 AM - 8:00 PM',
                'regla' => 'rotacion_2',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '8:00 PM']]
            ]
        ]
    ],
    'ibague' => [
        'nombre' => 'Ibagué',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares (Placa Ibagué)',
                'horario' => '6:00 AM - 9:00 AM, 11:00 AM - 3:00 PM, 5:00 PM - 9:00 PM',
                'regla' => 'rotacion_5',
                'rangos_horarios_php' => [
                    ['inicio' => '6:00 AM', 'fin' => '9:00 AM'],
                    ['inicio' => '11:00 AM', 'fin' => '3:00 PM'],
                    ['inicio' => '5:00 PM', 'fin' => '9:00 PM']
                ]
            ],
            'taxis' => [
                'nombre_display' => 'Taxis',
                'horario' => 'Todo el día',
                'regla' => 'ibague_taxis',
                'rangos_horarios_php' => [['inicio' => '00:00 AM', 'fin' => '11:59 PM']]
            ],
            'transporte-colectivo' => [
                'nombre_display' => 'Transporte Colectivo',
                'horario' => 'Todo el día',
                'regla' => 'ibague_colectivo',
                'rangos_horarios_php' => [['inicio' => '00:00 AM', 'fin' => '11:59 PM']]
            ]
        ]
    ],
    'pereira' => [
        'nombre' => 'Pereira',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => '6:00 AM - 8:00 PM',
                'regla' => 'rotacion_5',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '8:00 PM']]
            ],
            'motos' => [
                'nombre_display' => 'Motos',
                'horario' => '6:00 AM - 8:00 PM',
                'regla' => 'rotacion_5',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '8:00 PM']]
            ],
            'taxis' => [
                'nombre_display' => 'Taxis',
                'horario' => '7:00 AM - 3:00 AM (día sig.)',
                'regla' => 'pereira_taxis',
                'rangos_horarios_php' => [
                    ['inicio' => '7:00 AM', 'fin' => '11:59 PM'],
                    ['inicio' => '00:00 AM', 'fin' => '3:00 AM']
                ]
            ]
        ]
    ],
    'popayan' => [
        'nombre' => 'Popayán',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => '6:00 AM - 7:00 PM',
                'regla' => 'rotacion_2',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '7:00 PM']]
            ],
            'motos' => [
                'nombre_display' => 'Motos',
                'horario' => '6:00 AM - 7:00 PM',
                'regla' => 'rotacion_2',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '7:00 PM']]
            ],
            'carga-menor' => [
                'nombre_display' => 'Carga < 1.5t',
                'horario' => '7:00 AM - 8:00 PM',
                'regla' => 'popayan_carga',
                'rangos_horarios_php' => [['inicio' => '7:00 AM', 'fin' => '8:00 PM']]
            ]
        ]
    ],
    'villavicencio' => [
        'nombre' => 'Villavicencio',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => '6:00 AM - 8:00 PM',
                'regla' => 'rotacion_6',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '8:00 PM']]
            ],
            'taxis' => [
                'nombre_display' => 'Taxis',
                'horario' => '6:00 AM - 12:00 PM',
                'regla' => 'villavicencio_taxis',
                'rangos_horarios_php' => [['inicio' => '6:00 AM', 'fin' => '11:59 PM']]
            ]
        ]
    ],
    'cucuta' => [
        'nombre' => 'Cúcuta',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares (Área Metro)',
                'horario' => '7:00-8:30 AM, 11:30-2:30 PM, 5:30-7:30 PM',
                'regla' => 'rotacion_1',
                'rangos_horarios_php' => [
                    ['inicio' => '7:00 AM', 'fin' => '8:30 AM'],
                    ['inicio' => '11:30 AM', 'fin' => '2:30 PM'],
                    ['inicio' => '5:30 PM', 'fin' => '7:30 PM']
                ]
            ],
            'taxis' => [
                'nombre_display' => 'Taxis',
                'horario' => '7:00 AM - 8:00 PM',
                'regla' => 'cucuta_taxis',
                'rangos_horarios_php' => [['inicio' => '7:00 AM', 'fin' => '8:00 PM']]
            ],
            'motos' => [
                'nombre_display' => 'Motos',
                'horario' => '7:00 AM - 8:00 PM',
                'regla' => 'rotacion_1',
                'rangos_horarios_php' => [['inicio' => '7:00 AM', 'fin' => '8:00 PM']]
            ]
        ]
    ],
    'buenaventura' => [
        'nombre' => 'Buenaventura',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => '7:00 AM - 7:00 PM',
                'regla' => 'rotacion_2',
                'rangos_horarios_php' => [['inicio' => '7:00 AM', 'fin' => '7:00 PM']]
            ],
            'taxis' => [
                'nombre_display' => 'Taxis',
                'horario' => '7:00 AM - 7:00 PM',
                'regla' => 'buenaventura_taxis',
                'rangos_horarios_php' => [['inicio' => '7:00 AM', 'fin' => '7:00 PM']]
            ]
        ]
    ],
    'pasto' => [
        'nombre' => 'Pasto',
        'tipos' => [
            'particulares' => [
                'nombre_display' => 'Particulares',
                'horario' => '7:30 AM - 7:00 PM',
                'regla' => 'pasto_particulares',
                'rangos_horarios_php' => [['inicio' => '7:30 AM', 'fin' => '7:00 PM']]
            ],
            'motos' => [
                'nombre_display' => 'Motos',
                'horario' => '7:30 AM - 7:00 PM',
                'regla' => 'pasto_particulares',
                'rangos_horarios_php' => [['inicio' => '7:30 AM', 'fin' => '7:00 PM']]
            ],
            'taxis' => [
                'nombre_display' => 'Taxis',
                'horario' => '7:30 AM - 7:00 PM',
                'regla' => 'pasto_particulares',
                'rangos_horarios_php' => [['inicio' => '7:30 AM', 'fin' => '7:00 PM']]
            ]
        ]
    ]
];

$ciudades = array_merge($ciudades, ['rotaciones_base' => $rotaciones_base]);
?>

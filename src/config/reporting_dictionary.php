<?php

// Este es el diccionario de datos para el generador de reportes.
// Define las tablas, columnas y uniones permitidas para construir consultas de forma segura.
// Es una lista blanca (whitelist) para prevenir inyecciones de SQL y acceso no autorizado a datos.

$reportingDictionary = [
    'base_table' => 'documentos_detalle',
    'tables' => [
        'documentos_detalle' => [
            'friendly_name' => 'Detalle de Documentos',
            'alias' => 'dd',
            'columns' => [
                'dd.item' => ['friendly_name' => 'Item', 'type' => 'numeric'],
                'dd.cantidad' => ['friendly_name' => 'Cantidad', 'type' => 'numeric'],
                'dd.descripcion' => ['friendly_name' => 'Descripción Detalle', 'type' => 'string'],
                'dd.precio_unitario' => ['friendly_name' => 'Precio Unitario', 'type' => 'numeric'],
                'dd.precio_total' => ['friendly_name' => 'Precio Total', 'type' => 'numeric'],
                'dd.total_soles' => ['friendly_name' => 'Total Soles (Detalle)', 'type' => 'numeric'],
                'dd.total_dolares' => ['friendly_name' => 'Total Dólares (Detalle)', 'type' => 'numeric'],
            ]
        ],
        'documentos' => [
            'friendly_name' => 'Documentos (Cabecera)',
            'alias' => 'd',
            'columns' => [
                'd.serie_documento' => ['friendly_name' => 'Serie', 'type' => 'string'],
                'd.numero_documento' => ['friendly_name' => 'Número', 'type' => 'string'],
                'd.fecha_emision' => ['friendly_name' => 'Fecha Emisión', 'type' => 'date'],
                'd.fecha_registro' => ['friendly_name' => 'Fecha Registro', 'type' => 'date'],
                'd.moneda' => ['friendly_name' => 'Moneda', 'type' => 'string'],
                'd.tipo_cambio' => ['friendly_name' => 'Tipo Cambio', 'type' => 'numeric'],
                'd.subtotal' => ['friendly_name' => 'Subtotal (Cabecera)', 'type' => 'numeric'],
                'd.igv' => ['friendly_name' => 'IGV (Cabecera)', 'type' => 'numeric'],
                'd.total' => ['friendly_name' => 'Total (Cabecera)', 'type' => 'numeric'],
                'd.glosa' => ['friendly_name' => 'Glosa', 'type' => 'string'],
            ]
        ],
        'centros_costos' => [
            'friendly_name' => 'Centros de Costos',
            'alias' => 'cc',
            'columns' => [
                'cc.codigo' => ['friendly_name' => 'Código C.Costo', 'type' => 'string'],
                'cc.nombre' => ['friendly_name' => 'Nombre C.Costo', 'type' => 'string'],
            ]
        ],
        'documento_detalle_distribucion' => [
            'friendly_name' => 'Distribución de C.Costo',
            'alias' => 'ddd',
            'columns' => [
                'ddd.porcentaje' => ['friendly_name' => 'Porcentaje C.Costo', 'type' => 'numeric'],
            ]
        ],
        'conceptos' => [
            'friendly_name' => 'Conceptos',
            'alias' => 'c',
            'columns' => [
                'c.codigo' => ['friendly_name' => 'Código Concepto', 'type' => 'string'],
                'c.nombre' => ['friendly_name' => 'Nombre Concepto', 'type' => 'string'],
                'c.tipo' => ['friendly_name' => 'Tipo Concepto', 'type' => 'string'],
                'c.cuenta_contable' => ['friendly_name' => 'Cuenta Contable', 'type' => 'string'],
            ]
        ],
        'tipos_documento' => [
            'friendly_name' => 'Tipos de Documento',
            'alias' => 'td',
            'columns' => [
                'td.codigo' => ['friendly_name' => 'Código Tipo Doc.', 'type' => 'string'],
                'td.nombre' => ['friendly_name' => 'Nombre Tipo Doc.', 'type' => 'string'],
            ]
        ],
        'proyectos' => [
            'friendly_name' => 'Proyectos',
            'alias' => 'p',
            'columns' => [
                'p.codigo' => ['friendly_name' => 'Código Proyecto', 'type' => 'string'],
                'p.nombre' => ['friendly_name' => 'Nombre Proyecto', 'type' => 'string'],
            ]
        ],
        'sub_proyectos' => [
            'friendly_name' => 'Sub Proyectos',
            'alias' => 'sp',
            'columns' => [
                'sp.codigo' => ['friendly_name' => 'Código Sub Proyecto', 'type' => 'string'],
                'sp.nombre' => ['friendly_name' => 'Nombre Sub Proyecto', 'type' => 'string'],
            ]
        ],
        'auxiliares' => [
            'friendly_name' => 'Auxiliares',
            'alias' => 'a',
            'columns' => [
                'a.num_doc_identidad' => ['friendly_name' => 'Num. Doc. Auxiliar', 'type' => 'string'],
                'a.razon_social_nombres' => ['friendly_name' => 'Razón Social/Nombres Auxiliar', 'type' => 'string'],
                'a.direccion' => ['friendly_name' => 'Dirección Auxiliar', 'type' => 'string'],
            ]
        ]
    ],
    'joins' => [
        'documentos_detalle' => [
            'documentos' => 'INNER JOIN documentos d ON dd.id_documento = d.id',
            'centros_costos' => 'INNER JOIN documento_detalle_distribucion ddd ON dd.id = ddd.id_documento_detalle INNER JOIN centros_costos cc ON ddd.id_centro_costo = cc.id',
            'conceptos' => 'LEFT JOIN conceptos c ON dd.id_concepto = c.id',
        ],
        'documentos' => [
            'tipos_documento' => 'LEFT JOIN tipos_documento td ON d.id_tipo_documento = td.id',
            'proyectos' => 'LEFT JOIN proyectos p ON d.id_proyecto = p.id',
            'sub_proyectos' => 'LEFT JOIN sub_proyectos sp ON d.id_sub_proyecto = sp.id',
            'auxiliares' => 'LEFT JOIN auxiliares a ON d.id_auxiliar = a.id',
        ]
    ]
];

return $reportingDictionary;

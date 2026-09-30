<?php

final class Modelos_Compras_Ordenes extends Modelo {
    protected $_db = null;

    // BD dedicada a órdenes de compra (mismo servidor MySQL que la BD principal)
    const DB_OC = 'saevalcas_db';

    // Estatus de oc_ordenes.status
    const ST_CANCELADA  = 0;
    const ST_GENERADA   = 1;
    const ST_REVISADA   = 2;   // se asigna sola al abrir la orden en la vista de visualizar
    const ST_AUTORIZADA = 3;

    // Debe coincidir con ISR_RESTA de procesar.js / visualizar.js / Modelos_Compras_Requisiciones
    const ISR_RESTA = false;

    // IDs de empleados que pueden autorizar órdenes. Vacío = cualquier usuario con sesión.
    const AUTORIZAN = array();

    public function iniciarDb($db) {
        if (!$this->_db) {
            $this->_db = $db;
        }
        $this->listadoEmp = array(1232,1238,1267,1305,1382,1387,1388,1389,1390,1400,1401);
        $this->listadoVerTodo = array(1455, 1572, 1591);

    }

    private function ejecutar($sql, array $params = array()) {
        $sth = $this->_db->prepare($sql);
        if (!$sth || !$sth->execute($params)) {
            $info = $sth ? $sth->errorInfo() : $this->_db->errorInfo();
            throw new RuntimeException('SQL: ' . ($info[2] ?? 'error desconocido'), (int) ($info[1] ?? 0));
        }
        return $sth;
    }

    public function getListado($context = null, $id_proveedor = null){
        $response = array();

        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS");
        header("Cache-Control:");

        try {
            // Cada pestaña del listado muestra las órdenes de un estatus
            $statusPorPestana = array(
                'generados'   => self::ST_GENERADA,
                'revisadas'   => self::ST_REVISADA,
                'autorizadas' => self::ST_AUTORIZADA,
            );
            if (!is_string($context) || !isset($statusPorPestana[$context])) {
                throw new InvalidArgumentException('Listado no válido.');
            }

            $db = '`' . self::DB_OC . '`';

            // Reservado para restringir la visibilidad por usuario / rol si se necesita
            $where = 'WHERE o.status = ' . (int) $statusPorPestana[$context];

            // Filtro opcional por proveedor (oc_ordenes.id_proveedor). Sin parámetro = todos los proveedores.
            if ($id_proveedor !== null && $id_proveedor !== '') {
                if (!ctype_digit((string) $id_proveedor) || (int) $id_proveedor <= 0) {
                    throw new InvalidArgumentException('Proveedor no válido.');
                }
                $where .= ' AND o.id_proveedor = ' . (int) $id_proveedor; // entero validado: seguro de interpolar
            }

            // BEGIN :: INIT PARAMS
                $PAGINATION = (isset($_POST['pagination']) && is_array($_POST['pagination'])) ? $_POST['pagination'] : array();
                $SORT       = (isset($_POST['sort']) && is_array($_POST['sort'])) ? $_POST['sort'] : array();
                $QUERY      = (isset($_POST['query']) && is_array($_POST['query'])) ? $_POST['query'] : array();
            // END :: INIT PARAMS

            // BEGIN :: BUSQUEDA (campo "Buscar...")
            // Busca por folio (principal o anidado), proveedor, unidad de negocio y centro de costo
                $BUSQUEDA = (isset($QUERY['generalSearch']) && is_string($QUERY['generalSearch']) && trim($QUERY['generalSearch']) !== '') ? trim($QUERY['generalSearch']) : null;
                if ($BUSQUEDA !== null) {
                    $texto = str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $BUSQUEDA);
                    $B = $this->_db->quote('%' . $texto . '%');
                    $where .= " AND (
                        CAST(o.folio AS CHAR) LIKE $B
                        OR o.proveedor_razon_social LIKE $B
                        OR EXISTS (SELECT 1 FROM oc_sub_ordenes s WHERE s.id_orden = o.id AND s.folio LIKE $B)
                        OR EXISTS (
                            SELECT 1 FROM oc_partidas p
                            JOIN centros_trabajo ct ON ct.id = p.id_unidad_negocio
                            LEFT JOIN centro_trabajo_areas cta ON cta.id_ct_area = p.id_area_centro_costo
                            WHERE p.id_orden = o.id
                            AND (CONVERT(ct.nombre USING utf8mb4) LIKE $B OR CONVERT(cta.nombre_area USING utf8mb4) LIKE $B)
                        )
                    )";
                }
            // END :: BUSQUEDA

            // BEGIN :: META PARAMS
                $globalTotal = (int) $this->ejecutar("SELECT COUNT(*) FROM oc_ordenes o $where")->fetchColumn();

                $paginaActual = max(1, (int) ($PAGINATION['page'] ?? 1));
                $rowsPorPagina = (int) ($PAGINATION['perpage'] ?? 10);
                if ($rowsPorPagina === -1) {
                    $rowsPorPagina = max($globalTotal, 1);
                } elseif ($rowsPorPagina < 1) {
                    $rowsPorPagina = 10;
                }
                $rowsPorPagina = min($rowsPorPagina, 5000);

                $totalPaginas = max(1, (int) ceil($globalTotal / $rowsPorPagina));
                if ($paginaActual > $totalPaginas) $paginaActual = $totalPaginas;

                $sentido = (isset($SORT['sort']) && strtolower($SORT['sort']) === 'asc') ? 'asc' : 'desc';
                $campo   = (isset($SORT['field']) && is_string($SORT['field'])) ? $SORT['field'] : 'folio';

                $meta = array(
                    'page' => $paginaActual,
                    'perpage' => $rowsPorPagina,
                    'pages' => $totalPaginas,
                    'total' => $globalTotal,
                    'desplazamiento' => ($paginaActual - 1) * $rowsPorPagina,
                    'sort' => $sentido,
                    'field' => $campo,
                );
            // END :: META PARAMS

            // BEGIN :: ORDER BY (lista blanca: nunca se interpola el campo recibido)
                $columnasOrden = array(
                    'folio'     => 'o.folio',
                    'proveedor' => 'o.proveedor_razon_social',
                    'total'     => 'o.total',
                    'fecha'     => 'o.fecha_creacion',
                    'fecha_revision'     => 'o.fecha_revision',
                    'fecha_autorizacion' => 'o.fecha_autorizacion',
                );
                $ORDER_BY = ' ORDER BY ' . ($columnasOrden[$campo] ?? 'o.folio') . ' ' . $sentido . ', o.id DESC';
            // END :: ORDER BY

            $LIMIT = 'LIMIT ' . (int) $meta['perpage'] . ' OFFSET ' . (int) $meta['desplazamiento'];

            // LISTADO: 1) órdenes de la página
            $ordenes = $this->ejecutar(
                "SELECT o.id, o.folio, o.proveedor_razon_social, o.total, o.tiene_sub_ordenes, o.total_sub_ordenes, o.fecha_creacion, o.status,
                        o.fecha_revision, o.fecha_autorizacion,
                        CONCAT(er.nombre, ' ', er.apellidos) AS revisada_por,
                        CONCAT(ea.nombre, ' ', ea.apellidos) AS autorizada_por
                 FROM oc_ordenes o
                 LEFT JOIN empleados er ON er.id = o.id_usuario_revisa
                 LEFT JOIN empleados ea ON ea.id = o.id_usuario_autoriza
                 $where
                 $ORDER_BY $LIMIT"
            )->fetchAll(PDO::FETCH_ASSOC);

            $data = array();

            if (!empty($ordenes)) {
                $ids = array();
                foreach ($ordenes as $o) $ids[] = (int) $o['id'];
                $in = implode(',', array_fill(0, count($ids), '?'));

                // 2) sub-órdenes (folios anidados) de esas órdenes
                $subsPorOrden = array();
                $subs = $this->ejecutar(
                    "SELECT id_orden, folio, id_unidad_negocio, unidad_negocio, total_partidas, total
                     FROM oc_sub_ordenes
                     WHERE id_orden IN ($in)
                     ORDER BY id_orden, consecutivo",
                    $ids
                )->fetchAll(PDO::FETCH_ASSOC);
                foreach ($subs as $s) $subsPorOrden[(int) $s['id_orden']][] = $s;

                // 3) partidas agrupadas por sub-orden / unidad de negocio / centro de costo
                $detallePorOrden = array();
                $detalle = $this->ejecutar(
                    "SELECT p.id_orden, p.id_sub_orden, p.folio_oc,
                            p.id_unidad_negocio, ct.nombre AS unidad_negocio,
                            p.id_area_centro_costo, cta.nombre_area,
                            COUNT(*) AS partidas, SUM(p.total) AS monto
                     FROM oc_partidas p
                     JOIN centros_trabajo ct ON ct.id = p.id_unidad_negocio
                     LEFT JOIN centro_trabajo_areas cta ON cta.id_ct_area = p.id_area_centro_costo
                     WHERE p.id_orden IN ($in)
                     GROUP BY p.id_orden, p.id_sub_orden, p.folio_oc, p.id_unidad_negocio, ct.nombre, p.id_area_centro_costo, cta.nombre_area
                     ORDER BY p.id_orden, p.id_sub_orden, cta.nombre_area",
                    $ids
                )->fetchAll(PDO::FETCH_ASSOC);
                foreach ($detalle as $d) $detallePorOrden[(int) $d['id_orden']][] = $d;

                foreach ($ordenes as $o) {
                    $idOrden = (int) $o['id'];
                    $subsOrden = $subsPorOrden[$idOrden] ?? array();
                    $detalleOrden = $detallePorOrden[$idOrden] ?? array();

                    // Sub-órdenes
                    $subOrdenes = array();
                    $folioPorUnidad = array();
                    foreach ($subsOrden as $s) {
                        $folioPorUnidad[(int) $s['id_unidad_negocio']] = $s['folio'];
                        $subOrdenes[] = array(
                            'folio' => $s['folio'],
                            'unidad_negocio' => $s['unidad_negocio'],
                            'total_partidas' => (int) $s['total_partidas'],
                            'total' => number_format((float) $s['total'], 2, '.', ','),
                        );
                    }

                    // Unidades de negocio y centros de costo
                    $unidades = array();
                    $centrosCosto = array();
                    $totalPartidas = 0;
                    foreach ($detalleOrden as $d) {
                        $idUnidad = (int) $d['id_unidad_negocio'];
                        if (!isset($unidades[$idUnidad])) {
                            $unidades[$idUnidad] = array(
                                'id' => $idUnidad,
                                'nombre' => $d['unidad_negocio'],
                                'folio' => $folioPorUnidad[$idUnidad] ?? null,
                                'partidas' => 0,
                                'monto' => 0.0,
                            );
                        }
                        $unidades[$idUnidad]['partidas'] += (int) $d['partidas'];
                        $unidades[$idUnidad]['monto'] += (float) $d['monto'];
                        $totalPartidas += (int) $d['partidas'];

                        $centrosCosto[] = array(
                            'id' => (int) $d['id_area_centro_costo'],
                            'nombre' => $d['nombre_area'] !== null && $d['nombre_area'] !== '' ? $d['nombre_area'] : 'Sin centro de costo',
                            'unidad_negocio' => $d['unidad_negocio'],
                            'folio' => $d['folio_oc'],
                            'partidas' => (int) $d['partidas'],
                            'monto' => number_format((float) $d['monto'], 2, '.', ','),
                        );
                    }
                    foreach ($unidades as $k => $u) {
                        $unidades[$k]['monto'] = number_format($u['monto'], 2, '.', ',');
                    }

                    $fechaTimeStamp = (new DateTime($o['fecha_creacion']))->getTimestamp();

                    $data[] = array(
                        'id' => $idOrden,
                        'folio' => (string) $o['folio'],
                        'proveedor' => $o['proveedor_razon_social'],
                        'total' => number_format((float) $o['total'], 2, '.', ','),
                        'tiene_sub_ordenes' => (int) $o['tiene_sub_ordenes'] === 1,
                        'total_sub_ordenes' => (int) $o['total_sub_ordenes'],
                        'total_partidas' => $totalPartidas,
                        'sub_ordenes' => $subOrdenes,
                        'unidades' => array_values($unidades),
                        'centros_costo' => $centrosCosto,
                        'status' => (int) $o['status'],
                        'status_texto' => $this->textoStatus($o['status']),
                        'revisada_por' => trim((string) $o['revisada_por']),
                        'fecha_revision' => $o['fecha_revision'] ? Modelos_Fecha::formatearFecha($o['fecha_revision']) : '',
                        'autorizada_por' => trim((string) $o['autorizada_por']),
                        'fecha_autorizacion' => $o['fecha_autorizacion'] ? Modelos_Fecha::formatearFecha($o['fecha_autorizacion']) : '',
                        'fecha' => Modelos_Fecha::formatearFecha($o['fecha_creacion']),
                        'fechaTimeStamp' => $fechaTimeStamp,
                    );
                }
            }

            $response = array('meta' => $meta, 'data' => $data);

        } catch (\Throwable $th) {
            if (!($th instanceof InvalidArgumentException)) {
                error_log('[Compras_Ordenes::getListado] ' . $th->getMessage());
            }
            $response = array(
                'type' => 'error',
                'msj' => ($th instanceof InvalidArgumentException) ? $th->getMessage() : 'No se pudo cargar el listado de órdenes de compra.',
                'data' => array(),
            );
        }
        return $response;
    }

    // =====================================================================
    //  VISUALIZAR / EDITAR / REVISAR / AUTORIZAR
    // =====================================================================

    private function textoStatus($status) {
        $mapa = array(
            self::ST_CANCELADA  => 'Cancelada',
            self::ST_GENERADA   => 'Generada',
            self::ST_REVISADA   => 'Revisada',
            self::ST_AUTORIZADA => 'Autorizada',
        );
        return $mapa[(int) $status] ?? 'Desconocido';
    }

    private function idUsuarioSesion() {
        $id = (int) ($_SESSION['login_id'] ?? 0);
        if ($id <= 0) throw new InvalidArgumentException('Tu sesión expiró. Vuelve a iniciar sesión.');
        return $id;
    }

    private function puedeAutorizar($idUsuario) {
        return empty(self::AUTORIZAN) || in_array((int) $idUsuario, self::AUTORIZAN, true);
    }

    private function respuestaError(\Throwable $th, $contexto, $mensajeGenerico) {
        if (!($th instanceof InvalidArgumentException)) {
            error_log('[Compras_Ordenes::' . $contexto . '] ' . $th->getMessage());
        }
        return array(
            'type' => 'error',
            'msj'  => ($th instanceof InvalidArgumentException) ? $th->getMessage() : $mensajeGenerico,
        );
    }

    /**
     * Generada (1) -> Revisada (2). Se llama al abrir la vista de la orden.
     * Es atómico e idempotente: solo cambia si sigue en "Generada"; una orden ya
     * revisada, autorizada o cancelada no se toca. Devuelve true si cambió.
     */
    public function marcarRevisada($idOrden) {
        try {
            $idUsuario = $this->idUsuarioSesion();
            $sth = $this->ejecutar(
                "UPDATE oc_ordenes
                 SET status = ?, id_usuario_revisa = ?, fecha_revision = NOW()
                 WHERE id = ? AND status = ?",
                array(self::ST_REVISADA, $idUsuario, (int) $idOrden, self::ST_GENERADA)
            );
            return $sth->rowCount() === 1;
        } catch (\Throwable $th) {
            error_log('[Compras_Ordenes::marcarRevisada] ' . $th->getMessage());
            return false;
        }
    }

    /**
     * Datos de la orden para la vista de visualizar / editar.
     */
    public function getOrden($idOrden) {
        header("Content-Type: application/json");
        try {
            $idOrden = (int) $idOrden;
            if ($idOrden <= 0) throw new InvalidArgumentException('Orden de compra no válida.');
            $idUsuario = $this->idUsuarioSesion();

            $o = $this->ejecutar(
                "SELECT o.id, o.folio, o.proveedor_razon_social, o.proveedor_rfc, o.dias_entrega, o.observaciones,
                        o.tiene_sub_ordenes, o.status, o.fecha_creacion, o.motivo_cancelacion,
                        o.fecha_revision, o.fecha_autorizacion,
                        CONCAT(er.nombre, ' ', er.apellidos) AS revisada_por,
                        CONCAT(ea.nombre, ' ', ea.apellidos) AS autorizada_por
                 FROM oc_ordenes o
                 LEFT JOIN empleados er ON er.id = o.id_usuario_revisa
                 LEFT JOIN empleados ea ON ea.id = o.id_usuario_autoriza
                 WHERE o.id = ?",
                array($idOrden)
            )->fetch(PDO::FETCH_ASSOC);
            if (!$o) throw new InvalidArgumentException('La orden de compra no existe.');

            $partidas = $this->ejecutar(
                "SELECT p.id, p.folio_oc, p.descripcion, p.cantidad, p.um, p.dias_entrega, p.precio_unitario,
                        p.id_unidad_negocio, ct.nombre AS unidad_negocio, p.id_area_centro_costo,
                        p.subtotal, p.descuento, p.iva, p.isr, p.ieps, p.total
                 FROM oc_partidas p
                 JOIN centros_trabajo ct ON ct.id = p.id_unidad_negocio
                 WHERE p.id_orden = ?
                 ORDER BY p.id_sub_orden, p.id",
                array($idOrden)
            )->fetchAll(PDO::FETCH_ASSOC);

            $status   = (int) $o['status'];
            $editable = ($status === self::ST_GENERADA || $status === self::ST_REVISADA);

            return array(
                'type' => 'success',
                'data' => array(
                    'orden' => array(
                        'id'                 => (int) $o['id'],
                        'folio'              => (string) $o['folio'],
                        'proveedor'          => $o['proveedor_razon_social'],
                        'proveedor_rfc'      => $o['proveedor_rfc'],
                        'dias_entrega'       => (int) $o['dias_entrega'],
                        'observaciones'      => (string) $o['observaciones'],
                        'tiene_sub_ordenes'  => (int) $o['tiene_sub_ordenes'] === 1,
                        'status'             => $status,
                        'status_texto'       => $this->textoStatus($status),
                        'fecha'              => Modelos_Fecha::formatearFecha($o['fecha_creacion']),
                        'revisada_por'       => trim((string) $o['revisada_por']),
                        'fecha_revision'     => $o['fecha_revision'] ? Modelos_Fecha::formatearFecha($o['fecha_revision']) : null,
                        'autorizada_por'     => trim((string) $o['autorizada_por']),
                        'fecha_autorizacion' => $o['fecha_autorizacion'] ? Modelos_Fecha::formatearFecha($o['fecha_autorizacion']) : null,
                        'motivo_cancelacion' => $o['motivo_cancelacion'],
                        'editable'           => $editable,
                        'puede_autorizar'    => ($status === self::ST_REVISADA && $this->puedeAutorizar($idUsuario)),
                    ),
                    'partidas' => $partidas,
                ),
            );
        } catch (\Throwable $th) {
            return $this->respuestaError($th, 'getOrden', 'No se pudo cargar la orden de compra.');
        }
    }

    // ---------- helpers de captura (misma validación que Modelos_Compras_Requisiciones) ----------

    private function leerMonto(array $post, $campo, $idPartida, $etiqueta, $decimales = 2) {
        $arr = (isset($post[$campo]) && is_array($post[$campo])) ? $post[$campo] : array();
        if (!isset($arr[$idPartida]) || !is_scalar($arr[$idPartida]) || trim((string) $arr[$idPartida]) === '') {
            return 0.0;
        }
        $v = trim((string) $arr[$idPartida]);
        if (!is_numeric($v) || (float) $v < 0 || (float) $v > 999999999999.99) {
            throw new InvalidArgumentException('Captura ' . $etiqueta . ' de la partida #' . $idPartida . ' con un número válido (mayor o igual a cero).');
        }
        return round((float) $v, $decimales);
    }

    private function leerDias(array $post, $idPartida) {
        $arr = (isset($post['dias_entrega_partida']) && is_array($post['dias_entrega_partida'])) ? $post['dias_entrega_partida'] : array();
        $dias = isset($arr[$idPartida]) ? filter_var($arr[$idPartida], FILTER_VALIDATE_INT) : false;
        if ($dias === false || $dias < 1 || $dias > 255) {
            throw new InvalidArgumentException('Los días de entrega de la partida #' . $idPartida . ' deben estar entre 1 y 255.');
        }
        return (int) $dias;
    }

    // importe = subtotal - descuento + IVA + IEPS (+/-) ISR, en centavos enteros
    private function importePartida($subtotal, $descuento, $iva, $isr, $ieps) {
        $c = function ($v) { return (int) round($v * 100); };
        $centavos = $c($subtotal) - $c($descuento) + $c($iva) + $c($ieps) + (self::ISR_RESTA ? -$c($isr) : $c($isr));
        return $centavos / 100;
    }

    /**
     * Guarda los cambios de una orden en estatus Generada / Revisada.
     *
     * Editable: observaciones y, por partida, valor unitario, I.V.A., I.S.R., I.E.P.S.,
     * descuento, días de entrega y centro de costo (dentro de su misma unidad de negocio).
     * NO cambia: proveedor, cantidades ni unidades de negocio (cambiarlas alteraría las
     * sub-órdenes). Subtotales e importes se recalculan aquí; el navegador no manda totales.
     */
    public function guardarOrden($idOrden, $post = array()) {
        header("Content-Type: application/json");
        $transaccion = false;

        try {
            $idOrden = (int) $idOrden;
            if ($idOrden <= 0) throw new InvalidArgumentException('Orden de compra no válida.');
            $this->idUsuarioSesion();

            $observaciones = trim((string) ($post['observaciones'] ?? ''));
            if (mb_strlen($observaciones) > 65000) {
                throw new InvalidArgumentException('Las observaciones son demasiado largas.');
            }

            $this->_db->beginTransaction();
            $transaccion = true;

            // Se bloquea la orden: no se edita mientras otro usuario la autoriza
            $o = $this->ejecutar("SELECT id, status FROM oc_ordenes WHERE id = ? FOR UPDATE", array($idOrden))->fetch(PDO::FETCH_ASSOC);
            if (!$o) throw new InvalidArgumentException('La orden de compra no existe.');
            $status = (int) $o['status'];
            if ($status === self::ST_AUTORIZADA) throw new InvalidArgumentException('La orden ya fue autorizada y no se puede modificar.');
            if ($status === self::ST_CANCELADA)  throw new InvalidArgumentException('La orden está cancelada y no se puede modificar.');

            $partidas = $this->ejecutar(
                "SELECT id, id_sub_orden, id_unidad_negocio, id_area_centro_costo, cantidad
                 FROM oc_partidas WHERE id_orden = ? FOR UPDATE",
                array($idOrden)
            )->fetchAll(PDO::FETCH_ASSOC);
            if (empty($partidas)) throw new InvalidArgumentException('La orden de compra no tiene partidas.');

            // Centros de costo elegidos: activos y de la misma unidad de negocio de la partida
            // (si no se cambió el actual no se revalida: pudo desactivarse después de generar la orden)
            $areasPost = (isset($post['id_area_centro_costo']) && is_array($post['id_area_centro_costo'])) ? $post['id_area_centro_costo'] : array();
            $areasNuevas = array();
            foreach ($partidas as $p) {
                $idArea = (isset($areasPost[$p['id']]) && is_scalar($areasPost[$p['id']])) ? (int) $areasPost[$p['id']] : 0;
                if ($idArea <= 0) throw new InvalidArgumentException('Selecciona el Centro de Costo de la partida #' . $p['id'] . '.');
                if ($idArea !== (int) $p['id_area_centro_costo']) $areasNuevas[] = $idArea;
            }
            $unidadDeArea = array();
            if (!empty($areasNuevas)) {
                $areasNuevas = array_values(array_unique($areasNuevas));
                $in = implode(',', array_fill(0, count($areasNuevas), '?'));
                $unidadDeArea = $this->ejecutar(
                    "SELECT id_ct_area, id_centro_trabajo FROM centro_trabajo_areas WHERE status_area = 1 AND id_ct_area IN ($in)",
                    $areasNuevas
                )->fetchAll(PDO::FETCH_KEY_PAIR);
            }

            $conceptos = array('subtotal', 'descuento', 'iva', 'isr', 'ieps', 'total');
            $porSub    = array();
            $totalOrden = array_fill_keys($conceptos, 0.0);
            $diasOrden = 1;

            foreach ($partidas as $p) {
                $id = (int) $p['id'];

                $idArea = (int) $areasPost[$id];
                if ($idArea !== (int) $p['id_area_centro_costo']
                    && (!isset($unidadDeArea[$idArea]) || (int) $unidadDeArea[$idArea] !== (int) $p['id_unidad_negocio'])) {
                    throw new InvalidArgumentException('Un Centro de Costo seleccionado no pertenece a la Unidad de Negocio de su partida.');
                }

                $precio = $this->leerMonto($post, 'precio_unitario', $id, 'el valor unitario', 4);
                if ($precio <= 0) throw new InvalidArgumentException('El valor unitario de la partida #' . $id . ' debe ser mayor a cero.');

                $desc = $this->leerMonto($post, 'descuento', $id, 'el descuento');
                $iva  = $this->leerMonto($post, 'iva', $id, 'el I.V.A.');
                $isr  = $this->leerMonto($post, 'isr', $id, 'el I.S.R.');
                $ieps = $this->leerMonto($post, 'ieps', $id, 'el I.E.P.S.');
                $dias = $this->leerDias($post, $id);

                $subtotal = round((float) $p['cantidad'] * $precio, 2);
                $total    = $this->importePartida($subtotal, $desc, $iva, $isr, $ieps);
                if ($total < 0) throw new InvalidArgumentException('El descuento de la partida #' . $id . ' no puede dejar el importe en negativo.');

                $this->ejecutar(
                    "UPDATE oc_partidas
                     SET dias_entrega = ?, precio_unitario = ?, id_area_centro_costo = ?,
                         subtotal = ?, descuento = ?, iva = ?, isr = ?, ieps = ?, total = ?
                     WHERE id = ? AND id_orden = ?",
                    array($dias, $precio, $idArea, $subtotal, $desc, $iva, $isr, $ieps, $total, $id, $idOrden)
                );

                $linea = array('subtotal' => $subtotal, 'descuento' => $desc, 'iva' => $iva, 'isr' => $isr, 'ieps' => $ieps, 'total' => $total);
                $idSub = $p['id_sub_orden'] !== null ? (int) $p['id_sub_orden'] : 0;
                if (!isset($porSub[$idSub])) $porSub[$idSub] = array_fill_keys($conceptos, 0.0);
                foreach ($conceptos as $k) {
                    $porSub[$idSub][$k] += $linea[$k];
                    $totalOrden[$k]     += $linea[$k];
                }
                $diasOrden = max($diasOrden, $dias);
            }

            // Sub-órdenes (si la orden las tiene)
            foreach ($porSub as $idSub => $t) {
                if ($idSub === 0) continue;
                $this->ejecutar(
                    "UPDATE oc_sub_ordenes
                     SET subtotal = ?, descuento = ?, iva = ?, isr = ?, ieps = ?, total = ?
                     WHERE id = ? AND id_orden = ?",
                    array(round($t['subtotal'], 2), round($t['descuento'], 2), round($t['iva'], 2), round($t['isr'], 2),
                          round($t['ieps'], 2), round($t['total'], 2), $idSub, $idOrden)
                );
            }

            // Orden principal
            foreach ($totalOrden as $k => $v) $totalOrden[$k] = round($v, 2);
            $this->ejecutar(
                "UPDATE oc_ordenes
                 SET observaciones = ?, dias_entrega = ?, subtotal = ?, descuento = ?, iva = ?, isr = ?, ieps = ?, total = ?
                 WHERE id = ?",
                array($observaciones !== '' ? $observaciones : null, $diasOrden, $totalOrden['subtotal'], $totalOrden['descuento'],
                      $totalOrden['iva'], $totalOrden['isr'], $totalOrden['ieps'], $totalOrden['total'], $idOrden)
            );

            $this->_db->commit();
            $transaccion = false;

            return array('type' => 'success', 'msj' => 'Cambios guardados correctamente.', 'data' => array('total' => number_format($totalOrden['total'], 2, '.', '')));

        } catch (\Throwable $th) {
            if ($transaccion && $this->_db->inTransaction()) $this->_db->rollBack();
            return $this->respuestaError($th, 'guardarOrden', 'No se pudieron guardar los cambios. Intenta nuevamente o contacta a soporte.');
        }
    }

    /**
     * Revisada (2) -> Autorizada (3). Solo desde Revisada y solo usuarios permitidos.
     */
    public function autorizarOrden($idOrden) {
        header("Content-Type: application/json");
        $transaccion = false;

        try {
            $idOrden = (int) $idOrden;
            if ($idOrden <= 0) throw new InvalidArgumentException('Orden de compra no válida.');
            $idUsuario = $this->idUsuarioSesion();

            if (!$this->puedeAutorizar($idUsuario)) {
                throw new InvalidArgumentException('No tienes permiso para autorizar órdenes de compra.');
            }

            $this->_db->beginTransaction();
            $transaccion = true;

            $o = $this->ejecutar("SELECT folio, status FROM oc_ordenes WHERE id = ? FOR UPDATE", array($idOrden))->fetch(PDO::FETCH_ASSOC);
            if (!$o) throw new InvalidArgumentException('La orden de compra no existe.');

            $status = (int) $o['status'];
            if ($status === self::ST_AUTORIZADA) throw new InvalidArgumentException('La orden ya estaba autorizada.');
            if ($status === self::ST_CANCELADA)  throw new InvalidArgumentException('La orden está cancelada y no se puede autorizar.');
            if ($status !== self::ST_REVISADA)   throw new InvalidArgumentException('La orden debe estar en estatus Revisada para autorizarse.');

            $this->ejecutar(
                "UPDATE oc_ordenes SET status = ?, id_usuario_autoriza = ?, fecha_autorizacion = NOW() WHERE id = ? AND status = ?",
                array(self::ST_AUTORIZADA, $idUsuario, $idOrden, self::ST_REVISADA)
            );

            $this->_db->commit();
            $transaccion = false;

            return array('type' => 'success', 'msj' => 'Orden de compra ' . $o['folio'] . ' autorizada.');

        } catch (\Throwable $th) {
            if ($transaccion && $this->_db->inTransaction()) $this->_db->rollBack();
            return $this->respuestaError($th, 'autorizarOrden', 'No se pudo autorizar la orden. Intenta nuevamente o contacta a soporte.');
        }
    }

    /**
     * Total de órdenes por pestaña del listado (una sola consulta).
     * Las claves coinciden con el nombre de cada pestaña en ordenes.js.
     */
    public function getIndicadores() {
        header("Content-Type: application/json");
        try {
            $filas = $this->ejecutar(
                "SELECT status, COUNT(*) FROM oc_ordenes WHERE status IN (?, ?, ?) AND id_proveedor = ? GROUP BY status",
                array(self::ST_GENERADA, self::ST_REVISADA, self::ST_AUTORIZADA, $_SESSION['login_id'])
            )->fetchAll(PDO::FETCH_KEY_PAIR);

            return array(
                'type' => 'success',
                'data' => array(
                    'generados'   => (int) ($filas[self::ST_GENERADA] ?? 0),
                    'revisadas'   => (int) ($filas[self::ST_REVISADA] ?? 0),
                    'autorizadas' => (int) ($filas[self::ST_AUTORIZADA] ?? 0),
                ),
            );
        } catch (\Throwable $th) {
            return $this->respuestaError($th, 'getIndicadores', 'No se pudieron cargar los indicadores.');
        }
    }

    // =====================================================================
    //  PDF DE LA ORDEN DE COMPRA
    // =====================================================================

    // Datos del emisor (encabezado del documento)
    const EMISOR_NOMBRE    = 'MANTENIMIENTO Y ADMINISTRACION PROFESIONAL';
    const EMISOR_RFC       = 'MAP941111HE2';
    const EMISOR_DOMICILIO = 'Manuel Doblado 2721-1001, Calete';
    const EMISOR_CIUDAD    = 'Tijuana, Baja California, México · C.P. 22044';

    // Ruta del logo (si no existe el archivo se omite sin romper el PDF)
    const LOGO_PDF = 'public/img/logo_grupo_valcas.png';

    /**
     * Obtiene TODOS los datos necesarios para imprimir una orden de compra.
     *
     * @param int|string $id  oc_ordenes.id  ó  folio de sub-orden ('20001-2').
     *                        Con un folio de sub-orden se imprime solo esa sub-orden.
     * @return array {orden, partidas, resumen, requisiciones, genera}
     * @throws InvalidArgumentException si la orden no existe
     */
    public function getDatosOrden($id) {
        $id = trim((string) $id);
        $subFolio = null;

        // Folio de sub-orden -> se resuelve la orden principal
        if (strpos($id, '-') !== false) {
            $row = $this->ejecutar("SELECT id_orden, folio FROM oc_sub_ordenes WHERE folio = ?", array($id))->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new InvalidArgumentException('La sub-orden ' . $id . ' no existe.');
            $idOrden = (int) $row['id_orden'];
            $subFolio = $row['folio'];
        } else {
            $idOrden = (int) $id;
        }
        if ($idOrden <= 0) throw new InvalidArgumentException('Orden de compra no válida.');

        // 1) Orden principal
        $o = $this->ejecutar(
            "SELECT id, folio, proveedor_razon_social, proveedor_rfc, dias_entrega, observaciones,
                    tiene_sub_ordenes, total_sub_ordenes, subtotal, descuento, iva, isr, ieps, retencion_iva, total,
                    status, id_usuario_crea, fecha_creacion, motivo_cancelacion
             FROM oc_ordenes WHERE id = ?",
            array($idOrden)
        )->fetch(PDO::FETCH_ASSOC);
        if (!$o) throw new InvalidArgumentException('La orden de compra no existe.');

        // 2) Sub-órdenes
        $subs = $this->ejecutar(
            "SELECT id, folio, unidad_negocio, total_partidas, subtotal, descuento, iva, isr, ieps, total
             FROM oc_sub_ordenes WHERE id_orden = ? ORDER BY consecutivo",
            array($idOrden)
        )->fetchAll(PDO::FETCH_ASSOC);
        $subsPorId = array();
        foreach ($subs as $s) $subsPorId[(int) $s['id']] = $s;

        $subSel = null;
        if ($subFolio !== null) {
            foreach ($subs as $s) if ($s['folio'] === $subFolio) $subSel = $s;
        }

        // 3) Partidas (solo las de la sub-orden si se pidió una)
        $params = array($idOrden);
        $filtro = '';
        if ($subSel) {
            $filtro = ' AND p.id_sub_orden = ?';
            $params[] = (int) $subSel['id'];
        }
        // oc_partidas guarda descuento y días de entrega por partida:
        //   total = subtotal - descuento + iva + ieps (+/-) isr   (ver ISR_RESTA en Modelos_Compras_Requisiciones)
        $partidas = $this->ejecutar(
            "SELECT p.id, p.id_sub_orden, p.folio_oc, p.id_requisicion, p.descripcion, p.cantidad, p.um,
                    p.precio_unitario, p.id_unidad_negocio, ct.nombre AS unidad_negocio,
                    p.id_area_centro_costo, cta.nombre_area AS centro_costo,
                    p.dias_entrega, p.subtotal, p.descuento, p.iva, p.isr, p.ieps, p.total
             FROM oc_partidas p
             JOIN centros_trabajo ct ON ct.id = p.id_unidad_negocio
             LEFT JOIN centro_trabajo_areas cta ON cta.id_ct_area = p.id_area_centro_costo
             WHERE p.id_orden = ? $filtro
             ORDER BY p.id_sub_orden, p.id",
            $params
        )->fetchAll(PDO::FETCH_ASSOC);
        if (empty($partidas)) throw new InvalidArgumentException('La orden de compra no tiene partidas.');

        // 4) Resumen por folio / unidad de negocio / centro de costo + totales por sub-orden
        $resumen = array();
        $totalSub = array();
        $idsReq = array();
        $unidades = array();
        foreach ($partidas as $p) {
            $cc = ($p['centro_costo'] !== null && $p['centro_costo'] !== '') ? $p['centro_costo'] : 'Sin centro de costo';
            $k = $p['folio_oc'] . '|' . $p['id_unidad_negocio'] . '|' . $p['id_area_centro_costo'];
            if (!isset($resumen[$k])) {
                $resumen[$k] = array('folio' => $p['folio_oc'], 'unidad_negocio' => $p['unidad_negocio'], 'centro_costo' => $cc, 'partidas' => 0, 'monto' => 0.0);
            }
            $resumen[$k]['partidas']++;
            $resumen[$k]['monto'] += (float) $p['total'];

            $ids = (int) $p['id_sub_orden'];
            $totalSub[$ids] = ($totalSub[$ids] ?? 0.0) + (float) $p['total'];

            $idsReq[(int) $p['id_requisicion']] = true;
            $unidades[$p['unidad_negocio']] = true;
        }

        // 5) Requisiciones de origen (REQ, departamento, solicitante, fecha)
        $requisiciones = array();
        if (!empty($idsReq)) {
            $ids = array_keys($idsReq);
            $in = implode(',', array_fill(0, count($ids), '?'));
            $requisiciones = $this->ejecutar(
                "SELECT r.id, d.nombre AS departamento, CONCAT(e.nombre, ' ', e.apellidos) AS solicitante,
                        MIN(rp.fecha_creacion) AS fecha
                 FROM requisiciones r
                 LEFT JOIN departamentos d ON d.id = r.id_departamento
                 LEFT JOIN empleados e ON e.id = r.id_usuario
                 LEFT JOIN requisiciones_partes rp ON rp.id_requisicion = r.id
                 WHERE r.id IN ($in)
                 GROUP BY r.id, d.nombre, e.nombre, e.apellidos
                 ORDER BY r.id",
                $ids
            )->fetchAll(PDO::FETCH_ASSOC);
        }

        // 6) Quien genera la orden
        $genera = $this->ejecutar("SELECT CONCAT(nombre, ' ', apellidos) FROM empleados WHERE id = ?", array((int) $o['id_usuario_crea']))->fetchColumn();

        // Importes: los de la sub-orden si se imprime solo una
        if ($subSel) {
            $imp = array(
                'subtotal' => $subSel['subtotal'], 'descuento' => $subSel['descuento'], 'iva' => $subSel['iva'], 'isr' => $subSel['isr'],
                'ieps' => $subSel['ieps'], 'retencion_iva' => 0, 'total' => $subSel['total'],
            );
            $folioDoc = $subSel['folio'];
        } else {
            $imp = array(
                'subtotal' => $o['subtotal'], 'descuento' => $o['descuento'], 'iva' => $o['iva'], 'isr' => $o['isr'],
                'ieps' => $o['ieps'], 'retencion_iva' => $o['retencion_iva'], 'total' => $o['total'],
            );
            $folioDoc = (string) $o['folio'];
        }

        return array(
            'orden' => array_merge(array(
                'id' => (int) $o['id'],
                'folio' => (string) $o['folio'],
                'folio_documento' => $folioDoc,
                'es_sub_orden' => (bool) $subSel,
                'proveedor' => $o['proveedor_razon_social'],
                'proveedor_rfc' => $o['proveedor_rfc'],
                'dias_entrega' => (int) $o['dias_entrega'],
                'observaciones' => $o['observaciones'],
                'cancelada' => ((int) $o['status'] === self::ST_CANCELADA),
                'motivo_cancelacion' => $o['motivo_cancelacion'],
                'fecha_creacion' => $o['fecha_creacion'],
                'agrupar_sub_ordenes' => (!$subSel && (int) $o['tiene_sub_ordenes'] === 1),
                'unidades_negocio' => array_keys($unidades),
            ), array_map('floatval', $imp)),
            'sub_ordenes' => $subsPorId,
            'total_por_sub' => $totalSub,
            'partidas' => $partidas,
            'resumen' => array_values($resumen),
            'requisiciones' => $requisiciones,
            'genera' => array('nombre' => $genera ?: '', 'fecha' => $o['fecha_creacion']),
        );
    }

    /**
     * Devuelve la lista de "documentos" a imprimir.
     * - Orden con más de una unidad de negocio / con sub-órdenes -> un documento por sub-orden
     *   (cada uno con su folio, partidas e importes propios).
     * - Cualquier otro caso (orden simple, o una sola sub-orden solicitada) -> un solo documento.
     */
    private function documentosPdf(array $datos) {
        $o = $datos['orden'];

        $dividir = !$o['es_sub_orden']
            && !empty($datos['sub_ordenes'])
            && ($o['agrupar_sub_ordenes'] || count($o['unidades_negocio']) > 1);

        if (!$dividir) return array($datos);

        $docs = array();
        foreach ($datos['sub_ordenes'] as $sub) {   // ya viene ordenado por consecutivo
            try {
                $docs[] = $this->getDatosOrden($sub['folio']);
            } catch (InvalidArgumentException $e) {
                // sub-orden sin partidas: no genera hoja
            }
        }
        return !empty($docs) ? $docs : array($datos);
    }

    /**
     * Genera el PDF (vertical, tamaño carta) de una orden de compra.
     * Uso: pdf(12) -> orden completa | pdf('20001-2') -> solo la sub-orden 20001-2
     * Si la orden tiene sub-órdenes / varias unidades de negocio, cada una sale en su propia hoja
     * con su folio (encabezado, pie de página y numeración de página independientes).
     */
    public function pdf($id){

        require_once(APP . 'plugins/mpdf/mpdf.php');

        try {
            $datos = $this->getDatosOrden($id);
            $o = $datos['orden'];
            $documentos = $this->documentosPdf($datos);

            $pdf = new mPDF('utf-8', 'letter');
            $pdf->SetFont('montserrat');
            $pdf->SetTitle('Orden de Compra - ' . $o['folio_documento']);

            if ($o['cancelada']) {
                $pdf->SetWatermarkText('CANCELADA');
                $pdf->showWatermarkText = true;
            }

            $pdf->WriteHTML($this->pdfEstilos(), 1);

            foreach ($documentos as $doc) {
                $od = $doc['orden'];

                // El encabezado se define ANTES de AddPage (aplica a las páginas nuevas)
                $pdf->SetHTMLHeader($this->pdfEncabezado($doc));

                // Cada documento inicia en hoja nueva y reinicia la numeración de página
                $pdf->AddPageByArray([
                    'margin-left'   => 12,
                    'margin-right'  => 12,
                    'margin-top'    => 52,   // alto reservado al encabezado (logo + folio + título)
                    'margin-header' => 12,
                    'margin-bottom' => 18,
                    'margin-footer' => 6,
                    'resetpagenum'  => 1,
                ]);

                // El pie se define DESPUÉS de AddPage: mPDF lo imprime al cerrar la página,
                // así la hoja anterior conserva su propio folio.
                $pdf->SetHTMLFooter($this->pdfPie($od['folio_documento']));

                $pdf->WriteHTML($this->pdfHtml($doc), 2);
            }

            $pdf->Output('OrdenDeCompra_' . $o['folio_documento'] . '.pdf', 'I');
        } catch (\Throwable $th) {
            if (!($th instanceof InvalidArgumentException)) {
                error_log('[Compras_Ordenes::pdf] ' . $th->getMessage());
            }
            if (!headers_sent()) {
                header('Content-Type: text/plain; charset=utf-8');
                http_response_code($th instanceof InvalidArgumentException ? 404 : 500);
            }
            echo ($th instanceof InvalidArgumentException) ? $th->getMessage() : 'No se pudo generar el PDF de la orden de compra.';
        }
    }

    // ---------------------------------------------------------------------
    //  Helpers de presentación
    // ---------------------------------------------------------------------

    private function h($s) {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }

    private function dinero($n) {
        return '$' . number_format((float) $n, 2, '.', ',');
    }

    /** 21/Sep/2026  (con $hora: 21/09/2026 - 03:09 PM) */
    private function fechaPdf($fecha, $hora = false) {
        if (empty($fecha) || strpos((string) $fecha, '0000-00-00') === 0) return '';
        $t = strtotime($fecha);
        if (!$t) return '';
        if ($hora) return date('d/m/Y - h:i A', $t);
        $meses = array('', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic');
        return date('d', $t) . '/' . $meses[(int) date('n', $t)] . '/' . date('Y', $t);
    }

    /** 21-SEP-26 (formato de las observaciones) */
    private function fechaObs($fecha) {
        if (empty($fecha) || strpos((string) $fecha, '0000-00-00') === 0) return '';
        $t = strtotime($fecha);
        if (!$t) return '';
        $meses = array('', 'ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC');
        return date('d', $t) . '-' . $meses[(int) date('n', $t)] . '-' . date('y', $t);
    }

    /** 18786.00 -> DIECIOCHO MIL SETECIENTOS OCHENTA Y SEIS PESOS 00/100 M.N. */
    private function importeConLetra($monto) {
        $monto = round((float) $monto, 2);
        $entero = (int) floor($monto);
        $cent = (int) round(($monto - $entero) * 100);
        if ($cent === 100) { $entero++; $cent = 0; }

        $txt = ($entero === 0) ? 'CERO' : $this->numeroALetras($entero);
        if ($entero >= 1000000 && $entero % 1000000 === 0) $txt .= ' DE';
        $moneda = ($entero === 1) ? 'PESO' : 'PESOS';

        return $txt . ' ' . $moneda . ' ' . sprintf('%02d', $cent) . '/100 M.N.';
    }

    private function numeroALetras($n) {
        $millones = intdiv($n, 1000000);
        $resto    = $n % 1000000;
        $miles    = intdiv($resto, 1000);
        $cientos  = $resto % 1000;

        $partes = array();
        if ($millones > 0) $partes[] = ($millones === 1) ? 'UN MILLÓN' : $this->menorMil($millones) . ' MILLONES';
        if ($miles > 0)    $partes[] = ($miles === 1) ? 'MIL' : $this->menorMil($miles) . ' MIL';
        if ($cientos > 0)  $partes[] = $this->menorMil($cientos);
        return implode(' ', $partes);
    }

    private function menorMil($n) {
        $u = array('', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE',
                   'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE', 'VEINTIÚN', 'VEINTIDÓS', 'VEINTITRÉS',
                   'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE');
        $d = array(3 => 'TREINTA', 4 => 'CUARENTA', 5 => 'CINCUENTA', 6 => 'SESENTA', 7 => 'SETENTA', 8 => 'OCHENTA', 9 => 'NOVENTA');
        $c = array(1 => 'CIENTO', 2 => 'DOSCIENTOS', 3 => 'TRESCIENTOS', 4 => 'CUATROCIENTOS', 5 => 'QUINIENTOS',
                   6 => 'SEISCIENTOS', 7 => 'SETECIENTOS', 8 => 'OCHOCIENTOS', 9 => 'NOVECIENTOS');

        if ($n === 100) return 'CIEN';
        $t = '';
        if ($n >= 100) {
            $t = $c[intdiv($n, 100)];
            $n %= 100;
            if ($n > 0) $t .= ' ';
        }
        if ($n < 30) {
            $t .= $u[$n];
        } else {
            $t .= $d[intdiv($n, 10)];
            if ($n % 10 > 0) $t .= ' Y ' . $u[$n % 10];
        }
        return $t;
    }

    // ---------------------------------------------------------------------
    //  Estilos y HTML (mismo look de la Requisición Interna)
    //  Azul oscuro #00436c (etiquetas) · azul #0573ba (encabezado de tabla) · gris #dddcdd (separadores)
    // ---------------------------------------------------------------------

    private function pdfEstilos() {
        return '
            body { font-family: montserrat; font-size: 8.5pt; color: #000000; }
            table { border-collapse: collapse; }
            .lbl  { background-color: #00436c; color: #ffffff; font-weight: bold; text-align: center; padding: 4px 3px; border: 1px solid #ffffff; font-size: 8.5pt; }
            .val  { text-align: center; padding: 5px 3px; font-size: 8.5pt; }
            .th   { background-color: #0573ba; color: #ffffff; font-weight: bold; text-align: center; padding: 5px 3px; border: 1px solid #ffffff; font-size: 7.5pt; }
            .td   { padding: 5px 3px; font-size: 8pt; border-bottom: 1px solid #dddcdd; vertical-align: top; }
            .band { background-color: #dddcdd; font-weight: bold; padding: 4px 4px; font-size: 8pt; }
            .r    { text-align: right; }
            .c    { text-align: center; }
            .tit  { font-size: 15pt; font-weight: bold; color: #00436c; }
            .emi  { font-size: 13pt; font-weight: bold; }
            .small{ font-size: 7.5pt; color: #444444; }
            .tot-l{ text-align: right; padding: 3px 6px; font-size: 8.5pt; }
            .tot-v{ text-align: right; padding: 3px 4px; font-size: 8.5pt; width: 40%; }
            .firma-l { border-top: 1px solid #444444; text-align: center; padding-top: 3px; font-size: 8pt; }
        ';
    }

    /** Pie de página con folio y paginación. */
    private function pdfPie($folio) {
        $folio = $this->h($folio);
        return <<<HTML
<table width="100%" style="font-size:7pt;color:#444;border-top:1px solid #dddcdd;">
    <tr>
        <td width="70%">Orden de Compra {$folio}</td>
        <td width="30%" align="right">Página {PAGENO} de {nb}</td>
    </tr>
</table>
HTML;
    }

    /**
     * Encabezado que se repite en cada página (mPDF SetHTMLHeader).
     * Izquierda: logo · Centro: folio, fecha y usuario · Derecha: QR · Debajo: título.
     */
    private function pdfEncabezado(array $d) {
        $o = $d['orden'];

        $folio = $this->h($o['folio_documento']);
        $fecha = $this->h($this->fechaPdf($o['fecha_creacion']));
        $user  = $this->h($d['genera']['nombre']);

        $qrCode = Modelos_QR::generateQRCodeImage(STASIS.'/compras/ordenes/visualizar/'.$o['id'], 90);

        // QR con el folio (mPDF: <barcode type="QR">). Puede llevar una URL si se prefiere.

        $logo = "<img style=\"height:60px;\" src=\"".STASIS."/img/gvalcas.png\" />";

        return <<<HTML
<table style="font-size: 10px; vertical-align: center;" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width: 180px; color: #444; text-align: left;">{$logo}</td>
        <td style="width: 380px; color: #444; text-align: right;">
            <br /><br /><br />
            <span style="font-size: 12px;">Folio: <strong>{$folio}</strong><br />Fecha: {$fecha}</span><br />
            <span style="font-size: 12px;">Generado Por: {$user}</span>
        </td>
        <td style="width: 90px;">{$qrCode}</td>
    </tr>
</table>
<br />
<table style="text-align: center;" cellpadding="2" cellspacing="0" width="100%">
    <tr>
        <td style="width: 100%">
            <h1 style="text-align: center; font-family: 'SanFranciscoBold';">ORDEN DE COMPRA</h1>
        </td>
    </tr>
</table>
HTML;
    }

    // ---------------------------------------------------------------------
    //  Piezas del cuerpo (cada una devuelve filas <tr> ya listas)
    // ---------------------------------------------------------------------

    private function filasPartidas(array $d) {
        $o = $d['orden'];
        $rows = '';
        $subActual = false;

        foreach ($d['partidas'] as $p) {
            $idSub = (int) $p['id_sub_orden'];

            // Banda por sub-orden cuando la orden tiene más de una unidad de negocio
            if ($o['agrupar_sub_ordenes'] && $subActual !== $idSub) {
                $subActual = $idSub;
                $folio  = $this->h($p['folio_oc']);
                $unidad = $this->h($p['unidad_negocio']);
                $total  = $this->dinero(isset($d['sub_ordenes'][$idSub]) ? $d['sub_ordenes'][$idSub]['total'] : ($d['total_por_sub'][$idSub] ?? 0));
                $rows .= <<<HTML
<tr>
    <td class="band" colspan="7">Sub-orden {$folio} &middot; {$unidad}</td>
    <td class="band r" colspan="3">Total: {$total}</td>
</tr>
HTML;
            }

            $cant   = number_format((float) $p['cantidad'], 2, '.', ',');
            $um     = $this->h($p['um']);
            $desc   = nl2br($this->h($p['descripcion']));
            $req    = (int) $p['id_requisicion'];
            $cc     = $this->h($p['centro_costo'] !== null && $p['centro_costo'] !== '' ? $p['centro_costo'] : 'Sin centro de costo');
            $unit   = $this->dinero($p['precio_unitario']);
            $import = $this->dinero($p['total']);   // importe = cant x unit. - desc. + IVA + IEPS (+/-) ISR
            $desc_m = $this->dinero($p['descuento'] ?? 0);
            $iva_m  = $this->dinero($p['iva'] ?? 0);
            $isr_m  = $this->dinero($p['isr'] ?? 0);

            $rows .= <<<HTML
<tr>
    <td class="td c">{$cant}</td>
    <td class="td c">{$um}</td>
    <td class="td">{$desc}</td>
    <td class="td c">{$req}</td>
    <td class="td">{$cc}</td>
    <td class="td r">{$unit}</td>
    <td class="td r">{$desc_m}</td>
    <td class="td r">{$iva_m}</td>
    <td class="td r">{$isr_m}</td>
    <td class="td r">{$import}</td>
</tr>
HTML;
        }
        return $rows;
    }

    private function filasResumen(array $resumen) {
        $rows = '';
        foreach ($resumen as $r) {
            $folio = $this->h($r['folio']);
            $un    = $this->h($r['unidad_negocio']);
            $cc    = $this->h($r['centro_costo']);
            $part  = (int) $r['partidas'];
            $monto = $this->dinero($r['monto']);
            $rows .= "<tr><td class=\"td c\">{$folio}</td><td class=\"td\">{$un}</td><td class=\"td\">{$cc}</td><td class=\"td c\">{$part}</td><td class=\"td r\">{$monto}</td></tr>";
        }
        return $rows;
    }

    private function filasTotales(array $o) {
        $conceptos = array(
            'Subtotal'      => $o['subtotal'],
            'Descuentos'    => $o['descuento'],
            'I.V.A.'        => $o['iva'],
            'I.E.P.S.'      => $o['ieps'],   // solo se muestra si es > 0
            'I.S.R.'        => $o['isr'],
            'Retención IVA' => $o['retencion_iva'],
        );

        $rows = '';
        foreach ($conceptos as $nombre => $monto) {
            if ($nombre === 'I.E.P.S.' && $monto <= 0) continue;
            $valor = $this->dinero($monto);
            $rows .= "<tr><td class=\"tot-l\">{$nombre}:</td><td class=\"tot-v\">{$valor}</td></tr>";
        }
        return $rows;
    }

    private function textoObservaciones(array $d) {
        $o = $d['orden'];
        $txt = '';
        if (!empty($o['observaciones'])) $txt = '' . nl2br($this->h($o['observaciones']));
        if ($o['cancelada'] && !empty($o['motivo_cancelacion'])) $txt .= '<br><b>CANCELADA:</b> ' . $this->h($o['motivo_cancelacion']);

        return $txt;
    }

    // ---------------------------------------------------------------------
    //  Cuerpo del documento
    // ---------------------------------------------------------------------

    private function pdfHtml(array $d) {
        $o = $d['orden'];

        // Valores ya escapados / formateados para la plantilla
        $fecha      = $this->h($this->fechaPdf($o['fecha_creacion']));
        $folio      = $this->h($o['folio_documento']);
        $proveedor  = $this->h($o['proveedor']);
        $rfc        = $this->h($o['proveedor_rfc']);
        $unidades   = $this->h(implode(', ', $o['unidades_negocio']));
        $dias       = (int) $o['dias_entrega'];
        $etiquetaN  = $o['es_sub_orden'] ? 'Orden principal:' : 'Productos/Servicios:';
        $valorN     = $o['es_sub_orden'] ? $this->h($o['folio']) : count($d['partidas']);

        $partidas   = $this->filasPartidas($d);
        $totales    = $this->filasTotales($o);
        $total      = $this->dinero($o['total']);
        $observ     = $this->textoObservaciones($d);
        $conLetra   = $this->h($this->importeConLetra($o['total']));
        $resumen    = $this->filasResumen($d['resumen']);

        $genera = $d['genera']['nombre'] !== ''
            ? $this->h($d['genera']['nombre']) . '<br>' . $this->h($this->fechaPdf($d['genera']['fecha'], true))
            : '&nbsp;<br>&nbsp;';

        return <<<HTML
<table width="100%">
    <tr>
        <td class="lbl" width="18%">Fecha:</td>
        <td class="lbl" width="40%">Proveedor:</td>
        <td class="lbl" width="17%">RFC:</td>
    </tr>
    <tr>
        <td class="val">{$fecha}</td>
        <td class="val">{$proveedor}</td>
        <td class="val">{$rfc}</td>
    </tr>
</table>
<table width="100%">
    <tr>
        <td class="lbl" width="58%">Unidad de Negocio:</td>
        <td class="lbl" width="24%">{$etiquetaN}</td>
    </tr>
    <tr>
        <td class="val">{$unidades}</td>
        <td class="val">{$valorN}</td>
    </tr>
</table>
<br>

<table width="100%" repeat_header="1">
    <thead>
        <tr>
            <td class="th" width="7%">Cantidad</td>
            <td class="th" width="6%">UM</td>
            <td class="th" width="21%">Descripción</td>
            <td class="th" width="6%">Req.</td>
            <td class="th" width="12%">Centro de Costo</td>
            <td class="th" width="11%">Valor unit.</td>
            <td class="th" width="9%">Desc.</td>
            <td class="th" width="9%">IVA</td>
            <td class="th" width="9%">ISR</td>
            <td class="th" width="10%">Importe</td>
        </tr>
    </thead>
    <tbody>{$partidas}</tbody>
</table>
<br>

<table width="100%">
    <tr>
        <td width="56%" valign="top">
            <table width="100%">
                <tr><td class="" style="border-bottom: 1px solid #000;"><h3>&nbsp;Observaciones:</h3></td></tr>
                <tr><td style="padding:5px 4px;font-size:8pt;">{$observ}</td></tr>
            </table>
        </td>
        <td width="4%"></td>
        <td width="40%" valign="top">
            <table width="100%">
                {$totales}
                <tr>
                    <td class="tot-l" style="background-color:#00436c;color:#ffffff;font-weight:bold;">Total:</td>
                    <td class="tot-v" style="background-color:#00436c;color:#ffffff;font-weight:bold;">{$total}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>

<table width="100%">
    <tr><td class="lbl" style="text-align:left;">Importe con letra</td></tr>
    <tr><td style="padding:5px 4px;font-size:8.5pt;">{$conLetra}</td></tr>
</table>
HTML;
    }
}
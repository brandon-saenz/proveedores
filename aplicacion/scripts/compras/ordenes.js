var content = {
    UI_Template: false,
    // indicadores: indicadores,
    tabs: [
        // {
        //     name: 'generados', 
        //     title: 'Generados', 
        //     total: 0, 
        //     class: 'success', 
        //     icon: '<i class="fa fa-clock"></i>', 
        //     current: true,
        // },
        // {
        //     name: 'revisadas', 
        //     title: 'Revisadas', 
        //     total: 0, 
        //     class: 'info', 
        //     icon: '<i class="fa fa-eye"></i>', 
        //     current: false,
        // },
        {
            name: 'autorizadas', 
            title: 'Total&nbsp;', 
            total: 0, 
            class: 'primary', 
            icon: '<i class="fa fa-check"></i>', 
            current: true,
        },
    ]
};

// Base de los endpoints JSON de órdenes (mismo controlador Compras::ordenes)
var URL_OC_API = 'https://saevalcas.mx/proveedores/compras/ordenes/';

// Debe coincidir con Modelos_Compras_Ordenes::REQUIERE_PAGO_COMPLEMENTO (backend).
// true: el complemento solo se carga con la factura pagada; false: también con la factura pendiente de pago.
// (Aquí solo decide si el menú muestra la opción; el modal y el servidor validan con el valor del backend.)
var REQUIERE_PAGO_COMPLEMENTO = false;

document.addEventListener('DOMContentLoaded', function () {
    setterData();
    initModalFactura();
});

function escapeHtml(valor) {
    return String(valor ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Dropdown con la estructura estándar del sistema (dropdown-inline + navi).
 *   etiqueta : texto del botón (ya escapado)
 *   titulo   : encabezado del menú
 *   items    : [{icono, texto (HTML ya escapado), monto (opcional)}]
 *   pie      : {texto, monto} fila de total opcional
 */
function dropdownOC(etiqueta, titulo, items, pie){
    var lis = items.map(function(it){
        return '<li class="navi-item">' +
            '<span class="navi-link">' +
                '<span class="navi-icon"><i class="' + it.icono + '"></i></span>' +
                '<span class="navi-text">' + it.texto + '</span>' +
                (it.monto ? '<span class="navi-label ml-3"><span class="label label-light-primary label-inline font-weight-bold">' + it.monto + '</span></span>' : '') +
            '</span>' +
        '</li>';
    }).join('');

    var footer = pie
        ? '<li class="navi-separator my-2"></li>' +
          '<li class="navi-item"><span class="navi-link">' +
              '<span class="navi-text font-weight-bolder">' + pie.texto + '</span>' +
              '<span class="navi-label ml-3"><span class="label label-primary label-inline font-weight-bold">' + pie.monto + '</span></span>' +
          '</span></li>'
        : '';

    return '<div class="dropdown dropdown-inline">' +
        '<button class="btn btn-primary font-weight-bold btn-sm dropdown-toggle" type="right" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="padding-block: 0.3em; padding-inline: 0.6em;">' +
            '<span class="d-inline-block text-truncate align-middle" style="max-width: 170px;">' + etiqueta + '</span>' +
        '</button>' +
        '<div class="dropdown-menu dropdown-menu-md dropdown-menu-right">' +
            '<ul class="navi navi-hover">' +
                '<li class="navi-header font-weight-bolder text-uppercase font-size-sm text-primary pb-2">' + titulo + '</li>' +
                lis + footer +
            '</ul>' +
        '</div>' +
    '</div>';
}

// Actualiza el contador de la pestaña con el total real que devuelve el backend
function actualizarTotalTab(name, total){
    content.tabs.forEach(function(item){
        if (item.name !== name) return;
        item.total = total;

        var label = document.querySelector('#navTabsDesktop a[href="#' + name + '"] .label');
        if (label) label.textContent = total;

        var p = document.querySelector('.btnDropdownTab p');
        if (p && item.current) p.innerHTML = item.title + ' (' + total + ')';
    });
}

function renderTabs(){
    var navDesktop = document.getElementById('navTabsDesktop');
    var navMobileList = document.getElementById('navTabsMobileList');
    var tabsContent = document.getElementById('tabsContent');

    if (!navDesktop || !navMobileList || !tabsContent) return;

    var htmlDesktop = '';
    var htmlMobile = '';
    var htmlPanes = '';

    var tplAutorizadas = document.getElementById('tpl-autorizadas-actions');
    var htmlAutorizadas = tplAutorizadas ? tplAutorizadas.innerHTML : '';

    content.tabs.forEach(function(item){
        // Pestañas de escritorio
        htmlDesktop += '<li class="nav-item">' +
            '<a class="' + (item.current ? 'nav-link active' : 'nav-link') + '" data-toggle="tab" href="#' + item.name + '" onclick="setTab(\'' + item.name + '\')">' +
                '<span class="nav-icon">' + item.icon + '</span>' +
                '<span class="nav-text">' + item.title + '</span>' +
                '<span class="label label-rounded ml1 text-white label-' + item.class + '" style="width: 40px;">' + item.total + '</span>' +
            '</a>' +
        '</li>';

        // Lista de pestañas en dropdown móvil
        htmlMobile += '<li class="navi-item">' +
            '<a class="' + (item.current ? 'navi-link pointer btn-light-' + item.class : 'navi-link pointer') + '" onclick="setTab(\'' + item.name + '\')">' +
                '<span class="' + (item.current ? 'navi-text text-' + item.class : 'navi-text') + '"><strong class="text-uppercase">' + item.title + '</strong></span>' +
            '</a>' +
        '</li>';

        // Panel de contenido de cada pestaña
        htmlPanes += '<div class="' + (item.current ? 'tab-pane fade show active' : 'tab-pane fade show') + '" id="' + item.name + '" role="tabpanel" aria-labelledby="' + item.name + '">' +
            (item.name === 'autorizadas' ? htmlAutorizadas : '') +
            '<div class="mt-3 mb-3 row align-items-center">' +
                '<div class="col-12">' +
                    '<div class="row align-items-center">' +
                        '<div class="col-xxl-3 col-xl-4 col-lg-5 col-md-6 col-sm-8 my-2 my-md-0">' +
                            '<div class="input-icon">' +
                                '<input type="text" class="form-control" placeholder="Buscar..." id="buscador_' + item.name + '" />' +
                                '<span><i class="flaticon2-search-1 text-muted"></i></span>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="table-responsive" style="overflow-x: auto;">' +
                '<div class="datatable datatable-bordered datatable-head-custom" style="min-width: 1200px;" id="datatable_' + item.name + '"></div>' +
            '</div>' +
        '</div>';
    });

    navDesktop.innerHTML = htmlDesktop;
    navMobileList.innerHTML = htmlMobile;
    tabsContent.innerHTML = htmlPanes;
}

// Trae el total de TODAS las pestañas de una vez para mostrarlos desde el inicio
function cargarIndicadores(){
    $.ajax({
        type: 'GET',
        dataType: 'json',
        url: 'https://saevalcas.mx/proveedores/compras/ordenes/get_indicadores',
        success: function(res){
            if (!res || res.type !== 'success' || !res.data) return;
            Object.keys(res.data).forEach(function(name){
                actualizarTotalTab(name, parseInt(res.data[name], 10) || 0);
            });
        },
        error: function(xhr){
            console.error('Error al cargar los indicadores de las pestañas:', xhr.status);
        }
    });
}

function setterData(){
    renderTabs();
    cargarIndicadores();

    var inicializarKT = function(){
        if (typeof jQuery.fn.dataTable !== 'undefined') {
            setTab('autorizadas');
            clearInterval(intervalID);
        }
    }
    var intervalID = setInterval(inicializarKT, 100);
}

function setTab(name){

    content.tabs.forEach(item => {
        if(item.name==name){
            item.current = true;

            var dropdownTab = document.querySelector('.btnDropdownTab');
            dropdownTab.className = 'btnDropdownTab btn btn-clean btn-light-'+item.class+' btn-hover-'+item.class+' btn-sm';
            dropdownTab.querySelector('p').innerHTML = item.title+' ('+item.total+')';
            dropdownTab.querySelector('i').outerHTML = item.icon;
            dropdownTab.querySelector('i').classList.add('sfs-11');
            dropdownTab.querySelector('i').style = 'margin-right: -0.22em; margin-left: 0.2em; margin-block: auto;';

            createDataTable(name);
            if (name === 'autorizadas' && typeof actualizarAccionesMultiples === 'function') {
                setTimeout(actualizarAccionesMultiples, 150);
            }
        }else{
            item.current = false;
        }
    });
}

function createDataTable(step){
    
    setTimeout(() => {
        datatable = $('#datatable_'+step).KTDatatable({
            data: {
                type: 'remote',
                source: {
                    read: {
                        url: 'https://saevalcas.mx/compras/ordenes/get_listado/'+step+'/'+idProveedor,
                        map: function(raw) {
                            // sample data mapping
                            var dataSet = raw;
                            // control_acceso.indicadores = dataSet.indicadores;
                            // El contador de la pestaña lo maneja cargarIndicadores() (total global,
                            // no el filtrado por la búsqueda)
                            if (typeof raw.data !== 'undefined') {
                                dataSet = raw.data;
                            }
                            return dataSet;
                        },
                    }
                },
                pageSize: 10,
                serverPaging: true,
                serverFiltering: true,
                serverSorting: true,
            },
            layout: {
                scroll: true,
                footer: false,
            },
            sortable: true,
            pagination: true,
            toolbar: {
                layout: ['info', 'pagination'],
                placement: ['top', 'bottom'],
                items: {
                    pagination: {
                        type: 'default',
                        pages: {
                            desktop: {
                                layout: 'default',
                                pagesNumber: 5,
                            },
                            tablet: {
                                layout: 'default',
                                pagesNumber: 3,
                            },
                            mobile: {
                                layout: 'compact',
                            },
                        },
                        navigation: {
                            prev: true,
                            next: true,
                            first: true,
                            last: true,
                            more: true
                        },
                        pageSizeSelect: [5, 10, 20, 50, 100, 200, 500],
                    },
                    info: true,
                },
            },
            search: {
                input: $('#buscador_'+step),
                key: 'generalSearch'
            },
            columns: generarColumnas(step),
        }).on('datatable-on-ajax-done', function() {

        });
    }, 250);
}

function generarColumnas(step){
    var columns = [];
    // Mismas columnas base en las tres pestañas; revisadas / autorizadas agregan quién y cuándo
    var base = [
        generarCelda('folio'),
        generarCelda('proveedor'),
        generarCelda('unidad_negocio'),
        generarCelda('total'),
        generarCelda('centro_costo'),
        generarCelda('fecha'),
        generarCelda('factura'),
    ];

    switch (step) {
        case 'generados':
            columns = base.concat([generarCelda('acciones')]);
        break;
        case 'revisadas':
            columns = base.concat([generarCelda('revision'), generarCelda('acciones')]);
        break;
        case 'autorizadas':
            columns = base.concat([generarCelda('revision'), generarCelda('autorizacion'), generarCelda('acciones')]);
        break;
    }
    
    return columns;
}

function generarCelda(field){
    var celda = {};
    switch (field) {
        case 'folio':
            celda = {
                field: 'folio',
                title: 'FOLIO',
                sortable: 'desc',
                width: 140,
                textAlign: 'center',
                type: 'number',
                template: function(row){
                    var html = '<div class="d-flex flex-column align-items-center py-2"><a href="'+STASIS+'/compras/ordenes/visualizar/' + escapeHtml(row.id) + '" target="_blank"><strong>' + escapeHtml(row.folio) + '</strong></a>';

                    // Orden con folios anidados: 20001-1, 20001-2...
                    if (row.tiene_sub_ordenes && row.sub_ordenes && row.sub_ordenes.length) {
                        var items = row.sub_ordenes.map(function(s){
                            return {
                                icono: 'las la-file-invoice',
                                texto: '<strong>' + escapeHtml(s.folio) + '</strong>',
                                monto: '$' + escapeHtml(s.total)
                            };
                        });
                        html += '<div class="mt-1">' + dropdownOC(row.sub_ordenes.length + ' órdenes', 'Órdenes anidadas:', items) + '</div>';
                    }

                    return html + '</div>';
                }
            };
        break;
        case 'proveedor':
            celda = {
                field: 'proveedor',
                title: 'Proveedor',
                sortable: 'desc',
                width: 240,
                textAlign: 'center',
                type: 'text',
                template: function(row){
                    return escapeHtml(row.proveedor);
                }
            };
        break;
        case 'unidad_negocio':
            celda = {
                field: 'unidad_negocio',
                title: 'Unidad de Negocio',
                sortable: false,
                width: 200,
                textAlign: 'center',
                template: function(row){
                    var unidades = row.unidades || [];

                    if (!unidades.length) return '-';
                    if (unidades.length === 1) return escapeHtml(unidades[0].nombre);

                    var items = unidades.map(function(u){
                        return {
                            icono: 'las la-building',
                            texto: escapeHtml(u.nombre),
                            monto: '$' + escapeHtml(u.monto)
                        };
                    });
                    return dropdownOC(unidades.length + ' unidades', 'Unidades de negocio:', items);
                }
            };
        break;
        case 'centro_costo':
            celda = {
                field: 'centro_costo',
                title: 'Centro de Costo',
                sortable: false,
                width: 220,
                textAlign: 'center',
                template: function(row){
                    var centros = row.centros_costo || [];
                    if (!centros.length) return '-';

                    var items = centros.map(function(c){
                        return {
                            icono: 'las la-coins',
                            texto: escapeHtml(c.nombre),
                            monto: '$' + escapeHtml(c.monto)
                        };
                    });
                    var etiqueta = centros.length === 1 ? escapeHtml(centros[0].nombre) : centros.length + ' centros de costo';

                    return dropdownOC(etiqueta, 'Centros de costo | monto:', items, { texto: 'Total', monto: '$' + escapeHtml(row.total) });
                }
            };
        break;
        case 'total':
            celda = {
                field: 'total',
                title: 'Monto Total',
                sortable: 'desc',
                width: 120,
                textAlign: 'center',
                type: 'text',
                template: function(row){
                    return `$${row.total}`;
                }
            };
        break;
        case 'fecha':
            celda = {
                field: 'fecha',
                title: 'Fecha de Creación',
                sortable: 'desc',
                width: 100,
                textAlign: 'center',
                type: 'text',
            };
        break;
        case 'revision':
            celda = {
                field: 'fecha_revision',
                title: 'Revisada',
                sortable: 'desc',
                width: 150,
                textAlign: 'center',
                type: 'text',
                template: function(row){
                    if (!row.fecha_revision && !row.revisada_por) return '-';
                    return '<div class="d-flex flex-column align-items-center">' +
                        '<span>' + escapeHtml(row.fecha_revision) + '</span>' +
                        '<small class="text-muted">' + escapeHtml(row.revisada_por) + '</small>' +
                    '</div>';
                }
            };
        break;
        case 'autorizacion':
            celda = {
                field: 'fecha_autorizacion',
                title: 'Autorizada',
                sortable: 'desc',
                width: 150,
                textAlign: 'center',
                type: 'text',
                template: function(row){
                    if (!row.fecha_autorizacion && !row.autorizada_por) return '-';
                    return '<div class="d-flex flex-column align-items-center">' +
                        '<span>' + escapeHtml(row.fecha_autorizacion) + '</span>' +
                        '<small class="text-muted">' + escapeHtml(row.autorizada_por) + '</small>' +
                    '</div>';
                }
            };
        break;
        case 'factura':
            celda = {
                field: 'factura_status',
                title: 'Factura',
                sortable: false,
                width: 140,
                textAlign: 'center',
                template: function(row){
                    // factura_status lo agrega getListado (ver parche); si no viene, no se muestra nada
                    if (typeof row.factura_status === 'undefined') return '-';
                    var st = row.factura_status === null ? 0 : parseInt(row.factura_status, 10);
                    var mapa = {
                        0: ['Sin factura', 'label-light-secondary'],
                        1: ['Pendiente de pago', 'label-light-warning'],
                        2: ['Pagada', 'label-light-success'],
                        3: ['Refacturación', 'label-light-danger']
                    };
                    var m = mapa[st] || mapa[0];
                    var tip = (st === 3 && row.motivo_refacturacion) ? ' title=\"' + escapeHtml(row.motivo_refacturacion) + '\"' : '';
                    return '<span class=\"label ' + m[1] + ' label-inline font-weight-bold\"' + tip + '>' + m[0] + '</span>';
                }
            };
        break;
        case 'acciones':
            celda = {
                field: 'acciones',
                title: 'ACCIONES',
                sortable: false,
                width: 90,
                textAlign: 'center',
                template: function(row){
                    return accionesOC(row);
                }
            };
        break;
    }

    return celda;
}

/* =====================================================================
   MENÚ DE ACCIONES + MODAL DE FACTURAS (PDF, XML y complemento de pago)
   ===================================================================== */

function accionesOC(row){
    var id = escapeHtml(row.id);
    var datos = ' data-id="' + id + '" data-folio="' + escapeHtml(row.folio) + '"';

    // Estatus de la factura: undefined = el listado no lo trae (se muestran todas las opciones
    // y el modal valida con el servidor), null = sin factura, 1 pendiente, 2 pagada, 3 refacturación
    var conocido = typeof row.factura_status !== 'undefined';
    var st = (!conocido || row.factura_status === null) ? 0 : parseInt(row.factura_status, 10);

    var items = '';

    // Factura (PDF + XML)
    var txtFactura = 'Cargar factura (PDF y XML)', icoFactura = 'las la-file-upload';
    if (conocido && st === 3) { txtFactura = 'Cargar refacturación'; icoFactura = 'las la-sync'; }
    if (conocido && (st === 1 || st === 2)) { txtFactura = 'Ver factura cargada'; icoFactura = 'las la-file-invoice'; }
    items += '<li class="navi-item"><a href="javascript:;" class="navi-link btn-cargar-factura"' + datos + '>' +
        '<span class="navi-icon"><i class="' + icoFactura + '"></i></span><span class="navi-text">' + txtFactura + '</span></a></li>';

    // Complemento de pago: solo cuando la factura ya está pagada
    if (!conocido || st === 2 || (!REQUIERE_PAGO_COMPLEMENTO && st === 1)) {
        items += '<li class="navi-item"><a href="javascript:;" class="navi-link btn-cargar-complemento"' + datos + '>' +
            '<span class="navi-icon"><i class="las la-file-invoice-dollar"></i></span><span class="navi-text">Cargar complemento de pago</span></a></li>';
    }

    // PDF de la orden
    items += '<li class="navi-item"><a target="_blank" href="https://saevalcas.mx/compras/ordenes/visualizar/' + id + '" class="navi-link">' +
        '<span class="navi-icon"><i class="las la-file-pdf"></i></span><span class="navi-text">Visualizar Orden de Compra</span></a></li>';

    return '<div class="dropdown dropdown-inline">' +
        '<a href="#" class="btn btn-clean btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
            '<i class="ki ki-bold-more-ver"></i>' +
        '</a>' +
        '<div class="dropdown-menu dropdown-menu-md dropdown-menu-right">' +
            '<ul class="navi navi-hover">' +
                '<li class="navi-header font-weight-bolder text-uppercase font-size-sm text-primary pb-2">Elige una opción:</li>' +
                items +
            '</ul>' +
        '</div>' +
    '</div>';
}

var facturaModal = { id: null, modo: 'factura', puede: false, enviando: false };

function initModalFactura(){
    $(document).on('click', '.btn-cargar-factura', function(){
        abrirModalFactura($(this).data('id'), $(this).data('folio'), 'factura');
    });
    $(document).on('click', '.btn-cargar-complemento', function(){
        abrirModalFactura($(this).data('id'), $(this).data('folio'), 'complemento');
    });

    // Muestra el nombre del archivo elegido en el input de Metronic
    $(document).on('change', '#modalFacturaOC .custom-file-input', function(){
        var nombre = this.files && this.files.length ? this.files[0].name : 'Seleccionar archivo...';
        $(this).next('.custom-file-label').text(nombre);
    });

    $(document).on('click', '#btnGuardarFacturaOC', enviarFacturaOC);
}

function alertaFactura(tipo, html){
    var $a = $('#facturaOC_alerta');
    if (!html) { $a.addClass('d-none').empty(); return; }
    $a.removeClass('d-none alert-success alert-danger alert-warning alert-info')
      .addClass('alert-' + tipo).html(html);
}

function abrirModalFactura(id, folio, modo){
    facturaModal = { id: id, modo: modo, puede: false, enviando: false };

    $('#facturaOC_form')[0].reset();
    $('#modalFacturaOC .custom-file-label').text('Seleccionar archivo...');
    $('#facturaOC_progreso').addClass('d-none').find('.progress-bar').css('width', '0%');
    $('#facturaOC_estado').empty();
    alertaFactura(null);

    $('#modalFacturaOCTitulo').text((modo === 'complemento' ? 'Complemento de pago' : 'Factura') + ' · Orden ' + folio);
    $('.bloque-factura').toggleClass('d-none', modo !== 'factura');
    $('.bloque-complemento').toggleClass('d-none', modo !== 'complemento');
    $('#btnGuardarFacturaOC').addClass('d-none').prop('disabled', false).text('Guardar');
    $('#facturaOC_cargando').removeClass('d-none');
    $('#facturaOC_form').addClass('d-none');

    $('#modalFacturaOC').modal('show');

    $.ajax({
        type: 'GET',
        dataType: 'json',
        url: URL_OC_API + 'get_factura/' + encodeURIComponent(id),
        success: function(res){
            $('#facturaOC_cargando').addClass('d-none');
            if (!res || res.type !== 'success') {
                alertaFactura('danger', escapeHtml(res && res.msj ? res.msj : 'No se pudo consultar la factura.'));
                return;
            }
            pintarEstadoFactura(res.data);
        },
        error: function(xhr){
            $('#facturaOC_cargando').addClass('d-none');
            var msj = (xhr.responseJSON && xhr.responseJSON.msj) || 'No se pudo consultar la factura (' + xhr.status + ').';
            alertaFactura('danger', escapeHtml(msj));
        }
    });
}

function pintarEstadoFactura(d){
    var f = d.factura;               // null = aún no hay factura
    var st = f ? parseInt(f.status, 10) : 0;
    var modo = facturaModal.modo;

    var resumen = '<div class="mb-4"><div class="font-weight-bold">' + escapeHtml(d.proveedor) + '</div>' +
        '<div class="text-muted">Total de la orden: <strong>$' + escapeHtml(d.total) + '</strong></div></div>';

    var detalle = '';
    if (f) {
        var etiquetas = {1: ['Pendiente de pago', 'warning'], 2: ['Pagada', 'success'], 3: ['Refacturación solicitada', 'danger']};
        var e = etiquetas[st] || ['-', 'secondary'];
        detalle += '<div class="mb-3"><span class="label label-light-' + e[1] + ' label-inline font-weight-bold mr-2">' + e[0] + '</span>' +
            (f.num_refacturaciones > 0 ? '<small class="text-muted">Refacturaciones: ' + escapeHtml(f.num_refacturaciones) + '</small>' : '') + '</div>';
        detalle += '<ul class="list-unstyled text-muted mb-3">' +
            '<li><i class="las la-file-pdf mr-1"></i>PDF: <strong>' + (f.archivo_pdf ? 'cargado' : '-') + '</strong></li>' +
            '<li><i class="las la-file-code mr-1"></i>XML: <strong>' + (f.archivo_xml ? 'cargado' : '-') + '</strong>' +
                (f.uuid_cfdi ? ' · UUID ' + escapeHtml(f.uuid_cfdi) : '') + (f.monto !== null && f.monto !== '' ? ' · $' + escapeHtml(f.monto) : '') + '</li>' +
            '<li><i class="las la-file-invoice-dollar mr-1"></i>Complemento: <strong>' + (f.archivo_complemento ? 'cargado' : 'pendiente') + '</strong></li>' +
        '</ul>';
        if (st === 3 && f.motivo_refacturacion) {
            detalle += '<div class="alert alert-custom alert-light-danger py-3 mb-3"><div class="alert-text"><strong>Motivo de la refacturación:</strong> ' +
                escapeHtml(f.motivo_refacturacion) + '</div></div>';
        }
    }
    $('#facturaOC_estado').html(resumen + detalle);

    var puede = false, aviso = '';
    if (modo === 'factura') {
        if (st === 0 || st === 3) puede = true;
        else if (st === 1) aviso = ['info', 'La factura ya fue cargada y está pendiente de pago.'];
        else if (st === 2) aviso = ['success', 'La factura ya fue pagada. Puedes cargar el complemento de pago desde el menú de acciones.'];
    } else {
        var requierePago = d.requiere_pago_complemento !== false; // el backend manda el valor vigente
        if (st === 2 || (!requierePago && st === 1)) puede = true;
        else if (st === 3) aviso = ['warning', 'La factura está en refacturación. Primero carga la nueva factura.'];
        else if (st === 0) aviso = ['warning', 'Primero debes cargar la factura (PDF y XML).'];
        else aviso = ['warning', 'El complemento de pago se habilita cuando la factura ya fue pagada.'];
    }

    facturaModal.puede = puede;
    if (aviso) alertaFactura(aviso[0], escapeHtml(aviso[1]));

    if (puede) {
        $('#facturaOC_form').removeClass('d-none');
        $('#btnGuardarFacturaOC').removeClass('d-none')
            .text(modo === 'complemento' ? 'Subir complemento' : (st === 3 ? 'Enviar refacturación' : 'Subir factura'));
    }
}

function validarArchivo(input, extensiones, maxMB, nombre){
    if (!input.files || !input.files.length) return nombre + ': selecciona un archivo.';
    var file = input.files[0];
    var ext = (file.name.split('.').pop() || '').toLowerCase();
    if (extensiones.indexOf(ext) === -1) return nombre + ': solo se permite ' + extensiones.join(' / ').toUpperCase() + '.';
    if (file.size > maxMB * 1024 * 1024) return nombre + ': excede ' + maxMB + ' MB.';
    if (file.size === 0) return nombre + ': el archivo está vacío.';
    return '';
}

function enviarFacturaOC(){
    if (facturaModal.enviando || !facturaModal.puede) return;
    alertaFactura(null);

    var fd = new FormData();
    var errores = [];

    if (facturaModal.modo === 'factura') {
        var pdf = document.getElementById('facturaOC_pdf');
        var xml = document.getElementById('facturaOC_xml');
        var e1 = validarArchivo(pdf, ['pdf'], 10, 'Factura PDF');
        var e2 = validarArchivo(xml, ['xml'], 5, 'Factura XML');
        if (e1) errores.push(e1);
        if (e2) errores.push(e2);
        if (!errores.length) { fd.append('archivo_pdf', pdf.files[0]); fd.append('archivo_xml', xml.files[0]); }
    } else {
        var comp = document.getElementById('facturaOC_complemento');
        var e3 = validarArchivo(comp, ['pdf', 'xml'], 10, 'Complemento de pago');
        if (e3) errores.push(e3);
        if (!errores.length) fd.append('archivo_complemento', comp.files[0]);
    }

    if (errores.length) {
        alertaFactura('danger', errores.map(escapeHtml).join('<br>'));
        return;
    }

    fd.append('tipo', facturaModal.modo);

    var $btn = $('#btnGuardarFacturaOC');
    var $prog = $('#facturaOC_progreso');
    facturaModal.enviando = true;
    $btn.prop('disabled', true).addClass('spinner spinner-white spinner-right');
    $prog.removeClass('d-none');

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: URL_OC_API + 'guardar_factura/' + encodeURIComponent(facturaModal.id),
        data: fd,
        processData: false,
        contentType: false,
        xhr: function(){
            var xhr = $.ajaxSettings.xhr();
            if (xhr.upload) {
                xhr.upload.addEventListener('progress', function(ev){
                    if (ev.lengthComputable) $prog.find('.progress-bar').css('width', Math.round(ev.loaded / ev.total * 100) + '%');
                });
            }
            return xhr;
        },
        success: function(res){
            if (res && res.type === 'success') {
                alertaFactura('success', escapeHtml(res.msj));
                $('#facturaOC_form').addClass('d-none');
                $btn.addClass('d-none');
                if (typeof datatable !== 'undefined' && datatable && typeof datatable.reload === 'function') datatable.reload();
                setTimeout(function(){ $('#modalFacturaOC').modal('hide'); }, 1400);
            } else {
                alertaFactura('danger', escapeHtml(res && res.msj ? res.msj : 'No se pudo guardar.'));
            }
        },
        error: function(xhr){
            var msj = (xhr.responseJSON && xhr.responseJSON.msj) || 'Error al subir los archivos (' + xhr.status + ').';
            alertaFactura('danger', escapeHtml(msj));
        },
        complete: function(){
            facturaModal.enviando = false;
            $btn.prop('disabled', false).removeClass('spinner spinner-white spinner-right');
            $prog.addClass('d-none');
        }
    });
}
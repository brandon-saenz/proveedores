var content = {
    UI_Template: false,
    // indicadores: indicadores,
    tabs: [
        {
            name: 'generados', 
            title: 'Generados', 
            total: 0, 
            class: 'success', 
            icon: '<i class="fa fa-clock"></i>', 
            current: true,
        },
        {
            name: 'revisadas', 
            title: 'Revisadas', 
            total: 0, 
            class: 'info', 
            icon: '<i class="fa fa-eye"></i>', 
            current: false,
        },
        {
            name: 'autorizadas', 
            title: 'Autorizadas', 
            total: 0, 
            class: 'primary', 
            icon: '<i class="fa fa-check"></i>', 
            current: false,
        },
    ]
};

document.addEventListener('DOMContentLoaded', function () {
    setterData();
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
            setTab('generados');
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
        case 'acciones':
            celda = {
                field: 'acciones',
                title: 'VISUALIZAR',
                sortable: false,
                width: 110,
                textAlign: 'center',
                template: function(row){
                    var id = escapeHtml(row.id);
                    return '<a href="https://saevalcas.mx/compras/ordenes/visualizar/' + id + '" target="_blank" class="btn btn-icon btn-light-danger btn-sm" title="Ver PDF">' +
                               '<i class="la la-file-pdf"></i></a>';
                }
            };
        break;
    }

    return celda;
}
<?php
require_once(APP . '/vistas/inc/encabezado.php');

$empleadosCompras = array(1,1145,1221,1247,1272,1506,1560,1561,1273,1344,1512,1455,1181);
$idUsuario = $_SESSION['login_id'];
$indicadoresArr = json_decode($indicadores, true);
?>

<div id="content" class="">
    <div class="card card-custom gutter-b card-stretch">
        <div class="card-header flex-wrap border-0 pt-6 pb-0" style="margin-bottom: -0.8em;">
            <div class="card-title">
                <h3 class="card-label">Listado de Registros</h3>
            </div>
            <div class="card-toolbar d-none d-sm-flex"></div>
        </div>
        <div class="card-body mt-0">
            <div class="row">
                <div class="d-none d-lg-flex col-12 mb-3">
                    <ul class="nav nav-tabs nav-bold" id="navTabsDesktop">
                        <!-- Generado dinámicamente por renderTabs() en requisiciones.js -->
                    </ul>
                </div>
                <div class="d-flex d-lg-none col-12" style="justify-content: space-between;">
                    <div class="dropdown dropdown-inline show">
                        <a href="javascript:;" class="btnDropdownTab btn btn-clean btn-light-success btn-hover-success btn-sm" data-toggle="dropdown" aria-expanded="true">
                            <div class="nav-text no-wrap-text" style="display: flex;">
                                <i class="fa fa-clock sfs-11" style="margin-right: -0.22em; margin-left: 0.2em; margin-block: auto;"></i>
                                <p class="sfs-11 bold-500 text-uppercase" style="max-width: 20em; text-overflow: ellipsis; white-space: nowrap; display: inline-block; overflow: hidden; margin-inline: 0.5em; margin-block: 0;">
                                    Pendientes (<?php echo isset($indicadoresArr['pendientes']) ? $indicadoresArr['pendientes'] : 0; ?>)
                                </p>
                                <i class="ki ki-arrow-down sfs-09" style="margin-right: -0.5em; margin-left: 0.75em; margin-block: auto;"></i>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-md-2 dropdown-menu-left" style="">
                            <ul class="navi navi-hover" id="navTabsMobileList">
                                <!-- Generado dinámicamente por renderTabs() en requisiciones.js -->
                            </ul>
                        </div>
                    </div>
                    <div class="dropdown dropdown-inline show">
                        <a href="javascript:;" class="btn btn-clean btn-light-primary btn-hover-primary btn-md btn-icon" data-toggle="dropdown" aria-expanded="true">
                            <i class="ki ki-bold-more-hor"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-md dropdown-menu-right" style="">
                            <ul class="navi navi-hover">
                                <li class="navi-header font-weight-bolder text-uppercase font-size-sm text-primary pb-4">ACCIONES</li>
                                <!-- <li class="navi-item">
                                    <a class="navi-link pointer">
                                        <span class="navi-icon">
                                            <i class="fas fa-file-upload"></i>
                                        </span>
                                        <span class="navi-text no-wrap-text">Carga de Documentos por Lote</span>
                                    </a>
                                </li> -->
                                <li class="navi-item" style="border-top: 0.5px solid #EBEDF3;">
                                    <!-- <a class="navi-link pointer" :href="stasis+'/inventario/lotes/nuevo'">
                                        <span class="navi-icon">
                                            <i class="fas fa-plus"></i>
                                        </span>
                                        <span class="navi-text no-wrap-text">Nuevo Lote</span>
                                    </a> -->
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-content" id="tabsContent">
                <!-- Generado dinámicamente por renderTabs() en requisiciones.js -->
            </div>

        </div>
    </div>
</div>

<!-- Modal: carga de factura (PDF + XML) y complemento de pago -->
<div class="modal fade" id="modalFacturaOC" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFacturaOCTitulo">Factura</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <i aria-hidden="true" class="ki ki-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="facturaOC_cargando" class="text-center py-10">
                    <span class="spinner spinner-primary spinner-lg"></span>
                </div>
                <div id="facturaOC_estado"></div>
                <div id="facturaOC_alerta" class="alert d-none" role="alert"></div>

                <form id="facturaOC_form" class="d-none" onsubmit="return false;" enctype="multipart/form-data">
                    <div class="bloque-factura">
                        <div class="form-group">
                            <label class="font-weight-bold">Factura PDF <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="facturaOC_pdf" accept=".pdf,application/pdf">
                                <label class="custom-file-label" for="facturaOC_pdf">Seleccionar archivo...</label>
                            </div>
                            <span class="form-text text-muted">Formato PDF, máximo 10 MB.</span>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Factura XML (CFDI) <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="facturaOC_xml" accept=".xml,text/xml,application/xml">
                                <label class="custom-file-label" for="facturaOC_xml">Seleccionar archivo...</label>
                            </div>
                            <span class="form-text text-muted">Formato XML, máximo 5 MB. Se leerán el UUID y el total del CFDI.</span>
                        </div>
                    </div>
                    <div class="bloque-complemento d-none">
                        <div class="form-group">
                            <label class="font-weight-bold">Complemento de pago <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="facturaOC_complemento" accept=".pdf,.xml,application/pdf,text/xml,application/xml">
                                <label class="custom-file-label" for="facturaOC_complemento">Seleccionar archivo...</label>
                            </div>
                            <span class="form-text text-muted">PDF o XML, máximo 10 MB.</span>
                        </div>
                    </div>
                </form>

                <div id="facturaOC_progreso" class="progress d-none mt-4">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-primary font-weight-bold" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary font-weight-bold d-none" id="btnGuardarFacturaOC">Guardar</button>
            </div>
        </div>
    </div>
</div>
<script>
    const idProveedor = <?php echo $_SESSION['login_id']; ?>;
</script>
<script src="aplicacion/scripts/compras/ordenes.js?<?php echo time(); ?>"></script>
<?php
require_once(APP . '/vistas/inc/pie_pagina.php');
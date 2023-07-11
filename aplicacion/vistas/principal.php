<?php
require_once(APP . '/vistas/inc/encabezado.php');
?>

<?php
if ($datos['porcentaje'] == 100) {
?>
<div class="row">
	<div class="col-md-12 text-center">
		<div class="card card-custom gutter-b card-stretch">
			<div class="card-body">
				<p class="m-0">Estimado proveedor, con el siguiente botón podrás descargar tu expediente con toda tu información en formato PDF.</p><br />
                <a href="<?php echo STASIS; ?>/perfil/pdf/<?php echo $_SESSION['login_uniqueid']; ?>" class="btn btn-primary mr-2"><i class="fa fa-download"></i> Descargar Expediente PDF</a>
			</div>
		</div>
	</div>
</div>
<?php
}
?>

<div class="row">
	<div class="col-md-3 text-center hfit">
		<div class="card card-custom gutter-b card-stretch">
			<div class="card-header border-0">
				<div class="card-title font-weight-bolder text-center" style="margin: 0 auto;">
					<div class="card-label text-center" style="margin: 0 auto;">Cumplimiento</div>
				</div>
			</div>
			<div class="card-body d-flex flex-column">
				<div class="flex-grow-1 mt-n12">
					<div id="porcentaje-cumplimiento" style="height: 200px"></div>
				</div>
				<div style="position: relative; margin-top: -25px;">

					<?php
					if ($datos['status'] == 3) {
					?>

					<div class="label label-inline label-lg font-weight-bolder label-rounded label-info" style="padding: 25px 5px; width: 100%;">Status de Proveedor:<br /> Autorizado</div>
					<p class="text-center font-weight-normal font-size-lg mt-5">
						Has sido autorizado como proveedor, ya puedes cargar los archivos PDF y XML de las facturas.
				    </p>

					<?php
					} elseif ($datos['status'] == 2) {
					?>

					<div class="label label-inline label-lg font-weight-bolder label-rounded label-primary" style="padding: 25px 5px; width: 100%;">Status de Proveedor:<br /> Revisado</div>
					<p class="text-center font-weight-normal font-size-lg mt-5">
						Has sido revisado como proveedor, aún está pendiente la firma por parte de autorización para que puedas cargar facturas.
				    </p>

					<?php
					} elseif ($datos['status'] == 1 && $datos['porcentaje'] == 100) {
					?>

					<div class="label label-inline label-lg font-weight-bolder label-rounded label-primary" style="padding: 25px 5px; width: 100%;">Status de Proveedor:<br /> En Revisión</div>
					<p class="text-center font-weight-normal font-size-lg mt-5">
						La información enviada está en proceso de revisión. Una vez revisada podrás subir tus facturas a nuestra plataforma.
				    </p>

				    <?php
					} else {
					?>

				    <div class="label label-inline label-lg font-weight-bolder label-rounded label-success" style="padding: 25px 5px; width: 100%;">Status de Proveedor:<br /> Pendiente</div>
					<p class="text-center font-weight-normal font-size-lg mt-5">
						Para poder cambiar a status de "Proveedor Activo" deben primero cumplirse todas las tareas pendientes.
				    </p>

			    	<?php
				    }
				    ?>

				</div>
			</div>
		</div>
	</div>

	<div class="col-md-9">
		<div class="card card-custom gutter-b card-stretch">
			<div class="card-header border-0">
				<div class="card-title font-weight-bolder">
					<div class="card-label">Tareas Pendientes</div>
				</div>
			</div>
			<div class="card-body">
				<div class="mt-n12">

					<?php if ($datos['porcentaje'] == 100) { ?>
					<div class="alert alert-success p-5" role="alert">
					    <p class="m-0"><i class="fa fa-check text-white"></i> Gracias por la información enviada, has cumplido con todos los requerimientos.</p>
					</div>
				    <?php } else { ?>
					<div class="alert alert-warning p-5" role="alert">
					    <p class="m-0"><i class="fa fa-exclamation-triangle text-white"></i> Fecha de vencimiento para cumplir todos los requerimientos: <?php echo $datos['fechaVencimiento']; ?>.</p>
					</div>
				    <?php } ?>

				    <!-- Contratista -->
					<?php if ($_SESSION['login_tipo'] == 3) { ?>
				    <p><i class="fa fa-info-circle"></i> Estimado proveedor, por haberte dado de alta en nuestra plataforma como <b>Contratista de Proyectos y Obras</b>, favor de descargar y leer el archivo de <a href="<?php echo STASIS; ?>/data/privada/Solicitud_de_Requisitos_a_Contratistas.docx" target="_blank">Solicitud de Requisitos a Contratistas</a>.</p>
					<?php } ?>

					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['principales'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/principales" class="text-hover-primary <?php if ($datos['principales'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Completar datos de perfil de proveedor.</a>
						</div>
					</div>

					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['logo'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/logo" class="text-hover-primary <?php if ($datos['logo'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Cargar logo de la empresa.</a>
						</div>
					</div>

					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['referencias'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/referencias" class="text-hover-primary <?php if ($datos['referencias'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">
							<!-- Contratista -->
							<?php if ($_SESSION['login_tipo'] != 3) { ?>
						    Especificar referencias comerciales.
							<?php } else { ?>
							Especificar recomendaciones comerciales.
							<?php } ?></a>
						</div>
					</div>

					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['certificaciones'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/certificaciones" class="text-hover-primary <?php if ($datos['certificaciones'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Especificar certificaciones.</a>
						</div>
					</div>

					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<?php if ($_SESSION['login_tipo'] == 1) { ?>
								<input type="checkbox" disabled value="1" <?php if ($datos['ine'] == 1) echo 'checked'; ?> />
								<?php } else { ?>
									<input type="checkbox" disabled value="1" <?php if ($datos['iorl'] == 1) echo 'checked'; ?> />
							<?php } ?>
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							
							<?php if ($_SESSION['login_tipo'] == 1) { ?>
								<a href="<?php echo STASIS; ?>/perfil/datos/ine" class="text-hover-primary <?php if ($datos['ine'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">
							    	Subir archivo de Identificación Oficial.
								</a>
								<?php } else { ?>
								<a href="<?php echo STASIS; ?>/perfil/datos/iorl" class="text-hover-primary <?php if ($datos['iorl'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">
									Subir archivo de Identificación Oficial del Representante Legal.
								</a>
							<?php } ?>
						</div>
					</div>

					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['csf'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/csf" class="text-hover-primary <?php if ($datos['csf'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">

								<!-- Contratista -->
								<?php if ($_SESSION['login_tipo'] == 1) { ?>
							    Subir archivo de Constancia de Situación Fiscal (Persona Física o Actividad Empresarial).
								<?php } else { ?>
								Subir archivo de Constancia de Situación Fiscal (Persona Moral).
								<?php } ?>
							</a>
						</div>
					</div>

					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['cdd'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/cdd" class="text-hover-primary <?php if ($datos['cdd'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Subir archivo de Comprobante de Domicilio (Recibo de Servicios).</a>
						</div>
					</div>

					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['edocta'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/edocta" class="text-hover-primary <?php if ($datos['edocta'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Subir carátula/encabezado de Estado de Cuenta Bancario (Para comprobación de cuentas y CLABE).</a>
						</div>
					</div>

					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['opcs'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/opcs" class="text-hover-primary <?php if ($datos['opcs'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Subir archivo de Opinión Positiva del Cumplimiento por parte del SAT.</a>
						</div>
					</div>

					<!-- Persona Moral -->
					<?php
					if ($_SESSION['login_tipo'] == 2) {
					?>
					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['ac'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/ac" class="text-hover-primary <?php if ($datos['ac'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Subir archivo de Acta Constitutiva.</a>
						</div>
					</div>
					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['pnrl'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/pnrl" class="text-hover-primary <?php if ($datos['pnrl'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Subir archivo del Poder Notarial del Representante Legal.</a>
						</div>
					</div>
					<?php
					}
					?>

					<!-- Contratista / UMA -->
					<?php
					if ($_SESSION['login_tipo'] == 3) {
					?>
					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['ac'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/ac" class="text-hover-primary <?php if ($datos['ac'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Subir archivo de Acta Constitutiva</a>
						</div>
					</div>
					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['pnrl'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/pnrl" class="text-hover-primary <?php if ($datos['pnrl'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Subir archivo del Poder Notarial del Representante Legal.</a>
						</div>
					</div>
					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['upp'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/upp" class="text-hover-primary <?php if ($datos['upp'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Subir archivo del último pago provisional ISR, ultimo pago provisional IVA, retencion de sueldos y salarios.</a>
						</div>
					</div>
					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['eoss'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/eoss" class="text-hover-primary <?php if ($datos['eoss'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Subir archivo del comprobante de pago al seguro social en caso de empresas outsourcing y lista de personal registrado en el Seguro Social (IMSS).</a>
						</div>
					</div>

					<!-- NUEVA DATA EN CONSTRATISTA -->
					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['recom_sec_cons'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/recom_sec_cons" class="text-hover-primary <?php if ($datos['recom_sec_cons'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Recomendaciones dentro del sector de construcción.</a>
						</div>
					</div>
					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['cat_trab_prev'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/cat_trab_prev" class="text-hover-primary <?php if ($datos['cat_trab_prev'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Catálogo de trabajos previos.</a>
						</div>
					</div>
					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['firma_conform'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/firma_conform" class="text-hover-primary <?php if ($datos['firma_conform'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Firma de conformidad.</a>
						</div>
					</div>
					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['firma_reg_disen'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/firma_reg_disen" class="text-hover-primary <?php if ($datos['firma_reg_disen'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Firma de las Reglas de Diseño y Reglamento de Construcción.</a>
						</div>
					</div>
					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['repse'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/repse" class="text-hover-primary <?php if ($datos['repse'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">REPSE.</a>
						</div>
					</div>
					<?php
					}
					?>

					<!-- END CONSTRATISTA / UMA -->

					<div class="d-flex align-items-center">
						<label class="checkbox checkbox-lg checkbox-primary flex-shrink-0 m-0 mr-4 checkbox-nocursor">
							<input type="checkbox" disabled value="1" <?php if ($datos['ce'] == 1) echo 'checked'; ?> />
							<span></span>
						</label>
						<div class="d-flex flex-column flex-grow-1 py-2">
							<a href="<?php echo STASIS; ?>/perfil/datos/ce" class="text-hover-primary <?php if ($datos['ce'] == 1) echo 'text-info'; else echo 'text-dark-75'; ?>">Aceptar de conformidad el código de ética.</a>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>

	<?php
	if ($datos['porcentaje'] == 100 && $datos['status'] == 3) {
	?>
    <div class="col-xl-12">
		<div class="card card-custom gutter-b">
			<div class="card-header border-0">
				<h3 class="card-title">
					<span class="card-label font-weight-bolder text-dark">Listado de Requisiciones Asignadas</span>
				</h3>

				<div class="card-toolbar">
					<form method="get" action="<?php echo STASIS; ?>/perfil/carga/">
						<input type="hidden" name="ids" id="ids" />
					    <button type="submit" id="btn-cargar" class="btn btn-primary btn-md py-2 mr-5 font-weight-bolder" style="display: none;"><i class="fa fa-upload"></i> Cargar PDF y XML</button>
					</form>

					<div class="text-right">
						<div class="input-icon">
							<input type="text" class="form-control" placeholder="Buscar..." id="kt_datatable_search">
							<span>
								<i class="las la-search text-muted"></i>
							</span>
						</div>
					</div>

				</div>

			</div>

			<div class="card-body pt-2">
				<div class="mb-7">
					<div class="row">

						<div class="col-md-12">
							<ul class="nav nav-tabs nav-bold">
								<li class="nav-item">
									<a class="nav-link active" data-toggle="tab" href="#pendientes">
										<span class="nav-icon">
											<i class="fa fa-clock"></i>
										</span>
										<span class="nav-text">Pendientes <span class="label label-rounded label-success" style="width: 40px;"><?php echo $listado['nPendientes']; ?></span></span>
									</a>
								</li>
								<li class="nav-item">
									<a class="nav-link" data-toggle="tab" href="#cargados">
										<span class="nav-icon">
											<i class="fa fa-check"></i>
										</span>
										<span class="nav-text">PDF/XML Cargado <span class="label label-rounded label-primary" style="width: 40px;"><?php echo $listado['nCargados']; ?></span></span>
									</a>
								</li>
							</ul>
						</div>
						
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">

						<div class="tab-content">
							<!-- Pendientes -->
							<div class="tab-pane active" id="pendientes" role="tabpanel">
								<table class="table table-bordered table-striped kt_datatable-1">
						  			<thead>
						  				<tr>
									    	<th style="text-align: center;">Cargar XML/PDF</th>
									    	<th style="text-align: center;">Folio Requisición</th>
									    	<th style="text-align: center;">Orden de Compra</th>
									    	<th style="text-align: center;">Solicitado Por</th>
									    	<th style="text-align: center;">Departamento</th>
									    	<th style="text-align: center;">Producto</th>
									    	<th style="text-align: center;">Cantidad</th>
								    	</tr>
								    </thead>
									<tbody>
										<?php
										foreach ($listado['pendientes'] as $dato) {
										?>
										<tr>
											<td style="text-align: center;">
												<input type="checkbox" class="form-control checkbox-carga" style="width: 20px; height: 20px;" name="checkboxIds" value="<?php echo $dato['id']; ?>">
											</td>
											<td style="text-align: center;"><?php echo $dato['id_requisicion']; ?></td>
											<td style="text-align: center;"><?php echo $dato['oc']; ?></td>
											<td style="text-align: center;"><?php echo $dato['solicita']; ?></td>
											<td style="text-align: center;"><?php echo $dato['departamento']; ?></td>
											<td style="text-align: center;"><?php echo $dato['producto']; ?></td>
											<td style="text-align: center;"><?php echo $dato['cantidad']; ?></td>
										</tr>
										<?php
										}
										?>
									</tbody>
								</table>
							</div>

							<!-- Cargados -->
							<div class="tab-pane" id="cargados" role="tabpanel">
								<table class="table table-bordered table-striped kt_datatable-0">
						  			<thead>
						  				<tr>
									    	<th style="text-align: center;">Folio Requisición</th>
									    	<th style="text-align: center;">Orden de Compra</th>
									    	<th style="text-align: center;">Archivo PDF</th>
									    	<th style="text-align: center;">Archivo XML</th>
									    	<th style="text-align: center;">Subtotal</th>
									    	<th style="text-align: center;">IVA</th>
									    	<th style="text-align: center;">Total</th>
									    	<th style="text-align: center;">T.C.</th>
									    	<th style="text-align: center;">Fecha Cargada</th>
									    	<th style="text-align: center;">Status</th>
								    	</tr>
								    </thead>
									<tbody>
										<?php
										foreach ($listado['cargados'] as $dato) {
										?>
										<tr>
											<td style="text-align: center;"><?php echo $dato['id_requisicion']; ?></td>
											<td style="text-align: center;"><?php echo $dato['oc']; ?></td>
											<td style="text-align: center;"><a target="_blank" href="<?php echo STASIS; ?>/data/privada/facturas/<?php echo $dato['archivo_pdf']; ?>"><?php echo $dato['archivo_pdf']; ?></a></td>
											<td style="text-align: center;"><a target="_blank" href="<?php echo STASIS; ?>/data/privada/facturas/<?php echo $dato['archivo_xml']; ?>"><?php echo $dato['archivo_xml']; ?></a></td>
											<td style="text-align: center;"><?php echo $dato['subtotal']; ?></td>
											<td style="text-align: center;"><?php echo $dato['iva']; ?></td>
											<td style="text-align: center;"><?php echo $dato['total']; ?></td>
											<td style="text-align: center;"><?php echo $dato['tipo_cambio']; ?></td>
											<td style="text-align: center;"><?php echo $dato['fecha_carga']; ?></td>
											<td style="text-align: center;">PENDIENTE DE PAGO</td>
										</tr>
										<?php
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
						
					</div>
				</div>
			</div>
		</div>
		<?php
		}
		?>

     </div>
</div>

<?php
require_once(APP . '/vistas/inc/pie_pagina.php');
?>
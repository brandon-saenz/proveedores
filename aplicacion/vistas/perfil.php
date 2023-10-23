<?php
require_once(APP . '/vistas/inc/encabezado.php');

if (!empty($status)) echo $status;

// Datos principales
if (isset($principales)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="">
				<div class="card-body">
					<div class="form-group row">
						<label class="col-2 col-form-label">* Nombre Comercial</label>
						<div class="col-6">
							<input name="nombre" class="form-control mayusculas" type="text" value="<?php echo $datos['nombre']; ?>" required>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* Razón Social</label>
						<div class="col-6">
							<input name="razon_social" class="form-control mayusculas" type="text" value="<?php echo $datos['razon_social']; ?>" required>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* Tipo</label>
						<div class="col-6">
							<input class="form-control mayusculas" type="text" value="<?php echo $datos['tipo']; ?>" disabled>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* Domicilio Fiscal</label>
						<div class="col-6">
							<input name="domicilio" class="form-control mayusculas" type="text" value="<?php echo $datos['domicilio']; ?>" required>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* Ciudad</label>
						<div class="col-6">
							<input name="ciudad" class="form-control mayusculas" type="text" value="<?php echo $datos['ciudad']; ?>" required>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* Estado</label>
						<div class="col-6">
							<input name="estado" class="form-control mayusculas" type="text" value="<?php echo $datos['estado']; ?>" required>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* RFC</label>
						<div class="col-6">
							<input name="rfc" class="form-control mayusculas" type="text" value="<?php echo $datos['rfc']; ?>" maxlength="13" required>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* Nombre del Contacto</label>
						<div class="col-6">
							<input name="contacto" class="form-control mayusculas" type="text" value="<?php echo $datos['contacto']; ?>" required>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* Teléfono</label>
						<div class="col-6">
							<input name="telefono" class="form-control mask-telefono" type="text" value="<?php echo $datos['telefono']; ?>" required>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">Fax</label>
						<div class="col-6">
							<input name="fax" class="form-control mask-telefono" type="text" value="<?php echo $datos['fax']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* E-Mail</label>
						<div class="col-6">
							<input name="email" class="form-control" type="text" value="<?php echo $datos['email']; ?>" disabled>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* Producto o Servicio que Ofrece</label>
						<div class="col-6">
							<input name="ofrece" class="form-control mayusculas" type="text" value="<?php echo $datos['ofrece']; ?>" required>
						</div>
					</div>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarDatos" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Confirmar Datos</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// Carga de archivos
} elseif (isset($carga)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="form-group row">
						<label class="col-2 col-form-label">* Ordenes de Compra</label>
						<div class="col-6">
							<input name="ids" type="hidden" value="<?php echo $_GET['ids']; ?>">
							<input class="form-control" type="text" value="<?php echo $ordenesCompra; ?>" disabled>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* Archivo PDF</label>
						<div class="col-6">
							<input type="file" name="pdf" accept="application/pdf" required>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* Archivo XML</label>
						<div class="col-6">
							<input type="file" name="xml" accept="application/xml" required>
						</div>
					</div>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="subirPdfXml" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivos</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// Referencias comerciales
} elseif (isset($referencias)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="">
				<div class="card-body">

					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-exclamation-triangle text-white"></i> Las tres referencias comerciales son obligatorias.</p>
					</div>

					<div class="form-group row">
						<?php
						for ($x=1; $x<=3; $x++) {
						?>
						<div class="col-md-5">
							<?php if ($x == 1) { ?> <label class="col-form-label">Empresa:</label> <?php } ?>
							<input type="text" class="form-control mayusculas" name="empresa<?php echo $x; ?>" value="<?php echo $datos[$x]['empresa']; ?>" required />
						</div>
						<div class="col-md-4">
							<?php if ($x == 1) { ?> <label class="col-form-label">Contacto:</label> <?php } ?>
							<input type="text" class="form-control mayusculas" name="contacto<?php echo $x; ?>" value="<?php echo $datos[$x]['contacto']; ?>" required />
						</div>
						<div class="col-md-3">
							<?php if ($x == 1) { ?> <label class="col-form-label">Teléfono:</label> <?php } ?>
							<input type="text" class="form-control mask-telefono" name="telefono<?php echo $x; ?>" value="<?php echo $datos[$x]['telefono']; ?>" required />
						</div>
						<?php
						}
						?>
					</div>
					
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarReferencias" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Confirmar Datos</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// Certificaciones
} elseif (isset($certificaciones)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="">
				<div class="card-body">
					<div class="form-group row">
						<label class="col-2 col-form-label">* ¿Su empresa cuenta con una o más <b>certificaciones</b> que garanticen la calidad de sus productos y/o servicios?</label>
						<div class="col-6">
							<select name="garantia_certificaciones" id="garantia-certificaciones" class="form-control" required>
								<option value="">Selecciona opción...</option>
								<option <?php if ($datos['garantia_certificaciones'] == 1) echo 'selected'; ?> value="1">SI</option>
								<option <?php if ($datos['garantia_certificaciones'] == 2) echo 'selected'; ?> value="2">NO</option>
							</select>
						</div>
					</div>
					<div class="form-group row" <?php if ($datos['garantia_certificaciones'] != 1) echo 'style="display: none;"'; ?> id="contenedor-certificaciones">
						<label class="col-2 col-form-label">Especifique las certificaciones con las que cuenta</label>
						<div class="col-6">
							<textarea name="certificaciones" id="input-certificaciones" class="form-control mayusculas" rows="6"><?php echo $datos['certificaciones']; ?></textarea>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* ¿Cuáles son las <b>garantias</b> que ofrece de sus productos y/o servicios?</label>
						<div class="col-6">
							<textarea name="garantias" id="input-garantias" class="form-control mayusculas" rows="6" required><?php echo $datos['garantias']; ?></textarea>
						</div>
					</div>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarCertificaciones" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Confirmar Datos</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// Csf
} elseif (isset($csf)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> La constancia de situación fiscal debe estar en status de <b>activa</b> y en formato PDF.</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6">
							<input type="file" name="archivo" accept="application/pdf" required>
						</div>
					</div>

					<?php
					if (!empty($datos['csf'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['csf']; ?>"><i class="fa fa-download"></i> <?php echo $datos['csf']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarCsf" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// Cdd
} elseif (isset($cdd)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El comprobante debe ser por lo menos de los últimos 3 meses.</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['cdd'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['cdd']; ?>"><i class="fa fa-download"></i> <?php echo $datos['cdd']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarCdd" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// Edocta
} elseif (isset($edocta)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> Favor de cargar la imagen del estado de cuenta bancario (Solamente es requerido el encabezado sin información sensible, esto solo para comprobación del nombre de la cuenta y el número CLABE) y escribir en los siguientes campos la información que se encuentra en dicho estado de cuenta.</p>
					</div>

					<div class="form-group row">
						<label class="col-2">Seleccionar imagen de estado de cuenta</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword">
						</div>
					</div>
					<?php
					if (!empty($datos['edocta'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['edocta']; ?>"><i class="fa fa-download"></i> <?php echo $datos['edocta']; ?></a>
						</div>
					</div>
					<?php
					}
					?>

					<div class="form-group row">
						<label class="col-2 col-form-label">* Banco</label>
						<div class="col-6">
							<input name="cta_banco" class="form-control mayusculas" type="text" value="<?php echo $datos['cta_banco']; ?>" required>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* Sucursal</label>
						<div class="col-6">
							<input name="cta_sucursal" class="form-control mayusculas" type="text" value="<?php echo $datos['cta_sucursal']; ?>" required>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* Número de Cuenta</label>
						<div class="col-6">
							<input name="cta_cuenta" class="form-control mayusculas" type="text" value="<?php echo $datos['cta_cuenta']; ?>" required>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* CLABE</label>
						<div class="col-6">
							<input name="cta_clabe" class="form-control mayusculas" type="text" value="<?php echo $datos['cta_clabe']; ?>" required>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-2 col-form-label">* Nombre de Encargado de Crédito y Cobranza</label>
						<div class="col-6">
							<input name="cta_encargado" class="form-control mayusculas" type="text" value="<?php echo $datos['cta_encargado']; ?>" required>
						</div>
					</div>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarEdocta" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Aplicar Cambios</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// Opcs
} elseif (isset($opcs)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El resultado de la opinión positiva debe estar en formato PDF (directamente descargado del SAT) y ser del mes en curso.</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf" required>
						</div>
					</div>

					<?php
					if (!empty($datos['opcs'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['opcs']; ?>"><i class="fa fa-download"></i> <?php echo $datos['opcs']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarOpcs" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// Ce
} elseif (isset($ce)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> Haz <a class="text-white" href="<?php echo STASIS; ?>/data/privada/Anexo_2_Codigo_de_Etica_de_Proveedores.pdf"><b><u>click aquí</u></b></a> para descargar el código de ética..</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Estoy de acuerdo con el Código de Ética</label>
						<div class="col-6" >
							<label class="checkbox">
								<input type="checkbox" name="checkboxCe" value="1" <?php if (!empty($datos['ce'])) echo 'checked'; ?>>
								<span></span>
							</label>
						</div>
					</div>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarCe" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Aplicar Cambios</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// Logo
} elseif (isset($logo)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El logo no debe superar los 5mb.</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="image/png, image/gif, image/jpeg" required>
						</div>
					</div>

					<?php
					if (!empty($datos['logo'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['logo']; ?>"><i class="fa fa-download"></i> <?php echo $datos['logo']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarLogo" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// Ac
} elseif (isset($ac)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El archivo de estar en formato PDF, formato Word o en formato de imagen (hojas escaneadas en un solo archivo).</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['ac'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['ac']; ?>"><i class="fa fa-download"></i> <?php echo $datos['ac']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarAc" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// pnrl
} elseif (isset($pnrl)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El archivo de estar en formato PDF, formato Word o en formato de imagen (hojas escaneadas en un solo archivo).</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['pnrl'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['pnrl']; ?>"><i class="fa fa-download"></i> <?php echo $datos['pnrl']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarPnrl" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// iorl
} elseif (isset($iorl)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El archivo de estar en formato PDF, formato Word o en formato de imagen (hojas escaneadas en un solo archivo).</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* INE Anverso</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['iorl'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['iorl']; ?>"><i class="fa fa-download"></i> <?php echo $datos['iorl']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
						<div class="form-group row">
						<label class="col-2">* INE Inverso</label>
						<div class="col-6" >
							<input type="file" name="archivo2" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['ine_reverso'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['ine_reverso']; ?>"><i class="fa fa-download"></i> <?php echo $datos['ine_reverso']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarIorl" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// upp
} elseif (isset($upp)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El archivo de estar en formato PDF, formato Word o en formato de imagen (hojas escaneadas en un solo archivo).</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['upp'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['upp']; ?>"><i class="fa fa-download"></i> <?php echo $datos['upp']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarUpp" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// eoss
} elseif (isset($eoss)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El archivo de estar en formato PDF, formato Word o en formato de imagen (hojas escaneadas en un solo archivo).</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['eoss'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['eoss']; ?>"><i class="fa fa-download"></i> <?php echo $datos['eoss']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarEoss" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// pep
} elseif (isset($pep)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El archivo de estar en formato PDF, formato Word o en formato de imagen (hojas escaneadas en un solo archivo).</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['pep'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['pep']; ?>"><i class="fa fa-download"></i> <?php echo $datos['pep']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarPep" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// ine
} elseif (isset($ine)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> La identificación oficial debe estar vigente.</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* INE Anverso</label>
						<div class="col-6" >
							<input type="file" name="archivo_anverso" accept="application/pdf, image/png, image/gif, image/jpeg" required>
						</div>
					</div>

					<?php
					if (!empty($datos['ine_anverso'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['ine_anverso']; ?>"><i class="fa fa-download"></i> <?php echo $datos['ine_anverso']; ?></a>
						</div>
					</div>
					<?php
					}
					?>

					<div class="form-group row">
						<label class="col-2">* INE Reverso</label>
						<div class="col-6" >
							<input type="file" name="archivo_reverso" accept="application/pdf, image/png, image/gif, image/jpeg" required>
						</div>
					</div>

					<?php
					if (!empty($datos['ine_anverso'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['ine_anverso']; ?>"><i class="fa fa-download"></i> <?php echo $datos['ine_anverso']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarIne" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// recom_sec_cons
} elseif (isset($recom_sec_cons)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El archivo de estar en formato PDF, formato Word o en formato de imagen (hojas escaneadas en un solo archivo).</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['recom_sec_cons'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['recom_sec_cons']; ?>"><i class="fa fa-download"></i> <?php echo $datos['recom_sec_cons']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarRecom_sec_cons" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// cat_trab_prev
} elseif (isset($cat_trab_prev)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El archivo de estar en formato PDF, formato Word o en formato de imagen (hojas escaneadas en un solo archivo).</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['cat_trab_prev'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['cat_trab_prev']; ?>"><i class="fa fa-download"></i> <?php echo $datos['cat_trab_prev']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarCat_trab_prev" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// firma_conform
} elseif (isset($firma_conform)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El archivo de estar en formato PDF, formato Word o en formato de imagen (hojas escaneadas en un solo archivo).</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['firma_conform'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['firma_conform']; ?>"><i class="fa fa-download"></i> <?php echo $datos['firma_conform']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarFirma_conform" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// firma_conform
} elseif (isset($firma_conform)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El archivo de estar en formato PDF, formato Word o en formato de imagen (hojas escaneadas en un solo archivo).</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['firma_conform'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['firma_conform']; ?>"><i class="fa fa-download"></i> <?php echo $datos['firma_conform']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarFirma_conform" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>


<?php
// firma_reg_disen
} elseif (isset($firma_reg_disen)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El archivo de estar en formato PDF, formato Word o en formato de imagen (hojas escaneadas en un solo archivo).</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo de Firma de las Reglas de Diseño</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo Firma del Reglamento de Construcción</label>
						<div class="col-6" >
							<input type="file" name="archivo2" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['firma_reg_disen'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['firma_reg_disen']; ?>"><i class="fa fa-download"></i> <?php echo $datos['firma_reg_disen']; ?></a>
						</div>
					</div>
					<?php
					}
					?>

					<?php
					if (!empty($datos['firma_reg_cons'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['firma_reg_cons']; ?>"><i class="fa fa-download"></i> <?php echo $datos['firma_reg_cons']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarFirma_reg_disen" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// repse
} elseif (isset($repse)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="alert alert-info mb-5 p-5" role="alert">
					    <p class="m-0"><i class="fa fa-info-circle text-white"></i> El archivo de estar en formato PDF, formato Word o en formato de imagen (hojas escaneadas en un solo archivo).</p>
					</div>

					<div class="form-group row">
						<label class="col-2">* Seleccionar archivo</label>
						<div class="col-6" >
							<input type="file" name="archivo" accept="application/pdf, image/png, image/gif, image/jpeg, application/msword" required>
						</div>
					</div>

					<?php
					if (!empty($datos['repse'])) {
					?>
					<div class="form-group row">
						<label class="col-2">Archivo cargado</label>
						<div class="col-6">
							<a target="_blank" href="<?php echo STASIS; ?>/data/privada/archivos/<?php echo $datos['repse']; ?>"><i class="fa fa-download"></i> <?php echo $datos['repse']; ?></a>
						</div>
					</div>
					<?php
					}
					?>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="actualizarRepse" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Subir Archivo</button>
				</div>
			</form>
		</div>
	</div>
</div>


<?php
}

require_once(APP . '/vistas/inc/pie_pagina.php');
?>
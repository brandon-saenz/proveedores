<?php
require_once(APP . '/vistas/inc/encabezado.php');
?>

<?php
// Nueva
if (isset($nueva)) {
?>
<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="" enctype="multipart/form-data">
				<div class="card-body">
					<div class="row">

						<div class="col-md-6">
							<div class="form-group row">
								<div class="col-md-4">
									<label>No. Folio:</label>
									<input type="text" class="form-control form-disabled" disabled value="<?php echo $datos['folio']; ?>" />
								</div>
								<div class="col-md-8">
									<label>Nombre del Propietario:</label>
									<input type="text" class="form-control form-disabled" disabled value="<?php echo $_SESSION['login_nombre'] . ' ' . $_SESSION['login_apellidos']; ?>" />
								</div>
								
							</div>

							<div class="form-group row">
								<div class="col-lg-4">
									<label>* Tipo de Solicitud:</label>
									<select name="tipo" id="solicitud-tipo" class="form-control" required>
										<option value="">Selecciona tipo...</option>
										<option value="A">ATENCIÓN</option>
										<option value="S">SERVICIO</option>
									</select>
								</div>
								<div class="col-lg-8">
									<label>* Servicio:</label>
									<select name="id_servicio" id="solicitud-servicios" class="form-control" required>
										<option value="">Selecciona servicio...</option>
										<?php echo $listadoServicios; ?>
									</select>
								</div>
							</div>

							<div class="form-group row" id="contenedor-otro" style="display: none;">
								<div class="col-lg-4"></div>
								<div class="col-lg-8">
									<label>* Especifica Servicio Requerido:</label>
									<input type="text" class="form-control" id="input-otro" name="otro" value="<?php echo $datos['otro']; ?>" />
								</div>
							</div>

							<div class="form-group row">
								<div class="col-lg-6">
									<label>Adjuntar Archivo:</label>
									<input type="file" name="archivo[]" multiple="1">
									<span class="form-text text-muted">(Subir uno o más archivos)</span>
								</div>
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group row">
								<div class="col-md-4">
									<label>No. Lote:</label>
									<input type="text" class="form-control form-disabled" disabled value="<?php echo $_SESSION['login_lote']; ?>" />
								</div>
								<div class="col-md-4">
									<label>Fecha:</label>
									<input type="text" class="form-control form-disabled" disabled value="<?php echo date('d/m/Y'); ?>" />
								</div>
								<div class="col-md-4">
									<label>Hora:</label>
									<input type="text" class="form-control form-disabled" disabled value="<?php echo date('H:i'); ?> hrs" />
								</div>
							</div>

							<div class="form-group row">
								<div class="col-lg-12">
									<label>* Por favor, escríbenos tus comentarios:</label>
									<textarea name="descripcion" class="form-control" rows="10" required></textarea>
								</div>
							</div>
						</div>

					</div>
				</div>

				<div class="card-footer">
					<input type="hidden" value="1" name="generar" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Enviar Comentario</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
// Enviada
} elseif (isset($enviada)) {
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<div class="card-body text-center">
				<div class="container px-40">
					<h1 style="font-weight: bold;" class="pt-10 pb-10">¡SU COMENTARIO HA SIDO ENVIADO CORRECTAMENTE!</h1>
					<h4>Gracias por enviar sus comentarios, nuestro equipo le dará una respuesta de seguimiento en un periodo máximo de 24 horas.</h4>
					<img src="<?php echo STASIS; ?>/img/guirnalda.png" width="80" class="pt-2 pb-10" />
				</div>
			</div>
			<div class="card-footer text-center">
				<a href="<?php echo STASIS; ?>" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-reply"></i> Regresar</a>
			</div>
		</div>
	</div>
</div>

<?php
}

require_once(APP . '/vistas/inc/pie_pagina.php');
?>
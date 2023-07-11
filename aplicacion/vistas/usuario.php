<?php
require_once(APP . '/vistas/inc/encabezado.php');

if (!empty($status)) echo $status;
?>

<div class="row mb-12">
	<div class="col-md-12">
		<div class="card card-custom">
			<form class="form" method="post" action="">
				<div class="card-body">
					<div class="form-group row">
						<label for="example-text-input" class="col-2 col-form-label">Nombre de Propietario</label>
						<div class="col-6">
							<input class="form-control mayusculas" type="text" value="<?php echo $_SESSION['login_nombre']; ?>" disabled>
						</div>
					</div>
					<div class="form-group row">
						<label for="example-search-input" class="col-2 col-form-label">Sección</label>
						<div class="col-6">
							<input class="form-control mayusculas" type="text" value="<?php echo $_SESSION['login_seccion']; ?>" disabled>
						</div>
					</div>
					<div class="form-group row">
						<label for="example-search-input" class="col-2 col-form-label">Manzana</label>
						<div class="col-6">
							<input class="form-control mayusculas" type="text" value="<?php echo $_SESSION['login_manzana']; ?>" disabled>
						</div>
					</div>
					<div class="form-group row">
						<label for="example-search-input" class="col-2 col-form-label">Lote</label>
						<div class="col-6">
							<input class="form-control mayusculas" type="text" value="<?php echo $_SESSION['login_lote']; ?>" disabled>
						</div>
					</div>
					<div class="form-group row">
						<label for="example-search-input" class="col-2 col-form-label">* E-Mail</label>
						<div class="col-6">
							<input class="form-control minusculas" type="email" required name="email" value="<?php echo $_SESSION['login_email']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<label for="example-search-input" class="col-2 col-form-label">* Teléfono 1</label>
						<div class="col-6">
							<input class="form-control" type="text" required name="telefono1" value="<?php echo $_SESSION['login_telefono1']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<label for="example-search-input" class="col-2 col-form-label">* Teléfono 2</label>
						<div class="col-6">
							<input class="form-control" type="text" required name="telefono2" value="<?php echo $_SESSION['login_telefono2']; ?>">
						</div>
					</div>
				</div>

				<div class="card-footer">
					<input type="hidden" value="<?php echo $datos['id']; ?>" name="id" />
					<input type="hidden" value="1" name="actualizarDatos" />
					<a href="<?php echo STASIS; ?>" class="btn btn-secondary">Regresar</a>
					<button type="submit" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-check"></i> Confirmar Datos de Contacto</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
require_once(APP . '/vistas/inc/pie_pagina.php');
?>
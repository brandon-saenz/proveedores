<!DOCTYPE html>
<html lang="en">
	<head>
		<base href="<?php echo STASIS; ?>/">
		<meta charset="utf-8" />
		<title>Proveedores Grupo Valcas</title>
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
		<link href="assets/css/pages/login/classic/login-4.css" rel="stylesheet" type="text/css" />
		<link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
		<link href="assets/plugins/custom/prismjs/prismjs.bundle.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/themes/layout/header/base/light.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/themes/layout/header/menu/light.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/themes/layout/brand/dark.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/themes/layout/aside/dark.css" rel="stylesheet" type="text/css" />
		<link rel="apple-touch-icon" sizes="180x180" href="img/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="img/favicon-16x16.png">
		<link rel="manifest" href="/site.webmanifest">
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<style type="text/css">
			.mayusculas { text-transform: uppercase; }
		</style>
	</head>
	<body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable page-loading">
		<div class="d-flex flex-column flex-root">
			<div class="login login-4 login-signin-on d-flex flex-row-fluid" id="kt_login">
				<div class="d-flex flex-center flex-row-fluid bgi-size-cover bgi-position-top bgi-no-repeat" style="background: #FAFAFA;">
					
					<?php
					if(isset($index)){
					?>
					<div class="card card-custom login-form p-7 position-relative overflow-hidden">
						<div style="text-align: center; margin: 20px 0;">
							<img src="img/gvalcas.png" height="50" />
						</div>
						<div class="login-signin text-center">
							<div class="mb-10">
								<h3>Portal de Proveedores <?php echo $test;?> </h3>
							</div>

							<form class="form" action="" method="post" id="kt_login_signin_form">
								<div class="form-group">
									<label>Correo Electrónico:</label>
									<div class="input-group">
										<div class="input-group-prepend">
											<span class="input-group-text">
												<i class="la la-user"></i>
											</span>
										</div>
										<input type="text" class="form-control" name="nombreUsuario">
									</div>
								</div>
								<div class="form-group">
									<label>Contraseña:</label>
									<div class="input-group">
										<div class="input-group-prepend">
											<span class="input-group-text">
												<i class="la la-lock"></i>
											</span>
										</div>
										<input type="password" class="form-control" name="contrasena">
									</div>
								</div>

								<div class="form-group d-flex flex-wrap justify-content-between align-items-center">
									<div class="checkbox-inline">
										<label class="checkbox m-0 text-muted">
										<input type="checkbox" name="remember" />
										<span></span>Recordarme</label>
									</div>
									<a  href="<?php echo STASIS; ?>/login/forgot" class="text-muted text-hover-primary font-weight-bold">¿Olvidaste tu contraseña?</a>
								</div>

								<?php if ($mensaje) echo $mensaje; ?>

								<div class="text-center">
									<button type="submit" name="login" class="btn font-weight-bold px-9 py-4 mb-3 mx-4" style="background: #83AB29; color: #FFF;">Ingresar</button>
								</div>
							</form>

							<div class="mt-5">
								<span class="opacity-70 mr-4">¿Deseas registrarte como proveedor?</span>
								<a href="javascript:;" id="kt_login_signup" class="text-muted text-hover-primary font-weight-bold">Regístrate</a>
							</div>
							Consulta nuestro <a href="<?php echo STASIS; ?>/avisoprivacidad">Aviso de Privacidad</a>.
						</div>

						<div class="login-signup">
							<div class="mb-10 text-center">
								<h3>Registro de Proveedor</h3>
								<div class="text-muted font-weight-bold">Introduce tus datos para crear tu cuenta.</div>
							</div>
							<form class="form" action="" method="post" id="kt_login_signup_form">
								<div class="form-group">
									<label>* Nombre del Proveedor:</label>
									<input type="text" class="form-control mayusculas" name="nombre" required />
								</div>
								<div class="form-group">
									<label>* RFC:</label>
									<input type="text" class="form-control mayusculas" name="rfc" maxlength="13" required />
								</div>
								<div class="form-group">
									<label>* Tipo de Proveedor:</label>
									<select class="form-control" name="tipo" required>
										<option value="">Selecciona opción...</option>
										<option value="1">PERSONA FÍSICA</option>
										<option value="2">PERSONA MORAL</option>
										<option value="3">CONTRATISTA DE PROYECTO Y OBRAS</option>
									</select>
								</div>
								<div class="form-group">
									<label>* Nombre del Contacto:</label>
									<input type="text" class="form-control mayusculas" name="contacto" required />
								</div>
								<div class="form-group">
									<label>* Teléfono:</label>
									<input type="text" class="form-control phone_with_ddd" id="mask-telefono" name="telefono" required />
								</div>
								<div class="form-group">
									<label>* Correo Electrónico:</label>
									<input type="email" class="form-control" name="correo" required />
								    <span class="form-text text-muted">El correo electrónico se utilizará para poder iniciar sesión.</span>
								</div>
								<div class="form-group">
									<label>* Contraseña:</label>
									<input type="password" class="form-control" name="contrasena1" required />
								    <span class="form-text text-muted">Contraseña para inicio de sesión en la Plataforma de Proveedores.</span>
								</div>
								<div class="form-group">
									<label>* Confirma Contraseña:</label>
									<input type="password" class="form-control" name="contrasena2" required />
								</div>
								<div class="form-group d-flex flex-wrap flex-center mt-10">
									<button type="submit" name="registro" class="btn font-weight-bold px-9 py-4 my-3 mx-2" style="background: #83AB29; color: #FFF;">Registrarme</button>
									<button id="kt_login_signup_cancel" class="btn btn-secondary font-weight-bold px-9 py-4 my-3 mx-2">Cancelar</button>
								</div>
							</form>
						</div>
					</div>
					<?php
					} elseif(isset($forgot)){
					?>

					<div class="card card-custom login-form p-7 position-relative overflow-hidden">
						<div style="text-align: center; margin: 20px 0;">
							<img src="img/gvalcas.png" height="50" />
						</div>
						<div class="login-signin text-center">
							<div class="mb-10">
								<h3>Restablecimiento de contraseña</h3>
							</div>
							<p class="opacity-70 mbottom-2">Te enviaremos un enlace para restablecer tu contraseña al correo que captures a continuación</p>
							<form class="form" action="" method="post" id="kt_login_signin_form">
								<?php
									if ($mensaje) {
										echo $mensaje;
								?>
								<div class="text-center">
									<a href="<?php echo STASIS;?>" class="btn font-weight-bold px-9 py-4 mb-3 mx-4 btn-primary">Iniciar Sesión</a>
								</div>
								<?php
									}else{
								?>
								<div class="form-group">
									<label>Correo Electrónico:</label>
									<div class="input-group">
										<div class="input-group-prepend">
											<span class="input-group-text">
												<i class="la la-user"></i>
											</span>
										</div>
										<input type="text" class="form-control" name="nombreUsuario">
									</div>
								</div>
								<div class="text-center">
									<a href="<?php echo STASIS;?>" class="btn font-weight-bold px-9 py-4 mb-3 mx-4 btn-primary">Iniciar Sesión</a>
									<button type="submit" name="forgot" class="btn font-weight-bold px-9 py-4 mb-3 mx-4" style="background: #83AB29; color: #FFF;">Enviar</button>
								</div>
								<?php
									}
								?>
							</form>

							<div class="mt-5">
								<span class="opacity-70 mr-4">¿Deseas registrarte como proveedor?</span>
								<a href="javascript:;" id="kt_login_signup" class="text-muted text-hover-primary font-weight-bold">Regístrate</a>
							</div>
							Consulta nuestro <a href="<?php echo STASIS; ?>/avisoprivacidad">Aviso de Privacidad</a>.
						</div>
						<div class="login-signup">
							<div class="mb-10 text-center">
								<h3>Registro de Proveedor</h3>
								<div class="text-muted font-weight-bold">Introduce tus datos para crear tu cuenta.</div>
							</div>
							<form class="form" action="" method="post" id="kt_login_signup_form">
								<div class="form-group">
									<label>* Nombre del Proveedor:</label>
									<input type="text" class="form-control mayusculas" name="nombre" required />
								</div>
								<div class="form-group">
									<label>* RFC:</label>
									<input type="text" class="form-control mayusculas" name="rfc" maxlength="13" required />
								</div>
								<div class="form-group">
									<label>* Tipo de Proveedor:</label>
									<select class="form-control" name="tipo" required>
										<option value="">Selecciona opción...</option>
										<option value="1">PERSONA FÍSICA</option>
										<option value="2">PERSONA MORAL</option>
										<option value="3">CONTRATISTA DE PROYECTO Y OBRAS</option>
									</select>
								</div>
								<div class="form-group">
									<label>* Nombre del Contacto:</label>
									<input type="text" class="form-control mayusculas" name="contacto" required />
								</div>
								<div class="form-group">
									<label>* Teléfono:</label>
									<input type="text" class="form-control phone_with_ddd" id="mask-telefono" name="telefono" required />
								</div>
								<div class="form-group">
									<label>* Correo Electrónico:</label>
									<input type="email" class="form-control" name="correo" required />
								    <span class="form-text text-muted">El correo electrónico se utilizará para poder iniciar sesión.</span>
								</div>
								<div class="form-group">
									<label>* Contraseña:</label>
									<input type="password" class="form-control" name="contrasena1" required />
								    <span class="form-text text-muted">Contraseña para inicio de sesión en la Plataforma de Proveedores.</span>
								</div>
								<div class="form-group">
									<label>* Confirma Contraseña:</label>
									<input type="password" class="form-control" name="contrasena2" required />
								</div>
								<div class="form-group d-flex flex-wrap flex-center mt-10">
									<button type="submit" name="registro" class="btn font-weight-bold px-9 py-4 my-3 mx-2" style="background: #83AB29; color: #FFF;">Registrarme</button>
									<button id="kt_login_signup_cancel" class="btn btn-secondary font-weight-bold px-9 py-4 my-3 mx-2">Cancelar</button>
								</div>
							</form>
						</div>
					</div>
					<?php
					} elseif(isset($restore_password_page)){
					?>
					<div class="card card-custom login-form p-7 position-relative overflow-hidden">
						<div style="text-align: center; margin: 20px 0;">
							<img src="img/gvalcas.png" height="50" />
						</div>
						<div class="login-signin text-center">
							<div class="mb-10">
								<h3>Restablecimiento de contraseña</h3>
							</div>
							<?php
								if($validarTempID){
							?>

							<p class="opacity-70 mbottom-2">Captura la nueva contraseña 2 veces para continuar</p>
							<form class="form" action="" method="post" id="kt_login_signin_form">
								<div class="form-group">
									<label>Contraseña:</label>
									<div class="input-group">
										<div class="input-group-prepend">
											<span class="input-group-text">
												<i class="la la-lock"></i>
											</span>
										</div>
										<input type="password" class="form-control" name="pass_1">
									</div>
								</div>
								<div class="form-group">
									<label>Confirmar Contraseña:</label>
									<div class="input-group">
										<div class="input-group-prepend">
											<span class="input-group-text">
												<i class="la la-lock"></i>
											</span>
										</div>
										<input type="password" class="form-control" name="pass_2">
									</div>
								</div>

								<?php if ($mensaje) echo $mensaje; ?>

								<div class="text-center">
									<a href="<?php echo STASIS;?>" class="btn font-weight-bold px-9 py-4 mb-3 mx-4 btn-primary">Iniciar Sesión</a>
									<button type="submit" name="restore" class="btn font-weight-bold px-9 py-4 mb-3 mx-4" style="background: #83AB29; color: #FFF;">Restablecer</button>
								</div>
							</form>

							<?php
								}else{
							?>
								<form class="form" action="" method="post" id="kt_login_signin_form">
									<div class="alert alert-custom alert-info" role="alert" style="margin-bottom: 2rem;">
										<div class="alert-icon">
											<i class="fa fa-info-circle"></i>
										</div>
										<div class="alert-text">Este enlace ya ha sido utilizado o ha expirado</div>
									</div>
									<div class="text-center">
										<a href="<?php echo STASIS;?>" class="btn font-weight-bold px-9 py-4 mb-3 mx-4 btn-primary">Iniciar Sesión</a>
									</div>
								</form>
							<?php
								}
							?>
							Consulta nuestro <a href="<?php echo STASIS; ?>/avisoprivacidad">Aviso de Privacidad</a>.
						</div>
					</div>
					<?php
					}
					?>
				</div>
			</div>
		</div>
		<script>var KTAppSettings = { "breakpoints": { "sm": 576, "md": 768, "lg": 992, "xl": 1200, "xxl": 1400 }, "colors": { "theme": { "base": { "white": "#ffffff", "primary": "#3699FF", "secondary": "#E5EAEE", "success": "#1BC5BD", "info": "#8950FC", "warning": "#FFA800", "danger": "#F64E60", "light": "#E4E6EF", "dark": "#181C32" }, "light": { "white": "#ffffff", "primary": "#E1F0FF", "secondary": "#EBEDF3", "success": "#C9F7F5", "info": "#EEE5FF", "warning": "#FFF4DE", "danger": "#FFE2E5", "light": "#F3F6F9", "dark": "#D6D6E0" }, "inverse": { "white": "#ffffff", "primary": "#ffffff", "secondary": "#3F4254", "success": "#ffffff", "info": "#ffffff", "warning": "#ffffff", "danger": "#ffffff", "light": "#464E5F", "dark": "#ffffff" } }, "gray": { "gray-100": "#F3F6F9", "gray-200": "#EBEDF3", "gray-300": "#E4E6EF", "gray-400": "#D1D3E0", "gray-500": "#B5B5C3", "gray-600": "#7E8299", "gray-700": "#5E6278", "gray-800": "#3F4254", "gray-900": "#181C32" } }, "font-family": "Poppins" };</script>
		<script src="assets/plugins/global/plugins.bundle.js"></script>
		<script src="assets/plugins/custom/prismjs/prismjs.bundle.js"></script>
		<script src="assets/js/scripts.bundle.js"></script>
		<script src="assets/js/pages/custom/login/login-general.js"></script>
		<script src="assets/js/pages/crud/forms/widgets/jquery-mask.js"></script>
		<script>
			$(function(){
				$('#mask-telefono').mask('(000) 000-0000', {
		            placeholder: "(999) 999-9999"
		        });
		    });
	    </script>
	</body>
</html>
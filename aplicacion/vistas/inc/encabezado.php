<!DOCTYPE html>
<html lang="en">
    <!--begin::Head-->
    <head>
        <base href="<?php echo STASIS; ?>/">
        <meta charset="utf-8" />
        <title>Proveedores Grupo Valcas</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

        <!--begin::Fonts-->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />

        <!--end::Fonts-->

        <!--begin::Page Vendors Styles(used by this page)-->
        <link href="assets/plugins/custom/fullcalendar/fullcalendar.bundle.css" rel="stylesheet" type="text/css" />
        <link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />

        <!--end::Page Vendors Styles-->

        <!--begin::Global Theme Styles(used by all pages)-->
        <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
        <link href="assets/plugins/custom/prismjs/prismjs.bundle.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />

        <!--end::Global Theme Styles-->

        <!--begin::Layout Themes(used by all pages)-->
        <link href="assets/css/themes/layout/header/base/light.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/themes/layout/header/menu/light.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/themes/layout/brand/light.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/themes/layout/aside/light.css" rel="stylesheet" type="text/css" />

        <link href="css/global.css?<?php echo filemtime(ROOT_DIR . 'static/css/global.css'); ?>" rel="stylesheet" type="text/css" />
        <link href="css/app.css?<?php echo filemtime(ROOT_DIR . 'static/css/app.css'); ?>" rel="stylesheet" type="text/css" />

        <!--end::Layout Themes-->
        <link rel="apple-touch-icon" sizes="180x180" href="img/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="img/favicon-16x16.png">
        <link rel="manifest" href="site.webmanifest">

        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />

    </head>

    <!--end::Head-->

    <!--begin::Body-->
    <body id="kt_body" class="header-fixed header-mobile-fixed aside-enabled aside-fixed page-loading">

        
<!--begin::Main-->

        
<!--begin::Header Mobile-->
        <div id="kt_header_mobile" class="header-mobile align-items-center header-mobile-fixed">

            <!--begin::Logo-->
            <a href="./">
                <img alt="Logo" src="img/logo.png" height="18" />
            </a>

            <!--end::Logo-->

            <!--begin::Toolbar-->
            <div class="d-flex align-items-center">

                <!--begin::Aside Mobile Toggle-->
                <button class="btn p-0 burger-icon burger-icon-left" id="kt_aside_mobile_toggle">
                    <span></span>
                </button>

                <!--end::Aside Mobile Toggle-->


                <!--end::Header Menu Mobile Toggle-->

                <!--begin::Topbar Mobile Toggle-->
                <button class="btn btn-hover-text-primary p-0 ml-2" id="kt_header_mobile_topbar_toggle">
                    <span class="svg-icon svg-icon-xl">

                        <!--begin::Svg Icon | path:assets/media/svg/icons/General/User.svg-->
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                <polygon points="0 0 24 0 24 24 0 24" />
                                <path d="M12,11 C9.790861,11 8,9.209139 8,7 C8,4.790861 9.790861,3 12,3 C14.209139,3 16,4.790861 16,7 C16,9.209139 14.209139,11 12,11 Z" fill="#000000" fill-rule="nonzero" opacity="0.3" />
                                <path d="M3.00065168,20.1992055 C3.38825852,15.4265159 7.26191235,13 11.9833413,13 C16.7712164,13 20.7048837,15.2931929 20.9979143,20.2 C21.0095879,20.3954741 20.9979143,21 20.2466999,21 C16.541124,21 11.0347247,21 3.72750223,21 C3.47671215,21 2.97953825,20.45918 3.00065168,20.1992055 Z" fill="#000000" fill-rule="nonzero" />
                            </g>
                        </svg>

                        <!--end::Svg Icon-->
                    </span>
                </button>

                <!--end::Topbar Mobile Toggle-->
            </div>

            <!--end::Toolbar-->
        </div>

        <!--end::Header Mobile-->
        <div class="d-flex flex-column flex-root">

            <!--begin::Page-->
            <div class="d-flex flex-row flex-column-fluid page">

                
<!--begin::Aside-->
                <div class="aside aside-left aside-fixed d-flex flex-column flex-row-auto" id="kt_aside">

                    <!--begin::Brand-->
                    <div class="text-center mt-2" id="kt_brand">

                        <!--begin::Logo-->
                        <a href="./" class="brand-logo">
                            <img alt="Logo" src="img/logo.png" height="50" />
                        </a>

                        <!--end::Logo-->
                    </div>

                    <!--end::Brand-->

                    <!--begin::Aside Menu-->
                    <div class="aside-menu-wrapper flex-column-fluid" id="kt_aside_menu_wrapper">

                        <!--begin::Menu Container-->
                        <div id="kt_aside_menu" class="aside-menu" data-menu-vertical="1" data-menu-scroll="1" data-menu-dropdown-timeout="500">

                            <!--begin::Menu Nav-->
                            <ul class="menu-nav">
                                <li class="menu-section">
                                    <h4 class="menu-text">Menú Principal</h4>
                                    <i class="menu-icon ki ki-bold-more-hor icon-md"></i>
                                </li>
                                <li class="menu-item menu-item-submenu <?php if ($menu == 'principal') echo 'menu-item-active'; ?>" aria-haspopup="true" data-menu-toggle="hover">
                                    <a href="<?php echo STASIS; ?>" class="menu-link menu-toggle">
                                        <span class="menu-text">Página de Inicio</span>
                                    </a>
                                </li>

                                <li class="menu-section">
                                    <h4 class="menu-text">Opciones</h4>
                                    <i class="menu-icon ki ki-bold-more-hor icon-md"></i>
                                </li>

                                <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                                    <a href="<?php echo STASIS; ?>/usuario/cs" class="menu-link menu-toggle">
                                        <i class="menu-icon fa fa-sign-out-alt"></i>
                                        <span class="menu-text">Cerrar Sesión</span>
                                    </a>
                                </li>
                            </ul>

                            <!--end::Menu Nav-->
                        </div>

                        <!--end::Menu Container-->
                    </div>

                    <!--end::Aside Menu-->
                </div>

                <!--end::Aside-->

                <!--begin::Wrapper-->
                <div class="d-flex flex-column flex-row-fluid wrapper" id="kt_wrapper">

                    
<!--begin::Header-->
                    <div id="kt_header" class="header header-fixed">

                        <!--begin::Container-->
                        <div class="container-fluid d-flex align-items-stretch justify-content-between">

                            <!--begin::Header Menu Wrapper-->
                            <div class="header-menu-wrapper header-menu-wrapper-left" id="kt_header_menu_wrapper" style="font-weight: bold; font-size: 20px; margin-top: 17px;">
                                <?php echo $titulo; ?>
                            </div>
                            <!--end::Header Menu Wrapper-->

                            <!--begin::Topbar-->
                            <div class="topbar">

                                <!--begin::User-->
                                <div class="topbar-item">
                                    <div class="btn btn-icon btn-icon-mobile w-auto btn-clean d-flex align-items-center btn-lg px-2">
                                        <span class="text-muted font-weight-bold font-size-base d-none d-md-inline mr-1"><i class="fa fa-user-circle mr-2"></i> Proveedor</span>
                                        <span class="text-dark-50 font-weight-bolder font-size-base d-none d-md-inline"><?php echo $_SESSION['login_nombre']; ?></span>
                                    </div>
                                </div>

                                <!--end::User-->
                            </div>

                            <!--end::Topbar-->
                        </div>

                        <!--end::Container-->
                    </div>

                    <!--end::Header-->

                    <!--begin::Content-->
                    <div class="content d-flex flex-column flex-column-fluid" id="kt_content">

                        
<!--begin::Entry-->
                        <div class="d-flex flex-column-fluid">

                            
<!--begin::Container-->
                            <div class="container-fluid">

                                <!--begin::Dashboard-->
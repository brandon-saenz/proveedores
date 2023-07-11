$(function(){
	$(document).on("keydown", function (e) {
	    if (e.which === 8 && !$(e.target).is("input, textarea")) {
	        e.preventDefault();
	    }
	});

	$('.kt_datatable-0').DataTable({
		responsive: true,
		order: [[ 0, "desc" ]],
		lengthMenu: [
	        [10, 50, 100, -1],
	        [10, 50, 100, "Todos"]
	    ],
	    dom: "<'row'<'col-md-6'l><'col-md-6'p>r>t<'row'<'col-md-6'i><'col-md-6'p>r>",
		language: {
	        sProcessing:     "Procesando...",
	        sLengthMenu:     "Mostrar _MENU_ registros",
	        sZeroRecords:    "No se encontraron resultados",
	        sEmptyTable:     "Ningún registro disponible",
	        sInfo:           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
	        sInfoEmpty:      "Mostrando registros del 0 al 0 de un total de 0 registros",
	        sInfoFiltered:   "(filtrado de un total de _MAX_ registros)",
	        sInfoPostFix:    "",
	        sSearch:         "Buscar:",
	        sUrl:            "",
	        sInfoThousands:  ",",
	        sLoadingRecords: "Cargando...",
	        oPaginate: {
	            sFirst:    "Primero",
	            sLast:     "Último",
	            sNext:     "Siguiente",
	            sPrevious: "Anterior"
	        },
	        oAria: {
	            sSortAscending:  ": Activar para ordenar la columna de manera ascendente",
	            sSortDescending: ": Activar para ordenar la columna de manera descendente"
	        }
	    },
	});

	$('.kt_datatable-1').DataTable({
		responsive: true,
		order: [[ 1, "asc" ]],
		lengthMenu: [
	        [10, 50, 100, -1],
	        [10, 50, 100, "Todos"]
	    ],
	    dom: "<'row'<'col-md-6'l><'col-md-6'p>r>t<'row'<'col-md-6'i><'col-md-6'p>r>",
		language: {
	        sProcessing:     "Procesando...",
	        sLengthMenu:     "Mostrar _MENU_ registros",
	        sZeroRecords:    "No se encontraron resultados",
	        sEmptyTable:     "Ningún registro disponible",
	        sInfo:           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
	        sInfoEmpty:      "Mostrando registros del 0 al 0 de un total de 0 registros",
	        sInfoFiltered:   "(filtrado de un total de _MAX_ registros)",
	        sInfoPostFix:    "",
	        sSearch:         "Buscar:",
	        sUrl:            "",
	        sInfoThousands:  ",",
	        sLoadingRecords: "Cargando...",
	        oPaginate: {
	            sFirst:    "Primero",
	            sLast:     "Último",
	            sNext:     "Siguiente",
	            sPrevious: "Anterior"
	        },
	        oAria: {
	            sSortAscending:  ": Activar para ordenar la columna de manera ascendente",
	            sSortDescending: ": Activar para ordenar la columna de manera descendente"
	        }
	    },
	});

	$('.kt_datatable-2').DataTable({
		responsive: true,
		order: [[ 0, "asc" ]],
		lengthMenu: [
	        [10, 50, 100, -1],
	        [10, 50, 100, "Todos"]
	    ],
	    dom: "<'row'<'col-md-6'l><'col-md-6'p>r>t<'row'<'col-md-6'i><'col-md-6'p>r>",
		language: {
	        sProcessing:     "Procesando...",
	        sLengthMenu:     "Mostrar _MENU_ registros",
	        sZeroRecords:    "No se encontraron resultados",
	        sEmptyTable:     "Ningún registro disponible",
	        sInfo:           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
	        sInfoEmpty:      "Mostrando registros del 0 al 0 de un total de 0 registros",
	        sInfoFiltered:   "(filtrado de un total de _MAX_ registros)",
	        sInfoPostFix:    "",
	        sSearch:         "Buscar:",
	        sUrl:            "",
	        sInfoThousands:  ",",
	        sLoadingRecords: "Cargando...",
	        oPaginate: {
	            sFirst:    "Primero",
	            sLast:     "Último",
	            sNext:     "Siguiente",
	            sPrevious: "Anterior"
	        },
	        oAria: {
	            sSortAscending:  ": Activar para ordenar la columna de manera ascendente",
	            sSortDescending: ": Activar para ordenar la columna de manera descendente"
	        }
	    },
	});

	$('.kt_datatable_sort').DataTable({
	    responsive: true,
	    order: [[ 0, "asc" ]],
	    lengthMenu: [
	        [10, 50, 100, -1],
	        [10, 50, 100, "All"]
	    ],
	    dom: "<'row'<'col-md-6'l><'col-md-6'p>r>t<'row'<'col-md-6'i><'col-md-6'p>r>",
	});

	$('.kt_datatable_nosort').DataTable({
	    responsive: true,
	    order: false,
	    lengthMenu: [
	        [10, 50, 100, -1],
	        [10, 50, 100, "All"]
	    ],
	    dom: "<'row'<'col-md-6'l><'col-md-6'p>r>t<'row'<'col-md-6'i><'col-md-6'p>r>",
	});

	$('#kt_datatable_search').on('keyup', function () {
		$('.kt_datatable-0').DataTable().search( this.value ).draw();
		$('.kt_datatable-1').DataTable().search( this.value ).draw();
		$('.kt_datatable-2').DataTable().search( this.value ).draw();
	} );

	// Cambiar de tipo
	$("#solicitud-tipo").on("change", function(event){
        var t = $(this).val();
        $('#contenedor-otro').hide();
        $("#solicitud-servicios").val('');
        $("#solicitud-servicios option").hide();
        $("#solicitud-servicios option[data-t='" + t + "']").show();
	});

	$(document).on('click', '.id-solicitud', function() {
        var id = $(this).attr('data-id');

		$.ajax({
	        url: window.STASIS + "/movimientos/solicitudes/info",
	        type: "POST",
	        data: { id: id },
	        success: function(output){
	            $('#info-solicitud').html(output);
	        }
	    });

	    $.ajax({
	        url: window.STASIS + "/movimientos/solicitudes/info_comentarios",
	        type: "POST",
	        data: { id: id },
	        success: function(output){
	            $('#info-comentarios').html(output);
	        }
	    });
	});

	$('.mask-telefono').mask('(000) 000-0000', {
        placeholder: "(999) 999-9999"
    });

	$("#garantia-certificaciones").on("change", function(event){
        var o = $(this).val();

        if (o == 1) {
        	$('#contenedor-certificaciones').slideDown();
        	$('#contenedor-garantias').slideUp();

        	$('#input-certificaciones').attr('required', 'required');
        	$('#input-garantias').removAttr('required');
        } else {
        	$('#contenedor-certificaciones').slideUp();
        	$('#contenedor-garantias').slideDown();

        	$('#input-garantias').attr('required', 'required');
        	$('#input-certificaciones').removAttr('required');
        }
	});

	$(document).on('click', '.checkbox-carga', function() {
		var ids = new Array();
		$("input:checkbox[name=checkboxIds]:checked").each(function(){
		    ids.push($(this).val());
		});

		$('#ids').val(ids);

		if($('.checkbox-carga[type=checkbox]:checked').length) {
			$('#btn-cargar').show();
		} else {
			$('#btn-cargar').hide();
		}
	});

});
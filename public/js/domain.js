var ruta_global = `${window.location.origin}/`;
// var ruta_global = ""https://miskyselva.com/";
function consultarNumdocumento(DocumentType, idValue,customerName = null , customerAddress= null,clientStatus = null,nombreVENTAWEB = null){
    // nombreVENTAWEB se agrego nomas para que funcione en la pagina web
    let tipoDocumento= "";
    let valorNum ="";
    let tipoRespuesta ="";
    if(DocumentType){
        tipoDocumento = $('#'+DocumentType).val();
    }
    if(idValue){
        valorNum = $('#'+idValue).val();
    }
    $('#'+clientStatus).html("");
    // verificamos que tipo de documento es
    respuesta('Buscando....', 'info');

    if(tipoDocumento == 4){
        if(valorNum.length == 11){
            if (!isNaN(valorNum)){
                if(valorNum=="00000000000"){
                    respuesta('Proveedor Extranjero', 'success');
                    tipoRespuesta = "text-success";
                    $('#'+clientStatus).html("HABIDO");
                }else{
                    var formData = new FormData();
                    formData.append("token", "uTZu2aTvMPpqWFuzKATPRWNujUUe7Re1scFlRsTy9Q15k1sjdJVAc9WGy57m");
                    formData.append("ruc", valorNum);
                    var request = new XMLHttpRequest();
                    request.open("POST", "https://api.migo.pe/api/v1/ruc");
                    request.setRequestHeader("Accept", "application/json");
                    request.send(formData);
                    $('.loader').show();
                    request.onload = function() {
                        var data = JSON.parse(this.response);
                        if(data.success){
                            $('.loader').hide();
                            respuesta('Datos Encontrados', 'success');
                            if(data.condicion_de_domicilio=="NO HABIDO"){
                                respuesta('Este ruc se encuentra como NO HABIDO.', 'error');
                                tipoRespuesta = "text-danger";
                            }else{
                                $('#'+customerName).val(data.nombre_o_razon_social);
                                $('#'+customerAddress).val(data.direccion);
                                tipoRespuesta = "text-success";
                            }
                            $('#'+clientStatus).html(data.condicion_de_domicilio);
                            $('#'+clientStatus).addClass(tipoRespuesta);
                            if(nombreVENTAWEB){
                                $('#'+nombreVENTAWEB).html('<i class="fa fa-user"></i> ' + data.nombre_o_razon_social);
                            }
                        }else{
                            $('.loader').hide();
                            respuesta(data.message, 'error');
                        }
                    };
                }
            }else{
                respuesta('El ruc debe contener solo números.', 'error');
                $('#'+clientStatus).html("");
            }
        }else{
            respuesta('El ruc debe contener 11 dígitos.', 'error');
            $('#'+clientStatus).html("");
        }
    }else{
        if(valorNum.length == 8){
            if (!isNaN(valorNum)){
                if(valorNum=="00000000"){
                    respuesta('CLIENTE GENERAL', 'success');
                    $('#'+clientStatus).html("HABIDO");
                }else{
                    var formData = new FormData();
                    formData.append("token", "uTZu2aTvMPpqWFuzKATPRWNujUUe7Re1scFlRsTy9Q15k1sjdJVAc9WGy57m");
                    formData.append("dni", valorNum);
                    var request = new XMLHttpRequest();
                    request.open("POST", "https://api.migo.pe/api/v1/dni");
                    request.setRequestHeader("Accept", "application/json");
                    request.send(formData);
                    $('.loader').show();
                    request.onload = function() {
                        var data = JSON.parse(this.response);
                        if(data.success){
                            $('.loader').hide();
                            tipoRespuesta = "text-success";
                            respuesta('Datos Encontrados', 'success');
                            $('#'+customerName).val(data.nombre);
                            if(customerAddress){
                                $('#'+customerAddress).val("");
                            }
                            // solo sirve para la web ya que ahi se tiene que visualizar en un card el nombre del cliente
                            if(nombreVENTAWEB){
                                $('#'+nombreVENTAWEB).html('<i class="fa fa-user"></i> ' +data.nombre);
                            }
                            $('#'+clientStatus).html("HABIDO");
                            $('#'+clientStatus).addClass(tipoRespuesta);
                        }else{
                            $('.loader').hide();
                            tipoRespuesta = "text-danger";
                            respuesta(data.message, 'error');
                            $('#'+clientStatus).addClass(tipoRespuesta);
                        }
                    };
                }
            }else{
                respuesta('El DNI debe contener solo números.', 'error');
                $('#'+clientStatus).html("");
            }
        }else{
            respuesta('El DNI debe contener 8 dígitos.', 'error');
            $('#'+clientStatus).html("");
        }
    }

    // if(customerAddress){
    //     direccionCliente = $('#'+customerAddress).val();
    // }




}

function validarTamanoFoto(foto,ancho,alto) {
    return new Promise((resolve, reject) => {
        if (!foto) {
            resolve(false); // Si no se ha seleccionado ninguna foto, se considera inválida
        } else {
            const image = new Image();

            image.onload = function() {
                const width = this.width;
                const height = this.height;

                if (width === ancho && height === alto) {
                    resolve(true); // La foto tiene el tamaño correcto
                } else {
                    resolve(false); // La foto no tiene el tamaño correcto
                }
            };

            const reader = new FileReader();
            reader.onload = function(e) {
                image.src = e.target.result;
            };
            reader.readAsDataURL(foto);
        }
    });
}

function cambiar_estado_boton(id, texto, deshabilitado){
    $("#" + id).html(texto);
    $("#" + id).attr("disabled", deshabilitado);
}
function quoteCheckboxesInContainer() {
    $('input[type="checkbox"]').each(function () {
        $(this).attr('checked', false)
    } )
}
function validar_campo_vacio(campo, valor, estado) {
    var objeto = document.getElementById(campo);
    if(!valor){
        respuesta('El siguiente Campo Resaltado no puede estar vacío', 'error');
        estado = false;
        objeto.classList.add('is-invalid'); // Agregar clase is-invalid si el campo está vacío
        console.log('Campo vacio: ' + campo + " Valor: " + valor);
    } else {
        objeto.style.border = '';
        objeto.classList.remove('is-invalid'); // Remover clase is-invalid si el campo está lleno
        objeto.classList.add('is-valid'); // Agregar clase is-valid si el campo está lleno y es válido
    }
    return estado;
}
// function validar_campo_vacio(campo, valor, estado) {
//     //var variable = "#" + campo;
//     var objeto = document.getElementById(campo);
//     if(valor == ""){
//         respuesta('El siguiente Campo Resaltado no puede estar vacío', 'error');
//         objeto.style.border = 'solid #ff4d4d ';
//         estado = false;
//         console.log('Campo vacio: ' + campo + " Valor: " + valor);
//     } else {
//         objeto.style.border = '';
//     }
//     return estado;
// }
// function respuesta(mensaje, tipo,tiempo = 3000){
//     const Toast = Swal.mixin({
//         toast: true,
//         position: 'center',
//         showConfirmButton: false,
//         timer: tiempo,
//         timerProgressBar: true,
//         didOpen: (toast) => {
//             toast.addEventListener('mouseenter', Swal.stopTimer)
//             toast.addEventListener('mouseleave', Swal.resumeTimer)
//         }
//     });
//     Toast.fire({icon: tipo, title: mensaje});
// }
function respuesta(mensaje, tipo, tiempo = 3000,tipoNotificacion = 1){
    if (tipoNotificacion == 1){
        let titulo = '';
        switch (tipo) {
            case 'error':   titulo = '¡Error!'; break;
            case 'success': titulo = '¡Éxito!'; break;
            case 'warning': titulo = '¡Atención!'; break;
            case 'info':    titulo = 'Información'; break;
            default:        titulo = ''; break;
        }
        Swal.fire({
            icon: tipo,
            title: titulo,   // 👈 arriba (Error!, Éxito!, etc.)
            text: mensaje,   // 👈 abajo (tu mensaje)
            position: 'center',
            showConfirmButton: false,
            timer: tiempo,
            timerProgressBar: true,
            didOpen: () => {
                const container = Swal.getContainer();
                if (container) container.style.zIndex = 99999;
            }
        });
    }else{
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: tiempo,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
        Toast.fire({icon: tipo, title: mensaje});
    }

}
function respuestaCargando(mensaje = 'Procesando...') {
    Swal.fire({
        title: mensaje,
        html: 'Espere un momento, por favor...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
            const container = Swal.getContainer();
            if (container) container.style.zIndex = 99999;
        }
    });
}
function money(v){
    return (Number(v) || 0).toFixed(2);
}
function mostrarErroresValidacion(errors) {
    // Limpia errores previos
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    // Recorre campos
    Object.keys(errors).forEach(function (campo) {
        const mensajes = errors[campo];
        const mensaje = Array.isArray(mensajes) ? mensajes[0] : mensajes;

        // Busca el input por name (recomendado)
        const $input = $('[name="'+campo+'"]');

        if ($input.length) {
            $input.addClass('is-invalid');
            $input.after('<div class="invalid-feedback d-block">'+mensaje+'</div>');
        } else {
            // Si no encuentra el input, lo manda como alerta general
            respuesta(mensaje, 'error');
        }
    });

    // Si quieres: mostrar el primer error general como toast también
    const primerCampo = Object.keys(errors)[0];
    if (primerCampo) {
        const primerMensaje = errors[primerCampo][0];
        respuesta(primerMensaje, 'error');
    }
}


function limpiarCampos(id) {
    const formulario = document.getElementById(id);
    const elementos = formulario.elements;

    for (let i = 0; i < elementos.length; i++) {
        const elemento = elementos[i];
        const tipo = elemento.type.toLowerCase();

        // Restablecer el valor según el tipo de elemento
        switch (tipo) {
            case "text":
            case "email":
            case "password":
            case "number":
            case "textarea":
            case "select-one":
                elemento.value = "";
                elemento.classList.remove('is-invalid'); // Remover la clase is-invalid
                break;
            case "radio":
            case "checkbox":
                elemento.checked = false;
                break;
            // Agrega aquí más casos si tienes otros tipos de elementos
        }
    }
}

function limpiarFormulario_sin_input(formId, inputNoLimpiar) {
    const formulario = document.getElementById(formId);
    const inputs = formulario.getElementsByTagName('input');

    for (let i = 0; i < inputs.length; i++) {
        const input = inputs[i];
        if (input.type === 'text' || input.type === 'email') {
            if (input.id !== inputNoLimpiar) {
                input.value = ''; // Limpia el valor del input de texto o email, excepto el input específico
            }
        } else if (input.type === 'checkbox') {
            input.checked = false; // Desmarca el checkbox
        }
    }
}
function validar_numeros(id) {
    var text = document.getElementById(id).value;
    var expreg = new RegExp(/^[0-9]*$/);
    if(expreg.test(text)){
        return true;
    } else {
        //alertify.error("Carácter Inválido");
        //var long = text.length;
        //var text_to_extract = long - 1;
        //document.getElementById(id).value = text.substring(0, text_to_extract);
        var re = /[a-zA-ZñáéíóúÁÉÍÓÚ´*+?^$&!¡¿#%/{}()='|[\]\\"]/g;
        document.getElementById(id).value = text.replace(re, '');
        return false;
    }
}
function redondear (numero, decimales = 2, usarComa = false) {
    //Esta respuesta
    var opciones = {
        maximumFractionDigits: decimales,
        useGrouping: false
    };
    return new Intl.NumberFormat((usarComa ? "es" : "en"), opciones).format(numero);
}


function formatDate(fecha, formato) {
    const year = fecha.getFullYear();
    const month = ('0' + (fecha.getMonth() + 1)).slice(-2);
    const day = ('0' + fecha.getDate()).slice(-2);
    const hours = ('0' + fecha.getHours()).slice(-2);
    const minutes = ('0' + fecha.getMinutes()).slice(-2);
    const seconds = ('0' + fecha.getSeconds()).slice(-2);

    let formattedDate = formato;
    formattedDate = formattedDate.replace('Y', year);
    formattedDate = formattedDate.replace('m', month);
    formattedDate = formattedDate.replace('d', day);
    formattedDate = formattedDate.replace('H', hours);
    formattedDate = formattedDate.replace('i', minutes);
    formattedDate = formattedDate.replace('s', seconds);
    return formattedDate;
}


function previewImage(input, preview) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#'+preview).attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function consultarRUC(valor,razon, direccion = null){
    var numero_ruc =  valor;
    var formData = new FormData();
    formData.append("token", "uTZu2aTvMPpqWFuzKATPRWNujUUe7Re1scFlRsTy9Q15k1sjdJVAc9WGy57m");
    formData.append("ruc", numero_ruc);
    var request = new XMLHttpRequest();
    request.open("POST", "https://api.migo.pe/api/v1/ruc");
    request.setRequestHeader("Accept", "application/json");
    request.send(formData);
    $('.loader').show();
    request.onload = function() {
        var data = JSON.parse(this.response);
        if(data.success){
            //$('.loader').hide();
            $("#"+razon).val(data.nombre_o_razon_social);
            $('#'+direccion).val(data.direccion_simple);
        }else{
            console.log(data.message);
        }
    };
    /*$.ajax({
        type: "POST",
        url: urlweb + "api/Cliente/obtener_datos_x_ruc",
        data: "numero_ruc="+numero_ruc,
        dataType: 'json',
        success:function (r) {
            $("#client_name").val(r.result.razon_social);
        }
    });*/
}
function consultarDNI(valor,nombre,apellido = null){
    if(valor.length == 8){
        var numero_dni =  valor;

        var formData = new FormData();
        formData.append("token", "uTZu2aTvMPpqWFuzKATPRWNujUUe7Re1scFlRsTy9Q15k1sjdJVAc9WGy57m");
        formData.append("dni", numero_dni);
        var request = new XMLHttpRequest();
        request.open("POST", "https://api.migo.pe/api/v1/dni");
        request.setRequestHeader("Accept", "application/json");
        request.send(formData);
        //$('.loader').show();
        request.onload = function() {
            var data = JSON.parse(this.response);
            if(data.success){
                console.log(data);
                //$('.loader').hide();
                let datos = data.nombre.split(' ')
                //$('#cotizacion_beneficiario').val(data.nombre);
                $("#"+nombre).val(datos[2]+" "+datos[3]+" "+datos[0]+" "+datos[1]);
                if(apellido){
                    $("#"+nombre).val(datos[2]+" "+datos[3]);
                    $("#"+apellido).val(datos[0]+" "+datos[1]);
                }
            }else{
                //$('.loader').hide();
                console.log(data.message);
            }
            $('#menssage_api').html(" ")

        };
    }

}

function preguntar(mensaje, funcion_usar, confirmar, denegar, id, id2 = '', id3 = ''){
    Swal.fire({
        title: mensaje,
        showDenyButton: true,
        showCancelButton: false,
        confirmButtonText: confirmar,
        denyButtonText: denegar,
    }).then((result) => {
        /* Read more about isConfirmed, isDenied below */
        if (result.isConfirmed) {
            //Swal.fire('Saved!', '', 'success')
            if(id3 !== ''){
                window[funcion_usar].apply(this, [id,id2,id3]);
            } else {
                if(id2 !== ''){
                    window[funcion_usar].apply(this, [id,id2]);
                } else {
                    window[funcion_usar].apply(this, [id]);
                }
            }
        } else if (result.isDenied) {
            respuesta('Operacion Cancelada', 'error');
        }
    })
}
//Función para validar que sólo se ingresen 2 numeros decimales en un campo numerico
//Llamar así: onk
// eyup="return validar_numeros_decimales_dos(this.id)"
function validar_numeros_decimales_dos(id) {
    var text = document.getElementById(id).value;
    var expreg = new RegExp(/^[+-]?[0-9]*$/);
    var expreg2 = new RegExp(/^[+-]?[0-9]+([.]+)?$/);
    var expreg3 = new RegExp(/^[+-]?[0-9]+([.][0-9]{1,3})?$/);
    if(expreg.test(text)){
        return true;
    } else {
        if(expreg2.test(text)){
            return true;
        } else {
            if (expreg3.test(text)){
                return true;
            } else {
                //alertify.error("Carácter Inválido");
                var re = /[a-zA-ZñáéíóúÁÉÍÓÚ´,*+?^$&!¡¿#%/{}()='|[\]\\"]/g;
                document.getElementById(id).value = text.replace(re, '');
                text = document.getElementById(id).value;
                var long1 = text.length;
                var count = 1;
                if(long1 !== 0){
                    while (!expreg3.test(text)){
                        if(count !== 5){
                            var long = text.length;
                            var text_to_extract = long - 1;
                            document.getElementById(id).value = text.substring(0, text_to_extract);
                            text = document.getElementById(id).value;
                            count++;
                        } else {
                            document.getElementById(id).value = '0';
                            return false;
                        }
                    }
                }
                return false;
            }
        }

    }
}
//Función para que todo el texto de un cuadro sea en mayuscula
//Llamar así: onkeyup="mayuscula(this.id)"
function mayuscula(id) {
    var texto = document.getElementById(id).value;
    document.getElementById(id).value = texto.toUpperCase();
}
// /Llamar así: onchange="return validar_solo_texto(this.id)"
function validar_solo_texto(id) {
    var text = document.getElementById(id).value;
    var expreg = new RegExp(/^[a-zA-ZÀ-ÿ\u00f1\u00d1]+(\s*[a-zA-ZÀ-ÿ\u00f1\u00d1]*)*[a-zA-ZÀ-ÿ\u00f1\u00d1]+$/);
    if(expreg.test(text)){
        return true;
    } else {
        error("El texto contiene carácteres no válidos.");
        document.getElementById(id).value = '';
        return false;
    }
}
//Llamar así: onchange="return validar_correo(this.id)"
function validar_correo(id) {
    var text = document.getElementById(id).value;
    var expreg = new RegExp(/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/);
    if(expreg.test(text)){
        return true;
    } else {
        error("Formato de Correo Inválido");
        document.getElementById(id).value = '';
        return false;
    }
}




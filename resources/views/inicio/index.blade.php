@extends('layouts.plantilla_inicio')
@section('title','Panel de Inicio')
@section('content_inicio')


    <!-- Modal -->
<div class="container-fluid p-0 mb-5 wow fadeIn" data-wow-delay="0.1s">
    <div id="header-carousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            @php $a = 1; @endphp
            @foreach($banner as $b)
                @if($a == 1)
                    <div class="carousel-item active">
                    @php $a++; @endphp
                @else
                    <div class="carousel-item ">
                @endif
                        <img class="w-100" src="{{asset( $idioma == "es" ? $b->banner_inicio_fotografia : $b->banner_inicio_fotografia_in)}}" alt="Image">
                        <div class="carousel-caption">
                            <div class="container">
                                <div class="row justify-content-start">
                                    <div class="col-lg-7">
{{--                                        <h1 class="display-2 mb-5 animated slideInDown "><span class="border_letras">{{$b->banner_inicio_titulo}}</span></h1>--}}
{{--                                        <h1 class="display-3 mb-5 animated slideInDown ">{{$b->banner_inicio_titulo}}</h1>--}}
{{--                                        <a href="{{route('inicio.productos')}}" class="btn btn-primary rounded-pill py-sm-3 px-sm-5">Productos</a>--}}
{{--                                        <a href="" class="btn btn-secondary rounded-pill py-sm-3 px-sm-5 ms-3 d-none">Servicios</a>--}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            @endforeach
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel"
                data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#header-carousel"
                data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</div>
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <div id="header-carousel2" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            @php $e = 1; @endphp
                            @foreach($recursos as $re)
                                    <div class="carousel-item {{$e == 1 ? 'active' : ''}} ">
                                        <div class="row">
                                            <div class="col-lg-5 col-md-12 col-sm-12">
                                                <img class="w-100" src="{{asset($re->recursos_foto)}}" alt="Image">
                                            </div>
                                            <div class="col-lg-6 col-md-12 col-sm-12">
                                                <h1 class="display-3 mb-4 animated slideInDown ">{{$re->recursos_nombre}}</h1>
                                                <p class="mb-4" style="font-size: 18px!important;">@php echo $re->recursos_descripcion @endphp</p>
                                                @if(count($re->propiedades) > 0)
                                                    <h5 class=" mb-4">{{$idioma == "es" ? 'Entre sus atributos se incluyen: ' : 'Its attributes include:'}}</h5>
                                                    <div class="row">
                                                        @php $ee = 1; @endphp
                                                        @foreach($re->propiedades as $d)
                                                            <div class="col-lg-3 col-md-12 col-sm-12">
                                                                <i class="fa fa-check-circle"></i> {{$d->nutrientes_nombre}}
                                                            </div>
                                                            @php $ee++ @endphp
                                                        @endforeach
                                                    </div>
{{--                                                    <table class="table table-hover">--}}
{{--                                                        <thead>--}}
{{--                                                        <tr>--}}
{{--                                                            <th></th>--}}
{{--                                                            <th></th>--}}
{{--                                                        </tr>--}}
{{--                                                        </thead>--}}
{{--                                                        <tbody>--}}
{{--                                                        @php $ee = 1; @endphp--}}
{{--                                                        @foreach($re->propiedades as $d)--}}
{{--                                                            <tr>--}}
{{--                                                                <td>{{$ee}}</td>--}}
{{--                                                                <td>{{$d->nutrientes_nombre}}</td>--}}
{{--                                                            </tr>--}}
{{--                                                            @php $ee++ @endphp--}}
{{--                                                        @endforeach--}}
{{--                                                        </tbody>--}}
{{--                                                    </table>--}}

                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                @php $e++; @endphp
                            @endforeach
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel2"
                                data-bs-slide="prev">
                            <span class="carousel-control-prev-icon d-none" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#header-carousel2"
                                data-bs-slide="next">
                            <span class="carousel-control-next-icon d-none" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
{{--    <div class="container-xxl py-5">--}}
{{--        <div class="container">--}}
{{--            <div class="row g-5 align-items-center">--}}
{{--                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">--}}
{{--                    <div class="about-img position-relative overflow-hidden p-5 pe-0">--}}
{{--                        <img class="img-fluid w-100" src="{{asset($curiosidades[0]->curiosidades_fotografia)}}">--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">--}}
{{--                    @if($curiosidades[0]->curiosidades_titulo != null)--}}
{{--                        <h1 class="display-5 mb-4">{{$curiosidades[0]->curiosidades_titulo}}</h1>--}}
{{--                    @endif--}}
{{--                    <p class="mb-4">{{$curiosidades[0]->curiosidades_descripcion}}</p>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    https://vm.tiktok.com/ZM2EvpSBt/--}}
{{--    <iframe src="https://www.tiktok.com/embed/v2/ZM2EvpSBt/" width="640" height="360" frameborder="0" allowfullscreen  loading="lazy"></iframe>--}}

{{--<!-- Feature Start -->--}}
{{--<div class="container-fluid bg-light bg-icon py-6">--}}
{{--    <div class="container">--}}
{{--        <div class="section-header text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">--}}
{{--            <h1 class="display-5 mb-3">{{$valores[0]->nosotros_valores_titulo}}</h1>--}}
{{--            <p>{{$valores[0]->nosotros_valores_descripcion}}</p>--}}
{{--        </div>--}}
{{--        <div class="row g-4">--}}
{{--            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">--}}
{{--                <div class="bg-white text-center h-100 p-4 p-xl-5">--}}
{{--                    <img class="img-fluid mb-4" src="{{asset('inicio/img/integridad.png')}}" alt="">--}}
{{--                    <h4 class="mb-3">{{$valores[1]->nosotros_valores_titulo}}</h4>--}}
{{--                    <p class="mb-4">{{$valores[1]->nosotros_valores_descripcion}}</p>--}}
{{--                    --}}{{--                        <a class="btn btn-outline-primary border-2 py-2 px-4 rounded-pill" href="">Leer más</a>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">--}}
{{--                <div class="bg-white text-center h-100 p-4 p-xl-5">--}}
{{--                    <img class="img-fluid mb-4" src="{{asset('inicio/img/calidad.png')}}" alt="">--}}
{{--                    <h4 class="mb-3">{{$valores[2]->nosotros_valores_titulo}}</h4>--}}
{{--                    <p class="mb-4">{{$valores[2]->nosotros_valores_descripcion}}</p>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.5s">--}}
{{--                <div class="bg-white text-center h-100 p-4 p-xl-5">--}}
{{--                    <img class="img-fluid mb-4" src="{{asset('inicio/img/superacion.png')}}" alt="">--}}
{{--                    <h4 class="mb-3">{{$valores[3]->nosotros_valores_titulo}}</h4>--}}
{{--                    <p class="mb-4">{{$valores[3]->nosotros_valores_descripcion}}</p>--}}

{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}
{{--<!-- Feature End -->--}}


{{--<!-- Product Start -->--}}
{{--    <div class="container-xxl py-5">--}}
{{--        <div class="container">--}}
{{--            <div class="row g-0 gx-5 align-items-end">--}}
{{--                <div class="col-lg-6">--}}
{{--                    <div class="section-header text-start mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">--}}
{{--                        <h1 class="display-5 mb-3" style="font-family: 'Arial'">Nuestros productos  </h1>--}}
{{--                        <p style="font-family: 'Arial'">Explora nuestro amplio catálogo y descubrir Nuestra Exclusiva Gama de Productos</p>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--                <div class="col-lg-6 text-start text-lg-end wow slideInRight" data-wow-delay="0.1s">--}}
{{--                    <ul class="nav nav-pills d-inline-flex justify-content-end mb-5">--}}
{{--                        @php $a = 1 @endphp--}}
{{--                        @foreach($categorias as $c)--}}
{{--                            <li class="nav-item me-2">--}}
{{--                                <a class="btn btn-outline-primary border-2 {{ $a == 1 ? 'active' : '' }}" data-bs-toggle="pill" href="#tab-{{$c->id_categoria}}">{{$c->categoria_nombre}}</a>--}}
{{--                            </li>--}}
{{--                            @php $a++ @endphp--}}
{{--                        @endforeach--}}

{{--                    </ul>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-content">--}}
{{--                @php $e = 1 @endphp--}}
{{--                @foreach($categorias as $c)--}}
{{--                    <div id="tab-{{$c->id_categoria}}" class="tab-pane fade show p-0 {{ $e == 1 ? 'active' : '' }}">--}}
{{--                        <div class="row g-4">--}}
{{--                            @foreach($c->productos as $p)--}}
{{--                                <div class="col-xl-3 col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">--}}
{{--                                    <div class="product-item">--}}
{{--                                        <a href="{{route('inicio.detalle',base64_encode($p->codigo_barra))}}">--}}
{{--                                            <div class="position-relative bg-light overflow-hidden">--}}
{{--                                                <img class="img-fluid w-100 imagen_border" src="{{asset($p->producto_foto)}}" alt="">--}}
{{--                                            </div>--}}
{{--                                        </a>--}}
{{--                                        <div class=" p-3">--}}
{{--                                            <a class="d-block h5 " style="height: 50px;font-family: 'Arial'" href="{{route('inicio.detalle',base64_encode($p->codigo_barra))}}">{{$p->recetas_nombre}}</a>--}}
{{--                                            <p class=" text-justify" style="height: 200px!important;font-family: 'Arial'">{{$p->description_producto}}</p>--}}
{{--                                        </div>--}}
{{--                                        <div class="d-flex border-top justify-content-between align-items-center p-2">--}}
{{--                                            <small class=" text-center ">--}}
{{--                                                <a class="text-body" href="{{route('inicio.producto_detalle',base64_encode($p->codigo_barra))}}"><i class="fa-regular fa-rectangle-list text-primary me-2"></i>Ver detalle</a>--}}
{{--                                            </small>--}}
{{--                                            |--}}
{{--                                            <small class=" text-center">--}}
{{--                                                <a class="text-body" href="{{route('inicio.detalle',base64_encode($p->codigo_barra))}}"><i class="fa fa-shopping-bag text-primary me-2"></i>Añadir a la cesta</a>--}}
{{--                                            </small>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}

{{--                            @endforeach--}}
{{--                            --}}{{--                    <div class="col-xl-3 col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">--}}
{{--                            --}}{{--                        <div class="product-item">--}}
{{--                            --}}{{--                            <a href="{{route('inicio.detalle')}}">--}}
{{--                            --}}{{--                                <div class="position-relative bg-light overflow-hidden">--}}
{{--                            --}}{{--                                    <img class="img-fluid w-100 imagen_border" src="{{asset('inicio/img/producto_2_miski.png')}}" alt="">--}}
{{--                            --}}{{--                                    <div class="bg-secondary rounded text-white position-absolute start-0 top-0 m-4 py-1 px-3">Nuevo</div>--}}
{{--                            --}}{{--                                </div>--}}
{{--                            --}}{{--                            </a>--}}

{{--                            --}}{{--                            <div class="text-center p-4">--}}
{{--                            --}}{{--                                <a class="d-block h5 mb-2" href="{{route('inicio.detalle')}}">NECTAR DE SABILA Y LIMON</a>--}}
{{--                            --}}{{--                                <span class="text-primary me-1">S/4.00</span>--}}
{{--                            --}}{{--                                <span class="text-body text-decoration-line-through">S/5.00</span>--}}
{{--                            --}}{{--                            </div>--}}
{{--                            --}}{{--                            <div class="d-flex border-top">--}}

{{--                            --}}{{--                                <small class="w-75 text-center py-2">--}}
{{--                            --}}{{--                                    <a class="text-body" href=""><i class="fa fa-shopping-bag text-primary me-2"></i>Comprar</a>--}}
{{--                            --}}{{--                                </small>--}}
{{--                            --}}{{--                            </div>--}}
{{--                            --}}{{--                        </div>--}}
{{--                            --}}{{--                    </div>--}}

{{--                        </div>--}}
{{--                    </div>--}}
{{--                    @php $e++ @endphp--}}
{{--                @endforeach--}}

{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--<!-- Product End -->--}}


<!-- Firm Visit Start -->
<!-- Firm Visit End -->


<!-- Testimonial Start -->
{{--<div class="container-fluid bg-light bg-icon py-6 mb-5">--}}
{{--    <div class="container">--}}
{{--        <div class="section-header text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">--}}
{{--            <h1 class="display-5 mb-3">Opinión del cliente</h1>--}}
{{--            <p>Tempor ut dolore lorem kasd vero ipsum sit eirmod sit. Ipsum diam justo sed rebum vero dolor duo.</p>--}}
{{--        </div>--}}

{{--        <div class="owl-carousel testimonial-carousel wow fadeInUp" data-wow-delay="0.1s">--}}
{{--            @foreach($clientes as $c)--}}
{{--                <div class="testimonial-item position-relative bg-white p-5 mt-4">--}}
{{--                    <i class="fa fa-quote-left fa-3x text-primary position-absolute top-0 start-0 mt-n4 ms-5"></i>--}}
{{--                    <p class="mb-4">{{$c->clientes_miski_descripcion}}</p>--}}
{{--                    <div class="d-flex align-items-center">--}}
{{--                        <img class="flex-shrink-0 rounded-circle" src="{{asset($c->clientes_miski_fotografia)}}" alt="">--}}
{{--                        <div class="ms-3">--}}
{{--                            <h5 class="mb-1">{{$c->clientes_miski_nombre}}</h5>--}}
{{--                            <span>Contadora</span>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            @endforeach--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}
<!-- Testimonial End -->


<!-- Blog Start -->
{{--<div class="container-xxl py-5">--}}
{{--    <div class="container">--}}
{{--        <div class="section-header text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">--}}
{{--            <h1 class="display-5 mb-3">Blog más reciente</h1>--}}
{{--            <p>Tempor ut dolore lorem kasd vero ipsum sit eirmod sit. Ipsum diam justo sed rebum vero dolor duo.</p>--}}
{{--        </div>--}}
{{--        <div class="row g-4">--}}
{{--            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">--}}
{{--                <img class="img-fluid" src="{{asset('inicio/img/blog-1.jpg')}}" alt="">--}}
{{--                <div class="bg-light p-4">--}}
{{--                    <a class="d-block h5 lh-base mb-4" href="">Cómo cultivar frutas y verduras orgánicas</a>--}}
{{--                    <div class="text-muted border-top pt-4">--}}
{{--                        <small class="me-3"><i class="fa fa-user text-primary me-2"></i>Administradora</small>--}}
{{--                        <small class="me-3"><i class="fa fa-calendar text-primary me-2"></i>01 Jan, 2045</small>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">--}}
{{--                <img class="img-fluid" src="{{asset('inicio/img/blog-2.jpg')}}" alt="">--}}
{{--                <div class="bg-light p-4">--}}
{{--                    <a class="d-block h5 lh-base mb-4" href="">Cómo cultivar frutas y verduras orgánicas</a>--}}
{{--                    <div class="text-muted border-top pt-4">--}}
{{--                        <small class="me-3"><i class="fa fa-user text-primary me-2"></i>Administradora</small>--}}
{{--                        <small class="me-3"><i class="fa fa-calendar text-primary me-2"></i>01 Jan, 2045</small>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.5s">--}}
{{--                <img class="img-fluid" src="{{asset('inicio/img/blog-3.jpg')}}" alt="">--}}
{{--                <div class="bg-light p-4">--}}
{{--                    <a class="d-block h5 lh-base mb-4" href="">Cómo cultivar frutas y verduras orgánicas</a>--}}
{{--                    <div class="text-muted border-top pt-4">--}}
{{--                        <small class="me-3"><i class="fa fa-user text-primary me-2"></i>Administradora</small>--}}
{{--                        <small class="me-3"><i class="fa fa-calendar text-primary me-2"></i>01 Jan, 2045</small>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}

<!-- Blog End -->

    <script>
        let oferta = ' {{$oferta_descuento}}'
        $(document).ready(function(){
            if(oferta.length > 0){
                $('#exampleModal').modal('show');
            }
        })
    </script>


@endsection

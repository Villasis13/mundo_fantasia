@extends('layouts.plantilla')
@section('content')

<style>
    /* ─── Librería de Íconos ──────────────────────────────────── */
    #iconos-wrap .card-header { padding: 1.25rem 1.5rem 0.75rem; }

    #iconos-wrap h3.text-primary {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #6c757d !important;
        margin: 0 0 0.85rem;
        padding-left: 0.65rem;
        border-left: 3px solid #0d6efd;
    }

    #iconos-wrap .card-body { padding: 1.25rem 1.5rem 0.5rem; }

    #iconos-wrap .col-lg-3 {
        margin-bottom: 0 !important;
        padding: 0.15rem 0.25rem;
    }

    #iconos-wrap .col-lg-3 a {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.38rem 0.6rem;
        border-radius: 6px;
        font-size: 0.78rem;
        color: #495057;
        text-decoration: none;
        cursor: pointer;
        transition: background .12s, color .12s;
        user-select: none;
        overflow: hidden;
        white-space: nowrap;
    }

    #iconos-wrap .col-lg-3 a:hover {
        background: #eff3ff;
        color: #0d6efd;
    }

    #iconos-wrap .col-lg-3 a.copiado {
        background: #d1e7dd !important;
        color: #0a3622 !important;
    }

    #iconos-wrap .col-lg-3 a i {
        font-size: 1rem !important;
        width: 18px;
        text-align: center;
        flex-shrink: 0;
        transition: color .12s;
    }

    #iconos-wrap .col-lg-3 a:hover i { color: #0d6efd; }

    /* Toast notificación */
    #toast-icono {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 9999;
        min-width: 240px;
        opacity: 0;
        transform: translateY(8px);
        transition: opacity .18s, transform .18s;
        pointer-events: none;
    }
    #toast-icono.visible {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
</style>

    <div id="iconos-wrap" class="col-12 mb-4">
        <div class="card border-0 shadow-sm">

            {{-- Cabecera --}}
            <div class="card-header bg-white border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0 fw-bold">
                            <i class="fa-solid fa-icons me-2 text-primary"></i>
                            Librería de Íconos
                        </h5>
                        <small class="text-muted">
                            Referencia de Font Awesome 6 — haz clic en un ícono para copiar su clase al portapapeles.
                            <span id="contadorIconos" class="fw-semibold text-primary"></span>
                        </small>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
                    <div class="input-group input-group-sm" style="max-width:340px;">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text"
                               id="buscarIcono"
                               class="form-control border-start-0 bg-light"
                               placeholder="Buscar por nombre de clase...">
                        <button class="btn btn-outline-secondary" id="limpiarBusqueda" type="button" style="display:none;">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <small id="resultadoBusqueda" class="text-muted"></small>
                </div>
            </div>

            {{-- Aviso sin resultados --}}
            <div id="sin-resultados" class="text-center text-muted py-5" style="display:none;">
                <i class="fa-solid fa-magnifying-glass fa-2x mb-2 d-block opacity-25"></i>
                No se encontraron íconos que coincidan con la búsqueda.
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Accesibilidad</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-info"></i>fa fa-circle-info
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-eye"></i> fa fa-eye
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-eye"></i>fa-regular fa-eye
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-question"></i>fa fa-question
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-phone-volume"></i>fa fa-phone-volume
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-address-card"></i>fa fa-address-card
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-address-card"></i>fa-regular fa-address-card
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tty"></i>fa fa-tty
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hands"></i>fa fa-hands
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-audio-description"></i>fa fa-audio-description
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-accessible-icon"></i>fa-brands fa-accessible-icon
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-closed-captioning"></i>fa fa-closed-captioning
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-closed-captioning"></i>fa-regular fa-closed-captioning
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fingerprint"></i>fa fa-fingerprint
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wheelchair-move"></i>fa fa-wheelchair-move
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wheelchair"></i>fa fa-wheelchair
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-universal-access"></i>fa fa-universal-access
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-walking-with-cane"></i>fa fa-person-walking-with-cane
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-cane"></i>fa fa-person-cane
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-handshake-angle"></i>fa fa-handshake-angle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hands-asl-interpreting"></i>fa fa-hands-asl-interpreting
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-eye-low-vision"></i>fa fa-eye-low-vision
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ear-listen"></i>fa fa-ear-listen
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ear-deaf"></i>fa fa-ear-deaf
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-question"></i>fa fa-circle-question
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-braille"></i>fa fa-braille
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-circle-question"></i>fa-regular fa-circle-question
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Alerta</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bell"></i>fa fa-bell
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bell"></i>fa fa-bell
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bell-slash"></i>fa fa-bell-slash
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-bell-slash"></i>fa-regular fa-bell-slash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-exclamation"></i>fa fa-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-radiation"></i>fa fa-circle-radiation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-exclamation"></i>fa fa-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-radiation"></i>fa fa-radiation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-skull-crossbones"></i>fa fa-skull-crossbones
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-triangle-exclamation"></i>fa fa-triangle-exclamation
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Alfabeto</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-a"></i>fa fa-a
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-b"></i>fa fa-b
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-c"></i>fa fa-c
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-h"></i>fa fa-circle-h
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-d"></i>fa fa-d
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-e"></i>fa fa-e
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-f"></i>fa fa-f
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-g"></i>fa fa-g
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-h"></i>fa fa-h
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-i"></i>fa fa-i
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-j"></i>fa fa-j
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-k"></i>fa fa-k
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-l"></i>fa fa-l
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-m"></i>fa fa-m
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-n"></i>fa fa-n
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-o"></i>fa fa-o
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-p"></i>fa fa-p
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-q"></i>fa fa-q
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-r"></i>fa fa-r
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-s"></i>fa fa-s
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-h"></i>fa fa-square-h
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-t"></i>fa fa-t
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-u"></i>fa fa-u
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-v"></i>fa fa-v
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-w"></i>fa fa-w
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-x"></i>fa fa-x
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-y"></i>fa fa-y
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-z"></i>fa fa-z
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Animales</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bugs"></i>fa fa-bugs
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cat"></i>fa fa-cat
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cow"></i>fa fa-cow
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-crow"></i>fa fa-crow
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dog"></i>fa fa-dog
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dove"></i>fa fa-dove
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dragon"></i>fa fa-dragon
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-feather"></i>fa fa-feather
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-feather-pointed"></i>fa fa-feather-pointed
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fish"></i>fa fa-fish
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fish-fins"></i>fa fa-fish-fins
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-frog"></i>fa fa-frog
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hippo"></i>fa fa-hippo
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-horse"></i>fa fa-horse
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-horse-head"></i>fa fa-horse-head
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-kiwi-bird"></i>fa fa-kiwi-bird
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-locust"></i>fa fa-locust
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mosquito"></i>fa fa-mosquito
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-otter"></i>fa fa-otter
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-paw"></i>fa fa-paw
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shield-cat"></i>fa fa-shield-cat
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shield-dog"></i>fa fa-shield-dog
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shrimp"></i>fa fa-shrimp
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-spider"></i>fa fa-spider
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-worm"></i>fa fa-worm
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Flechas</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-angle-down"></i>fa fa-angle-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-angle-left"></i>fa fa-angle-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-angle-right"></i>fa fa-angle-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-angle-up"></i>fa fa-angle-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-angles-down"></i>fa fa-angles-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-angles-left"></i>fa fa-angles-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-angles-right"></i>fa fa-angles-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-angles-up"></i>fa fa-angles-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-down"></i>fa fa-arrow-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-down-long"></i>fa fa-arrow-down-long
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-down-1-9"></i>fa fa-arrow-down-1-9
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-down-9-1"></i>fa fa-arrow-down-9-1
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-down-a-z"></i>fa fa-arrow-down-a-z
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-down-z-a"></i>fa fa-arrow-down-z-a
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-down-short-wide"></i>fa fa-arrow-down-short-wide
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-down-wide-short"></i>fa fa-arrow-down-wide-short
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-down-up-across-line"></i>fa fa-arrow-down-up-across-line
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-down-up-lock"></i>fa fa-arrow-down-up-lock
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-left"></i>fa fa-arrow-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-left-long"></i>fa fa-arrow-left-long
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-right"></i>fa fa-arrow-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-right-long"></i>fa fa-arrow-right-long
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-pointer"></i>fa fa-arrow-pointer
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-right-arrow-left"></i>fa fa-arrow-right-arrow-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-right-from-bracket"></i>fa fa-arrow-right-from-bracket
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-right-to-bracket"></i>fa fa-arrow-right-to-bracket
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-rotate-left"></i>fa fa-arrow-rotate-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-rotate-right"></i>fa fa-arrow-rotate-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-trend-down"></i>fa fa-arrow-trend-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-trend-up"></i>fa fa-arrow-trend-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-turn-down"></i>fa fa-arrow-turn-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-turn-up"></i>fa fa-arrow-turn-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-up"></i>fa fa-arrow-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-up-long"></i>fa fa-arrow-up-long
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-up-1-9"></i>fa fa-arrow-up-1-9
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-up-9-1"></i>fa fa-arrow-up-9-1
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-up-a-z"></i>fa fa-arrow-up-a-z
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-up-z-a"></i>fa fa-arrow-up-z-a
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-up-from-bracket"></i>fa fa-arrow-up-from-bracket
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-up-right-dots"></i>fa fa-arrow-up-right-dots
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-up-right-from-square"></i>fa fa-arrow-up-right-from-square
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-up-short-wide"></i>fa fa-arrow-up-short-wide
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-up-wide-short"></i>fa fa-arrow-up-wide-short
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-down-to-line"></i>fa fa-arrows-down-to-line
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-left-right"></i>fa fa-arrows-left-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-left-right-to-line"></i>fa fa-arrows-left-right-to-line
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-rotate"></i>fa fa-arrows-rotate
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-spin"></i>fa fa-arrows-spin
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-split-up-and-left"></i>fa fa-arrows-split-up-and-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-to-circle"></i>fa fa-arrows-to-circle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-to-dot"></i>fa fa-arrows-to-dot
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-to-eye"></i>fa fa-arrows-to-eye
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-turn-right"></i>fa fa-arrows-turn-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-turn-to-dots"></i>fa fa-arrows-turn-to-dots
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-up-down"></i>fa fa-arrows-up-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-up-down-left-right"></i>fa fa-arrows-up-down-left-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrows-up-to-line"></i>fa fa-arrows-up-to-line
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-caret-down"></i>fa fa-caret-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-caret-up"></i>fa fa-caret-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-caret-left"></i>fa fa-caret-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-caret-right"></i>fa fa-caret-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chevron-down"></i>fa fa-chevron-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chevron-up"></i>fa fa-chevron-up
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chevron-left"></i>fa fa-chevron-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chevron-right"></i>fa fa-chevron-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-arrow-down"></i>fa fa-circle-arrow-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-arrow-up"></i>fa fa-circle-arrow-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-arrow-left"></i>fa fa-circle-arrow-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-arrow-right"></i>fa fa-circle-arrow-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-chevron-down"></i>fa fa-circle-chevron-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-chevron-left"></i>fa fa-circle-chevron-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-chevron-right"></i>fa fa-circle-chevron-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-chevron-up"></i>fa fa-circle-chevron-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-down"></i>fa fa-circle-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-up"></i>fa fa-circle-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-down"></i>fa fa-circle-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-circle-up"></i>fa-regular fa-circle-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-left"></i>fa fa-circle-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-right"></i>fa fa-circle-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-circle-left"></i>fa-regular fa-circle-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-circle-right"></i>fa-regular fa-circle-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-up"></i>fa fa-circle-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-circle-up"></i>fa-regular fa-circle-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-clock-rotate-left"></i>fa fa-clock-rotate-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cloud-arrow-down"></i>fa fa-cloud-arrow-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cloud-arrow-up"></i>fa fa-cloud-arrow-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-down-left-and-up-right-to-center"></i>fa fa-down-left-and-up-right-to-center
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-down-long"></i>fa fa-down-long
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-download"></i>fa fa-download
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-left-long"></i>fa fa-left-long
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-right-long"></i>fa fa-right-long
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-left-right"></i>fa fa-left-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-location-arrow"></i>fa fa-location-arrow
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-maximize"></i>fa fa-maximize
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-recycle"></i>fa fa-recycle
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-repeat"></i>fa fa-repeat
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-reply"></i>fa fa-reply
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-share"></i>fa fa-share
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-reply-all"></i>fa fa-reply-all
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-retweet"></i>fa fa-retweet
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-right-from-bracket"></i>fa fa-right-from-bracket
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-right-to-bracket"></i>fa fa-right-to-bracket
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-right-left"></i>fa fa-right-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-rotate"></i>fa fa-rotate
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-rotate-left"></i>fa fa-rotate-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-rotate-right"></i>fa fa-rotate-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-share-from-square"></i>fa fa-share-from-square
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-share-from-square"></i>fa-regular fa-share-from-square
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shuffle"></i>fa fa-shuffle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sort"></i>fa fa-sort
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sort-down"></i>fa fa-sort-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sort-up"></i>fa fa-sort-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-arrow-up-right"></i>fa fa-square-arrow-up-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-caret-down"></i>fa fa-square-caret-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-caret-up"></i>fa fa-square-caret-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-square-caret-down"></i>fa-regular fa-square-caret-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-square-caret-up"></i>fa-regular fa-square-caret-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-caret-left"></i>fa fa-square-caret-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-caret-right"></i>fa fa-square-caret-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-square-caret-left"></i>fa-regular fa-square-caret-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-square-caret-right"></i>fa-regular fa-square-caret-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-caret-up"></i>fa fa-square-caret-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-square-caret-up"></i>fa-regular fa-square-caret-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-up-right"></i>fa fa-square-up-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-turn-down"></i>fa fa-turn-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-turn-up"></i>fa fa-turn-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-up-down"></i>fa fa-up-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-up-down-left-right"></i>fa fa-up-down-left-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-up-long"></i>fa fa-up-long
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-up-right-and-down-left-from-center"></i>fa fa-up-right-and-down-left-from-center
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-up-right-from-square"></i>fa fa-up-right-from-square
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-upload"></i>fa fa-upload
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Astronomía</h3>
                    <div class="row">

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-binoculars"></i>fa fa-binoculars
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-globe"></i>fa fa-globe
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-meteor"></i>fa fa-meteor
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-moon"></i>fa fa-moon
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-moon"></i>fa-regular fa-moon
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-satellite"></i>fa fa-satellite
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-satellite-dish"></i>fa fa-satellite-dish
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shuttle-space"></i>fa fa-shuttle-space
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Automotor</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bus"></i>fa fa-bus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bus-simple"></i>fa fa-bus-simple
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-car"></i>fa fa-car
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-car-battery"></i>fa fa-car-battery
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-car-burst"></i>fa fa-car-burst
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-car-on"></i>fa fa-car-on
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-car-rear"></i>fa fa-car-rear
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-car-side"></i>fa fa-car-side
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-car-tunnel"></i>fa fa-car-tunnel
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-caravan"></i>fa fa-caravan
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-charging-station"></i>fa fa-charging-station
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-gas-pump"></i>fa fa-gas-pump
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-gauge"></i>fa fa-gauge
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-gauge-high"></i>fa fa-gauge-high
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-gauge-simple"></i>fa fa-gauge-simple
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-gauge-simple-high"></i>fa fa-gauge-simple-high
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-motorcycle"></i>fa fa-motorcycle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-oil-can"></i>fa fa-oil-can
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-spray-can-sparkles"></i>fa fa-spray-can-sparkles
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-taxi"></i>fa fa-taxi
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck"></i>fa fa-truck
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck-field"></i>fa fa-truck-field
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck-field-un"></i>fa fa-truck-field-un
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck-medical"></i>fa fa-truck-medical
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck-monster"></i>fa fa-truck-monster
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck-pickup"></i>fa fa-truck-pickup
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-van-shuttle"></i>fa fa-van-shuttle
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Edificios</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-archway"></i>fa fa-archway
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-right-to-city"></i>fa fa-arrow-right-to-city
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building"></i>fa fa-building
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-building"></i>fa-regular fa-building
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building-circle-arrow-right"></i>fa fa-building-circle-arrow-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building-circle-check"></i>fa fa-building-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building-circle-exclamation"></i>fa fa-building-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building-circle-xmark"></i>fa fa-building-circle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building-columns"></i>fa fa-building-columns
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building-flag"></i>fa fa-building-flag
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building-lock"></i>fa fa-building-lock
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building-ngo"></i>fa fa-building-ngo
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building-shield"></i>fa fa-building-shield
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building-un"></i>fa fa-building-un
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building-user"></i>fa fa-building-user
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building-wheat"></i>fa fa-building-wheat
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-campground"></i>fa fa-campground
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-church"></i>fa fa-church
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-city"></i>fa fa-city
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dungeon"></i>fa fa-dungeon
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-gopuram"></i>fa fa-gopuram
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hospital"></i>fa fa-hospital
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-hospital"></i>fa-regular fa-hospital
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hospital-user"></i>fa fa-hospital-user
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hotel"></i>fa fa-hotel
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house"></i>fa fa-house
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-chimney"></i>fa fa-house-chimney
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-chimney-crack"></i>fa fa-house-chimney-crack
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-chimney-medical"></i>fa fa-house-chimney-medical
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-chimney-window"></i>fa fa-house-chimney-window
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-circle-check"></i>fa fa-house-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-circle-exclamation"></i>fa fa-house-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-circle-xmark"></i>fa fa-house-circle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-crack"></i>fa fa-house-crack
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-fire"></i>fa fa-house-fire
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-flag"></i>fa fa-house-flag
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-lock"></i>fa fa-house-lock
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-medical"></i>fa fa-house-medical
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-medical-circle-check"></i>fa fa-house-medical-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-medical-circle-exclamation"></i>fa fa-house-medical-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-medical-circle-xmark"></i>fa fa-house-medical-circle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-medical-flag"></i>fa fa-house-medical-flag
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-igloo"></i>fa fa-igloo
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-industry"></i>fa fa-industry
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-kaaba"></i>fa fa-kaaba
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-landmark"></i>fa fa-landmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-landmark-dome"></i>fa fa-landmark-dome
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-landmark-flag"></i>fa fa-landmark-flag
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-monument"></i>fa fa-monument
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mosque"></i>fa fa-mosque
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mountain-city"></i>fa fa-mountain-city
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-oil-well"></i>fa fa-oil-well
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-place-of-worship"></i>fa fa-place-of-worship
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-school"></i>fa fa-school
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-school-circle-check"></i>fa fa-school-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-school-circle-exclamation"></i>fa fa-school-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-school-circle-xmark"></i>fa fa-school-circle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-school-flag"></i>fa fa-school-flag
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-school-lock"></i>fa fa-school-lock
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shop"></i>fa fa-shop
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shop-lock"></i>fa fa-shop-lock
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-store"></i>fa fa-store
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-synagogue"></i>fa fa-synagogue
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tent"></i>fa fa-tent
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tents"></i>fa fa-tents
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tent-arrow-down-to-line"></i>fa fa-tent-arrow-down-to-line
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tent-arrow-left-right"></i>fa fa-tent-arrow-left-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tent-arrow-turn-left"></i>fa fa-tent-arrow-turn-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tent-arrows-down"></i>fa fa-tent-arrows-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-toilet-portable"></i>fa fa-toilet-portable
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-toilets-portable"></i>fa fa-toilets-portable
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-torii-gate"></i>fa fa-torii-gate
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tower-observation"></i>fa fa-tower-observation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tree-city"></i>fa fa-tree-city
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-vihara"></i>fa fa-vihara
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-warehouse"></i>fa fa-warehouse
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Negocios</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-address-book"></i>fa fa-address-book
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-address-book"></i>fa-regular fa-address-book
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bars-progress"></i>fa-bars-progress
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bars-staggered"></i>fa fa-bars-staggered
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-book"></i>fa fa-book
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-box-archive"></i>fa fa-box-archive
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-boxes-packing"></i>fa fa-boxes-packing
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-briefcase"></i>fa fa-briefcase
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bullhorn"></i>fa fa-bullhorn
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bullseye"></i>fa fa-bullseye
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-business-time"></i>fa fa-business-time
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cake-candles"></i>fa fa-cake-candles
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-calculator"></i>fa fa-calculator
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-calendar"></i>fa fa-calendar
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-calendar"></i>fa-regular fa-calendar
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-calendar-days"></i>fa fa-calendar-days
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-calendar-days"></i>fa-regular fa-calendar-days
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-certificate"></i>fa fa-certificate
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chart-line"></i>fa fa-chart-line
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chart-pie"></i>fa fa-chart-pie
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chart-simple"></i>fa fa-chart-simple
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-city"></i>fa fa-city
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-clipboard"></i>fa fa-clipboard
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-clipboard"></i>fa-regular fa-clipboard
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-clipboard-question"></i>fa fa-clipboard-question
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-compass"></i>fa fa-compass
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-compass"></i>fa-regular fa-compass
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-copy"></i>fa fa-copy
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-copy"></i>fa-regular fa-copy
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-copyright"></i>fa fa-copyright
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-copyright"></i>fa-regular fa-copyright
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-envelope"></i>fa fa-envelope
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-envelope"></i>fa-regular fa-envelope
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-envelope-circle-check"></i>fa fa-envelope-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-envelope-open"></i>fa fa-envelope-open
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-envelope-open"></i>fa-regular fa-envelope-open
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-eraser"></i>fa fa-eraser
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fax"></i>fa fa-fax
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file"></i>fa fa-file
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-file"></i>fa-regular fa-file
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-circle-plus"></i>fa-file-circle-plus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-lines"></i>fa fa-file-lines
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-file-lines"></i>fa-regular fa-file-lines
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-floppy-disk"></i>fa fa-floppy-disk
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-floppy-disk"></i>fa-regular fa-floppy-disk
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-folder"></i>fa fa-folder
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-folder"></i>fa-regular fa-folder
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-folder-minus"></i>fa fa-folder-minus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-folder-open"></i>fa fa-folder-open
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-folder-open"></i>fa-regular fa-folder-open
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-folder-plus"></i>fa fa-folder-plus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-folder-tree"></i>fa fa-folder-tree
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-glasses"></i>fa fa-glasses
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-globe"></i>fa fa-globe
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-highlighter"></i>fa fa-highlighter
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-laptop"></i>fa fa-house-laptop
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-laptop-file"></i>fa fa-laptop-file
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-list-check"></i>fa fa-list-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-magnifying-glass-arrow-right"></i>fa fa-magnifying-glass-arrow-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-magnifying-glass-chart"></i>fa fa-magnifying-glass-chart
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-marker"></i>fa fa-marker
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-network-wired"></i>fa fa-network-wired
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-note-sticky"></i>fa fa-note-sticky
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-note-sticky"></i>fa-regular fa-note-sticky
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-paperclip"></i>fa fa-paperclip
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-paste"></i>fa fa-paste
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-paste"></i>fa-regular fa-paste
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-pen"></i>fa fa-pen
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-pen-clip"></i>fa fa-pen-clip
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-pen-fancy"></i>fa fa-pen-fancy
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-pen-nib"></i>fa fa-pen-nib
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-pen-to-square"></i>fa fa-pen-to-square
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-pen-to-square"></i>fa-regular fa-pen-to-square
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-pencil"></i>fa fa-pencil
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-percent"></i>fa fa-percent
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-chalkboard"></i>fa fa-person-chalkboard
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-phone"></i>fa fa-phone
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-phone-flip"></i>fa fa-phone-flip
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-phone-slash"></i>fa fa-phone-slash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-print"></i>fa fa-print
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-registered"></i>fa fa-registered
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-registered"></i>fa fa-registered
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-scale-balanced"></i>fa fa-scale-balanced
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-scale-unbalanced"></i>fa fa-scale-unbalanced
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-scale-unbalanced-flip"></i>fa fa-scale-unbalanced-flip
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-scissors"></i>fa fa-scissors
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-signature"></i>fa fa-signature
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sitemap"></i>fa fa-sitemap
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-socks"></i>fa fa-socks
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-envelope"></i>fa fa-square-envelope
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-envelope"></i>fa fa-square-envelope
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-pen"></i>fa fa-square-pen
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-phone"></i>fa fa-square-phone
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-phone-flip"></i>fa fa-square-phone-flip
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-poll-horizontal"></i>fa fa-square-poll-horizontal
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-poll-vertical"></i>fa fa-square-poll-vertical
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-stapler"></i>fa fa-stapler
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-table"></i>fa fa-table
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-table-columns"></i>fa fa-table-columns
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tag"></i>fa fa-tag
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tags"></i>fa fa-tags
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-thumbtack"></i>fa fa-thumbtack
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-timeline"></i>fa fa-timeline
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-trademark"></i>fa fa-trademark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-vault"></i>fa fa-vault
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wallet"></i>fa fa-wallet
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Camping</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-binoculars"></i>fa fa-binoculars
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bottle-water"></i>fa fa-bottle-water
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bucket"></i>fa fa-bucket
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-campground"></i>fa fa-campground
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-caravan"></i>fa fa-caravan
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-faucet"></i>fa fa-faucet
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-faucet-drip"></i>fa fa-faucet-drip
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fire"></i>fa fa-fire
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fire-burner"></i>fa fa-fire-burner
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fire-flame-curved"></i>fa fa-fire-flame-curved
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-frog"></i>fa fa-frog
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-kit-medical"></i>fa fa-kit-medical
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-map"></i>fa fa-map
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-map"></i>fa-regular fa-map
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-map-location"></i>fa fa-map-location
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-map-location-dot"></i>fa fa-map-location-dot
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mattress-pillow"></i>fa fa-mattress-pillow
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mosquito"></i>fa fa-mosquito
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mosquito-net"></i>fa fa-mosquito-net
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mountain"></i>fa fa-mountain
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mountain-sun"></i>fa fa-mountain-sun
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-people-roof"></i>fa fa-people-roof
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-hiking"></i>fa fa-person-hiking
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-shelter"></i>fa fa-person-shelter
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-signs-post"></i>fa fa-signs-post
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tarp"></i>fa fa-tarp
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tarp-droplet"></i>fa fa-tarp-droplet
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-toilet-paper"></i>fa fa-toilet-paper
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tree"></i>fa fa-tree
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Caridad</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-dollar-to-slot"></i>fa fa-circle-dollar-to-slot
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dollar-sign"></i>fa fa-dollar-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-gift"></i>fa fa-gift
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-gifts"></i>fa fa-gifts
                            </a>
                        </div>


                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-holding-dollar"></i>fa fa-hand-holding-dollar
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-holding-droplet"></i>fa fa-hand-holding-droplet
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-holding-hand"></i>fa fa-hand-holding-hand
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-holding-heart"></i>fa fa-hand-holding-heart
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hands-holding-child"></i>fa fa-hands-holding-child
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hands-holding-circle"></i>fa fa-hands-holding-circle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-handshake-angle"></i>fa fa-handshake-angle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-handshake-simple"></i>fa fa-handshake-simple
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-heart"></i>fa fa-heart
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-heart"></i>fa-regular fa-heart
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-leaf"></i>fa fa-leaf
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-parachute-box"></i>fa fa-parachute-box
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-piggy-bank"></i>fa fa-piggy-bank
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ribbon"></i>fa fa-ribbon
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-seedling"></i>fa fa-seedling
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Cuadros + Diagramas</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chart-area"></i>fa fa-chart-area
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chart-bar"></i>fa fa-chart-bar
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-chart-bar"></i>fa-regular fa-chart-bar
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chart-column"></i>fa fa-chart-column
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chart-gantt"></i>fa fa-chart-gantt
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chart-line"></i>fa fa-chart-line
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chart-pie"></i>fa fa-chart-pie
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chart-simple"></i>fa fa-chart-simple
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-half-stroke"></i>fa fa-circle-half-stroke
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-diagram-next"></i>fa fa-diagram-next
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-diagram-predecessor"></i>fa fa-diagram-predecessor
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-diagram-successor"></i>fa fa-diagram-successor
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-diagram-project"></i>fa fa-diagram-project
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-poll-horizontal"></i>fa fa-square-poll-horizontal
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-poll-vertical"></i>fa fa-square-poll-vertical
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Infancia</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-apple-whole"></i>fa fa-apple-whole
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-baby"></i>fa fa-baby
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-baby-carriage"></i>fa fa-baby-carriage
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-baseball-bat-ball"></i>fa fa-baseball-bat-ball
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bath"></i>fa fa-bath
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bucket"></i>fa fa-bucket
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-child"></i>fa fa-child
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-child-dress"></i>fa fa-child-dress
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-child-reaching"></i>fa fa-child-reaching
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-children"></i>fa fa-children
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cookie"></i>fa fa-cookie
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cookie-bite"></i>fa fa-cookie-bite
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cubes-stacked"></i>fa fa-cubes-stacked
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-gamepad"></i>fa fa-gamepad
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ice-cream"></i>fa fa-ice-cream
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mitten"></i>fa fa-mitten
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-biking"></i>fa fa-person-biking
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-breastfeeding"></i>fa fa-person-breastfeeding
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-puzzle-piece"></i>fa fa-puzzle-piece
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-robot"></i>fa fa-robot
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shapes"></i>fa fa-shapes
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-snowman"></i>fa fa-snowman
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Ropa y Moda</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-graduation-cap"></i>fa fa-graduation-cap
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hat-cowboy"></i>fa fa-hat-cowboy
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hat-cowboy-side"></i>fa fa-hat-cowboy-side
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shirt"></i>fa fa-shirt
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shoe-prints"></i>fa fa-shoe-prints
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-vest"></i>fa fa-vest
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-vest-patches"></i>fa fa-vest-patches
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Codificación</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-barcode"></i>fa fa-barcode
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bug"></i>fa fa-bug
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bug-slash"></i>fa fa-bug-slash
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-nodes"></i>fa fa-circle-nodes
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-code"></i>fa fa-code
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-code-branch"></i>fa fa-code-branch
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-code-commit"></i>fa fa-code-commit
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-code-compare"></i>fa fa-code-compare
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-code-fork"></i>fa fa-code-fork
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-code-merge"></i>fa fa-code-merge
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-code-pull-request"></i>fa fa-code-pull-request
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cube"></i>fa fa-cube
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cubes"></i>fa fa-cubes
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-code"></i>fa fa-file-code
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-file-code"></i>fa-regular fa-file-code
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-filter"></i>fa fa-filter
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fire-extinguisher"></i>fa fa-fire-extinguisher
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-font-awesome"></i>fa fa-font-awesome
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-font-awesome"></i>fa-regular fa-font-awesome
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-font-awesome"></i>fa-brands fa-font-awesome
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-gear"></i>fa fa-gear
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-gears"></i>fa fa-gears
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-keyboard"></i>fa fa-keyboard
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-keyboard"></i>fa-regular fa-keyboard
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-laptop-code"></i>fa fa-laptop-code
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-microchip"></i>fa fa-microchip
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-network-wired"></i>fa fa-network-wired
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-notdef"></i>fa fa-notdef
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-qrcode"></i>fa fa-qrcode
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-rectangle-xmark"></i>fa fa-rectangle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-rectangle-xmark"></i>fa-regular fa-rectangle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shield"></i>fa fa-shield
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shield-halved"></i>fa fa-shield-halved
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-terminal"></i>fa fa-terminal
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-window-maximize"></i>fa fa-window-maximize
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-window-maximize"></i>fa-regular fa-window-maximize
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-window-minimize"></i>fa fa-window-minimize
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-window-minimize"></i>fa-regular fa-window-minimize
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-window-restore"></i>fa fa-window-restore
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-window-restore"></i>fa-regular fa-window-restore
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Comunicación</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-at"></i>fa fa-at
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-blender-phone"></i>fa fa-blender-phone
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-bluetooth-b"></i>fa-brands fa-bluetooth-b
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-comment"></i>fa fa-comment
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-comment"></i>fa-regular fa-comment
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-comment-dots"></i>fa fa-comment-dots
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-comment-dots"></i>fa-regular fa-comment-dots
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-comment-medical"></i>fa fa-comment-medical
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-comment-slash"></i>fa fa-comment-slash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-comment-sms"></i>fa fa-comment-sms
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-comments"></i>fa fa-comments
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-comments"></i>fa-regular fa-comments
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-frown"></i>fa fa-face-frown
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-frown"></i>fa-regular fa-face-frown
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-meh"></i>fa fa-face-meh
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-meh"></i>fa-regular fa-face-meh
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-smile"></i>fa fa-face-smile
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-smile"></i>fa-regular fa-face-smile
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fax"></i>fa fa-fax
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-icons"></i>fa fa-icons
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-inbox"></i>fa fa-inbox
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-language"></i>fa fa-language
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-message"></i>fa fa-message
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-message"></i>fa-regular fa-message
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mobile"></i>fa fa-mobile
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mobile-button"></i>fa fa-mobile-button
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mobile-retro"></i>fa fa-mobile-retro
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mobile-screen"></i>fa fa-mobile-screen
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mobile-screen-button"></i>fa fa-mobile-screen-button
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-paper-plane"></i>fa fa-paper-plane
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-paper-plane"></i>fa-regular fa-paper-plane
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-poo"></i>fa fa-poo
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-quote-left"></i>fa fa-quote-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-quote-right"></i>fa fa-quote-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-rss"></i>fa fa-square-rss
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tower-cell"></i>fa fa-tower-cell
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tty"></i>fa fa-tty
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-video"></i>fa fa-video
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-video-slash"></i>fa fa-video-slash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-voicemail"></i>fa fa-voicemail
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-walkie-talkie"></i>fa fa-walkie-talkie
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Conectividad</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cloud"></i>fa fa-cloud
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ethernet"></i>fa fa-ethernet
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-signal"></i>fa fa-house-signal
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-rss"></i>fa fa-rss
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-signal"></i>fa fa-signal
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tower-broadcast"></i>fa fa-tower-broadcast
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wifi"></i>fa fa-wifi
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Construcción</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-up-from-ground-water"></i>fa fa-arrow-up-from-ground-water
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bore-hole"></i>fa fa-bore-hole
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-brush"></i>fa fa-brush
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bucket"></i>fa fa-bucket
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-compass-drafting"></i>fa fa-compass-drafting
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dumpster"></i>fa fa-dumpster
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dumpster-fire"></i>fa fa-dumpster-fire
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hammer"></i>fa fa-hammer
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-helmet-safety"></i>fa fa-helmet-safety
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mound"></i>fa fa-mound
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-paint-roller"></i>fa fa-paint-roller
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-pen-ruler"></i>fa fa-pen-ruler
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-digging"></i>fa fa-person-digging
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ruler"></i>fa fa-ruler
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ruler-combined"></i>fa fa-ruler-combined
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ruler-horizontal"></i>fa fa-ruler-horizontal
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ruler-vertical"></i>fa fa-ruler-vertical
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-screwdriver"></i>fa fa-screwdriver
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sheet-plastic"></i>fa fa-sheet-plastic
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-toolbox"></i>fa fa-toolbox
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-trowel"></i>fa fa-trowel
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-trowel-bricks"></i>fa fa-trowel-bricks
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wrench"></i>fa fa-wrench
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Diseño</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bezier-curve"></i>fa fa-bezier-curve
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-clone"></i>fa fa-clone
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="fa-regular fa-clone"></i>fa-regular fa-clone
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-crop"></i>fa fa-crop
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-crop-simple"></i>fa fa-crop-simple
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-crosshairs"></i>fa fa-crosshairs
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-draw-polygon"></i>fa fa-draw-polygon
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-droplet"></i>fa fa-droplet
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-droplet-slash"></i>fa fa-droplet-slash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-eye-dropper"></i>fa fa-eye-dropper
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-eye-slash"></i>fa fa-eye-slash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-eye-slash"></i>fa-regular fa-eye-slash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fill"></i>fa fa-fill
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fill-drip"></i>fa fa-fill-drip
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-layer-group"></i>fa fa-layer-group
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-lines-leaning"></i>fa fa-lines-leaning
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-object-group"></i>fa fa-object-group
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-object-group"></i>fa-regular fa-object-group
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-object-ungroup"></i>fa fa-object-ungroup
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-object-ungroup"></i>fa-regular fa-object-ungroup
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-paintbrush"></i>fa fa-paintbrush
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-palette"></i>fa fa-palette
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-splotch"></i>fa fa-splotch
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-spray-can"></i>fa fa-spray-can
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-stamp"></i>fa fa-stamp
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-swatchbook"></i>fa fa-swatchbook
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-vector-square"></i>fa fa-vector-square
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wand-magic"></i>fa fa-wand-magic
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wand-magic-sparkles"></i>fa fa-wand-magic-sparkles
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Dispositivos + Hardware</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-compact-disc"></i>fa fa-compact-disc
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-computer"></i>fa fa-computer
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-computer-mouse"></i>fa fa-computer-mouse
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-database"></i>fa fa-database
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-desktop"></i>fa fa-desktop
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-display"></i>fa fa-display
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hard-drive"></i>fa fa-hard-drive
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-hard-drive"></i>fa-regular fa-hard-drive
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-memory"></i>fa fa-memory
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-power-off"></i>fa fa-power-off
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sd-card"></i>fa fa-sd-card
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sim-card"></i>fa fa-sim-card
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tablet"></i>fa fa-tablet
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tablet-button"></i>fa fa-tablet-button
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tablet-screen-button"></i>fa fa-tablet-screen-button
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tachograph-digital"></i>fa fa-tachograph-digital
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tv"></i>fa fa-tv
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Desastre + Crisis</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-biohazard"></i>fa fa-biohazard
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-burst"></i>fa fa-burst
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-child-combatant"></i>fa fa-child-combatant
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cloud-bolt"></i>fa fa-cloud-bolt
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cloud-showers-heavy"></i>fa fa-cloud-showers-heavy
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cloud-showers-water"></i>fa fa-cloud-showers-water
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-helmet-un"></i>fa fa-helmet-un
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hill-avalanche"></i>fa fa-hill-avalanche
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hill-rockslide"></i>fa fa-hill-rockslide
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-flood-water"></i>fa fa-house-flood-water
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-flood-water-circle-arrow-right"></i>fa fa-house-flood-water-circle-arrow-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-house-tsunami"></i>fa fa-house-tsunami
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hurricane"></i>fa fa-hurricane
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-drowning"></i>fa fa-person-drowning
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-rifle"></i>fa fa-person-rifle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-walking-arrow-loop-left"></i>fa fa-person-walking-arrow-loop-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-walking-arrow-right"></i>fa fa-person-walking-arrow-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-walking-dashed-line-arrow-right"></i>fa fa-person-walking-dashed-line-arrow-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-walking"></i>fa fa-person-walking
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-walking-luggage"></i>fa fa-person-walking-luggage
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plant-wilt"></i>fa fa-plant-wilt
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-snowflake"></i>fa fa-snowflake
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-snowflake"></i>fa-regular fa-snowflake
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sun-plant-wilt"></i>fa fa-sun-plant-wilt
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-temperature-arrow-down"></i>fa fa-temperature-arrow-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-temperature-arrow-up"></i>fa fa-temperature-arrow-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tornado"></i>fa fa-tornado
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-volcano"></i>fa fa-volcano
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wheat-awn-circle-exclamation"></i>fa fa-wheat-awn-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wind"></i>fa fa-wind
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-xmarks-lines"></i>fa fa-xmarks-lines
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Edición</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bandage"></i>fa fa-bandage
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bars"></i>fa fa-bars
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-check"></i>fa fa-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-check-double"></i>fa fa-check-double
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-check"></i>fa fa-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-circle-check"></i>fa-regular fa-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-delete-left"></i>fa fa-delete-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ellipsis"></i>fa fa-ellipsis
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ellipsis-vertical"></i>fa fa-ellipsis-vertical
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-grip"></i>fa fa-grip
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-grip-lines"></i>fa fa-grip-lines
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-grip-lines-vertical"></i>fa fa-grip-lines-vertical
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-grip-vertical"></i>fa fa-grip-vertical
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-link"></i>fa fa-link
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-link-slash"></i>fa fa-link-slash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-minus"></i>fa fa-minus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plus"></i>fa fa-plus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-rotate"></i>fa fa-rotate
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-check"></i>fa fa-square-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-square-check"></i>fa-regular fa-square-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-pen"></i>fa fa-square-pen
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-trash"></i>fa fa-trash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-trash-arrow-up"></i>fa fa-trash-arrow-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-trash-can"></i>fa fa-trash-can
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-trash-can"></i>fa-regular fa-trash-can
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-trash-can-arrow-up"></i>fa fa-trash-can-arrow-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-xmark"></i>fa fa-xmark
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Educación</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-atom"></i>fa fa-atom
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-award"></i>fa fa-award
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-book-open"></i>fa fa-book-open
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-book-open-reader"></i>fa fa-book-open-reader
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chalkboard"></i>fa fa-chalkboard
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chalkboard-user"></i>fa fa-chalkboard-user
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-graduation-cap"></i>fa fa-graduation-cap
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-masks-theater"></i>fa fa-masks-theater
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-microscope"></i>fa fa-microscope
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Emoji</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-angry"></i>fa fa-face-angry
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-angry"></i>fa-regular fa-face-angry
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-dizzy"></i>fa fa-face-dizzy
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-dizzy"></i>fa-regular fa-face-dizzy
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-flushed"></i>fa fa-face-flushed
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-flushed"></i>fa-regular fa-face-flushed
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-frown"></i>fa fa-face-frown
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-frown"></i>fa-regular fa-face-frown
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-frown-open"></i>fa fa-face-frown-open
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-frown-open"></i>fa-regular fa-face-frown-open
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grimace"></i>fa fa-face-grimace
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grimace"></i>fa-regular fa-face-grimace
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grin"></i>fa fa-face-grin
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grin"></i>fa-regular fa-face-grin
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grin-beam"></i>fa fa-face-grin-beam
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grin-beam"></i>fa-regular fa-face-grin-beam
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grin-beam-sweat"></i>fa fa-face-grin-beam-sweat
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grin-beam-sweat"></i>fa-regular fa-face-grin-beam-sweat
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grin-hearts"></i>fa fa-face-grin-hearts
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grin-hearts"></i>fa-regular fa-face-grin-hearts
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grin-squint"></i>fa fa-face-grin-squint
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grin-squint"></i>fa-regular fa-face-grin-squint
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grin-squint-tears"></i>fa fa-face-grin-squint-tears
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grin-squint-tears"></i>fa-regular fa-face-grin-squint-tears
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grin-stars"></i>fa fa-face-grin-stars
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grin-stars"></i>fa-regular fa-face-grin-stars
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grin-tears"></i>fa fa-face-grin-tears
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grin-tears"></i>fa-regular fa-face-grin-tears
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grin-tongue"></i>fa fa-face-grin-tongue
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grin-tongue"></i>fa-regular fa-face-grin-tongue
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grin-tongue-squint"></i>fa fa-face-grin-tongue-squint
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grin-tongue-squint"></i>fa-regular fa-face-grin-tongue-squint
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grin-tongue-wink"></i>fa fa-face-grin-tongue-wink
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grin-tongue-wink"></i>fa-regular fa-face-grin-tongue-wink
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grin-wide"></i>fa fa-face-grin-wide
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grin-wide"></i>fa-regular fa-face-grin-wide
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-grin-wink"></i>fa fa-face-grin-wink
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-grin-wink"></i>fa-regular fa-face-grin-wink
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-kiss"></i>fa fa-face-kiss
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-kiss"></i>fa-regular fa-face-kiss
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-kiss-beam"></i>fa fa-face-kiss-beam
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-kiss-beam"></i>fa-regular fa-face-kiss-beam
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-kiss-wink-heart"></i>fa fa-face-kiss-wink-heart
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-kiss-wink-heart"></i>fa-regular fa-face-kiss-wink-heart
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-laugh"></i>fa fa-face-laugh
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-laugh"></i>fa-regular fa-face-laugh
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-laugh-beam"></i>fa fa-face-laugh-beam
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-laugh-beam"></i>fa-regular fa-face-laugh-beam
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-laugh-squint"></i>fa fa-face-laugh-squint
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-laugh-squint"></i>fa-regular fa-face-laugh-squint
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-laugh-wink"></i>fa fa-face-laugh-wink
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-laugh-wink"></i>fa-regular fa-face-laugh-wink
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-meh"></i>fa fa-face-meh
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-meh"></i>fa-regular fa-face-meh
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-meh-blank"></i>fa fa-face-meh-blank
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-meh-blank"></i>fa-regular fa-face-meh-blank
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-rolling-eyes"></i>fa fa-face-rolling-eyes
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-rolling-eyes"></i>fa-regular fa-face-rolling-eyes
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-sad-cry"></i>fa fa-face-sad-cry
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-sad-cry"></i>fa-regular fa-face-sad-cry
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-sad-tear"></i>fa fa-face-sad-tear
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-sad-tear"></i>fa-regular fa-face-sad-tear
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-smile"></i>fa fa-face-smile
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-smile"></i>fa-regular fa-face-smile
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-smile-beam"></i>fa fa-face-smile-beam
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-smile-beam"></i>fa-regular fa-face-smile-beam
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-smile-wink"></i>fa fa-face-smile-wink
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-smile-wink"></i>fa-regular fa-face-smile-wink
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-surprise"></i>fa fa-face-surprise
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-surprise"></i>fa-regular fa-face-surprise
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-face-tired"></i>fa fa-face-tired
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-face-tired"></i>fa-regular fa-face-tired
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Energía</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-battery-empty"></i>fa fa-battery-empty
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-battery-full"></i>fa fa-battery-full
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-battery-half"></i>fa fa-battery-half
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-battery-quarter"></i>fa fa-battery-quarter
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-battery-three-quarters"></i>fa fa-battery-three-quarters
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bolt"></i>fa fa-bolt
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-explosion"></i>fa fa-explosion
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fan"></i>fa fa-fan
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fire-flame-simple"></i>fa fa-fire-flame-simple
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-lightbulb"></i>fa fa-lightbulb
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-lightbulb"></i>fa-regular fa-lightbulb
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plug"></i>fa fa-plug
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plug-circle-bolt"></i>fa fa-plug-circle-bolt
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plug-circle-check"></i>fa fa-plug-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plug-circle-exclamation"></i>fa fa-plug-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plug-circle-minus"></i>fa fa-plug-circle-minus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plug-circle-plus"></i>fa fa-plug-circle-plus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plug-circle-xmark"></i>fa fa-plug-circle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-solar-panel"></i>fa fa-solar-panel
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sun"></i>fa fa-sun
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-sun"></i>fa-regular fa-sun
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-water"></i>fa fa-water
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Carpetas</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-arrow-down"></i>fa fa-file-arrow-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-arrow-up"></i>fa fa-file-arrow-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-circle-check"></i>fa fa-file-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-circle-exclamation"></i>fa fa-file-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-circle-minus"></i>fa fa-file-circle-minus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-circle-plus"></i>fa fa-file-circle-plus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-circle-question"></i>fa fa-file-circle-question
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-circle-xmark"></i>fa fa-file-circle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-csv"></i>fa fa-file-csv
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-excel"></i>fa fa-file-excel
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-file-excel"></i>fa-regular fa-file-excel
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-export"></i>fa fa-file-export
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-image"></i>fa fa-file-image
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-file-image"></i>fa-regular fa-file-image
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-import"></i>fa fa-file-import
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-pdf"></i>fa fa-file-pdf
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-file-pdf"></i>fa-regular fa-file-pdf
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-pen"></i>fa fa-file-pen
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-powerpoint"></i>fa fa-file-powerpoint
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-file-powerpoint"></i>fa-regular fa-file-powerpoint
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-shield"></i>fa fa-file-shield
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-video"></i>fa fa-file-video
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-file-video"></i>fa-regular fa-file-video
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-word"></i>a fa-file-word
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-file-word"></i>fa-regular fa-file-word
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-zipper"></i>fa fa-file-zipper
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-file-zipper"></i>fa-regular fa-file-zipper
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-folder-closed"></i>fa fa-folder-closed
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-folder-closed"></i>fa-regular fa-folder-closed
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-photo-film"></i>fa fa-photo-film
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Cine + Video</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle"></i>fa fa-circle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-circle"></i>fa-regular fa-circle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-clapperboard"></i>fa fa-clapperboard
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-film"></i>fa fa-film
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-podcast"></i>fa fa-podcast
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ticket"></i>fa fa-ticket
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-youtube"></i>fa-brands fa-youtube
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Comida + Bebida</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bacon"></i>fa fa-bacon
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-beer-mug-empty"></i>fa-beer-mug-empty
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-blender"></i>fa fa-blender
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bone"></i>fa fa-bone
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bottle-droplet"></i>fa fa-bottle-droplet
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bowl-food"></i>fa fa-bowl-food
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bowl-rice"></i>fa fa-bowl-rice
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bread-slice"></i>fa fa-bread-slice
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-burger"></i>fa fa-burger
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-candy-cane"></i>fa fa-candy-cane
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-carrot"></i>fa fa-carrot
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-champagne-glasses"></i>fa fa-champagne-glasses
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cheese"></i>fa fa-cheese
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cloud-meatball"></i>fa fa-cloud-meatball
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-drumstick-bite"></i>fa fa-drumstick-bite
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-egg"></i>fa fa-egg
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-flask"></i>fa fa-flask
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-glass-water"></i>fa fa-glass-water
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-glass-water-droplet"></i>fa fa-glass-water-droplet
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hotdog"></i>fa fa-hotdog
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-jar"></i>fa fa-jar
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-jar-wheat"></i>fa fa-jar-wheat
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-lemon"></i>fa fa-lemon
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-lemon"></i>fa-regular fa-lemon
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-martini-glass"></i>fa fa-martini-glass
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-martini-glass-citrus"></i>fa fa-martini-glass-citrus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-martini-glass-empty"></i>fa fa-martini-glass-empty
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mug-hot"></i>fa fa-mug-hot
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-pepper-hot"></i>fa fa-pepper-hot
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-pizza-slice"></i>fa fa-pizza-slice
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plate-wheat"></i>fa fa-plate-wheat
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-stroopwafel"></i>fa fa-stroopwafel
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wheat-awn"></i>fa fa-wheat-awn
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-whiskey-glass"></i>fa fa-whiskey-glass
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wine-bottle"></i>fa fa-wine-bottle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wine-glass"></i>fa fa-wine-glass
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wine-glass-empty"></i>fa fa-wine-glass-empty
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Juegos De Azar</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-book-skull"></i>fa fa-book-skull
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chess"></i>fa fa-chess
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chess-bishop"></i>fa fa-chess-bishop
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-chess-bishop"></i>fa-regular fa-chess-bishop
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chess-board"></i>fa fa-chess-board
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chess-king"></i>fa fa-chess-king
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-chess-king"></i>fa-regular fa-chess-king
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chess-knight"></i>fa fa-chess-knight
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-chess-knight"></i>fa-regular fa-chess-knight
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chess-pawn"></i>fa fa-chess-pawn
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-chess-pawn"></i>fa-regular fa-chess-pawn
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chess-queen"></i>fa fa-chess-queen
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-chess-queen"></i>fa-regular fa-chess-queen
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chess-rook"></i>fa fa-chess-rook
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-chess-rook"></i>fa-regular fa-chess-rook
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-critical-role"></i>fa-brands fa-critical-role
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-d-and-d"></i>fa-brands fa-d-and-d
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-d-and-d-beyond"></i>fa-brands fa-d-and-d-beyond
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-diamond"></i>fa fa-diamond
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dice"></i>fa fa-dice
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dice-d20"></i>fa fa-dice-d20
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dice-d6"></i>fa fa-dice-d6
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dice-five"></i>fa fa-dice-five
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dice-four"></i>fa fa-dice-four
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dice-one"></i>fa fa-dice-one
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dice-six"></i>fa fa-dice-six
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dice-three"></i>fa fa-dice-three
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dice-two"></i>fa fa-dice-two
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-fantasy-flight-games"></i>fa-brands fa-fantasy-flight-games
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-fist"></i>fa fa-hand-fist
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-headset"></i>fa fa-headset
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ring"></i>fa fa-ring
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-scroll"></i>fa fa-scroll
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-full"></i>fa fa-square-full
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-square-full"></i>fa-regular fa-square-full
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-square-steam"></i>fa-brands fa-square-steam
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-steam"></i>fa-brands fa-steam
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-steam-symbol"></i>fa-brands fa-steam-symbol
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-twitch"></i>fa-brands fa-twitch
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-vr-cardboard"></i>fa fa-vr-cardboard
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wand-sparkles"></i>fa fa-wand-sparkles
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-wizards-of-the-coast"></i>fa-brands fa-wizards-of-the-coast
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-xbox"></i>fa-brands fa-xbox
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Géneros</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-genderless"></i>fa fa-genderless
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mars"></i>fa fa-mars
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mars-and-venus"></i>fa fa-mars-and-venus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mars-double"></i>fa fa-mars-double
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mars-stroke"></i>fa fa-mars-stroke
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mars-stroke-right"></i>fa fa-mars-stroke-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mars-stroke-up"></i>fa fa-mars-stroke-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mercury"></i>fa fa-mercury
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-neuter"></i>fa fa-neuter
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-half-dress"></i>fa fa-person-half-dress
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-transgender"></i>fa fa-transgender
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-venus"></i>fa fa-venus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-venus-double"></i>fa fa-venus-double
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-venus-mars"></i>fa fa-venus-mars
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Hallowen</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ghost"></i>fa fa-ghost
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-book-skull"></i>fa fa-book-skull
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-broom"></i>fa fa-broom
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cloud-moon"></i>fa fa-cloud-moon
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 me-2 fa fa-hat-wizard"></i>fa fa-hat-wizard
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mask"></i>fa fa-mask
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-skull"></i>fa fa-skull
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wand-sparkles"></i>fa fa-wand-sparkles
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Manos</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand"></i>fa fa-hand
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-hand"></i>fa-regular fa-hand
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-back-fist"></i>fa fa-hand-back-fist
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-hand-back-fist"></i>fa-regular fa-hand-back-fist
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-dots"></i>fa fa-hand-dots
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-fist"></i>fa fa-hand-fist
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-holding-medical"></i>fa fa-hand-holding-medical
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-lizard"></i>fa fa-hand-lizard
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-hand-lizard"></i>fa-regular fa-hand-lizard
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-middle-finger"></i>fa fa-hand-middle-finger
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-peace"></i>fa fa-hand-peace
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-hand-peace"></i>fa-regular fa-hand-peace
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-point-down"></i>fa fa-hand-point-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-hand-point-down"></i>fa-regular fa-hand-point-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-point-left"></i>fa fa-hand-point-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-hand-point-left"></i>fa-regular fa-hand-point-left
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-point-right"></i>fa fa-hand-point-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-hand-point-right"></i>fa-regular fa-hand-point-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-point-up"></i>fa fa-hand-point-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-hand-point-up"></i>fa-regular fa-hand-point-up"
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-pointer"></i>fa fa-hand-pointer
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-hand-pointer"></i>fa-regular fa-hand-pointer
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-scissors"></i>fa fa-hand-scissors
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-hand-scissors"></i>fa-regular fa-hand-scissors
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-sparkles"></i>fa fa-hand-sparkles
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-spock"></i>fa fa-hand-spock
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-hand-spock"></i>fa-regular fa-hand-spock
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hands-bound"></i>fa fa-hands-bound
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hands-bubbles"></i>fa fa-hands-bubbles
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hands-clapping"></i>fa fa-hands-clapping
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-handshake-simple-slash"></i>fa fa-handshake-simple-slash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-handshake-slash"></i>fa fa-handshake-slash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-thumbs-down"></i>fa fa-thumbs-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-thumbs-down"></i>fa-regular fa-thumbs-down
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-thumbs-up"></i>fa fa-thumbs-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-thumbs-up"></i>fa-regular fa-thumbs-up
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Familiar</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-up-from-water-pump"></i>fa fa-arrow-up-from-water-pump
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-box-tissue"></i>fa fa-box-tissue
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chair"></i>fa fa-chair
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-couch"></i>fa fa-couch
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-door-closed"></i>fa fa-door-closed
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-door-open"></i>fa fa-door-open
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fan"></i>fa fa-fan
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-faucet"></i>fa fa-faucet
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-faucet-drip"></i>fa fa-faucet-drip
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-jug-detergent"></i>fa fa-jug-detergent
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-kitchen-set"></i>fa fa-kitchen-set
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mattress-pillow"></i>fa fa-mattress-pillow
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mug-saucer"></i>fa fa-mug-saucer
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-holly-berry"></i>fa fa-holly-berry
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-menorah"></i>fa fa-menorah
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sleigh"></i>fa fa-sleigh
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-pump-soap"></i>fa-pump-soap
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-rug"></i>fa fa-rug
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shower"></i>fa fa-shower
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sink"></i>fa fa-sink
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-soap"></i>fa fa-soap
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-spoon"></i>fa fa-spoon
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-stairs"></i>fa fa-stairs
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-toilet"></i>fa fa-toilet
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Humanitario</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-anchor"></i>fa fa-anchor
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-anchor-circle-check"></i>fa fa-anchor-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-anchor-circle-exclamation"></i>fa fa-anchor-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="fa fa-anchor-circle-xmark"></i>fa fa-anchor-circle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-anchor-lock"></i>fa fa-anchor-lock
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-arrow-right-to-city"></i>fa fa-arrow-right-to-city
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bacterium"></i>fa fa-bacterium
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ban"></i>fa fa-ban
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-book-bookmark"></i>fa fa-book-bookmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bridge"></i>fa fa-bridge
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bridge-circle-check"></i>fa fa-bridge-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bridge-circle-exclamation"></i>fa fa-bridge-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bridge-circle-xmark"></i>fa fa-bridge-circle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bridge-lock"></i>fa fa-bridge-lock
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bridge-water"></i>fa fa-bridge-water
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ferry"></i>fa fa-ferry
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-group-arrows-rotate"></i>fa fa-group-arrows-rotate
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-heart-circle-bolt"></i>fa fa-heart-circle-bolt
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-heart-circle-check"></i>fa fa-heart-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-heart-circle-exclamation"></i>fa fa-heart-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-heart-circle-minus"></i>fa fa-heart-circle-minus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-heart-circle-plus"></i>fa fa-heart-circle-plus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-heart-circle-xmark"></i>fa fa-heart-circle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-helicopter"></i>fa fa-helicopter
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-helicopter-symbol"></i>fa-helicopter-symbol
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-jet-fighter-up"></i>fa fa-jet-fighter-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-kitchen-set"></i>fa fa-kitchen-set
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-land-mine-on"></i>fa fa-land-mine-on
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-life-ring"></i>fa fa-life-ring
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-life-ring"></i>fa-regular fa-life-ring
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-location-pin-lock"></i>fa fa-location-pin-lock
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-lungs"></i>fa fa-lungs
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-money-bill-transfer"></i>fa fa-money-bill-transfer
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-money-bill-trend-up"></i>fa fa-money-bill-trend-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mask-face"></i>fa fa-mask-face
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mask-ventilator"></i>fa fa-mask-ventilato
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-money-bill-wheat"></i>fa fa-money-bill-wheat
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-money-bills"></i>fa fa-money-bills
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-people-arrows"></i>fa fa-people-arrows
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-people-group"></i>fa fa-people-group
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-people-line"></i>fa fa-people-line
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-people-pulling"></i>fa fa-people-pulling
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-people-robbery"></i>fa fa-people-robbery
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-people-roof"></i>fa fa-people-roof
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person"></i>fa fa-person
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-arrow-down-to-line"></i>fa fa-person-arrow-down-to-line
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-arrow-up-from-line"></i>fa fa-person-arrow-up-from-line
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-burst"></i>fa fa-person-burst
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-cane"></i>fa fa-person-cane
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-chalkboard"></i>fa fa-person-chalkboard
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-circle-check"></i>fa fa-person-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-circle-exclamation"></i>fa fa-person-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-circle-minus"></i>fa fa-person-circle-minus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-circle-plus"></i>fa fa-person-circle-plus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-circle-question"></i>fa fa-person-circle-question
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-circle-xmark"></i>fa fa-person-circle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-dress"></i>fa fa-person-dress
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-falling"></i>fa fa-person-falling
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-falling-burst"></i>fa fa-person-falling-burst
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-harassing"></i>fa fa-person-harassing
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-military-pointing"></i>fa fa-person-military-pointing
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-military-rifle"></i>fa fa-person-military-rifle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-military-to-person"></i>fa fa-person-military-to-person
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-pregnant"></i>fa fa-person-pregnant
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-rays"></i>fa fa-person-rays
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-through-window"></i>fa fa-person-through-window
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-pills"></i>fa fa-pills
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plane-circle-check"></i>fa fa-plane-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plane-circle-exclamation"></i>fa fa-plane-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plane-circle-xmark"></i>fa fa-plane-circle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plane-lock"></i>fa fa-plane-lock
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plane-up"></i>fa fa-plane-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-plant-wilt"></i>fa fa-plant-wilt
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ranking-star"></i>fa fa-ranking-star
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-road"></i>fa fa-road
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-road-barrier"></i>fa fa-road-barrier
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-road-bridge"></i>fa fa-road-bridge
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-road-circle-check"></i>fa fa-road-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-road-circle-exclamation"></i>fa fa-road-circle-exclamation
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-road-circle-xmark"></i>fa fa-road-circle-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-road-lock"></i>fa fa-road-lock
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-road-spikes"></i>fa fa-road-spikes
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sack-dollar"></i>fa fa-sack-dollar
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sack-xmark"></i>fa fa-sack-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sailboat"></i>fa fa-sailboat
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shield-heart"></i>fa fa-shield-heart
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ship"></i>fa fa-ship
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-nfi"></i>fa fa-square-nfi
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-person-confined"></i>fa fa-square-person-confined
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-square-virus"></i>fa fa-square-virus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-stethoscope"></i>fa fa-stethoscope
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-suitcase-medical"></i>fa fa-suitcase-medical
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck-arrow-right"></i>fa fa-truck-arrow-right
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck-droplet"></i>fa fa-truck-droplet
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck-field"></i>fa fa-truck-field
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck-field-un"></i>fa fa-truck-field-un
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck-front"></i>fa fa-truck-front
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck-plane"></i>fa fa-truck-plane
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-vial-circle-check"></i>fa fa-vial-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-vial-virus"></i>fa fa-vial-virus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-vihara"></i>fa fa-vihara
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-virus"></i>fa fa-virus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-virus-covid"></i>fa fa-virus-covid
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-volcano"></i>fa fa-volcano
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Números</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-0"></i>fa fa-0
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-1"></i>fa fa-1
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-2"></i>fa fa-2
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-3"></i>fa fa-3
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-4"></i>fa fa-4
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-5"></i>fa fa-5
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-6"></i>fa fa-6
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-7"></i>fa fa-7
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-8"></i>fa fa-8
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-9"></i>fa fa-9
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Marketing</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bullseye"></i>fa fa-bullseye
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-comment-dollar"></i>fa fa-comment-dollar
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-comments-dollar"></i>fa fa-comments-dollar
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-envelope-open-text"></i>fa fa-envelope-open-text
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-envelopes-bulk"></i>fa fa-envelopes-bulk
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-filter-circle-dollar"></i>fa fa-filter-circle-dollar
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-magnifying-glass-dollar"></i>fa fa-magnifying-glass-dollar
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-magnifying-glass-location"></i>fa fa-magnifying-glass-location
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-rectangle-ad"></i>fa fa-rectangle-ad
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Usuarios</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user"></i>fa fa-user
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-user"></i>fa-regular fa-user
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-clock"></i>fa fa-user-clock
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-check"></i>fa fa-user-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-gear"></i>fa fa-user-gear
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-group"></i>fa fa-user-group
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-large"></i>fa fa-user-large
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-large-slash"></i>fa fa-user-large-slash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-lock"></i>fa fa-user-lock
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-ninja"></i>fa fa-user-ninja
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-nurse"></i>fa fa-user-nurse
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-pen"></i>fa fa-user-pen
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-plus"></i>fa fa-user-plus
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-xmark"></i>fa fa-user-xmark
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-injured"></i>fa fa-user-injured
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-users-between-lines"></i>fa fa-users-between-lines
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-users-line"></i>fa fa-users-line
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-users-rays"></i>fa fa-users-rays
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-users-rectangle"></i>fa fa-users-rectangle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-users-viewfinder"></i>fa fa-users-viewfinder
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-astronaut"></i>fa fa-user-astronaut
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-tie"></i>fa fa-user-tie
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-secret"></i>fa fa-user-secret
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-graduate"></i>fa fa-user-graduate
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-users"></i>fa fa-users
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-users-slash"></i>fa fa-users-slash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-user-doctor"></i>fa fa-user-doctor
                            </a>
                        </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Dinero</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-austral-sign"></i>fa fa-austral-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-baht-sign"></i>fa fa-baht-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bangladeshi-taka-sign"></i>fa fa-bangladeshi-taka-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-bitcoin"></i>fa-brands fa-bitcoin
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bitcoin-sign"></i>fa fa-bitcoin-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-brazilian-real-sign"></i>fa fa-brazilian-real-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-btc"></i>fa-brands fa-btc
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cash-register"></i>fa fa-cash-register
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cedi-sign"></i>fa fa-cedi-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cent-sign"></i>fa fa-cent-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-dollar-to-slot"></i>fa fa-circle-dollar-to-slot
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-coins"></i>fa fa-coins
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-colon-sign"></i>fa fa-colon-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-cruzeiro-sign"></i>fa fa-cruzeiro-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dong-sign"></i>fa fa-dong-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-ethereum"></i>fa-brands fa-ethereum
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-euro-sign"></i>fa fa-euro-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-invoice"></i>fa fa-file-invoice
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-invoice-dollar"></i>fa fa-file-invoice-dollar
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-florin-sign"></i>fa fa-florin-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-franc-sign"></i>fa fa-franc-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-gg"></i>fa-brands fa-gg
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-gg-circle"></i>fa-brands fa-gg-circle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-guarani-sign"></i>fa fa-guarani-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hryvnia-sign"></i>fa fa-hryvnia-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-manat-sign"></i>fa fa-manat-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-money-bill"></i>fa fa-money-bill
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-money-bill-1"></i>fa fa-money-bill-1
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-money-bill-1"></i>fa-regular fa-money-bill-1
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-money-bill-1-wave"></i>fa fa-money-bill-1-wave
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-money-bill-wave"></i>fa fa-money-bill-wave
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-money-check"></i>fa fa-money-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-money-check-dollar"></i>fa fa-money-check-dollar
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-naira-sign"></i>fa fa-naira-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-peseta-sign"></i>fa fa-peseta-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-peso-sign"></i>fa fa-peso-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-piggy-bank"></i>fa fa-piggy-bank
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-receipt"></i>fa fa-receipt
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ruble-sign"></i>fa fa-ruble-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-rupee-sign"></i>fa fa-rupee-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-rupiah-sign"></i>fa fa-rupiah-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-shekel-sign"></i>fa fa-shekel-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sterling-sign"></i>fa fa-sterling-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tenge-sign"></i>fa fa-tenge-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-turkish-lira-sign"></i>fa fa-turkish-lira-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-won-sign"></i>fa fa-won-sign
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-yen-sign"></i>fa fa-yen-sign
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Toggle</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-star"> </i>fa fa-star
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-star"> </i>fa-regular fa-star
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-toggle-on"></i> fafa-toggle-on
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-toggle-off"></i>fa fa-toggle-off
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-star-half-stroke"></i>fa fa-star-half-stroke
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-star-half-stroke"></i>fa-regular fa-star-half-stroke
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-star-half"> </i>fa fa-star-half
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-star-half"></i>fa-regular fa-star-half
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-dot"></i>fa fa-circle-dot
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-circle-dot"></i>fa-regular fa-circle-dot
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle"></i>fa fa-circle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="">
                                <i class="me-2 fa-regular fa-circle"></i>fa-regular fa-circle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="">
                                <i class="me-2 fa fa-bullseye"></i> fa fa-bullseye
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="">
                                <i class="me-2 fa fa-circle-check"></i>fa fa-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="">
                                <i class="me-2 fa-regular fa-circle-check"></i> fa-regular fa-circle-check
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="">
                                <i class="me-2 fa fa-microphone-slash"></i> fa fa-microphone-slash
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="">
                                <i class="me-2 fa fa-microphone"></i>fa fa-microphone
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="">
                                <i class="me-2 fa fa-toggle-off"></i>fa fa-toggle-off
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="">
                                <i class="me-2 fa fa-plane-up"></i>fa fa-plane-up
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="">
                                <i class="me-2 fa fa-location-crosshairs"></i>fa fa-location-crosshairs
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="">
                                <i class="me-2 fa fa-circle-dot"></i> fa fa-circle-dot
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="">
                                <i class="me-2 fa-regular fa-star-half-stroke"></i>fa-regular fa-star-half-stroke
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="">
                                <i class="me-2 fa-regular fa-circle-dot"></i>fa-regular fa-circle-dot
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Moving</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-route"></i>fa fa-route
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck-ramp-box"></i>fa fa-truck-ramp-box
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-truck-moving"></i>fa fa-truck-moving
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-trailer"></i>fa fa-trailer
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-tape"></i>fa fa-tape
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-suitcase"></i>fa fa-suitcase
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sign-hanging"></i>fa fa-sign-hanging
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-people-carry-box"></i>fa fa-people-carry-box
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dolly"></i>fa fa-dolly
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-wine-glass"></i> fa fa-wine-glass
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-couch"></i>fa fa-couch
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-boxes-packing"></i>fa fa-boxes-packing
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-box-open"></i>fa fa-box-open
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-caravan"></i>fa fa-caravan
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Música + Audio</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-music"></i> fa fa-music
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-headphones"></i>fa fa-headphones
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sliders"></i>fa fa-sliders
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-soundcloud"></i>fa-brands fa-soundcloud
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-record-vinyl"></i> fa-record-vinyl
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-radio"></i>fa fa-radio
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-spotify"></i>fa-brands fa-spotify
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-microphone-slash"></i>fa fa-microphone-slash
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-microphone"></i> fa-microphone
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-volume-xmark"></i>fa fa-volume-xmark
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-volume-off"></i>fa fa-volume-off
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-volume-low"></i>fa fa-volume-low
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-volume-high"></i>fa fa-volume-high
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-microphone-lines-slash"></i>fa fa-microphone-lines-slash
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-microphone-lines"></i>fa fa-microphone-lines
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-headphones-simple"></i>fa fa-headphones-simple
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-guitar"></i>fa fa-guitar
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-audio"></i>fa fa-file-audio
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-drum-steelpan"></i> fa-drum-steelpan
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-drum"></i>fa fa-drum
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-compact-disc"></i>fa fa-compact-disc
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-napster"></i>fa-brands fa-napster
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-file-audio"></i>fa-regular fa-file-audio
                            </a>
                        </div>

                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Fotos + imágenes</h3>

                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-image"></i>fa fa-image
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-image"></i>fa-regular fa-image
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-camera-retro"></i>fa fa-camera-retro
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bolt"></i>fa fa-bolt
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-film"></i>fa fa-film
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-camera"></i>fa fa-camera
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-half-stroke"></i>fa fa-circle-half-stroke
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-droplet"></i>fa fa-droplet
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-eye"></i>fa fa-eye
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-eye"></i>fa-regular fa-eye
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-eye-slash"></i>fa fa-eye-slash
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-eye-slash"></i>fa-regular fa-eye-slash
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-sliders"></i>fa fa-sliders
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-chalkboard"></i>fa fa-chalkboard
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-clone"></i>fa fa-clone
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-clone"></i>ffa-regulara fa-clone
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-images"></i>fa fa-images
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-images"></i>fa-regular fa-images
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-photo-film"></i>fa fa-photo-film
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-panorama"></i>fa fa-panorama
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-minimize"></i>fa fa-minimize
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-image-portrait"></i>fa fa-image-portrait
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-id-card"></i>fa fa-id-card
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-id-badge"></i>fa fa-id-badge
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-file-image"></i>fa fa-file-image
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-eye-dropper"></i>fa fa-eye-dropper
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-camera-rotate"></i>fa fa-camera-rotate
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bolt-lightning"></i>fa fa-bolt-lightning
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-brands fa-unsplash"></i>fa-brands fa-unsplash
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-id-card"></i>fa-regular fa-id-card
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-id-badge"></i>fa-regular fa-id-badge
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-file-image"></i>fa-regular fa-file-image
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Política</h3>

                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dumpster-fire"></i>
                                fa fa-dumpster-fire
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-handshake"></i>
                                fa fa-handshake
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-award"></i>
                                fa fa-award
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-scale-unbalanced-flip"></i>
                                fa fa-scale-unbalanced-flip
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-scale-unbalanced"></i>
                                fa fa-scale-unbalanced
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-scale-balanced"></i>
                                fa fa-scale-balanced
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-republican"></i>
                                fa fa-republican
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-piggy-bank"></i>
                                fa fa-piggy-bank
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-booth"></i>
                                fa fa-person-booth
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-landmark-flag"></i>
                                fa fa-landmark-flag
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-landmark-dome"></i>
                                fa fa-landmark-dome
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hand-fist"></i>
                                fa fa-hand-fist
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-flag-usa"></i>
                                fa fa-flag-usa
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dove"></i>
                                fa fa-dove
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-democrat"></i>
                                fa fa-democrat
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-circle-dollar-to-slot"></i>
                                fa fa-circle-dollar-to-slot
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-check-to-slot"></i>
                                fa fa-check-to-slot
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-check-double"></i>
                                fa fa-check-double
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bullhorn"></i>
                                fa fa-bullhorn
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-building-flag"></i>
                                fa fa-building-flag
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <h3 class="text-primary">Punctuación + Símbolos</h3>
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-check"></i>
                            fa fa-check
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-plus"></i>
                            fa fa-plus
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-minus"></i>
                            fa fa-minus
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-circle-exclamation"></i>
                            fa-circle-exclamation
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-hashtag"></i>
                            fa fa-hashtag
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-quote-left"></i>
                            fa fa-quote-left
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-question"></i>
                            fa-question
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-quote-right"></i>
                            fa fa-quote-right
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-exclamation"></i>
                            fa fa-exclamation
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-percent"></i>
                            fa fa-percent
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-section"></i>
                            fa fa-section
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-less-than"></i>
                            fa fa-less-than
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-greater-than"></i>
                            fa fa-greater-than
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-equals"></i>
                            fa fa-equals
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-circle-question"></i>
                            fa fa-circle-question
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-check-double"></i>
                            fa fa-check-double
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-at"></i>
                            fa fa-at
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-asterisk"></i>
                            fa fa-asterisk
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa-regular fa-circle-question"></i>
                            fa-regular fa-circle-question
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <section>
                    <h3 class="text-primary">Sports + Fitness</h3>

                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-heart"></i>
                                fa fa-heart
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa-regular fa-heart"></i>
                                fa-regular fa-heart
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bicycle"></i>
                                fa fa-bicycle
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-medal"></i>
                                fa fa-medal
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-dumbbell"></i>
                                fa fa-dumbbell
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-weight-hanging"></i>
                                fa fa-weight-hanging
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-volleyball"></i>
                                fa fa-volleyball
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-table-tennis-paddle-ball"></i>
                                fa fa-table-tennis-paddle-ball
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-stopwatch-20"></i>
                                fa fa-stopwatch-20
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-spa"></i>
                                fa fa-spa
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="fa fa-shoe-prints"></i>
                                fa fa-shoe-prints
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-ranking-star"></i>
                                fa fa fa-ranking-star
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-walking"></i>
                                fa fa-person-walking
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-swimming"></i>
                                fa fa-person-swimming
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-snowboarding"></i>
                                fa fa-person-snowboarding
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-skiing-nordic"></i>
                                fa fa-person-skiing-nordic
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-skiing"></i>
                                fa fa-person-skiing
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-skating"></i>
                                fa fa-person-skating
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-running"></i>
                                fa fa fa-person-running
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-hiking"></i>
                                fa fa-person-hiking
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-person-biking"></i>
                                fa fa-person-biking
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-mound"></i>
                                fa fa-mound
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-hockey-puck"></i>
                                fa fa-hockey-puck
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-heart-pulse"></i>
                                fa fa-heart-pulse
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-golf-ball-tee"></i>
                                fa fa-golf-ball-tee
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-futbol"></i>
                                fa fa-futbol
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-football"></i>
                                fa fa-football
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fire-flame-simple"></i>
                                fa fa-fire-flame-simple
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-fire-flame-curved"></i>
                                fa fa-fire-flame-curved
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-broom-ball"></i>
                                fa fa-broom-ball
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-bowling-ball"></i>
                                fa-bowling-ball
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-basketball"></i>
                                fa fa-basketball
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <a href="#">
                                <i class="me-2 fa fa-baseball"></i>
                                fa fa-baseball
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-body">
                <h3 class="text-primary">Tiempo</h3>
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-calendar-days"></i>
                            fa fa-calendar-days
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa-regular fa-calendar-days"></i>
                            fa-regular fa-calendar-days
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-bell"></i>
                            fa fa-bell
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa-regular fa-bell"></i>
                            fa-regular fa-bell
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-calendar"></i>
                            fa fa-calendar
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa-regular fa-calendar"></i>
                            fa-regular fa-calendar
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-clock"></i>
                            fa fa-clock
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa-regular fa-clock"></i>
                            fa-regular fa-clock
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-hourglass-start"></i>
                            fa fa-hourglass-start
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-calendar-week"></i>
                            fa fa-calendar-week
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-stopwatch-20"></i>
                            fa fa-stopwatch-20
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-stopwatch"></i>
                            fa fa-stopwatch
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-hourglass-half"></i>
                            fa fa-hourglass-half
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-hourglass-end"></i>
                            fa fa-hourglass-end
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-hourglass"></i>
                            fa fa-hourglass
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-calendar-xmark"></i>
                            fa fa-calendar-xmark
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-calendar-plus"></i>
                            fa fa-calendar-plus
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-calendar-minus"></i>
                            fa fa-calendar-minus
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-calendar-day"></i>
                            fa fa-calendar-day
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-calendar-check"></i>
                            fa fa-calendar-check
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa fa-bell-slash"></i>
                            fa fa-bell-slash
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa-regular fa-hourglass-half"></i>
                            fa-regular fa-hourglass-half
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa-regular fa-hourglass"></i>
                            fa-regular fa-hourglass
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa-regular fa-calendar-xmark"></i>
                            fa-regular fa-calendar-xmark
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa-regular fa-calendar-plus"></i>
                            fa-regular fa-calendar-plus
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa-regular fa-calendar-minus"></i>
                            fa-regular fa-calendar-minus
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa-regular fa-calendar-check"></i>
                            fa-regular fa-calendar-check
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#">
                            <i class="me-2 fa-regular fa-bell-slash"></i>
                            fa-regular fa-bell-slash
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <section>
                        <h3 class="text-primary">Transporte</h3>
                        <div class="row">
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-cart-shopping"></i>
                                    fa fa-cart-shopping
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-car"></i>
                                    fa fa-car
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-plane"></i>
                                    fa fa-plane
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-paper-plane"></i>
                                    fa fa-paper-plane
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa-regular fa-paper-plane"></i>
                                    fa-regular fa-paper-plane
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-truck"></i>
                                    fa fa-truck
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-bicycle"></i>
                                    fa fa-bicycle
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-car-side"></i>
                                    fa fa-car-side
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="fa fa-truck-front"></i>
                                    fa fa-truck-front
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-cable-car"></i>
                                    fa fa-cable-car
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-rocket"></i>
                                    fa fa-rocket
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-ship"></i>
                                    fa fa-ship
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-motorcycle"></i>
                                    fa fa-motorcycle
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-accessible-icon"></i>
                                    fa fa-accessible-icon
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-train"></i>
                                    fa fa-train
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-road"></i>
                                    fa fa-road
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-truck-monster"></i>
                                    fa fa-truck-monster
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-wheelchair-move"></i>
                                    fa fa-wheelchair-move
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-wheelchair"></i>
                                    fa fa-wheelchair
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-van-shuttle"></i>
                                    fa fa-van-shuttle
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-truck-plane"></i>
                                    fa fa-truck-plane
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-truck-pickup"></i>
                                    fa fa-truck-pickup
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-truck-medical"></i>
                                    fa fa-truck-medical
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-truck-field-un"></i>
                                    fa fa-truck-field-un
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-truck-field"></i>
                                    fa fa-truck-field
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-truck-droplet"></i>
                                    fa fa-truck-droplet
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-truck-arrow-right"></i>
                                    fa fa-truck-arrow-right
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-train-tram"></i>
                                    fa fa-train-tram
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-train-subway"></i>
                                    fa fa-train-subway
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-tractor"></i>
                                    fa fa-tractor
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-taxi"></i>
                                    fa fa-taxi
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-snowplow"></i>
                                    fa fa-snowplow
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-sleigh"></i>
                                    fa fa-sleigh
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-shuttle-space"></i>
                                    fa fa-shuttle-space
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-sailboat"></i>
                                    fa fa-sailboat
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-road-spikes"></i>
                                    fa fa-road-spikes
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-road-barrier"></i>
                                    fa fa-road-barrier
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-plane-up"></i>
                                    fa fa-plane-up
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-plane-slash"></i>
                                    fa fa-plane-slash
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-mound"></i>
                                    fa fa-mound
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="fa fa-jet-fighter-up"></i>
                                    fa-jet-fighter-up
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-jet-fighter"></i>
                                    fa fa-jet-fighter
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-horse"></i>
                                    fa fa-horse
                                </a>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-helicopter"></i>
                                    fa fa-helicopter
                                </a>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="card-body">
                    <section>
                        <h3 class="text-primary">Clima</h3>
                        <div class="row">

                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-cloud"></i>
                                    fa fa-cloud
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-bolt"></i>
                                    fa fa-bolt
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-umbrella"></i>
                                    fa fa-umbrella
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-snowflake"></i>
                                    fa fa-snowflake
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa-regular fa-snowflake"></i>
                                    fa-regular fa-snowflake
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-sun"></i>
                                    fa fa-sun
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa-regular fa-sun"></i>
                                    fa-regular fa-sun
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-poo-storm"></i>
                                    fa fa-poo-storm
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-water"></i>
                                    fa fa-water
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-moon"></i>
                                    fa fa-moon
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa-regular fa-moon"></i>
                                    fa-regular fa-moon
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-wind"></i>
                                    fa fa-wind
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-volcano"></i>
                                    fa fa-volcano
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-tornado"></i>
                                    fa fa-tornado
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-temperature-three-quarters"></i>
                                    fa fa-temperature-three-quarters
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-temperature-quarter"></i>
                                    fa fa-temperature-quarter
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-temperature-low"></i>
                                    fa fa-temperature-low
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-temperature-high"></i>
                                    fa fa-temperature-high
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-temperature-half"></i>
                                    fa fa-temperature-half
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-temperature-full"></i>
                                    fa fa-temperature-full
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-temperature-empty"></i>
                                    fa fa-temperature-empty
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-temperature-arrow-up"></i>
                                    fa fa-temperature-arrow-up
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-temperature-arrow-down"></i>
                                    fa fa-temperature-arrow-down
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-sun-plant-wilt"></i>
                                    fa fa-sun-plant-wilt
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-smog"></i>
                                    fa fa-smog
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-rainbow"></i>
                                    fa fa-rainbow
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-meteor"></i>
                                    fa fa-meteor
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-icicles"></i>
                                    fa fa-icicles
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-hurricane"></i>
                                    fa fa-hurricane
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-house-tsunami"></i>
                                    fa fa-house-tsunami
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-cloud-sun-rain"></i>
                                    fa fa-cloud-sun-rain
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-cloud-sun"></i>
                                    fa fa-cloud-sun
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-cloud-showers-water"></i>
                                    fa fa-cloud-showers-water
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-cloud-showers-heavy"></i>
                                    fa fa-cloud-showers-heavy
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-cloud-rain"></i>
                                    fa fa-cloud-rain
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-cloud-moon-rain"></i>
                                    fa fa-cloud-moon-rain
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-cloud-moon"></i>
                                    fa fa-cloud-moon
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-cloud-meatball"></i>
                                    fa fa-cloud-meatball
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-cloud-bolt"></i>
                                    fa fa-cloud-bolt
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="#">
                                    <i class="me-2 fa fa-bolt-lightning"></i>
                                    fa fa-bolt-lightning
                                </a>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

    </div>

{{-- Toast de copiado al portapapeles --}}
<div id="toast-icono" class="toast align-items-center text-bg-success border-0 rounded-3 shadow" role="alert">
    <div class="d-flex">
        <div class="toast-body d-flex align-items-center gap-2">
            <i class="fa-solid fa-clipboard-check"></i>
            <span>Copiado: <strong id="clase-copiada"></strong></span>
        </div>
    </div>
</div>

<script>
(function () {
    // ── Copiar al portapapeles ──────────────────────────────
    document.querySelectorAll('#iconos-wrap .col-lg-3 a').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const clase = this.textContent.trim();
            navigator.clipboard.writeText(clase).then(() => {
                this.classList.add('copiado');
                const icon = this.querySelector('i');
                const claseOrig = icon.className;
                icon.className = 'fa-solid fa-check';
                setTimeout(() => {
                    this.classList.remove('copiado');
                    icon.className = claseOrig;
                }, 1200);

                const toast = document.getElementById('toast-icono');
                document.getElementById('clase-copiada').textContent = clase;
                toast.classList.add('visible');
                clearTimeout(toast._t);
                toast._t = setTimeout(() => toast.classList.remove('visible'), 2600);
            }).catch(() => {});
        });
    });

    // ── Contador de íconos ──────────────────────────────────
    const total = document.querySelectorAll('#iconos-wrap .col-lg-3').length;
    const contEl = document.getElementById('contadorIconos');
    if (contEl) contEl.textContent = '— ' + total + ' íconos disponibles.';

    // ── Búsqueda en tiempo real ─────────────────────────────
    const input   = document.getElementById('buscarIcono');
    const limpiar = document.getElementById('limpiarBusqueda');
    const resEl   = document.getElementById('resultadoBusqueda');
    const sinRes  = document.getElementById('sin-resultados');

    input.addEventListener('input', filtrar);
    limpiar.addEventListener('click', () => { input.value = ''; filtrar(); input.focus(); });

    function filtrar() {
        const q = input.value.trim().toLowerCase();
        limpiar.style.display = q ? 'block' : 'none';

        let visibles = 0;

        document.querySelectorAll('#iconos-wrap .col-lg-3').forEach(col => {
            const texto = col.textContent.trim().toLowerCase();
            const ok = !q || texto.includes(q);
            col.style.display = ok ? '' : 'none';
            if (ok) visibles++;
        });

        // Ocultar categorías completas sin coincidencias
        document.querySelectorAll('#iconos-wrap h3.text-primary').forEach(h3 => {
            const row = h3.nextElementSibling;
            const hayVisibles = row
                ? [...row.querySelectorAll('.col-lg-3')].some(c => c.style.display !== 'none')
                : false;
            h3.style.display = (!q || hayVisibles) ? '' : 'none';
            if (row) row.style.display = (!q || hayVisibles) ? '' : 'none';
        });

        sinRes.style.display = (q && visibles === 0) ? 'block' : 'none';
        resEl.textContent = q ? visibles + ' resultado' + (visibles !== 1 ? 's' : '') : '';
    }
})();
</script>

@endsection

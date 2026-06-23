<div class="loader-overlay" id="globalLoader" >
    <div class="loader-box">
        <div class="wheel " style="width: 70px;height: 70px;border-radius: 50%">
            <img src="{{ asset('isologo.ico') }}" class="loader-rim" alt="Cargando">
        </div>
        <div class="loader-text">Cargando...</div>
    </div>
</div>
<style>
    .loader-overlay{
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;

        background: rgba(0,0,0,.55);   /* oscurece fondo */
        backdrop-filter: blur(2px);    /* opcional */
    }

    /* Caja central */
    .loader-box{
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
    }

    /* Imagen girando */
    .loader-rim{
        width: 70px;        /* ajusta tamaño */
        height: 70px;
        object-fit: contain;
        animation: spin 1s linear infinite;
        filter: drop-shadow(0 6px 18px rgba(0,0,0,.35));
    }

    .loader-text{
        color: #fff;
        font-weight: 600;
        font-size: 14px;
        letter-spacing: .2px;
    }

    /* Animación giro */
    @keyframes spin{
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }
</style>

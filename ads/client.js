/**
 * ads/client.js - Módulo Cliente de Anuncios (Ad Client)
 * Versión 1.1: Corrección de errores de logging y compatibilidad.
 */

document.addEventListener('DOMContentLoaded', initAdSystemClient);

const BANNER_LAPSE_MS = 60000; // Lapso de 60 segundos entre la aparición de diferentes banners
const BANNER_LAPSE_KEY = 'ad_last_closed_ts';
const BANNER_DELAY = 2000; // 2 segundos de retraso al cargar
const ADS_SERVER_URL = '/ads/server.php'; 

/**
 * Función de registro de eventos (tracking)
 * Usa la ruta completa con parámetros para máxima fiabilidad.
 */
function registrar_evento(id, tipo) {
    const citySlug = document.body.getAttribute('data-city-slug'); 
    
    // Usamos XMLHttpRequest (XHR) en lugar de fetch. XHR es más simple y confiable para logs "fire-and-forget".
    const logUrl = `/ads/log.php?id=${id}&tipo=${tipo}&ciudad=${citySlug}`;

    const xhr = new XMLHttpRequest();
    xhr.open('GET', logUrl, true); // true = asíncrono
    xhr.send();
    
    // No necesitamos manejar la respuesta aquí, solo asegurar que se envíe.
}

/**
 * Crea y añade el banner flotante al DOM.
 */
function renderBanner(banner) {
    const adBanner = document.createElement('a');
    adBanner.href = banner.cta_url;
    adBanner.id = `ad-banner-${banner.id}`;
    adBanner.target = '_blank';
    adBanner.className = `floating-ad-banner ad-${banner.posicion}`;
    
    // HTML del banner. NOTA: Los comentarios HTML se usan para recordar el límite de caracteres al admin.
    adBanner.innerHTML = `
        <div class="ad-content">
            <div class="ad-logo-wrapper">
                <img src="${banner.logo_url}" alt="Logo Anunciante" class="ad-logo">
            </div>
            <div class="ad-text-group">
                <span class="ad-title">${banner.titulo}</span>
                <span class="ad-desc">${banner.descripcion}</span>
            </div>
            <span class="ad-cta-btn">¡Ver Ahora!</span>
        </div>
        <button class="ad-close-btn" aria-label="Cerrar publicidad">✕</button>
        <a href="/contacto_anuncios.html" class="ad-anuncie-btn" onclick="event.stopPropagation();">Anuncie aquí</a>
        <span class="ad-tag-mini">Anuncio</span>
    `;

    document.body.appendChild(adBanner);

    const closeBtn = adBanner.querySelector('.ad-close-btn');

    // 1. Cierre manual
    closeBtn.addEventListener('click', (e) => {
        e.preventDefault(); 
        e.stopPropagation(); 
        adBanner.classList.remove('show');
        localStorage.setItem(BANNER_LAPSE_KEY, new Date().getTime());
    });

    // 2. Registro de Click
    adBanner.addEventListener('mousedown', () => { registrar_evento(banner.id, 'click'); });
    adBanner.addEventListener('touchstart', () => { registrar_evento(banner.id, 'click'); });
    
    // 3. Lógica de Aparición
    setTimeout(() => {
        adBanner.classList.add('show');
        registrar_evento(banner.id, 'impresion'); // Registra la impresión (vista)

        // 4. Ocultar después de la duración
        setTimeout(() => {
            adBanner.classList.remove('show');
        }, banner.tiempo_muestra);

    }, BANNER_DELAY);
}


async function initAdSystemClient() {
    const citySlug = document.body.getAttribute('data-city-slug');
    if (!citySlug) return;
    
    // 1. Aplicar Cooldown de aparición
    const lastClosedTS = localStorage.getItem(BANNER_LAPSE_KEY) || 0;
    const now = new Date().getTime();
    if (now - lastClosedTS < BANNER_LAPSE_MS) { return; }
    
    // 2. Fetch de datos del servidor
    try {
        const response = await fetch(`${ADS_SERVER_URL}?ciudad=${citySlug}`); 
        const data = await response.json();

        if (data.success && data.banner) {
            const banner = data.banner;
            
            // Simulación de frecuencia: 1 de cada 'frecuencia_factor' veces
            if (Math.floor(Math.random() * (banner.frecuencia_factor || 1)) !== 0) { return; }
            
            renderBanner(banner);
        }
    } catch (error) {
        console.error('Error al obtener el banner:', error);
    }
}
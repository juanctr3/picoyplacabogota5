/**
 * ads/client.js - Módulo Cliente de Anuncios (Ad Client)
 * Versión Final SaaS: Implementa Priorización de Ofertas, Cobro y Corrección Móvil.
 */

document.addEventListener('DOMContentLoaded', initAdSystemClient);

const BANNER_LAPSE_MS = 60000; // Lapso de 60 segundos (cooldown)
const BANNER_LAPSE_KEY = 'ad_last_closed_ts';
const BANNER_DELAY = 2000; // 2 segundos de retraso antes de mostrar
const ADS_SERVER_URL = '/ads/server.php'; 

/**
 * Función de registro de eventos (tracking y cobro)
 * Envía el ID, el tipo de evento Y los valores de la oferta (CPC/CPM) para el cobro.
 */
function registrar_evento(id, tipo, cpc = 0.00, cpm = 0.00) { 
    const citySlug = document.body.getAttribute('data-city-slug'); 
    
    // Se agregan cpc y cpm a la URL para que log.php pueda calcular el costo
    const logUrl = `/ads/log.php?id=${id}&tipo=${tipo}&ciudad=${citySlug}&cpc=${cpc}&cpm=${cpm}`;

    // Usamos XMLHttpRequest (XHR) para un envío de log más fiable en segundo plano
    const xhr = new XMLHttpRequest();
    xhr.open('GET', logUrl, true);
    xhr.send();
}

/**
 * Crea y añade el banner flotante al DOM.
 */
function renderBanner(banner) {
    const adBanner = document.createElement('a');
    adBanner.href = banner.cta_url;
    adBanner.id = `ad-banner-${banner.id}`;
    adBanner.target = '_blank';
    adBanner.rel = 'sponsored'; // ⬅️ IMPORTANTE PARA EL SEO
    adBanner.className = `floating-ad-banner ad-${banner.posicion}`;
    
    // Almacenar las ofertas en el DOM para uso posterior
    adBanner.setAttribute('data-cpc', banner.offer_cpc);
    adBanner.setAttribute('data-cpm', banner.offer_cpm);
    
    // HTML del banner
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

    // 1. Cierre manual (Lógica de Cooldown)
    closeBtn.addEventListener('click', (e) => {
        e.preventDefault(); 
        e.stopPropagation(); 
        adBanner.classList.remove('show');
        localStorage.setItem(BANNER_LAPSE_KEY, new Date().getTime()); // Siguiente banner en 60s
    });

    // 2. Registro de Click (Cobro CPC)
    adBanner.addEventListener('mousedown', () => { 
        // Envía el evento de click junto con los valores de oferta del banner
        registrar_evento(banner.id, 'click', banner.offer_cpc, banner.offer_cpm); 
    });
    adBanner.addEventListener('touchstart', () => { 
        registrar_evento(banner.id, 'click', banner.offer_cpc, banner.offer_cpm); 
    });
    
    // 3. Lógica de Aparición (Cobro CPM)
    setTimeout(() => {
        adBanner.classList.add('show');
        // Registro de impresión (Cobro CPM / 1000)
        registrar_evento(banner.id, 'impresion', banner.offer_cpc, banner.offer_cpm); 

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
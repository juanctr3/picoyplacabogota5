/**
 * ads/ads.js - Cliente SaaS v3.0
 * Incluye: Control de frecuencia por usuario, Corrección de Enlace y Moneda COP.
 */

document.addEventListener('DOMContentLoaded', initAdSystemLoader);

const BANNER_LAPSE_MS = 60000; // 60 segundos entre anuncios distintos
const BANNER_GLOBAL_KEY = 'ad_global_cooldown';
const BANNER_DELAY = 2000; 
const ADS_SERVER_URL = '/ads/server.php'; 
const ADS_LOG_URL = '/ads/log.php';

function registrar_evento(id, tipo, cpc = 0.00, cpm = 0.00) { 
    const citySlug = document.body.getAttribute('data-city-slug'); 
    if (!citySlug) return;
    // Log en segundo plano
    const logUrl = `${ADS_LOG_URL}?id=${id}&tipo=${tipo}&ciudad=${citySlug}&cpc=${cpc}&cpm=${cpm}`;
    if (navigator.sendBeacon) {
        navigator.sendBeacon(logUrl);
    } else {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', logUrl, true);
        xhr.send();
    }
}

/**
 * Verifica si el usuario ya vio este anuncio X veces en Y horas
 */
function checkFrequencyCap(bannerId, maxViews, resetHours) {
    const storageKey = `ad_freq_${bannerId}`;
    const now = new Date().getTime();
    const resetMs = resetHours * 60 * 60 * 1000;
    
    let data = JSON.parse(localStorage.getItem(storageKey)) || { count: 0, first_view: now };

    // Si pasó el tiempo de reinicio, reseteamos
    if (now - data.first_view > resetMs) {
        data = { count: 0, first_view: now };
    }

    if (data.count >= maxViews) {
        return false; // Bloquear anuncio (Límite alcanzado)
    }

    // Incrementamos y guardamos
    data.count++;
    localStorage.setItem(storageKey, JSON.stringify(data));
    return true; // Permitir anuncio
}

function renderBanner(banner) {
    // 1. Verificación de Frecuencia (Lógica de Negocio Punto 3)
    const allowed = checkFrequencyCap(banner.id, banner.freq_max, banner.freq_hours);
    if (!allowed) {
        console.log(`Anuncio ${banner.id} oculto por límite de frecuencia.`);
        return; 
    }

    const adBanner = document.createElement('a');
    adBanner.href = banner.cta_url;
    adBanner.id = `ad-banner-${banner.id}`;
    adBanner.target = '_blank';
    adBanner.rel = 'sponsored'; 
    adBanner.className = `floating-ad-banner ad-${banner.posicion}`;
    
    // HTML del banner
    adBanner.innerHTML = `
        <div class="ad-content">
            <div class="ad-logo-wrapper">
                <img src="${banner.logo_url}" alt="Logo" class="ad-logo">
            </div>
            <div class="ad-text-group">
                <span class="ad-title">${banner.titulo}</span>
                <span class="ad-desc">${banner.descripcion}</span>
            </div>
            <span class="ad-cta-btn">¡Ver Más!</span>
        </div>
        <button class="ad-close-btn" aria-label="Cerrar">✕</button>
        <a href="/user/register.php" class="ad-anuncie-btn" onclick="event.stopPropagation();">Anuncie aquí</a>
        <span class="ad-tag-mini">Publicidad</span>
    `;

    document.body.appendChild(adBanner);

    const closeBtn = adBanner.querySelector('.ad-close-btn');

    closeBtn.addEventListener('click', (e) => {
        e.preventDefault(); 
        e.stopPropagation(); 
        adBanner.classList.remove('show');
        localStorage.setItem(BANNER_GLOBAL_KEY, new Date().getTime());
    });

    // Evento CLICK único (Solución Clicks Dobles)
    adBanner.addEventListener('click', () => { 
        registrar_evento(banner.id, 'click', banner.offer_cpc, banner.offer_cpm); 
    });
    
    setTimeout(() => {
        adBanner.classList.add('show');
        // Registro visualización
        registrar_evento(banner.id, 'impresion', banner.offer_cpc, banner.offer_cpm); 

        setTimeout(() => {
            adBanner.classList.remove('show');
        }, banner.tiempo_muestra);

    }, BANNER_DELAY);
}

async function initAdSystemLoader() {
    const citySlug = document.body.getAttribute('data-city-slug');
    if (!citySlug) return;
    
    // Cooldown global entre cualquier anuncio
    const lastClosedTS = localStorage.getItem(BANNER_GLOBAL_KEY) || 0;
    const now = new Date().getTime();
    if (now - lastClosedTS < BANNER_LAPSE_MS) { return; }
    
    try {
        const response = await fetch(`${ADS_SERVER_URL}?ciudad=${citySlug}`); 
        const data = await response.json();

        if (data.success && data.banner) {
            renderBanner(data.banner);
        }
    } catch (error) {
        console.error('Ad Error:', error);
    }
}

/**
 * ads/ads.js - Cliente SaaS v6.0 (Rotación Estricta de Inventario)
 */

const ADS_SERVER_URL = '/ads/server.php'; 
const ADS_LOG_URL = '/ads/log.php';

// CONFIGURACIÓN
const INITIAL_DELAY = 5000; // 5s al iniciar
const CYCLE_DELAY = 2000;   // 2s entre posiciones

// Límite de vistas por URL (Estricto: 1 vez por página para forzar rotación)
const MAX_VIEWS_PER_PAGE = 1; 
const viewedAds = {}; // Registro de IDs mostrados en esta carga

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(startAdCycle, INITIAL_DELAY);
});

async function startAdCycle() {
    const citySlug = document.body.getAttribute('data-city-slug');
    if (!citySlug) return;

    while (true) {
        // 1. Mostrar TOP
        await fetchAndShowAd(citySlug, 'top');
        
        // Pausa
        await sleep(CYCLE_DELAY);

        // 2. Mostrar BOTTOM
        await fetchAndShowAd(citySlug, 'bottom');

        // Pausa
        await sleep(CYCLE_DELAY);
    }
}

async function fetchAndShowAd(citySlug, position) {
    try {
        // Generar lista de excluidos (Anuncios que ya cumplieron su cupo en esta vista)
        const excludeList = Object.keys(viewedAds).filter(id => viewedAds[id] >= MAX_VIEWS_PER_PAGE);
        
        // Solicitar anuncio excluyendo los vistos
        const response = await fetch(`${ADS_SERVER_URL}?ciudad=${citySlug}&posicion=${position}&exclude=${excludeList.join(',')}`);
        const data = await response.json();

        if (data.success && data.banner) {
            const banner = data.banner;
            
            // Mostrar y registrar
            await renderBanner(banner);
            
            // Marcar como visto
            if (!viewedAds[banner.id]) viewedAds[banner.id] = 0;
            viewedAds[banner.id]++;
            
            return true;
        }
    } catch (e) {
        console.error("Ads:", e);
    }
    return false;
}

function renderBanner(banner) {
    return new Promise((resolve) => {
        const adContainer = document.createElement('div');
        // Importante: Usamos la posición que pidió el servidor ('top' o 'bottom')
        // aunque el banner sea 'both' en BD, el servidor nos dice dónde pintarlo hoy.
        adContainer.className = `ad-container ad-${banner.posicion}`; 
        
        adContainer.innerHTML = `
            <a href="${banner.cta_url}" target="_blank" class="ad-link">
                <div class="ad-content-flex">
                    <img src="${banner.logo_url}" class="ad-img" alt="Ad">
                    <div class="ad-info">
                        <div class="ad-title">${banner.titulo}</div>
                        <div class="ad-desc">${banner.descripcion}</div>
                    </div>
                    <div class="ad-cta">VER</div>
                </div>
            </a>
            <div class="ad-footer-bar">
                <div class="ad-timer-text">Cierra en <span id="ad-countdown"></span>s</div>
                <button class="ad-close">✕</button>
            </div>
            <div class="ad-progress-bg">
                <div class="ad-progress-fill" style="animation-duration: ${banner.tiempo_muestra}ms"></div>
            </div>
        `;

        document.body.appendChild(adContainer);

        // Tracking
        registrar_evento(banner.id, 'impresion', banner.offer_cpc, banner.offer_cpm);
        adContainer.querySelector('.ad-link').addEventListener('click', () => {
            registrar_evento(banner.id, 'click', banner.offer_cpc, banner.offer_cpm);
        });

        // Timer Visual
        let timeLeft = Math.ceil(banner.tiempo_muestra / 1000);
        const timerSpan = adContainer.querySelector('#ad-countdown');
        timerSpan.innerText = timeLeft;

        const interval = setInterval(() => {
            timeLeft--;
            if(timeLeft >= 0) timerSpan.innerText = timeLeft;
        }, 1000);

        const closeAd = () => {
            clearInterval(interval);
            adContainer.classList.add('ad-closing');
            setTimeout(() => {
                if(adContainer.parentNode) adContainer.parentNode.removeChild(adContainer);
                resolve();
            }, 500); 
        };

        const timeout = setTimeout(closeAd, banner.tiempo_muestra);

        adContainer.querySelector('.ad-close').addEventListener('click', (e) => {
            e.preventDefault(); e.stopPropagation();
            clearTimeout(timeout);
            closeAd();
        });

        requestAnimationFrame(() => adContainer.classList.add('ad-visible'));
    });
}

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

function registrar_evento(id, tipo, cpc, cpm) {
    const city = document.body.getAttribute('data-city-slug');
    const url = `${ADS_LOG_URL}?id=${id}&tipo=${tipo}&ciudad=${city}&cpc=${cpc}&cpm=${cpm}`;
    if (navigator.sendBeacon) navigator.sendBeacon(url);
    else new Image().src = url;
}
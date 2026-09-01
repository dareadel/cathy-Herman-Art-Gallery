document.addEventListener('DOMContentLoaded', function() {
    
    // Find all gallery instances on the page
    const galleries = document.querySelectorAll('.cag-gallery-container');

    galleries.forEach(function(gallery) {
        // Read the configuration data attributes provided by PHP
        const uid = gallery.getAttribute('data-uid');
        const displayType = gallery.getAttribute('data-display');
        
        // 1. Swiper Slider Logic
        if (displayType === 'slider_thumbs' || displayType === 'slider') {
            let thumbsSwiper = null;

            // Initialize thumbnail row if needed
            if (displayType === 'slider_thumbs') {
                thumbsSwiper = new Swiper(`.cag-swiper-thumbs-${uid}`, {
                    spaceBetween: 15,
                    slidesPerView: 4,
                    freeMode: true,
                    watchSlidesProgress: true,
                    breakpoints: { 768: { slidesPerView: 6 } }
                });
            }

            // Initialize main carousel
            new Swiper(`.cag-swiper-main-${uid}`, {
                slidesPerView: 1,
                spaceBetween: 20,
                loop: displayType === 'slider', // Loop only on standard carousel
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                pagination: displayType === 'slider' ? { el: '.swiper-pagination', clickable: true } : false,
                breakpoints: displayType === 'slider' ? { 768: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } } : {},
                thumbs: displayType === 'slider_thumbs' ? { swiper: thumbsSwiper } : false
            });
        }

        // 2. SimpleLightbox Initialization
        if (typeof SimpleLightbox !== 'undefined') {
            const lightboxLinks = gallery.querySelectorAll('.cag-lightbox');
            
            if (lightboxLinks.length > 0) {
                new SimpleLightbox(lightboxLinks, {
                    captionsData: 'alt', 
                    captionDelay: 250,
                    // Map the custom HTML caption structure from the data attribute
                    captionSelector: function(el) {
                        return el.getAttribute('data-caption');
                    },
                    captionPosition: 'bottom', 
                    showCounter: true,
                    alertError: true
                });
            }
        }
    });
});
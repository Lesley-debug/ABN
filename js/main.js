(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner(0);
    
    
    // Initiate the wowjs
    new WOW().init();


    // Sticky Navbar: keep it pinned, only toggle shadow on scroll
    $(window).scroll(function () {
        if ($(this).scrollTop() > 20) {
            $('.sticky-top').addClass('shadow-sm');
        } else {
            $('.sticky-top').removeClass('shadow-sm');
        }
    });


    // testimonial carousel (with no-plugin fallback so content still shows)
    if ($.fn.owlCarousel) {
        $(".testimonial-carousel").owlCarousel({
            autoplay: true,
            smartSpeed: 1500,
            center: false,
            dots: true,
            loop: true,
            margin: 25,
            nav : true,
            navText : [
                '<i class="fa fa-angle-right"></i>',
                '<i class="fa fa-angle-left"></i>'
            ],
            responsiveClass: true,
            responsive: {
                0:{
                    items:1
                },
                576:{
                    items:1
                },
                768:{
                    items:1
                },
                992:{
                    items:2
                },
                1200:{
                    items:2
                }
            }
        });
    } else {
        $('.testimonial-carousel').removeClass('owl-carousel').css('display', 'block');
    }


    // Facts counter
    $('[data-toggle="counter-up"]').counterUp({
        delay: 5,
        time: 2000
    });


   // Back to top button
   $(window).scroll(function () {
    if ($(this).scrollTop() > 300) {
        $('.back-to-top').fadeIn('slow');
    } else {
        $('.back-to-top').fadeOut('slow');
    }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });

    // Search modal handler: redirect to search results page.
    (function () {
        var searchModal = document.getElementById('searchModal');
        if (!searchModal) return;

        var searchInput = searchModal.querySelector('input[type="search"]');
        var searchTrigger = searchModal.querySelector('#search-icon-1');
        if (!searchInput || !searchTrigger) return;

        searchTrigger.style.cursor = 'pointer';
        var runSearch = function () {
            var q = searchInput.value.trim();
            if (!q) {
                searchInput.focus();
                return;
            }
            window.location.href = 'search.php?q=' + encodeURIComponent(q);
        };

        searchTrigger.addEventListener('click', runSearch);
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                runSearch();
            }
        });
    })();

    // Contact page status message from query string.
    (function () {
        var statusContainer = document.getElementById('contact-status');
        if (!statusContainer) return;

        var params = new URLSearchParams(window.location.search);
        var status = params.get('contact');
        if (!status) return;

        if (status === 'success') {
            statusContainer.innerHTML = '<div class="alert alert-success mb-0">Message sent successfully. Our team will contact you soon.</div>';
            return;
        }

        statusContainer.innerHTML = '<div class="alert alert-danger mb-0">Unable to send your message right now. Please try again.</div>';
    })();

    // AJAX contact form submit.
    (function () {
        var form = document.getElementById('contact-form');
        var statusContainer = document.getElementById('contact-status');
        if (!form || !statusContainer) return;

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Sending...';
            }

            statusContainer.innerHTML = '';
            var formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (response) {
                return response.json().catch(function () {
                    return {
                        ok: false,
                        message: 'Unexpected server response.'
                    };
                });
            })
            .then(function (payload) {
                var ok = !!payload.ok;
                var message = payload.message || (ok ? 'Message sent successfully.' : 'Unable to send message.');
                statusContainer.innerHTML =
                    '<div class="alert ' + (ok ? 'alert-success' : 'alert-danger') + ' mb-0">' +
                    message +
                    '</div>';
                if (ok) {
                    form.reset();
                }
            })
            .catch(function () {
                statusContainer.innerHTML = '<div class="alert alert-danger mb-0">Network error. Please try again.</div>';
            })
            .finally(function () {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Send Message';
                }
            });
        });
    })();

})(jQuery);


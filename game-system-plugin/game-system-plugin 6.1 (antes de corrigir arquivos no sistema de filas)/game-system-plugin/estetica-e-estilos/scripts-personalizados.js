jQuery(document).ready(function ($) {
    // Adiciona um efeito de escala ao passar o mouse sobre elementos com a classe "scale-up"
    $('.scale-up').on('mouseenter', function () {
        $(this).css('transform', 'scale(1.1)');
    }).on('mouseleave', function () {
        $(this).css('transform', 'scale(1)');
    });

    // Adiciona um efeito de fade-in ao carregar elementos com a classe "fade-in"
    if ($('.fade-in').length) {
        $('.fade-in').each(function () {
            $(this).css('opacity', 0).animate({ opacity: 1 }, 1000);
        });
    }
});
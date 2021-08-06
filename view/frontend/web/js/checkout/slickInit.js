define([
    'jquery',
    'slick'
], function ($, slick) {
    'use strict';

    $.widget('nwt.slickInit', {
        options: {
            dots: false,
            arrows: true,
            infinite: false,
            speed: 300,
            slidesToShow: 4,
            slidesToScroll: 4
        },

        _create: function () {
            this._bindSlick();
        },

        _bindSlick: function () {            
            $(this.element).slick({
                dots: this.options.dots,
                arrows: this.options.arrows,
                infinite: this.options.infinite,
                speed: parseFloat(this.options.speed),
                slidesToShow: parseInt(this.options.slidesToShow),
                slidesToScroll: parseInt(this.options.slidesToScroll),
                responsive: [
                    {
                        breakpoint: 1280,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3
                        }
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 2
                        }
                    },
                    {
                        breakpoint: 600,
                        settings: {
                            slidesToShow: 1.5,
                            slidesToScroll: 1
                        }
                    }
                ]
            })
        }
    });

    return $.nwt.slickInit;
});

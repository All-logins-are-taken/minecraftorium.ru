var elixir = require('laravel-elixir');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

elixir(function(mix) {
    mix.sass('app.scss');

    mix.scripts(
        [
            "materialize/initial.js",
            "materialize/jquery.easing.1.3.js",
            "materialize/animation.js",
            "materialize/velocity.min.js",
            "materialize/hammer.min.js",
            "materialize/jquery.hammer.js",
            "materialize/global.js",
            "materialize/collapsible.js",
            "materialize/dropdown.js",
            "materialize/leanModal.js",
            "materialize/materialbox.js",
            "materialize/parallax.js",
            "materialize/tabs.js",
            "materialize/tooltip.js",
            "materialize/waves.js",
            "materialize/toasts.js",
            "materialize/sideNav.js",
            "materialize/scrollspy.js",
            "materialize/forms.js",
            "materialize/slider.js",
            "materialize/cards.js",
            "materialize/chips.js",
            "materialize/pushpin.js",
            "materialize/buttons.js",
            "materialize/transitions.js",
            "materialize/scrollFire.js",
            "materialize/date_picker/picker.js",
            "materialize/date_picker/picker.date.js",
            "materialize/character_counter.js",
            "materialize/carousel.js"
        ],
        'public/js/materialize.js'
    );

    mix.version(['css/app.css', 'js/materialize.js']);
});

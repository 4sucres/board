import $ from 'jquery';
import CustomFileInput from 'bs-custom-file-input';
import HighlightJS from 'highlightjs';
import * as Ladda from 'ladda';
import AuthedAxios from './axios';
import Toast from './toasts';
import WOW from 'wowjs';
import moment from 'moment';

global.jQuery = require('jquery');
require('owl.carousel');

/**
 * Gère le chargement de diverses fonctionnalités.
 */
class Setup {
  constructor() {
    $(document).ready(() => this.initialize());
  }

  initialize() {
    CustomFileInput.init();
    this.initializeSelect2();
    this.initializeTooltips();
    this.initializeAjax();
    this.initializeHighlightJS();
    this.initializeOwl();
    this.initializeLadda();
    this.initializeForms();
    this.initializeWow();
    this.initializeCountdown();
  }

  initializeSelect2() {
    $('select:visible').select2({
      theme: 'bootstrap4',
    });
  }

  initializeTooltips() {
    $('[data-toggle="tooltip"]').tooltip({
      container: 'body',
    });
  }

  initializeAjax() {
    // $.ajaxSetup({
    //     headers: {
    //         'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content')
    //     }
    // });
  }

  initializeHighlightJS() {
    HighlightJS.initHighlightingOnLoad();
  }

  initializeForms() {
    $('[data-js-submit]').submit(function(e) {
      e.preventDefault();

      let $submitBtn = $(this);
      let $form = $($submitBtn.closest('form'));

      AuthedAxios({
        method: $form.attr('method'),
        url: $form.attr('data-url'),
        data: new FormData($form[0]),
      })
        .then((response) => {
          if (response.data.redirect) {
            window.location.href = response.data.redirect;
          }
        })
        .catch((error) => {
          let message = '';

          if (error.response.status == 422) {
            for (let [fieldName, fieldErrors] of Object.entries(error.response.data.errors)) {
              message += fieldErrors[0] + '<br>';
            }
          } else {
            message = 'Erreur ' + error.response.status + '<br>' + error.response.data.message;
          }

          Toast.create(
            {
              message: message,
              balloon: 0,
              layout: 2,
              position: 'center',
              closeOnClick: true,
              overlay: true,
            },
            'error',
          );

          Ladda.stopAll();
        });
    });
  }

  initializeLadda() {
    Ladda.bind('button[type=submit]');
  }

  initializeWow() {
    new WOW.WOW().init();
  }

  initializeOwl() {
    $('.owl-carousel').owlCarousel({
      autoplay: true,
      autoplaySpeed: 500,
      autoplayTimeout: 10000,
      loop: true,
      items: 1,
    });
  }

  initializeCountdown() {
    var $timer = $('.countdown>.row>.block>.timer');
    var then = '01/01/2020 00:00:00';

    if ($timer) {
      setInterval(() => {
        var html = '';
        var ms = moment(then, 'DD/MM/YYYY HH:mm:ss').diff(moment());
        var d = moment.duration(ms);
        var s = Math.floor(d.asHours()) + moment.utc(ms).format('mmss');

        html = s.padStart(6, '0').replace('0', '<span class="dimmed">0</span>');
        $timer.html(html);
      }, 1000);
    }
  }
}

export default new Setup();

define(
  ['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates'],
  function ($, AJAX, NOTIFICATION, STR, TEMPLATES) {
    return {
      generate: function (uniqid) {
        var amount = $('#amount-' + uniqid).val();
        var publisherid = $('#publisherid-' + uniqid).val();
        // console.log({ amount: amount, publisherid: publisherid });

        AJAX.call([{
          methodname: 'block_edupublisher_licence_generate',
          args: {amount: amount, publisherid: publisherid},
          done: function (result) {
            $('#licencekeys-' + uniqid).val(result);
          },
          fail: NOTIFICATION.exception
        }]);
      },
      generateNow: function (uniqid) {
        var licencekeys = $('#licencekeys-' + uniqid).val();
        var publisherid = $('#publisherid-' + uniqid).val();
        AJAX.call([{
          methodname: 'block_edupublisher_licence_generatenow',
          args: {licencekeys: licencekeys, publisherid: publisherid},
          done: function () {
            $('#licencekeys-' + uniqid).val('');
          },
          fail: NOTIFICATION.exception
        }]);
      },
      // Before we submit the form all amounts are removed if package is not checked!
      generateStepSubmit: function (uniqid, step) {
        if (step == 1) {
          $('#' + uniqid).find('.package_choice').each(function (index, ele) {
            if (!$(ele).prop('checked')) {
              $(ele).parent().find('.package_amount').val('');
            }
          });
        }
      },
      generateStepUi: function (uniqid, step) {
        if (step == 1) {
          $('#' + uniqid).find('.package_choice').each(function (index, ele) {
            var pc = $(ele);
            var line = $(ele).closest('.line');
            var type = $('#type-' + uniqid).val();
            var amount = parseInt(line.find('.package_amount').val());

            STR.get_strings([
              {'key': 'licence_amount_usages', component: 'block_edupublisher', param: {amount: amount}},
              {'key': 'licence_amount_infinite', component: 'block_edupublisher'},
              {'key': 'licence_amount_none', component: 'block_edupublisher'},
            ]).done(function (s) {
                if ((type == 2 && pc.prop('checked')) || amount > 0) {
                  line.find('.package_choice').prop('checked', true);
                  line.find('.package_amount_lbl').html(s[0]);
                  line.find('a.btn').addClass('active');
                  line.addClass('active');
                } else if ((type == 2 && pc.prop('checked')) || amount == -1) {
                  line.find('.package_choice').prop('checked', true);
                  line.find('.package_amount_lbl').html(s[1]);
                  line.find('a.btn').addClass('active');
                  line.addClass('active');
                } else {
                  line.find('.package_choice').prop('checked', false);
                  line.find('.package_amount_lbl').html(s[2]);
                  line.find('a.btn').removeClass('active');
                  line.removeClass('active');
                }
              }
            ).fail(NOTIFICATION.exception);
          });
        }
      },
      loadList: function (uniqid) {
        var publisherid = $('#publisherid-' + uniqid).val();
        AJAX.call([{
          methodname: 'block_edupublisher_licence_list',
          args: {publisherid: publisherid},
          done: function (result) {
            try {
              result = JSON.parse(result);
              var licences = Object.keys(result);
              var ul = $('#licences-' + uniqid).empty();
              if (licences.length == 0) {
                STR.get_strings([
                  {'key': 'licences_none', component: 'block_edupublisher'},
                ]).done(function (s) {
                    $(ul).append($('<li>').html(s[0]));
                  }
                ).fail(NOTIFICATION.exception);
              } else {
                for (var a = 0; a < licences.length; a++) {
                  var licence = result[licences[a]];
                  $(ul).append($('<li>').append($('<a>').attr('href', '#').html(licence.licencekey)));
                }
              }
            } catch (e) {
              // eslint-disable-next-line no-console
              console.error('Invalid response');
            }
          },
          fail: NOTIFICATION.exception
        }]);
      },
      /**
       * Request the type of a licence and show an appropriate modal.
       * @param {string} uniqid of template.
       * @param {string} targetid (optional) to redeem licence.
       */
      redeem: function (uniqid, targetid) {
        if (typeof targetid === 'undefined') {
          targetid = 0;
        }
        var LICENCE = this;
        var licencekey = $('#addlicence-' + uniqid + ' input[type="text"]').val();
        // console.log({ licencekey: licencekey, targetid: targetid });

        AJAX.call([{
          methodname: 'block_edupublisher_licence_redeem',
          args: {licencekey: licencekey, targetid: targetid},
          done: function (result) {
            try {
              result = JSON.parse(result);
              if (typeof result.error !== 'undefined') {
                NOTIFICATION.alert(result.heading, result.error);
              } else if (typeof result.success !== 'undefined') {
                // eslint-disable-next-line no-self-assign
                top.location.href = top.location.href;
              } else if (typeof result.options !== 'undefined') {
                result.myuniqid = uniqid;
                require(['core/modal_factory', 'core/modal_events'], function (ModalFactory, ModalEvents) {
                  ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: result.heading,
                    body: TEMPLATES.render('block_edupublisher/licence_redeem_options', result),
                  }, undefined).done(function (modal) {
                    // console.log(modal);
                    modal.show();
                    var root = modal.getRoot();
                    root.on(ModalEvents.save, function () {
                      var targetid = $('#targetid-' + uniqid).val();
                      LICENCE.redeem(uniqid, targetid);
                    });
                  });
                });
              }
            } catch (e) {
              // eslint-disable-next-line no-console
              console.error('Invalid response', e);
            }
          },
          fail: NOTIFICATION.exception
        }]);
      },
    };
  });

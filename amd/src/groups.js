define(
  ['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates', 'core/url', 'core/modal'],
  function ($, Ajax, Notification, Str, Templates, Url, Modal) {
    return {
      /**
       * Enlarge a QR-Code.
       * @param {string} a html selector
       */
      enlargeQR: function (a) {
        var qr = $(a).html();
        Modal.create({
          title: 'QR Code',
          body: $(qr).css('width', '450px').css('height', '450px'),
          footer: '',
          show: true,
        }).then(function (modal) {
          $(modal.getRoot()).css('text-align', 'center');
        });
      },
      rename: function (inp) {
        var groupid = $(inp).attr('data-groupid');
        var name = $(inp).val();

        if (name.length < 1) {
          return;
        }
        if (name.length > 50) {
          return;
        }

        Ajax.call([{
          methodname: 'block_edupublisher_group_rename',
          args: {groupid: groupid, name: name},
          done: function (result) {
            result = JSON.parse(result);
            if (typeof result.error == 'undefined') {
              $(inp).addClass('alert-success');
              $(inp).attr('data-shadow', name);
              setTimeout(function () {
                if ($(inp).attr('data-shadow') == name) {
                  $(inp).removeClass('alert-success');
                  $(inp).attr('data-shadow', '');
                }
              }, 2000);
            } else {
              Str.get_strings([
                {'key': 'error', component: 'core'},
              ]).done(function (s) {
                  Notification.alert(s[0], result.error);
                }
              ).fail(Notification.exception);
            }
          },
          fail: Notification.exception
        }]);
      },
    };
  });

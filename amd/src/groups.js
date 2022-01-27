define(
    ['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates', 'core/url', 'core/modal_factory', 'core/modal_events'],
    function($, Ajax, Notification, Str, Templates, Url, ModalFactory, ModalEvents) {
    return {
        rename: function(inp) {
            var groupid = $(inp).attr('data-groupid');
            var name = $(inp).val();

            if (name.length < 4) return;
            if (name.length > 50) return;

            Ajax.call([{
                methodname: 'block_edupublisher_group_rename',
                args: { groupid: groupid, name: name },
                done: function(result) {
                    var result = JSON.parse(result);
                    if (typeof result.error == 'undefined') {
                        $(inp).addClass('alert-success');
                        $(inp).attr('data-shadow', name);
                        setTimeout(function() {
                            if ($(inp).attr('data-shadow') == name) {
                                $(inp).removeClass('alert-success');
                                $(inp).attr('data-shadow', '');
                            }
                        }, 2000);
                    } else {
                        Str.get_strings([
                                {'key' : 'error', component: 'core' },
                            ]).done(function(s) {
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

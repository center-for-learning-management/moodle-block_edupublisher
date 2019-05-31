define(
    ['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates', 'core/url'],
    function($, AJAX, NOTIFICATION, STR, TEMPLATES, URL) {
    return {
        generate: function(uniqid) {
            var amount = $('#amount-' + uniqid).val();
            var publisherid = $('#publisherid-' + uniqid).val();
            console.log({ amount: amount, publisherid: publisherid });
            AJAX.call([{
                methodname: 'block_edupublisher_licence_generate',
                args: { amount: amount, publisherid: publisherid },
                done: function(result) {
                    $('#licencekeys-' + uniqid).val(result);
                },
                fail: NOTIFICATION.exception
            }]);
        },
        generateNow: function(uniqid) {
            var licencekeys = $('#licencekeys-' + uniqid).val();
            var publisherid = $('#publisherid-' + uniqid).val();
            AJAX.call([{
                methodname: 'block_edupublisher_licence_generatenow',
                args: { licencekeys: licencekeys, publisherid: publisherid },
                done: function(result) {
                    $('#licencekeys-' + uniqid).val('');
                },
                fail: NOTIFICATION.exception
            }]);
        },
        // Before we submit the form all amounts are removed if package is not checked!
        generateStepSubmit: function(uniqid, step) {
            if (step == 1) {
                $('#' + uniqid).find('.package_choice').each(function() {
                    if (!$(this).prop('checked')) {
                        $(this).parent().find('.package_amount').val('');
                    }
                });
            }
        },
        generateStepUi: function(uniqid, step) {
            if (step == 1) {
                $('#' + uniqid).find('.package_choice').each(function() {
                    var pc = $(this);
                    var line = $(this).closest('.line');
                    var amount = parseInt(line.find('.package_amount').val());
                    if (pc.prop('checked')) {
                        line.find('.package_amount').css('display', 'inline-block');
                        STR.get_strings([
                                {'key' : 'licence_amount_usages', component: 'block_edupublisher', param: { amount: amount } },
                                {'key' : 'licence_amount_infinite', component: 'block_edupublisher' },
                                {'key' : 'licence_amount_none', component: 'block_edupublisher' },
                            ]).done(function(s) {

                                if (amount > 0){
                                    line.find('.package_amount_lbl').html(s[0]);
                                } else if (amount == -1) {
                                    line.find('.package_amount_lbl').html(s[1]);
                                } else {
                                    line.find('.package_amount_lbl').html(s[2]);
                                }
                            }
                        ).fail(NOTIFICATION.exception);
                    } else {
                        line.find('.package_amount').css('display', 'none');
                        line.find('.package_amount_lbl').html('');
                    }
                });
            }
        },
        loadList: function(uniqid) {
            var MAIN = this;
            var publisherid = $('#publisherid-' + uniqid).val();
            AJAX.call([{
                methodname: 'block_edupublisher_licence_list',
                args: { publisherid: publisherid },
                done: function(result) {
                    try {
                        result = JSON.parse(result);
                        var licences = Object.keys(result);
                        var ul = $('#licences-' + uniqid).empty();
                        if (licences.length == 0) {
                            STR.get_strings([
                                    {'key' : 'licences_none', component: 'block_edupublisher' },
                                ]).done(function(s) {
                                    $(ul).append($('<li>').html(s[0]));
                                }
                            ).fail(NOTIFICATION.exception);
                        } else {
                            for (var a = 0; a < licences.length; a++) {
                                var licence = result[licences[a]];
                                $(ul).append($('<li>').append($('<a>').attr('href', '#').html(licence.licencekey)));
                            }
                        }
                    } catch(e) {
                        console.error('Invalid response');
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        },
    };
});

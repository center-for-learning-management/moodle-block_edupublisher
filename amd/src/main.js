define(
    ['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates', 'core/url', 'core/modal_factory', 'core/modal_events'],
    function($, AJAX, NOTIFICATION, STR, TEMPLATES, URL, ModalFactory, ModalEvents) {
    return {
        searchid: 0, // Ensures that only the last search is shown.
        loadpositions: {},
        cancelPackageForm: function(url) {
            var MAIN = this;
            STR.get_strings([
                    {'key' : 'removal:title', component: 'block_edupublisher' },
                    {'key' : 'removal:text', component: 'block_edupublisher' },
                    {'key' : 'yes' },
                    {'key': 'no'}
                ]).done(function(s) {
                    NOTIFICATION.confirm(s[0], s[1], s[2], s[3], function() {
                        top.location.href = url;
                    });
                }
            ).fail(NOTIFICATION.exception);
        },
        clickImportConfirmation: function() {
            $('#page-content div[role="main"] form[action*="/edupublisher/pages/import.php"]').submit();
        },
        confirmDeleteGroup: function(url) {
            var MAIN = this;
            STR.get_strings([
                    {'key' : 'groups:remove:title', component: 'block_edupublisher' },
                    {'key' : 'groups:remove:text', component: 'block_edupublisher' },
                    {'key' : 'yes' },
                    {'key': 'no'}
                ]).done(function(s) {
                    NOTIFICATION.confirm(s[0], s[1], s[2], s[3], function() {
                        top.location.href = url;
                    });
                }
            ).fail(NOTIFICATION.exception);
        },
        confirmRemoval: function(url) {
            var MAIN = this;
            STR.get_strings([
                    {'key' : 'removal:title', component: 'block_edupublisher' },
                    {'key' : 'removal:text', component: 'block_edupublisher' },
                    {'key' : 'yes' },
                    {'key': 'no'}
                ]).done(function(s) {
                    NOTIFICATION.confirm(s[0], s[1], s[2], s[3], function() {
                        top.location.href = url;
                    });
                }
            ).fail(NOTIFICATION.exception);
        },
        exportCourseWarning: function() {
            var MAIN = this;
            var active = $('#id_exportcourse').is(':checked');
            if (!active) {
                STR.get_strings([
                        {'key' : 'exportcourse', component: 'block_edupublisher' },
                        {'key' : 'exportcourse_help', component: 'block_edupublisher' },
                        {'key' : 'yes' },
                        {'key': 'no'}
                    ]).done(function(s) {
                        NOTIFICATION.confirm(s[0], s[1], s[2], s[3], function() {}, function() { $('#id_exportcourse').prop('checked', true); });
                    }
                ).fail(NOTIFICATION.exception);
            }
        },
        initImportSelection: function(packageid, courseid, allowsubcourses) {
            var MAIN = this;
            STR.get_strings([
                    {'key' : 'import', component: 'core' },
                ]).done(function(s) {
                    ModalFactory.create({
                        title: s[0],
                        body: TEMPLATES.render('block_edupublisher/init_import_selection', { packageid: packageid, courseid: courseid, allowsubcourses: allowsubcourses }),
                    }).done(function(modal) {
                        modal.show();
                    });
                }
            ).fail(NOTIFICATION.exception);
        },
        initImportLoadGo: function(uniqid) {
            var MAIN = this;
            var courseid = $('#courseid-' + uniqid).val();
            var packageid = $('#packageid-' + uniqid).val();
            var sectionid = $('#sectionid-' + uniqid).val();
            var assubcourse = $('#assubcourse-' + uniqid).prop('checked') ? 1 : 0;
            top.location.href = URL.fileUrl("/blocks/edupublisher/pages/import.php", "?package=" + packageid + "&course=" + courseid + "&section=" + sectionid + '&assubcourse=' + assubcourse);
            //top.location.href = URL.fileUrl("/blocks/edupublisher/pages/restore.php", "?package=" + packageid + "&course=" + courseid + "&section=" + sectionid + '&assubcourse=' + assubcourse);
        },
        /**
         * Load list of courses we could import to.
         *
         * @param uniqid of template.
         * @param initial course to select initially.
         */
        initImportLoadCourses: function(uniqid, initial) {
            console.log('MAIN.initIportLoadCourses(uniqid, initial)', uniqid, initial);
            var MAIN = this;
            $('#courseid-' + uniqid).empty().attr('disabled', 'disabled');
            $('#sectionid-' + uniqid).empty().attr('disabled', 'disabled');
            var packageid = +$('#packageid-' + uniqid).val();
            STR.get_strings([
                    {'key' : 'loading', component: 'core' },
                ]).done(function(s) {
                    $('#courseid-' + uniqid + ', #sectionid-' + uniqid).append($('<option>').html(s[0]));
                    AJAX.call([{
                        methodname: 'block_edupublisher_init_import_load_courses',
                        args: { packageid: packageid },
                        done: function(result) {
                            console.log('Result', result);
                            var result = JSON.parse(result);
                            $('#courseid-' + uniqid).empty();
                            if (typeof result.courses !== 'undefined' && Object.keys(result.courses).length > 0) {
                                $('#courseid-' + uniqid).removeAttr('disabled');
                                var first = 0;
                                Object.keys(result.courses).forEach(function(courseid) {
                                    var c = result.courses[courseid];
                                    var opt = $('<option>').attr('value', c.id).html(c.fullname);
                                    $('#courseid-' + uniqid).append(opt);
                                    if (first == 0 && typeof initial !== 'undefined') {
                                        first = c.id;
                                    }
                                    if (typeof initial !== 'undefined' && initial == c.id) {
                                        opt.attr('selected', 'selected');
                                    }
                                });
                                MAIN.initImportLoadSections(uniqid);
                            } else {
                                STR.get_strings([
                                        {'key' : 'error', component: 'core' },
                                    ]).done(function(s) {
                                        $('#courseid-' + uniqid).append($('<option>').html(s[0]));
                                        $('#sectionid-' + uniqid).append($('<option>').html(s[0]));
                                    }
                                ).fail(NOTIFICATION.exception);
                                NOTIFICATION.show('error', 'no courses');
                            }
                        },
                        fail: NOTIFICATION.exception
                    }]);
                }
            ).fail(NOTIFICATION.exception);
        },
        initImportLoadSections: function(uniqid) {
            var MAIN = this;
            $('#sectionid-' + uniqid).empty().attr('disabled', 'disabled');
            STR.get_strings([
                    {'key' : 'loading', component: 'core' },
                ]).done(function(s) {
                    $('#sectionid-' + uniqid).append($('<option>').html(s[0]));
                    AJAX.call([{
                        methodname: 'block_edupublisher_init_import_load_sections',
                        args: { courseid: +$('#courseid-' + uniqid).val() },
                        done: function(result) {
                            console.log('Result', result);
                            var result = JSON.parse(result);
                            $('#sectionid-' + uniqid).empty();
                            if (typeof result.sections !== 'undefined' && Object.keys(result.sections).length > 0) {
                                $('#sectionid-' + uniqid).removeAttr('disabled');
                                var i = 0;
                                Object.keys(result.sections).forEach(function(sectionid) {
                                    var s = result.sections[sectionid];
                                    if (!s.name) s.name = "#" + (i);
                                    $('#sectionid-' + uniqid).append($('<option>').attr('value', s.id).html(s.name));
                                    i++;
                                });
                            } else {
                                STR.get_strings([
                                        {'key' : 'error', component: 'core' },
                                    ]).done(function(s) {
                                        $('#sectionid-' + uniqid).append($('<option>').html(s[0]));
                                    }
                                ).fail(NOTIFICATION.exception);
                            }
                        },
                        fail: NOTIFICATION.exception
                    }]);
                }
            ).fail(NOTIFICATION.exception);
        },
        injectEnrolButton: function(courseid, isguestuser) {
            console.log('MAIN.injectEnrolButton(courseid, isguestuser)', courseid, isguestuser);
            if (isguestuser) return;
            var context = {
                isguestuser: isguestuser,
                url: URL.relativeUrl('/blocks/edupublisher/pages/self_enrol.php', { id: courseid }),
            };

            // This will call the function to load and render our template.
            TEMPLATES.render('block_edupublisher/inject_enrol_button', context)
                .then(function(html, js) {
                    // Here eventually I have my compiled template, and any javascript that it generated.
                    // The templates object has append, prepend and replace functions.
                    TEMPLATES.prependNodeContents('#page-content #region-main-box', html, js);
                    // Remove the default course-guestaccess-infobox
                    $('.course-guestaccess-infobox').remove();
                })
                .fail(NOTIFICATION.exception);
        },
        preparePackageForm: function(channels) {
            var MAIN = this;
            console.log('MAIN.preparePackageForm(channels)', channels);
            require(["jquery"], function($) {
                if (typeof channels !== 'undefined') {
                    channels = channels.split(',');
                    for (var a = 0; a < channels.length; a++) {
                        if (channels[a] == 'default') continue;
                        // The former one is the checkbox, the latter on the div.
                        if (!$('div[role="main"] #id_' + channels[a] + '_publishas').is(":checked")) {
                            $('div[role="main"] #id_' + channels[a] + '_publish_as').css("display", "none");
                        } else if($('div[role="main"] input[name="id"]').val() > 0) {
                            // If id greater 0 and already active disable this box.
                            $('div[role="main"] #id_' + channels[a] + '_publishas').attr('disabled', 'disabled');
                        }
                    }
                }
            });
        },
        search: function(uniqid, courseid, sectionid) {
            var MAIN = this;
            console.log('MAIN.search(uniqid, courseid, sectionid)', uniqid, courseid, sectionid);

            MAIN.watchValue({
                courseid: courseid,
                sectionid: sectionid,
                target: '#' + uniqid + '-search',
                uniqid: uniqid,
                run: function() {
                    var o = this;
                    require(['block_edupublisher/main'], function(MAIN) { MAIN.searchNow(o); });
                }
            }, 200);
        },
        /**
         * Do the search.
         * @param object containing courseid, sectionid, target and uniqid.
         * @param sender html-object that caused the event.
         */
        searchNow: function(o, sender) {
            o.subjectareas = [];
            o.schoollevels = [];
            if (typeof sender !== 'undefined') {
                if ($(sender).attr('name') == 'subjectarea') {
                    $(sender).toggleClass('selected');
                    $('.' + o.uniqid + '-subjectarea').prop('checked', false);
                    $('.' + o.uniqid + '-subjectarea.selected').prop('checked', true);
                }
                if ($(sender).attr('name') == 'schoollevel') {
                    $(sender).toggleClass('selected');
                    $('.' + o.uniqid + '-schoollevel').prop('checked', false);
                    $('.' + o.uniqid + '-schoollevel.selected').prop('checked', true);
                }
            }
            $('.' + o.uniqid + '-subjectarea.selected').each(function() {
                o.subjectareas[o.subjectareas.length] = $(this).attr('value');
            });
            $('.' + o.uniqid + '-schoollevel.selected').each(function() {
                o.schoollevels[o.schoollevels.length] = $(this).attr('value');
            });
            o.search = $('#' + o.uniqid + '-search').val();
            // Generate object for sending (only some parameters accepted by webservice)
            var o2 = { courseid: o.courseid, search: o.search, subjectareas: o.subjectareas.join(','), schoollevels: o.schoollevels.join(',') };
            require(['block_edupublisher/main'], function(MAIN) {
                MAIN.searchid++;
                var searchid = MAIN.searchid;
                console.log('Doing search via ajax for', o2, MAIN.searchid, searchid);
                AJAX.call([{
                    methodname: 'block_edupublisher_search',
                    args: o2,
                    done: function(result) {
                        if (MAIN.searchid != searchid) {
                            console.log(' => Got response for searchid ', searchid, ' but it is not the current search', MAIN.searchid);
                        } else {
                            console.log('Result', result, result.sql, result.sqlparams);
                            //$('ul#' + o.uniqid + '-results').empty().html(result);

                            var result = JSON.parse(result);
                            console.log('Received', result);
                            $('ul#' + o.uniqid + '-results').empty();

                            var counts = Object.keys(result.relevance);
                            var stagesrelevances = [0, 1, 2];
                            var stagesprinted = [false, false, false];
                            if (counts.length === 0) {
                                STR.get_strings([
                                        {'key' : 'search:enter_term', component: 'block_edupublisher' },
                                    ]).done(function(s) {
                                        $('ul#' + o.uniqid + '-results').append($('<li>').append('<a href="#">').append('<h3>').html(s[0]));
                                    }
                                ).fail(NOTIFICATION.exception);
                            } else if (counts.length === 1) {
                                // All are the same relevant
                                var stagesrelevances = [0, 0, counts[0], counts[0] + 1];
                            } else if (counts.length === 2) {
                                // All are the only two relevant stages
                                var max = Math.round(counts[counts.length - 1]);
                                var stagesrelevances = [0, 0, max / 2, max];
                            } else {
                                // We divide into three fields
                                var max = Math.round(counts[counts.length - 1]);
                                var stagesrelevances = [0, max / 3, max / 3 * 2, max];
                            }
                            var position = 0;
                            for(var a = counts.length - 1; a >= 0; a--) {
                                var relevance = counts[a];
                                var ids = result.relevance[relevance];
                                if (ids.length == 0) continue;

                                var stage = -1;
                                for (var b = 0; b < stagesrelevances.length; b++) {
                                    console.log('Compare ', b, 'of ', stagesrelevances, ' to ', relevance)
                                    if (relevance >= stagesrelevances[b]) {
                                        stage = b;
                                    }
                                    console.log('=> Stage is ', stage);
                                }

                                if (stage > -1 && !stagesprinted[stage]) {
                                    MAIN.searchTemplate(o.uniqid, position++, 'block_edupublisher/search_li_divider', { stage0: (stage == 0), stage1: (stage == 1), stage2: (stage == 2), stage3: (stage == 3) });
                                    stagesprinted[stage] = true;
                                }

                                for (var b = 0; b < ids.length; b++) {
                                    var item = result.packages[ids[b]];
                                    item.importtocourseid = o.courseid;
                                    item.importtosectionid = o.sectionid;
                                    item.showpreviewbutton = true;
                                    console.log('Call list-template for item ', item.id);
                                    MAIN.searchTemplate(o.uniqid, position++, 'block_edupublisher/search_li', item);
                                }
                            }
                        }
                    },
                    fail: NOTIFICATION.exception
                }]);
            });
        },
        /**
         * Print all items that are loaded in listpositions.
         */
        searchPrint: function(uniqid) {
            var MAIN = this;
            var ok = true;
            var positions = Object.keys(MAIN.loadpositions[uniqid]);
            for (var a = 0; a < positions.length; a++) {
                var position = positions[a];
                if (!MAIN.loadpositions[uniqid][position]) ok = false;
            };
            if (ok) {
                // Everything was loaded!
                for (var a = 0; a < positions.length; a++) {
                    var position = positions[a];
                    TEMPLATES.appendNodeContents('ul#' + uniqid + '-results',
                        MAIN.loadpositions[uniqid][position].html,
                        MAIN.loadpositions[uniqid][position].js
                    );
                };
            }
        },
        /**
         * Loads a specific template.
         */
        searchTemplate: function(uniqid, position, template, o) {
            console.log('Call template', template, ' for object ', o);
            var MAIN = this;
            if (typeof MAIN.loadpositions[uniqid] === 'undefined') {
                MAIN.loadpositions[uniqid] = [];
            }
            MAIN.loadpositions[uniqid][position] = false;
            TEMPLATES
                .render(template, o)
                .then(function(html, js) {
                    console.log('Received a template', template, ' for object', o);
                    MAIN.loadpositions[uniqid][position] = { html: html, js: js };
                    MAIN.searchPrint(uniqid);
                    //templates.appendNodeContents('ul#' + o.uniqid + '-results', html, js);
                }).fail(function(ex) {
                    console.error(ex);
                });
        },
        storePublisher: function(uniqid, sender) {
            var MAIN = this;
            var self = this;
            var active = $('#active-' + uniqid).prop('checked') ? 1 : 0;
            var id = parseInt($('#id-' + uniqid).val());
            var name = $('#name-' + uniqid).val();
            var mail = $('#mail-' + uniqid).val();
            if (name.length == 0) return;
            var data = { active: active, id: id, name: name, mail: mail };
            console.log(data, sender);
            AJAX.call([{
                methodname: 'block_edupublisher_store_publisher',
                args: data,
                done: function(result) {
                    try {
                        result = JSON.parse(result);
                        var publishers = Object.keys(result);
                        self.triggerConfirm($(sender).closest('tr'), 1, 'success');
                        for (var a = 0; a < publishers.length; a++) {
                            var publisher = result[publishers[a]];
                            var form = $('#publisher-' + publisher.id);
                            if (form.length == 0) {
                                var form = $('#publisher-0').attr('id', 'publisher-' + publisher.id);
                                var uniqid = $(form).attr('data-uniqid');
                                $(form).find('#id-' + uniqid).val(publisher.id);
                                $(form).find('#edit-' + uniqid + '>*').css('opacity', 1).attr('href', '/blocks/edupublisher/pages/publishers.php?id=' + publisher.id);
                                TEMPLATES.render('block_edupublisher/publisher', { id: 0, name: '' })
                                        .then(function(html, js) {
                                            $(html).insertAfter($('.edupublisher-publishers').last());
                                        }).fail(function(ex) {
                                            NOTIFICATION.exception(ex);
                                        });
                            } else {
                                // This is an existing item, update it.
                                var uniqid = $(form).attr('data-uniqid');
                                $(form).find('#active-' + uniqid).prop('checked', publisher.active);
                                $(form).find('#name-' + uniqid).val(publisher.name);
                                $(form).find('#mail-' + uniqid).val(publisher.mail);
                            }

                        }
                    } catch(e) {
                        console.error('Invalid response');
                        self.triggerConfirm($(sender).closest('tr'), 1, 'error');
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        },
        storePublisherUsers: function(uniqid, action) {
            var MAIN = this;
            var self = this;

            var publisherid = $('#publisherid-' + uniqid).val();
            var userids = '';
            if (action == 'add') {
                userids = $('#userids-' + uniqid).val();
            }
            if (action == 'remove') {
                userids = $('#users-' + uniqid).val().join(' ');
            }
            var data = { action: action, publisherid: publisherid, userids: userids };
            console.log(data);
            AJAX.call([{
                methodname: 'block_edupublisher_store_publisher_user',
                args: data,
                done: function(result) {
                    try {
                        result = JSON.parse(result);
                        console.log('Response', result);
                        var sel = $('#users-' + uniqid).empty();
                        var users = Object.keys(result);
                        for (var a = 0; a < users.length; a++) {
                            var user = result[users[a]];
                            $(sel).append($('<option>').attr('value', user.id).html(user.fullname));
                        }
                    } catch(e) {
                        console.error('Invalid response', e, result);
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        },
        triggerActive: function(packageid, type, sender){
            var MAIN = this;
            var self = this;
            console.log({packageid: packageid, type: type, to: $(sender).is(':checked') ? 1 : 0 });
            AJAX.call([{
                methodname: 'block_edupublisher_trigger_active',
                args: {packageid: packageid, type: type, to: $(sender).is(':checked') ? 1 : 0 },
                done: function(result) {
                    console.log(type, result);
                    try {
                        result = JSON.parse(result);
                        var chans = Object.keys(result);
                        for (var a = 0; a < chans.length; a++) {
                            var x = chans[a].split('_');
                            var type = x[0];
                            if (parseInt(result[chans[a]]) == 1) {
                                $('#channel-' + type).addClass('channel-active').removeClass('channel-inactive');
                                $('#channel-' + type + '-active').prop('checked', true);
                            } else {
                                $('#channel-' + type).addClass('channel-inactive').removeClass('channel-active');
                                $('#channel-' + type + '-active').prop('checked', false);
                            }
                        }
                    } catch(e) {
                        console.error('Invalid response');
                    }

                    // Not necessary, ui confirms via active/inactive classes
                    //self.triggerConfirm($(sender).parent(), 1, 'success');
                },
                fail: NOTIFICATION.exception
            }]);
        },
        triggerRating: function(uniqid, packageid, to) {
            var MAIN = this;
            var self = this;
            console.log({packageid: packageid, to: to});
            AJAX.call([{
                methodname: 'block_edupublisher_rate',
                args: {packageid: packageid, to: to },
                done: function(result) {
                    console.log(packageid, result);
                    $('#' + uniqid + '-ratingcount').html(result.amount);
                    for (var a = 1; a <= 5; a++) {
                        //console.log('Set image of #' + uniqid + '-rating-' + a + 'to /blocks/edupublisher/pix/star_' + ((result.average >= a) ? 1 : 0) + '_' + ((result.current == a) ? 1 : 0) + '.png');
                        $('#' + uniqid + '-rating-' + a).attr('src', '/blocks/edupublisher/pix/star_' + ((result.average >= a) ? 1 : 0) + '_' + ((result.current == a) ? 1 : 0) + '.png');
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        },
        triggerConfirm: function(sender, step, type) {
            var MAIN = this;
            var self = this;
            if (step == 1) {
                $(sender).addClass('block-edupublisher-' + type);
                setTimeout(function() { self.triggerConfirm(sender, 0, type)  }, 2000);
            } else {
                $(sender).removeClass('block-edupublisher-' + type);
            }
        },
        watchValue: function(o, interval) {
            var MAIN = this;
            if (this.debug > 5) console.log('MAIN.watchValue(o, interval)', o, interval);
            if (typeof interval === 'undefined') interval = 1000;
            var self = this;

            if ($(o.target).attr('data-iswatched') != '1') {
                $(o.target).attr('data-iswatched', 1);

                o.interval = setInterval(
                    function() {
                         if ($(o.target).val() == o.compareto) {
                            o.run();
                            clearInterval(o.interval);
                            $(o.target).attr('data-iswatched', 0);
                         } else {
                            o.compareto = $(o.target).val();
                         }
                    },
                    interval
                );
            }
        },
    };
});

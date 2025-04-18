<?php

use block_edupublisher\package;

require __DIR__ . '/../inc.php';

require_once("$CFG->libdir/formslib.php");

$PAGE->set_url('/blocks/edupublisher/pages/list.php');

\block_edupublisher\permissions::require_login();

$PAGE->set_context(\context_system::instance());
$PAGE->set_title('Eduthek-Ressourcen');
$PAGE->set_heading('Eduthek-Ressourcen');

$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

\block_edupublisher\lib::check_requirements(true);

class block_edupublisher_resources_table extends \local_table_sql\table_sql {
    protected function define_table_configs() {
        global $USER;

        $is_maintainer = \block_edupublisher\permissions::is_maintainer();

        $params = [];

        $state_text_sql = "
            CASE
                WHEN resource.active THEN 'Veröffentlicht'
                WHEN channel_default.published THEN 'Freigegeben'
                WHEN channel_default.publishas THEN 'Eingereicht'
                ELSE 'Entwurf'
            END
        ";

        $etapas_state_text_sql = "
            CASE
                WHEN channel_etapas.publishas AND resource.active THEN 'Veröffentlicht'
                WHEN channel_etapas.publishas AND channel_etapas.is_vorschlag THEN 'Vorschlag Eingereicht'
                WHEN channel_etapas.publishas AND channel_default.publishas THEN 'Eingereicht'
                WHEN channel_etapas.publishas THEN 'Vorschlag bestätigt'
                ELSE '-'
            END
        ";
        $params[] = package::TYPE_ETAPA_VORSCHLAG;

        if ($is_maintainer) {
            // can manage all
            $where = '';
        } else {
            // can only edit own
            $where = 'AND resource.userid=?';
            $params[] = $USER->id;
        }


        $sql = "
            SELECT resource.*
                , $state_text_sql as state_text
                , $etapas_state_text_sql as channel_etapas_state_text
                , channel_default.publishas AS default_publishas
                , channel_default.published AS default_published
                , CONCAT(u.firstname, ' ', u.lastname, ' (', u.username, ')') AS user
            FROM {block_edupublisher_packages} resource
            JOIN {user} u ON resource.userid = u.id
            JOIN {block_edupublisher_md_def} channel_default ON resource.id = channel_default.package
            LEFT JOIN {block_edupublisher_md_eta} channel_etapas ON resource.id = channel_etapas.package
            WHERE resource.deleted = 0
            $where
        ";
        $this->set_sql_query($sql, $params);

        // Define headers and columns.
        $cols = array_filter([
            'id' => 'id',
            'image' => !$this->is_downloading() ? '' : null,
            'title' => get_string('title', 'block_edupublisher'),
            'user' => $is_maintainer ? 'Benutzer' : null,
            'state_text' => 'Status',
            // 'channel_default' => $is_maintainer ? 'eduvidual' : null,
            // 'channel_eduthekneu' => $is_maintainer ? 'eduthek.neu' : null,
            'channel_etapas_state_text' => $is_maintainer ? 'eTapa' : null,
            // 'channel_eduthek' => $is_maintainer ? 'eduthek' : null,
            'time_submitted' => 'Eingereicht',
            'default_published' => 'Veröffentlicht',
        ], fn($col) => $col !== null);

        $this->set_table_columns($cols);

        $this->sortable(true, 'time_submitted', SORT_DESC);

        $this->no_sorting('image');
        $this->no_filter('image');

        $this->set_column_options('id', visible: false);
        $this->set_column_options('time_submitted', data_type: static::PARAM_TIMESTAMP);
        $this->set_column_options('default_published', data_type: static::PARAM_TIMESTAMP);


        $this->add_row_action('package_edit.php?action=edit&id={id}&returnurl=' . urlencode((new moodle_url(qualified_me()))->out_as_local_url(false)), 'edit');
        if (\block_edupublisher\permissions::is_admin()) {
            $this->add_row_action('package_delete.php?id={id}', 'delete');
        }

        if ($is_maintainer) {
            $this->is_downloadable(true, 'Edupublisher Ressourcen');
        }
    }

    function col_title($row) {
        // return $this->format_col_content($row->title, 'package.php?id=' . $row->id);
        $package = $this->get_package($row->id);
        return $this->format_col_content($row->title, new \moodle_url('/course/view.php?id=' . $package->courseid));
    }

    function col_image($row) {
        $package = $this->get_package($row->id);

        $url = $package->get_preview_image_url();
        return $url ? '<img src="' . $url . '" style="width: 40px;"/>' : '';
    }

    function col_user($row) {
        return '<a href="' . new moodle_url('/user/profile.php', ['id' => $row->userid]) . '">' . $row->user . '</a>';
    }

    function col_state_text($row) {
        if ($row->state_text == 'Veröffentlicht') {
            $class = 'badge badge-success';
        } elseif ($row->state_text == 'Eingereicht') {
            $class = 'badge badge-warning';
        } else {
            $class = 'badge badge-secondary';
        }

        return '<span class="' . $class . '">' . $row->state_text . '</span>';

        // if ($row->active) {
        //     return '<span class="badge badge-success">Veröffentlicht</span>';
        // } elseif ($row->default_published) {
        //     return 'Freigegeben';
        // } elseif ($row->default_publishas) {
        //     return '<span class="badge badge-warning">Eingereicht</span>';
        // } else {
        //     return 'Entwurf';
        // }
    }

    function col_channel_etapas_state_text($row) {
        if ($row->channel_etapas_state_text == 'Veröffentlicht') {
            $class = 'badge badge-success';
        } elseif ($row->channel_etapas_state_text == 'Eingereicht' || $row->channel_etapas_state_text == 'Vorschlag Eingereicht') {
            $class = 'badge badge-warning';
        } elseif ($row->channel_etapas_state_text == '-') {
            $class = '';
        } else {
            $class = 'badge badge-secondary';
        }

        return '<span class="' . $class . '">' . $row->channel_etapas_state_text . '</span>';

        // $package = $this->get_package($row->id);
        //
        // $channel = 'etapas';
        // $published = $package->get('published', $channel);
        // $publishas = $package->get('publishas', $channel);
        //
        // if ($published) {
        //     return '<span class="badge badge-success">Veröffentlicht</span>';
        // } elseif ($publishas) {
        //     return '<span class="badge badge-warning">Todo</span>';
        // } else {
        //     return '-';
        // }
    }

    function get_package($id): \block_edupublisher\package {
        static $packages = [];

        return $packages[$id] ??= \block_edupublisher\package::get_package($id, true);
    }

    function get_row_actions(object $row, array $row_actions): ?array {
        $row_actions[0]->disabled = !$this->get_package($row->id)->can_edit();
        return $row_actions;
    }
}

$resources_table = new block_edupublisher_resources_table();

echo $OUTPUT->header();

?>
    <a class="btn btn-secondary my-3" href="package_edit.php?action=add">Ressource erstellen</a>
<?php

$resources_table->out();

echo $OUTPUT->footer();

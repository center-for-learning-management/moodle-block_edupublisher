<?php

require __DIR__ . '/../inc.php';

require_once("$CFG->libdir/formslib.php");

$PAGE->set_url('/blocks/edupublisher/pages/list.php');

require_login();

$PAGE->set_context(\context_system::instance());
$PAGE->set_title('Eduthek-Ressourcen');
$PAGE->set_heading('Eduthek-Ressourcen');

$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

\block_edupublisher\lib::check_requirements();

class block_edupublisher_resources_table extends \local_table_sql\table_sql {
    protected function define_table_configs() {
        global $USER;

        $category = get_config('block_edupublisher', 'category');
        $context = context_coursecat::instance($category);

        $maintainer_default = has_capability('block/edupublisher:managedefault', $context);
        if ($maintainer_default) {
            // can manage all
            $where = '';
        } else {
            // can only edit own
            $where = 'AND resource.userid = ' . $USER->id;
        }

        $sql = "
            SELECT resource.*, def.publishas AS default_publishas, def.published AS default_published, u.username, u.firstname, u.lastname
            FROM {block_edupublisher_packages} resource
            JOIN {user} u ON resource.userid = u.id
            JOIN {block_edupublisher_md_def} def ON resource.id = def.package
            WHERE resource.deleted = 0
            $where
        ";
        $this->set_sql_query($sql);

        // Define headers and columns.
        $cols = array_filter([
            'image' => '',
            'title' => get_string('title', 'block_edupublisher'),
            'username' => $maintainer_default ? 'Benutzer' : null,
            'state' => 'Status',
            'channel_default' => $maintainer_default ? 'eduvidual' : null,
            'channel_eduthekneu' => $maintainer_default ? 'eduthek.neu' : null,
            'channel_etapas' => $maintainer_default ? 'etapa' : null,
            'channel_eduthek' => $maintainer_default ? 'eduthek' : null,
        ], fn($col) => $col !== null);

        $this->set_table_columns($cols);

        $this->sortable(true, 'title', SORT_ASC);

        $this->no_sorting('image');
        $this->no_filter('image');
        $this->no_sorting('state');
        $this->no_filter('state');

        $this->no_sorting('state');
        $this->no_filter('state');
        $this->no_sorting('channel_default');
        $this->no_filter('channel_default');
        $this->no_sorting('channel_etapas');
        $this->no_filter('channel_etapas');
        $this->no_sorting('channel_eduthek');
        $this->no_filter('channel_eduthek');
        $this->no_sorting('channel_eduthekneu');
        $this->no_filter('channel_eduthekneu');

        $this->add_row_action('package_edit.php?action=edit&id={id}&return_url=' . urlencode((new moodle_url(qualified_me()))->out_as_local_url(false)), 'edit');
        // $this->add_row_action('package_edit.php?action=delete&id={id}', 'delete');
    }

    function col_title($row) {
        return $this->format_col_content($row->title, 'package.php?id=' . $row->id);
    }

    function col_image($row) {
        $package = $this->get_package($row->id);

        $url = $package->get_preview_image_url();
        return $url ? '<img src="' . $url . '" style="width: 40px;"/>' : '';
    }

    function col_state($row) {
        $package = $this->get_package($row->id);

        if ($row->active) {
            return '<span class="badge badge-success">Veröffentlicht</span>';
        } elseif ($row->default_published) {
            return 'Freigegeben';
        } elseif ($row->default_publishas) {
            return '<span class="badge badge-warning">Eingereicht</span>';
        } else {
            return 'Entwurf';
        }
    }

    function get_package($id): \block_edupublisher\package {
        static $packages = [];

        return $packages[$id] ??= new \block_edupublisher\package($id, true);
    }

    function get_row_actions(object $row, array $row_actions): ?array {
        $row_actions[0]->disabled = !$this->get_package($row->id)->can_edit();
        return $row_actions;
    }

    function other_cols($column, $row) {
        $package = $this->get_package($row->id);

        if (preg_match('!^channel_(.*)$!', $column, $matches)) {
            $channel = $matches[1];
            $published = $package->get('published', $channel);
            $publishas = $package->get('publishas', $channel);

            if ($published) {
                return '<span class="badge badge-success">Veröffentlicht</span>';
            } elseif ($publishas) {
                return '<span class="badge badge-warning">Todo</span>';
            } else {
                return '-';
            }
        }

        return parent::other_cols($column, $row);
    }
}

$resources_table = new block_edupublisher_resources_table();

echo $OUTPUT->header();

?>
    <a class="btn btn-secondary my-3" href="package_edit.php?action=add">Ressource erstellen</a>
<?php

$resources_table->out();

echo $OUTPUT->footer();

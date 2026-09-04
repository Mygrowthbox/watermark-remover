<?php
if (!defined('ABSPATH')) {
    exit;
}

class WMR_Settings {

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_wmr_add_model', array($this, 'ajax_add_model'));
        add_action('wp_ajax_wmr_delete_model', array($this, 'ajax_delete_model'));

        if (get_option('wmr_ai_models') === false) {
            $this->set_default_models();
        }
    }

    public function set_default_models() {
        $defaults = array(
            // OpenRouter 100% Free Models
            array('id' => 'deepseek/deepseek-r1:free', 'name' => 'DeepSeek R1 (Free)', 'provider' => 'openrouter'),
            array('id' => 'deepseek/deepseek-chat:free', 'name' => 'DeepSeek V3 (Free)', 'provider' => 'openrouter'),
            array('id' => 'meta-llama/llama-3.1-70b-instruct:free', 'name' => 'Llama 3.1 70B (Free)', 'provider' => 'openrouter'),
            array('id' => 'mistralai/mistral-7b-instruct:free', 'name' => 'Mistral 7B (Free)', 'provider' => 'openrouter'),

            // Groq Models
            array('id' => 'llama-3.1-70b-versatile', 'name' => 'Llama 3.1 70B (Groq)', 'provider' => 'groq'),
            array('id' => 'mixtral-8x7b-32768', 'name' => 'Mixtral 8x7B (Groq)', 'provider' => 'groq'),
            array('id' => 'gemma2-9b-it', 'name' => 'Gemma 2 9B (Groq)', 'provider' => 'groq'),
            array('id' => 'llama-3.1-8b-instant', 'name' => 'Llama 3.1 8B Instant (Groq)', 'provider' => 'groq')
        );
        update_option('wmr_ai_models', $defaults);
    }

    public function add_admin_menu() {
        add_options_page('Watermark Remover', 'Watermark Remover', 'manage_options', 'watermark-remover', array($this, 'render_settings'));
    }

    public function register_settings() {
        register_setting('wmr_settings_group', 'wmr_groq_api_key');
        register_setting('wmr_settings_group', 'wmr_openrouter_api_key');
    }

    public function render_settings() {
        $models = get_option('wmr_ai_models', array());
        ?>
        <div class="wrap wmr-admin-container">
            <h1>Configuration Watermark Remover Pro</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wmr_settings_group'); ?>
                <table class="form-table">
                    <tr><th>Clé API Groq</th><td><input type="password" name="wmr_groq_api_key" value="<?php echo esc_attr(get_option('wmr_groq_api_key')); ?>" class="regular-text"></td></tr>
                    <tr><th>Clé API OpenRouter</th><td><input type="password" name="wmr_openrouter_api_key" value="<?php echo esc_attr(get_option('wmr_openrouter_api_key')); ?>" class="regular-text"></td></tr>
                </table>
                <?php submit_button('Enregistrer'); ?>
            </form>

            <hr>
            <h2>Gestion des Modèles UI (Ajout / Suppression)</h2>
            <div style="display:flex; gap:10px; margin-bottom:15px;">
                <input type="text" id="wmr_new_model_id" placeholder="ID (ex: deepseek/deepseek-r1:free)" class="regular-text">
                <input type="text" id="wmr_new_model_name" placeholder="Nom (ex: DeepSeek R1 Free)" class="regular-text">
                <select id="wmr_new_model_provider"><option value="openrouter">OpenRouter</option><option value="groq">Groq</option></select>
                <button type="button" id="wmr_add_model_btn" class="button button-primary wmr-btn-accent">Ajouter</button>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Provider</th><th>Nom</th><th>ID</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($models as $idx => $m) : ?>
                        <tr>
                            <td><strong><?php echo strtoupper($m['provider']); ?></strong></td>
                            <td><?php echo esc_html($m['name']); ?></td>
                            <td><code><?php echo esc_html($m['id']); ?></code></td>
                            <td><button type="button" class="button wmr-delete-model" data-index="<?php echo $idx; ?>" style="color:#ef4444;">Supprimer</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function ajax_add_model() {
        check_ajax_referer('wmr_process_nonce', 'nonce');
        $models = get_option('wmr_ai_models', array());
        $models[] = array(
            'id' => sanitize_text_field($_POST['model_id']),
            'name' => sanitize_text_field($_POST['model_name']),
            'provider' => sanitize_text_field($_POST['model_provider'])
        );
        update_option('wmr_ai_models', $models);
        wp_send_json_success();
    }

    public function ajax_delete_model() {
        check_ajax_referer('wmr_process_nonce', 'nonce');
        $idx = intval($_POST['index']);
        $models = get_option('wmr_ai_models', array());
        if (isset($models[$idx])) {
            array_splice($models, $idx, 1);
            update_option('wmr_ai_models', $models);
        }
        wp_send_json_success();
    }
}
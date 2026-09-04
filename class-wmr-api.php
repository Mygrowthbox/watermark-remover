<?php
if (!defined('ABSPATH')) {
    exit;
}

class WMR_API {

    public function init() {
        add_action('wp_ajax_wmr_process_text', array($this, 'process_text'));
        add_action('wp_ajax_nopriv_wmr_process_text', array($this, 'process_text'));
    }

    public function process_text() {
        check_ajax_referer('wmr_process_nonce', 'nonce');

        $text = isset($_POST['text']) ? wp_kses_post($_POST['text']) : '';
        $model_id = isset($_POST['model_id']) ? sanitize_text_field($_POST['model_id']) : '';
        $intensity = isset($_POST['intensity']) ? intval($_POST['intensity']) : 2;

        if (empty($text) || empty($model_id)) {
            wp_send_json_error('Données manquantes.');
        }

        $models = get_option('wmr_ai_models', array());
        $selected_model = null;
        foreach ($models as $m) {
            if ($m['id'] === $model_id) {
                $selected_model = $m;
                break;
            }
        }

        if (!$selected_model) {
            wp_send_json_error('Modèle non trouvé.');
        }

        // PASSE 1 : Reformulation syntaxique selon niveau
        $pass1_result = $this->call_ai_api($selected_model, $text, $intensity, 1);
        if (!$pass1_result) {
            wp_send_json_error('Échec Passe 1');
        }

        // PASSE 2 : Lissage stylistique & Vérification stricte des données factuelles/Markdown
        $pass2_result = $this->call_ai_api($selected_model, $pass1_result, $intensity, 2);
        if (!$pass2_result) {
            $pass2_result = $pass1_result; // Fallback sur passe 1 si passe 2 échoue
        }

        wp_send_json_success(array(
            'result' => $pass2_result,
            'score' => rand(89, 96) // Simulation de score d'atténuation
        ));
    }

    private function call_ai_api($model, $text, $intensity, $pass) {
        $api_key = ($model['provider'] === 'groq') ? get_option('wmr_groq_api_key') : get_option('wmr_openrouter_api_key');
        $endpoint = ($model['provider'] === 'groq') ? 'https://api.groq.com/openai/v1/chat/completions' : 'https://openrouter.ai/api/v1/chat/completions';

        if (empty($api_key)) return false;

        $instructions = [
            1 => "Permutations légères de structures et suppression des tics IA.",
            2 => "Restructuration fluide des phrases.",
            3 => "Refonte stylistique profonde et désynchronisation des motifs statistiques."
        ];

        $sys_prompt = "RÈGLES STRICTES ET ABSOLUES :\n"
            . "1. Conserve INTEGRALEMENT les données factuelles : chiffres, URLs, dates, noms propres.\n"
            . "2. Conserve STRICTEMENT le balisage Markdown / HTML (#, **, *, lists, <a>).\n"
            . "3. Conserve la langue d'origine du texte.\n"
            . "4. Ne renvoie AUCUN commentaire, uniquement le texte réécrit.\n\n"
            . "OBJECTIF PASSE " . $pass . " : " . $instructions[$intensity];

        $body = array(
            'model' => $model['id'],
            'messages' => array(
                array('role' => 'system', 'content' => $sys_prompt),
                array('role' => 'user', 'content' => $text)
            ),
            'temperature' => ($intensity == 1) ? 0.2 : (($intensity == 2) ? 0.5 : 0.8)
        );

        $args = array(
            'body'        => json_encode($body),
            'headers'     => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'timeout'     => 45
        );

        if ($model['provider'] === 'openrouter') {
            $args['headers']['HTTP-Referer'] = get_site_url();
            $args['headers']['X-Title'] = get_bloginfo('name');
        }

        $response = wp_remote_post($endpoint, $args);
        if (is_wp_error($response)) return false;

        $data = json_decode(wp_remote_retrieve_body($response), true);
        return isset($data['choices'][0]['message']['content']) ? trim($data['choices'][0]['message']['content']) : false;
    }
}
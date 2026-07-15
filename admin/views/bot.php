<?php if ( ! defined( 'ABSPATH' ) ) exit;

$bot_settings = get_option( 'wai_bot_settings', [] );
$bot_enabled  = ! empty( $bot_settings['enabled'] );
$rules        = WAI_Bot::get_rules();
$stats        = WAI_Bot::get_conversation_stats();

// Handle form saves
if ( isset( $_POST['wai_bot_save_settings'] ) && check_admin_referer( 'wai_bot_settings' ) ) {
    $wai_bot_input = isset( $_POST['wai_bot'] ) ? wp_unslash( $_POST['wai_bot'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized inside WAI_Bot::save_settings().
    WAI_Bot::save_settings( $wai_bot_input );
    $bot_settings = get_option( 'wai_bot_settings', [] );
    $bot_enabled  = ! empty( $bot_settings['enabled'] );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Bot settings saved!', 'business-messaging-hub' ) . '</p></div>';
}

if ( isset( $_POST['wai_bot_save_rule'] ) && check_admin_referer( 'wai_bot_rule' ) ) {
    $rule_data = [
        'keyword'    => isset( $_POST['keyword'] )    ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) )    : '',
        'match_type' => isset( $_POST['match_type'] ) ? sanitize_text_field( wp_unslash( $_POST['match_type'] ) ) : 'contains',
        'reply'      => isset( $_POST['reply'] )      ? sanitize_textarea_field( wp_unslash( $_POST['reply'] ) )  : '',
        'enabled'    => 1,
    ];
    $edit_id = isset( $_POST['rule_id'] ) ? sanitize_text_field( wp_unslash( $_POST['rule_id'] ) ) : '';
    if ( $edit_id ) {
        WAI_Bot::update_rule( $edit_id, $rule_data );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rule updated!', 'business-messaging-hub' ) . '</p></div>';
    } else {
        WAI_Bot::add_rule( $rule_data );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rule added!', 'business-messaging-hub' ) . '</p></div>';
    }
    $rules = WAI_Bot::get_rules();
}

if ( isset( $_GET['delete_rule'] ) ) {
    $delete_rule_id = sanitize_text_field( wp_unslash( $_GET['delete_rule'] ) );
    if ( check_admin_referer( 'delete_rule_' . $delete_rule_id ) ) {
        WAI_Bot::delete_rule( $delete_rule_id );
        $rules = WAI_Bot::get_rules();
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rule deleted.', 'business-messaging-hub' ) . '</p></div>';
    }
}

if ( isset( $_GET['toggle_rule'] ) ) {
    $rid = sanitize_text_field( wp_unslash( $_GET['toggle_rule'] ) );
    if ( check_admin_referer( 'toggle_rule_' . $rid ) ) {
        $rules_all = WAI_Bot::get_rules();
        foreach ( $rules_all as $r ) {
            if ( $r['id'] === $rid ) {
                WAI_Bot::update_rule( $rid, [ 'enabled' => empty( $r['enabled'] ) ? 1 : 0 ] );
                break;
            }
        }
        $rules = WAI_Bot::get_rules();
    }
}

// Check if editing a rule
$editing_rule = null;
if ( isset( $_GET['edit_rule'] ) ) {
    $edit_rid = sanitize_text_field( wp_unslash( $_GET['edit_rule'] ) );
    foreach ( $rules as $r ) {
        if ( $r['id'] === $edit_rid ) { $editing_rule = $r; break; }
    }
}
?>
<div class="wrap wai-wrap">

    <div class="wai-page-header wai-page-header--bot">
        <span class="wai-logo">🤖</span>
        <div>
            <h1><?php esc_html_e( 'WhatsApp Bot', 'business-messaging-hub' ); ?></h1>
            <p><?php esc_html_e( 'Auto-reply to incoming messages using keyword rules.', 'business-messaging-hub' ); ?></p>
        </div>
        <div class="wai-bot-status-badge <?php echo $bot_enabled ? 'wai-bot-on' : 'wai-bot-off'; ?>">
            <?php echo $bot_enabled ? '🟢 ' . esc_html__( 'Bot ON', 'business-messaging-hub' ) : '🔴 ' . esc_html__( 'Bot OFF', 'business-messaging-hub' ); ?>
        </div>
    </div>

    <!-- Stats row -->
    <div class="wai-stats-row">
        <div class="wai-stat-card wai-stat-card--green">
            <div class="wai-stat-card__number"><?php echo (int) $stats['total']; ?></div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Total Conversations', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wai-stat-card wai-stat-card--blue">
            <div class="wai-stat-card__number"><?php echo (int) $stats['unique']; ?></div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Unique Users', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wai-stat-card">
            <div class="wai-stat-card__number"><?php echo count( $rules ); ?></div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Active Rules', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wai-stat-card wai-stat-card--green">
            <div class="wai-stat-card__number"><?php echo (int) $stats['replied']; ?></div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Auto Replies Sent', 'business-messaging-hub' ); ?></div>
        </div>
    </div>

    <div class="wai-two-col">

        <!-- LEFT: Bot Settings -->
        <div>
            <div class="wai-card">
                <h2>⚙️ <?php esc_html_e( 'Bot Settings', 'business-messaging-hub' ); ?></h2>
                <form method="post">
                    <?php wp_nonce_field( 'wai_bot_settings' ); ?>
                    <input type="hidden" name="wai_bot_save_settings" value="1" />

                    <table class="wai-form-table">
                        <tr>
                            <th><?php esc_html_e( 'Enable Bot', 'business-messaging-hub' ); ?></th>
                            <td>
                                <label class="wai-toggle">
                                    <input type="checkbox" name="wai_bot[enabled]" value="1" <?php checked( $bot_settings['enabled'] ?? 0, 1 ); ?> />
                                    <span class="wai-toggle-slider"></span>
                                </label>
                                <span class="wai-toggle-label"><?php esc_html_e( 'Auto-reply to incoming messages', 'business-messaging-hub' ); ?></span>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e( 'Fallback Reply', 'business-messaging-hub' ); ?></th>
                            <td>
                                <label class="wai-toggle">
                                    <input type="checkbox" name="wai_bot[fallback_enabled]" value="1" <?php checked( $bot_settings['fallback_enabled'] ?? 0, 1 ); ?> />
                                    <span class="wai-toggle-slider"></span>
                                </label>
                                <span class="wai-toggle-label"><?php esc_html_e( 'Send when no rule matches', 'business-messaging-hub' ); ?></span>
                                <br><br>
                                <textarea name="wai_bot[fallback_message]" rows="3" class="large-text"
                                    placeholder="<?php esc_attr_e( 'Sorry, I didn\'t understand that. Type HELP for assistance.', 'business-messaging-hub' ); ?>"
                                ><?php echo esc_textarea( $bot_settings['fallback_message'] ?? '' ); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e( 'Greeting Message', 'business-messaging-hub' ); ?></th>
                            <td>
                                <label class="wai-toggle">
                                    <input type="checkbox" name="wai_bot[greeting_enabled]" value="1" <?php checked( $bot_settings['greeting_enabled'] ?? 0, 1 ); ?> />
                                    <span class="wai-toggle-slider"></span>
                                </label>
                                <span class="wai-toggle-label"><?php esc_html_e( 'Send on first message from new user', 'business-messaging-hub' ); ?></span>
                                <br><br>
                                <textarea name="wai_bot[greeting_message]" rows="3" class="large-text"
                                    placeholder="<?php esc_attr_e( 'Hello! Welcome to {site_name}. How can I help you today?', 'business-messaging-hub' ); ?>"
                                ><?php echo esc_textarea( $bot_settings['greeting_message'] ?? '' ); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e( 'Working Hours', 'business-messaging-hub' ); ?></th>
                            <td>
                                <label class="wai-toggle">
                                    <input type="checkbox" name="wai_bot[working_hours_enabled]" value="1" <?php checked( $bot_settings['working_hours_enabled'] ?? 0, 1 ); ?> />
                                    <span class="wai-toggle-slider"></span>
                                </label>
                                <span class="wai-toggle-label"><?php esc_html_e( 'Only reply during working hours', 'business-messaging-hub' ); ?></span>
                                <br><br>
                                <div class="wai-hours-row">
                                    <label><?php esc_html_e( 'From', 'business-messaging-hub' ); ?></label>
                                    <input type="time" name="wai_bot[working_hours_start]" value="<?php echo esc_attr( $bot_settings['working_hours_start'] ?? '09:00' ); ?>" />
                                    <label><?php esc_html_e( 'To', 'business-messaging-hub' ); ?></label>
                                    <input type="time" name="wai_bot[working_hours_end]" value="<?php echo esc_attr( $bot_settings['working_hours_end'] ?? '17:00' ); ?>" />
                                </div>
                                <br>
                                <textarea name="wai_bot[outside_hours_message]" rows="2" class="large-text"
                                    placeholder="<?php esc_attr_e( 'We are currently offline. Our working hours are 9 AM to 5 PM. We\'ll reply soon!', 'business-messaging-hub' ); ?>"
                                ><?php echo esc_textarea( $bot_settings['outside_hours_message'] ?? '' ); ?></textarea>
                                <p class="description"><?php esc_html_e( 'Message sent outside working hours.', 'business-messaging-hub' ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <div class="wai-form-actions">
                        <?php submit_button( __( 'Save Bot Settings', 'business-messaging-hub' ), 'primary', 'submit', false ); ?>
                    </div>
                </form>
            </div>

            <!-- Variables reference -->
            <div class="wai-card">
                <h3>📝 <?php esc_html_e( 'Available Variables', 'business-messaging-hub' ); ?></h3>
                <table class="wai-vars-table">
                    <tr><td><code>{message}</code></td><td><?php esc_html_e( "Customer's message text", 'business-messaging-hub' ); ?></td></tr>
                    <tr><td><code>{site_name}</code></td><td><?php esc_html_e( 'Your WordPress site name', 'business-messaging-hub' ); ?></td></tr>
                    <tr><td><code>{site_url}</code></td><td><?php esc_html_e( 'Your site URL', 'business-messaging-hub' ); ?></td></tr>
                    <tr><td><code>{date}</code></td><td><?php esc_html_e( 'Current date', 'business-messaging-hub' ); ?></td></tr>
                    <tr><td><code>{time}</code></td><td><?php esc_html_e( 'Current time', 'business-messaging-hub' ); ?></td></tr>
                    <tr><td><code>{count}</code></td><td><?php esc_html_e( "User's message count in session", 'business-messaging-hub' ); ?></td></tr>
                </table>
            </div>
        </div>

        <!-- RIGHT: Rules -->
        <div>
            <!-- Add / Edit Rule -->
            <div class="wai-card">
                <h2><?php echo $editing_rule ? '✏️ ' . esc_html__( 'Edit Rule', 'business-messaging-hub' ) : '➕ ' . esc_html__( 'Add Rule', 'business-messaging-hub' ); ?></h2>
                <form method="post">
                    <?php wp_nonce_field( 'wai_bot_rule' ); ?>
                    <input type="hidden" name="wai_bot_save_rule" value="1" />
                    <input type="hidden" name="rule_id" value="<?php echo esc_attr( $editing_rule['id'] ?? '' ); ?>" />

                    <table class="wai-form-table">
                        <tr>
                            <th><label for="wai-keyword"><?php esc_html_e( 'Keyword / Trigger', 'business-messaging-hub' ); ?> <span class="wai-required">*</span></label></th>
                            <td>
                                <input type="text" id="wai-keyword" name="keyword" class="regular-text"
                                    value="<?php echo esc_attr( $editing_rule['keyword'] ?? '' ); ?>"
                                    placeholder="<?php esc_attr_e( 'e.g. hello, help, price', 'business-messaging-hub' ); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="wai-match-type"><?php esc_html_e( 'Match Type', 'business-messaging-hub' ); ?></label></th>
                            <td>
                                <select id="wai-match-type" name="match_type" class="regular-text">
                                    <?php
                                    $match_types = [
                                        'contains'    => __( 'Contains keyword', 'business-messaging-hub' ),
                                        'exact'       => __( 'Exact match', 'business-messaging-hub' ),
                                        'starts_with' => __( 'Starts with keyword', 'business-messaging-hub' ),
                                        'regex'       => __( 'Regular expression', 'business-messaging-hub' ),
                                    ];
                                    foreach ( $match_types as $val => $label ) {
                                        $sel = selected( $editing_rule['match_type'] ?? 'contains', $val, false );
                                        echo '<option value="' . esc_attr( $val ) . '"' . esc_attr( $sel ) . '>' . esc_html( $label ) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="wai-reply"><?php esc_html_e( 'Auto-Reply Message', 'business-messaging-hub' ); ?> <span class="wai-required">*</span></label></th>
                            <td>
                                <textarea id="wai-reply" name="reply" rows="4" class="large-text"
                                    placeholder="<?php esc_attr_e( 'Hello! Thanks for contacting {site_name}. We\'ll be with you shortly.', 'business-messaging-hub' ); ?>"
                                ><?php echo esc_textarea( $editing_rule['reply'] ?? '' ); ?></textarea>
                                <p class="description"><?php esc_html_e( 'Use variables like {site_name}, {date} etc. from the panel on the left.', 'business-messaging-hub' ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <div class="wai-form-actions">
                        <?php submit_button(
                            $editing_rule ? __( 'Update Rule', 'business-messaging-hub' ) : __( 'Add Rule', 'business-messaging-hub' ),
                            'primary', 'submit', false
                        ); ?>
                        <?php if ( $editing_rule ) : ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wai-bot' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'business-messaging-hub' ); ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Rules list -->
            <div class="wai-card">
                <h2>📋 <?php esc_html_e( 'Rules', 'business-messaging-hub' ); ?> <span class="wai-badge wai-badge--sent"><?php echo count( $rules ); ?></span></h2>

                <?php if ( empty( $rules ) ) : ?>
                    <p class="wai-empty"><?php esc_html_e( 'No rules yet. Add your first rule above.', 'business-messaging-hub' ); ?></p>
                <?php else : ?>
                    <div class="wai-rules-list">
                    <?php foreach ( $rules as $rule ) :
                        $is_on = ! empty( $rule['enabled'] );
                    ?>
                        <div class="wai-rule-card <?php echo $is_on ? '' : 'wai-rule-disabled'; ?>">
                            <div class="wai-rule-header">
                                <div class="wai-rule-keyword">
                                    <span class="wai-keyword-badge"><?php echo esc_html( $rule['match_type'] ?? 'contains' ); ?></span>
                                    <strong><?php echo esc_html( $rule['keyword'] ); ?></strong>
                                </div>
                                <div class="wai-rule-actions">
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wai-bot&toggle_rule=' . $rule['id'] ), 'toggle_rule_' . $rule['id'] ) ); ?>"
                                       class="button button-small <?php echo $is_on ? 'wai-btn-disable' : 'wai-btn-enable'; ?>">
                                        <?php echo $is_on ? esc_html__( 'Disable', 'business-messaging-hub' ) : esc_html__( 'Enable', 'business-messaging-hub' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wai-bot&edit_rule=' . $rule['id'] ) ); ?>" class="button button-small">✏️</a>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wai-bot&delete_rule=' . $rule['id'] ), 'delete_rule_' . $rule['id'] ) ); ?>"
                                       class="button button-small wai-btn-delete"
                                       onclick="return confirm('<?php esc_attr_e( 'Delete this rule?', 'business-messaging-hub' ); ?>')">🗑</a>
                                </div>
                            </div>
                            <div class="wai-rule-reply"><?php echo esc_html( mb_strimwidth( $rule['reply'], 0, 100, '…' ) ); ?></div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- .wai-two-col -->

</div>

<style>
.wai-page-header--bot { background: linear-gradient(135deg, #1a1a2e, #16213e); }
.wai-bot-status-badge {
    margin-left:auto; padding:8px 18px; border-radius:20px;
    font-weight:700; font-size:.95rem;
}
.wai-bot-on  { background:rgba(37,211,102,.2); color:#25D366; border:1px solid #25D366; }
.wai-bot-off { background:rgba(231,76,60,.15);  color:#e74c3c; border:1px solid #e74c3c; }

/* Toggle switch */
.wai-toggle { position:relative; display:inline-block; width:44px; height:24px; vertical-align:middle; }
.wai-toggle input { opacity:0; width:0; height:0; }
.wai-toggle-slider {
    position:absolute; cursor:pointer; inset:0;
    background:#ccc; border-radius:24px; transition:.3s;
}
.wai-toggle-slider:before {
    content:''; position:absolute; width:18px; height:18px;
    left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s;
}
.wai-toggle input:checked + .wai-toggle-slider { background:var(--wai-green); }
.wai-toggle input:checked + .wai-toggle-slider:before { transform:translateX(20px); }
.wai-toggle-label { margin-left:10px; vertical-align:middle; }

/* Hours row */
.wai-hours-row { display:flex; align-items:center; gap:10px; }
.wai-hours-row input[type="time"] {
    padding:5px 8px; border:1px solid var(--wai-border); border-radius:5px;
}

/* Variables table */
.wai-vars-table { width:100%; font-size:.85rem; }
.wai-vars-table td { padding:5px 8px; }
.wai-vars-table td:first-child { width:130px; }
.wai-vars-table code { background:#f0f0f0; padding:2px 6px; border-radius:4px; }

/* Rules list */
.wai-rules-list { display:flex; flex-direction:column; gap:10px; }
.wai-rule-card {
    border:1px solid var(--wai-border); border-radius:8px;
    padding:12px 14px; background:#fff;
    border-left:4px solid var(--wai-green);
}
.wai-rule-disabled { border-left-color:#ccc; opacity:.6; }
.wai-rule-header { display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; }
.wai-rule-keyword { display:flex; align-items:center; gap:8px; }
.wai-keyword-badge {
    background:#e8f5e9; color:var(--wai-green-dark);
    padding:2px 8px; border-radius:10px; font-size:.72rem;
    text-transform:uppercase; font-weight:600;
}
.wai-rule-actions { display:flex; gap:5px; }
.wai-rule-reply { margin-top:8px; font-size:.85rem; color:var(--wai-muted); font-style:italic; }

.wai-btn-delete { color:var(--wai-red) !important; border-color:var(--wai-red) !important; }
.wai-btn-enable { color:var(--wai-green-dark) !important; border-color:var(--wai-green) !important; }
.wai-btn-disable { color:var(--wai-muted) !important; }
</style>

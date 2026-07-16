<?php if ( ! defined( 'ABSPATH' ) ) exit;

$bot_settings = get_option( 'wabmh_bot_settings', [] );
$bot_enabled  = ! empty( $bot_settings['enabled'] );
$rules        = WABMH_Bot::get_rules();
$stats        = WABMH_Bot::get_conversation_stats();

// Handle form saves
if ( isset( $_POST['wabmh_bot_save_settings'] ) && check_admin_referer( 'wabmh_bot_settings' ) ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to do this.', 'business-messaging-hub' ) );
    }
    $wabmh_bot_input = isset( $_POST['wabmh_bot'] ) ? wp_unslash( $_POST['wabmh_bot'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized inside WABMH_Bot::save_settings().
    WABMH_Bot::save_settings( $wabmh_bot_input );
    $bot_settings = get_option( 'wabmh_bot_settings', [] );
    $bot_enabled  = ! empty( $bot_settings['enabled'] );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Bot settings saved!', 'business-messaging-hub' ) . '</p></div>';
}

if ( isset( $_POST['wabmh_bot_save_rule'] ) && check_admin_referer( 'wabmh_bot_rule' ) ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to do this.', 'business-messaging-hub' ) );
    }
    $rule_data = [
        'keyword'    => isset( $_POST['keyword'] )    ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) )    : '',
        'match_type' => isset( $_POST['match_type'] ) ? sanitize_text_field( wp_unslash( $_POST['match_type'] ) ) : 'contains',
        'reply'      => isset( $_POST['reply'] )      ? sanitize_textarea_field( wp_unslash( $_POST['reply'] ) )  : '',
        'enabled'    => 1,
    ];
    $edit_id = isset( $_POST['rule_id'] ) ? sanitize_text_field( wp_unslash( $_POST['rule_id'] ) ) : '';
    if ( $edit_id ) {
        WABMH_Bot::update_rule( $edit_id, $rule_data );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rule updated!', 'business-messaging-hub' ) . '</p></div>';
    } else {
        WABMH_Bot::add_rule( $rule_data );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rule added!', 'business-messaging-hub' ) . '</p></div>';
    }
    $rules = WABMH_Bot::get_rules();
}

if ( isset( $_GET['delete_rule'] ) ) {
    $delete_rule_id = sanitize_text_field( wp_unslash( $_GET['delete_rule'] ) );
    if ( check_admin_referer( 'wabmh_delete_rule_' . $delete_rule_id ) ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'business-messaging-hub' ) );
        }
        WABMH_Bot::delete_rule( $delete_rule_id );
        $rules = WABMH_Bot::get_rules();
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rule deleted.', 'business-messaging-hub' ) . '</p></div>';
    }
}

if ( isset( $_GET['toggle_rule'] ) ) {
    $rid = sanitize_text_field( wp_unslash( $_GET['toggle_rule'] ) );
    if ( check_admin_referer( 'wabmh_toggle_rule_' . $rid ) ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'business-messaging-hub' ) );
        }
        $rules_all = WABMH_Bot::get_rules();
        foreach ( $rules_all as $r ) {
            if ( $r['id'] === $rid ) {
                WABMH_Bot::update_rule( $rid, [ 'enabled' => empty( $r['enabled'] ) ? 1 : 0 ] );
                break;
            }
        }
        $rules = WABMH_Bot::get_rules();
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
<div class="wrap wabmh-wrap">

    <div class="wabmh-page-header wabmh-page-header--bot">
        <span class="wabmh-logo">🤖</span>
        <div>
            <h1><?php esc_html_e( 'WhatsApp Bot', 'business-messaging-hub' ); ?></h1>
            <p><?php esc_html_e( 'Auto-reply to incoming messages using keyword rules.', 'business-messaging-hub' ); ?></p>
        </div>
        <div class="wabmh-bot-status-badge <?php echo $bot_enabled ? 'wabmh-bot-on' : 'wabmh-bot-off'; ?>">
            <?php echo $bot_enabled ? '🟢 ' . esc_html__( 'Bot ON', 'business-messaging-hub' ) : '🔴 ' . esc_html__( 'Bot OFF', 'business-messaging-hub' ); ?>
        </div>
    </div>

    <!-- Stats row -->
    <div class="wabmh-stats-row">
        <div class="wabmh-stat-card wabmh-stat-card--green">
            <div class="wabmh-stat-card__number"><?php echo (int) $stats['total']; ?></div>
            <div class="wabmh-stat-card__label"><?php esc_html_e( 'Total Conversations', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wabmh-stat-card wabmh-stat-card--blue">
            <div class="wabmh-stat-card__number"><?php echo (int) $stats['unique']; ?></div>
            <div class="wabmh-stat-card__label"><?php esc_html_e( 'Unique Users', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wabmh-stat-card">
            <div class="wabmh-stat-card__number"><?php echo count( $rules ); ?></div>
            <div class="wabmh-stat-card__label"><?php esc_html_e( 'Active Rules', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wabmh-stat-card wabmh-stat-card--green">
            <div class="wabmh-stat-card__number"><?php echo (int) $stats['replied']; ?></div>
            <div class="wabmh-stat-card__label"><?php esc_html_e( 'Auto Replies Sent', 'business-messaging-hub' ); ?></div>
        </div>
    </div>

    <div class="wabmh-two-col">

        <!-- LEFT: Bot Settings -->
        <div>
            <div class="wabmh-card">
                <h2>⚙️ <?php esc_html_e( 'Bot Settings', 'business-messaging-hub' ); ?></h2>
                <form method="post">
                    <?php wp_nonce_field( 'wabmh_bot_settings' ); ?>
                    <input type="hidden" name="wabmh_bot_save_settings" value="1" />

                    <table class="wabmh-form-table">
                        <tr>
                            <th><?php esc_html_e( 'Enable Bot', 'business-messaging-hub' ); ?></th>
                            <td>
                                <label class="wabmh-toggle">
                                    <input type="checkbox" name="wabmh_bot[enabled]" value="1" <?php checked( $bot_settings['enabled'] ?? 0, 1 ); ?> />
                                    <span class="wabmh-toggle-slider"></span>
                                </label>
                                <span class="wabmh-toggle-label"><?php esc_html_e( 'Auto-reply to incoming messages', 'business-messaging-hub' ); ?></span>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e( 'Fallback Reply', 'business-messaging-hub' ); ?></th>
                            <td>
                                <label class="wabmh-toggle">
                                    <input type="checkbox" name="wabmh_bot[fallback_enabled]" value="1" <?php checked( $bot_settings['fallback_enabled'] ?? 0, 1 ); ?> />
                                    <span class="wabmh-toggle-slider"></span>
                                </label>
                                <span class="wabmh-toggle-label"><?php esc_html_e( 'Send when no rule matches', 'business-messaging-hub' ); ?></span>
                                <br><br>
                                <textarea name="wabmh_bot[fallback_message]" rows="3" class="large-text"
                                    placeholder="<?php esc_attr_e( 'Sorry, I didn\'t understand that. Type HELP for assistance.', 'business-messaging-hub' ); ?>"
                                ><?php echo esc_textarea( $bot_settings['fallback_message'] ?? '' ); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e( 'Greeting Message', 'business-messaging-hub' ); ?></th>
                            <td>
                                <label class="wabmh-toggle">
                                    <input type="checkbox" name="wabmh_bot[greeting_enabled]" value="1" <?php checked( $bot_settings['greeting_enabled'] ?? 0, 1 ); ?> />
                                    <span class="wabmh-toggle-slider"></span>
                                </label>
                                <span class="wabmh-toggle-label"><?php esc_html_e( 'Send on first message from new user', 'business-messaging-hub' ); ?></span>
                                <br><br>
                                <textarea name="wabmh_bot[greeting_message]" rows="3" class="large-text"
                                    placeholder="<?php esc_attr_e( 'Hello! Welcome to {site_name}. How can I help you today?', 'business-messaging-hub' ); ?>"
                                ><?php echo esc_textarea( $bot_settings['greeting_message'] ?? '' ); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e( 'Working Hours', 'business-messaging-hub' ); ?></th>
                            <td>
                                <label class="wabmh-toggle">
                                    <input type="checkbox" name="wabmh_bot[working_hours_enabled]" value="1" <?php checked( $bot_settings['working_hours_enabled'] ?? 0, 1 ); ?> />
                                    <span class="wabmh-toggle-slider"></span>
                                </label>
                                <span class="wabmh-toggle-label"><?php esc_html_e( 'Only reply during working hours', 'business-messaging-hub' ); ?></span>
                                <br><br>
                                <div class="wabmh-hours-row">
                                    <label><?php esc_html_e( 'From', 'business-messaging-hub' ); ?></label>
                                    <input type="time" name="wabmh_bot[working_hours_start]" value="<?php echo esc_attr( $bot_settings['working_hours_start'] ?? '09:00' ); ?>" />
                                    <label><?php esc_html_e( 'To', 'business-messaging-hub' ); ?></label>
                                    <input type="time" name="wabmh_bot[working_hours_end]" value="<?php echo esc_attr( $bot_settings['working_hours_end'] ?? '17:00' ); ?>" />
                                </div>
                                <br>
                                <textarea name="wabmh_bot[outside_hours_message]" rows="2" class="large-text"
                                    placeholder="<?php esc_attr_e( 'We are currently offline. Our working hours are 9 AM to 5 PM. We\'ll reply soon!', 'business-messaging-hub' ); ?>"
                                ><?php echo esc_textarea( $bot_settings['outside_hours_message'] ?? '' ); ?></textarea>
                                <p class="description"><?php esc_html_e( 'Message sent outside working hours.', 'business-messaging-hub' ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <div class="wabmh-form-actions">
                        <?php submit_button( __( 'Save Bot Settings', 'business-messaging-hub' ), 'primary', 'submit', false ); ?>
                    </div>
                </form>
            </div>

            <!-- Variables reference -->
            <div class="wabmh-card">
                <h3>📝 <?php esc_html_e( 'Available Variables', 'business-messaging-hub' ); ?></h3>
                <table class="wabmh-vars-table">
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
            <div class="wabmh-card">
                <h2><?php echo $editing_rule ? '✏️ ' . esc_html__( 'Edit Rule', 'business-messaging-hub' ) : '➕ ' . esc_html__( 'Add Rule', 'business-messaging-hub' ); ?></h2>
                <form method="post">
                    <?php wp_nonce_field( 'wabmh_bot_rule' ); ?>
                    <input type="hidden" name="wabmh_bot_save_rule" value="1" />
                    <input type="hidden" name="rule_id" value="<?php echo esc_attr( $editing_rule['id'] ?? '' ); ?>" />

                    <table class="wabmh-form-table">
                        <tr>
                            <th><label for="wabmh-keyword"><?php esc_html_e( 'Keyword / Trigger', 'business-messaging-hub' ); ?> <span class="wabmh-required">*</span></label></th>
                            <td>
                                <input type="text" id="wabmh-keyword" name="keyword" class="regular-text"
                                    value="<?php echo esc_attr( $editing_rule['keyword'] ?? '' ); ?>"
                                    placeholder="<?php esc_attr_e( 'e.g. hello, help, price', 'business-messaging-hub' ); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="wabmh-match-type"><?php esc_html_e( 'Match Type', 'business-messaging-hub' ); ?></label></th>
                            <td>
                                <select id="wabmh-match-type" name="match_type" class="regular-text">
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
                            <th><label for="wabmh-reply"><?php esc_html_e( 'Auto-Reply Message', 'business-messaging-hub' ); ?> <span class="wabmh-required">*</span></label></th>
                            <td>
                                <textarea id="wabmh-reply" name="reply" rows="4" class="large-text"
                                    placeholder="<?php esc_attr_e( 'Hello! Thanks for contacting {site_name}. We\'ll be with you shortly.', 'business-messaging-hub' ); ?>"
                                ><?php echo esc_textarea( $editing_rule['reply'] ?? '' ); ?></textarea>
                                <p class="description"><?php esc_html_e( 'Use variables like {site_name}, {date} etc. from the panel on the left.', 'business-messaging-hub' ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <div class="wabmh-form-actions">
                        <?php submit_button(
                            $editing_rule ? __( 'Update Rule', 'business-messaging-hub' ) : __( 'Add Rule', 'business-messaging-hub' ),
                            'primary', 'submit', false
                        ); ?>
                        <?php if ( $editing_rule ) : ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wabmh-bot' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'business-messaging-hub' ); ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Rules list -->
            <div class="wabmh-card">
                <h2>📋 <?php esc_html_e( 'Rules', 'business-messaging-hub' ); ?> <span class="wabmh-badge wabmh-badge--sent"><?php echo count( $rules ); ?></span></h2>

                <?php if ( empty( $rules ) ) : ?>
                    <p class="wabmh-empty"><?php esc_html_e( 'No rules yet. Add your first rule above.', 'business-messaging-hub' ); ?></p>
                <?php else : ?>
                    <div class="wabmh-rules-list">
                    <?php foreach ( $rules as $rule ) :
                        $is_on = ! empty( $rule['enabled'] );
                    ?>
                        <div class="wabmh-rule-card <?php echo $is_on ? '' : 'wabmh-rule-disabled'; ?>">
                            <div class="wabmh-rule-header">
                                <div class="wabmh-rule-keyword">
                                    <span class="wabmh-keyword-badge"><?php echo esc_html( $rule['match_type'] ?? 'contains' ); ?></span>
                                    <strong><?php echo esc_html( $rule['keyword'] ); ?></strong>
                                </div>
                                <div class="wabmh-rule-actions">
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wabmh-bot&toggle_rule=' . $rule['id'] ), 'wabmh_toggle_rule_' . $rule['id'] ) ); ?>"
                                       class="button button-small <?php echo $is_on ? 'wabmh-btn-disable' : 'wabmh-btn-enable'; ?>">
                                        <?php echo $is_on ? esc_html__( 'Disable', 'business-messaging-hub' ) : esc_html__( 'Enable', 'business-messaging-hub' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wabmh-bot&edit_rule=' . $rule['id'] ) ); ?>" class="button button-small">✏️</a>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wabmh-bot&delete_rule=' . $rule['id'] ), 'wabmh_delete_rule_' . $rule['id'] ) ); ?>"
                                       class="button button-small wabmh-btn-delete"
                                       onclick="return confirm('<?php esc_attr_e( 'Delete this rule?', 'business-messaging-hub' ); ?>')">🗑</a>
                                </div>
                            </div>
                            <div class="wabmh-rule-reply"><?php echo esc_html( mb_strimwidth( $rule['reply'], 0, 100, '…' ) ); ?></div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- .wabmh-two-col -->

</div>


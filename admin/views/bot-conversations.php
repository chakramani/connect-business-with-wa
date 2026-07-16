<?php if ( ! defined( 'ABSPATH' ) ) exit;
$conversations = WABMH_Bot::get_conversations( 100 );
?>
<div class="wrap wabmh-wrap">

    <div class="wabmh-page-header wabmh-page-header--bot">
        <span class="wabmh-logo">💬</span>
        <div>
            <h1><?php esc_html_e( 'Bot Conversations', 'business-messaging-hub' ); ?></h1>
            <p><?php esc_html_e( 'All incoming messages and bot auto-replies.', 'business-messaging-hub' ); ?></p>
        </div>
    </div>

    <div class="wabmh-card">
        <?php if ( empty( $conversations ) ) : ?>
            <div class="wabmh-notice wabmh-notice--info">
                <strong><?php esc_html_e( 'No conversations yet.', 'business-messaging-hub' ); ?></strong><br>
                <?php esc_html_e( 'Once your bot is enabled and webhook is configured, conversations will appear here.', 'business-messaging-hub' ); ?>
                <br>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wabmh-bot' ) ); ?>">→ <?php esc_html_e( 'Go to Bot Settings', 'business-messaging-hub' ); ?></a>
            </div>
        <?php else : ?>

            <div class="wabmh-log-topbar">
                <span class="wabmh-log-count">
                <?php
                    /* translators: %d: Number of conversations. */
                    $count_text = __( '%d conversations', 'business-messaging-hub' );
                    echo esc_html(
                        sprintf(
                            $count_text,
                            count( $conversations )
                        )
                    );
                ?>
                </span>
                <input type="text" id="wabmh-conv-search" placeholder="🔍 Search phone or message…" class="wabmh-log-search" />
            </div>

            <table class="wabmh-log-table widefat" id="wabmh-conv-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php esc_html_e( 'From', 'business-messaging-hub' ); ?></th>
                        <th><?php esc_html_e( 'Incoming Message', 'business-messaging-hub' ); ?></th>
                        <th><?php esc_html_e( 'Bot Reply', 'business-messaging-hub' ); ?></th>
                        <th><?php esc_html_e( 'Replied', 'business-messaging-hub' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'business-messaging-hub' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $conversations as $c ) : ?>
                    <tr class="wabmh-log-row">
                        <td><?php echo (int) $c->id; ?></td>
                        <td><strong><?php echo esc_html( $c->phone ); ?></strong></td>
                        <td class="wabmh-conv-msg wabmh-conv-incoming">
                            <span class="wabmh-conv-bubble wabmh-bubble-in">
                                <?php echo esc_html( $c->incoming ); ?>
                            </span>
                        </td>
                        <td class="wabmh-conv-msg wabmh-conv-reply">
                            <span class="wabmh-conv-bubble wabmh-bubble-out">
                                <?php echo esc_html( mb_strimwidth( $c->reply, 0, 120, '…' ) ); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ( $c->bot_replied ) : ?>
                                <span class="wabmh-badge wabmh-badge--sent">✅ <?php esc_html_e( 'Yes', 'business-messaging-hub' ); ?></span>
                            <?php else : ?>
                                <span class="wabmh-badge wabmh-badge--failed">❌ <?php esc_html_e( 'Failed', 'business-messaging-hub' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $c->created_at ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>

</div>



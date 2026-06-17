<?php if ( ! defined( 'ABSPATH' ) ) exit;
$conversations = WAI_Bot::get_conversations( 100 );
?>
<div class="wrap wai-wrap">

    <div class="wai-page-header wai-page-header--bot">
        <span class="wai-logo">💬</span>
        <div>
            <h1><?php esc_html_e( 'Bot Conversations', 'connect-business-with-wa' ); ?></h1>
            <p><?php esc_html_e( 'All incoming messages and bot auto-replies.', 'connect-business-with-wa' ); ?></p>
        </div>
    </div>

    <div class="wai-card">
        <?php if ( empty( $conversations ) ) : ?>
            <div class="wai-notice wai-notice--info">
                <strong><?php esc_html_e( 'No conversations yet.', 'connect-business-with-wa' ); ?></strong><br>
                <?php esc_html_e( 'Once your bot is enabled and webhook is configured, conversations will appear here.', 'connect-business-with-wa' ); ?>
                <br>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wai-bot' ) ); ?>">→ <?php esc_html_e( 'Go to Bot Settings', 'connect-business-with-wa' ); ?></a>
            </div>
        <?php else : ?>

            <div class="wai-log-topbar">
                <span class="wai-log-count">
                <?php
                    /* translators: %d: Number of conversations. */
                    $count_text = __( '%d conversations', 'connect-business-with-wa' );
                    echo esc_html(
                        sprintf(
                            $count_text,
                            count( $conversations )
                        )
                    );
                ?>
                </span>
                <input type="text" id="wai-conv-search" placeholder="🔍 Search phone or message…" class="wai-log-search" />
            </div>

            <table class="wai-log-table widefat" id="wai-conv-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php esc_html_e( 'From', 'connect-business-with-wa' ); ?></th>
                        <th><?php esc_html_e( 'Incoming Message', 'connect-business-with-wa' ); ?></th>
                        <th><?php esc_html_e( 'Bot Reply', 'connect-business-with-wa' ); ?></th>
                        <th><?php esc_html_e( 'Replied', 'connect-business-with-wa' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'connect-business-with-wa' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $conversations as $c ) : ?>
                    <tr class="wai-log-row">
                        <td><?php echo (int) $c->id; ?></td>
                        <td><strong><?php echo esc_html( $c->phone ); ?></strong></td>
                        <td class="wai-conv-msg wai-conv-incoming">
                            <span class="wai-conv-bubble wai-bubble-in">
                                <?php echo esc_html( $c->incoming ); ?>
                            </span>
                        </td>
                        <td class="wai-conv-msg wai-conv-reply">
                            <span class="wai-conv-bubble wai-bubble-out">
                                <?php echo esc_html( mb_strimwidth( $c->reply, 0, 120, '…' ) ); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ( $c->bot_replied ) : ?>
                                <span class="wai-badge wai-badge--sent">✅ <?php esc_html_e( 'Yes', 'connect-business-with-wa' ); ?></span>
                            <?php else : ?>
                                <span class="wai-badge wai-badge--failed">❌ <?php esc_html_e( 'Failed', 'connect-business-with-wa' ); ?></span>
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

<style>
.wai-page-header--bot { background: linear-gradient(135deg, #1a1a2e, #16213e); }
.wai-conv-msg  { max-width:240px; }
.wai-conv-bubble {
    display:inline-block; padding:7px 12px;
    border-radius:14px; font-size:.85rem; line-height:1.4;
    word-break:break-word;
}
.wai-bubble-in  { background:#f0f0f0; color:#333; border-bottom-left-radius:3px; }
.wai-bubble-out { background:#d4f5e2; color:#1a5c35; border-bottom-right-radius:3px; }
.wai-log-row.wai-hidden { display:none; }
</style>

<script>
jQuery(function($){
    $('#wai-conv-search').on('input', function(){
        var q = $(this).val().toLowerCase();
        $('#wai-conv-table tbody tr').each(function(){
            $(this).toggleClass('wai-hidden', q.length > 0 && $(this).text().toLowerCase().indexOf(q) === -1);
        });
    });
});
</script>

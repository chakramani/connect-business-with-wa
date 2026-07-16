<?php if ( ! defined( 'ABSPATH' ) ) exit;
$contacts = WABMH_Inbox::get_contacts();
?>
<div class="wrap wabmh-wrap">

<div class="wabmh-page-header" style="background:linear-gradient(135deg,#075e54,#128c7e);">
    <span class="wabmh-logo">💬</span>
    <div>
        <h1><?php esc_html_e( 'WhatsApp Inbox', 'business-messaging-hub' ); ?></h1>
        <p><?php esc_html_e( 'Two-way conversations with your contacts.', 'business-messaging-hub' ); ?></p>
    </div>
</div>

<div class="wabmh-inbox-shell">

    <!-- ══ SIDEBAR ══ -->
    <div class="wabmh-inbox-sidebar">
        <div class="wabmh-inbox-search-wrap">
            <input type="text" id="wabmh-inbox-search" placeholder="🔍 Search contacts…" />
        </div>
        <ul class="wabmh-contact-list" id="wabmh-contact-list">
        <?php if ( empty( $contacts ) ) : ?>
            <li class="wabmh-no-contacts">
                <p>📭 <?php esc_html_e( 'No conversations yet.', 'business-messaging-hub' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wabmh-send' ) ); ?>" class="button button-primary">
                    ✉️ <?php esc_html_e( 'Send First Message', 'business-messaging-hub' ); ?>
                </a>
            </li>
        <?php else :
            foreach ( $contacts as $contact ) :
                $norm     = WABMH_Inbox::normalize_phone( $contact->phone );
                $initials = strtoupper( substr( $norm, -2 ) );
                $preview  = mb_strimwidth( $contact->last_message, 0, 38, '…' );
                $time     = human_time_diff( strtotime( $contact->last_message_time ), current_time( 'timestamp' ) ) . ' ago';
                $dir_icon = ( 'received' === $contact->last_status ) ? '↙' : '↗';
        ?>
            <li class="wabmh-contact-item"
                data-phone="<?php echo esc_attr( $norm ); ?>"
                data-search="<?php echo esc_attr( $norm ); ?>">
                <div class="wabmh-contact-avatar"><?php echo esc_html( $initials ); ?></div>
                <div class="wabmh-contact-info">
                    <div class="wabmh-contact-top">
                        <span class="wabmh-contact-phone"><?php echo esc_html( $norm ); ?></span>
                        <span class="wabmh-contact-time"><?php echo esc_html( $time ); ?></span>
                    </div>
                    <div class="wabmh-contact-preview">
                        <span class="wabmh-dir"><?php echo esc_html( $dir_icon ); ?></span>
                        <?php echo esc_html( $preview ); ?>
                        <?php if ( $contact->unread > 0 ) : ?>
                            <span class="wabmh-badge"><?php echo (int) $contact->unread; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
        <?php endforeach; endif; ?>
        </ul>
    </div>

    <!-- ══ MAIN ══ -->
    <div class="wabmh-inbox-main" id="wabmh-inbox-main">
        <div class="wabmh-no-contact">
            <div style="font-size:4rem;opacity:.4;">💬</div>
            <h2><?php esc_html_e( 'WhatsApp Inbox', 'business-messaging-hub' ); ?></h2>
            <p><?php esc_html_e( 'Select a conversation from the left to start chatting.', 'business-messaging-hub' ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wabmh-send' ) ); ?>" class="button button-primary">
                ✉️ <?php esc_html_e( 'Start a New Conversation', 'business-messaging-hub' ); ?>
            </a>
        </div>
    </div>

</div><!-- .wabmh-inbox-shell -->
</div><!-- .wrap -->

<!-- Chat template (hidden, cloned by JS) -->
<script type="text/template" id="wabmh-chat-tpl">
<div class="wabmh-chat-header">
    <div class="wabmh-chat-avatar" id="wabmh-chat-avatar"></div>
    <div class="wabmh-chat-header-info">
        <strong id="wabmh-chat-phone"></strong>
        <span>WhatsApp Contact</span>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;">
        <button class="button button-small" id="wabmh-refresh-btn">🔄</button>
    </div>
</div>
<div class="wabmh-chat-messages" id="wabmh-chat-messages"></div>
<div class="wabmh-reply-box">
    <textarea class="wabmh-reply-textarea" id="wabmh-reply-text" placeholder="Type a message… (Enter to send, Shift+Enter for new line)" rows="1" maxlength="4096"></textarea>
    <button class="wabmh-reply-send-btn" id="wabmh-reply-send">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
            <path d="M1.101 21.757L23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"/>
        </svg>
    </button>
</div>
</script>


<?php if ( ! defined( 'ABSPATH' ) ) exit;
$contacts = WAI_Inbox::get_contacts();
?>
<div class="wrap wai-wrap">

<div class="wai-page-header" style="background:linear-gradient(135deg,#075e54,#128c7e);">
    <span class="wai-logo">💬</span>
    <div>
        <h1><?php esc_html_e( 'WhatsApp Inbox', 'business-messaging-hub' ); ?></h1>
        <p><?php esc_html_e( 'Two-way conversations with your contacts.', 'business-messaging-hub' ); ?></p>
    </div>
</div>

<div class="wai-inbox-shell">

    <!-- ══ SIDEBAR ══ -->
    <div class="wai-inbox-sidebar">
        <div class="wai-inbox-search-wrap">
            <input type="text" id="wai-inbox-search" placeholder="🔍 Search contacts…" />
        </div>
        <ul class="wai-contact-list" id="wai-contact-list">
        <?php if ( empty( $contacts ) ) : ?>
            <li class="wai-no-contacts">
                <p>📭 <?php esc_html_e( 'No conversations yet.', 'business-messaging-hub' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wai-send' ) ); ?>" class="button button-primary">
                    ✉️ <?php esc_html_e( 'Send First Message', 'business-messaging-hub' ); ?>
                </a>
            </li>
        <?php else :
            foreach ( $contacts as $contact ) :
                $norm     = WAI_Inbox::normalize_phone( $contact->phone );
                $initials = strtoupper( substr( $norm, -2 ) );
                $preview  = mb_strimwidth( $contact->last_message, 0, 38, '…' );
                $time     = human_time_diff( strtotime( $contact->last_message_time ), current_time( 'timestamp' ) ) . ' ago';
                $dir_icon = ( 'received' === $contact->last_status ) ? '↙' : '↗';
        ?>
            <li class="wai-contact-item"
                data-phone="<?php echo esc_attr( $norm ); ?>"
                data-search="<?php echo esc_attr( $norm ); ?>">
                <div class="wai-contact-avatar"><?php echo esc_html( $initials ); ?></div>
                <div class="wai-contact-info">
                    <div class="wai-contact-top">
                        <span class="wai-contact-phone"><?php echo esc_html( $norm ); ?></span>
                        <span class="wai-contact-time"><?php echo esc_html( $time ); ?></span>
                    </div>
                    <div class="wai-contact-preview">
                        <span class="wai-dir"><?php echo esc_html( $dir_icon ); ?></span>
                        <?php echo esc_html( $preview ); ?>
                        <?php if ( $contact->unread > 0 ) : ?>
                            <span class="wai-badge"><?php echo (int) $contact->unread; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
        <?php endforeach; endif; ?>
        </ul>
    </div>

    <!-- ══ MAIN ══ -->
    <div class="wai-inbox-main" id="wai-inbox-main">
        <div class="wai-no-contact">
            <div style="font-size:4rem;opacity:.4;">💬</div>
            <h2><?php esc_html_e( 'WhatsApp Inbox', 'business-messaging-hub' ); ?></h2>
            <p><?php esc_html_e( 'Select a conversation from the left to start chatting.', 'business-messaging-hub' ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wai-send' ) ); ?>" class="button button-primary">
                ✉️ <?php esc_html_e( 'Start a New Conversation', 'business-messaging-hub' ); ?>
            </a>
        </div>
    </div>

</div><!-- .wai-inbox-shell -->
</div><!-- .wrap -->

<!-- Chat template (hidden, cloned by JS) -->
<script type="text/template" id="wai-chat-tpl">
<div class="wai-chat-header">
    <div class="wai-chat-avatar" id="wai-chat-avatar"></div>
    <div class="wai-chat-header-info">
        <strong id="wai-chat-phone"></strong>
        <span>WhatsApp Contact</span>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;">
        <button class="button button-small" id="wai-refresh-btn">🔄</button>
    </div>
</div>
<div class="wai-chat-messages" id="wai-chat-messages"></div>
<div class="wai-reply-box">
    <textarea class="wai-reply-textarea" id="wai-reply-text" placeholder="Type a message… (Enter to send, Shift+Enter for new line)" rows="1" maxlength="4096"></textarea>
    <button class="wai-reply-send-btn" id="wai-reply-send">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
            <path d="M1.101 21.757L23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"/>
        </svg>
    </button>
</div>
</script>

<style>
.wai-inbox-shell{display:flex;height:calc(100vh - 160px);border:1px solid #ddd;border-radius:12px;overflow:hidden;background:#fff;margin-top:16px;box-shadow:0 2px 12px rgba(0,0,0,.08);}
.wai-inbox-sidebar{width:300px;min-width:260px;border-right:1px solid #e8e8e8;display:flex;flex-direction:column;background:#fff;}
.wai-inbox-search-wrap{padding:10px 12px;border-bottom:1px solid #f0f0f0;background:#f7f7f7;}
#wai-inbox-search{width:100%;padding:7px 12px;border:1px solid #ddd;border-radius:20px;font-size:.85rem;box-sizing:border-box;outline:none;}
#wai-inbox-search:focus{border-color:#128c7e;}
.wai-contact-list{margin:0;padding:0;list-style:none;overflow-y:auto;flex:1;}
.wai-no-contacts{padding:24px 16px;text-align:center;color:#888;font-size:.85rem;}
.wai-contact-item{display:flex;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid #f5f5f5;cursor:pointer;transition:background .15s;}
.wai-contact-item:hover{background:#f5f5f5;}
.wai-contact-item.active{background:#e8f5e9!important;border-left:3px solid #075e54;}
.wai-contact-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#075e54,#128c7e);color:#fff;font-weight:700;font-size:.85rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.wai-contact-info{flex:1;min-width:0;}
.wai-contact-top{display:flex;justify-content:space-between;align-items:baseline;}
.wai-contact-phone{font-weight:600;font-size:.85rem;color:#111;}
.wai-contact-time{font-size:.72rem;color:#999;margin-left:6px;}
.wai-contact-preview{font-size:.78rem;color:#888;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;display:flex;align-items:center;gap:4px;}
.wai-dir{font-size:.7rem;color:#128c7e;}
.wai-badge{background:#25d366;color:#fff;border-radius:50%;width:18px;height:18px;font-size:.7rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center;margin-left:auto;flex-shrink:0;}
.wai-inbox-main{flex:1;display:flex;flex-direction:column;background:#e5ddd5;overflow:hidden;}
.wai-no-contact{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#888;text-align:center;padding:40px;}
.wai-no-contact h2{color:#555;margin-bottom:8px;}
.wai-chat-header{background:#075e54;color:#fff;padding:12px 16px;display:flex;align-items:center;gap:12px;flex-shrink:0;box-shadow:0 1px 4px rgba(0,0,0,.2);}
.wai-chat-avatar{width:38px;height:38px;border-radius:50%;background:#128c7e;color:#fff;font-weight:700;font-size:.85rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.wai-chat-header-info{flex:1;}
.wai-chat-header-info strong{display:block;font-size:.95rem;}
.wai-chat-header-info span{font-size:.75rem;color:#a8d5b5;}
.wai-chat-header .button{background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.3);font-size:.8rem;}
.wai-chat-messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:4px;}
.wai-msg-wrap{display:flex;margin-bottom:2px;}
.wai-msg-incoming{justify-content:flex-start;}
.wai-msg-outgoing{justify-content:flex-end;}
.wai-msg-bubble{max-width:65%;padding:7px 10px 4px;border-radius:8px;box-shadow:0 1px 1px rgba(0,0,0,.1);}
.wai-msg-incoming .wai-msg-bubble{background:#fff;border-top-left-radius:2px;}
.wai-msg-outgoing .wai-msg-bubble{background:#d9fdd3;border-top-right-radius:2px;}
.wai-msg-text{font-size:.88rem;color:#111;line-height:1.5;word-break:break-word;}
.wai-msg-meta{display:flex;justify-content:flex-end;align-items:center;gap:4px;margin-top:2px;}
.wai-msg-time{font-size:.68rem;color:#999;}
.wai-msg-tick{font-size:.75rem;}
.wai-msg-error{font-size:.75rem;color:#c0392b;margin-top:4px;}
.wai-date-divider{text-align:center;margin:10px 0;}
.wai-date-divider span{background:rgba(255,255,255,.8);padding:3px 12px;border-radius:10px;font-size:.75rem;color:#666;}
.wai-loading{text-align:center;padding:40px;color:#888;font-size:.9rem;}
.wai-reply-box{background:#f0f0f0;padding:10px 14px;display:flex;align-items:flex-end;gap:10px;border-top:1px solid #ddd;flex-shrink:0;}
.wai-reply-textarea{flex:1;padding:10px 14px;border:none;border-radius:22px;background:#fff;font-size:.9rem;resize:none;outline:none;max-height:120px;overflow-y:auto;line-height:1.5;font-family:inherit;box-shadow:0 1px 3px rgba(0,0,0,.1);}
.wai-reply-send-btn{width:44px;height:44px;border-radius:50%;background:#075e54;color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .2s;padding:0;}
.wai-reply-send-btn:hover{background:#128c7e;}
.wai-reply-send-btn:disabled{background:#aaa;cursor:not-allowed;}
</style>

<script>
jQuery(function($){

    var activePhone   = null;
    var lastMsgId     = 0;
    var pollTimer     = null;
    var sidebarTimer  = null;
    var ajaxUrl       = '<?php echo esc_js( admin_url( "admin-ajax.php" ) ); ?>';
    var nonce         = '<?php echo esc_js( wp_create_nonce( "wai_nonce" ) ); ?>';
    var ticks         = { pending:'🕐', sent:'✓', delivered:'✓✓', read:'<span style="color:#53bdeb">✓✓</span>', failed:'<span style="color:red">✗</span>', received:'' };

    // ── Helper: escape HTML ─────────────────────────────────────────
    function esc(str){ return $('<div>').text(str).html(); }

    // ── Build a message bubble ──────────────────────────────────────
    function buildBubble(msg){
        var dir   = (msg.status === 'received') ? 'incoming' : 'outgoing';
        var time  = msg.created_at ? msg.created_at.substr(11,5) : '';
        var tick  = (dir === 'outgoing') ? (ticks[msg.status] || '✓') : '';
        var err   = msg.error ? '<div class="wai-msg-error">❌ '+esc(msg.error)+'</div>' : '';
        return '<div class="wai-msg-wrap wai-msg-'+dir+'">'
             + '<div class="wai-msg-bubble">'
             + '<div class="wai-msg-text">'+esc(msg.message).replace(/\n/g,'<br>')+'</div>'
             + '<div class="wai-msg-meta"><span class="wai-msg-time">'+time+'</span>'
             + (tick ? '<span class="wai-msg-tick">'+tick+'</span>' : '')
             + '</div>'+err
             + '</div></div>';
    }

    // ── Load conversation via AJAX ──────────────────────────────────
    function loadConversation(phone){
        var $main = $('#wai-inbox-main');

        // Clone template and render chat shell
        var tpl = $('#wai-chat-tpl').html();
        $main.html(tpl);

        $('#wai-chat-phone').text(phone);
        $('#wai-chat-avatar').text(String(phone).slice(-2).toUpperCase());
        $('#wai-chat-messages').html('<div class="wai-loading">⏳ Loading messages…</div>');

        // Fetch messages
        $.post(ajaxUrl, {
            action:  'wai_inbox_load',
            nonce:   nonce,
            phone:   phone
        }, function(res){
            console.log('WAI inbox_load response:', JSON.stringify(res));
            var $msgs = $('#wai-chat-messages');
            if(!res.success){ $msgs.html('<div class="wai-loading">❌ '+esc(res.data ? res.data.message : 'Unknown error')+'</div>'); return; }

            var rows = res.data.messages;
            if(!rows.length){ $msgs.html('<div class="wai-loading">💬 No messages yet.</div>'); lastMsgId=0; return; }

            var html     = '';
            var prevDate = '';
            $.each(rows, function(i, msg){
                var d = msg.created_at ? msg.created_at.substr(0,10) : '';
                if(d && d !== prevDate){
                    var today = new Date().toISOString().substr(0,10);
                    var yest  = new Date(Date.now()-86400000).toISOString().substr(0,10);
                    var label = d===today ? 'Today' : (d===yest ? 'Yesterday' : d);
                    html += '<div class="wai-date-divider"><span>'+label+'</span></div>';
                    prevDate = d;
                }
                html += buildBubble(msg);
            });
            $msgs.html(html);
            $msgs.scrollTop($msgs[0].scrollHeight);
            // Track last message ID for reliable polling
            lastMsgId = rows[rows.length-1].id;
        });

        // Poll for new messages every 3 seconds
        clearInterval(pollTimer);
        pollTimer = setInterval(function(){ pollNew(phone); }, 3000);

        // Refresh sidebar contact list every 5 seconds for new contacts/unread counts
        clearInterval(sidebarTimer);
        sidebarTimer = setInterval(function(){ refreshSidebar(); }, 5000);

        // Bind reply
        bindReply(phone);
        bindRefresh(phone);
    }

    // ── Poll for new messages using last ID ────────────────────────
    function pollNew(phone){
        var $msgs = $('#wai-chat-messages');
        if(!$msgs.length || activePhone !== phone) return;
        $.post(ajaxUrl, { action:'wai_inbox_poll_new', nonce:nonce, phone:phone, last_id:lastMsgId }, function(res){
            if(!res.success || !res.data.messages.length) return;
            var html = '';
            $.each(res.data.messages, function(i,msg){
                html += buildBubble(msg);
                lastMsgId = Math.max(lastMsgId, msg.id);
            });
            // Remove "no messages" placeholder if present
            $msgs.find('.wai-loading').remove();
            $msgs.append(html);
            $msgs.scrollTop($msgs[0].scrollHeight);
            // Flash title to notify admin
            flashTitle('💬 New message!');
        });
    }

    // ── Refresh sidebar contact list ─────────────────────────────
    function refreshSidebar(){
        $.post(ajaxUrl, { action:'wai_inbox_sidebar', nonce:nonce }, function(res){
            if(!res.success || !res.data.html) return;
            var $list = $('#wai-contact-list');
            var scrollTop = $list.scrollTop();
            $list.html(res.data.html);
            $list.scrollTop(scrollTop);
            // Restore active state
            if(activePhone){
                $list.find('[data-phone="'+activePhone+'"]').addClass('active');
            }
        });
    }

    // ── Flash browser tab title on new message ───────────────────
    var flashInterval = null;
    var origTitle = document.title;
    function flashTitle(msg){
        var count = 0;
        clearInterval(flashInterval);
        flashInterval = setInterval(function(){
            document.title = (count % 2 === 0) ? msg : origTitle;
            count++;
            if(count > 6){ clearInterval(flashInterval); document.title = origTitle; }
        }, 600);
    }

    // ── Bind reply box ──────────────────────────────────────────────
    function bindReply(phone){
        $(document).off('click','#wai-reply-send').on('click','#wai-reply-send', function(){ doSend(phone); });
        $(document).off('keydown','#wai-reply-text').on('keydown','#wai-reply-text', function(e){
            if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); doSend(phone); }
            this.style.height='auto';
            this.style.height=Math.min(this.scrollHeight,120)+'px';
        });
    }

    function bindRefresh(phone){
        $(document).off('click','#wai-refresh-btn').on('click','#wai-refresh-btn', function(){ loadConversation(phone); });
    }

    // ── Send a message ──────────────────────────────────────────────
    function doSend(phone){
        var $txt  = $('#wai-reply-text');
        var $btn  = $('#wai-reply-send');
        var msg   = $txt.val().trim();
        if(!msg) return;

        $btn.prop('disabled',true);
        $txt.prop('disabled',true);

        console.log('WAI send to:', phone, 'msg:', msg);
        $.post(ajaxUrl, { action:'wai_send_message', nonce:nonce, to:phone, message:msg }, function(res){
            if(res.success){
                var now  = new Date();
                var time = now.getHours().toString().padStart(2,'0')+':'+now.getMinutes().toString().padStart(2,'0');
                var pseudo = { id: lastMsgId+1, status:'sent', message:msg, created_at:'    '+time, error:'' };
                var $msgs = $('#wai-chat-messages');
                $msgs.append(buildBubble(pseudo));
                $msgs.scrollTop($msgs[0].scrollHeight);
                $txt.val('').css('height','auto');
                lastMsgId++; // prevent duplicate on next poll
            } else {
                alert('❌ '+(res.data.message||'Failed to send'));
            }
        }).always(function(){ $btn.prop('disabled',false); $txt.prop('disabled',false).focus(); });
    }

    // ── Click contact ───────────────────────────────────────────────
    $(document).on('click', '.wai-contact-item', function(){
        var phone = String($(this).data('phone'));
        if(!phone) return;
        activePhone = phone;
        $('.wai-contact-item').removeClass('active');
        $(this).addClass('active');
        loadConversation(phone);
    });

    // ── Search ──────────────────────────────────────────────────────
    $('#wai-inbox-search').on('input', function(){
        var q = $(this).val().toLowerCase().replace(/\D/g,'');
        $('.wai-contact-item').each(function(){
            var p = $(this).data('phone').toString().replace(/\D/g,'');
            $(this).toggle(!q || p.indexOf(q) !== -1);
        });
    });

});
</script>

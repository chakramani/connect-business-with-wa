/* WABMH Frontend Chat Widget */
(function($){
    'use strict';

    var lastMsgId = 0;
    var pollTimer = null;

    function esc(s){ return $('<div>').text(s).html(); }

    function bubble(msg){
        return '<div class="wabmh-widget-msg '+esc(msg.dir)+'">'
             + '<div class="wabmh-widget-bubble">'
             + esc(msg.text).replace(/\n/g,'<br>')
             + '<div class="wabmh-widget-bubble-meta">'+esc(msg.time)+'</div>'
             + '</div></div>';
    }

    function scrollBottom(){
        var $m = $('#wabmh-widget-messages');
        if($m.length) $m.scrollTop($m[0].scrollHeight);
    }

    // ── Load full history ─────────────────────────────────────────
    function loadHistory(){
        $('#wabmh-widget-messages').html(
            '<div style="text-align:center;padding:20px;color:#888;font-size:.82rem;">⏳ Loading…</div>'
        );
        $.post(wabmhWidget.ajax_url, {
            action: 'wabmh_widget_history',
            nonce:  wabmhWidget.nonce
        }, function(res){
            var $msgs = $('#wabmh-widget-messages');
            $msgs.empty();
            if(!res.success || !res.data.messages || !res.data.messages.length){
                $msgs.html('<div style="text-align:center;padding:30px;color:#888;font-size:.82rem;">💬 No messages yet.<br>Your conversation will appear here.</div>');
                return;
            }
            var html = '';
            $.each(res.data.messages, function(i, msg){
                html += bubble(msg);
                if(msg.id > lastMsgId) lastMsgId = msg.id;
            });
            $msgs.html(html);
            scrollBottom();
        });
    }

    // ── Poll every 4 seconds ─────────────────────────────────────
    function startPolling(){
        if(pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(function(){
            $.post(wabmhWidget.ajax_url, {
                action:  'wabmh_widget_poll',
                nonce:   wabmhWidget.nonce,
                last_id: lastMsgId
            }, function(res){
                if(!res.success || !res.data.messages || !res.data.messages.length) return;
                var $msgs = $('#wabmh-widget-messages');
                $.each(res.data.messages, function(i, msg){
                    $msgs.append(bubble(msg));
                    if(msg.id > lastMsgId) lastMsgId = msg.id;
                });
                scrollBottom();
                // Badge if closed
                if(!$('.wabmh-chat-widget').hasClass('wabmh-widget-open')){
                    var $b = $('.wabmh-launcher-badge');
                    var c  = parseInt($b.text()||'0') + res.data.messages.length;
                    $b.text(c).addClass('visible');
                }
            });
        }, 4000);
    }

    // ── Show chat screen ─────────────────────────────────────────
    function showChatScreen(){
        $('.wabmh-welcome-screen').removeClass('active');
        $('.wabmh-chat-screen').addClass('active');
        loadHistory();
        startPolling();
    }

    // ── Show welcome screen ──────────────────────────────────────
    function showWelcomeScreen(){
        $('.wabmh-chat-screen').removeClass('active');
        $('.wabmh-welcome-screen').addClass('active');
    }

    // ── Send message ─────────────────────────────────────────────
    function sendMessage(){
        var $txt = $('#wabmh-widget-textarea');
        var $btn = $('#wabmh-widget-send');
        var msg  = $txt.val().trim();
        if(!msg) return;

        $txt.val('').css('height','auto');
        $btn.prop('disabled', true);

        $.post(wabmhWidget.ajax_url, {
            action:  'wabmh_widget_send',
            nonce:   wabmhWidget.nonce,
            message: msg
        }, function(res){
            if(res.success){
                // Only append after server confirms — prevents duplicate with poll
                var now  = new Date();
                var time = now.getHours().toString().padStart(2,'0')+':'+now.getMinutes().toString().padStart(2,'0');
                var $msgs = $('#wabmh-widget-messages');
                $msgs.append(bubble({ dir:'outgoing', text:msg, time:time, id: res.data.id || 0 }));
                // Update lastMsgId so poll doesn't fetch this message again
                if(res.data && res.data.id && res.data.id > lastMsgId){
                    lastMsgId = res.data.id;
                }
                scrollBottom();
            } else {
                // Show inline error
                var $msgs = $('#wabmh-widget-messages');
                $msgs.append(
                    '<div class="wabmh-widget-msg outgoing">'
                    +'<div class="wabmh-widget-bubble" style="background:#ffe0e0;">'
                    + esc(msg)
                    +'<div class="wabmh-widget-bubble-meta" style="color:#c0392b;">❌ '+(res.data ? esc(res.data.message) : 'Failed')+'</div>'
                    +'</div></div>'
                );
                scrollBottom();
            }
        }).always(function(){ $btn.prop('disabled', false); });
    }

    $(document).ready(function(){

        // ── Toggle widget ─────────────────────────────────────────
        $(document).on('click', '.wabmh-chat-launcher', function(){
            var $w = $('.wabmh-chat-widget');
            if($w.hasClass('wabmh-widget-open')){
                $w.removeClass('wabmh-widget-open');
                if(pollTimer){ clearInterval(pollTimer); pollTimer = null; }
            } else {
                $w.addClass('wabmh-widget-open');
                $('.wabmh-launcher-badge').removeClass('visible').text('');
                // Returning user — go straight to chat
                if(wabmhWidget.has_started){
                    showChatScreen();
                }
                // First-time user — stay on welcome screen (already shown by PHP)
            }
        });

        // ── Close ─────────────────────────────────────────────────
        $(document).on('click', '.wabmh-widget-close', function(){
            $('.wabmh-chat-widget').removeClass('wabmh-widget-open');
            if(pollTimer){ clearInterval(pollTimer); pollTimer = null; }
        });

        // ── First time: phone input form submit ───────────────────
        $(document).on('click', '#wabmh-open-whatsapp', function(){
            var phone = $('#wabmh-user-phone').val().replace(/\D/g,'');
            var $err  = $('#wabmh-phone-error');
            $err.hide();

            if(!phone || phone.length < 7){
                $err.text('Please enter a valid WhatsApp number.').show();
                return;
            }

            if(!wabmhWidget.business_phone){
                $err.text('Business phone not configured.').show();
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('Opening WhatsApp…');

            // Save phone to user meta + mark as started
            $.post(wabmhWidget.ajax_url, {
                action: 'wabmh_widget_start',
                nonce:  wabmhWidget.nonce,
                phone:  phone
            }, function(res){
                $btn.prop('disabled', false).text('💬 Start Chat on WhatsApp');
                if(res.success){
                    // Update local flag so polling works immediately
                    wabmhWidget.has_started = true;
                    wabmhWidget.user_phone  = phone;

                    // Open WhatsApp
                    var link = 'https://wa.me/' + wabmhWidget.business_phone
                             + '?text=' + encodeURIComponent(wabmhWidget.default_msg || 'Hi, I need help');
                    window.open(link, '_blank');

                    // Switch to chat screen
                    showChatScreen();
                    // Prepend instruction bubble
                    $('#wabmh-widget-messages').prepend(
                        '<div class="wabmh-widget-msg incoming">'
                        +'<div class="wabmh-widget-bubble">'
                        +'✅ WhatsApp opened!<br>'
                        +'👉 Send the message from WhatsApp.<br>'
                        +'Your replies will appear here.'
                        +'</div></div>'
                    );
                } else {
                    $err.text(res.data.message || 'Error. Try again.').show();
                }
            });
        });

        // ── Send on button click ──────────────────────────────────
        $(document).on('click', '#wabmh-widget-send', function(){
            sendMessage();
        });

        // ── Send on Enter (Shift+Enter = newline) ─────────────────
        $(document).on('keydown', '#wabmh-widget-textarea', function(e){
            if(e.key === 'Enter' && !e.shiftKey){
                e.preventDefault();
                sendMessage();
            }
        });

        // ── Auto-resize textarea ──────────────────────────────────
        $(document).on('input', '#wabmh-widget-textarea', function(){
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

    });  // end document.ready

})(jQuery);

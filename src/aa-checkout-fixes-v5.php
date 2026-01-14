<?php
/**
 * AA Checkout Fixes Bundle v5.0
 *
 * Fixes:
 * - 3DS authentication fix for checkout
 * - 3DS authentication fix for order-pay (subsequent payments)
 * - Auto-select payment_option for order-pay
 * - CSS fix for 3DS modal positioning
 * - DEBUG PANEL: Floating debug overlay for full flow tracking
 *
 * @author Ahmed Sallemi
 * @date January 2026
 * @version 5.0
 */

// ============================================
// PART 0: DEBUG PANEL (TOP-LEFT FLOATING)
// ============================================

add_action('wp_footer', function() {
    // Show on checkout, order-pay, and account pages only
    if (!is_checkout() && !is_wc_endpoint_url('order-pay') && !is_account_page()) return;
    // ONLY show on dev site - NOT production
    if (strpos($_SERVER['HTTP_HOST'], 'dev.') !== 0) return;

    // Gather debug info
    $user_logged_in = is_user_logged_in();
    $current_user = wp_get_current_user();
    $page_type = is_wc_endpoint_url('order-pay') ? 'order-pay' : (is_wc_endpoint_url('order-received') ? 'order-received' : 'checkout');

    // Get order ID if on order-pay
    global $wp;
    $order_id = isset($wp->query_vars['order-pay']) ? absint($wp->query_vars['order-pay']) : 0;
    $order_status = '';
    $order_total = '';
    $order_paid = '';
    $order_due = '';

    if ($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $order_status = $order->get_status();
            $order_total = $order->get_total();
            $order_paid = $order->get_meta('_aa_total_paid') ?: '0';
            $order_due = floatval($order_total) - floatval($order_paid);
        }
    }

    // Session data
    $session_order_id = WC()->session ? WC()->session->get('aa_order_id') : null;
    $session_pay_order_id = WC()->session ? WC()->session->get('aa_pay_order_id') : null;
    $saved_payment_option = $order_id ? get_post_meta($order_id, '_aa_payment_option', true) : '';

    ?>
    <style id="aa-debug-panel-styles">
    #aa-debug-panel {
        position: fixed !important;
        top: 10px !important;
        left: 10px !important;
        width: 320px !important;
        max-height: 90vh !important;
        background: rgba(0, 0, 0, 0.92) !important;
        color: #0f0 !important;
        font-family: 'Monaco', 'Menlo', 'Consolas', monospace !important;
        font-size: 11px !important;
        line-height: 1.4 !important;
        padding: 0 !important;
        border-radius: 8px !important;
        z-index: 2147483640 !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5) !important;
        overflow: hidden !important;
        pointer-events: auto !important;
    }
    #aa-debug-panel * {
        box-sizing: border-box !important;
    }
    #aa-debug-header {
        background: #1a1a1a !important;
        padding: 8px 12px !important;
        border-bottom: 1px solid #333 !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        cursor: move !important;
    }
    #aa-debug-header h4 {
        margin: 0 !important;
        color: #0f0 !important;
        font-size: 12px !important;
        font-weight: bold !important;
    }
    #aa-debug-toggle {
        background: #333 !important;
        color: #fff !important;
        border: none !important;
        padding: 2px 8px !important;
        border-radius: 3px !important;
        cursor: pointer !important;
        font-size: 10px !important;
    }
    #aa-debug-content {
        padding: 10px 12px !important;
        max-height: calc(90vh - 40px) !important;
        overflow-y: auto !important;
    }
    #aa-debug-content::-webkit-scrollbar {
        width: 6px !important;
    }
    #aa-debug-content::-webkit-scrollbar-thumb {
        background: #444 !important;
        border-radius: 3px !important;
    }
    .aa-debug-section {
        margin-bottom: 12px !important;
        padding-bottom: 8px !important;
        border-bottom: 1px solid #333 !important;
    }
    .aa-debug-section:last-child {
        border-bottom: none !important;
        margin-bottom: 0 !important;
    }
    .aa-debug-title {
        color: #ff0 !important;
        font-weight: bold !important;
        margin-bottom: 4px !important;
        font-size: 11px !important;
    }
    .aa-debug-row {
        display: flex !important;
        justify-content: space-between !important;
        padding: 2px 0 !important;
    }
    .aa-debug-label {
        color: #888 !important;
    }
    .aa-debug-value {
        color: #0f0 !important;
        font-weight: bold !important;
    }
    .aa-debug-value.success { color: #0f0 !important; }
    .aa-debug-value.warning { color: #ff0 !important; }
    .aa-debug-value.error { color: #f00 !important; }
    .aa-debug-value.info { color: #0ff !important; }
    #aa-debug-log {
        background: #111 !important;
        border-radius: 4px !important;
        padding: 6px !important;
        max-height: 150px !important;
        overflow-y: auto !important;
        font-size: 10px !important;
    }
    .aa-log-entry {
        padding: 2px 0 !important;
        border-bottom: 1px solid #222 !important;
        word-wrap: break-word !important;
    }
    .aa-log-entry:last-child {
        border-bottom: none !important;
    }
    .aa-log-time {
        color: #666 !important;
        margin-right: 6px !important;
    }
    .aa-log-msg { color: #0f0 !important; }
    .aa-log-msg.warn { color: #ff0 !important; }
    .aa-log-msg.error { color: #f00 !important; }
    .aa-log-msg.info { color: #0ff !important; }
    #aa-debug-clear {
        background: #333 !important;
        color: #fff !important;
        border: none !important;
        padding: 4px 8px !important;
        border-radius: 3px !important;
        cursor: pointer !important;
        font-size: 10px !important;
        margin-top: 6px !important;
    }
    /* Tab Styles */
    #aa-debug-tabs {
        display: flex !important;
        background: #1a1a1a !important;
        border-bottom: 1px solid #333 !important;
        padding: 0 5px !important;
    }
    .aa-tab-btn {
        background: transparent !important;
        border: none !important;
        color: #888 !important;
        padding: 8px 12px !important;
        cursor: pointer !important;
        font-size: 11px !important;
        font-family: inherit !important;
        border-bottom: 2px solid transparent !important;
        margin-bottom: -1px !important;
    }
    .aa-tab-btn:hover {
        color: #ccc !important;
    }
    .aa-tab-btn.active {
        color: #0f0 !important;
        border-bottom-color: #0f0 !important;
    }
    .aa-tab-content {
        display: none !important;
    }
    .aa-tab-content.active {
        display: block !important;
    }
    /* Orders Tab Styles */
    .aa-order-card {
        background: #111 !important;
        border-radius: 4px !important;
        padding: 8px !important;
        margin-bottom: 8px !important;
        border-left: 3px solid #333 !important;
    }
    .aa-order-card.aligned {
        border-left-color: #0f0 !important;
    }
    .aa-order-card.misaligned {
        border-left-color: #f00 !important;
    }
    .aa-order-header {
        display: flex !important;
        justify-content: space-between !important;
        margin-bottom: 6px !important;
    }
    .aa-order-id {
        color: #0ff !important;
        font-weight: bold !important;
    }
    .aa-order-status {
        padding: 2px 6px !important;
        border-radius: 3px !important;
        font-size: 9px !important;
        text-transform: uppercase !important;
    }
    .aa-order-status.processing { background: #f90 !important; color: #000 !important; }
    .aa-order-status.completed { background: #0f0 !important; color: #000 !important; }
    .aa-order-status.pending { background: #ff0 !important; color: #000 !important; }
    .aa-order-status.cancelled { background: #f00 !important; color: #fff !important; }
    .aa-txn-row {
        display: flex !important;
        justify-content: space-between !important;
        padding: 3px 0 !important;
        border-bottom: 1px dashed #333 !important;
        font-size: 10px !important;
    }
    .aa-txn-row:last-child {
        border-bottom: none !important;
    }
    .aa-stripe-match { color: #0f0 !important; }
    .aa-stripe-mismatch { color: #f00 !important; }
    .aa-loading-orders {
        text-align: center !important;
        padding: 20px !important;
        color: #888 !important;
    }
    #aa-orders-list {
        max-height: 400px !important;
        overflow-y: auto !important;
    }
    .aa-refresh-btn {
        background: #333 !important;
        color: #fff !important;
        border: none !important;
        padding: 6px 12px !important;
        border-radius: 3px !important;
        cursor: pointer !important;
        font-size: 10px !important;
        margin-bottom: 10px !important;
        width: 100% !important;
    }
    .aa-refresh-btn:hover {
        background: #444 !important;
    }
    /* Reset Button Styles */
    .aa-reset-btn {
        background: #c00 !important;
        color: #fff !important;
        border: none !important;
        padding: 8px 16px !important;
        border-radius: 4px !important;
        cursor: pointer !important;
        font-size: 11px !important;
        font-weight: bold !important;
        width: 100% !important;
        transition: background 0.2s !important;
    }
    .aa-reset-btn:hover {
        background: #e00 !important;
    }
    .aa-reset-btn:disabled {
        background: #666 !important;
        cursor: not-allowed !important;
    }
    .aa-reset-result-item {
        padding: 4px 0 !important;
        border-bottom: 1px solid #333 !important;
        font-size: 10px !important;
    }
    .aa-reset-success { color: #0f0 !important; }
    .aa-reset-error { color: #f00 !important; }
    .aa-reset-info { color: #0ff !important; }
    </style>

    <div id="aa-debug-panel">
        <div id="aa-debug-header">
            <h4>🔧 AA Debug v6</h4>
            <button id="aa-debug-toggle">−</button>
        </div>
        <div id="aa-debug-tabs">
            <button class="aa-tab-btn active" data-tab="debug">Debug</button>
            <button class="aa-tab-btn" data-tab="orders">Orders</button>
            <button class="aa-tab-btn" data-tab="tools">Tools</button>
        </div>
        <div id="aa-debug-content">
            <!-- DEBUG TAB -->
            <div id="aa-tab-debug" class="aa-tab-content active">
            <!-- Authentication Section -->
            <div class="aa-debug-section">
                <div class="aa-debug-title">👤 AUTHENTICATION</div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Logged In:</span>
                    <span class="aa-debug-value <?php echo $user_logged_in ? 'success' : 'error'; ?>">
                        <?php echo $user_logged_in ? 'YES' : 'NO'; ?>
                    </span>
                </div>
                <?php if ($user_logged_in): ?>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">User:</span>
                    <span class="aa-debug-value info"><?php echo esc_html($current_user->user_email); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Page Info Section -->
            <div class="aa-debug-section">
                <div class="aa-debug-title">📄 PAGE INFO</div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Page Type:</span>
                    <span class="aa-debug-value info"><?php echo $page_type; ?></span>
                </div>
                <?php if ($order_id): ?>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Order ID:</span>
                    <span class="aa-debug-value">#<?php echo $order_id; ?></span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Order Status:</span>
                    <span class="aa-debug-value <?php echo $order_status == 'completed' ? 'success' : ($order_status == 'pending' ? 'warning' : 'info'); ?>">
                        <?php echo $order_status; ?>
                    </span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Total:</span>
                    <span class="aa-debug-value">£<?php echo number_format($order_total, 2); ?></span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Paid:</span>
                    <span class="aa-debug-value">£<?php echo number_format($order_paid, 2); ?></span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Due:</span>
                    <span class="aa-debug-value <?php echo $order_due > 0 ? 'warning' : 'success'; ?>">
                        £<?php echo number_format($order_due, 2); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Stripe Status Section -->
            <div class="aa-debug-section">
                <div class="aa-debug-title">💳 STRIPE STATUS</div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Stripe SDK:</span>
                    <span id="aa-stripe-js" class="aa-debug-value warning">Checking...</span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">WC Stripe JS:</span>
                    <span id="aa-wc-stripe-js" class="aa-debug-value warning">Checking...</span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">wc_stripe_params:</span>
                    <span id="aa-stripe-params" class="aa-debug-value warning">Checking...</span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Payment Form:</span>
                    <span id="aa-payment-form" class="aa-debug-value warning">Checking...</span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Payment Method:</span>
                    <span id="aa-payment-method" class="aa-debug-value warning">Checking...</span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">3DS Iframe:</span>
                    <span id="aa-3ds-iframe" class="aa-debug-value">None</span>
                </div>
            </div>

            <!-- Order Payment Check (Server-side) -->
            <?php if ($order_id && $order): ?>
            <div class="aa-debug-section">
                <div class="aa-debug-title">🔍 STRIPE LOAD CHECK</div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">needs_payment():</span>
                    <span class="aa-debug-value <?php echo $order->needs_payment() ? 'success' : 'error'; ?>">
                        <?php echo $order->needs_payment() ? 'YES' : 'NO - BLOCKS STRIPE!'; ?>
                    </span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">can_pay_for_order:</span>
                    <span class="aa-debug-value <?php echo current_user_can('pay_for_order', $order_id) ? 'success' : 'error'; ?>">
                        <?php echo current_user_can('pay_for_order', $order_id) ? 'YES' : 'NO - BLOCKS STRIPE!'; ?>
                    </span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">get_total():</span>
                    <span class="aa-debug-value">£<?php echo $order->get_total(); ?></span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">is_paid():</span>
                    <span class="aa-debug-value <?php echo $order->is_paid() ? 'error' : 'success'; ?>">
                        <?php echo $order->is_paid() ? 'YES - BLOCKS STRIPE!' : 'NO'; ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payment Options Section -->
            <div class="aa-debug-section">
                <div class="aa-debug-title">💰 PAYMENT OPTIONS</div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Selected:</span>
                    <span id="aa-payment-option" class="aa-debug-value warning">Checking...</span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Saved Meta:</span>
                    <span class="aa-debug-value info"><?php echo $saved_payment_option ?: 'None'; ?></span>
                </div>
            </div>

            <!-- Session Section -->
            <div class="aa-debug-section">
                <div class="aa-debug-title">🗂️ SESSION</div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Session Order:</span>
                    <span class="aa-debug-value"><?php echo $session_order_id ? '#'.$session_order_id : 'None'; ?></span>
                </div>
                <div class="aa-debug-row">
                    <span class="aa-debug-label">Pay Order:</span>
                    <span class="aa-debug-value"><?php echo $session_pay_order_id ? '#'.$session_pay_order_id : 'None'; ?></span>
                </div>
            </div>

            <!-- AJAX Log Section -->
            <div class="aa-debug-section">
                <div class="aa-debug-title">📡 LIVE LOG</div>
                <div id="aa-debug-log"></div>
                <button id="aa-debug-clear">Clear Log</button>
            </div>
            </div><!-- END DEBUG TAB -->

            <!-- ORDERS TAB -->
            <div id="aa-tab-orders" class="aa-tab-content">
                <button class="aa-refresh-btn" id="aa-refresh-orders">🔄 Refresh Orders</button>
                <div id="aa-orders-list">
                    <div class="aa-loading-orders">Loading orders...</div>
                </div>
            </div><!-- END ORDERS TAB -->

            <!-- TOOLS TAB -->
            <div id="aa-tab-tools" class="aa-tab-content">
                <div class="aa-debug-section">
                    <div class="aa-debug-title">🗑️ RESET TEST DATA</div>
                    <p style="color:#888;font-size:10px;margin:5px 0 10px;">Delete all orders for <strong style="color:#0ff;">fenous@gmail.com</strong> from DB and cancel Stripe charges.</p>
                    <button id="aa-reset-test-data" class="aa-reset-btn">🗑️ Reset Test Data</button>
                    <div id="aa-reset-result" style="margin-top:10px;display:none;"></div>
                </div>
                <div class="aa-debug-section">
                    <div class="aa-debug-title">🔄 CLEAR SESSION</div>
                    <p style="color:#888;font-size:10px;margin:5px 0 10px;">Clear WooCommerce session data to start fresh checkout.</p>
                    <button id="aa-clear-session" class="aa-reset-btn" style="background:#555;">🔄 Clear Session</button>
                </div>
            </div><!-- END TOOLS TAB -->
        </div>
    </div>

    <script>
    (function() {
        var panel = document.getElementById('aa-debug-panel');
        var content = document.getElementById('aa-debug-content');
        var toggle = document.getElementById('aa-debug-toggle');
        var logDiv = document.getElementById('aa-debug-log');
        var clearBtn = document.getElementById('aa-debug-clear');
        var isMinimized = false;

        // Make panel draggable
        var header = document.getElementById('aa-debug-header');
        var isDragging = false;
        var offsetX, offsetY;

        header.addEventListener('mousedown', function(e) {
            if (e.target === toggle) return;
            isDragging = true;
            offsetX = e.clientX - panel.offsetLeft;
            offsetY = e.clientY - panel.offsetTop;
            panel.style.transition = 'none';
        });

        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            panel.style.left = (e.clientX - offsetX) + 'px';
            panel.style.top = (e.clientY - offsetY) + 'px';
        });

        document.addEventListener('mouseup', function() {
            isDragging = false;
            panel.style.transition = '';
        });

        // Toggle minimize
        toggle.addEventListener('click', function() {
            isMinimized = !isMinimized;
            content.style.display = isMinimized ? 'none' : 'block';
            toggle.textContent = isMinimized ? '+' : '−';
        });

        // Clear log
        clearBtn.addEventListener('click', function() {
            logDiv.innerHTML = '';
        });

        // Tab switching
        var tabBtns = document.querySelectorAll('.aa-tab-btn');
        var tabContents = document.querySelectorAll('.aa-tab-content');

        tabBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tabId = this.getAttribute('data-tab');

                // Update buttons
                tabBtns.forEach(function(b) { b.classList.remove('active'); });
                this.classList.add('active');

                // Update content
                tabContents.forEach(function(c) { c.classList.remove('active'); });
                document.getElementById('aa-tab-' + tabId).classList.add('active');

                // Load orders on first tab switch
                if (tabId === 'orders' && !ordersLoaded) {
                    loadUserOrders();
                }
            });
        });

        // Orders tab functionality
        var ordersLoaded = false;
        var ordersList = document.getElementById('aa-orders-list');
        var refreshBtn = document.getElementById('aa-refresh-orders');

        refreshBtn.addEventListener('click', function() {
            loadUserOrders();
        });

        function loadUserOrders() {
            ordersList.innerHTML = '<div class="aa-loading-orders">Loading orders...</div>';

            // AJAX call to get user orders with Stripe alignment
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                renderOrders(response.data);
                                ordersLoaded = true;
                            } else {
                                ordersList.innerHTML = '<div class="aa-loading-orders">Error: ' + (response.data || 'Unknown error') + '</div>';
                            }
                        } catch(e) {
                            ordersList.innerHTML = '<div class="aa-loading-orders">Error parsing response</div>';
                        }
                    } else {
                        ordersList.innerHTML = '<div class="aa-loading-orders">Request failed</div>';
                    }
                }
            };
            xhr.send('action=aa_get_user_orders_stripe&nonce=<?php echo wp_create_nonce('aa_debug_orders'); ?>');
        }

        function renderOrders(orders) {
            if (!orders || orders.length === 0) {
                ordersList.innerHTML = '<div class="aa-loading-orders">No orders found</div>';
                return;
            }

            var html = '';
            orders.forEach(function(order) {
                var allMatch = order.transactions && order.transactions.every(function(t) { return t.stripe_verified; });
                var alignClass = allMatch ? 'aligned' : 'misaligned';

                html += '<div class="aa-order-card ' + alignClass + '">';
                html += '<div class="aa-order-header">';
                html += '<span class="aa-order-id">#' + order.id + '</span>';
                html += '<span class="aa-order-status ' + order.status + '">' + order.status + '</span>';
                html += '</div>';

                if (order.transactions && order.transactions.length > 0) {
                    // Calculate totals from transactions
                    var dbTotal = 0, stripeTotal = 0;
                    order.transactions.forEach(function(t) {
                        dbTotal += parseFloat(t.amount) || 0;
                        stripeTotal += parseFloat(t.stripe_amount) || 0;
                    });

                    html += '<table style="width:100%;border-collapse:collapse;font-size:10px;margin-top:4px;">';
                    html += '<thead><tr style="border-bottom:1px solid #444;">';
                    html += '<th style="text-align:left;color:#ff0;padding:4px 2px;">Payment</th>';
                    html += '<th style="text-align:right;color:#ff0;padding:4px 2px;">DB</th>';
                    html += '<th style="text-align:right;color:#ff0;padding:4px 2px;">Stripe</th>';
                    html += '<th style="text-align:center;color:#ff0;padding:4px 2px;">✓</th>';
                    html += '</tr></thead><tbody>';

                    order.transactions.forEach(function(txn) {
                        var match = txn.stripe_verified;
                        var icon = match ? '✓' : '✗';
                        var cls = match ? 'aa-stripe-match' : 'aa-stripe-mismatch';
                        var rowBg = match ? '' : 'background:rgba(255,0,0,0.15);';
                        html += '<tr style="' + rowBg + '">';
                        html += '<td style="padding:3px 2px 0;color:#ccc;">' + txn.type + '</td>';
                        html += '<td style="padding:3px 2px 0;text-align:right;color:#0f0;">£' + txn.amount + '</td>';
                        html += '<td style="padding:3px 2px 0;text-align:right;" class="' + cls + '">£' + txn.stripe_amount + '</td>';
                        html += '<td style="padding:3px 2px 0;text-align:center;" class="' + cls + '">' + icon + '</td>';
                        html += '</tr>';
                        // Show Stripe ID under the transaction
                        if (txn.stripe_id) {
                            html += '<tr><td colspan="4" style="padding:0 2px 6px;font-size:8px;color:#555;font-family:monospace;">' + txn.stripe_id + '</td></tr>';
                        }
                    });

                    // Totals row
                    var totalsMatch = Math.abs(dbTotal - stripeTotal) < 0.01;
                    var totalsCls = totalsMatch ? 'aa-stripe-match' : 'aa-stripe-mismatch';
                    html += '<tr style="border-top:1px solid #444;font-weight:bold;">';
                    html += '<td style="padding:4px 2px;color:#ff0;">TOTAL</td>';
                    html += '<td style="padding:4px 2px;text-align:right;color:#0f0;">£' + dbTotal.toFixed(2) + '</td>';
                    html += '<td style="padding:4px 2px;text-align:right;" class="' + totalsCls + '">£' + stripeTotal.toFixed(2) + '</td>';
                    html += '<td style="padding:4px 2px;text-align:center;" class="' + totalsCls + '">' + (totalsMatch ? '✓' : '✗') + '</td>';
                    html += '</tr>';

                    html += '</tbody></table>';
                } else {
                    html += '<div style="color:#888;padding:8px;">No transactions</div>';
                }

                html += '</div>';
            });

            ordersList.innerHTML = html;
        }

        // Log function
        window.aaLog = function(msg, type) {
            type = type || 'info';
            var time = new Date().toLocaleTimeString();
            var entry = document.createElement('div');
            entry.className = 'aa-log-entry';
            entry.innerHTML = '<span class="aa-log-time">' + time + '</span><span class="aa-log-msg ' + type + '">' + msg + '</span>';
            logDiv.insertBefore(entry, logDiv.firstChild);

            // Keep only last 50 entries
            while (logDiv.children.length > 50) {
                logDiv.removeChild(logDiv.lastChild);
            }

            // Also console log
            console.log('[AA Debug] ' + msg);
        };

        // Update value helper
        function updateValue(id, value, className) {
            var el = document.getElementById(id);
            if (el) {
                el.textContent = value;
                el.className = 'aa-debug-value ' + (className || '');
            }
        }

        // Check Stripe SDK (js.stripe.com)
        function checkStripeJS() {
            if (typeof Stripe !== 'undefined') {
                updateValue('aa-stripe-js', 'Loaded', 'success');
                return true;
            } else if (document.querySelector('script[src*="js.stripe.com"]')) {
                updateValue('aa-stripe-js', 'Loading...', 'warning');
                return false;
            } else {
                updateValue('aa-stripe-js', 'NOT LOADED', 'error');
                aaLog('Stripe SDK NOT loaded!', 'error');
                return false;
            }
        }

        // Check WooCommerce Stripe JS (both legacy and UPE)
        function checkWCStripeJS() {
            // Check for UPE script
            var upeScript = document.querySelector('script[src*="upe-classic"], script[src*="upe_classic"]');
            if (upeScript) {
                updateValue('aa-wc-stripe-js', 'UPE Loaded', 'success');
                aaLog('UPE Stripe script loaded', 'info');
                return true;
            }
            // Check for legacy script
            var legacyScript = document.querySelector('script[src*="woocommerce-gateway-stripe"][src*="stripe"]');
            if (legacyScript) {
                updateValue('aa-wc-stripe-js', 'Legacy Loaded', 'success');
                aaLog('Legacy Stripe script loaded', 'info');
                return true;
            }
            // Neither found
            updateValue('aa-wc-stripe-js', 'NOT LOADED!', 'error');
            aaLog('WC Stripe handler NOT loaded!', 'error');
            return false;
        }

        // Check wc_stripe_params (localized data) - supports both legacy and UPE
        function checkStripeParams() {
            // Check for UPE params first (newer)
            if (typeof wc_stripe_upe_params !== 'undefined') {
                var params = wc_stripe_upe_params;
                updateValue('aa-stripe-params', 'UPE Available', 'success');

                // Check critical params for order-pay
                if (params.isOrderPay) {
                    aaLog('isOrderPay: TRUE', 'info');
                } else {
                    aaLog('isOrderPay: FALSE/MISSING - payment will fail!', 'error');
                }
                if (params.orderId) {
                    aaLog('orderId: ' + params.orderId, 'info');
                }
                if (params.isPaymentNeeded !== undefined) {
                    aaLog('isPaymentNeeded: ' + params.isPaymentNeeded, params.isPaymentNeeded ? 'info' : 'error');
                }
                if (params.cartTotal !== undefined) {
                    aaLog('cartTotal: ' + params.cartTotal, 'info');
                }
                return true;
            }
            // Check for legacy params
            if (typeof wc_stripe_params !== 'undefined') {
                updateValue('aa-stripe-params', 'Legacy Available', 'success');
                aaLog('wc_stripe_params found (legacy mode)', 'info');
                return true;
            }
            // Neither found
            updateValue('aa-stripe-params', 'MISSING!', 'error');
            aaLog('Stripe params MISSING - form handler broken!', 'error');
            return false;
        }

        // Check Payment Form
        function checkPaymentForm() {
            var form = document.querySelector('form.woocommerce-checkout, form#order_review');
            var loginForm = document.querySelector('form.woocommerce-form-login');

            if (loginForm && !form) {
                updateValue('aa-payment-form', 'LOGIN REQUIRED', 'error');
                aaLog('Payment form hidden - LOGIN REQUIRED', 'error');
                return false;
            } else if (form) {
                updateValue('aa-payment-form', 'Found', 'success');
                return true;
            } else {
                updateValue('aa-payment-form', 'Not Found', 'error');
                return false;
            }
        }

        // Check Payment Method
        function checkPaymentMethod() {
            var stripeRadio = document.querySelector('input[value="stripe"]:checked, input[id*="stripe"]:checked');
            var stripeElement = document.querySelector('[class*="StripeElement"], #stripe-card-element, .wc-stripe-elements-field');
            var paymentMethods = document.querySelectorAll('input[name="payment_method"]');

            if (stripeRadio) {
                updateValue('aa-payment-method', 'Stripe (selected)', 'success');
                return true;
            } else if (stripeElement) {
                updateValue('aa-payment-method', 'Stripe Element Found', 'success');
                return true;
            } else if (paymentMethods.length > 0) {
                var selected = document.querySelector('input[name="payment_method"]:checked');
                updateValue('aa-payment-method', selected ? selected.value : 'None selected', 'warning');
                return false;
            } else {
                updateValue('aa-payment-method', 'No methods found', 'error');
                return false;
            }
        }

        // Check Payment Options
        function checkPaymentOptions() {
            var options = document.querySelectorAll('input[name="payment_option"]');
            var selected = document.querySelector('input[name="payment_option"]:checked');

            if (selected) {
                updateValue('aa-payment-option', selected.value, 'success');
            } else if (options.length > 0) {
                updateValue('aa-payment-option', 'Not selected (' + options.length + ' options)', 'warning');
            } else {
                updateValue('aa-payment-option', 'No options found', 'info');
            }
        }

        // Monitor iframes for 3DS
        var iframeCount = 0;
        function monitorIframes() {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.tagName === 'IFRAME') {
                            var name = node.name || '';
                            var src = node.src || '';

                            if (name.indexOf('__privateStripe') === 0) {
                                iframeCount++;
                                aaLog('Stripe iframe #' + iframeCount + ': ' + name.substring(0, 30), 'info');

                                if (src.indexOf('3d-secure') !== -1 ||
                                    src.indexOf('challenge') !== -1 ||
                                    src.indexOf('authenticate') !== -1) {
                                    updateValue('aa-3ds-iframe', '3DS ACTIVE!', 'success');
                                    aaLog('3DS Challenge iframe detected!', 'warn');
                                } else {
                                    updateValue('aa-3ds-iframe', name.substring(0, 20) + '...', 'info');
                                }
                            }
                        }
                    });

                    mutation.removedNodes.forEach(function(node) {
                        if (node.tagName === 'IFRAME' && node.name && node.name.indexOf('__privateStripe') === 0) {
                            aaLog('Stripe iframe removed: ' + node.name.substring(0, 30), 'info');
                        }
                    });
                });
            });

            observer.observe(document.body, { childList: true, subtree: true });
            aaLog('Iframe monitor started', 'info');
        }

        // Monitor AJAX calls
        function monitorAJAX() {
            if (typeof jQuery !== 'undefined') {
                jQuery(document).ajaxSend(function(event, xhr, settings) {
                    if (settings.url && (settings.url.indexOf('wc-ajax') !== -1 || settings.url.indexOf('stripe') !== -1)) {
                        var action = settings.url.match(/wc-ajax=([^&]+)/);
                        action = action ? action[1] : settings.url.substring(0, 50);
                        aaLog('AJAX → ' + action, 'info');
                    }
                });

                jQuery(document).ajaxComplete(function(event, xhr, settings) {
                    if (settings.url && (settings.url.indexOf('wc-ajax') !== -1 || settings.url.indexOf('stripe') !== -1)) {
                        var action = settings.url.match(/wc-ajax=([^&]+)/);
                        action = action ? action[1] : 'ajax';
                        var status = xhr.status;

                        if (status >= 200 && status < 300) {
                            aaLog('AJAX ✓ ' + action + ' (' + status + ')', 'info');
                        } else if (status >= 400) {
                            aaLog('AJAX ✗ ' + action + ' (' + status + ')', 'error');
                        }

                        // Check response for redirect
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.result === 'success') {
                                aaLog('Payment SUCCESS!', 'warn');
                            } else if (response.result === 'failure') {
                                aaLog('Payment FAILED: ' + (response.messages || '').substring(0, 50), 'error');
                            }
                        } catch(e) {}
                    }
                });

                aaLog('AJAX monitor started', 'info');
            }
        }

        // Monitor form submissions - AGGRESSIVE VERSION
        function monitorForms() {
            // Log ALL clicks on the document
            document.addEventListener('click', function(e) {
                var target = e.target;
                var tagName = target.tagName || '';
                var id = target.id || '';
                var className = target.className || '';
                var text = (target.textContent || '').substring(0, 30);

                // Log if it's a button or looks like a button
                if (tagName === 'BUTTON' || tagName === 'INPUT' ||
                    className.indexOf('button') !== -1 ||
                    className.indexOf('btn') !== -1 ||
                    id.indexOf('place') !== -1 ||
                    text.indexOf('Pay') !== -1) {
                    aaLog('CLICK: ' + tagName + ' #' + id + ' .' + className.substring(0,30), 'warn');
                }
            }, true);

            // Capture ALL form submits
            document.addEventListener('submit', function(e) {
                aaLog('FORM SUBMIT: ' + (e.target.id || e.target.className), 'warn');
            }, true);

            // Hook into jQuery submit if available
            if (typeof jQuery !== 'undefined') {
                var $ = jQuery;

                // Check what payment method is selected
                function getSelectedPaymentMethod() {
                    var checked = $('input[name="payment_method"]:checked');
                    return checked.length ? checked.val() : 'none';
                }

                // Check for Stripe element
                function hasStripeElement() {
                    return $('.wc-stripe-upe-element').length > 0 || $('#stripe-card-element').length > 0;
                }

                $(document).on('submit', 'form', function(e) {
                    aaLog('jQuery SUBMIT: ' + (this.id || this.className), 'warn');
                });

                // Monitor the checkout form specifically - BEFORE other handlers
                $('form#order_review').on('submit.aadebug', function(e) {
                    aaLog('=== ORDER_REVIEW SUBMIT ===', 'warn');
                    aaLog('Payment method: ' + getSelectedPaymentMethod(), 'info');
                    aaLog('Has Stripe element: ' + hasStripeElement(), 'info');

                    // Check for required hidden fields
                    var intentId = $('#wc-stripe-payment-intent').val() || $('#stripe-intent-id').val();
                    var setupIntent = $('#wc-stripe-setup-intent').val();
                    aaLog('Payment Intent ID: ' + (intentId || 'NONE'), intentId ? 'info' : 'warn');
                    aaLog('Setup Intent: ' + (setupIntent || 'NONE'), 'info');

                    // Check UPE params
                    if (typeof wc_stripe_upe_params !== 'undefined') {
                        aaLog('UPE key present: ' + (wc_stripe_upe_params.key ? 'YES' : 'NO'), 'info');
                    }
                });

                // Hook WooCommerce checkout events
                $(document.body).on('checkout_error', function(e, errorMessage) {
                    aaLog('WC checkout_error: ' + (errorMessage || 'unknown'), 'error');
                });

                $(document.body).on('payment_method_selected', function() {
                    aaLog('Payment method selected: ' + getSelectedPaymentMethod(), 'info');
                });

                // Monitor Stripe UPE specific events
                $(document.body).on('wc-stripe-upe-form-init', function() {
                    aaLog('Stripe UPE form initialized', 'info');
                });

                // Try to hook into the actual UPE handler
                var origOnSubmit = $.fn.on;
                $.fn.on = function(events, selector, data, handler) {
                    // Intercept #order_review submit handlers
                    if (this.selector === '#order_review' && events === 'submit') {
                        aaLog('UPE submit handler being attached!', 'warn');
                    }
                    return origOnSubmit.apply(this, arguments);
                };

                // Check for blocked form submission
                $(document).on('submit', '#order_review', function(e) {
                    setTimeout(function() {
                        if (!e.isDefaultPrevented()) {
                            aaLog('Form submit NOT prevented', 'info');
                        } else {
                            aaLog('Form submit WAS prevented', 'warn');
                        }
                    }, 10);
                });

                aaLog('Payment method on load: ' + getSelectedPaymentMethod(), 'info');
                aaLog('Stripe element present: ' + hasStripeElement(), 'info');
            }

            // Monitor XMLHttpRequest
            var origXHROpen = XMLHttpRequest.prototype.open;
            var origXHRSend = XMLHttpRequest.prototype.send;

            XMLHttpRequest.prototype.open = function(method, url) {
                this._url = url;
                return origXHROpen.apply(this, arguments);
            };

            XMLHttpRequest.prototype.send = function(body) {
                if (this._url && (this._url.indexOf('wc-ajax') !== -1 || this._url.indexOf('stripe') !== -1 || this._url.indexOf('admin-ajax') !== -1)) {
                    aaLog('XHR: ' + this._url.substring(0, 60), 'info');
                }
                return origXHRSend.apply(this, arguments);
            };

            // Monitor fetch
            var origFetch = window.fetch;
            window.fetch = function(url, options) {
                var urlStr = typeof url === 'string' ? url : (url.url || '');
                if (urlStr.indexOf('stripe') !== -1 || urlStr.indexOf('wc-ajax') !== -1 || urlStr.indexOf('admin-ajax') !== -1) {
                    aaLog('Fetch: ' + urlStr.substring(0, 60), 'info');
                }
                return origFetch.apply(this, arguments).then(function(response) {
                    if (urlStr.indexOf('stripe') !== -1) {
                        aaLog('Fetch OK: ' + response.status, 'info');
                    }
                    return response;
                }).catch(function(err) {
                    aaLog('Fetch ERR: ' + err.message, 'error');
                    throw err;
                });
            };

            // Watch for button state changes
            var payBtn = document.querySelector('#place_order, button[type="submit"], .wc-stripe-elements-field button');
            if (payBtn) {
                aaLog('Pay button found: ' + payBtn.tagName + ' #' + payBtn.id, 'info');
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(m) {
                        if (m.attributeName === 'disabled' || m.attributeName === 'class') {
                            aaLog('Button state changed: disabled=' + payBtn.disabled + ' class=' + payBtn.className.substring(0,30), 'warn');
                        }
                    });
                });
                observer.observe(payBtn, { attributes: true });
            } else {
                aaLog('Pay button NOT found!', 'error');
                // List all buttons for debugging
                var allBtns = document.querySelectorAll('button, input[type="submit"]');
                allBtns.forEach(function(btn, i) {
                    aaLog('Btn[' + i + ']: ' + btn.tagName + ' #' + btn.id + ' val=' + (btn.value||btn.textContent||'').substring(0,20), 'info');
                });
            }

            aaLog('Form monitor v2 started', 'info');
        }

        // Monitor for JavaScript errors
        window.addEventListener('error', function(e) {
            aaLog('JS Error: ' + e.message + ' at ' + e.filename + ':' + e.lineno, 'error');
        });

        // Monitor unhandled promise rejections
        window.addEventListener('unhandledrejection', function(e) {
            var reason = e.reason;
            var msg = reason ? (reason.message || reason.toString()) : 'unknown';
            aaLog('Promise REJECT: ' + msg.substring(0, 100), 'error');
        });

        // Console log interceptor
        var origConsoleError = console.error;
        console.error = function() {
            var msg = Array.prototype.slice.call(arguments).join(' ').substring(0, 100);
            aaLog('Console.error: ' + msg, 'error');
            return origConsoleError.apply(this, arguments);
        };

        // Initial checks
        function runChecks() {
            checkStripeJS();
            checkWCStripeJS();
            checkStripeParams();
            checkPaymentForm();
            checkPaymentMethod();
            checkPaymentOptions();
        }

        // Initialize
        aaLog('Debug panel v6 initialized', 'warn');
        aaLog('Page: <?php echo $page_type; ?>', 'info');
        <?php if (!$user_logged_in): ?>
        aaLog('WARNING: User not logged in!', 'error');
        <?php endif; ?>
        <?php if ($order_id): ?>
        aaLog('Order #<?php echo $order_id; ?> - Status: <?php echo $order_status; ?>', 'info');
        <?php endif; ?>

        // Run checks on load
        if (document.readyState === 'complete') {
            runChecks();
            monitorIframes();
            monitorAJAX();
            monitorForms();
        } else {
            window.addEventListener('load', function() {
                runChecks();
                monitorIframes();
                monitorAJAX();
                monitorForms();
            });
        }

        // Re-run checks periodically
        setInterval(runChecks, 2000);

        // Reset Test Data button handler
        var resetBtn = document.getElementById('aa-reset-test-data');
        var resetResult = document.getElementById('aa-reset-result');
        var clearSessionBtn = document.getElementById('aa-clear-session');

        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                if (!confirm('⚠️ This will DELETE all orders for fenous@gmail.com and cancel Stripe charges.\n\nAre you sure?')) {
                    return;
                }

                resetBtn.disabled = true;
                resetBtn.textContent = '⏳ Processing...';
                resetResult.style.display = 'block';
                resetResult.innerHTML = '<div class="aa-reset-info">Starting reset...</div>';

                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        resetBtn.disabled = false;
                        resetBtn.textContent = '🗑️ Reset Test Data';

                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    var html = '<div class="aa-reset-success">✓ Reset complete!</div>';
                                    if (response.data.orders_deleted > 0) {
                                        html += '<div class="aa-reset-result-item aa-reset-info">Orders deleted: ' + response.data.orders_deleted + '</div>';
                                    }
                                    if (response.data.stripe_cancelled > 0) {
                                        html += '<div class="aa-reset-result-item aa-reset-info">Stripe refunds: ' + response.data.stripe_cancelled + '</div>';
                                    }
                                    if (response.data.errors && response.data.errors.length > 0) {
                                        response.data.errors.forEach(function(err) {
                                            html += '<div class="aa-reset-result-item aa-reset-error">⚠️ ' + err + '</div>';
                                        });
                                    }
                                    if (response.data.orders_deleted === 0) {
                                        html += '<div class="aa-reset-result-item aa-reset-info">No orders found for this email.</div>';
                                    }
                                    resetResult.innerHTML = html;
                                    // Refresh orders list if visible
                                    if (ordersLoaded) loadUserOrders();
                                } else {
                                    resetResult.innerHTML = '<div class="aa-reset-error">Error: ' + (response.data || 'Unknown error') + '</div>';
                                }
                            } catch(e) {
                                resetResult.innerHTML = '<div class="aa-reset-error">Error parsing response</div>';
                            }
                        } else {
                            resetResult.innerHTML = '<div class="aa-reset-error">Request failed (status: ' + xhr.status + ')</div>';
                        }
                    }
                };
                xhr.send('action=aa_reset_test_data&nonce=<?php echo wp_create_nonce('aa_reset_test_data'); ?>');
            });
        }

        // Clear Session button handler
        if (clearSessionBtn) {
            clearSessionBtn.addEventListener('click', function() {
                clearSessionBtn.disabled = true;
                clearSessionBtn.textContent = '⏳ Clearing...';

                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        clearSessionBtn.disabled = false;
                        clearSessionBtn.textContent = '🔄 Clear Session';
                        if (xhr.status === 200) {
                            alert('Session cleared! Refreshing page...');
                            window.location.reload();
                        } else {
                            alert('Failed to clear session');
                        }
                    }
                };
                xhr.send('action=aa_clear_session&nonce=<?php echo wp_create_nonce('aa_clear_session'); ?>');
            });
        }

        // Retry Stripe check
        var stripeCheckCount = 0;
        var stripeInterval = setInterval(function() {
            stripeCheckCount++;
            if (checkStripeJS() || stripeCheckCount > 10) {
                clearInterval(stripeInterval);
            }
        }, 1000);
    })();
    </script>
    <?php
}, 5); // Priority 5 - load early in footer

// ============================================
// AJAX HANDLER: Get User Orders with Stripe Alignment
// ============================================

add_action('wp_ajax_aa_get_user_orders_stripe', 'aa_get_user_orders_stripe_handler');
function aa_get_user_orders_stripe_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aa_debug_orders')) {
        wp_send_json_error('Invalid nonce');
        return;
    }

    // Must be logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
        return;
    }

    $user_id = get_current_user_id();

    // Get Stripe API key
    $stripe_settings = get_option('woocommerce_stripe_settings', []);
    $stripe_secret = $stripe_settings['testmode'] === 'yes'
        ? ($stripe_settings['test_secret_key'] ?? '')
        : ($stripe_settings['secret_key'] ?? '');

    // Get user's orders (last 10)
    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'limit' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    $result = [];

    foreach ($orders as $order) {
        $order_id = $order->get_id();
        $total = floatval($order->get_total());

        // Get DB paid amount
        $db_paid = floatval($order->get_meta('_aa_total_paid') ?: 0);

        // Get transactions from meta
        $transactions = get_post_meta($order_id, 'aa_order_transactions', true);
        $txn_list = [];
        $stripe_total = 0;

        if (is_array($transactions)) {
            foreach ($transactions as $txn) {
                $amount = floatval($txn['amount'] ?? 0);
                // Field can be: transaction_id, stripe_charge_id, or charge_id
                $stripe_id = $txn['transaction_id'] ?? $txn['stripe_charge_id'] ?? $txn['charge_id'] ?? '';
                $stripe_verified = false;
                $stripe_amount = 0;

                // Verify with Stripe if we have an ID and API key
                if ($stripe_id && $stripe_secret) {
                    $stripe_data = aa_verify_stripe_charge($stripe_secret, $stripe_id);
                    if ($stripe_data && isset($stripe_data['amount'])) {
                        $stripe_amount = $stripe_data['amount'];
                        $stripe_verified = abs($stripe_amount - $amount) < 0.01;
                        if ($stripe_verified) {
                            $stripe_total += $amount;
                        }
                    }
                }

                $txn_list[] = [
                    'type' => $txn['description'] ?? $txn['type'] ?? 'Payment',
                    'amount' => number_format($amount, 2),
                    'stripe_id' => $stripe_id,
                    'stripe_amount' => number_format($stripe_amount, 2),
                    'stripe_verified' => $stripe_verified,
                    'time' => isset($txn['time']) ? date('Y-m-d H:i', $txn['time']) : '',
                ];
            }
        }

        // Determine alignment status
        $stripe_match = abs($db_paid - $stripe_total) < 0.01;
        $alignment_status = $stripe_match ? 'aligned' : 'misaligned';

        $result[] = [
            'id' => $order_id,
            'status' => $order->get_status(),
            'total' => number_format($total, 2),
            'db_paid' => number_format($db_paid, 2),
            'stripe_paid' => number_format($stripe_total, 2),
            'stripe_match' => $stripe_match,
            'alignment_status' => $alignment_status,
            'transactions' => $txn_list,
        ];
    }

    wp_send_json_success($result);
}

// Helper function to verify Stripe charge - returns array with amount and status
function aa_verify_stripe_charge($api_key, $charge_id) {
    // Use transient cache to avoid excessive API calls
    $cache_key = 'aa_stripe_data_' . md5($charge_id);
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    // Call Stripe API for charges (ch_xxx)
    $response = wp_remote_get(
        "https://api.stripe.com/v1/charges/{$charge_id}",
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'timeout' => 10,
        ]
    );

    if (is_wp_error($response)) {
        return null;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body) && !isset($body['error']) && isset($body['amount'])) {
        $result = [
            'amount' => ($body['amount'] ?? 0) / 100,
            'currency' => strtoupper($body['currency'] ?? 'GBP'),
            'status' => $body['status'] ?? 'unknown',
            'paid' => $body['paid'] ?? false,
        ];
        set_transient($cache_key, $result, HOUR_IN_SECONDS);
        return $result;
    }

    // Try PaymentIntent if charge ID doesn't work (pi_xxx)
    if (strpos($charge_id, 'pi_') === 0) {
        $response = wp_remote_get(
            "https://api.stripe.com/v1/payment_intents/{$charge_id}",
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                ],
                'timeout' => 10,
            ]
        );

        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($body['amount_received'])) {
                $result = [
                    'amount' => $body['amount_received'] / 100,
                    'currency' => strtoupper($body['currency'] ?? 'GBP'),
                    'status' => $body['status'] ?? 'unknown',
                    'paid' => $body['status'] === 'succeeded',
                ];
                set_transient($cache_key, $result, HOUR_IN_SECONDS);
                return $result;
            }
        }
    }

    return null;
}

// ============================================
// AJAX HANDLER: Reset Test Data (Delete orders for fenous@gmail.com)
// ============================================

add_action('wp_ajax_aa_reset_test_data', 'aa_reset_test_data_handler');
function aa_reset_test_data_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aa_reset_test_data')) {
        wp_send_json_error('Invalid nonce');
        return;
    }

    $test_email = 'fenous@gmail.com';

    // Must be logged in as admin OR as the test user
    $current_user = wp_get_current_user();
    if (!current_user_can('manage_options') && $current_user->user_email !== $test_email) {
        wp_send_json_error('Unauthorized');
        return;
    }
    $orders_deleted = 0;
    $stripe_cancelled = 0;
    $errors = [];

    // Get Stripe API key
    $stripe_settings = get_option('woocommerce_stripe_settings', []);
    $stripe_secret = $stripe_settings['testmode'] === 'yes'
        ? ($stripe_settings['test_secret_key'] ?? '')
        : ($stripe_settings['secret_key'] ?? '');

    // Get all orders for this email
    $orders = wc_get_orders([
        'billing_email' => $test_email,
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    foreach ($orders as $order) {
        $order_id = $order->get_id();

        // Get Stripe charge IDs from transactions
        $transactions = get_post_meta($order_id, 'aa_order_transactions', true);
        if (is_array($transactions)) {
            foreach ($transactions as $txn) {
                $stripe_id = $txn['transaction_id'] ?? $txn['stripe_charge_id'] ?? $txn['charge_id'] ?? '';

                // Refund the charge in Stripe if we have an ID
                if ($stripe_id && $stripe_secret) {
                    $refund_result = aa_refund_stripe_charge($stripe_secret, $stripe_id);
                    if ($refund_result === true) {
                        $stripe_cancelled++;
                    } elseif ($refund_result !== null) {
                        $errors[] = "Order #{$order_id}: {$refund_result}";
                    }
                }
            }
        }

        // Also check for direct Stripe meta
        $stripe_charge = $order->get_meta('_stripe_charge_id');
        $stripe_intent = $order->get_meta('_stripe_intent_id');

        if ($stripe_charge && $stripe_secret) {
            $refund_result = aa_refund_stripe_charge($stripe_secret, $stripe_charge);
            if ($refund_result === true) {
                $stripe_cancelled++;
            } elseif ($refund_result !== null) {
                $errors[] = "Order #{$order_id} (charge): {$refund_result}";
            }
        }

        if ($stripe_intent && $stripe_secret && !$stripe_charge) {
            // Cancel PaymentIntent if not yet charged
            $cancel_result = aa_cancel_stripe_payment_intent($stripe_secret, $stripe_intent);
            if ($cancel_result === true) {
                $stripe_cancelled++;
            }
        }

        // Delete the order
        $order->delete(true); // true = force delete (bypass trash)
        $orders_deleted++;
    }

    // Clear any related transients
    delete_transient('aa_stripe_data_*');

    wp_send_json_success([
        'orders_deleted' => $orders_deleted,
        'stripe_cancelled' => $stripe_cancelled,
        'errors' => $errors,
    ]);
}

// Helper function to refund a Stripe charge
function aa_refund_stripe_charge($api_key, $charge_id) {
    // Skip if not a charge ID
    if (strpos($charge_id, 'ch_') !== 0 && strpos($charge_id, 'pi_') !== 0) {
        return null;
    }

    // For PaymentIntent, get the charge first
    if (strpos($charge_id, 'pi_') === 0) {
        $response = wp_remote_get(
            "https://api.stripe.com/v1/payment_intents/{$charge_id}",
            [
                'headers' => ['Authorization' => 'Bearer ' . $api_key],
                'timeout' => 10,
            ]
        );

        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($body['latest_charge'])) {
                $charge_id = $body['latest_charge'];
            } else {
                return null; // No charge to refund
            }
        }
    }

    // Create refund
    $response = wp_remote_post(
        "https://api.stripe.com/v1/refunds",
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => ['charge' => $charge_id],
            'timeout' => 10,
        ]
    );

    if (is_wp_error($response)) {
        return $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body['error'])) {
        // Already refunded is OK
        if (strpos($body['error']['message'] ?? '', 'already been refunded') !== false) {
            return true;
        }
        return $body['error']['message'] ?? 'Unknown Stripe error';
    }

    return true;
}

// Helper function to cancel a PaymentIntent
function aa_cancel_stripe_payment_intent($api_key, $intent_id) {
    if (strpos($intent_id, 'pi_') !== 0) {
        return null;
    }

    $response = wp_remote_post(
        "https://api.stripe.com/v1/payment_intents/{$intent_id}/cancel",
        [
            'headers' => ['Authorization' => 'Bearer ' . $api_key],
            'timeout' => 10,
        ]
    );

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return !isset($body['error']);
}

// ============================================
// AJAX HANDLER: Clear WooCommerce Session
// ============================================

add_action('wp_ajax_aa_clear_session', 'aa_clear_session_handler');
add_action('wp_ajax_nopriv_aa_clear_session', 'aa_clear_session_handler');
function aa_clear_session_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aa_clear_session')) {
        wp_send_json_error('Invalid nonce');
        return;
    }

    // Clear WooCommerce session
    if (WC()->session) {
        WC()->session->destroy_session();
    }

    // Clear cart
    if (WC()->cart) {
        WC()->cart->empty_cart();
    }

    // Clear AA session
    if (class_exists('AA_Session')) {
        AA_Session::clear();
    }

    wp_send_json_success('Session cleared');
}

// ============================================
// PART 0.5: FORCE STRIPE SCRIPTS ON ORDER-PAY
// ============================================

/**
 * CRITICAL FIX: Allow "processing" orders to accept payment on order-pay page.
 *
 * Root cause: WooCommerce's needs_payment() checks:
 *   1. has_status(['pending', 'failed']) - "processing" is NOT in this list!
 *   2. get_total() > 0
 *
 * Since "processing" is not a valid payment status, needs_payment() returns FALSE
 * and the payment form never renders. This filter adds "processing" to valid statuses
 * when there's a balance due.
 */
add_filter('woocommerce_valid_order_statuses_for_payment', function($statuses, $order) {
    // Only modify on order-pay page
    if (!is_wc_endpoint_url('order-pay')) {
        return $statuses;
    }

    // Check if order has balance due
    $total = floatval($order->get_total());
    $paid = floatval($order->get_meta('_aa_total_paid'));

    // If no _aa_total_paid meta and order is "processing", assume partial payment system
    if ($paid == 0 && $order->get_status() === 'processing') {
        // Check transactions for paid amount
        $transactions = get_post_meta($order->get_id(), 'aa_order_transactions', true);
        if (is_array($transactions)) {
            foreach ($transactions as $txn) {
                if (!empty($txn['amount']) && (!empty($txn['status']) && $txn['status'] === 'completed')) {
                    $paid += floatval($txn['amount']);
                }
            }
        }
    }

    // If balance is due, allow "processing" status to accept payment
    if ($paid < $total || ($paid == 0 && $order->get_status() === 'processing')) {
        if (!in_array('processing', $statuses)) {
            $statuses[] = 'processing';
            error_log("[AA Fix] Order #{$order->get_id()}: Added 'processing' to valid payment statuses (total={$total}, paid={$paid})");
        }
    }

    return $statuses;
}, 5, 2);

/**
 * CRITICAL FIX: Override needs_payment() result for partial-paid orders.
 *
 * Even with the status fix above, needs_payment() might still return false
 * due to other WooCommerce logic. This filter ensures it returns true
 * when there's a balance due.
 */
add_filter('woocommerce_order_needs_payment', function($needs_payment, $order) {
    // Only modify on order-pay page
    if (!is_wc_endpoint_url('order-pay')) {
        return $needs_payment;
    }

    // If already needs payment, don't change
    if ($needs_payment) {
        return true;
    }

    // Check if order has balance due
    $total = floatval($order->get_total());
    $paid = floatval($order->get_meta('_aa_total_paid'));

    // Check transactions if no direct meta
    if ($paid == 0) {
        $transactions = get_post_meta($order->get_id(), 'aa_order_transactions', true);
        if (is_array($transactions)) {
            foreach ($transactions as $txn) {
                if (!empty($txn['amount']) && (!empty($txn['status']) && $txn['status'] === 'completed')) {
                    $paid += floatval($txn['amount']);
                }
            }
        }
    }

    // If balance is due, needs payment
    if ($total > 0 && $paid < $total) {
        error_log("[AA Fix] Order #{$order->get_id()}: needs_payment override - total={$total}, paid={$paid}");
        return true;
    }

    // Special case: "processing" order with no paid tracking = needs payment
    if ($order->get_status() === 'processing' && $paid == 0 && $total > 0) {
        error_log("[AA Fix] Order #{$order->get_id()}: needs_payment override for processing order with no tracking");
        return true;
    }

    return $needs_payment;
}, 5, 2);

/**
 * Fix: Order with deposit paid has is_paid()=true, blocking Stripe scripts.
 * Force WooCommerce to NOT consider partial-paid orders as "paid".
 *
 * Root cause: Order status "processing" makes is_paid() return true,
 * but AA custom checkout allows partial payments, so we need to check actual amounts.
 */
add_filter('woocommerce_order_is_paid', function($is_paid, $order) {
    // Only modify for order-pay page
    if (!is_wc_endpoint_url('order-pay')) {
        return $is_paid;
    }

    // If WooCommerce already thinks it's not paid, don't change
    if (!$is_paid) {
        return false;
    }

    // Get order total
    $total = floatval($order->get_total());
    if ($total <= 0) {
        return $is_paid;
    }

    // Check _aa_total_paid meta
    $paid = floatval($order->get_meta('_aa_total_paid'));

    // If paid amount is less than total, NOT fully paid
    if ($paid < $total) {
        error_log("[AA Fix] Order #{$order->get_id()}: is_paid override - total={$total}, paid={$paid}");
        return false;
    }

    // Also check by looking at order transactions
    $transactions = get_post_meta($order->get_id(), 'aa_order_transactions', true);
    if (is_array($transactions) && !empty($transactions)) {
        $total_paid_via_txn = 0;
        foreach ($transactions as $txn) {
            if (!empty($txn['amount']) && (!empty($txn['status']) && $txn['status'] === 'completed')) {
                $total_paid_via_txn += floatval($txn['amount']);
            }
        }
        if ($total_paid_via_txn < $total) {
            error_log("[AA Fix] Order #{$order->get_id()}: is_paid override via txn - total={$total}, paid_txn={$total_paid_via_txn}");
            return false;
        }
    }

    // Check WooCommerce's own payment records
    $order_paid_total = floatval($order->get_meta('_order_total_paid'));
    if ($order_paid_total > 0 && $order_paid_total < $total) {
        return false;
    }

    return $is_paid;
}, 5, 2); // Priority 5 - run early

/**
 * Force Stripe/UPE scripts to load on order-pay page
 * This runs early to ensure scripts are enqueued properly
 */
add_action('wp', function() {
    if (!is_wc_endpoint_url('order-pay')) {
        return;
    }

    global $wp;
    $order_id = isset($wp->query_vars['order-pay']) ? absint($wp->query_vars['order-pay']) : 0;
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    // Always try to load Stripe scripts on order-pay page with balance
    $total = floatval($order->get_total());
    $paid = floatval($order->get_meta('_aa_total_paid'));

    // If no balance tracking, assume needs payment
    if ($paid == 0 && $order->get_status() !== 'completed') {
        $has_balance = true;
    } else {
        $has_balance = ($total - $paid) > 0;
    }

    if (!$has_balance) return;

    error_log("[AA Fix] Order #{$order_id}: Forcing Stripe scripts on order-pay");

    // Force enqueue Stripe scripts
    add_action('wp_enqueue_scripts', function() use ($order_id) {
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();

        // Temporarily make is_paid return false
        add_filter('woocommerce_order_is_paid', '__return_false', 999);

        // Try UPE gateway first (newer)
        if (isset($gateways['stripe']) && is_a($gateways['stripe'], 'WC_Stripe_UPE_Payment_Gateway')) {
            error_log("[AA Fix] Using UPE gateway for order #{$order_id}");
            if (method_exists($gateways['stripe'], 'payment_scripts')) {
                $gateways['stripe']->payment_scripts();
            }
        }
        // Try legacy gateway
        elseif (isset($gateways['stripe'])) {
            error_log("[AA Fix] Using legacy Stripe gateway for order #{$order_id}");
            if (method_exists($gateways['stripe'], 'payment_scripts')) {
                $gateways['stripe']->payment_scripts();
            }
        }

        remove_filter('woocommerce_order_is_paid', '__return_false', 999);
    }, 5); // Priority 5 - run early

}, 5); // Priority 5

/**
 * Additional hook: Ensure Stripe scripts load via wp_enqueue_scripts
 */
add_action('wp_enqueue_scripts', function() {
    if (!is_wc_endpoint_url('order-pay')) {
        return;
    }

    // Check if Stripe params are already set
    if (wp_script_is('wc-stripe-upe-classic', 'enqueued') || wp_script_is('woocommerce_stripe', 'enqueued')) {
        return; // Already loaded
    }

    global $wp;
    $order_id = isset($wp->query_vars['order-pay']) ? absint($wp->query_vars['order-pay']) : 0;
    if (!$order_id) return;

    error_log("[AA Fix] Scripts not loaded, forcing manual enqueue for order #{$order_id}");

    // Force is_paid to return false temporarily
    add_filter('woocommerce_order_is_paid', '__return_false', 999);

    $gateways = WC()->payment_gateways()->get_available_payment_gateways();
    if (isset($gateways['stripe']) && method_exists($gateways['stripe'], 'payment_scripts')) {
        $gateways['stripe']->payment_scripts();
    }

    remove_filter('woocommerce_order_is_paid', '__return_false', 999);
}, 15);

/**
 * CRITICAL FIX: Override wc_stripe_upe_params.isOrderPay via JavaScript
 *
 * The Stripe UPE plugin sets isOrderPay based on conditions that may have
 * been false when originally evaluated (because needs_payment() was false).
 * This script runs after Stripe params are localized and corrects the value.
 */
add_action('wp_footer', function() {
    if (!is_wc_endpoint_url('order-pay')) return;

    global $wp;
    $order_id = isset($wp->query_vars['order-pay']) ? absint($wp->query_vars['order-pay']) : 0;
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    // Get the order key for payment
    $order_key = $order->get_order_key();
    ?>
    <script type="text/javascript" id="aa-fix-stripe-order-pay-params">
    (function() {
        'use strict';

        // Fix wc_stripe_upe_params for order-pay
        function fixStripeOrderPayParams() {
            if (typeof wc_stripe_upe_params !== 'undefined') {
                var orderId = <?php echo json_encode($order_id); ?>;
                var orderKey = <?php echo json_encode($order_key); ?>;
                var cartTotal = <?php echo json_encode(intval(floatval($order->get_total()) * 100)); ?>; // In cents

                console.log('[AA Fix] Fixing wc_stripe_upe_params for order-pay');
                console.log('[AA Fix] Before - isOrderPay:', wc_stripe_upe_params.isOrderPay);

                // Force correct order-pay values
                wc_stripe_upe_params.isOrderPay = true;
                wc_stripe_upe_params.orderId = orderId;
                wc_stripe_upe_params.orderReturnURL = '<?php echo esc_url($order->get_checkout_order_received_url()); ?>';

                // Ensure payment is needed
                if (!wc_stripe_upe_params.isPaymentNeeded || wc_stripe_upe_params.isPaymentNeeded === '0') {
                    wc_stripe_upe_params.isPaymentNeeded = '1';
                }

                // Ensure cart total is set
                if (!wc_stripe_upe_params.cartTotal || wc_stripe_upe_params.cartTotal === '0') {
                    wc_stripe_upe_params.cartTotal = cartTotal.toString();
                }

                console.log('[AA Fix] After - isOrderPay:', wc_stripe_upe_params.isOrderPay);
                console.log('[AA Fix] After - orderId:', wc_stripe_upe_params.orderId);
                console.log('[AA Fix] After - cartTotal:', wc_stripe_upe_params.cartTotal);

                // Trigger re-initialization of Stripe UPE if needed
                if (window.wc_stripe_upe && typeof window.wc_stripe_upe.init === 'function') {
                    console.log('[AA Fix] Re-initializing Stripe UPE');
                    try {
                        window.wc_stripe_upe.init();
                    } catch(e) {
                        console.log('[AA Fix] UPE re-init error (may be normal):', e.message);
                    }
                }

                return true;
            }
            return false;
        }

        // Try immediately
        if (!fixStripeOrderPayParams()) {
            // Try after a short delay (scripts may still be loading)
            setTimeout(fixStripeOrderPayParams, 100);
            setTimeout(fixStripeOrderPayParams, 500);
            setTimeout(fixStripeOrderPayParams, 1000);
        }

        // Also fix on DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(fixStripeOrderPayParams, 100);
        });
    })();
    </script>
    <?php
}, 50); // Priority 50 - run after most scripts

// ============================================
// PART 1: CSS FIX FOR 3DS MODAL
// ============================================

/**
 * Add CSS to fix Stripe 3DS modal positioning
 */
add_action('wp_head', function() {
    if (!is_checkout() && !is_wc_endpoint_url('order-pay')) return;
    ?>
    <style id="aa-stripe-3ds-fix">
    /* Force Stripe 3DS iframe to display as modal overlay */
    iframe[name^="__privateStripeFrame"][src*="3d-secure"],
    iframe[name^="__privateStripeFrame"][title*="3D Secure"],
    iframe[src*="stripe.com/three-d-secure"],
    iframe[src*="js.stripe.com/v3/controller"],
    body > iframe[name^="__privateStripeFrame"] {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 2147483647 !important;
        border: none !important;
        background: rgba(0,0,0,0.6) !important;
    }

    /* Stripe challenge frame container */
    div[class*="__PrivateStripeElement"] iframe[src*="3d-secure"],
    div[class*="__PrivateStripeElement"] iframe[title*="challenge"] {
        position: fixed !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        width: 500px !important;
        max-width: 95vw !important;
        height: 600px !important;
        max-height: 90vh !important;
        z-index: 2147483647 !important;
        border-radius: 8px !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
    }

    /* Backdrop for 3DS modal */
    .stripe-3ds-backdrop {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        background: rgba(0, 0, 0, 0.6) !important;
        z-index: 2147483646 !important;
    }

    /* Ensure body doesn't clip the modal */
    body.stripe-3ds-active {
        overflow: visible !important;
    }

    body.stripe-3ds-active * {
        overflow: visible !important;
    }
    </style>
    <?php
}, 99);

// ============================================
// PART 2: 3DS AUTHENTICATION FIX
// ============================================

/**
 * Save payment option to order meta when order is created
 */
add_action('woocommerce_checkout_order_created', function($order) {
    $payment_option = $_POST['payment_option'] ?? '';
    $custom_amount = $_POST['custom_payment_amount'] ?? '';

    if ($payment_option) {
        update_post_meta($order->get_id(), '_aa_payment_option', sanitize_text_field($payment_option));
    }
    if ($custom_amount) {
        update_post_meta($order->get_id(), '_aa_custom_payment_amount', floatval($custom_amount));
    }
}, 5);

/**
 * CRITICAL: Set payment_option VERY EARLY in the request lifecycle.
 * This must run BEFORE the checkout.php adds its filter at 'wp' priority 10.
 * checkout.php line 232-240 adds maybe_modify_order_total_before_payment_processing
 * on 'wp' action at priority 10, which checks $_POST['payment_option'].
 */
add_action('wp', function() {
    // Only for order-pay POST submissions
    if (!isset($_POST['woocommerce_pay']) || !isset($_GET['key'])) {
        return;
    }

    // Auto-set payment_option to pay_full if not provided
    if (empty($_POST['payment_option'])) {
        $_POST['payment_option'] = 'pay_full';
        $_REQUEST['payment_option'] = 'pay_full';
        error_log("[AA Fix] Set payment_option=pay_full on wp action (priority 5)");
    }
}, 5); // Priority 5 - BEFORE checkout.php's priority 10

/**
 * For additional payments (pay page), save to order meta
 * Also auto-set payment_option if missing
 */
add_action('woocommerce_before_pay_action', function($order) {
    // Auto-set payment_option to pay_full if not provided (backup)
    if (empty($_POST['payment_option'])) {
        $_POST['payment_option'] = 'pay_full';
        $_REQUEST['payment_option'] = 'pay_full';
        error_log("[AA Fix] Set payment_option=pay_full in woocommerce_before_pay_action");
    }

    $payment_option = $_POST['payment_option'] ?? '';
    $custom_amount = $_POST['custom_payment_amount'] ?? '';

    error_log("[AA Fix] Order #{$order->get_id()} payment_option={$payment_option}");

    if ($payment_option) {
        update_post_meta($order->get_id(), '_aa_payment_option', sanitize_text_field($payment_option));
    }
    if ($custom_amount) {
        update_post_meta($order->get_id(), '_aa_custom_payment_amount', floatval($custom_amount));
    }

    // Store for 3DS callback
    if (WC()->session) {
        WC()->session->set('aa_order_pay_id', $order->get_id());
        WC()->session->set('aa_order_pay_option', $payment_option);
        WC()->session->set('aa_order_pay_amount', $custom_amount);
    }
}, 1); // Priority 1 - run BEFORE validation

/**
 * Restore payment option from order meta during 3DS callback
 */
add_action('init', function() {
    if (!wp_doing_ajax()) return;

    $action = $_REQUEST['wc-ajax'] ?? $_REQUEST['action'] ?? '';

    $stripe_actions = [
        'wc_stripe_update_order_status',
        'wc_stripe_verify_intent',
        'wc_stripe_create_payment_intent'
    ];

    if (!in_array($action, $stripe_actions)) return;

    $order_id = isset($_REQUEST['order_id']) ? absint($_REQUEST['order_id']) : 0;
    if (!$order_id) {
        $order_id = isset($_REQUEST['order']) ? absint($_REQUEST['order']) : 0;
    }

    // Try to get from session if not in request
    if (!$order_id && WC()->session) {
        $order_id = WC()->session->get('aa_order_pay_id');
    }

    if (!$order_id) return;

    // Restore payment_option
    if (empty($_POST['payment_option'])) {
        $saved_option = get_post_meta($order_id, '_aa_payment_option', true);
        if (!$saved_option && WC()->session) {
            $saved_option = WC()->session->get('aa_order_pay_option');
        }
        if (!$saved_option) {
            $saved_option = 'pay_full'; // Default fallback
        }
        $_POST['payment_option'] = $saved_option;
        $_REQUEST['payment_option'] = $saved_option;
    }

    // Restore custom amount
    if (empty($_POST['custom_payment_amount'])) {
        $saved_amount = get_post_meta($order_id, '_aa_custom_payment_amount', true);
        if (!$saved_amount && WC()->session) {
            $saved_amount = WC()->session->get('aa_order_pay_amount');
        }
        if ($saved_amount) {
            $_POST['custom_payment_amount'] = $saved_amount;
            $_REQUEST['custom_payment_amount'] = $saved_amount;
        }
    }
}, 1);

/**
 * Additional hooks for Stripe filter and action
 */
add_filter('wc_stripe_order_status_update', function($result, $order_id) {
    if (empty($_POST['payment_option'])) {
        $saved_option = get_post_meta($order_id, '_aa_payment_option', true);
        if ($saved_option) {
            $_POST['payment_option'] = $saved_option;
        } else {
            $_POST['payment_option'] = 'pay_full';
        }
    }
    return $result;
}, 1, 2);

add_action('wc_stripe_process_response', function($response, $order) {
    if (empty($_POST['payment_option'])) {
        $saved_option = get_post_meta($order->get_id(), '_aa_payment_option', true);
        if ($saved_option) {
            $_POST['payment_option'] = $saved_option;
        } else {
            $_POST['payment_option'] = 'pay_full';
        }
    }
}, 1, 2);

/**
 * CRITICAL FIX: Restore payment data BEFORE woocommerce_pre_payment_complete runs
 * This handles the 3DS redirect flow where $_POST is empty
 * Priority 0 ensures this runs BEFORE checkout.php's handler at priority 1
 */
add_action('woocommerce_pre_payment_complete', function($order_id, $transaction_id) {
    // If payment_option is already set, no need to restore
    if (!empty($_POST['payment_option'])) {
        error_log("[AA Pre-Payment] payment_option already set: {$_POST['payment_option']}");
        return;
    }

    error_log("[AA Pre-Payment] Restoring payment data for order #{$order_id}");

    // Restore payment_option from order meta
    $saved_option = get_post_meta($order_id, '_aa_payment_option', true);
    if ($saved_option) {
        $_POST['payment_option'] = $saved_option;
        $_REQUEST['payment_option'] = $saved_option;
        error_log("[AA Pre-Payment] Restored payment_option from meta: {$saved_option}");
    } else {
        // Try session
        if (WC()->session) {
            $saved_option = WC()->session->get('aa_order_pay_option');
            if ($saved_option) {
                $_POST['payment_option'] = $saved_option;
                $_REQUEST['payment_option'] = $saved_option;
                error_log("[AA Pre-Payment] Restored payment_option from session: {$saved_option}");
            }
        }
    }

    // Fallback to pay_full if still empty
    if (empty($_POST['payment_option'])) {
        $_POST['payment_option'] = 'pay_full';
        $_REQUEST['payment_option'] = 'pay_full';
        error_log("[AA Pre-Payment] Fallback to pay_full");
    }

    // Restore custom_payment_amount from order meta
    $saved_amount = get_post_meta($order_id, '_aa_custom_payment_amount', true);
    if ($saved_amount) {
        $_POST['custom_payment_amount'] = $saved_amount;
        $_REQUEST['custom_payment_amount'] = $saved_amount;
        error_log("[AA Pre-Payment] Restored custom_payment_amount from meta: {$saved_amount}");
    } else {
        // Try session
        if (WC()->session) {
            $saved_amount = WC()->session->get('aa_order_pay_amount');
            if ($saved_amount) {
                $_POST['custom_payment_amount'] = $saved_amount;
                $_REQUEST['custom_payment_amount'] = $saved_amount;
                error_log("[AA Pre-Payment] Restored custom_payment_amount from session: {$saved_amount}");
            }
        }
    }

}, 0, 2); // Priority 0 - run BEFORE checkout.php's priority 1 handler

// ============================================
// PART 3: CHECKOUT REDIRECT/RECOVERY FIX
// ============================================

/**
 * Track order when processed (works for ALL order types)
 */
add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
    if (WC()->session) {
        WC()->session->set('aa_order_id', $order_id);
        WC()->session->set('aa_order_redirect', $order->get_checkout_order_received_url());
        WC()->session->set('aa_order_time', time());
    }
}, 9999, 3);

/**
 * Clear cart when order is processed
 */
add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
    if (WC()->cart && !WC()->cart->is_empty()) {
        WC()->cart->empty_cart();
    }
}, 10000, 3);

/**
 * Also track payment complete
 */
add_action('woocommerce_payment_complete', function($order_id) {
    $order = wc_get_order($order_id);
    if ($order && WC()->session) {
        WC()->session->set('aa_order_id', $order_id);
        WC()->session->set('aa_order_redirect', $order->get_checkout_order_received_url());
        WC()->session->set('aa_order_time', time());
    }
}, 1);

/**
 * Store order redirect URL for order-pay page
 */
add_action('woocommerce_before_pay_action', function($order) {
    if (WC()->session) {
        WC()->session->set('aa_pay_order_id', $order->get_id());
        WC()->session->set('aa_pay_order_redirect', $order->get_checkout_order_received_url());
        WC()->session->set('aa_pay_order_time', time());
    }
}, 10);

/**
 * Clear cart on order-received page
 */
add_action('template_redirect', function() {
    if (is_wc_endpoint_url('order-received') && WC()->cart && !WC()->cart->is_empty()) {
        WC()->cart->empty_cart();
    }
}, 1);

// ============================================
// PART 4: AJAX RECOVERY HANDLERS
// ============================================

/**
 * AJAX handler for checkout recovery
 */
add_action('wp_ajax_aa_checkout_recover', 'aa_checkout_recover_handler_v5');
add_action('wp_ajax_nopriv_aa_checkout_recover', 'aa_checkout_recover_handler_v5');

function aa_checkout_recover_handler_v5() {
    $order_id = WC()->session ? WC()->session->get('aa_order_id') : null;
    $redirect_url = WC()->session ? WC()->session->get('aa_order_redirect') : null;

    if (!$order_id || !$redirect_url) {
        wp_send_json_error(['message' => 'No pending order in session']);
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(['message' => 'Order not found']);
        return;
    }

    if (!in_array($order->get_status(), ['pending', 'processing', 'completed', 'on-hold'])) {
        wp_send_json_error(['message' => 'Order status not valid: ' . $order->get_status()]);
        return;
    }

    WC()->session->set('aa_order_id', null);
    WC()->session->set('aa_order_redirect', null);
    WC()->session->set('aa_order_time', null);

    if (WC()->cart && !WC()->cart->is_empty()) {
        WC()->cart->empty_cart();
    }

    wp_send_json_success([
        'order_id' => $order_id,
        'redirect_url' => $redirect_url,
        'order_status' => $order->get_status()
    ]);
}

/**
 * AJAX handler for order-pay recovery
 */
add_action('wp_ajax_aa_pay_order_recover', 'aa_pay_order_recover_handler_v5');
add_action('wp_ajax_nopriv_aa_pay_order_recover', 'aa_pay_order_recover_handler_v5');

function aa_pay_order_recover_handler_v5() {
    $order_id = WC()->session ? WC()->session->get('aa_pay_order_id') : null;
    $redirect_url = WC()->session ? WC()->session->get('aa_pay_order_redirect') : null;

    if (!$order_id || !$redirect_url) {
        $order_id = isset($_REQUEST['order_id']) ? absint($_REQUEST['order_id']) : 0;
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $redirect_url = $order->get_checkout_order_received_url();
            }
        }
    }

    if (!$order_id || !$redirect_url) {
        wp_send_json_error(['message' => 'No pending order']);
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(['message' => 'Order not found']);
        return;
    }

    // Check for recent transaction
    $transactions = get_post_meta($order_id, 'aa_order_transactions', true) ?: [];
    $recent_transaction = false;
    foreach ($transactions as $txn) {
        if (!empty($txn['time']) && (time() - $txn['time']) < 300) {
            $recent_transaction = true;
            break;
        }
    }

    $valid_statuses = ['pending', 'processing', 'completed', 'on-hold'];
    if (!in_array($order->get_status(), $valid_statuses) && !$recent_transaction) {
        wp_send_json_error(['message' => 'Order not in valid state']);
        return;
    }

    if (WC()->session) {
        WC()->session->set('aa_pay_order_id', null);
        WC()->session->set('aa_pay_order_redirect', null);
        WC()->session->set('aa_pay_order_time', null);
    }

    wp_send_json_success([
        'order_id' => $order_id,
        'redirect_url' => $redirect_url,
        'order_status' => $order->get_status()
    ]);
}

// ============================================
// PART 5: JAVASCRIPT FOR CHECKOUT PAGE
// ============================================

add_action('wp_footer', function() {
    if (!is_checkout()) return;
    if (is_wc_endpoint_url('order-received')) return;

    // Check for pending redirect
    $order_id = WC()->session ? WC()->session->get('aa_order_id') : null;
    $redirect_url = WC()->session ? WC()->session->get('aa_order_redirect') : null;
    $order_time = WC()->session ? WC()->session->get('aa_order_time') : 0;

    // Auto-redirect if order was just created
    if ($order_id && $redirect_url && $order_time && (time() - $order_time) < 120) {
        $order = wc_get_order($order_id);
        if ($order && in_array($order->get_status(), ['pending', 'processing', 'completed', 'on-hold'])) {
            WC()->session->set('aa_order_id', null);
            WC()->session->set('aa_order_redirect', null);
            WC()->session->set('aa_order_time', null);
            if (WC()->cart && !WC()->cart->is_empty()) {
                WC()->cart->empty_cart();
            }
            ?>
            <script>
            (function() {
                if (typeof aaLog === 'function') aaLog('Order #<?php echo esc_js($order_id); ?> found, redirecting...', 'warn');
                var overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:#fff;z-index:999999;display:flex;align-items:center;justify-content:center;flex-direction:column;';
                overlay.innerHTML = '<h2 style="margin:0 0 10px 0;color:#333;">Order Received!</h2><p style="margin:0;color:#666;">Redirecting to confirmation page...</p>';
                document.body.appendChild(overlay);
                window.location.href = '<?php echo esc_js($redirect_url); ?>';
            })();
            </script>
            <?php
            return;
        }
    }

    // AJAX fallback handler for checkout
    ?>
    <script>
    jQuery(function($) {
        if (typeof aaLog === 'function') aaLog('Checkout handler initialized', 'info');

        // 3DS iframe monitor
        function monitor3DSIframe() {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.tagName === 'IFRAME' && node.name && node.name.indexOf('__privateStripe') === 0) {
                            if (typeof aaLog === 'function') aaLog('Stripe iframe: ' + node.name.substring(0, 25), 'info');
                            $('body').addClass('stripe-3ds-active');

                            if (node.src && (node.src.indexOf('3d-secure') !== -1 || node.src.indexOf('challenge') !== -1)) {
                                if (typeof aaLog === 'function') aaLog('3DS Challenge iframe - forcing modal', 'warn');
                                $(node).css({
                                    'position': 'fixed',
                                    'top': '0',
                                    'left': '0',
                                    'width': '100vw',
                                    'height': '100vh',
                                    'z-index': '2147483647',
                                    'background': 'white'
                                });
                            }
                        }
                    });
                });
            });

            observer.observe(document.body, { childList: true, subtree: true });
        }

        monitor3DSIframe();

        $(document).ajaxComplete(function(event, xhr, settings) {
            if (!settings.url || settings.url.indexOf('wc-ajax=checkout') === -1) return;

            try {
                var response = JSON.parse(xhr.responseText || '{}');
                if (response.result === 'success' && response.redirect) {
                    if (typeof aaLog === 'function') aaLog('Checkout SUCCESS - redirecting', 'warn');
                    window.location.href = response.redirect;
                    return;
                }
            } catch(e) {}

            // Recovery attempt
            setTimeout(function() {
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'aa_checkout_recover'
                }, function(result) {
                    if (result.success && result.data && result.data.redirect_url) {
                        if (typeof aaLog === 'function') aaLog('Recovery SUCCESS - redirecting', 'warn');
                        var overlay = $('<div>').css({position:'fixed',top:0,left:0,right:0,bottom:0,background:'#fff',zIndex:999999,display:'flex',alignItems:'center',justifyContent:'center',flexDirection:'column'})
                            .html('<h2 style="margin:0 0 10px 0;color:#333;">Order Received!</h2><p style="margin:0;color:#666;">Redirecting...</p>');
                        $('body').append(overlay);
                        window.location.href = result.data.redirect_url;
                    }
                });
            }, 1000);
        });

        $(document.body).on('checkout_error', function() {
            $('body').removeClass('stripe-3ds-active');
            if (typeof aaLog === 'function') aaLog('Checkout error - attempting recovery', 'error');
            setTimeout(function() {
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'aa_checkout_recover'
                }, function(result) {
                    if (result.success && result.data && result.data.redirect_url) {
                        if (typeof aaLog === 'function') aaLog('Error recovery SUCCESS', 'warn');
                        var overlay = $('<div>').css({position:'fixed',top:0,left:0,right:0,bottom:0,background:'#fff',zIndex:999999,display:'flex',alignItems:'center',justifyContent:'center',flexDirection:'column'})
                            .html('<h2 style="margin:0 0 10px 0;color:#333;">Order Received!</h2><p style="margin:0;color:#666;">Redirecting...</p>');
                        $('body').append(overlay);
                        window.location.href = result.data.redirect_url;
                    }
                });
            }, 500);
        });
    });
    </script>
    <?php
}, 50);

// ============================================
// PART 6: JAVASCRIPT FOR ORDER-PAY PAGE
// ============================================

add_action('wp_footer', function() {
    if (!is_wc_endpoint_url('order-pay')) return;

    global $wp;
    $order_id = isset($wp->query_vars['order-pay']) ? absint($wp->query_vars['order-pay']) : 0;
    if (!$order_id) return;

    ?>
    <script>
    jQuery(function($) {
        if (typeof aaLog === 'function') aaLog('Order-pay handler for #<?php echo $order_id; ?>', 'info');

        var isSubmitting = false;
        var paymentIntentCreated = false;

        // 3DS iframe monitor and fixer
        function monitor3DSIframe() {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.tagName === 'IFRAME') {
                            var name = node.name || '';
                            var src = node.src || '';

                            if (name.indexOf('__privateStripe') === 0) {
                                $('body').addClass('stripe-3ds-active');

                                if (src.indexOf('3d-secure') !== -1 ||
                                    src.indexOf('challenge') !== -1 ||
                                    src.indexOf('authenticate') !== -1 ||
                                    node.title && node.title.indexOf('3D') !== -1) {

                                    if (typeof aaLog === 'function') aaLog('3DS CHALLENGE detected!', 'warn');

                                    if (!$('.stripe-3ds-backdrop').length) {
                                        $('body').append('<div class="stripe-3ds-backdrop"></div>');
                                    }

                                    node.style.setProperty('position', 'fixed', 'important');
                                    node.style.setProperty('top', '50%', 'important');
                                    node.style.setProperty('left', '50%', 'important');
                                    node.style.setProperty('transform', 'translate(-50%, -50%)', 'important');
                                    node.style.setProperty('width', '500px', 'important');
                                    node.style.setProperty('height', '600px', 'important');
                                    node.style.setProperty('z-index', '2147483647', 'important');
                                    node.style.setProperty('background', 'white', 'important');
                                }
                            }
                        }
                    });
                });
            });

            observer.observe(document.body, { childList: true, subtree: true });
        }

        monitor3DSIframe();

        // Auto-select pay_full if no payment option selected
        var paymentOptions = $('input[name="payment_option"]');
        if (paymentOptions.length > 0 && !paymentOptions.filter(':checked').length) {
            var payFullOption = paymentOptions.filter('[value="pay_full"]');
            if (payFullOption.length) {
                payFullOption.prop('checked', true).trigger('change');
                if (typeof aaLog === 'function') aaLog('Auto-selected pay_full', 'info');
            } else {
                paymentOptions.first().prop('checked', true).trigger('change');
            }
        }

        // Ensure payment_option is in form data
        $('form#order_review').on('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            if (!$(this).find('input[name="payment_option"]').length) {
                $(this).append('<input type="hidden" name="payment_option" value="pay_full">');
                if (typeof aaLog === 'function') aaLog('Added hidden payment_option', 'info');
            } else if (!$(this).find('input[name="payment_option"]:checked').length) {
                $(this).find('input[name="payment_option"][value="pay_full"]').prop('checked', true);
            }

            isSubmitting = true;
        });

        // Monitor Stripe payment intent
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (!settings.url) return;

            var isStripeCall = settings.url.indexOf('wc-ajax=wc_stripe') !== -1 ||
                              settings.url.indexOf('wc_stripe') !== -1;

            if (!isStripeCall) return;

            if (settings.url.indexOf('create_payment_intent') !== -1 ||
                settings.url.indexOf('create_setup_intent') !== -1) {
                paymentIntentCreated = true;
            }

            if (xhr.status >= 400) {
                if (typeof aaLog === 'function') aaLog('Stripe error - recovering', 'error');
                $('body').removeClass('stripe-3ds-active');
                $('.stripe-3ds-backdrop').remove();

                setTimeout(function() {
                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'aa_pay_order_recover',
                        order_id: <?php echo $order_id; ?>
                    }, function(result) {
                        if (result.success && result.data && result.data.redirect_url) {
                            if (typeof aaLog === 'function') aaLog('Recovery SUCCESS', 'warn');
                            var overlay = $('<div>').css({position:'fixed',top:0,left:0,right:0,bottom:0,background:'#fff',zIndex:999999,display:'flex',alignItems:'center',justifyContent:'center',flexDirection:'column'})
                                .html('<h2 style="margin:0 0 10px 0;color:#333;">Payment Received!</h2><p style="margin:0;color:#666;">Redirecting...</p>');
                            $('body').append(overlay);
                            window.location.href = result.data.redirect_url;
                        } else {
                            isSubmitting = false;
                        }
                    });
                }, 1500);
            }
        });

        // Handle checkout errors
        $(document.body).on('checkout_error', function() {
            if (typeof aaLog === 'function') aaLog('Checkout error event', 'error');
            $('body').removeClass('stripe-3ds-active');
            $('.stripe-3ds-backdrop').remove();

            setTimeout(function() {
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'aa_pay_order_recover',
                    order_id: <?php echo $order_id; ?>
                }, function(result) {
                    if (result.success && result.data && result.data.redirect_url) {
                        if (typeof aaLog === 'function') aaLog('Error recovery SUCCESS', 'warn');
                        var overlay = $('<div>').css({position:'fixed',top:0,left:0,right:0,bottom:0,background:'#fff',zIndex:999999,display:'flex',alignItems:'center',justifyContent:'center',flexDirection:'column'})
                            .html('<h2 style="margin:0 0 10px 0;color:#333;">Payment Received!</h2><p style="margin:0;color:#666;">Redirecting...</p>');
                        $('body').append(overlay);
                        window.location.href = result.data.redirect_url;
                    } else {
                        isSubmitting = false;
                    }
                });
            }, 1000);
        });

        // Fallback: Check for 3DS completion
        var check3DSInterval = null;

        function start3DSMonitor() {
            if (check3DSInterval) return;

            check3DSInterval = setInterval(function() {
                if (paymentIntentCreated && !$('.woocommerce-error').length) {
                    $.get('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'aa_pay_order_recover',
                        order_id: <?php echo $order_id; ?>
                    }, function(result) {
                        if (result.success && result.data && result.data.order_status !== 'pending') {
                            if (typeof aaLog === 'function') aaLog('Order status: ' + result.data.order_status, 'warn');
                            clearInterval(check3DSInterval);
                            $('body').removeClass('stripe-3ds-active');
                            $('.stripe-3ds-backdrop').remove();
                            window.location.href = result.data.redirect_url;
                        }
                    });
                }
            }, 3000);
        }

        $('form#order_review').on('submit', function() {
            setTimeout(start3DSMonitor, 2000);
        });
    });
    </script>
    <?php
}, 99);

// ============================================
// PART 7: STRIPE PAYMENT INTENT AMOUNT FIX
// ============================================

/**
 * CRITICAL FIX: Correct payment intent amount using Stripe's official filter.
 *
 * Root Cause: The maybe_modify_order_total_before_payment_processing filter
 * in checkout.php returns £0 when payment_option is empty. This happens
 * during AJAX payment intent creation because $_POST['payment_option']
 * isn't set at that time.
 *
 * Solution: Use wc_stripe_generate_create_intent_request filter to intercept
 * the Stripe request AFTER the amount is calculated but BEFORE it's sent.
 * This is the official, documented approach from WooCommerce Stripe Gateway.
 *
 * @see https://github.com/woocommerce/woocommerce-gateway-stripe/wiki/Action-and-Filter-Hooks
 * @see https://github.com/woocommerce/woocommerce-gateway-stripe/issues/115
 */
add_filter('wc_stripe_generate_create_intent_request', function($request, $order, $prepared_source) {
    // Only process if we have an order
    if (!$order || !is_a($order, 'WC_Order')) {
        return $request;
    }

    $order_id = $order->get_id();

    // Get the REAL order total (bypass any filters by using 'edit' context)
    $raw_total = floatval($order->get_total('edit'));

    // Get amount already paid
    $paid = floatval($order->get_meta('_aa_total_paid') ?: 0);

    // Check transactions for paid amount if meta is empty
    if ($paid == 0) {
        $transactions = get_post_meta($order_id, 'aa_order_transactions', true);
        if (is_array($transactions)) {
            foreach ($transactions as $txn) {
                if (!empty($txn['amount'])) {
                    // Check if transaction is completed
                    $status = $txn['status'] ?? 'completed';
                    if ($status === 'completed' || empty($txn['status'])) {
                        $paid += floatval($txn['amount']);
                    }
                }
            }
        }
    }

    // Calculate balance due
    $balance_due = max(0, $raw_total - $paid);

    // Get current request amount (in cents)
    $current_amount = isset($request['amount']) ? intval($request['amount']) : 0;
    $currency = strtolower($order->get_currency());

    // Calculate what the correct amount should be (in cents)
    $correct_amount = WC_Stripe_Helper::get_stripe_amount($balance_due, $currency);

    // Log for debugging
    error_log("[AA Stripe Fix] Order #{$order_id}: raw_total={$raw_total}, paid={$paid}, balance_due={$balance_due}");
    error_log("[AA Stripe Fix] Order #{$order_id}: current_amount={$current_amount}, correct_amount={$correct_amount}");

    // Fix the amount if it's wrong (0 or different from balance due)
    if ($current_amount == 0 || abs($current_amount - $correct_amount) > 100) { // 100 cents = £1 tolerance
        $request['amount'] = $correct_amount;
        error_log("[AA Stripe Fix] Order #{$order_id}: FIXED amount from {$current_amount} to {$correct_amount} cents");

        // Also update description to reflect correct amount
        if ($balance_due > 0) {
            $formatted_amount = strip_tags(wc_price($balance_due, ['currency' => $order->get_currency()]));
            $request['description'] = sprintf(
                'Payment for Order #%d - %s',
                $order_id,
                $formatted_amount
            );
        }
    }

    return $request;
}, 99999, 3); // Very high priority to run AFTER all other filters

/**
 * BACKUP FIX: Also hook into wc_stripe_generate_payment_request filter
 * This filter is used in some Stripe payment flows (legacy mode)
 */
add_filter('wc_stripe_generate_payment_request', function($request, $order) {
    // Only process if we have an order
    if (!$order || !is_a($order, 'WC_Order')) {
        return $request;
    }

    $order_id = $order->get_id();

    // Get the REAL order total
    $raw_total = floatval($order->get_total('edit'));

    // Get amount already paid
    $paid = floatval($order->get_meta('_aa_total_paid') ?: 0);

    // Check transactions if meta is empty
    if ($paid == 0) {
        $transactions = get_post_meta($order_id, 'aa_order_transactions', true);
        if (is_array($transactions)) {
            foreach ($transactions as $txn) {
                if (!empty($txn['amount'])) {
                    $status = $txn['status'] ?? 'completed';
                    if ($status === 'completed' || empty($txn['status'])) {
                        $paid += floatval($txn['amount']);
                    }
                }
            }
        }
    }

    // Calculate balance due
    $balance_due = max(0, $raw_total - $paid);

    // Get current request amount (in cents)
    $current_amount = isset($request['amount']) ? intval($request['amount']) : 0;
    $currency = strtolower($order->get_currency());

    // Calculate correct amount in cents
    $correct_amount = WC_Stripe_Helper::get_stripe_amount($balance_due, $currency);

    // Fix if wrong
    if ($current_amount == 0 && $balance_due > 0) {
        $request['amount'] = $correct_amount;
        error_log("[AA Stripe Fix - Legacy] Order #{$order_id}: FIXED amount to {$correct_amount} cents");
    }

    return $request;
}, 99999, 2);

/**
 * ADDITIONAL FIX: Hook into payment intent update as well
 * Some flows update the payment intent after initial creation
 */
add_filter('wc_stripe_generate_payment_request_args', function($args, $order) {
    if (!$order || !is_a($order, 'WC_Order')) {
        return $args;
    }

    $order_id = $order->get_id();
    $raw_total = floatval($order->get_total('edit'));
    $paid = floatval($order->get_meta('_aa_total_paid') ?: 0);

    if ($paid == 0) {
        $transactions = get_post_meta($order_id, 'aa_order_transactions', true);
        if (is_array($transactions)) {
            foreach ($transactions as $txn) {
                if (!empty($txn['amount']) && ($txn['status'] ?? 'completed') === 'completed') {
                    $paid += floatval($txn['amount']);
                }
            }
        }
    }

    $balance_due = max(0, $raw_total - $paid);

    if (isset($args['amount']) && $args['amount'] == 0 && $balance_due > 0) {
        $currency = strtolower($order->get_currency());
        $args['amount'] = WC_Stripe_Helper::get_stripe_amount($balance_due, $currency);
        error_log("[AA Stripe Fix - Args] Order #{$order_id}: FIXED amount to {$args['amount']} cents");
    }

    return $args;
}, 99999, 2);

/**
 * EARLY FIX: Set payment_option BEFORE any Stripe AJAX processing
 * This ensures the checkout.php filter doesn't return 0
 */
add_action('wp_loaded', function() {
    // Check if this is a Stripe AJAX request
    $is_stripe_ajax = false;
    $action = '';

    if (defined('DOING_AJAX') && DOING_AJAX) {
        $action = $_REQUEST['action'] ?? $_REQUEST['wc-ajax'] ?? '';
        $stripe_actions = [
            'wc_stripe_create_payment_intent',
            'wc_stripe_update_payment_intent',
            'wc_stripe_create_setup_intent',
            'wc_stripe_verify_intent',
            'wc_stripe_update_order_status'
        ];
        $is_stripe_ajax = in_array($action, $stripe_actions);
    }

    if (!$is_stripe_ajax) {
        return;
    }

    // Set payment_option if not set
    if (empty($_POST['payment_option'])) {
        $_POST['payment_option'] = 'pay_full';
        $_REQUEST['payment_option'] = 'pay_full';
        error_log("[AA Stripe Fix] Set payment_option=pay_full during AJAX action: {$action}");
    }

    // Get order ID from various sources
    $order_id = 0;
    if (!empty($_POST['order_id'])) {
        $order_id = absint($_POST['order_id']);
    } elseif (!empty($_REQUEST['order'])) {
        $order_id = absint($_REQUEST['order']);
    } elseif (!empty($_POST['wc-stripe-payment-order'])) {
        $order_id = absint($_POST['wc-stripe-payment-order']);
    }

    // Also try to get from referer URL
    if (!$order_id && !empty($_SERVER['HTTP_REFERER'])) {
        if (preg_match('/order-pay\/(\d+)/', $_SERVER['HTTP_REFERER'], $matches)) {
            $order_id = absint($matches[1]);
        }
    }

    if ($order_id) {
        // Save payment option to order meta for later retrieval
        update_post_meta($order_id, '_aa_payment_option', 'pay_full');

        // Also store in session
        if (function_exists('WC') && WC()->session) {
            WC()->session->set('aa_stripe_order_id', $order_id);
            WC()->session->set('aa_stripe_payment_option', 'pay_full');
        }

        error_log("[AA Stripe Fix] Saved payment_option for order #{$order_id}");
    }
}, 1); // Priority 1 - very early

// ============================================
// PART 8: FORCE NEW PAYMENT INTENT FOR ORDER-PAY
// ============================================

/**
 * CRITICAL FIX: Clear old payment intent ID on order-pay page
 *
 * When a deposit has been paid, the order has _stripe_intent_id set.
 * The Stripe plugin tries to reuse this intent, but it's already "succeeded".
 * This causes the payment to hang because you can't reuse a completed intent.
 *
 * Solution: Clear the old intent ID on the order-pay page so Stripe creates a new one.
 */
add_action('template_redirect', function() {
    // Only on order-pay page
    if (!is_wc_endpoint_url('order-pay')) {
        return;
    }

    // Get order ID from URL
    global $wp;
    $order_id = absint($wp->query_vars['order-pay'] ?? 0);

    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    // Check if order has an existing payment intent
    $existing_intent_id = $order->get_meta('_stripe_intent_id');

    if (empty($existing_intent_id)) {
        return;
    }

    // Check if this order has partial payment (deposit paid, balance remaining)
    $total = floatval($order->get_total('edit'));
    $paid = floatval($order->get_meta('_aa_total_paid') ?: 0);

    // If nothing paid yet, don't interfere
    if ($paid <= 0) {
        return;
    }

    $balance_due = $total - $paid;

    // If there's still balance to pay, we need a NEW payment intent
    if ($balance_due > 0) {
        global $wpdb;

        // Store the old intent ID for reference
        $order->update_meta_data('_stripe_intent_id_deposit', $existing_intent_id);

        // Clear the current intent ID so Stripe creates a new one
        $order->delete_meta_data('_stripe_intent_id');

        // Also clear related Stripe meta that might interfere
        $order->delete_meta_data('_stripe_source_id');
        $order->delete_meta_data('_stripe_setup_intent');

        $order->save();

        // HPOS FIX: Delete directly from HPOS table (wc_orders_meta)
        $deleted_hpos = $wpdb->delete(
            $wpdb->prefix . 'wc_orders_meta',
            ['order_id' => $order_id, 'meta_key' => '_stripe_intent_id'],
            ['%d', '%s']
        );

        // Also delete from legacy postmeta (for sync mode)
        $deleted_legacy = $wpdb->delete(
            $wpdb->postmeta,
            ['post_id' => $order_id, 'meta_key' => '_stripe_intent_id'],
            ['%d', '%s']
        );

        // Clear all caches for this order
        wp_cache_delete($order_id, 'post_meta');
        wp_cache_delete('order-' . $order_id, 'orders');
        clean_post_cache($order_id);

        error_log("[AA Stripe Fix] Cleared old payment intent for order #{$order_id}. Old intent: {$existing_intent_id}, Balance due: {$balance_due}, HPOS deleted: {$deleted_hpos}, Legacy deleted: {$deleted_legacy}");
    }
}, 5); // Priority 5 - early, before Stripe loads

/**
 * ADDITIONAL FIX: Clear intent ID during Stripe AJAX calls too
 */
add_action('wp_ajax_wc_stripe_create_payment_intent', function() {
    // Get order ID from request
    $order_id = 0;

    if (!empty($_POST['order_id'])) {
        $order_id = absint($_POST['order_id']);
    } elseif (!empty($_REQUEST['order'])) {
        $order_id = absint($_REQUEST['order']);
    }

    // Try referer URL
    if (!$order_id && !empty($_SERVER['HTTP_REFERER'])) {
        if (preg_match('/order-pay\/(\d+)/', $_SERVER['HTTP_REFERER'], $matches)) {
            $order_id = absint($matches[1]);
        }
    }

    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    // Check if there's a partial payment situation
    $paid = floatval($order->get_meta('_aa_total_paid') ?: 0);
    $total = floatval($order->get_total('edit'));

    if ($paid > 0 && $paid < $total) {
        // Clear old intent to force new creation
        $existing_intent = $order->get_meta('_stripe_intent_id');
        if ($existing_intent) {
            $order->update_meta_data('_stripe_intent_id_deposit', $existing_intent);
            $order->delete_meta_data('_stripe_intent_id');
            $order->save();

            error_log("[AA Stripe Fix - AJAX] Cleared intent for new payment. Order #{$order_id}");
        }
    }
}, 1); // Priority 1 - before Stripe's handler

add_action('wp_ajax_nopriv_wc_stripe_create_payment_intent', function() {
    // Same logic for non-logged-in users
    $order_id = 0;

    if (!empty($_POST['order_id'])) {
        $order_id = absint($_POST['order_id']);
    } elseif (!empty($_REQUEST['order'])) {
        $order_id = absint($_REQUEST['order']);
    }

    if (!$order_id && !empty($_SERVER['HTTP_REFERER'])) {
        if (preg_match('/order-pay\/(\d+)/', $_SERVER['HTTP_REFERER'], $matches)) {
            $order_id = absint($matches[1]);
        }
    }

    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $paid = floatval($order->get_meta('_aa_total_paid') ?: 0);
    $total = floatval($order->get_total('edit'));

    if ($paid > 0 && $paid < $total) {
        $existing_intent = $order->get_meta('_stripe_intent_id');
        if ($existing_intent) {
            $order->update_meta_data('_stripe_intent_id_deposit', $existing_intent);
            $order->delete_meta_data('_stripe_intent_id');
            $order->save();

            error_log("[AA Stripe Fix - AJAX nopriv] Cleared intent for order #{$order_id}");
        }
    }
}, 1);

// =============================================================================
// PART 9: DISABLE UPE FOR ORDER-PAY PAGES WITH PARTIAL PAYMENTS
// =============================================================================
// Problem: Stripe UPE (Updated Payment Element) has a known bug with order-pay
// pages where payment_method comes through empty. The Stripe Element JavaScript
// doesn't properly collect card data on order-pay pages.
// Solution: Force legacy card element for ALL order-pay pages.
// Using URL-based detection since is_wc_endpoint_url() runs too late.
// =============================================================================

// Helper function to detect order-pay page from URL (works very early)
if (!function_exists('aa_is_order_pay_page_from_url')) {
    function aa_is_order_pay_page_from_url() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        // Match /checkout/order-pay/XXXX/ pattern
        return (bool) preg_match('/\/checkout\/order-pay\/(\d+)/', $request_uri);
    }
}

// Helper function to get order ID from URL
if (!function_exists('aa_get_order_id_from_url')) {
    function aa_get_order_id_from_url() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('/\/checkout\/order-pay\/(\d+)/', $request_uri, $matches)) {
            return absint($matches[1]);
        }
        return 0;
    }
}

// Method 0: AGGRESSIVE - Disable UPE for ALL order-pay pages using URL detection
// This runs BEFORE WordPress parses the query
add_filter('option_woocommerce_stripe_settings', function($settings) {
    // Check URL directly - works even before WordPress knows it's order-pay
    if (!aa_is_order_pay_page_from_url()) {
        return $settings;
    }

    // Disable UPE for ALL order-pay pages (not just partial payments)
    // This is safer as UPE has issues with order-pay in general
    if (is_array($settings)) {
        $settings['upe_checkout_experience_enabled'] = 'no';
        error_log("[AA Stripe Fix] PART 9: Disabled UPE via URL detection");
    }

    return $settings;
}, 1); // Priority 1 - run as early as possible

// Method 1: Filter Stripe settings to disable UPE on order-pay
add_filter('option_woocommerce_stripe_settings', function($settings) {
    // Only modify on order-pay pages
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-pay')) {
        return $settings;
    }

    global $wp;
    $order_id = absint($wp->query_vars['order-pay'] ?? 0);

    if (!$order_id) {
        return $settings;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return $settings;
    }

    // Check if this is a partial payment situation
    $paid = floatval($order->get_meta('_aa_total_paid') ?: 0);
    $total = floatval($order->get_total('edit'));

    if ($paid > 0 && $paid < $total) {
        // Disable UPE for partial payments
        $settings['upe_checkout_experience_enabled'] = 'no';
        error_log("[AA Stripe Fix] PART 9: Disabled UPE for order #{$order_id} (partial payment: {$paid}/{$total})");
    }

    return $settings;
}, 99999);

// Method 2: REMOVED - pre_option filters with anonymous functions cause infinite loops
// The option_woocommerce_stripe_settings filter (Method 0 and 1) should be sufficient

// Method 3: Disable UPE via class filter if available
add_filter('wc_stripe_upe_is_enabled', function($is_enabled) {
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-pay')) {
        return $is_enabled;
    }

    global $wp;
    $order_id = absint($wp->query_vars['order-pay'] ?? 0);

    if ($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $paid = floatval($order->get_meta('_aa_total_paid') ?: 0);
            $total = floatval($order->get_total('edit'));

            if ($paid > 0 && $paid < $total) {
                error_log("[AA Stripe Fix] PART 9: wc_stripe_upe_is_enabled returning FALSE for order #{$order_id}");
                return false;
            }
        }
    }

    return $is_enabled;
}, 99999);

// Method 4: Force legacy mode via JavaScript params
add_filter('wc_stripe_params', function($params) {
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-pay')) {
        return $params;
    }

    global $wp;
    $order_id = absint($wp->query_vars['order-pay'] ?? 0);

    if ($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $paid = floatval($order->get_meta('_aa_total_paid') ?: 0);
            $total = floatval($order->get_total('edit'));

            if ($paid > 0 && $paid < $total) {
                // Set params that indicate legacy mode
                $params['isUPEEnabled'] = 'no';
                $params['use_legacy'] = true;
                error_log("[AA Stripe Fix] PART 9: Set legacy params for order #{$order_id}");
            }
        }
    }

    return $params;
}, 99999);

// Method 5: Dequeue UPE scripts and enqueue legacy scripts on order-pay
add_action('wp_enqueue_scripts', function() {
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-pay')) {
        return;
    }

    global $wp;
    $order_id = absint($wp->query_vars['order-pay'] ?? 0);

    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $paid = floatval($order->get_meta('_aa_total_paid') ?: 0);
    $total = floatval($order->get_total('edit'));

    if ($paid > 0 && $paid < $total) {
        // Dequeue UPE scripts
        wp_dequeue_script('wc-stripe-upe-classic');
        wp_dequeue_script('wc-stripe-upe-blocks');
        wp_dequeue_script('wc-stripe-payment-request');

        error_log("[AA Stripe Fix] PART 9: Dequeued UPE scripts for order #{$order_id}");
    }
}, 999);

// Method 6: Override gateway settings at runtime
add_action('woocommerce_before_pay_action', function($order) {
    if (!$order) {
        return;
    }

    $paid = floatval($order->get_meta('_aa_total_paid') ?: 0);
    $total = floatval($order->get_total('edit'));

    if ($paid > 0 && $paid < $total) {
        // Try to access Stripe gateway and disable UPE
        $gateways = WC()->payment_gateways()->payment_gateways();

        if (isset($gateways['stripe'])) {
            $stripe = $gateways['stripe'];

            // Try different property names that might control UPE
            if (property_exists($stripe, 'upe_enabled')) {
                $stripe->upe_enabled = false;
            }
            if (property_exists($stripe, 'use_new_checkout_experience')) {
                $stripe->use_new_checkout_experience = false;
            }

            error_log("[AA Stripe Fix] PART 9: Modified Stripe gateway instance for order #{$order->get_id()}");
        }
    }
}, 5);

error_log("[AA Stripe Fix] PART 9 LOADED: UPE disabling filters registered");

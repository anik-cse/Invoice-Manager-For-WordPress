<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MIM_Admin {

    public function __construct() {
        add_action( 'add_meta_boxes',    array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_invoice', array( $this, 'save_invoice_meta' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_filter( 'manage_invoice_posts_columns',       array( $this, 'set_custom_columns' ) );
        add_action( 'manage_invoice_posts_custom_column', array( $this, 'render_custom_column' ), 10, 2 );
        add_filter( 'post_row_actions', array( $this, 'remove_quick_edit' ), 10, 2 );
    }

    /* -------------------------------------------------------------------------
     * Admin Scripts & Styles
     * ---------------------------------------------------------------------- */
    public function enqueue_admin_scripts( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'invoice' ) {
            return;
        }

        // Reuse frontend styles for the meta box form elements
        wp_enqueue_style( 'mim-admin-style', MIM_PLUGIN_URL . 'assets/css/admin.css', array(), MIM_VERSION );
        wp_enqueue_script( 'mim-admin-script', MIM_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), MIM_VERSION, true );
    }

    /* -------------------------------------------------------------------------
     * Custom Columns on Invoice List
     * ---------------------------------------------------------------------- */
    public function set_custom_columns( $columns ) {
        return array(
            'cb'       => $columns['cb'],
            'title'    => __( 'Invoice', 'mir-invoice-manager' ),
            'client'   => __( 'Client', 'mir-invoice-manager' ),
            'amount'   => __( 'Amount', 'mir-invoice-manager' ),
            'status'   => __( 'Status', 'mir-invoice-manager' ),
            'due_date' => __( 'Due Date', 'mir-invoice-manager' ),
            'date'     => __( 'Created', 'mir-invoice-manager' ),
        );
    }

    public function render_custom_column( $column, $post_id ) {
        switch ( $column ) {
            case 'client':
                echo esc_html( get_post_meta( $post_id, '_client_name', true ) );
                break;
            case 'amount':
                $currency = get_post_meta( $post_id, '_payment_currency', true ) ?: 'USD';
                $sym      = MIM_Shortcodes::get_currency_symbol( $currency );
                $total    = (float) get_post_meta( $post_id, '_total', true );
                echo esc_html( $sym . number_format( $total, 2 ) );
                break;
            case 'status':
                $status = get_post_meta( $post_id, '_status', true ) ?: 'Draft';
                $colors = array(
                    'Draft'   => '#888',
                    'Sent'    => '#006678',
                    'Paid'    => '#46b450',
                    'Overdue' => '#dc3232',
                );
                $color = isset( $colors[ $status ] ) ? $colors[ $status ] : '#888';
                echo '<span style="color:' . esc_attr( $color ) . '; font-weight:600;">' . esc_html( $status ) . '</span>';
                break;
            case 'due_date':
                $due = get_post_meta( $post_id, '_due_date', true );
                echo $due ? esc_html( $due ) : '&mdash;';
                break;
        }
    }

    public function remove_quick_edit( $actions, $post ) {
        if ( $post->post_type === 'invoice' ) {
            unset( $actions['inline hide-if-no-js'] ); // Remove Quick Edit
        }
        return $actions;
    }

    /* -------------------------------------------------------------------------
     * Meta Boxes
     * ---------------------------------------------------------------------- */
    public function register_meta_boxes() {
        add_meta_box(
            'mim_invoice_details',
            __( 'Invoice Details', 'mir-invoice-manager' ),
            array( $this, 'render_meta_box' ),
            'invoice',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'mim_save_meta_' . $post->ID, 'mim_meta_nonce' );

        $client_name      = get_post_meta( $post->ID, '_client_name', true );
        $client_email     = get_post_meta( $post->ID, '_client_email', true );
        $client_phone     = get_post_meta( $post->ID, '_client_phone', true );
        $client_addr      = get_post_meta( $post->ID, '_client_addr', true );

        $sender_name      = get_post_meta( $post->ID, '_sender_name', true )  ?: 'Mir Monoarul Alam';
        $sender_email     = get_post_meta( $post->ID, '_sender_email', true ) ?: 'anik_cse@live.com';
        $sender_phone     = get_post_meta( $post->ID, '_sender_phone', true ) ?: '01713289118';
        $sender_addr      = get_post_meta( $post->ID, '_sender_addr', true )  ?: 'Dhaka, Bangladesh';

        $payment_method   = get_post_meta( $post->ID, '_payment_method', true );
        $payment_currency = get_post_meta( $post->ID, '_payment_currency', true ) ?: 'USD';
        $logo_url         = get_post_meta( $post->ID, '_logo_url', true );
        $due_date         = get_post_meta( $post->ID, '_due_date', true );
        $notes            = get_post_meta( $post->ID, '_notes', true );
        $status           = get_post_meta( $post->ID, '_status', true ) ?: 'Draft';
        $items            = get_post_meta( $post->ID, '_items', true );
        $total            = get_post_meta( $post->ID, '_total', true );

        if ( empty( $items ) || ! is_array( $items ) ) {
            $items = array( array( 'name' => '', 'type' => 'fixed', 'qty' => 1, 'price' => 0.00 ) );
        }

        $currencies = array( 'USD', 'EUR', 'GBP', 'BDT', 'INR', 'AUD', 'CAD' );
        $statuses   = array( 'Draft', 'Sent', 'Paid', 'Overdue' );
        ?>
        <div class="mim-admin-metabox">

            <div class="mim-admin-row">
                <div class="mim-admin-col">
                    <label><?php esc_html_e( 'Status', 'mir-invoice-manager' ); ?></label>
                    <select name="mim_status">
                        <?php foreach ( $statuses as $s ) : ?>
                            <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>><?php echo esc_html( $s ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mim-admin-col">
                    <label><?php esc_html_e( 'Due Date', 'mir-invoice-manager' ); ?></label>
                    <input type="date" name="mim_due_date" value="<?php echo esc_attr( $due_date ); ?>">
                </div>
                <div class="mim-admin-col">
                    <label><?php esc_html_e( 'Currency', 'mir-invoice-manager' ); ?></label>
                    <select name="mim_payment_currency" id="mim-admin-currency">
                        <?php foreach ( $currencies as $c ) : ?>
                            <option value="<?php echo esc_attr( $c ); ?>" <?php selected( $payment_currency, $c ); ?>><?php echo esc_html( $c ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mim-admin-col">
                    <label><?php esc_html_e( 'Logo URL', 'mir-invoice-manager' ); ?></label>
                    <input type="url" name="mim_logo_url" value="<?php echo esc_url( $logo_url ); ?>" placeholder="https://...">
                </div>
            </div>

            <hr>

            <h4><?php esc_html_e( 'Sender / Business Info', 'mir-invoice-manager' ); ?></h4>
            <div class="mim-admin-row">
                <div class="mim-admin-col">
                    <label><?php esc_html_e( 'Name / Company', 'mir-invoice-manager' ); ?></label>
                    <input type="text" name="mim_sender_name" value="<?php echo esc_attr( $sender_name ); ?>">
                </div>
                <div class="mim-admin-col">
                    <label><?php esc_html_e( 'Email', 'mir-invoice-manager' ); ?></label>
                    <input type="email" name="mim_sender_email" value="<?php echo esc_attr( $sender_email ); ?>">
                </div>
                <div class="mim-admin-col">
                    <label><?php esc_html_e( 'Phone', 'mir-invoice-manager' ); ?></label>
                    <input type="text" name="mim_sender_phone" value="<?php echo esc_attr( $sender_phone ); ?>">
                </div>
            </div>
            <div class="mim-admin-row">
                <div class="mim-admin-col" style="flex:1">
                    <label><?php esc_html_e( 'Address', 'mir-invoice-manager' ); ?></label>
                    <textarea name="mim_sender_addr" rows="2"><?php echo esc_textarea( $sender_addr ); ?></textarea>
                </div>
            </div>

            <hr>

            <h4><?php esc_html_e( 'Client Details', 'mir-invoice-manager' ); ?></h4>
            <div class="mim-admin-row">
                <div class="mim-admin-col">
                    <label><?php esc_html_e( 'Client Name', 'mir-invoice-manager' ); ?> *</label>
                    <input type="text" name="mim_client_name" value="<?php echo esc_attr( $client_name ); ?>" required>
                </div>
                <div class="mim-admin-col">
                    <label><?php esc_html_e( 'Client Email', 'mir-invoice-manager' ); ?> *</label>
                    <input type="email" name="mim_client_email" value="<?php echo esc_attr( $client_email ); ?>" required>
                </div>
                <div class="mim-admin-col">
                    <label><?php esc_html_e( 'Client Phone', 'mir-invoice-manager' ); ?></label>
                    <input type="text" name="mim_client_phone" value="<?php echo esc_attr( $client_phone ); ?>">
                </div>
            </div>
            <div class="mim-admin-row">
                <div class="mim-admin-col" style="flex:1">
                    <label><?php esc_html_e( 'Client Address', 'mir-invoice-manager' ); ?></label>
                    <textarea name="mim_client_addr" rows="2"><?php echo esc_textarea( $client_addr ); ?></textarea>
                </div>
            </div>

            <hr>

            <h4><?php esc_html_e( 'Payment Info', 'mir-invoice-manager' ); ?></h4>
            <div class="mim-admin-row">
                <div class="mim-admin-col" style="flex:1">
                    <label><?php esc_html_e( 'Payment Method / Instructions', 'mir-invoice-manager' ); ?></label>
                    <input type="text" name="mim_payment_method" value="<?php echo esc_attr( $payment_method ); ?>" placeholder="e.g. Bank Transfer...">
                </div>
            </div>

            <hr>

            <h4><?php esc_html_e( 'Invoice Items', 'mir-invoice-manager' ); ?></h4>
            <table class="mim-admin-items-table" id="mim-admin-items-table" width="100%">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Description', 'mir-invoice-manager' ); ?></th>
                        <th width="12%"><?php esc_html_e( 'Type', 'mir-invoice-manager' ); ?></th>
                        <th width="10%"><?php esc_html_e( 'Qty/Hrs', 'mir-invoice-manager' ); ?></th>
                        <th width="15%"><?php esc_html_e( 'Rate/Price', 'mir-invoice-manager' ); ?></th>
                        <th width="15%"><?php esc_html_e( 'Total', 'mir-invoice-manager' ); ?></th>
                        <th width="5%"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $items as $index => $item ) : ?>
                    <tr class="mim-admin-item-row">
                        <td><input type="text" name="mim_items[<?php echo $index; ?>][name]" value="<?php echo esc_attr( $item['name'] ); ?>" class="widefat mim-admin-item-name"></td>
                        <td>
                            <select name="mim_items[<?php echo $index; ?>][type]" class="mim-admin-item-type">
                                <option value="fixed"  <?php selected( $item['type'] ?? 'fixed', 'fixed' ); ?>><?php esc_html_e( 'Fixed', 'mir-invoice-manager' ); ?></option>
                                <option value="hourly" <?php selected( $item['type'] ?? 'fixed', 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'mir-invoice-manager' ); ?></option>
                            </select>
                        </td>
                        <td><input type="number" step="0.01" min="0" name="mim_items[<?php echo $index; ?>][qty]" value="<?php echo esc_attr( $item['qty'] ); ?>" class="small-text mim-admin-item-qty"></td>
                        <td><input type="number" step="0.01" min="0" name="mim_items[<?php echo $index; ?>][price]" value="<?php echo esc_attr( $item['price'] ); ?>" class="small-text mim-admin-item-price"></td>
                        <td class="mim-admin-line-total"><?php echo esc_html( number_format( (float)$item['qty'] * (float)$item['price'], 2 ) ); ?></td>
                        <td><button type="button" class="button mim-admin-remove-item">✕</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" id="mim-admin-add-item"><?php esc_html_e( '+ Add Item', 'mir-invoice-manager' ); ?></button>
            </p>

            <div style="text-align:right; font-size:18px; font-weight:700; padding:10px 0; border-top:2px solid #eee; margin-top:10px;">
                <?php esc_html_e( 'Grand Total: ', 'mir-invoice-manager' ); ?>
                <span id="mim-admin-grand-total"><?php echo esc_html( number_format( (float)$total, 2 ) ); ?></span>
            </div>

            <hr>

            <h4><?php esc_html_e( 'Notes', 'mir-invoice-manager' ); ?></h4>
            <textarea name="mim_notes" rows="4" class="widefat"><?php echo esc_textarea( $notes ); ?></textarea>

        </div>
        <?php
    }

    /* -------------------------------------------------------------------------
     * Save Meta Box Data
     * ---------------------------------------------------------------------- */
    public function save_invoice_meta( $post_id, $post ) {
        // Nonce check
        if ( ! isset( $_POST['mim_meta_nonce'] ) || ! wp_verify_nonce( $_POST['mim_meta_nonce'], 'mim_save_meta_' . $post_id ) ) {
            return;
        }
        // Autosave guard
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        // Capability check
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Sanitize & save all fields
        $fields = array(
            '_client_name'      => sanitize_text_field( $_POST['mim_client_name'] ?? '' ),
            '_client_email'     => sanitize_email( $_POST['mim_client_email'] ?? '' ),
            '_client_phone'     => sanitize_text_field( $_POST['mim_client_phone'] ?? '' ),
            '_client_addr'      => sanitize_textarea_field( $_POST['mim_client_addr'] ?? '' ),
            '_sender_name'      => sanitize_text_field( $_POST['mim_sender_name'] ?? '' ),
            '_sender_email'     => sanitize_email( $_POST['mim_sender_email'] ?? '' ),
            '_sender_phone'     => sanitize_text_field( $_POST['mim_sender_phone'] ?? '' ),
            '_sender_addr'      => sanitize_textarea_field( $_POST['mim_sender_addr'] ?? '' ),
            '_payment_method'   => sanitize_text_field( $_POST['mim_payment_method'] ?? '' ),
            '_payment_currency' => sanitize_text_field( $_POST['mim_payment_currency'] ?? 'USD' ),
            '_logo_url'         => esc_url_raw( $_POST['mim_logo_url'] ?? '' ),
            '_due_date'         => sanitize_text_field( $_POST['mim_due_date'] ?? '' ),
            '_notes'            => sanitize_textarea_field( $_POST['mim_notes'] ?? '' ),
            '_status'           => sanitize_text_field( $_POST['mim_status'] ?? 'Draft' ),
        );

        foreach ( $fields as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }

        // Items
        $raw_items       = isset( $_POST['mim_items'] ) && is_array( $_POST['mim_items'] ) ? wp_unslash( $_POST['mim_items'] ) : array();
        $sanitized_items = array();
        $total           = 0;

        foreach ( $raw_items as $item ) {
            $name  = sanitize_text_field( $item['name'] ?? '' );
            $type  = sanitize_text_field( $item['type'] ?? 'fixed' );
            $qty   = floatval( $item['qty'] ?? 0 );
            $price = floatval( $item['price'] ?? 0 );

            if ( $name && $qty > 0 ) {
                $sanitized_items[] = compact( 'name', 'type', 'qty', 'price' );
                $total += $qty * $price;
            }
        }

        update_post_meta( $post_id, '_items', $sanitized_items );
        update_post_meta( $post_id, '_total', $total );

        // Update post title auto-generate
        remove_action( 'save_post_invoice', array( $this, 'save_invoice_meta' ), 10 );
        wp_update_post( array(
            'ID'         => $post_id,
            'post_title' => sprintf(
                /* translators: 1: Client name, 2: date */
                __( 'Invoice - %1$s - %2$s', 'mir-invoice-manager' ),
                sanitize_text_field( $_POST['mim_client_name'] ?? 'Client' ),
                wp_date( 'Y-m-d' )
            ),
        ) );
        add_action( 'save_post_invoice', array( $this, 'save_invoice_meta' ), 10, 2 );
    }
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MIM_PDF {
    public function __construct() {
        add_action( 'init', array( $this, 'generate_pdf' ) );
    }

    public function generate_pdf() {
        if ( isset( $_GET['mim_download_pdf'] ) && isset( $_GET['mim_nonce'] ) ) {
            $invoice_id = intval( $_GET['mim_download_pdf'] );
            
            if ( ! wp_verify_nonce( $_GET['mim_nonce'], 'mim_pdf_' . $invoice_id ) || ! is_user_logged_in() || ! MIM_Security::user_owns_invoice( $invoice_id ) ) {
                wp_die( esc_html__( 'Unauthorized access or invalid invoice.', 'mir-invoice-manager' ) );
            }

            $this->output_pdf( $invoice_id );
        }
    }

    private function output_pdf( $invoice_id ) {
        // Lightweight PDF Generation using basic output for demonstration.
        // In a true production environment without external libraries, we generate a print-ready HTML view,
        // or require a library like TCPDF via Composer. To remain standalone, we output a printable HTML page 
        // that triggers window.print(). For actual PDF file generation, an external dependency is strictly required.

        $client_name  = get_post_meta( $invoice_id, '_client_name', true );
        $client_email = get_post_meta( $invoice_id, '_client_email', true );
        $client_phone = get_post_meta( $invoice_id, '_client_phone', true );
        $client_addr  = get_post_meta( $invoice_id, '_client_addr', true );

        $sender_name  = get_post_meta( $invoice_id, '_sender_name', true );
        $sender_email = get_post_meta( $invoice_id, '_sender_email', true );
        $sender_phone = get_post_meta( $invoice_id, '_sender_phone', true );
        $sender_addr  = get_post_meta( $invoice_id, '_sender_addr', true );

        $payment_method   = get_post_meta( $invoice_id, '_payment_method', true );
        $payment_currency = get_post_meta( $invoice_id, '_payment_currency', true );
        $currency_sym     = MIM_Shortcodes::get_currency_symbol( $payment_currency ? $payment_currency : 'USD' );

        $logo_url     = get_post_meta( $invoice_id, '_logo_url', true );
        $due_date     = get_post_meta( $invoice_id, '_due_date', true );
        $notes        = get_post_meta( $invoice_id, '_notes', true );
        $items        = get_post_meta( $invoice_id, '_items', true );
        $total        = get_post_meta( $invoice_id, '_total', true );
        $status       = get_post_meta( $invoice_id, '_status', true );
        $date         = get_the_date( '', $invoice_id );

        if ( ! is_array( $items ) ) $items = array();

        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title><?php echo esc_html__( 'Invoice #', 'mir-invoice-manager' ) . $invoice_id; ?></title>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 40px; color: #333; max-width: 800px; margin: 0 auto; }
                .invoice-header { border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; }
                .invoice-title { font-size: 28px; font-weight: bold; color: #006678; }
                .invoice-meta { font-size: 14px; text-align: right; }
                .client-details, .sender-details { width: 45%; margin-bottom: 30px; }
                .client-details h3, .sender-details h3 { margin-top: 0; color: #555; font-size: 16px; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background-color: #f9f9f9; font-weight: bold; }
                .total-row td { font-weight: bold; font-size: 18px; text-align: right; border-top: 2px solid #333; }
                .status { top: 40px; right: 40px; padding: 10px 20px; font-weight: bold; text-transform: uppercase; border: 2px solid; display: inline-block; border-radius: 5px; }
                .status-draft { color: #888; border-color: #888; }
                .status-sent { color: #006678; border-color: #006678; }
                .status-paid { color: #46b450; border-color: #46b450; }
                .status-overdue { color: #dc3232; border-color: #dc3232; }
                .notes { background: #f9f9f9; padding: 15px; border-left: 4px solid #006678; }
                @media print { .no-print { display: none; } }
            </style>
        </head>
        <body>
            <div class="no-print" style="margin-bottom: 20px; text-align: right;">
                <button onclick="window.print();" style="padding: 10px 20px; background: #006678; color: #fff; border: none; cursor: pointer; border-radius: 4px;"><?php esc_html_e( 'Download / Print PDF', 'mir-invoice-manager' ); ?></button>
            </div>
            
            <div class="invoice-header">
                <div>
                    <div class="invoice-title"><?php esc_html_e( 'INVOICE', 'mir-invoice-manager' ); ?></div>
                    <div style="margin-top: 5px; color: #777;">
                        <?php if ( $logo_url ) : ?>
                            <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo" style="max-width: 254px; max-height: 135px;">
                        <?php else : ?>
                            [Logo Placeholder]
                        <?php endif; ?>
                    </div>
                </div>
                <div class="invoice-meta">
                    <strong><?php esc_html_e( 'Invoice #', 'mir-invoice-manager' ); ?></strong><?php echo esc_html( $invoice_id ); ?><br>
                    <strong><?php esc_html_e( 'Date:', 'mir-invoice-manager' ); ?></strong> <?php echo esc_html( $date ); ?><br>
                    <strong><?php esc_html_e( 'Due Date:', 'mir-invoice-manager' ); ?></strong> <?php echo esc_html( $due_date ); ?>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px;">
                <!-- Sender Info -->
                <div class="sender-details">
                    <?php if ( $sender_name || $sender_email || $sender_phone || $sender_addr ) : ?>
                    <h3><?php esc_html_e( 'From:', 'mir-invoice-manager' ); ?></h3>
                    <?php if ( $sender_name ) echo '<strong>' . esc_html( $sender_name ) . '</strong><br>'; ?>
                    <?php if ( $sender_email ) echo esc_html( $sender_email ) . '<br>'; ?>
                    <?php if ( $sender_phone ) echo esc_html( $sender_phone ) . '<br>'; ?>
                    <?php if ( $sender_addr ) echo nl2br( esc_html( $sender_addr ) ) . '<br>'; ?>
                    <?php endif; ?>
                </div>

                <div class="status status-<?php echo esc_attr( strtolower( $status ) ); ?>" style="position: static;">
                    <?php echo esc_html( strtolower( $status ) === 'paid' ? 'PAID' : 'UNPAID' ); ?>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px;">
                <!-- Client Info -->
                <div class="client-details">
                    <h3><?php esc_html_e( 'Billed To:', 'mir-invoice-manager' ); ?></h3>
                    <strong><?php echo esc_html( $client_name ); ?></strong><br>
                    <?php echo esc_html( $client_email ); ?><br>
                    <?php if ( $client_phone ) echo esc_html( $client_phone ) . '<br>'; ?>
                    <?php if ( $client_addr ) echo nl2br( esc_html( $client_addr ) ) . '<br>'; ?>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Description', 'mir-invoice-manager' ); ?></th>
                        <th width="15%"><?php esc_html_e( 'Type', 'mir-invoice-manager' ); ?></th>
                        <th width="15%"><?php esc_html_e( 'Qty/Hrs', 'mir-invoice-manager' ); ?></th>
                        <th width="20%"><?php esc_html_e( 'Rate/Price', 'mir-invoice-manager' ); ?></th>
                        <th width="20%"><?php esc_html_e( 'Total', 'mir-invoice-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $items as $item ) : ?>
                    <tr>
                        <td><?php echo esc_html( $item['name'] ); ?></td>
                        <td><?php echo esc_html( ucfirst( $item['type'] ?? 'fixed' ) ); ?></td>
                        <td><?php echo esc_html( $item['qty'] ); ?></td>
                        <td><?php echo esc_html( $currency_sym . number_format( (float) $item['price'], 2 ) ); ?></td>
                        <td><?php echo esc_html( $currency_sym . number_format( $item['qty'] * $item['price'], 2 ) ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="4"><?php esc_html_e( 'Grand Total:', 'mir-invoice-manager' ); ?></td>
                        <td><?php echo esc_html( $currency_sym . number_format( (float) $total, 2 ) ); ?></td>
                    </tr>
                </tbody>
            </table>

            <?php if ( $payment_method ) : ?>
            <div class="notes" style="margin-bottom: 20px; border-color: #46b450;">
                <strong><?php esc_html_e( 'Payment Method:', 'mir-invoice-manager' ); ?></strong><br>
                <?php echo nl2br( esc_html( $payment_method ) ); ?>
            </div>
            <?php endif; ?>

            <?php if ( $notes ) : ?>
            <div class="notes">
                <strong><?php esc_html_e( 'Notes:', 'mir-invoice-manager' ); ?></strong><br>
                <?php echo nl2br( esc_html( $notes ) ); ?>
            </div>
            <?php endif; ?>
            
            <script>
                // Automatically open print dialog on load
                window.onload = function() { window.print(); }
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

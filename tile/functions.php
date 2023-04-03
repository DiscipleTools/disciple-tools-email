<?php


add_filter( 'dt_list_action_menu_items', function ( $list_menu_items, $post_type ){
    if ( $post_type === 'contacts' ){
        $list_menu_items['send_email'] = [
            'label' => __( 'Send Email', 'disciple_tools' ),
            'icon' => get_template_directory_uri() . '/dt-assets/images/email.svg',
            'section_id' => 'send_email_modal',
            'show_list_checkboxes' => true,
        ];
    }
    return $list_menu_items;
}, 10, 2 );

add_action( 'dt_list_exports_menu_items', function ( $post_type ){
    if ( $post_type === 'contacts' ) :?>
        <a id='send_email_list_menu'><?php esc_html_e( 'Email Filtered List', 'disciple-tools-list-exports' ) ?></a><br>

        <div id='send_email_modal' class='large reveal' data-reveal data-v-offset='10px'>
            <span class='section-header'><?php esc_html_e( 'List Export Help', 'disciple-tools-list-exports' ) ?></span>
            <hr>
            <div class="grid-x">
                <div class="cell">
                    <p><strong>Query</strong></p>
                    <p id="email-query-view"></p>

                </div>
                <div class="cell">
                    <p><strong>From Email</strong></p>
                    <select>
                        <option value="">Select Email to Send from</option>
                    </select>

                </div>
                <div class="cell">
                    <p><strong>Send To Email</strong></p>
                    <p id="crm-email-address"></p>
                    <p id="crm-email-fw"></p>

                </div>

            </div>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                $('#send_email_list_menu').on('click', function () {
                    $('#send_email_modal').foundation('open');

                    let query = {}
                    let last_view_cookie = window.SHAREDFUNCTIONS.getCookie('last_view');
                    console.log(last_view_cookie);
                    if (last_view_cookie) {
                        let filter = JSON.parse(last_view_cookie);
                        if (filter.query) {
                            query = filter.query;
                        }
                    }


                    $('#email-query-view').html( query ? JSON.stringify(query) : 'No query' );

                    makeRequest('GET', 'query-stats', {query}, 'dt-email/v1/').then(response => {

                        $('#crm-email-address').html(response.email_address);
                        $('#crm-email-fw').html('The email will be forwarded to the ' + response.with_email + ' contacts with an email address that match this filter.')
                    })
                });
            });
        </script>

    <?php endif;



}, 10, 1 );


add_action( 'dt_list_action_section', function ( $post_type ){
    ?>
    <div class='reveal' id='send_email_modal' data-reveal data-reset-on-close>

        <h3><?php esc_html_e( 'Email List', 'disciple_tools' ) ?></h3>


        <div class="grid-x">
            <a class="button reveal-after-record-create" id="go-to-record" style="display: none">
                <?php esc_html_e( 'Edit New Record', 'disciple_tools' ) ?>
            </a>
            <button class="button reveal-after-record-create button-cancel clear" data-close type="button"
                    id="create-record-return" style="display: none">
                <?php
                echo esc_html( sprintf( _x( 'Back to %s', 'back to record', 'disciple_tools' ), DT_Posts::get_label_for_post_type( get_post_type( get_the_ID() ), true ) ) );
                ?>
            </button>
            <button class="close-button" data-close aria-label="<?php esc_html_e( 'Close', 'disciple_tools' ); ?>"
                    type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>
    <?php
}, 20, 3 );

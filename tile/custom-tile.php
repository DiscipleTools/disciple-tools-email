<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Disciple_Tools_Email_Tile
{
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct(){
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields' ], 1, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_add_section' ], 30, 2 );
        add_filter( 'dt_comments_additional_sections', [ $this, 'dt_comments_additional_sections' ], 10, 2 );

   }

    public function dt_comments_additional_sections( $sections, $post_type ){
        if ( $post_type === 'contacts' ){
            $sections[] = [
                'key' => 'email',
                'label' => 'Email',
            ];
        }
        return $sections;
    }

    /**
     * This function registers a new tile to a specific post type
     *
     * @todo Set the post-type to the target post-type (i.e. contacts, groups, trainings, etc.)
     * @todo Change the tile key and tile label
     *
     * @param $tiles
     * @param string $post_type
     * @return mixed
     */
    public function dt_details_additional_tiles( $tiles, $post_type = '' ) {
        if ( $post_type === 'contacts' || $post_type === 'dt_email' ){
            $tiles['disciple_tools_email'] = [ 'label' => __( 'D.T Email', 'disciple-tools-email' ) ];
        }
        return $tiles;
    }

    /**
     * @param array $fields
     * @param string $post_type
     * @return array
     */
    public function dt_custom_fields( array $fields, string $post_type = '' ) {

        return $fields;
    }

    public function dt_add_section( $section, $post_type ) {

        if ( ( $post_type === 'contacts' || $post_type === 'dt_email' ) && $section === 'disciple_tools_email' ){

            $this_post = DT_Posts::get_post( $post_type, get_the_ID() );

            $email = '';
            if ( isset( $this_post['contact_email'][0]['value'] ) ){
                $email = $this_post['contact_email'][0]['value'];
            }
            ?>


            <div class="cell small-12 medium-4">
                <button id="dt-send-email-open" class="button">SEND EMAIL</button>
            </div>

            <script>
                jQuery(document).ready(function($) {
                    $('#dt-send-email-open').on('click', function (e) {
                        $('#modal-large-title').empty().html(`Send Email`);
                        $('#modal-large-content').empty().html(`
                            <label>Email Address
                                <input type="text" id="dt-send-email-address" value="<?php echo esc_html( $email ) ?>">
                            </label>
                            <label>Subject
                                <input type="text" id="dt-send-email-subject" value="">
                            </label>
                            <label>Message
                                <textarea id="dt-send-email-message" rows="4"></textarea>
                            </label>
                            <button class="button loader" id="dt-send-email-send">Send</button>
                        `)
                        $('#modal-large').foundation('open');
                    })
                    $(document).on('click', '#dt-send-email-send', function (){
                        let email = $('#dt-send-email-address').val();
                        let subject = $('#dt-send-email-subject').val();
                        let message = $('#dt-send-email-message').val();
                        $(this).addClass('loading');
                        if ( !email || !subject || !message ){
                            alert('Please fill out all fields');
                            return;
                        }
                        $.ajax({
                            type: "POST",
                            url: '<?php echo esc_url( site_url( '/wp-json/dt-email/v1/send' ) ) ?>',
                            data: {
                                email: email,
                                subject: subject,
                                message: message,
                                contact_id: <?php echo esc_html( get_the_ID() ) ?>
                            },
                            beforeSend: function (xhr) {
                                xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_html( wp_create_nonce( 'wp_rest' ) ) ?>');
                            },
                            success: function (response) {
                                console.log(response);
                                $('#modal-large').foundation('close');
                                $(this).removeClass('loading');
                            },
                            error: function (err) {
                                console.log(err);
                            }
                        });
                    })
                })
            </script>

        <?php }
    }
}
Disciple_Tools_Email_Tile::instance();
